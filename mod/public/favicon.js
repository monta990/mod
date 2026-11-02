(function () {
    'use strict';

    function applyCustomFavicon() {
        var custom = document.querySelector('link[data-mod-favicon]');
        if (!custom || !document.head) {
            return;
        }

        // Keep the server-rendered, plugin-owned link as the source of truth.
        // Move that same node to the end of <head> so it takes precedence over
        // GLPI's native favicon without copying a DOM-derived URL into a sink.
        document.querySelectorAll('link[rel~="icon"], link[rel="shortcut icon"]').forEach(function (icon) {
            if (icon !== custom) {
                icon.remove();
            }
        });

        custom.setAttribute('rel', 'icon');
        document.head.appendChild(custom);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyCustomFavicon, { once: true });
    } else {
        applyCustomFavicon();
    }
})();
