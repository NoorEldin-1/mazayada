{{-- Client-side guards for the auction asset media, shared by create + edit.

     These are a courtesy layer — AdminAuctionController re-validates everything
     — with one exception that matters: a request larger than PHP's post_max_size
     is discarded by PHP before Laravel boots, so the server can only ever answer
     it with a misleading CSRF 419 *after* the whole upload has been sent. The
     total-size check below is therefore the only place that failure can be
     reported honestly, and it reports it before a single byte leaves the browser.

     Limits and messages both come from the server (data-media-limits /
     #media-strings) so there is exactly one definition of "too big". --}}
(function () {
    var card = document.getElementById('media-card');
    var strings = document.getElementById('media-strings');
    if (!card || !strings) return;

    var L;
    try {
        L = JSON.parse(card.dataset.mediaLimits || '{}');
    } catch (e) {
        return; // Malformed payload — fall back to server-side validation only.
    }

    var S = strings.dataset;
    var photosInput = document.getElementById('photos');
    var photosPreview = document.getElementById('photos-preview');
    var photosError = document.getElementById('photos-error');
    var photosCounter = document.getElementById('photos-counter');
    var videoInput = document.getElementById('video');
    var videoError = document.getElementById('video-error');
    var videoStatus = document.getElementById('video-status');
    var videoPreview = document.getElementById('video-preview');
    var videoClear = document.getElementById('video-clear');
    var totalError = document.getElementById('media-total-error');
    var form = card.closest('form') || (photosInput && photosInput.form) || (videoInput && videoInput.form);

    // --- helpers -----------------------------------------------------------
    function mb(bytes) {
        return (Math.round((bytes / 1048576) * 10) / 10).toString();
    }

    function fill(template, values) {
        return String(template || '').replace(/:(\w+)/g, function (m, key) {
            return Object.prototype.hasOwnProperty.call(values, key) ? values[key] : m;
        });
    }

    function show(el, message) {
        if (!el) return;
        el.textContent = message;
        el.style.display = message ? 'block' : 'none';
    }

    function extensionOf(name) {
        var dot = String(name || '').lastIndexOf('.');
        return dot === -1 ? '' : name.slice(dot + 1).toLowerCase();
    }

    // Some browsers report an empty File.type for perfectly valid files, so the
    // extension is accepted as a fallback rather than rejecting a good upload.
    function typeAllowed(file, mimes, extensions) {
        if (file.type) return mimes.indexOf(file.type) !== -1;
        return extensions.indexOf(extensionOf(file.name)) !== -1;
    }

    // Object URLs are revoked explicitly: the old code revoked image URLs inside
    // onload and never revoked the video one at all, so every re-selection leaked
    // a blob for the lifetime of the page.
    var objectUrls = [];
    function trackUrl(url) {
        objectUrls.push(url);
        return url;
    }
    function releaseUrls() {
        objectUrls.forEach(function (u) { URL.revokeObjectURL(u); });
        objectUrls = [];
    }
    window.addEventListener('pagehide', releaseUrls);

    // ----------------------------------------------------------------- photos
    var selectedPhotos = [];
    var existingPhotos = parseInt(L.existingPhotos || 0, 10);

    function photoKey(file) {
        return file.name + '|' + file.size + '|' + (file.lastModified || 0);
    }

    // The input's FileList is read-only, so the authoritative selection lives in
    // `selectedPhotos` and is written back through a DataTransfer. That is what
    // makes per-file removal possible at all — previously a single unwanted
    // photo meant re-picking the entire batch.
    function syncPhotoInput() {
        if (!photosInput || typeof DataTransfer === 'undefined') return;
        try {
            var dt = new DataTransfer();
            selectedPhotos.forEach(function (f) { dt.items.add(f); });
            photosInput.files = dt.files;
        } catch (e) { /* older browser — the raw selection still submits */ }
    }

    function renderPhotos() {
        if (!photosPreview) return;
        photosPreview.textContent = '';

        selectedPhotos.forEach(function (file, index) {
            var tile = document.createElement('figure');
            tile.className = 'mzd-media-tile';

            var img = document.createElement('img');
            img.alt = '';
            img.src = trackUrl(URL.createObjectURL(file));
            tile.appendChild(img);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'mzd-media-x';
            remove.setAttribute('aria-label', S.remove || 'x');
            remove.title = S.remove || '';
            remove.innerHTML = '&times;';
            remove.addEventListener('click', function () {
                selectedPhotos.splice(index, 1);
                syncPhotoInput();
                renderPhotos();
                validateTotal();
            });
            tile.appendChild(remove);

            var name = document.createElement('span');
            name.className = 'mzd-media-name';
            name.textContent = file.name;
            name.title = file.name;
            tile.appendChild(name);

            photosPreview.appendChild(tile);
        });

        if (photosCounter) {
            var bytes = selectedPhotos.reduce(function (sum, f) { return sum + f.size; }, 0);
            photosCounter.textContent = fill(photosCounter.dataset.template, {
                count: selectedPhotos.length + existingPhotos,
                max: L.maxPhotos,
                size: mb(bytes),
            });
            photosCounter.style.display = selectedPhotos.length ? 'block' : 'none';
        }
    }

    if (photosInput) {
        photosInput.addEventListener('change', function () {
            var rejected = [];
            var chosen = Array.prototype.slice.call(photosInput.files || []);
            var seen = {};
            selectedPhotos.forEach(function (f) { seen[photoKey(f)] = true; });

            // Files chosen in a later batch are ADDED to the selection rather
            // than replacing it, so picking photos from two folders works.
            chosen.forEach(function (file) {
                if (seen[photoKey(file)]) return; // same file picked twice
                if (!typeAllowed(file, L.photoMimes, L.photoExtensions)) {
                    rejected.push(fill(S.errPhotoType, { name: file.name }));
                    return;
                }
                if (file.size > L.photoMaxBytes) {
                    rejected.push(fill(S.errPhotoSize, { name: file.name }));
                    return;
                }
                if (selectedPhotos.length + existingPhotos >= L.maxPhotos) {
                    rejected.push(fill(S.errPhotoCount, {
                        max: L.maxPhotos,
                        remaining: Math.max(0, L.maxPhotos - existingPhotos - selectedPhotos.length),
                    }));
                    return;
                }
                seen[photoKey(file)] = true;
                selectedPhotos.push(file);
            });

            // Rejected files are dropped from the selection, never the accepted
            // ones — a single oversized photo used to cost the whole batch.
            syncPhotoInput();
            renderPhotos();
            show(photosError, rejected.length ? rejected.join(' ') : '');
            validateTotal();
        });
    }

    // ------------------------------------------------------------------ video
    // 'idle' → nothing selected · 'checking' → duration probe running ·
    // 'ready' → validated. The old code left a rejected file in a permanent
    // "invalid" state even after clearing the input, which silently blocked
    // every subsequent submit of the whole form.
    var videoState = 'idle';
    var resumeSubmit = false;

    function resetVideo(message) {
        videoState = 'idle';
        if (videoInput) videoInput.value = '';
        if (videoPreview) {
            var el = videoPreview.querySelector('video');
            if (el) el.removeAttribute('src');
            videoPreview.style.display = 'none';
        }
        show(videoStatus, '');
        show(videoError, message || '');
        validateTotal();
    }

    if (videoInput) {
        videoInput.addEventListener('change', function () {
            show(videoError, '');
            show(videoStatus, '');
            resumeSubmit = false;

            var file = videoInput.files && videoInput.files[0];
            if (!file) { resetVideo(''); return; }

            if (!typeAllowed(file, L.videoMimes, ['mp4'])) { resetVideo(S.errVideoType); return; }
            if (file.size > L.videoMaxBytes) { resetVideo(S.errVideoSize); return; }

            videoState = 'checking';
            show(videoStatus, S.videoChecking);

            var url = trackUrl(URL.createObjectURL(file));
            var probe = document.createElement('video');
            probe.preload = 'metadata';

            probe.onloadedmetadata = function () {
                var duration = probe.duration;
                // A non-finite duration means the browser could not measure it
                // (fragmented MP4, streaming header). Unmeasurable is not the
                // same as too long — reject only what is provably over.
                if (isFinite(duration) && duration > L.videoMaxSeconds) {
                    resetVideo(S.errVideoDuration);
                    return;
                }
                videoState = 'ready';
                show(videoStatus, '');
                if (videoPreview) {
                    videoPreview.querySelector('video').src = url;
                    videoPreview.style.display = 'block';
                }
                validateTotal();
                // The admin pressed submit while the probe was still running;
                // honour it now that the file has cleared.
                if (resumeSubmit && form) {
                    resumeSubmit = false;
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                }
            };

            probe.onerror = function () {
                resumeSubmit = false;
                resetVideo(S.errVideoUnreadable);
            };

            probe.src = url;
        });
    }

    if (videoClear) {
        videoClear.addEventListener('click', function () { resetVideo(''); });
    }

    // ------------------------------------------------------------ total budget
    function totalBytes() {
        var bytes = selectedPhotos.reduce(function (sum, f) { return sum + f.size; }, 0);
        if (videoInput && videoInput.files && videoInput.files[0]) bytes += videoInput.files[0].size;
        return bytes;
    }

    function validateTotal() {
        var bytes = totalBytes();
        if (bytes > L.totalMaxBytes) {
            show(totalError, fill(S.errTotal, { size: mb(bytes) }));
            return false;
        }
        show(totalError, '');
        return true;
    }

    // ------------------------------------------------------------------ submit
    if (form) {
        // form.elements — not querySelector — because the media delete buttons
        // sit inside this form in the DOM but belong to another form via form=,
        // and would otherwise be picked up as "the" submit button.
        var submitBtn = Array.prototype.filter
            .call(form.elements, function (el) { return el.type === 'submit'; })
            .pop();
        var submitLabel = submitBtn ? submitBtn.innerHTML : null;

        form.addEventListener('submit', function (e) {
            if (videoState === 'checking') {
                e.preventDefault();
                resumeSubmit = true;
                show(videoStatus, S.videoWait);
                return;
            }

            if (!validateTotal()) {
                e.preventDefault();
                if (totalError) totalError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // A large upload takes long enough that an impatient second click
            // used to create a duplicate auction. Lock the button for the
            // lifetime of the request.
            if (submitBtn && !e.defaultPrevented) {
                submitBtn.disabled = true;
                submitBtn.textContent = S.uploading || submitBtn.textContent;
            }
        });

        // Returning via the back button restores the page from the bfcache with
        // the button still disabled — un-stick it.
        window.addEventListener('pageshow', function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                if (submitLabel !== null) submitBtn.innerHTML = submitLabel;
            }
        });
    }
})();
