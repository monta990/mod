#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="${1:-$ROOT/../dist}"
mkdir -p "$DIST"
DIST="$(cd "$DIST" && pwd)"
WORK="$DIST/package"
PACKAGE_ROOT="$WORK/mod"

rm -rf "$WORK"
mkdir -p "$PACKAGE_ROOT"

# Ship runtime files. Administrator documentation is copied explicitly after
# source/build cleanup so only the two intended Markdown guides are packaged.
cp -a "$ROOT/LICENSE.txt" "$ROOT/mod.xml" "$ROOT/setup.php" "$ROOT/hook.php" "$PACKAGE_ROOT/"
cp -a "$ROOT/src" "$ROOT/public" "$ROOT/templates" "$ROOT/resources" "$ROOT/locales" "$PACKAGE_ROOT/"

# Compile every translation catalogue for the installation package.
if ! command -v msgfmt >/dev/null 2>&1; then
    echo "gettext (msgfmt) is required to build translated plugin catalogs." >&2
    exit 1
fi
for po in "$PACKAGE_ROOT"/locales/*.po; do
    msgfmt --check --check-format -o "${po%.po}.mo" "$po"
done

# The SVG is a source/build asset. Generate raster files, then remove source
# artwork, resource notes and development tooling from the runtime package.
python3 "$ROOT/scripts/generate-assets.py" "$PACKAGE_ROOT"
find "$PACKAGE_ROOT" -type f \( -iname '*.svg' -o -iname '*.md' -o -iname '*.py' -o -iname '*.sh' \) -delete

# Include the administrator documentation referenced by the repository README.
cp -a "$ROOT/README.md" "$ROOT/TESTING.md" "$PACKAGE_ROOT/"

rm -f "$DIST/mod-12.0.1.zip"
(
    cd "$WORK"
    zip -qr "$DIST/mod-12.0.1.zip" mod
)

# Defensive package-shape checks: one top-level directory, no build artifacts.
if unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qv '^mod/'; then
    echo "Unexpected archive entry outside mod/." >&2
    exit 1
fi

if unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -Eq '^mod/(scripts|devtools)/'; then
    echo "Development tooling must not be included in the plugin ZIP." >&2
    exit 1
fi

if unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -Eiq '\.(svg|py|sh)$'; then
    echo "Source artwork and build scripts must not be included in the plugin ZIP." >&2
    exit 1
fi

# Only the two administrator-facing Markdown guides are allowed in the ZIP.
if unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -Ei '\.md$' | grep -Evq '^mod/(README|TESTING)\.md$'; then
    echo "Unexpected development Markdown found in the release archive." >&2
    exit 1
fi
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/README.md'
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/TESTING.md'

# Confirm required runtime branding assets are present.
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/logo.png'
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/resources/images/background.jpg'
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/resources/images/sample-icon.ico'
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/resources/images/sample-logo-mark.png'
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/resources/images/sample-logo-horizontal.png'
unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -qx 'mod/resources/images/sample-logo-login.png'

if unzip -Z1 "$DIST/mod-12.0.1.zip" | grep -Eq 'logo-(GLPI|G-100)'; then
    echo "Third-party GLPI branding files found in the release archive." >&2
    exit 1
fi

rm -rf "$WORK"
echo "Created $DIST/mod-12.0.1.zip"
