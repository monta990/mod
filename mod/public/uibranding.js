(function () {
    'use strict';

    var previewStates = new Map();

    function clearPreview(input) {
        var state = previewStates.get(input);
        if (!state) {
            return;
        }

        if (state.preview && state.preview.parentNode) {
            state.preview.parentNode.removeChild(state.preview);
        }

        state.originalPreview.hidden = state.wasHidden;
        URL.revokeObjectURL(state.objectUrl);
        previewStates.delete(input);
    }

    function updatePreview(input) {
        if (!input || !input.files || input.files.length === 0) {
            clearPreview(input);
            return;
        }

        var file = input.files[0];
        var previewId = input.getAttribute('data-mod-preview');
        if (!previewId) {
            return;
        }

        var originalPreview = document.getElementById(previewId);
        if (!originalPreview || originalPreview.tagName !== 'IMG' || !originalPreview.parentNode) {
            return;
        }

        clearPreview(input);

        var objectUrl = URL.createObjectURL(file);
        var temporaryPreview = document.createElement('img');
        temporaryPreview.className = originalPreview.className;
        temporaryPreview.alt = originalPreview.alt;
        temporaryPreview.src = objectUrl;

        var wasHidden = originalPreview.hidden;
        originalPreview.hidden = true;
        originalPreview.parentNode.insertBefore(temporaryPreview, originalPreview.nextSibling);

        previewStates.set(input, {
            originalPreview: originalPreview,
            preview: temporaryPreview,
            wasHidden: wasHidden,
            objectUrl: objectUrl,
        });
    }

    function restorePreview(input) {
        clearPreview(input);
    }

    function clearAllPreviews() {
        Array.from(previewStates.keys()).forEach(clearPreview);
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

        window.addEventListener('pagehide', clearAllPreviews, { once: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilePreviews, { once: true });
    } else {
        initFilePreviews();
    }
})();
