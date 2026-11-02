<?php

namespace GlpiPlugin\Mod\Controller;

use Glpi\Cache\CacheManager;
use Glpi\Controller\AbstractController;
use Glpi\Error\ErrorHandler;
use GlpiPlugin\Mod\Paths;
use GlpiPlugin\Mod\Service\GitHubVersionChecker;
use GlpiPlugin\Mod\UIBranding;
use Config;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class UIBrandingController extends AbstractController
{
    #[Route('/UIBranding', name: 'mod_uibranding', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if (!Session::haveRight(Config::$rightname, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $baseUrl = Paths::getWebPrefix();

        if ($request->isMethod('POST')) {
            if ($request->request->has('clear_cache')) {
                try {
                    $cacheManager = new CacheManager();
                    if ($cacheManager->resetAllCaches()) {
                        Session::addMessageAfterRedirect(self::escapeFlashMessage(__('GLPI cache cleared successfully.', 'mod')));
                    } else {
                        Session::addMessageAfterRedirect(
                            self::escapeFlashMessage(__('Unable to clear the GLPI cache completely.', 'mod')),
                            true,
                            WARNING,
                        );
                    }
                } catch (Throwable $e) {
                    ErrorHandler::logCaughtException($e);
                    Session::addMessageAfterRedirect(
                        self::escapeFlashMessage(__('Unable to clear the GLPI cache. Check the GLPI logs for more information.', 'mod')),
                        true,
                        ERROR,
                    );
                }

                return new RedirectResponse($baseUrl . '/UIBranding');
            }

            try {
                (new UIBranding())->save(
                    $request->request->all(),
                    $request->files->all(),
                );

                Session::addMessageAfterRedirect(
                    self::escapeFlashMessage(__('UI Branding settings saved successfully. It is recommended to clear both the GLPI cache and your browser cache to apply all changes.', 'mod'))
                );
            } catch (Throwable $e) {
                ErrorHandler::logCaughtException($e);

                Session::addMessageAfterRedirect(
                    self::escapeFlashMessage(__('Unable to save UI Branding settings. Check the GLPI logs for more information.', 'mod')),
                    true,
                    ERROR,
                );
            }

            return new RedirectResponse($baseUrl . '/UIBranding');
        }

        $branding = new UIBranding();

        $viewData = $branding->getViewData(
            $baseUrl . '/UIBranding',
            $baseUrl . '/UIBranding/Asset',
        );
        $viewData['version_check'] = (new GitHubVersionChecker())->checkLatestVersion();

        return $this->render('@mod/uibranding.html.twig', $viewData);
    }

    /**
     * GLPI stores flash messages and renders them as HTML without escaping.
     * Escape translated messages before sending them to that sink.
     */
    private static function escapeFlashMessage(string $message): string
    {
        return htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
