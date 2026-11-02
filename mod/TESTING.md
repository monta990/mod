# Release test plan — UI Branding 12.0.0

Run these checks on disposable or staging instances, never first on production.

## Supported GLPI and PHP matrix

| GLPI | PHP | Classification |
|---|---:|---|
| 12.0.0 | 8.3 | Supported baseline |
| 12.0.0 | 8.4 | Supported |
| 12.0.0 | 8.5 | Supported |
| 12.0.1 | 8.3 | Supported baseline |
| 12.0.1 | 8.4 | Supported |
| 12.0.1 | 8.5 | Supported |
| 12.0.x | 8.6 nightly/RC | Experimental only |

GLPI 12's current published requirements specify PHP 8.3 or newer. The plugin therefore declares PHP `>=8.3`. PHP 8.6 is still pre-GA as of this test plan; its GitHub Actions job is non-blocking and must not be treated as a production compatibility certification.

## Installation and lifecycle

- [ ] Install from a ZIP whose only top-level directory is `mod/`.
- [ ] Verify every plugin file is inside that directory, including `logo.png`, metadata, translations, scripts, docs and bundled images.
- [ ] Install and enable on clean GLPI 12.0.0.
- [ ] Repeat on GLPI 12.0.1.
- [ ] Open configuration as a user with `config / UPDATE`.
- [ ] Confirm a user without that right receives HTTP 403.
- [ ] Disable/re-enable without changing GLPI core files.
- [ ] On a disposable system, uninstall and verify only `GLPI_PLUGIN_DOC_DIR/mod/` is removed.

**Version transition:** GLPI uses `plugin_mod_install()` for both initial installation and plugin updates. The hook is idempotent: it does not rewrite an existing `settings.ini`, maps previous uploaded logo/favicon filenames to the new neutral names by copying (not moving), does not overwrite already-migrated uploads, leaves other files untouched and records a migration marker. Do not uninstall/reinstall when data must be kept; explicit uninstall intentionally removes `GLPI_PLUGIN_DOC_DIR/mod/`. Run `php scripts/test-upgrade-preservation.php` to verify this behavior before release.

## Demonstration branding assets

- [ ] Inspect `resources/images/` and confirm all sample logos/favicon are original, neutral assets with no GLPI or third-party brand marks.
- [ ] Verify the sample icon and horizontal/square logo variants display correctly in the configuration UI.
- [ ] Verify light and dark logo variants on the corresponding GLPI palettes.
- [ ] Confirm a fresh installation falls back to the bundled sample artwork.
- [ ] Confirm administrator uploads override the bundled sample files without modifying plugin code.\n- [ ] Run `php scripts/test-upgrade-preservation.php` and confirm settings, existing uploads and unrelated files survive a repeated update.

## Branding behavior

- [ ] Set a custom application title; confirm on authenticated and anonymous pages.
- [ ] Enable standard logos; test expanded navigation, collapsed navigation and login page.
- [ ] Enable theme-aware logos; test light and dark palettes from GLPI's ThemeManager.
- [ ] Upload a custom favicon and verify authenticated and anonymous pages.
- [ ] Enable the login background and verify responsive positioning/cover.
- [ ] Switch back to standard logos and confirm the correct standard set is used.

## Upload validation and rollback

- [ ] Upload a valid JPEG background at least 1920×1080.
- [ ] Upload valid PNG logo variants and a valid ICO.
- [ ] Reject renamed text/HTML masquerading as JPG, PNG or ICO.
- [ ] Reject oversized images and undersized login background.
- [ ] Reject malformed ICO directory or invalid embedded offsets.
- [ ] Submit multiple files with one invalid file; verify previous files and settings remain unchanged.
- [ ] Force a file-write failure on staging; verify previous files are restored, or recovery data is retained and logged.

## GitHub version check\n\n- [ ] Verify the configuration page shows the installed version.\n- [ ] Publish a higher stable GitHub release and confirm the page shows an update alert and a link to the release list.\n- [ ] Confirm draft and pre-release tags are ignored.\n- [ ] Confirm a network failure does not affect page loading, settings or uploads and is retried after the short failure cache.\n- [ ] Confirm a successful result is cached for six hours.\n\n## Routing, permissions and cache

- [ ] Test normal `plugins/mod` installation.
- [ ] Test Marketplace installation if used by the deployment.
- [ ] Test GLPI hosted under a non-empty `root_doc`.
- [ ] Unknown asset key returns HTTP 404.
- [ ] Anonymous requests can access only allowlisted assets.
- [ ] Same-origin configuration POST succeeds; cross-origin POST is rejected by GLPI's native CSRF protections.
- [ ] Repeated CSS/image request supports conditional caching (304).
- [ ] Replacing a logo changes its content fingerprint.
- [ ] Cache clearing works without affecting core navigation.

## Localization and logs

- [ ] Check German, English, Spanish (Spain), Spanish (Mexico), French and Italian.
- [ ] Confirm PO and MO catalogs are synchronized.
- [ ] Confirm no PHP warnings, deprecations, uncaught exceptions or route errors appear in GLPI logs.

Record GLPI build, PHP version, DB engine, install method, root_doc, test date and outcome before approving the release.
