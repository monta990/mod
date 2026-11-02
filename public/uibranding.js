(function () {
    'use strict';

    function updatePreview(input) {
        if (!input || !input.files || input.files.length === 0) {
            return;
        }

        var file = input.files[0];
        var previewId = input.getAttribute('data-mod-preview');
        if (!previewId) {
            return;
        }

        var preview = document.getElementById(previewId);
        if (!preview) {
            return;
        }

        var previousUrl = preview.getAttribute('data-mod-object-url');
        if (previousUrl) {
            URL.revokeObjectURL(previousUrl);
        }

        var objectUrl = URL.createObjectURL(file);
        preview.setAttribute('src', objectUrl);
        preview.setAttribute('data-mod-object-url', objectUrl);
    }

    function restorePreview(input) {
        var previewId = input.getAttribute('data-mod-preview');
        if (!previewId) {
            return;
        }

        var preview = document.getElementById(previewId);
        if (!preview) {
            return;
        }

        var previousUrl = preview.getAttribute('data-mod-object-url');
        if (previousUrl) {
            URL.revokeObjectURL(previousUrl);
            preview.removeAttribute('data-mod-object-url');
        }

        var defaultSrc = preview.getAttribute('data-mod-default-src');
        if (defaultSrc) {
            preview.setAttribute('src', defaultSrc);
        }
    }

    function initFilePreviews() {
        document.querySelectorAll('input[type="file"][data-mod-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                if (input.files && input.files.length > 0) {
                    updatePreview(input);
                } else {
                    restorePreview(input);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilePreviews, { once: true });
    } else {
        initFilePreviews();
    }
})();
