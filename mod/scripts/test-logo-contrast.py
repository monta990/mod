#!/usr/bin/env python3
"""Regression test for generated dark-theme logo contrast."""
from pathlib import Path
from tempfile import TemporaryDirectory
import importlib.util
import sys

from PIL import Image

GENERATOR_PATH = Path(__file__).with_name('generate-assets.py')
SPEC = importlib.util.spec_from_file_location('generate_assets', GENERATOR_PATH)
if SPEC is None or SPEC.loader is None:
    raise RuntimeError('Unable to load the artwork generator for contrast testing.')
generate_assets = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = generate_assets
SPEC.loader.exec_module(generate_assets)

DARK_THEME_MARK_BACKGROUND = generate_assets.DARK_THEME_MARK_BACKGROUND
LIGHT_MARK_MIN_CONTRAST = generate_assets.LIGHT_MARK_MIN_CONTRAST
make_light_mark = generate_assets.make_light_mark
render_master = generate_assets.render_master
validate_light_mark_contrast = generate_assets.validate_light_mark_contrast


def main() -> None:
    plugin_root = Path(__file__).resolve().parents[1]
    master = render_master(plugin_root / "resources/images/sample-ribbon-mark.svg")
    light_mark = make_light_mark(master)

    # Assert that both the source transform and actual shipped-size PNG
    # remain legible against the dark theme sidebar background.
    contrast = validate_light_mark_contrast(light_mark)
    assert contrast >= LIGHT_MARK_MIN_CONTRAST

    with TemporaryDirectory(prefix="mod-logo-contrast-") as temporary:
        output = Path(temporary) / "sample-logo-mark-light.png"
        light_mark.resize((53, 53), Image.Resampling.LANCZOS).save(output)
        with Image.open(output) as generated:
            actual = validate_light_mark_contrast(generated.convert("RGBA"))
        assert actual >= LIGHT_MARK_MIN_CONTRAST

    print(
        f"Dark-theme mark contrast test passed: {contrast:.2f}:1 minimum "
        f"against {DARK_THEME_MARK_BACKGROUND}."
    )


if __name__ == "__main__":
    main()
