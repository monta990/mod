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
| Light mark on dark surfaces | `#F8FAFF` with warm amber folds |
| Light canvas | Transparent |
| Dark canvas | Transparent |

The dark-theme collapsed-sidebar mark uses a near-white indigo fold treatment and a lifted amber accent. The generator validates a minimum 4.5:1 contrast ratio against the dark-sidebar reference color `#141E34`, including the quantized PNG included in the release package.

The PNG assets have transparent backgrounds except for `background.jpg`, which is a neutral 1920×1080 login background.
