#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="${1:-$ROOT/dist}"
mkdir -p "$DIST"
DIST="$(cd "$DIST" && pwd)"
WORK="$DIST/package"
PACKAGE_ROOT="$WORK/mod"

rm -rf "$WORK"
mkdir -p "$PACKAGE_ROOT" "$DIST"

cp -a "$ROOT/LICENSE.txt" "$ROOT/README.md" "$ROOT/CHANGELOG.md" "$ROOT/logo.png" "$ROOT/mod.xml" "$ROOT/setup.php" "$ROOT/hook.php" "$PACKAGE_ROOT/"
cp -a "$ROOT/src" "$ROOT/public" "$ROOT/templates" "$ROOT/resources" "$ROOT/locales" "$PACKAGE_ROOT/"

rm -f "$DIST/mod-12.0.0.zip"
(
    cd "$WORK"
    zip -qr "$DIST/mod-12.0.0.zip" mod
)

rm -rf "$WORK"
echo "Created $DIST/mod-12.0.0.zip"
