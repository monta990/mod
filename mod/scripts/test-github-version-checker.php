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

namespace Glpi\Toolbox {
    final class FakeHttpResponse
    {
        public function __construct(
            private int $statusCode,
            private string $body,
        ) {
        }

        public function getStatusCode(): int
        {
            return $this->statusCode;
        }

        public function getContent(bool $throw = true): string
        {
            return $this->body;
        }
    }

    final class HttpClient
    {
        public static ?array $lastRequest = null;
        public static ?FakeHttpResponse $nextResponse = null;

        public function get(string $uri, array $options = []): FakeHttpResponse
        {
            self::$lastRequest = [
                'uri' => $uri,
                'options' => $options,
            ];

            if (self::$nextResponse === null) {
                throw new \RuntimeException('No fake HTTP response configured.');
            }

            return self::$nextResponse;
        }
    }
}

namespace GlpiPlugin\Mod {
    final class BrandManager
    {
        public const FILES_DIR = GLPI_PLUGIN_DOC_DIR . '/mod';
    }
}

namespace {
    use Glpi\Toolbox\HttpClient;
    use GlpiPlugin\Mod\Service\GitHubVersionChecker;

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
    $checker = new GitHubVersionChecker(
        static function () use (&$fetchCount): string {
            $fetchCount++;
            return '12.0.2';
        },
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

    // Exercise the production transport without requiring a live network.
    // GLPI's HttpClient is the production path and honors GLPI proxy settings.
    $oldCache['plugin_version'] = '12.0.1';
    $oldCache['latest'] = '12.0.1';
    if (file_put_contents($cachePath, json_encode($oldCache, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Unable to reset the previous version-check cache.');
    }

    HttpClient::$nextResponse = new \Glpi\Toolbox\FakeHttpResponse(
        200,
        json_encode([
            'tag_name' => '12.0.1',
            'draft' => false,
            'prerelease' => false,
        ], JSON_THROW_ON_ERROR),
    );

    $httpResult = (new GitHubVersionChecker())->checkLatestVersion();
    $request = HttpClient::$lastRequest;

    if ($httpResult['latest'] !== '12.0.1' || !$httpResult['check_available'] || !$httpResult['installed_is_newer']) {
        throw new RuntimeException('The GLPI HTTP client response was not parsed as the latest stable release.');
    }
    if (!is_array($request) || $request['uri'] !== 'https://api.github.com/repos/monta990/mod/releases/latest') {
        throw new RuntimeException('The checker did not use the expected GitHub API endpoint.');
    }
    if (($request['options']['timeout'] ?? null) !== 10.0) {
        throw new RuntimeException('The GitHub request timeout was not increased to ten seconds.');
    }
    if (($request['options']['headers']['Accept'] ?? null) !== 'application/vnd.github+json') {
        throw new RuntimeException('The GitHub API Accept header was not sent.');
    }

    fwrite(STDOUT, "GitHub version checker cache and GLPI HTTP client tests passed.\n");
}
