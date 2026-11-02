# UI Branding

UI Branding customizes the GLPI 12 interface without modifying GLPI core files.

## Compatibility

| Component | Supported version |
|---|---|
| GLPI | 12.0.x (`>=12.0.0 <12.1.0`) |
| PHP | 8.3 or newer |
| Stable CI matrix | PHP 8.3, 8.4 and 8.5 |
| Experimental CI | PHP 8.6 pre-release/nightly (non-blocking) |

The plugin uses GLPI's native plugin autoloader and Symfony-based Controllers. It does not require a plugin-local Composer installation, `vendor/` directory, or npm runtime dependency.

## Features

- Customize the GLPI application title.
- Replace standard navigation and login logos.
- Use separate black and white logo assets according to the active GLPI light/dark theme.
- Replace the public login-page background.
- Replace the browser favicon.
- Show an accessible, safe upload-selection state before saving; file data is never copied into DOM URL attributes.
- Validate uploaded files before replacing existing resources.
- Preserve administrator settings and uploaded branding assets across plugin updates.
- Serve public branding assets through allowlisted GLPI Controllers with content fingerprints and conditional HTTP caching.
- Check the latest stable GitHub release from the configuration page without blocking the page when GitHub is unavailable.

## Installation and upgrade

1. Back up the GLPI database and the plugin's persistent data directory before changing a production installation.
2. Extract the release archive. It contains one top-level directory named `mod/`.
3. Place that directory in the GLPI plugin directory so the resulting path is `<GLPI_ROOT>/plugins/mod/`.
4. In GLPI, open **Setup → Plugins**, install UI Branding if it is not already installed, and enable it.
5. Open the UI Branding configuration page and apply the required branding settings.

The plugin is designed for GLPI 12.0.x only. Do not install it on GLPI 10, GLPI 11, or GLPI 12.1+.

### Updating an existing installation

Replace the plugin code with the new package and run the normal GLPI plugin update process. The install/update hook:

- Keeps the existing `GLPI_PLUGIN_DOC_DIR/mod/settings.ini`.
- Keeps administrator uploads in `GLPI_PLUGIN_DOC_DIR/mod/uploads/`.
- Copies recognized previous upload filenames to the current neutral filenames.
- Does not delete the old files, overwrite an upload already using a current filename, or modify unrelated files.
- Uses an idempotent migration marker after all required copies succeed.

The migration includes the earlier favicon and standard/theme logo filenames. Existing uploaded files remain available for recovery. A repeated update does not repeat completed migration work.

**Uninstall is different from an update:** explicitly uninstalling UI Branding removes its plugin-owned persistent data directory. Back up custom settings and uploads before uninstalling.

## Configuration

The configuration page is available to users with GLPI's configuration update permission.

| Setting | Purpose |
|---|---|
| Page Title | Changes the application name shown by GLPI. |
| Show custom logos | Enables the configured navigation and login logos. |
| Enable theme-based logo modifications | Selects separate black/white logo resources for light and dark GLPI themes instead of the standard logo set. |
| Show login background | Enables the uploaded login-page background. |
| Show custom favicon | Enables the uploaded browser favicon. |

When theme-based logos are enabled, upload the white variants for dark surfaces and the black variants for light surfaces. The plugin selects the matching resource from GLPI's active theme.

The upload status areas show the selected filename when a file is chosen. No image thumbnail is rendered before saving; upload data is never copied into DOM URL attributes. The new file and settings are applied only after saving.

### Image requirements

The dimensions below are **minimums**, not exact canvas requirements. Larger images are accepted.

| Resource | Format | Minimum dimensions | Maximum upload size |
|---|---|---:|---:|
| Small navigation mark | PNG | 40 × 40 px | 2 MiB |
| Medium navigation logo | PNG | 100 × 55 px | 2 MiB |
| Large/login logo | PNG | 220 × 130 px | 2 MiB |
| Theme small white/black marks | PNG | 40 × 40 px | 2 MiB |
| Theme medium white/black logos | PNG | 100 × 55 px | 2 MiB |
| Theme large white/black logos | PNG | 220 × 130 px | 2 MiB |
| Login background | JPG/JPEG | 1920 × 1080 px | 15 MiB |
| Favicon | ICO | Valid ICO containing a supported embedded image | 2 MiB |

Uploads are checked for file type, MIME/content consistency, image structure, size and applicable minimum dimensions. A failed upload does not intentionally replace the existing resource. The logo dimensions are based on GLPI's recommended minimums; an image is not rejected merely because it is larger than the sample canvas.

If an older stored logo is below the current minimum, the file is retained in persistent storage but is not served; the bundled sample is used as the fallback until a compliant replacement is uploaded.

## Applying changes and cache

After saving, UI Branding displays a message recommending that both the GLPI cache and browser cache be cleared. Use **Clear cache** on the configuration page to ask GLPI to reset its caches. Also refresh or clear the browser cache when an old logo, favicon or background remains visible.

The plugin fingerprints its branding assets and generated stylesheet so changed resources receive new asset URLs. Public asset responses support ETag and Last-Modified validation.

## GitHub version check

The configuration page checks the repository's latest stable GitHub Release. Drafts and pre-releases are ignored. Successful checks are cached for six hours; failed checks are retried after five minutes. Network or GitHub API failures are reported without preventing the configuration page from loading or changing saved branding settings.

Release information: <https://github.com/monta990/mod/releases>

## Persistent data

UI Branding stores its settings and administrator uploads under:

- `GLPI_PLUGIN_DOC_DIR/mod/settings.ini`
- `GLPI_PLUGIN_DOC_DIR/mod/uploads/`

The plugin does not edit GLPI core files. Keep this data directory in the site's normal backup plan.

## Package contents

The installation ZIP contains a single top-level `mod/` directory, plugin runtime files, translations, generated PNG/ICO/JPG artwork, and these two administrator documents:

- `README.md` — installation, configuration and upgrade guide.
- `TESTING.md` — functional and release validation checklist.

The editable SVG master, asset-generation scripts, CI/security tooling and other development-only files are kept in the source repository and are not shipped in the installation ZIP.

## Project files

- [Source repository](https://github.com/monta990/mod)
- [Pull request](https://github.com/monta990/mod/pull/1)
- [Changelog](https://github.com/monta990/mod/blob/main/CHANGELOG.md)
- [License](LICENSE.txt)

UI Branding 12.0.1 — GLPI 12.0.x.
