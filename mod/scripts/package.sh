#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "\${BASH_SOURCE[0]}")/.." && pwd)"
DIST="\${1:-$ROOT/../dist}"
mkdir -p "$DIST"
DIST="$(cd "$DIST" && pwd)"
WORK="$DIST/package"
PACKAGE_ROOT="$WORK/mod"

rm -rf "$WORK"
mkdir -p "$PACKAGE_ROOT"

# Ship only runtime files. Repository documentation and development helpers
# stay at the repository root and are not part of the GLPI plugin ZIP.
cp -a "$ROOT/LICENSE.txt" "$ROOT/mod.xml" "$ROOT/setup.php" "$ROOT/hook.php" "$PACKAGE_ROOT/"
cp -a "$ROOT/src" "$ROOT/public" "$ROOT/templates" "$ROOT/resources" "$ROOT/locales" "$PACKAGE_ROOT/"

# Compile every translation catalogue for the installation package.
if ! command -v msgfmt >/dev/null 2>&1; then
    echo "gettext (msgfmt) is required to build translated plugin catalogs." >&2
    exit 1
fi
for po in "$PACKAGE_ROOT"/locales/*.po; do
    msgfmt --check --check-format -o "\${po%.po}.mo" "$po"
done

# The SVG is a source/build asset. Use it to generate the PNG/ICO files, then
# remove it and all Markdown/build files from the final runtime package.
python3 "$ROOT/scripts/generate-assets.py" "$PACKAGE_ROOT"
find "$PACKAGE_ROOT" -type f \( -iname '*.svg' -o -iname '*.md' -o -iname '*.py' -o -iname '*.sh' \) -delete

rm -f "$DIST/mod-12.0.0.zip"
(
    cd "$WORK"
    zip -qr "$DIST/mod-12.0.0.zip" mod
)

# Defensive package-shape checks: one top-level directory, no build artifacts.
if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qv '^mod/'; then
    echo "Unexpected archive entry outside mod/." >&2
    exit 1
fi

if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -Eq '^mod/(scripts|devtools)/'; then
    echo "Development tooling must not be included in the plugin ZIP." >&2
    exit 1
fi

if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -Eiq '\.(svg|md|py|sh)$'; then
    echo "Source artwork, documentation and build scripts must not be included in the plugin ZIP." >&2
    exit 1
fi

# Confirm required runtime branding assets are present.
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/logo.png'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/background.jpg'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/sample-icon.ico'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/sample-logo-mark.png'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/sample-logo-horizontal.png'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/sample-logo-login.png'

if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -Eq 'logo-(GLPI|G-100)'; then
    echo "Third-party GLPI branding files found in the release archive." >&2
    exit 1
fi

rm -rf "$WORK"
echo "Created $DIST/mod-12.0.0.zip"
