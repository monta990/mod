# Release test plan — UI Branding 12.0.0

This checklist is the manual acceptance matrix for the release candidate. Run it on disposable/staging instances, not directly on production.

## Supported platform matrix

| GLPI | PHP | Expected |
|---|---:|---|
| 12.0.0 | 8.2 | Supported |
| 12.0.0 | 8.3 | Supported |
| 12.0.1 | 8.2 | Supported |
| 12.0.1 | 8.3 | Supported |

The plugin declares GLPI `>=12.0.0 <12.1.0` and PHP `>=8.2`. The release CI lints the plugin on PHP 8.2, 8.3 and 8.4; the functional matrix above still requires real GLPI instances.

## Installation and lifecycle

- [ ] Install from a ZIP whose only top-level directory is `mod/`.
- [ ] Install and enable the plugin on a clean GLPI 12.0.0 database.
- [ ] Repeat on GLPI 12.0.1.
- [ ] Open the configuration page as a user with `config / UPDATE`.
- [ ] Confirm a user without that right receives HTTP 403.
- [ ] Disable and re-enable the plugin; confirm no core files are modified.
- [ ] Uninstall only on a disposable instance and confirm that only `GLPI_PLUGIN_DOC_DIR/mod/` is removed.

**Version baseline note:** this release intentionally reports plugin version `12.0.0`. GLPI 12 stores the previously installed plugin version; changing from a previously installed build that reported a higher plugin version to this baseline will be detected as a plugin version change and may place the plugin in the update-required state. Test the exact transition on a database copy before deploying. The installer is idempotent and keeps an existing `settings.ini` and `uploads/` directory; do not use an uninstall/reinstall path if existing branding data must be preserved.

## Branding behavior

- [ ] Set a custom application title and confirm it appears on authenticated and anonymous pages.
- [ ] Enable standard logos; test expanded navigation, collapsed navigation and the login page.
- [ ] Enable theme-aware logos; test a light GLPI palette and a palette marked dark by GLPI's `ThemeManager`.
- [ ] Confirm the white/black variants are selected through GLPI's native logo variables.
- [ ] Upload a custom favicon and verify it on authenticated and anonymous pages.
- [ ] Enable a login background and verify sizing, centering and responsive behavior.
- [ ] Test the configuration page at desktop and narrow viewport sizes.
- [ ] Switch back to standard logos and confirm the standard set is used.

## Upload validation and rollback

- [ ] Upload valid JPEG background (at least 1920×1080).
- [ ] Upload valid PNG logos and a valid multi-resolution ICO.
- [ ] Reject a renamed text/HTML file with a JPG/PNG/ICO extension.
- [ ] Reject an oversized image and a too-small login background.
- [ ] Reject a malformed ICO directory or an ICO with invalid embedded offsets.
- [ ] Submit multiple files where one is invalid; verify no existing uploaded file or settings value is replaced.
- [ ] Force a write-permission failure on a disposable instance; verify old files are restored and an error is logged.

## Routing, security and cache

- [ ] Test normal `plugins/mod` installation.
- [ ] Test `marketplace/mod` installation if Marketplace deployment is supported by the instance.
- [ ] Test GLPI installed below a URL subdirectory (non-empty `root_doc`).
- [ ] Request an unknown asset key; expect HTTP 404.
- [ ] Request public logo/favicon resources without authentication; expect only allowlisted resources.
- [ ] Submit the configuration form from the same origin; expect success.
- [ ] Attempt a cross-site POST; confirm GLPI's native CSRF listener rejects it.
- [ ] Request a CSS/image URL twice and verify the second response can return 304 when validators match.
- [ ] Upload a replacement image and verify its fingerprinted URL changes.
- [ ] Clear GLPI cache and confirm normal GLPI navigation still works.

## Localization and logs

- [ ] Check German, English, Spanish (Spain), Spanish (Mexico), French and Italian catalogs.
- [ ] Confirm the PO and MO catalogs contain the same current translations.
- [ ] Confirm no PHP warnings, deprecations, uncaught exceptions or route errors appear in GLPI logs.

Record the tested GLPI build, PHP version, database type, installation path, test date and outcome before publishing.
