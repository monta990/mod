(function () {
    'use strict';

    var requestVersions = new WeakMap();

    function getPreview(input, attribute) {
        var id = input.getAttribute(attribute);
        return id ? document.getElementById(id) : null;
    }

    function nextRequestVersion(input) {
        var version = (requestVersions.get(input) || 0) + 1;
        requestVersions.set(input, version);
        return version;
    }

    function showFallback(status, canvas, file) {
        if (canvas) {
            canvas.hidden = true;
        }
        if (status) {
            status.textContent = file.name;
            status.hidden = false;
        }
    }

    function restoreSavedPreview(input) {
        if (!input) {
            return;
        }

        nextRequestVersion(input);

        var savedPreview = getPreview(input, 'data-mod-default-preview');
        var canvas = getPreview(input, 'data-mod-preview');
        var status = getPreview(input, 'data-mod-status-preview');

        if (canvas) {
            var context = typeof canvas.getContext === 'function' ? canvas.getContext('2d') : null;
            if (context) {
                context.clearRect(0, 0, canvas.width || 0, canvas.height || 0);
            }
            canvas.width = 0;
            canvas.height = 0;
            canvas.hidden = true;
            canvas.removeAttribute('aria-label');
        }

        if (status) {
            status.textContent = '';
            status.hidden = true;
        }
        if (savedPreview) {
            savedPreview.hidden = false;
        }
    }

    function updatePreview(input) {
        if (!input || !input.files || input.files.length === 0) {
            restoreSavedPreview(input);
            return;
        }

        var file = input.files[0];
        var savedPreview = getPreview(input, 'data-mod-default-preview');
        var canvas = getPreview(input, 'data-mod-preview');
        var status = getPreview(input, 'data-mod-status-preview');

        if (!file || typeof file.name !== 'string' || !savedPreview || !canvas || !status) {
            restoreSavedPreview(input);
            return;
        }

        var requestVersion = nextRequestVersion(input);
        savedPreview.hidden = false;
        status.textContent = file.name;
        status.hidden = false;
        canvas.hidden = true;

        if (typeof createImageBitmap !== 'function' || typeof canvas.getContext !== 'function') {
            return;
        }

        var bitmapPromise;
        try {
            bitmapPromise = createImageBitmap(file);
        } catch (error) {
            showFallback(status, canvas, file);
            return;
        }

        Promise.resolve(bitmapPromise).then(function (bitmap) {
            try {
                if (requestVersions.get(input) !== requestVersion) {
                    return;
                }

                var imageWidth = Number(bitmap && bitmap.width);
                var imageHeight = Number(bitmap && bitmap.height);
                var context = canvas.getContext('2d');

                if (!context || imageWidth <= 0 || imageHeight <= 0) {
                    showFallback(status, canvas, file);
                    return;
                }

                var maxWidth = Number(canvas.getAttribute('data-mod-max-width')) || 640;
                var maxHeight = Number(canvas.getAttribute('data-mod-max-height')) || 360;
                var scale = Math.min(1, maxWidth / imageWidth, maxHeight / imageHeight);

                canvas.width = Math.max(1, Math.round(imageWidth * scale));
                canvas.height = Math.max(1, Math.round(imageHeight * scale));
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

                canvas.setAttribute('aria-label', file.name);
                canvas.hidden = false;
                savedPreview.hidden = true;
                status.textContent = '';
                status.hidden = true;
            } catch (error) {
                showFallback(status, canvas, file);
            } finally {
                if (bitmap && typeof bitmap.close === 'function') {
                    bitmap.close();
                }
            }
        }, function () {
            if (requestVersions.get(input) === requestVersion) {
                showFallback(status, canvas, file);
            }
        });
    }

    function initFilePreviews() {
        document.querySelectorAll('input[type="file"][data-mod-preview]').forEach(function (input) {
            restoreSavedPreview(input);
            input.addEventListener('change', function () {
                updatePreview(input);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilePreviews, { once: true });
    } else {
        initFilePreviews();
    }
})();