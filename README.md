# UI Branding

UI Branding customizes the GLPI 12 interface without changing GLPI core files.

## Features

- Custom application title and optional login background.
- Standard and theme-aware logos for navigation and the login page.
- Custom favicon and live image previews.
- Administrator-only configuration with validated uploads.
- Public branding assets served through allowlisted GLPI Controllers.
- Content-fingerprinted asset URLs and HTTP caching.

## Compatibility

- GLPI 12.0.x only.
- PHP 8.3 or newer.
- Stable CI on PHP 8.3, 8.4 and 8.5.
- Experimental, non-blocking CI on PHP 8.6 pre-release/nightly.
- No Composer installation or plugin-local `vendor/` directory.

## Repository layout

- `mod/` contains the complete plugin, translations, resources, package documentation and build/validation scripts.
- `.github/` contains repository automation.
- This root README, LICENSE and CHANGELOG keep project information visible on GitHub. Plugin-specific copies remain inside `mod/` and are included in the installation package.

## Installation

Download the release ZIP and install it through **Setup → Plugins**. The ZIP contains a single top-level `mod/` folder. Install and enable **UI Branding**, then open its configuration page.

See the [plugin documentation](mod/README.md), [test plan](mod/TESTING.md), [changelog](CHANGELOG.md) and [license](LICENSE).
