<?php

declare(strict_types=1);

namespace {
    $testStorage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ui-branding-version-check-' . bin2hex(random_bytes(6));
    $testFilesDir = $testStorage . DIRECTORY_SEPARATOR . 'mod';

    if (!mkdir($testFilesDir, 0750, true) && !is_dir($testFilesDir)) {
        throw new RuntimeException('Unable to create temporary version-check directory.');
    }

    define('GLPI_ROOT', __DIR__);
    define('GLPI_PLUGIN_DOC_DIR', $testStorage);
    define('PLUGIN_MOD_VERSION', '12.0.2');
}

namespace GlpiPlugin\Mod {
    final class BrandManager
    {
        public const FILES_DIR = GLPI_PLUGIN_DOC_DIR . '/mod';
    }
}

namespace {
    require_once dirname(__DIR__) . '/src/Service/GitHubVersionChecker.php';

    $cachePath = $testFilesDir . DIRECTORY_SEPARATOR . 'github-version-cache.json';
    $oldCache = [
        'cache_version'  => 2,
        'checked_at'     => time(),
        'latest'         => '12.0.1',
        'check_ok'       => true,
        'plugin_version' => '12.0.1',
    ];

    if (file_put_contents($cachePath, json_encode($oldCache, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Unable to create the old version-check cache.');
    }

    $fetchCount = 0;
    $checker = new \GlpiPlugin\Mod\Service\GitHubVersionChecker(
        static function () use (&$fetchCount): string {
            $fetchCount++;
            return '12.0.2';
        }
    );

    try {
        $result = $checker->checkLatestVersion();

        if ($fetchCount !== 1) {
            throw new RuntimeException('An upgrade must bypass the previous plugin-version cache.');
        }
        if ($result['latest'] !== '12.0.2' || !$result['check_available']) {
            throw new RuntimeException('The latest GitHub version was not refreshed after the upgrade.');
        }
        if ($result['update_available'] || $result['installed_is_newer']) {
            throw new RuntimeException('The current release must be reported as up to date.');
        }

        $writtenCache = json_decode((string) file_get_contents($cachePath), true, 16, JSON_THROW_ON_ERROR);
        if (($writtenCache['plugin_version'] ?? null) !== '12.0.2') {
            throw new RuntimeException('The cache must record the installed plugin version.');
        }

        $checker->checkLatestVersion();
        if ($fetchCount !== 1) {
            throw new RuntimeException('A fresh cache for the same plugin version must be reused.');
        }
    } finally {
        @unlink($cachePath);
        @rmdir($testFilesDir);
        @rmdir($testStorage);
    }

    fwrite(STDOUT, "GitHub version checker cache invalidation tests passed.\n");
}