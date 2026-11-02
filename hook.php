<?php

use GlpiPlugin\Mod\BrandManager;

/**
 * Install UI Branding.
 */
function plugin_mod_install(): bool
{
    (new BrandManager())->install();

    return true;
}

/**
 * Remove UI Branding and its persistent data.
 */
function plugin_mod_uninstall(): bool
{
    (new BrandManager())->uninstall();

    return true;
}
