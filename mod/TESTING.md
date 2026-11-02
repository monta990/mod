# UI Branding — Testing Guide

This document describes the checks for UI Branding 12.0.1 on GLPI 12.0.x. Run installation, upgrade and visual checks on a disposable or staging instance before deploying to production.

## 1. Test environment

Required target platform:

- GLPI 12.0.x.
- PHP 8.3 or newer.
- A web server/PHP configuration supported by the tested GLPI installation.
- An administrator account with GLPI's configuration update permission.

The required stable CI matrix is PHP 8.3, 8.4 and 8.5. PHP 8.6 pre-release/nightly is experimental and non-blocking.

Before testing an update, back up the database and `GLPI_PLUGIN_DOC_DIR/mod/`. Record the active page title, enabled branding options, and the names of existing files under `uploads/`.

## 2. Installation and access

- [ ] Extract the ZIP and confirm it contains exactly one top-level directory: `mod/`.
- [ ] Install and enable UI Branding through **Setup → Plugins**.
- [ ] Confirm GLPI reports the plugin as compatible on GLPI 12.0.x.
- [ ] Open the configuration page as a user with configuration update permission.
- [ ] Open the configuration page as a user without that permission and confirm access is denied.
- [ ] Confirm the page renders without PHP/Twig errors and the installed version is shown.

## 3. Configuration and visual behavior

### General settings

- [ ] Change the Page Title, save, and confirm the application name changes.
- [ ] Disable custom logos and confirm the plugin does not apply its logo stylesheet.
- [ ] Enable custom logos and confirm the selected logo set is displayed.
- [ ] Enable custom favicon and verify it is applied on both authenticated and public login pages.
- [ ] Enable the login background and verify it appears on the public login page.
- [ ] Disable each option and verify the corresponding customization is no longer applied.
- [ ] Save settings without selecting replacement files and confirm existing uploads are retained.

### Standard logos

- [ ] Upload a valid PNG at or above each minimum: small 40×40, medium 100×55, and large/login 220×130.
- [ ] Upload a larger-than-minimum PNG and confirm it is accepted.
- [ ] Enable standard logos and inspect the collapsed sidebar, expanded navigation and login page.

### Theme-based logos

- [ ] Enable theme-based logos and verify the standard-logo upload section is hidden while the theme-specific section is available.
- [ ] Upload the white small/medium/large assets and the black small/medium/large assets.
- [ ] Test a light GLPI theme and confirm black variants are selected.
- [ ] Test a dark GLPI theme and confirm white variants are selected.
- [ ] Verify the small collapsed-sidebar mark remains legible against the dark sidebar.

### Background and favicon

- [ ] Upload a valid JPG/JPEG background of at least 1920×1080, verify the selected filename status, save, and confirm the background on the login page.
- [ ] Upload a valid ICO and verify the browser tab/favicon in authenticated and anonymous pages.
- [ ] Confirm selecting, replacing and clearing a file only updates the text status; empty status containers remain hidden, and no upload-derived value is assigned to a DOM URL attribute.

## 4. Upload validation and failure handling

Perform these checks with disposable test files:

- [ ] Reject a non-PNG file in a logo field.
- [ ] Reject a PNG below the corresponding minimum dimensions.
- [ ] Reject a non-JPEG file in the background field.
- [ ] Reject a background below 1920×1080.
- [ ] Reject a malformed ICO or one without a valid embedded image.
- [ ] Reject an upload exceeding the resource's maximum size.
- [ ] Submit multiple uploads where one is invalid and confirm no previously installed resource is partially replaced.
- [ ] Cause a save failure in a controlled test environment, where practical, and confirm that previous files/settings remain recoverable.
- [ ] Confirm errors are logged and the configuration page remains usable.

A file below a minimum may also be placed in the persistent upload directory to verify legacy fallback behavior: it must remain on disk, while the bundled default is served.

## 5. Upgrade and data preservation

Use a test copy of a previous installation that contains `settings.ini`, old administrator uploads and unrelated files.

- [ ] Update the plugin without uninstalling it.
- [ ] Confirm `settings.ini` is byte-for-byte unchanged by the install/update hook.
- [ ] Confirm previous upload files are copied to their current neutral filenames.
- [ ] Confirm the original filenames remain in place.
- [ ] Confirm already-existing current-name uploads are not overwritten.
- [ ] Confirm unrelated files and old grey-logo files remain untouched.
- [ ] Run the install/update hook a second time and confirm the migration is idempotent.
- [ ] Confirm the migration marker is created only after successful copying.
- [ ] Confirm branding settings and served images remain correct after the update.

The automated regression test covers the preservation and repeated-update cases in a temporary directory.

## 6. HTTP, caching and version-check behavior

- [ ] Confirm asset and stylesheet URLs are generated through GLPI's plugin URL prefix and work when GLPI is installed in a subdirectory.
- [ ] Confirm public asset Controllers serve only supported resource keys.
- [ ] Confirm asset responses have appropriate MIME types, ETag and Last-Modified headers.
- [ ] Repeat a request with matching conditional headers and verify a not-modified response where applicable.
- [ ] Change an uploaded asset and confirm the fingerprinted URL changes.
- [ ] Confirm the GitHub version check reports the latest stable release and ignores drafts/pre-releases.
- [ ] Simulate GitHub/network unavailability and confirm the configuration page still loads and saved settings are unchanged.
- [ ] Confirm the successful version-check cache and failed-check retry behavior (six hours and five minutes respectively).

## 7. Automated checks

Run these from the repository root on a Linux CI-like environment. The asset build requires Python 3.12, the packages in `mod/scripts/requirements-assets.txt`, DejaVu Sans Bold, and `gettext` (`msgfmt`).

```bash
sudo apt-get update
sudo apt-get install -y gettext fonts-dejavu-core
python3 -m pip install -r mod/scripts/requirements-assets.txt

python3 mod/scripts/generate-assets.py mod
python3 mod/scripts/test-logo-contrast.py

node --check mod/public/uibranding.js
node --check mod/public/favicon.js
node --test mod/scripts/test-public-js.cjs

find mod/setup.php mod/hook.php mod/src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php -l mod/scripts/validate-release.php
php -l mod/scripts/test-upgrade-preservation.php

php mod/scripts/test-upgrade-preservation.php
php mod/scripts/validate-release.php

for po in mod/locales/*.po; do
    msgfmt --check --check-format -o /tmp/catalog.mo "$po"
done

bash mod/scripts/package.sh dist
unzip -l dist/mod-12.0.1.zip
```

The package check must confirm:

- [ ] Exactly one top-level `mod/` directory.
- [ ] `mod/README.md` and `mod/TESTING.md` are present.
- [ ] Generated runtime assets and compiled translation catalogs are present.
- [ ] Development scripts, source SVG, and development-only Markdown are absent.
- [ ] No obsolete GLPI-branded sample artwork is present.
- [ ] No private `vendor/`, Composer or npm runtime dependencies are included.

## 8. CI and security analysis

The GitHub Actions workflows provide additional automated checks:

- PHP syntax checks and release metadata/layout validation.
- Upgrade-preservation regression test.
- Gettext catalog validation and release ZIP build.
- Stable PHP 8.3, 8.4 and 8.5 CI; experimental PHP 8.6 is non-blocking.
- GLPI-aware parallel PHP lint with deprecated-syntax detection.
- TwigCS using GLPI's Twig ruleset.
- PHPStan level 8 with GLPI extensions and Psalm security/taint analysis.
- CodeQL security-extended analysis for JavaScript/TypeScript, Python and GitHub Actions.
- Gitleaks repository-history scanning and zizmor workflow auditing.

Review the results for the exact commit being evaluated; a successful run on an earlier commit does not validate later changes.

## 9. Release acceptance

Do not merge or publish solely because the ZIP builds. Before accepting a release candidate:

- [ ] Test installation and update on a GLPI 12.0.x staging instance.
- [ ] Complete the functional, visual, upload, permission and upgrade checks above.
- [ ] Confirm all required CI and Security Analysis jobs pass on the candidate commit.
- [ ] Inspect the final `mod-12.0.1.zip` and test that exact archive.
- [ ] Confirm the plugin version remains `12.0.1` and compatibility remains GLPI 12.0.x.
- [ ] Review all open and newly added pull-request findings.

This guide is a test checklist, not a claim that every manual test has been executed.
