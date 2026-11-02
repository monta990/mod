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

# Copy only the runtime plugin and its user-facing documentation.
# Development helpers stay in the source repository and are not shipped.
cp -a "$ROOT/LICENSE.txt" "$ROOT/README.md" "$ROOT/CHANGELOG.md" "$ROOT/TESTING.md" "$ROOT/mod.xml" "$ROOT/setup.php" "$ROOT/hook.php" "$PACKAGE_ROOT/"
cp -a "$ROOT/src" "$ROOT/public" "$ROOT/templates" "$ROOT/resources" "$ROOT/locales" "$PACKAGE_ROOT/"

# Compile every catalog for the package; the English msgid catalogue remains
# the base language and the existing locales provide their translated strings.
if ! command -v msgfmt >/dev/null 2>&1; then
    echo "gettext (msgfmt) is required to build translated plugin catalogs." >&2
    exit 1
fi
for po in "$PACKAGE_ROOT"/locales/*.po; do
    msgfmt --check --check-format -o "${po%.po}.mo" "$po"
done

# Raster assets and logo.png are generated from the original SVG master.
python3 "$ROOT/scripts/generate-assets.py" "$PACKAGE_ROOT"

rm -f "$DIST/mod-12.0.0.zip"
(
    cd "$WORK"
    zip -qr "$DIST/mod-12.0.0.zip" mod
)

# Defensive package-shape checks: the archive has exactly one root directory.
if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qv '^mod/'; then
    echo "Unexpected archive entry outside mod/." >&2
    exit 1
fi

if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -Eq '^mod/(scripts|devtools)/'; then
    echo "Development tooling must not be included in the plugin ZIP." >&2
    exit 1
fi

# Confirm the package contains the generated artwork and no third-party logos.
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/logo.png'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/background.jpg'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/sample-icon.ico'
unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qx 'mod/resources/images/sample-logo-horizontal.png'
if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -Eq 'logo-(GLPI|G-100)'; then
    echo "Third-party GLPI branding files found in the release archive." >&2
    exit 1
fi

rm -rf "$WORK"
echo "Created $DIST/mod-12.0.0.zip"
