<?php

namespace GlpiPlugin\Mod;

use Symfony\Component\HttpFoundation\File\UploadedFile;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

final class UIBranding
{
    private const THEME_LOGO_RESOURCES = [
        'logo_s_black', 'logo_s_grey', 'logo_s_white',
        'logo_m_black', 'logo_m_grey', 'logo_m_white',
        'logo_l_black', 'logo_l_grey', 'logo_l_white',
    ];

    private const BASE_LOGO_RESOURCES = ['logo_s', 'logo_m', 'logo_l'];

    public function save(array $data, array $files): void
    {
        $manager = new BrandManager();
        $config = BrandManager::getConfiguration();

        $uploadedResources = [];

        foreach (array_merge(['background', 'favicon'], self::BASE_LOGO_RESOURCES, self::THEME_LOGO_RESOURCES) as $resource) {
            if (!isset($files[$resource]) || !$files[$resource] instanceof UploadedFile) {
                continue;
            }

            /** @var UploadedFile $file */
            $file = $files[$resource];
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $manager->uploadResource($resource, $file);
            $uploadedResources[] = $resource;
        }

        $newConfig = [
            'title'       => $data['title'] ?? $config['title'],
            'login'       => $data['show_background'] ?? '0',
            'theme_logos' => $data['use_theme_logos'] ?? '0',
            'show_logos'  => $data['show_custom_logos'] ?? '0',
            'favicon'     => $data['show_custom_favicon'] ?? '0',
        ];

        $manager->setConfiguration(array_merge($config, $newConfig));

        // Keep this variable updated for the remainder of the current request.
        global $CFG_GLPI;
        $CFG_GLPI['app_name'] = BrandManager::getCurrentTitle();
    }

    public function getViewData(string $url, string $previewUrl): array
    {
        $config = BrandManager::getConfiguration();

        return [
            'url'                 => $url,
            'preview_url'         => $previewUrl,
            'show_background'     => $config['login'] === '1',
            'show_custom_logos'   => $config['show_logos'] === '1',
            'use_theme_logos'     => $config['theme_logos'] === '1',
            'show_custom_favicon' => $config['favicon'] === '1',
            'page_title'          => $config['title'],
            'base_logos'          => [
                ['name' => 'logo_s', 'label' => __('Small Logo', 'mod'), 'size' => '53x53'],
                ['name' => 'logo_m', 'label' => __('Medium Logo', 'mod'), 'size' => '100x55'],
                ['name' => 'logo_l', 'label' => __('Large Logo', 'mod'), 'size' => '250x138'],
            ],
            'theme_logos'         => [
                ['name' => 'logo_s_black', 'label' => __('Small Logo (black)', 'mod'), 'size' => '53x53'],
                ['name' => 'logo_s_grey', 'label' => __('Small Logo (grey)', 'mod'), 'size' => '53x53'],
                ['name' => 'logo_s_white', 'label' => __('Small Logo (white)', 'mod'), 'size' => '53x53'],
                ['name' => 'logo_m_black', 'label' => __('Medium Logo (black)', 'mod'), 'size' => '100x55'],
                ['name' => 'logo_m_grey', 'label' => __('Medium Logo (grey)', 'mod'), 'size' => '100x55'],
                ['name' => 'logo_m_white', 'label' => __('Medium Logo (white)', 'mod'), 'size' => '100x55'],
                ['name' => 'logo_l_black', 'label' => __('Large Logo (black)', 'mod'), 'size' => '250x138'],
                ['name' => 'logo_l_grey', 'label' => __('Large Logo (grey)', 'mod'), 'size' => '250x138'],
                ['name' => 'logo_l_white', 'label' => __('Large Logo (white)', 'mod'), 'size' => '250x138'],
            ],
        ];
    }
}
