<?php

namespace GlpiPlugin\Mod;

use Symfony\Component\HttpFoundation\File\UploadedFile;

if (!defined('GLPI_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=UTF-8');
    return;
}

final class UIBranding
{
    private const THEME_LOGO_RESOURCES = [
        'logo_s_white',
        'logo_s_black',
        'logo_m_white',
        'logo_m_black',
        'logo_l_black',
        'logo_l_white',
    ];

    private const BASE_LOGO_RESOURCES = [
        'logo_s',
        'logo_m',
        'logo_l',
    ];

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     */
    public function save(array $data, array $files): void
    {
        $manager = new BrandManager();
        $config = BrandManager::getConfiguration();
        $pendingUploads = [];

        foreach (
            array_merge(
                ['background', 'favicon'],
                self::BASE_LOGO_RESOURCES,
                self::THEME_LOGO_RESOURCES,
            ) as $resource
        ) {
            if (!isset($files[$resource]) || !$files[$resource] instanceof UploadedFile) {
                continue;
            }

            /** @var UploadedFile $file */
            $file = $files[$resource];
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $pendingUploads[$resource] = $file;
        }

        $newConfig = [
            'title'       => $data['title'] ?? $config['title'],
            'login'       => $data['show_background'] ?? $config['login'],
            'theme_logos' => $data['use_theme_logos'] ?? $config['theme_logos'],
            'show_logos'  => $data['show_custom_logos'] ?? $config['show_logos'],
            'favicon'     => $data['show_custom_favicon'] ?? $config['favicon'],
        ];

        // Validate and stage every selected upload before replacing any current
        // resource or writing the new settings.
        $manager->saveConfiguration($newConfig, $pendingUploads);

        global $CFG_GLPI;
        $title = BrandManager::getCurrentTitle();
        $CFG_GLPI['app_name'] = $title !== '' ? $title : 'GLPI';
    }

    /** @return array<string, mixed> */
    public function getViewData(string $url, string $previewUrl): array
    {
        $config = BrandManager::getConfiguration();
        $previewVersions = [];

        foreach (array_keys(BrandManager::getImageResources()) as $resourceName) {
            $previewVersions[$resourceName] = BrandManager::getResourceFingerprint($resourceName);
        }

        return [
            'url'                 => $url,
            'preview_url'         => $previewUrl,
            'preview_versions'    => $previewVersions,
            'show_background'     => $config['login'] === '1',
            'show_custom_logos'   => $config['show_logos'] === '1',
            'use_theme_logos'     => $config['theme_logos'] === '1',
            'show_custom_favicon' => $config['favicon'] === '1',
            'page_title'          => $config['title'],
            'base_logos'          => [
                ['name' => 'logo_s', 'label' => __('Small Logo', 'mod'), 'size' => __('Minimum 40x40', 'mod')],
                ['name' => 'logo_m', 'label' => __('Medium Logo', 'mod'), 'size' => __('Minimum 100x55', 'mod')],
                ['name' => 'logo_l', 'label' => __('Large Logo', 'mod'), 'size' => __('Minimum 220x130', 'mod')],
            ],
            'theme_logos'         => [
                ['name' => 'logo_s_white', 'label' => __('Small Logo (white)', 'mod'), 'size' => __('Minimum 40x40', 'mod')],
                ['name' => 'logo_s_black', 'label' => __('Small Logo (black)', 'mod'), 'size' => __('Minimum 40x40', 'mod')],
                ['name' => 'logo_m_white', 'label' => __('Medium Logo (white)', 'mod'), 'size' => __('Minimum 100x55', 'mod')],
                ['name' => 'logo_m_black', 'label' => __('Medium Logo (black)', 'mod'), 'size' => __('Minimum 100x55', 'mod')],
                ['name' => 'logo_l_black', 'label' => __('Large Logo (black)', 'mod'), 'size' => __('Minimum 220x130', 'mod')],
                ['name' => 'logo_l_white', 'label' => __('Large Logo (white)', 'mod'), 'size' => __('Minimum 220x130', 'mod')],
            ],
        ];
    }
}
