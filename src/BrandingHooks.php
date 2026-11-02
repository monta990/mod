<?php

namespace GlpiPlugin\Mod;

use Glpi\Plugin\Hooks;
use Glpi\UI\ThemeManager;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

final class BrandingHooks
{
    public static function init(): void
    {
        global $PLUGIN_HOOKS, $CFG_GLPI;

        $headerTags = [];
        $hasCssBranding = BrandManager::isAnyLogoModified()
            || BrandManager::isLoginPageModified();

        if (BrandManager::isAnyLogoModified()) {
            $headerTags = self::getLogoPreloadTags();
        }

        // Avoid an extra stylesheet request when only the application title or
        // favicon is customized.
        if ($hasCssBranding) {
            $headerTags[] = [
                'tag' => 'link',
                'properties' => [
                    'rel'  => 'stylesheet',
                    'type' => 'text/css',
                    'href' => Paths::getBrandingCssUrl(BrandManager::getBrandingCssFingerprint()),
                ],
            ];
        }

        $faviconEnabled = BrandManager::isFaviconEnabled();
        if ($faviconEnabled) {
            $faviconUrl = Paths::getAssetUrl(
                'favicon',
                BrandManager::getResourceFingerprint('favicon'),
            );
            $headerTags[] = [
                'tag' => 'link',
                'properties' => [
                    'rel'              => 'icon',
                    'type'             => 'image/x-icon',
                    'sizes'            => '16x16',
                    'href'             => $faviconUrl,
                    'data-mod-favicon' => '1',
                ],
            ];
        }

        $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG]['mod'] = $headerTags;
        $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG_ANONYMOUS_PAGE]['mod'] = $headerTags;

        // Load upload-preview logic only on the plugin configuration route.
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $configPath = parse_url(Paths::getConfigUrl(), PHP_URL_PATH);
        $isConfigurationPage = is_string($requestPath)
            && is_string($configPath)
            && rtrim($requestPath, '/') === rtrim($configPath, '/');

        $javascript = [];
        if ($isConfigurationPage) {
            $javascript[] = 'uibranding.js';
        }
        if ($faviconEnabled) {
            $javascript[] = 'favicon.js';
        }
        if ($javascript !== []) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mod'] = $javascript;
        }

        // The favicon must also be applied on the public login page.
        if ($faviconEnabled) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_ANONYMOUS_PAGE]['mod'] = ['favicon.js'];
        }

        $title = BrandManager::getCurrentTitle();
        if ($title !== '') {
            $CFG_GLPI['app_name'] = $title;
        }
    }

    /**
     * Preload the logo assets that can be used by the current interface state.
     *
     * @return array<int, array{tag: string, properties: array<string, string>}>
     */
    private static function getLogoPreloadTags(): array
    {
        $themeLogos = BrandManager::isThemeLogosEnabled();
        $isAnonymous = !isset($_SESSION['glpiactiveprofile']);
        $themeIsDark = false;

        if ($themeLogos) {
            $themeIsDark = ThemeManager::getInstance()->getCurrentTheme()->isDarkTheme();
        }

        if ($isAnonymous) {
            $resourceName = $themeLogos
                ? ($themeIsDark ? 'logo_l_white' : 'logo_l_black')
                : 'logo_l';

            return [self::createPreloadTag($resourceName)];
        }

        if ($themeLogos) {
            return [
                self::createPreloadTag('logo_m_white'),
                self::createPreloadTag('logo_s_white'),
            ];
        }

        return [
            self::createPreloadTag('logo_m'),
            self::createPreloadTag('logo_s'),
        ];
    }

    /**
     * @return array{tag: string, properties: array<string, string>}
     */
    private static function createPreloadTag(string $resourceName): array
    {
        $url = Paths::getAssetUrl(
            $resourceName,
            BrandManager::getResourceFingerprint($resourceName),
        );

        return [
            'tag' => 'link',
            'properties' => [
                'rel'  => 'preload',
                'as'   => 'image',
                'href' => $url,
            ],
        ];
    }
}
