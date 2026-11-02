# UI Branding sample artwork

The sample identity is an original folded-ribbon emblem in indigo and amber, created specifically for UI Branding. It does not use the GLPI logo, GLPI product artwork, or third-party brand marks.

## Source and generated output

- `sample-ribbon-mark.svg` is the vector master.
- `scripts/generate-assets.py` creates the plugin `logo.png`, PNG logo variants, a multi-resolution ICO, and a sample login background during release packaging.
- Generated raster assets are included in the installation ZIP under this directory.
- A clean installation uses these demonstration assets until an administrator uploads their own branding.

## Palette

| Role | Color |
|---|---|
| Indigo | `#314FA8` |
| Deep navy | `#102553` |
| Amber | `#FFC62E` |
| Orange | `#F08B13` |
| Light canvas | Transparent |
| Dark canvas | Transparent |

The PNG assets have transparent backgrounds except for `background.jpg`, which is a neutral 1920×1080 login background.
