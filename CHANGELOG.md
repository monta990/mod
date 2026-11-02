# Changelog

All notable changes to UI Branding are documented here.

## [12.0.1] - 2026-11-02

### Security
- Remove upload-driven DOM URL sinks from preview handling. Upload selection is represented by a safe status container; file data is never assigned to DOM URL attributes. The favicon script reuses the trusted server-rendered link instead of copying a DOM-derived URL.

### Fixed
- Replace dynamic image preview elements with inert upload-status containers and add regression guards against dynamic URL and HTML insertion sinks.

### Changed
- Update plugin metadata, translation catalog metadata, package validation, and release automation for numeric version/tag `12.0.1` (without a `v` prefix).

## [12.0.0] - 2026-11-01

### Added
- Published the GLPI 12 runtime-branding baseline.
- Custom application title, login background, standard logos, theme-aware logos and favicon.
- Administrator configuration page with live image previews.
- Allowlisted asset and dynamic CSS Controllers.
- Content-fingerprinted URLs and conditional HTTP caching.
- GitHub latest-stable-release check on the configuration page, with a six-hour success cache and retry after five minutes when unavailable.
- Upgrade-safe migration that preserves existing settings and copies previous administrator logo/favicon uploads to current neutral filenames without deleting originals.

### Changed
- Standardized the plugin version to `12.0.0` across plugin metadata.
- Declared compatibility with GLPI 12.0.x and PHP 8.3 or newer.
- Reorganized the repository so all plugin files and bundled assets reside under `mod/`; GitHub Actions remains repository infrastructure outside that directory.
- Replaced the previous GLPI-branded sample artwork with an original indigo-and-amber folded-ribbon identity.
- Made the plugin-root `logo.png` an icon-only symbol, with no wordmark, subtitle or version.
- Added an editable SVG master and reproducible generator for `logo.png`, light/dark logo variants, the multi-resolution sample ICO and a neutral 1920×1080 login background.
- Generated raster assets are included in the installation ZIP under `resources/images/`; no external product marks are used.
- Bundled sample logo PNGs use fixed reference canvases of 53×53, 100×55 and 250×138 pixels; uploaded logos are validated using minimum dimensions, not exact-size equality.
- Upload minimums follow GLPI's published recommendations: 40×40 for collapsed navigation, 100×55 for the main navigation logo, and 220×130 for the login logo. Larger images are accepted.
- Keep the editable SVG and asset-generation documentation in the source repository; exclude them, Markdown notes and development scripts from the runtime ZIP.
- Expanded CI to stable PHP 8.3, 8.4 and 8.5, plus a non-blocking PHP 8.6 pre-release/nightly check.
- Use GLPI's native PSR-4 plugin autoloader.
- Use GLPI's native light/dark logo variables rather than overriding internal logo element selectors.
- Generate Controller URLs through GLPI's URL-prefix helper, including installations hosted in a subdirectory.
- Remove obsolete token-based CSRF markup; GLPI 12 validates unsafe Controller requests using request-origin headers.
- Load the branding stylesheet only when logo or login-background customization is enabled.

### Fixed
- Preserve existing configuration values when optional POST fields are omitted.
- Validate image MIME, structure, dimensions and size before replacing existing files.
- Accept logo uploads at or above GLPI's recommended minimum dimensions, including the supplied 53×53, 107×55 and 253×130 CY Alimentos PNGs.
- Retain older uploads that fall below the minimum, but serve the correctly-sized bundled image until a compliant logo is uploaded.
- Select dark artwork on light GLPI themes and a genuinely light, high-contrast mark on dark themes; the generated 53×53 light mark is checked against the dark sidebar background.
- Validate ICO directory entries and embedded image data before accepting a favicon.
- Stage multi-file uploads and restore the previous files if a replacement or settings write fails.
- Use explicit MIME types and ETag/last-modified validation for public assets.
- Log Controller exceptions through GLPI's error handler.
- Support installation paths using the GLPI root-document prefix and the canonical plugin URL.
