# Changelog

All notable changes to UI Branding are documented here.

## [12.0.0] - 2026-11-01

### Added
- Published the GLPI 12 runtime-branding baseline.
- Custom application title, login background, standard logos, theme-aware logos and favicon.
- Administrator configuration page with live image previews.
- Allowlisted asset and dynamic CSS Controllers.
- Content-fingerprinted URLs and conditional HTTP caching.

### Changed
- Standardized the plugin version to `12.0.0` across plugin metadata.
- Declared compatibility with GLPI 12.0.x and PHP 8.2 or newer.
- Use GLPI's native PSR-4 plugin autoloader.
- Use GLPI's native light/dark logo variables rather than overriding internal logo element selectors.
- Generate Controller URLs through GLPI's URL-prefix helper, including installations hosted in a subdirectory.
- Remove obsolete token-based CSRF markup; GLPI 12 validates unsafe Controller requests using request-origin headers.
- Load the branding stylesheet only when logo or login-background customization is enabled.

### Fixed
- Preserve existing configuration values when optional POST fields are omitted.
- Validate image MIME, structure, dimensions and size before replacing existing files.
- Validate ICO directory entries and embedded image data before accepting a favicon.
- Stage multi-file uploads and restore the previous files if a replacement or settings write fails.
- Use explicit MIME types and ETag/last-modified validation for public assets.
- Log Controller exceptions through GLPI's error handler.
- Support installation paths using the GLPI root-document prefix and the canonical plugin URL.
