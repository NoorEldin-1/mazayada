<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The single source of truth for how big an auction media upload may be.
 *
 * The form used to advertise a 50 MB video while PHP was configured with
 * post_max_size = 40M. PHP discards an over-sized request body *before* Laravel
 * boots: $_POST arrives empty, so the CSRF token is missing and the admin — who
 * just spent a minute uploading — got a bare "419 Page Expired" and lost the
 * whole form. Nothing in the application could catch it, because the rules said
 * one thing and the runtime enforced another.
 *
 * This class reconciles the two. Every declared cap is clamped to what PHP will
 * actually accept, and the clamped values drive the validation rules, the hint
 * text under each field and the client-side guards alike — so the number the
 * admin reads is the number that will really work.
 */
class UploadLimits
{
    /** Head-room (bytes) reserved for the non-file part of the form: text fields, boundaries, cookies. */
    private const OVERHEAD_BYTES = 512 * 1024;

    // ---------------------------------------------------------------- PHP ini

    /** post_max_size in bytes. 0 means "unlimited" — PHP treats it that way too. */
    public function postMaxBytes(): int
    {
        return self::parseIniBytes((string) ini_get('post_max_size'));
    }

    /** upload_max_filesize in bytes (per individual file). */
    public function uploadMaxBytes(): int
    {
        return self::parseIniBytes((string) ini_get('upload_max_filesize'));
    }

    /** max_file_uploads — the hard ceiling on how many files one request may carry. */
    public function maxFileUploads(): int
    {
        $value = (int) ini_get('max_file_uploads');

        return $value > 0 ? $value : 20;
    }

    /**
     * The largest single file PHP will accept, i.e. min(upload_max_filesize,
     * post_max_size). A file bigger than post_max_size can never arrive whole
     * no matter what upload_max_filesize says.
     */
    public function perFileCeilingBytes(): int
    {
        $post = $this->postMaxBytes();
        $upload = $this->uploadMaxBytes();

        if ($post <= 0) {
            return $upload > 0 ? $upload : PHP_INT_MAX;
        }
        if ($upload <= 0) {
            return $post;
        }

        return min($post, $upload);
    }

    // ------------------------------------------------------- Effective limits

    /** Effective per-photo cap in KILOBYTES — what the `max:` validation rule receives. */
    public function photoMaxKb(): int
    {
        $declared = (int) config('mazayada.media.photo_max_kb', 4096);

        return max(1, (int) min($declared, intdiv($this->perFileCeilingBytes(), 1024)));
    }

    public function photoMaxBytes(): int
    {
        return $this->photoMaxKb() * 1024;
    }

    /** Effective video cap in KILOBYTES. */
    public function videoMaxKb(): int
    {
        $declared = (int) config('mazayada.media.video_max_kb', 51200);
        // The video shares the request with the text fields, so it can never
        // occupy the whole post_max_size budget.
        $budget = $this->perFileCeilingBytes();
        if ($this->postMaxBytes() > 0) {
            $budget = min($budget, max(1024, $this->postMaxBytes() - self::OVERHEAD_BYTES));
        }

        return max(1, (int) min($declared, intdiv($budget, 1024)));
    }

    public function videoMaxBytes(): int
    {
        return $this->videoMaxKb() * 1024;
    }

    /**
     * How many photos may be attached at once. Clamped by max_file_uploads,
     * leaving one slot for the video.
     */
    public function maxPhotos(): int
    {
        $declared = (int) config('mazayada.media.max_photos', 10);

        return max(1, min($declared, $this->maxFileUploads() - 1));
    }

    public function videoMaxSeconds(): int
    {
        return max(1, (int) config('mazayada.media.video_max_seconds', 120));
    }

    public function photoMaxDimension(): int
    {
        return max(100, (int) config('mazayada.media.photo_max_dimension', 10000));
    }

    /**
     * Total bytes of file payload the client may send in one submit. The client
     * checks its selection against this and refuses to start an upload that PHP
     * would throw away — turning a 419-after-a-minute into an instant, readable
     * message.
     */
    public function totalPayloadBytes(): int
    {
        $post = $this->postMaxBytes();

        return $post > 0 ? max(1024, $post - self::OVERHEAD_BYTES) : PHP_INT_MAX;
    }

    // ------------------------------------------------------------- Detection

    /**
     * True when PHP silently dropped this request's body because it exceeded
     * post_max_size. The tell is a POST that declares a Content-Length beyond
     * the limit yet arrives with neither form fields nor files.
     *
     * Detecting it is what lets us answer with "your upload was too large"
     * instead of the misleading CSRF failure PHP's truncation produces.
     */
    public function requestWasTruncated(Request $request): bool
    {
        if (! in_array($request->getRealMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            return false;
        }

        $post = $this->postMaxBytes();
        if ($post <= 0) {
            return false;
        }

        $declared = (int) $request->server('CONTENT_LENGTH', 0);
        if ($declared <= $post) {
            return false;
        }

        return count($_POST) === 0 && count($_FILES) === 0;
    }

    // ------------------------------------------------------------ Presentation

    /** Human-readable megabytes for hint text, e.g. 4096 KB → "4". */
    public static function mb(int $bytes): string
    {
        $mb = $bytes / 1048576;

        return rtrim(rtrim(number_format($mb, 1, '.', ''), '0'), '.');
    }

    /**
     * The limit set the client-side guards consume, serialised onto a data-*
     * attribute. Bytes everywhere so JS never has to reproduce the arithmetic.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'maxPhotos' => $this->maxPhotos(),
            'photoMaxBytes' => $this->photoMaxBytes(),
            'photoMimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'photoExtensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'videoMaxBytes' => $this->videoMaxBytes(),
            'videoMimes' => ['video/mp4'],
            'videoMaxSeconds' => $this->videoMaxSeconds(),
            'totalMaxBytes' => $this->totalPayloadBytes(),
        ];
    }

    /**
     * Parse a PHP ini shorthand size ("40M", "512K", "1G", "8388608") to bytes.
     * Returns 0 for an empty/unlimited setting.
     */
    public static function parseIniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
