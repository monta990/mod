<?php

/**
 * UI Branding plugin for GLPI 12.
 *
 * @copyright Copyright (C) 2026 by i-Vertix/PGUM.
 * @license GPLv3
 */

namespace GlpiPlugin\Mod\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Mod\BrandManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AssetController extends AbstractController
{
    #[Route('/UIBranding/Asset/{resource}', name: 'mod_asset', requirements: ['resource' => '[a-z0-9_]+'], methods: ['GET'])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(string $resource): BinaryFileResponse
    {
        if (!BrandManager::isValidResourceKey($resource)) {
            throw new NotFoundHttpException();
        }

        $file = BrandManager::getCurrentResourceFile($resource);
        if ($file === null || !is_file($file) || !is_readable($file)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($file);

        if ($resource === 'favicon') {
            $response->headers->set('Content-Type', 'image/x-icon');
        }

        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->setEtag(sha1_file($file));
        $response->setAutoLastModified();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($file),
        );

        return $response;
    }
}
