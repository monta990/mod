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
# Development helpers in mod/scripts/ are deliberately excluded.
cp -a "$ROOT/LICENSE.txt" "$ROOT/README.md" "$ROOT/CHANGELOG.md" "$ROOT/TESTING.md" "$ROOT/logo.png" "$ROOT/mod.xml" "$ROOT/setup.php" "$ROOT/hook.php" "$PACKAGE_ROOT/"
cp -a "$ROOT/src" "$ROOT/public" "$ROOT/templates" "$ROOT/resources" "$ROOT/locales" "$PACKAGE_ROOT/"

rm -f "$DIST/mod-12.0.0.zip"
(
    cd "$WORK"
    zip -qr "$DIST/mod-12.0.0.zip" mod
)

# Defensive package-shape checks: the archive must have exactly one root dir.
if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -qv '^mod/'; then
    echo "Unexpected archive entry outside mod/." >&2
    exit 1
fi

if unzip -Z1 "$DIST/mod-12.0.0.zip" | grep -Eq '^mod/(scripts|devtools)/'; then
    echo "Development tooling must not be included in the plugin ZIP." >&2
    exit 1
fi

rm -rf "$WORK"
echo "Created $DIST/mod-12.0.0.zip"
