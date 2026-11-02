<?php

use GlpiPlugin\Mod\BrandManager;

function plugin_mod_install(array $params = []): bool
{
    (new BrandManager())->install();
    return true;
}

function plugin_mod_uninstall(): bool
{
    (new BrandManager())->uninstall();
    return true;
}

function plugin_mod_activate(): bool
{
    (new BrandManager())->install();
    return true;
}

function plugin_mod_deactivate(): bool
{
    global $CFG_GLPI;
    // Runtime branding is hook-based. Deactivation affects subsequent requests.
    $CFG_GLPI['app_name'] = 'GLPI';
    return true;
}
