{{--
    Asset media (spec §4 step 1) — photos + one short video.

    Shared by the create and edit forms so the two can never drift apart; the
    edit page additionally renders the already-stored files with a per-item
    delete. Expects:
      $mediaLimits  App\Support\UploadLimits — the EFFECTIVE caps (config clamped
                    to what PHP will really accept)
      $auction      the auction being edited, or null on create

    Every number shown here comes from $mediaLimits. The form used to advertise
    "50 MB" while PHP's post_max_size was 40M, so a legal-looking upload was
    silently discarded by PHP and surfaced as a bare 419 after a long wait.
--}}
@php
    $auction = $auction ?? null;
    $limitsJson = $mediaLimits->toArray();
    $photoMb = \App\Support\UploadLimits::mb($mediaLimits->photoMaxBytes());
    $videoMb = \App\Support\UploadLimits::mb($mediaLimits->videoMaxBytes());
    $totalMb = \App\Support\UploadLimits::mb($mediaLimits->totalPayloadBytes());
    $videoMinutes = rtrim(rtrim(number_format($mediaLimits->videoMaxSeconds() / 60, 1, '.', ''), '0'), '.');
    $storedPhotos = $auction?->photosArray() ?? [];
    $storedPhotoUrls = $auction?->photoUrls() ?? [];
    $storedVideo = $auction?->videoUrl();
    $canManageStored = $auction && $auction->status === \App\Enums\AuctionStatus::DRAFT;
    $remainingSlots = max(0, $mediaLimits->maxPhotos() - count($storedPhotos));
@endphp

{{-- Media-only styles. Kept here rather than in dashboard.css so the partial is
     self-contained and needs no asset rebuild; the `mzd-` prefix is mandatory —
     an `ad-` prefix gets eaten by ad-blocker cosmetic filters. --}}
<style>
    .mzd-media-tile { position: relative; margin: 0; }
    .mzd-media-tile img,
    .mzd-media-tile video { width: 90px; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line); display: block; }
    .mzd-media-x {
        position: absolute; inset-block-start: -7px; inset-inline-end: -7px;
        width: 22px; height: 22px; padding: 0; line-height: 1;
        display: flex; align-items: center; justify-content: center;
        border: 0; border-radius: 999px; cursor: pointer;
        background: var(--danger, #dc2626); color: #fff; font-size: 15px; font-weight: 700;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .35);
    }
    .mzd-media-x:hover { filter: brightness(1.1); }
    .mzd-media-remove {
        font-size: .8rem; font-weight: 600; cursor: pointer;
        color: var(--danger, #dc2626); background: none;
        border: 1px solid var(--line); border-radius: 8px; padding: .35rem .75rem;
    }
    .mzd-media-name {
        display: block; max-width: 90px; margin-block-start: 4px;
        font-size: .65rem; color: var(--ink-muted);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
</style>

<x-ui.card :title="__('admin.auctions.sec_photos')" class="mb-6" id="media-card"
           data-media-limits="{{ json_encode($limitsJson + ['existingPhotos' => count($storedPhotos)]) }}">

    {{-- Uploads are never restored after a failed submit — the browser cannot
         re-populate a file input — so say it plainly instead of letting the
         admin re-submit and silently lose their photos. --}}
    <p class="mzd-media-note" style="font-size:0.8rem;color:var(--ink-muted);background:var(--surface-2,rgba(127,127,127,.08));border-radius:8px;padding:0.6rem 0.8rem;margin-block-end:1rem">
        {{ __('admin.auctions.media_note_reselect') }}
    </p>

    {{-- ------------------------------------------------------------ Images --}}
    <h3 style="font-size:0.9rem;font-weight:600;color:var(--ink);margin-block-end:0.75rem">{{ __('admin.auctions.sec_photos_images') }}</h3>

    @if($storedPhotos)
        <p style="font-size:0.8rem;color:var(--ink-muted);margin-block-end:0.5rem">{{ __('admin.auctions.photos_existing') }}</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-block-end:12px">
            @foreach($storedPhotoUrls as $i => $url)
                <figure class="mzd-media-tile">
                    <img src="{{ $url }}" alt="" loading="lazy">
                    @if($canManageStored)
                        {{-- The delete form lives outside this <form> (nesting is
                             invalid HTML); the button reaches it via form=. --}}
                        <button type="submit" form="delete-photo-{{ $i }}"
                                class="mzd-media-x" aria-label="{{ __('admin.auctions.photos_remove') }}"
                                title="{{ __('admin.auctions.photos_remove') }}">&times;</button>
                    @endif
                </figure>
            @endforeach
        </div>
    @endif

    <div class="field">
        <label for="photos">{{ __('admin.auctions.f_photos') }}</label>
        <input type="file" id="photos" name="photos[]" class="input"
               accept="image/jpeg,image/png,image/webp" multiple
               @if($remainingSlots === 0) disabled @endif>
        <small style="color:var(--ink-muted)">
            {{ __('admin.auctions.photos_hint', ['max' => $mediaLimits->maxPhotos(), 'size' => $photoMb]) }}
        </small>
        @if($storedPhotos)
            <small style="color:var(--ink-muted);display:block">
                {{ __('admin.auctions.photos_remaining', ['remaining' => $remainingSlots]) }}
            </small>
        @endif
        {{-- Client-side rejections (count / size / type) land here. --}}
        <small id="photos-error" class="text-danger text-xs mt-1" style="display:none"></small>
        {{-- Array-level failures (too many files) key on `photos`, per-file ones
             on `photos.0`; both are rendered so neither is swallowed. --}}
        @error('photos') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
        @error('photos.*') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
    </div>

    <small id="photos-counter" style="color:var(--ink-muted);display:none;margin-block-start:8px"
           data-template="{{ __('admin.auctions.photos_counter') }}"></small>
    <div id="photos-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-block-start:12px"></div>

    {{-- ------------------------------------------------------------- Video --}}
    <hr style="border:0;border-top:1px solid var(--line);margin-block:1.25rem">
    <h3 style="font-size:0.9rem;font-weight:600;color:var(--ink);margin-block-end:0.75rem">{{ __('admin.auctions.sec_photos_video') }}</h3>

    @if($storedVideo)
        <p style="font-size:0.8rem;color:var(--ink-muted);margin-block-end:0.5rem">{{ __('admin.auctions.video_existing') }}</p>
        <div style="margin-block-end:12px">
            <video controls preload="metadata" src="{{ $storedVideo }}"
                   style="max-width:320px;width:100%;border-radius:8px;border:1px solid var(--line);display:block"></video>
            @if($canManageStored)
                <button type="submit" form="delete-video" class="mzd-media-remove" style="margin-block-start:8px">
                    {{ __('admin.auctions.video_delete') }}
                </button>
            @endif
        </div>
    @endif

    <div class="field">
        <label for="video">{{ __('admin.auctions.f_video') }}</label>
        <input type="file" id="video" name="video" class="input" accept="video/mp4">
        <small style="color:var(--ink-muted)">
            {{ __('admin.auctions.video_hint', ['size' => $videoMb, 'minutes' => $videoMinutes]) }}
        </small>
        <small id="video-error" class="text-danger text-xs mt-1" style="display:none"></small>
        <small id="video-status" style="color:var(--ink-muted);display:none"></small>
        @error('video') <small class="text-danger text-xs mt-1">{{ $message }}</small> @enderror
    </div>

    <div id="video-preview" style="margin-block-start:12px;display:none">
        <video controls preload="metadata" style="max-width:320px;width:100%;border-radius:8px;border:1px solid var(--line);display:block"></video>
        <button type="button" id="video-clear" class="mzd-media-remove" style="margin-block-start:8px">
            {{ __('admin.auctions.video_remove') }}
        </button>
    </div>

    {{-- Whole-request budget. Exceeding post_max_size is the one failure the
         server can never report properly, so it is stopped before the upload
         even starts. --}}
    <small id="media-total-error" class="text-danger text-xs" style="display:none;margin-block-start:10px"></small>

    {{-- Localised strings + numbers for the client-side guards. --}}
    <span id="media-strings" hidden
          data-err-photo-type="{{ __('admin.auctions.photos_err_type') }}"
          data-err-photo-size="{{ __('admin.auctions.photos_err_size', ['size' => $photoMb]) }}"
          data-err-photo-count="{{ __('admin.auctions.photos_err_count') }}"
          data-err-total="{{ __('admin.auctions.media_err_total', ['max' => $totalMb]) }}"
          data-err-video-type="{{ __('admin.auctions.video_err_type') }}"
          data-err-video-size="{{ __('admin.auctions.video_err_size', ['size' => $videoMb]) }}"
          data-err-video-duration="{{ __('admin.auctions.video_err_duration', ['minutes' => $videoMinutes]) }}"
          data-err-video-unreadable="{{ __('admin.auctions.video_err_unreadable') }}"
          data-video-checking="{{ __('admin.auctions.video_checking') }}"
          data-video-wait="{{ __('admin.auctions.video_wait') }}"
          data-remove="{{ __('admin.auctions.photos_remove') }}"
          data-uploading="{{ __('admin.auctions.media_uploading') }}"></span>
</x-ui.card>

{{-- Delete actions for already-stored media. Rendered outside the main form —
     a nested <form> is dropped by the parser — and reached by form= above. --}}
@if($canManageStored)
    @push('scripts')
        @foreach($storedPhotos as $i => $p)
            <form id="delete-photo-{{ $i }}" method="POST"
                  action="{{ route('admin.auctions.photos.destroy', $auction) }}"
                  data-confirm="{{ __('admin.auctions.photo_delete_confirm') }}"
                  data-confirm-variant="danger" hidden>
                @csrf
                @method('DELETE')
                <input type="hidden" name="path" value="{{ $p }}">
            </form>
        @endforeach

        @if($storedVideo)
            <form id="delete-video" method="POST"
                  action="{{ route('admin.auctions.video.destroy', $auction) }}"
                  data-confirm="{{ __('admin.auctions.video_delete_confirm') }}"
                  data-confirm-variant="danger" hidden>
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endpush
@endif
