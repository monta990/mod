# HANDOFF — UI Branding 12.0.4

## Current state

- Candidate version: `12.0.4`.
- Base version: `12.0.3`.
- Target platform: GLPI 12.0.x and PHP 8.3 or newer.
- Branch: `fix/12.0.3-github-http-client`.
- Status: draft pull request; do not merge or publish until review and CI are complete.

## Changes in this candidate

- Add SHA-256 fingerprints to every server-rendered saved-preview URL so a replaced upload cannot remain stuck behind the asset controller's one-year immutable cache.
- Restore server-rendered previews for the saved background, favicon and all standard/theme logos.
- Render newly selected images on existing canvas elements using `createImageBitmap`; upload data is never assigned to `src` or `href`.
- Fall back to the selected filename rendered with `textContent` when the browser cannot decode a format.
- Restore saved images when file selections are cleared and ignore stale asynchronous image decodes.
- Invalidate the GitHub latest-release cache when the installed plugin version changes; retain the six-hour success and five-minute failure TTLs.
- Add JavaScript and PHP regression tests for preview restoration, canvas rendering, safe filename fallback, selection clearing and cache invalidation.

## Architecture and compatibility

- GitHub release lookup uses GLPI's native `Glpi\\Toolbox\\HttpClient`, honoring GLPI proxy/TLS settings with a 10-second request timeout.
- Settings remain in `GLPI_PLUGIN_DOC_DIR/mod/settings.ini`; administrator uploads remain under `uploads/`.
- Public branding assets continue to be served by the existing allowlisted GLPI Controllers.
- GitHub release lookup continues to use the public `releases/latest` endpoint.
- Server-side MIME, file-structure, dimension and size validation remains authoritative.
- The version cache remains a file under the plugin data directory and stores the version that was checked.

## Validation status

- Source comparison confirmed that 12.0.1 replaced saved image elements with empty status containers; this candidate restores them.
- The GitHub version checker and controller were unchanged since 12.0.0; cache invalidation now covers a plugin upgrade.
- The public GitHub API currently reports 12.0.1 as the latest stable release; installed 12.0.3/12.0.4 builds should report that the installed version is newer until the newer version is published.
- Automated validation must complete on this candidate: PHP 8.3/8.4/8.5, experimental PHP 8.6, Node tests, cache tests, upgrade preservation, gettext, package build and security workflows.

## Manual validation required

- Test on a real GLPI 12.0.x staging instance with saved assets and every upload type.
- Confirm previews of saved background, favicon and standard/theme logos render from fingerprinted controller URLs; after replacing each resource, verify its `?v=` value changes and the browser requests the new image.
- Select/clear valid JPEG and PNG files; verify canvas preview and restoration.
- Test ICO decoding in the target browser; if unsupported, confirm the safe filename fallback.
- Confirm version check reports the latest stable release after an upgrade and does not reuse a cache from the previous plugin version.
- Do not publish until the exact CI-built `mod-12.0.4.zip` is installed and tested on staging.

## Next steps

1. Review the draft PR and resolve any review findings.
2. Confirm all required CI and Security Analysis jobs pass on its exact head commit.
3. Install and test the exact build artifact on GLPI 12.0.x staging.
4. Merge only after review and functional verification.
5. Create numeric tag `12.0.4` and publish the matching release asset.