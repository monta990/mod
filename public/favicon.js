(function () {
    'use strict';

    function applyCustomFavicon() {
        var custom = document.querySelector('link[data-mod-favicon]');
        if (!custom) {
            return;
        }

        var href = custom.href;
        if (!href) {
            return;
        }

        // GLPI 12 adds its native favicon after plugin header tags. Replace
        // every existing favicon declaration with one custom declaration so
        // the browser has exactly one authoritative favicon source.
        document.querySelectorAll('link[rel~="icon"], link[rel="shortcut icon"]').forEach(function (icon) {
            icon.remove();
        });

        var favicon = document.createElement('link');
        favicon.setAttribute('rel', 'icon');
        favicon.setAttribute('type', 'image/x-icon');
        favicon.setAttribute('sizes', '16x16');
        favicon.setAttribute('href', href);
        favicon.setAttribute('data-mod-favicon-applied', '1');
        document.head.appendChild(favicon);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyCustomFavicon, { once: true });
    } else {
        applyCustomFavicon();
    }
})();
