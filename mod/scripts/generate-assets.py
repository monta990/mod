#!/usr/bin/env python3
"""Generate original UI Branding sample artwork from its SVG master."""
from __future__ import annotations

import argparse
from io import BytesIO
from pathlib import Path

import cairosvg
from PIL import Image, ImageDraw, ImageFont, ImageFilter

FONT_PATH = Path('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf')


def load_font(size: int) -> ImageFont.FreeTypeFont:
    if not FONT_PATH.is_file():
        raise RuntimeError(
            'DejaVu Sans Bold is required to generate demo assets. '
            'Install the fonts-dejavu-core package.'
        )
    return ImageFont.truetype(str(FONT_PATH), size)


def quantize_png(image: Image.Image, path: Path) -> None:
    image.convert('RGBA').quantize(
        colors=96,
        method=Image.Quantize.FASTOCTREE,
        dither=Image.Dither.NONE,
    ).save(path, optimize=True)


def render_master(svg_path: Path) -> Image.Image:
    rendered = cairosvg.svg2png(
        url=str(svg_path),
        output_width=1024,
        output_height=1024,
    )
    return Image.open(BytesIO(rendered)).convert('RGBA')


def make_mark(master: Image.Image, size: int, bright_blue: bool = False) -> Image.Image:
    result = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    icon = master.copy()

    if bright_blue:
        pixels = icon.load()
        for y in range(icon.height):
            for x in range(icon.width):
                red, green, blue, alpha = pixels[x, y]
                if alpha and blue > red and blue > green:
                    pixels[x, y] = (
                        min(255, int(red * 1.10)),
                        min(255, int(green * 1.13)),
                        min(255, int(blue * 1.25)),
                        alpha,
                    )

    pad = int(size * 0.08)
    icon.thumbnail((size - 2 * pad, size - 2 * pad), Image.Resampling.LANCZOS)
    result.alpha_composite(icon, ((size - icon.width) // 2, (size - icon.height) // 2))
    return result


def make_lockup(master: Image.Image, width: int, height: int, light_surface: bool) -> Image.Image:
    result = Image.new('RGBA', (width, height), (0, 0, 0, 0))
    icon_size = min(int(height * 0.78), int(width * 0.38))
    left = int(height * 0.05)
    gap = int(height * 0.06)
    right = int(height * 0.04)

    icon = make_mark(master, icon_size, bright_blue=not light_surface)
    result.alpha_composite(icon, (left, (height - icon_size) // 2))

    text_color = (16, 37, 83, 255) if light_surface else (248, 250, 255, 255)
    text = 'UI Branding'
    draw = ImageDraw.Draw(result)
    available_width = width - left - icon_size - gap - right
    font_size = int(height * 0.24)
    font = load_font(font_size)
    while font_size > 10:
        bounds = draw.textbbox((0, 0), text, font=font)
        if bounds[2] - bounds[0] <= available_width:
            break
        font_size -= 1
        font = load_font(font_size)

    bounds = draw.textbbox((0, 0), text, font=font)
    text_height = bounds[3] - bounds[1]
    text_x = left + icon_size + gap
    text_y = (height - text_height) // 2 - bounds[1]
    draw.text((text_x, text_y), text, font=font, fill=text_color)
    return result


def make_background(master: Image.Image, destination: Path) -> None:
    width, height = 1920, 1080
    base = Image.new('RGB', (width, height))
    pixels = base.load()
    start = (10, 24, 54)
    end = (39, 63, 111)

    for y in range(height):
        for x in range(width):
            amount = (x / width) * 0.56 + (y / height) * 0.44
            pixels[x, y] = tuple(
                int(start[channel] * (1 - amount) + end[channel] * amount)
                for channel in range(3)
            )

    glow = Image.new('RGBA', (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(glow)
    draw.ellipse((1180, 590, 2110, 1520), fill=(242, 160, 28, 48))
    draw.ellipse((-420, -500, 760, 680), fill=(49, 79, 168, 70))
    glow = glow.filter(ImageFilter.GaussianBlur(130))
    result = Image.alpha_composite(base.convert('RGBA'), glow)

    watermark = make_mark(master, 600, bright_blue=True)
    watermark.putalpha(watermark.getchannel('A').point(lambda value: int(value * 0.10)))
    result.alpha_composite(watermark, (1180, 240))
    result.convert('RGB').save(destination, quality=78, optimize=True, progressive=True)


def generate(plugin_root: Path) -> None:
    plugin_root.mkdir(parents=True, exist_ok=True)
    images_dir = plugin_root / 'resources' / 'images'
    images_dir.mkdir(parents=True, exist_ok=True)
    svg_path = images_dir / 'sample-ribbon-mark.svg'
    if not svg_path.is_file():
        raise RuntimeError(f'SVG master not found: {svg_path}')
    master = render_master(svg_path)

    # Plugin logo is only the symbol: no name, subtitle or version text.
    quantize_png(make_mark(master, 512), plugin_root / 'logo.png')

    assets = {
        'sample-logo-mark.png': make_mark(master, 256, False),
        'sample-logo-mark-dark.png': make_mark(master, 256, False),
        'sample-logo-mark-light.png': make_mark(master, 256, True),
        'sample-logo-horizontal.png': make_lockup(master, 640, 352, True),
        'sample-logo-horizontal-dark.png': make_lockup(master, 640, 352, True),
        'sample-logo-horizontal-light.png': make_lockup(master, 640, 352, False),
        'sample-logo-login.png': make_lockup(master, 1000, 552, True),
        'sample-logo-login-dark.png': make_lockup(master, 1000, 552, True),
        'sample-logo-login-light.png': make_lockup(master, 1000, 552, False),
    }
    for name, image in assets.items():
        quantize_png(image, images_dir / name)

    icon = make_mark(master, 256, False)
    icon.save(
        images_dir / 'sample-icon.ico',
        format='ICO',
        sizes=[(16, 16), (32, 32), (48, 48), (64, 64)],
    )
    make_background(master, images_dir / 'background.jpg')

    expected = [
        'background.jpg', 'sample-icon.ico', 'sample-logo-mark.png',
        'sample-logo-mark-dark.png', 'sample-logo-mark-light.png',
        'sample-logo-horizontal.png', 'sample-logo-horizontal-dark.png',
        'sample-logo-horizontal-light.png', 'sample-logo-login.png',
        'sample-logo-login-dark.png', 'sample-logo-login-light.png',
    ]
    missing = [name for name in expected if not (images_dir / name).is_file()]
    if missing:
        raise RuntimeError(f'Generated asset files are missing: {missing}')

    print(f'Generated UI Branding sample assets in {plugin_root}')
    for name in expected:
        print(f' - {name}: {(images_dir / name).stat().st_size} bytes')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('plugin_root', type=Path, help='Plugin root containing resources/images')
    arguments = parser.parse_args()
    generate(arguments.plugin_root.resolve())
