<?php

use GlpiPlugin\\Mod\\BrandManager;

/**
 * Install or update UI Branding.
 *
 * GLPI invokes this hook both for the initial installation and when applying
 * a plugin update. BrandManager::install() is idempotent and preserves the
 * existing settings.ini and administrator uploads.
 *
 * @param array $params
 */
function plugin_mod_install(array $params = []): bool
{
    (new BrandManager())->install();

    return true;
}

/**
 * Remove UI Branding and its persistent data after an explicit uninstall.
 *
 * This hook must not be used during a plugin update.
 */
function plugin_mod_uninstall(): bool
{
    (new BrandManager())->uninstall();

    return true;
}

/**
 * Re-run the safe initialization when the plugin is enabled again.
 */
function plugin_mod_activate(): bool
{
    (new BrandManager())->install();

    return true;
}
