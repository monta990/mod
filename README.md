# UI Branding

UI Branding customizes the GLPI 12 interface without changing GLPI core files.

## Features

- Custom application title and optional login background.
- Standard and theme-aware logos for navigation and the login page.
- Custom favicon and safe upload-selection status.
- Administrator-only configuration with validated uploads.
- A GitHub latest-stable-release check on the configuration page, cached for six hours.
- Upgrade-safe preservation of administrator settings and previously uploaded images.
- Public branding assets served through allowlisted GLPI Controllers.
- Content-fingerprinted asset URLs and HTTP caching.

## Compatibility

- GLPI 12.0.x only.
- PHP 8.3 or newer.
- Stable CI on PHP 8.3, 8.4 and 8.5.
- Experimental, non-blocking CI on PHP 8.6 pre-release/nightly.
- No Composer installation or plugin-local `vendor/` directory.

## Branding assets

The plugin-root `logo.png` contains only the folded-ribbon symbol. The bundled horizontal and login examples use only the name **UI Branding**, without a subtitle or version. The source SVG and asset generator are kept in `mod/resources/images/` and `mod/scripts/`.

## Upgrade behavior

Plugin updates retain `GLPI_PLUGIN_DOC_DIR/mod/settings.ini` and the `uploads/` directory. The install/update hook copies previous upload filenames to the current neutral resource names without deleting originals, overwriting existing new-name uploads, or modifying unrelated files. Explicitly uninstalling the plugin is a different operation and removes its persistent data directory.

## Repository layout

- `mod/` contains the complete plugin, translations, resources, package documentation and build/validation scripts.
- `.github/` contains repository automation.
- The root README, LICENSE, CHANGELOG and RELEASE policy keep project information visible on GitHub. Plugin-specific documentation copies remain inside `mod/` and are included in the installation package.

## Installation

Download the release ZIP and install it through **Setup → Plugins**. The ZIP contains a single top-level `mod/` folder. Install and enable **UI Branding**, then open its configuration page.

Release tags use the numeric version only (for example, `12.0.1`), with no leading `v`. This is the standard convention across the maintained GLPI plugins; see [release policy](RELEASE.md).

See the [plugin documentation](mod/README.md), [test plan](mod/TESTING.md), [changelog](CHANGELOG.md) and [license](LICENSE).
