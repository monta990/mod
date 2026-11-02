<?php

/**
 * UI Branding plugin for GLPI 12.
 *
 * @copyright Copyright (C) 2026 by Edwin Elias Alvarez.
 * @license GPL-3.0-or-later
 */

namespace GlpiPlugin\Mod\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Mod\BrandManager;
use GlpiPlugin\Mod\Paths;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BrandingCssController extends AbstractController
{
    #[Route('/UIBranding/BrandingCss', name: 'mod_branding_css', methods: ['GET'])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        $config = BrandManager::getConfiguration();
        $assetBase = Paths::getControllerUrl('UIBranding/Asset') . '/';

        $assetUrl = static function (string $name) use ($assetBase): string {
            return $assetBase . rawurlencode($name) . '?v='
                . rawurlencode(BrandManager::getResourceFingerprint($name));
        };

        $rules = [];

        if ($config['show_logos'] === '1') {
            if ($config['theme_logos'] === '1') {
                $logoLight = $assetUrl('logo_m_white');
                $logoLightReduced = $assetUrl('logo_s_white');
                $logoDark = $assetUrl('logo_m_black');
                $logoDarkReduced = $assetUrl('logo_s_black');
                $logoDarkLogin = $assetUrl('logo_l_black');
                $logoLightLogin = $assetUrl('logo_l_white');
            } else {
                $logoLight = $assetUrl('logo_m');
                $logoLightReduced = $assetUrl('logo_s');
                $logoDark = $logoLight;
                $logoDarkReduced = $logoLightReduced;
                $logoDarkLogin = $assetUrl('logo_l');
                $logoLightLogin = $logoDarkLogin;
            }

            // Override GLPI's native variables instead of replacing logo elements.
            // This preserves GLPI's expanded/collapsed menu and login-page behavior.
            // !important is intentional: GLPI core styles are loaded after plugin header tags.
            $rules[] = ':root{'
                . '--glpi-logo-light:url("' . $logoLight . '") !important;'
                . '--glpi-logo-light-reduced:url("' . $logoLightReduced . '") !important;'
                . '--glpi-logo-dark:url("' . $logoDark . '") !important;'
                . '--glpi-logo-dark-reduced:url("' . $logoDarkReduced . '") !important;'
                . '--glpi-logo-dark-login:url("' . $logoDarkLogin . '") !important;'
                . '--glpi-logo-light-login:url("' . $logoLightLogin . '") !important;'
                . '}';
        }

        if ($config['login'] === '1') {
            $background = $assetUrl('background');
            $rules[] = 'html{min-height:100%;}';
            $rules[] = 'body.welcome-anonymous{min-height:100vh !important;margin:0 !important;background-color:transparent !important;background-image:url("' . $background . '") !important;background-repeat:no-repeat !important;background-attachment:fixed !important;background-position:center center !important;background-size:cover !important;}';
            $rules[] = 'body.welcome-anonymous .page-anonymous{min-height:100vh !important;background:transparent !important;}';
        }

        $content = implode("\n", $rules) . "\n";
        $response = new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setEtag(hash('sha256', $content));
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->headers->set('Cache-Control', 'public, max-age=31536000, s-maxage=31536000, immutable');

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
