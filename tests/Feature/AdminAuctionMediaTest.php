<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AuctionStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\User;
use App\Support\UploadLimits;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * Asset media on the admin auction forms: uploading, replacing, capping and
 * deleting photos and the short video.
 *
 * Each test here pins down a way this used to go wrong — an advertised limit PHP
 * would never honour, a batch that half-wrote and left an auction behind, an
 * append path with no total cap, a delete that trusted a client-supplied path.
 */
class AdminAuctionMediaTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->refs();
        Storage::fake('public');
    }

    private ?User $admin = null;

    /** Memoised — several tests act as the admin more than once per test. */
    private function admin(): User
    {
        if ($this->admin) {
            return $this->admin;
        }

        $user = User::create([
            'nin' => '109823041175663777',
            'first_name_ar' => 'مشرف', 'last_name_ar' => 'الوسائط',
            'phone' => '0555123777', 'email' => 'media-admin@example.test',
            'birth_date' => '1985-01-01', 'password' => 'StrongP@ss123',
            'role' => UserRole::SUPER_ADMIN, 'entity_id' => null,
            'kyc_status' => KycStatus::COMPLETE, 'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true, 'email_verified' => true,
        ]);
        $user->assignRole(UserRole::SUPER_ADMIN->value);

        return $this->admin = $user;
    }

    /** Base SALE payload; media keys merge on top. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'wilaya_id' => $this->refWilaya->id,
            'title_ar' => 'مزاد بوسائط',
            'description_ar' => 'وصف',
            'condition' => 'GOOD',
            'auction_type' => 'SALE',
            'opening_price' => 10000,
            'start_time' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ], $overrides);
    }

    /** A DRAFT auction owned by the reference entity, ready to be edited. */
    private function draft(array $overrides = []): Auction
    {
        return $this->makeAuction(array_merge([
            'status' => AuctionStatus::DRAFT,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ], $overrides));
    }

    // ------------------------------------------------------------------ limits

    /**
     * The bug that started all of this: the form advertised a 50 MB video while
     * PHP's post_max_size was 40M, so a "legal" upload was thrown away by PHP
     * before Laravel booted and surfaced as a bare CSRF 419.
     */
    public function test_effective_limits_never_exceed_what_php_accepts(): void
    {
        $limits = new UploadLimits;

        $ceiling = $limits->perFileCeilingBytes();

        $this->assertLessThanOrEqual($ceiling, $limits->photoMaxBytes());
        $this->assertLessThanOrEqual($ceiling, $limits->videoMaxBytes());
        $this->assertLessThanOrEqual($limits->maxFileUploads(), $limits->maxPhotos() + 1);

        if ($limits->postMaxBytes() > 0) {
            $this->assertLessThan($limits->postMaxBytes(), $limits->totalPayloadBytes());
        }
    }

    public function test_ini_shorthand_sizes_are_parsed(): void
    {
        $this->assertSame(40 * 1024 * 1024, UploadLimits::parseIniBytes('40M'));
        $this->assertSame(512 * 1024, UploadLimits::parseIniBytes('512K'));
        $this->assertSame(1024 * 1024 * 1024, UploadLimits::parseIniBytes('1G'));
        $this->assertSame(8388608, UploadLimits::parseIniBytes('8388608'));
        // -1 and "" both mean unlimited; callers treat 0 as "no ceiling".
        $this->assertSame(0, UploadLimits::parseIniBytes('-1'));
        $this->assertSame(0, UploadLimits::parseIniBytes(''));
    }

    /**
     * A body PHP threw away leaves a POST that declares more bytes than
     * post_max_size yet parsed nothing. Recognising that shape is what turns the
     * misleading "419 Page Expired" into "your upload was too large".
     */
    public function test_an_oversized_post_is_recognised_as_truncated(): void
    {
        $limits = new UploadLimits;

        if ($limits->postMaxBytes() <= 0) {
            $this->markTestSkipped('post_max_size is unlimited in this environment.');
        }

        $request = Request::create('/admin/auctions', 'POST');
        $request->server->set('CONTENT_LENGTH', $limits->postMaxBytes() + 1);

        $this->assertTrue($limits->requestWasTruncated($request));
    }

    /** A normal submit must never be mistaken for a truncated one. */
    public function test_a_normal_request_is_not_treated_as_truncated(): void
    {
        $limits = new UploadLimits;

        $small = Request::create('/admin/auctions', 'POST');
        $small->server->set('CONTENT_LENGTH', 1024);
        $this->assertFalse($limits->requestWasTruncated($small));

        $get = Request::create('/admin/auctions/create', 'GET');
        $get->server->set('CONTENT_LENGTH', $limits->postMaxBytes() + 1);
        $this->assertFalse($limits->requestWasTruncated($get));
    }

    /** The rendered hint must quote the effective cap, not the configured wish. */
    public function test_form_states_the_effective_video_limit(): void
    {
        $limits = new UploadLimits;

        $this->actingAs($this->admin())
            ->get(route('admin.auctions.create'))
            ->assertOk()
            ->assertSee(UploadLimits::mb($limits->videoMaxBytes()), false)
            ->assertSee('data-media-limits', false);
    }

    /**
     * The client-side guards are only as good as the payload they read. If the
     * JSON stops parsing they silently switch themselves off and the 419 comes
     * back, so the contract is asserted through the real rendered HTML.
     */
    public function test_the_limits_payload_survives_html_encoding(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.auctions.create'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/data-media-limits="([^"]*)"/', $html, $m));

        $payload = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        $limits = new UploadLimits;

        $this->assertIsArray($payload, 'The media limits attribute is not valid JSON.');
        $this->assertSame($limits->maxPhotos(), $payload['maxPhotos']);
        $this->assertSame($limits->photoMaxBytes(), $payload['photoMaxBytes']);
        $this->assertSame($limits->videoMaxBytes(), $payload['videoMaxBytes']);
        $this->assertSame($limits->videoMaxSeconds(), $payload['videoMaxSeconds']);
        $this->assertSame($limits->totalPayloadBytes(), $payload['totalMaxBytes']);
        $this->assertSame(0, $payload['existingPhotos']);
        $this->assertContains('image/webp', $payload['photoMimes']);
    }

    // ------------------------------------------------------------------ upload

    public function test_video_is_stored_and_linked(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->payload([
                'title_ar' => 'مزاد بفيديو',
                'video' => UploadedFile::fake()->create('asset.mp4', 512, 'video/mp4'),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.auctions.index'));

        $auction = Auction::where('title_ar', 'مزاد بفيديو')->firstOrFail();

        $this->assertNotNull($auction->video);
        Storage::disk('public')->assertExists($auction->video);
    }

    public function test_oversized_photo_is_rejected_and_no_auction_is_created(): void
    {
        $limits = new UploadLimits;

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->payload([
                'title_ar' => 'مزاد بصورة ضخمة',
                'photos' => [
                    UploadedFile::fake()->image('ok.jpg'),
                    UploadedFile::fake()->create('huge.jpg', $limits->photoMaxKb() + 1, 'image/jpeg'),
                ],
            ]))
            ->assertSessionHasErrors('photos.1');

        $this->assertDatabaseMissing('auctions', ['title_ar' => 'مزاد بصورة ضخمة']);
    }

    /**
     * `image` alone accepts SVG, which can carry script. The rules pin the
     * extension AND the sniffed content type so a disguised file cannot land in
     * a publicly served directory.
     */
    public function test_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->payload([
                'title_ar' => 'مزاد بملف مزيف',
                'photos' => [UploadedFile::fake()->create('payload.php', 20, 'image/jpeg')],
            ]))
            ->assertSessionHasErrors('photos.0');
    }

    public function test_non_mp4_video_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->payload([
                'title_ar' => 'مزاد بفيديو خاطئ',
                'video' => UploadedFile::fake()->create('clip.avi', 64, 'video/x-msvideo'),
            ]))
            ->assertSessionHasErrors('video');

        $this->assertDatabaseMissing('auctions', ['title_ar' => 'مزاد بفيديو خاطئ']);
    }

    public function test_more_photos_than_the_cap_are_rejected_in_one_request(): void
    {
        $limits = new UploadLimits;

        $photos = [];
        for ($i = 0; $i <= $limits->maxPhotos(); $i++) {
            $photos[] = UploadedFile::fake()->image("p{$i}.jpg");
        }

        $this->actingAs($this->admin())
            ->post(route('admin.auctions.store'), $this->payload([
                'title_ar' => 'مزاد بصور كثيرة',
                'photos' => $photos,
            ]))
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseMissing('auctions', ['title_ar' => 'مزاد بصور كثيرة']);
    }

    // ------------------------------------------------------------------ append

    public function test_editing_appends_photos_to_the_existing_gallery(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'photos' => [UploadedFile::fake()->image('first.jpg')],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'photos' => [UploadedFile::fake()->image('second.jpg')],
        ])->assertSessionHasNoErrors();

        $this->assertCount(2, $auction->fresh()->photosArray());
    }

    /**
     * The per-request `max` rule capped one batch but not the total: ten more
     * photos were accepted on every save, so an auction could accumulate any
     * number of them.
     */
    public function test_appending_beyond_the_total_cap_is_refused(): void
    {
        $limits = new UploadLimits;
        $auction = $this->draft();

        $existing = [];
        for ($i = 0; $i < $limits->maxPhotos(); $i++) {
            $existing[] = 'auctions/'.$auction->id.'/existing'.$i.'.jpg';
        }
        $auction->forceFill(['photos' => implode(';', $existing)])->save();

        $this->actingAs($this->admin())
            ->put(route('admin.auctions.update', $auction), [
                'photos' => [UploadedFile::fake()->image('one-too-many.jpg')],
            ])
            ->assertSessionHasErrors('photos');

        $this->assertCount($limits->maxPhotos(), $auction->fresh()->photosArray());
    }

    public function test_new_video_replaces_and_deletes_the_previous_one(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'video' => UploadedFile::fake()->create('old.mp4', 128, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $old = $auction->fresh()->video;
        $this->assertNotNull($old);

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'video' => UploadedFile::fake()->create('new.mp4', 128, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $new = $auction->fresh()->video;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    // ------------------------------------------------------------------ delete

    public function test_a_single_photo_can_be_removed(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'photos' => [
                UploadedFile::fake()->image('keep.jpg'),
                UploadedFile::fake()->image('drop.jpg'),
            ],
        ])->assertSessionHasNoErrors();

        $paths = $auction->fresh()->photosArray();
        $this->assertCount(2, $paths);

        $this->actingAs($this->admin())
            ->delete(route('admin.auctions.photos.destroy', $auction), ['path' => $paths[1]])
            ->assertSessionHasNoErrors();

        $remaining = $auction->fresh()->photosArray();

        $this->assertSame([$paths[0]], $remaining);
        Storage::disk('public')->assertMissing($paths[1]);
        Storage::disk('public')->assertExists($paths[0]);
    }

    /**
     * The path to delete arrives from a form field. Without an ownership check
     * a crafted request could erase any file on the public disk.
     */
    public function test_deleting_a_photo_outside_the_auction_is_refused(): void
    {
        $auction = $this->draft();
        Storage::disk('public')->put('somebody-elses/secret.jpg', 'x');

        $this->actingAs($this->admin())
            ->delete(route('admin.auctions.photos.destroy', $auction), [
                'path' => 'somebody-elses/secret.jpg',
            ])
            ->assertSessionHasErrors('photos');

        Storage::disk('public')->assertExists('somebody-elses/secret.jpg');
    }

    public function test_the_video_can_be_removed(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'video' => UploadedFile::fake()->create('asset.mp4', 128, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $path = $auction->fresh()->video;

        $this->actingAs($this->admin())
            ->delete(route('admin.auctions.video.destroy', $auction))
            ->assertSessionHasNoErrors();

        $this->assertNull($auction->fresh()->video);
        Storage::disk('public')->assertMissing($path);
    }

    /** Media edits follow the same DRAFT-only rule as the rest of the form. */
    public function test_media_cannot_be_deleted_once_the_auction_is_published(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'photos' => [UploadedFile::fake()->image('published.jpg')],
        ])->assertSessionHasNoErrors();

        $path = $auction->fresh()->photosArray()[0];
        $auction->forceFill(['status' => AuctionStatus::PUBLISHED])->save();

        $this->actingAs($this->admin())
            ->delete(route('admin.auctions.photos.destroy', $auction), ['path' => $path])
            ->assertSessionHasErrors('photos');

        Storage::disk('public')->assertExists($path);
    }

    /**
     * The row was the only pointer to the uploaded files; deleting an auction
     * used to leave its whole media directory behind forever.
     */
    public function test_deleting_an_auction_purges_its_media(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'photos' => [UploadedFile::fake()->image('gone.jpg')],
            'video' => UploadedFile::fake()->create('gone.mp4', 128, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $auction->refresh();
        $photo = $auction->photosArray()[0];
        $video = $auction->video;

        $this->actingAs($this->admin())
            ->delete(route('admin.auctions.destroy', $auction))
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($photo);
        Storage::disk('public')->assertMissing($video);
    }

    // -------------------------------------------------------------- edit page

    public function test_edit_page_offers_deletion_for_stored_media(): void
    {
        $auction = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.auctions.update', $auction), [
            'photos' => [UploadedFile::fake()->image('shown.jpg')],
            'video' => UploadedFile::fake()->create('shown.mp4', 128, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->get(route('admin.auctions.edit', $auction))
            ->assertOk()
            ->assertSee('delete-photo-0', false)
            ->assertSee('delete-video', false)
            ->assertSee(route('admin.auctions.photos.destroy', $auction), false);
    }
}
