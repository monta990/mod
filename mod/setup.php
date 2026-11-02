<?php

/**
 * UI Branding plugin for GLPI 12.
 *
 * @copyright Copyright (C) 2026 by Edwin Elias Alvarez.
 * @license GPL-3.0-or-later
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Mod\BrandingHooks;

const PLUGIN_MOD_VERSION = '12.0.0';
const PLUGIN_MOD_MIN_GLPI_VERSION = '12.0.0';
const PLUGIN_MOD_MAX_GLPI_VERSION = '12.1.0';

/**
 * Initialize UI Branding.
 *
 * GLPI 12 registers the plugin PSR-4 autoloader from the plugin directory.
 */
function plugin_init_mod(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['mod'] = 'UIBranding';

    BrandingHooks::init();
}

/**
 * Return plugin metadata and supported platform versions.
 */
/**
 * @return array{
 *   name: string,
 *   version: string,
 *   author: string,
 *   license: string,
 *   homepage: string,
 *   requirements: array{
 *     glpi: array{min: string, max: string},
 *     php: array{min: string}
 *   }
 * }
 */
function plugin_version_mod(): array
{
    return [
        'name'         => 'UI Branding',
        'version'      => PLUGIN_MOD_VERSION,
        'author'       => 'Edwin Elias Alvarez',
        'license'      => 'GPL-3.0-or-later',
        'homepage'     => 'https://github.com/monta990/mod/',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_MOD_MIN_GLPI_VERSION,
                'max' => PLUGIN_MOD_MAX_GLPI_VERSION,
            ],
            'php' => [
                'min' => '8.3',
            ],
        ],
    ];
}
