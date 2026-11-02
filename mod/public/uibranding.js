(function () {
    'use strict';

    function clearPreview(input, preview) {
        if (!input || !preview) {
            return;
        }

        preview.hidden = true;
        preview.textContent = '';
        preview.style.backgroundImage = 'none';
        preview.style.backgroundColor = '';
        preview.removeAttribute('aria-label');
    }

    function updatePreview(input) {
        if (!input || !input.files || input.files.length === 0) {
            clearPreview(input, input && document.getElementById(input.getAttribute('data-mod-preview')));
            return;
        }

        var previewId = input.getAttribute('data-mod-preview');
        if (!previewId) {
            return;
        }

        var preview = document.getElementById(previewId);
        if (!preview) {
            return;
        }

        var file = input.files[0];
        if (!file || typeof file.name !== 'string') {
            clearPreview(input, preview);
            return;
        }

        // Use textContent only: filenames are untrusted text and must never be
        // parsed as HTML or assigned to a URL-bearing DOM attribute.
        preview.textContent = file.name;
        preview.hidden = false;
        preview.style.backgroundImage = 'none';
        preview.style.backgroundColor = 'var(--tblr-bg-surface-secondary)';
        preview.setAttribute('aria-label', file.name);
    }

    function initFilePreviews() {
        document.querySelectorAll('input[type="file"][data-mod-preview]').forEach(function (input) {
            var previewId = input.getAttribute('data-mod-preview');
            var preview = previewId ? document.getElementById(previewId) : null;
            if (preview) {
                preview.hidden = true;
            }

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
