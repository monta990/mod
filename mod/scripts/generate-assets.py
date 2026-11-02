#!/usr/bin/env python3
"""Generate original UI Branding sample artwork from its SVG master."""
from __future__ import annotations

import argparse
from io import BytesIO
from pathlib import Path

import cairosvg
from PIL import Image, ImageDraw, ImageFont, ImageFilter

FONT_PATH = Path('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf')
DARK_THEME_MARK_BACKGROUND = (20, 30, 52)
LIGHT_MARK_MIN_CONTRAST = 4.5


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


def make_light_mark(master: Image.Image) -> Image.Image:
    """Create a clearly legible white/amber mark for dark GLPI surfaces."""
    image = master.copy().convert('RGBA')
    pixels = image.load()

    for y in range(image.height):
        for x in range(image.width):
            red, green, blue, alpha = pixels[x, y]
            if alpha == 0:
                continue

            # Keep amber folds as the visual accent. Turn every indigo/navy
            # region into near-white; merely brightening blue was insufficient
            # against GLPI's dark collapsed-sidebar background.
            if blue >= red and blue >= green:
                pixels[x, y] = (248, 250, 255, alpha)
            else:
                # Preserve the amber fold shape but lift its luminance enough
                # to remain distinguishable on the dark sidebar.
                pixels[x, y] = (255, 213, 91, alpha)

    return image


def relative_luminance(rgb: tuple[int, int, int]) -> float:
    channels = []
    for component in rgb:
        value = component / 255
        channels.append(value / 12.92 if value <= 0.04045 else ((value + 0.055) / 1.055) ** 2.4)
    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2]


def contrast_ratio(foreground: tuple[int, int, int], background: tuple[int, int, int]) -> float:
    first = relative_luminance(foreground)
    second = relative_luminance(background)
    lighter, darker = max(first, second), min(first, second)
    return (lighter + 0.05) / (darker + 0.05)


def validate_light_mark_contrast(image: Image.Image) -> float:
    """Validate opaque mark pixels against the dark-theme sidebar color."""
    background_luminance = relative_luminance(DARK_THEME_MARK_BACKGROUND)
    samples = []
    for red, green, blue, alpha in image.get_flattened_data():
        if alpha >= 240:
            luminance = relative_luminance((red, green, blue))
            samples.append((luminance + 0.05) / (background_luminance + 0.05))

    if not samples:
        raise RuntimeError('The light logo variant contains no opaque pixels.')

    minimum = min(samples)
    if minimum < LIGHT_MARK_MIN_CONTRAST:
        raise RuntimeError(
            f'Light logo contrast is too low ({minimum:.2f}:1); '
            f'expected at least {LIGHT_MARK_MIN_CONTRAST:.1f}:1 '
            f'against {DARK_THEME_MARK_BACKGROUND}.'
        )
    return minimum


def make_mark(master: Image.Image, size: int, light_variant: bool = False) -> Image.Image:
    result = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    icon = make_light_mark(master) if light_variant else master.copy()

    pad = int(size * 0.08)
    icon.thumbnail((size - 2 * pad, size - 2 * pad), Image.Resampling.LANCZOS)
    result.alpha_composite(icon, ((size - icon.width) // 2, (size - icon.height) // 2))
    return result


def make_lockup(master: Image.Image, width: int, height: int, light_surface: bool) -> Image.Image:
    """Render the horizontal wordmark into GLPI's exact native canvas size."""
    result = Image.new('RGBA', (width, height), (0, 0, 0, 0))

    # The navigation logo is only 100x55 px, so reserve enough width for the
    # complete "UI Branding" wordmark instead of scaling the symbol too large.
    icon_size = min(max(1, int(height * 0.50)), max(1, int(width * 0.28)))
    left = max(1, round(height * 0.055))
    gap = max(2, round(height * 0.075))
    right = max(1, round(height * 0.055))

    icon = make_mark(master, icon_size, light_variant=not light_surface)
    result.alpha_composite(icon, (left, (height - icon_size) // 2))

    text_color = (16, 37, 83, 255) if light_surface else (248, 250, 255, 255)
    text = 'UI Branding'
    draw = ImageDraw.Draw(result)
    available_width = width - left - icon_size - gap - right
    font_size = max(7, int(height * 0.18))

    while font_size >= 6:
        font = load_font(font_size)
        bounds = draw.textbbox((0, 0), text, font=font)
        if bounds[2] - bounds[0] <= available_width:
            break
        font_size -= 1

    if font_size < 6:
        raise RuntimeError(
            f'The UI Branding wordmark does not fit the requested {width}x{height} canvas.'
        )

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

    watermark = make_mark(master, 600)
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

    light_mark = make_light_mark(master)
    contrast = validate_light_mark_contrast(light_mark)
    assets = {
        'sample-logo-mark.png': make_mark(master, 53),
        'sample-logo-mark-dark.png': make_mark(master, 53),
        'sample-logo-mark-light.png': make_mark(master, 53, light_variant=True),
        'sample-logo-horizontal.png': make_lockup(master, 100, 55, True),
        'sample-logo-horizontal-dark.png': make_lockup(master, 100, 55, True),
        'sample-logo-horizontal-light.png': make_lockup(master, 100, 55, False),
        'sample-logo-login.png': make_lockup(master, 250, 138, True),
        'sample-logo-login-dark.png': make_lockup(master, 250, 138, True),
        'sample-logo-login-light.png': make_lockup(master, 250, 138, False),
    }
    expected_dimensions = {
        'sample-logo-mark.png': (53, 53),
        'sample-logo-mark-dark.png': (53, 53),
        'sample-logo-mark-light.png': (53, 53),
        'sample-logo-horizontal.png': (100, 55),
        'sample-logo-horizontal-dark.png': (100, 55),
        'sample-logo-horizontal-light.png': (100, 55),
        'sample-logo-login.png': (250, 138),
        'sample-logo-login-dark.png': (250, 138),
        'sample-logo-login-light.png': (250, 138),
    }

    for name, image in assets.items():
        if image.size != expected_dimensions[name]:
            raise RuntimeError(
                f'{name} has unexpected dimensions {image.size}; '
                f'expected {expected_dimensions[name]}.'
            )
        destination = images_dir / name
        quantize_png(image, destination)
        with Image.open(destination) as generated:
            if generated.size != expected_dimensions[name]:
                raise RuntimeError(
                    f'Generated file {name} has unexpected dimensions {generated.size}.'
                )

    # Validate the quantized file shipped to users, not only the in-memory source.
    with Image.open(images_dir / 'sample-logo-mark-light.png') as generated_light:
        generated_light = generated_light.convert('RGBA')
        contrast = validate_light_mark_contrast(generated_light)

    icon = make_mark(master, 256)
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
    print(
        f' - sample-logo-mark-light.png: minimum contrast {contrast:.2f}:1 '
        f'against {DARK_THEME_MARK_BACKGROUND}'
    )
    for name in expected:
        print(f' - {name}: {(images_dir / name).stat().st_size} bytes')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('plugin_root', type=Path, help='Plugin root containing resources/images')
    arguments = parser.parse_args()
    generate(arguments.plugin_root.resolve())
