# UI Branding 12.0.0

UI Branding customizes the GLPI 12 interface without modifying GLPI core files.

## Features

- Custom application title.
- Optional login-page background.
- Standard logos for the expanded menu, collapsed menu and login page.
- Theme-aware logos using GLPI's native light/dark logo variables.
- Custom favicon.
- Live image previews on the configuration page.
- Administrator-only configuration and cache-clearing controls.
- Public branding assets served through allowlisted GLPI Controllers.
- SHA-256 content fingerprints and HTTP validators for browser caching.

## Compatibility

- GLPI 12.0.x, from 12.0.0 up to (but not including) 12.1.0.
- PHP 8.3 or newer (the published minimum for GLPI 12).
- PHP 8.5 is included in stable CI checks; PHP 8.6 is tested as a non-blocking pre-release/nightly job only.
- No Composer installation or plugin-local `vendor/` directory is required.

## Installation

The release ZIP must contain a top-level `mod/` directory.

1. Upload the ZIP from **Setup → Plugins**.
2. Install and enable **UI Branding**.
3. Open its configuration page and set the title, logos, favicon and optional login background.
4. Save the settings. Clear the GLPI cache only when needed; the branding CSS and image URLs are content-versioned.

## Architecture

Branding is applied at runtime through GLPI plugin hooks and Symfony Controllers. The plugin does not overwrite files in GLPI's `public/pics` directory and does not introduce legacy `front/` or `ajax/` entry points.

Administrator settings and uploaded files are stored under:

`GLPI_PLUGIN_DOC_DIR/mod/`

Uploaded resources are stored in `uploads/`. Bundled fallback images remain in the installed plugin under `resources/imagenes/`; they are not copied to persistent storage. Asset routes expose only the resource identifiers declared by the plugin.

The configuration Controller requires the GLPI `config` right with `UPDATE`. GLPI 12 applies its built-in origin-based CSRF protection to unsafe Controller requests; the plugin does not implement a separate token mechanism.

## Logo behavior

When theme-aware logos are enabled, the plugin supplies GLPI's native variables:

- `--glpi-logo-light` and `--glpi-logo-light-reduced` for white expanded/collapsed navigation logos.
- `--glpi-logo-dark` and `--glpi-logo-dark-reduced` for black expanded/collapsed navigation logos.
- `--glpi-logo-dark-login` and `--glpi-logo-light-login` for the login logo in the corresponding GLPI palette.

This keeps the expanded/collapsed menu and login behavior aligned with GLPI's own theme implementation instead of targeting internal logo elements directly. The draft is awaiting the selected original demonstration artwork. Do not publish or install a release ZIP until `logo.png`, the neutral sample logo variants and the sample favicon have replaced the previous third-party-branded files in `resources/imagenes/`.

## Data and uninstall

The plugin does not migrate settings from unrelated earlier plugin generations. Its current settings and uploads remain in `GLPI_PLUGIN_DOC_DIR/mod/` when the plugin is upgraded.

Uninstalling the plugin removes this plugin-owned data directory, including uploaded images and settings. Back up that directory before uninstalling if the branding configuration must be retained.

## Release validation

All plugin files, bundled resources and documentation are stored inside the repository's `mod/` directory. GitHub Actions configuration remains outside this plugin directory as repository automation only.

See [TESTING.md](TESTING.md) for the GLPI 12.0.0/12.0.1 manual test matrix and release checks.
