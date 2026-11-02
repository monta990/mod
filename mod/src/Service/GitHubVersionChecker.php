<?php

namespace GlpiPlugin\Mod\Service;

use GlpiPlugin\Mod\BrandManager;

if (!defined('GLPI_ROOT')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    return;
}

final class GitHubVersionChecker
{
    private const API_URL = 'https://api.github.com/repos/monta990/mod/releases/latest';
    private const RELEASES_URL = 'https://github.com/monta990/mod/releases';
    private const CACHE_VERSION = 1;
    private const SUCCESS_CACHE_TTL = 21600; // 6 hours
    private const FAILURE_CACHE_TTL = 300;   // 5 minutes
    private const MAX_RESPONSE_BYTES = 65536;

    /**
     * Return the installed version and latest stable public GitHub release.
     *
     * Network failures never block the configuration page or modify plugin
     * settings/uploads. The last successful release value is retained in the
     * cache and a failed request is retried after a short negative-cache TTL.
     *
     * @return array{
     *   current: string,
     *   latest: string,
     *   update_available: bool,
     *   installed_is_newer: bool,
     *   check_available: bool,
     *   checked_at: int,
     *   releases_url: string
     * }
     */
    public function checkLatestVersion(): array
    {
        $cache = $this->readCache();
        $now = time();
        $latest = $this->normalizeVersion((string) ($cache['latest'] ?? ''));
        $checkOk = (bool) ($cache['check_ok'] ?? false);
        $checkedAt = (int) ($cache['checked_at'] ?? 0);
        $cacheVersion = (int) ($cache['cache_version'] ?? 0);

        $ttl = $checkOk ? self::SUCCESS_CACHE_TTL : self::FAILURE_CACHE_TTL;
        $cacheFresh = $cacheVersion === self::CACHE_VERSION
            && $checkedAt > 0
            && ($now - $checkedAt) >= 0
            && ($now - $checkedAt) < $ttl;

        if (!$cacheFresh) {
            $fetchedVersion = $this->fetchLatestGithubRelease();
            $checkOk = $fetchedVersion !== '';
            if ($checkOk) {
                $latest = $fetchedVersion;
            }
            $checkedAt = $now;

            $this->writeCache([
                'cache_version' => self::CACHE_VERSION,
                'checked_at'    => $checkedAt,
                'latest'        => $latest,
                'check_ok'      => $checkOk,
            ]);
        }

        $current = defined('PLUGIN_MOD_VERSION')
            ? (string) constant('PLUGIN_MOD_VERSION')
            : '0.0.0';
        $checkAvailable = $checkOk && $latest !== '';
        $updateAvailable = $checkAvailable && version_compare($latest, $current, '>');
        $installedIsNewer = $checkAvailable && version_compare($current, $latest, '>');

        return [
            'current'          => $current,
            'latest'           => $latest,
            'update_available' => $updateAvailable,
            'installed_is_newer' => $installedIsNewer,
            'check_available'  => $checkAvailable,
            'checked_at'       => $checkedAt,
            'releases_url'     => self::RELEASES_URL,
        ];
    }

    private function fetchLatestGithubRelease(): string
    {
        if (!function_exists('curl_init')) {
            $this->logFailure('cURL extension is unavailable.');
            return '';
        }

        $handle = curl_init(self::API_URL);
        if ($handle === false) {
            $this->logFailure('Unable to initialize cURL.');
            return '';
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: ui-branding-glpi-plugin',
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_MAXREDIRS      => 0,
        ]);

        $body = curl_exec($handle);
        $errorNumber = curl_errno($handle);
        $errorMessage = curl_error($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if (!is_string($body) || $body === '') {
            $detail = $errorMessage !== '' ? ' (' . $errorMessage . ')' : '';
            $this->logFailure(sprintf('cURL error %d%s.', $errorNumber, $detail));
            return '';
        }

        if ($httpCode !== 200) {
            $this->logFailure(sprintf('GitHub API returned HTTP %d.', $httpCode));
            return '';
        }

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            $this->logFailure('GitHub API response exceeded 64 KB.');
            return '';
        }

        try {
            $release = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->logFailure('GitHub API returned invalid JSON.');
            return '';
        }

        if (!is_array($release)) {
            $this->logFailure('GitHub API response was not an object.');
            return '';
        }

        // Defense in depth: never offer drafts or pre-releases as stable.
        if (!empty($release['draft']) || !empty($release['prerelease'])) {
            $this->logFailure('The latest GitHub release is not stable.');
            return '';
        }

        $tag = $this->normalizeVersion((string) ($release['tag_name'] ?? ''));
        if ($tag === '') {
            $this->logFailure('The latest GitHub release has an invalid version tag.');
            return '';
        }

        return $tag;
    }

    private function normalizeVersion(string $version): string
    {
        $version = trim($version);
        $version = preg_replace('/^v/i', '', $version) ?? '';

        return preg_match('/^\d+(?:\.\d+){1,3}$/', $version) === 1
            ? $version
            : '';
    }

    private function getCachePath(): string
    {
        return BrandManager::FILES_DIR . '/github-version-cache.json';
    }

    /** @return array<string, mixed> */
    private function readCache(): array
    {
        $path = $this->getCachePath();
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $size = @filesize($path);
        if (!is_int($size) || $size <= 0 || $size > 8192) {
            return [];
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    private function writeCache(array $data): void
    {
        $path = $this->getCachePath();
        $directory = dirname($path);

        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            return;
        }

        try {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $temporaryPath = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';

            if (@file_put_contents($temporaryPath, $json, LOCK_EX) === false) {
                @unlink($temporaryPath);
                return;
            }

            if (@rename($temporaryPath, $path)) {
                return;
            }

            // Windows may not replace an existing destination with rename().
            // Move it aside and restore it if the new cache cannot be installed.
            $backupPath = $path . '.backup-' . bin2hex(random_bytes(6));
            $hadPrevious = is_file($path);
            if ($hadPrevious && !@rename($path, $backupPath)) {
                @unlink($temporaryPath);
                return;
            }

            if (!@rename($temporaryPath, $path)) {
                if ($hadPrevious && is_file($backupPath)) {
                    @rename($backupPath, $path);
                }
                @unlink($temporaryPath);
                return;
            }

            if ($hadPrevious && is_file($backupPath)) {
                @unlink($backupPath);
            }
        } catch (\Throwable) {
            // Version checking is optional and must never break plugin operation.
        }
    }

    private function logFailure(string $message): void
    {
        \Toolbox::logInFile('mod', 'GitHub version check failed: ' . $message . "\n");
    }
}
