<?php

namespace App\Services;

use App\Models\Auction;
use App\Support\UploadLimits;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Every write, replacement and deletion of an auction's asset media goes
 * through here.
 *
 * The controller used to call $file->store() in a bare loop right after
 * Auction::create(). That loop had three ways to leave the record wrong:
 * store() returns FALSE on a disk failure and the false was silently imploded
 * into the ';'-joined column as an empty path; a throw halfway through left the
 * already-written files orphaned on disk *and* the auction saved without them;
 * and on edit the "append" path re-checked the 10-photo cap per request only, so
 * ten more photos could be added on every save.
 *
 * The rules enforced here: a batch is all-or-nothing (a partial write is rolled
 * back before the exception surfaces), the cap counts what is already stored,
 * and a delete only ever touches a path that genuinely belongs to this auction.
 */
class AuctionMediaService
{
    /** Separator for the ';'-joined auctions.photos column. */
    private const SEPARATOR = ';';

    public function __construct(private readonly UploadLimits $limits) {}

    private function disk(): Filesystem
    {
        return Storage::disk('public');
    }

    // ------------------------------------------------------------------ Write

    /**
     * Store a batch of uploaded photos and append them to the auction, honouring
     * the total cap across what is already stored.
     *
     * All-or-nothing: if any file fails to write, every file written by this
     * call is removed again and a ValidationException is thrown, so the auction
     * is never left pointing at a half-uploaded gallery.
     *
     * @param  array<int, UploadedFile|mixed>  $files
     * @return array<int, string> the paths written by this call
     *
     * @throws ValidationException
     */
    public function attachPhotos(Auction $auction, array $files): array
    {
        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));

        if ($files === []) {
            return [];
        }

        $existing = $auction->photosArray();
        $max = $this->limits->maxPhotos();

        if (count($existing) + count($files) > $max) {
            throw ValidationException::withMessages([
                'photos' => __('validation.custom.upload.photos_limit', [
                    'max' => $max,
                    'remaining' => max(0, $max - count($existing)),
                ]),
            ]);
        }

        $written = [];

        try {
            foreach ($files as $file) {
                $written[] = $this->put($file, 'auctions/'.$auction->id);
            }
        } catch (Throwable $e) {
            // Roll the batch back before surfacing the failure — a half-written
            // gallery is worse than none, and the admin can simply retry.
            $this->forget($written);

            Log::error('Auction photo upload failed', [
                'auction_id' => $auction->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'photos' => __('validation.custom.upload.store_failed'),
            ]);
        }

        $auction->forceFill([
            'photos' => $this->join(array_merge($existing, $written)),
        ])->save();

        return $written;
    }

    /**
     * Store the single asset video, replacing any previous one.
     *
     * The old file is deleted only AFTER the new one is safely written and the
     * row updated, so a failed upload can never leave the auction with no video
     * at all.
     *
     * @throws ValidationException
     */
    public function replaceVideo(Auction $auction, UploadedFile $file): string
    {
        $previous = $auction->video;

        try {
            $path = $this->put($file, 'auctions/'.$auction->id.'/video');
        } catch (Throwable $e) {
            Log::error('Auction video upload failed', [
                'auction_id' => $auction->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'video' => __('validation.custom.upload.store_failed'),
            ]);
        }

        $auction->forceFill(['video' => $path])->save();

        if ($previous && $previous !== $path) {
            $this->forget([$previous]);
        }

        return $path;
    }

    // ----------------------------------------------------------------- Delete

    /**
     * Remove one photo from the auction.
     *
     * The path is matched against the auction's own stored list before anything
     * touches the disk — the value arrives from a form field, and an unchecked
     * delete would let a crafted request erase any file on the public disk.
     */
    public function deletePhoto(Auction $auction, string $path): bool
    {
        $existing = $auction->photosArray();

        if (! in_array($path, $existing, true)) {
            return false;
        }

        $remaining = array_values(array_filter($existing, fn (string $p) => $p !== $path));

        $auction->forceFill(['photos' => $this->join($remaining)])->save();
        $this->forget([$path]);

        return true;
    }

    public function deleteVideo(Auction $auction): bool
    {
        if (! $auction->video) {
            return false;
        }

        $path = $auction->video;
        $auction->forceFill(['video' => null])->save();
        $this->forget([$path]);

        return true;
    }

    /**
     * Drop every media file belonging to an auction being deleted. Without this
     * each removed auction left its photos and video behind forever, since the
     * only pointer to them was the row itself.
     */
    public function purge(Auction $auction): void
    {
        try {
            $this->disk()->deleteDirectory('auctions/'.$auction->id);
        } catch (Throwable $e) {
            // Deleting the auction must not fail because of leftover files;
            // the orphans are logged for a later sweep instead.
            Log::warning('Auction media purge failed', [
                'auction_id' => $auction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // --------------------------------------------------------------- Internals

    /**
     * Write one uploaded file and return its path, turning every failure mode
     * into an exception.
     *
     * store() answers FALSE — not an exception — when the disk write fails, and
     * that false used to be cast into the path list as an empty string. A path
     * containing the ';' separator would likewise corrupt the joined column.
     *
     * @throws \RuntimeException
     */
    private function put(UploadedFile $file, string $directory): string
    {
        if (! $file->isValid()) {
            throw new \RuntimeException('Upload error '.$file->getError().' for '.$file->getClientOriginalName());
        }

        $path = $file->store($directory, 'public');

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Storage returned no path for '.$file->getClientOriginalName());
        }

        if (str_contains($path, self::SEPARATOR)) {
            $this->forget([$path]);

            throw new \RuntimeException('Generated path contains the list separator: '.$path);
        }

        return $path;
    }

    /**
     * Best-effort delete. A file that is already gone is not an error, and a
     * failed cleanup must never mask the operation that triggered it.
     *
     * @param  array<int, string>  $paths
     */
    private function forget(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                $this->disk()->delete($path);
            } catch (Throwable $e) {
                Log::warning('Auction media cleanup failed', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** Join paths into the ';'-separated column value; an empty list stores NULL. */
    private function join(array $paths): ?string
    {
        $paths = array_values(array_unique(array_filter($paths)));

        return $paths === [] ? null : implode(self::SEPARATOR, $paths);
    }
}
