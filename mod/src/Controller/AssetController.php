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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AssetController extends AbstractController
{
    #[Route('/UIBranding/Asset/{resource}', name: 'mod_asset', requirements: ['resource' => '[a-z0-9_]+'], methods: ['GET', 'HEAD'])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(string $resource, Request $request): BinaryFileResponse
    {
        if (!BrandManager::isValidResourceKey($resource)) {
            throw new NotFoundHttpException();
        }

        $file = BrandManager::getCurrentResourceFile($resource);
        if ($file === null || !is_file($file) || !is_readable($file)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', BrandManager::getResourceMimeType($resource));
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->headers->set('Cache-Control', 'public, max-age=31536000, s-maxage=31536000, immutable');
        $response->setEtag(BrandManager::getResourceFingerprint($resource));
        $response->setAutoLastModified();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($file),
        );

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
