<?php

/**
 * UI Branding plugin for GLPI 12.
 *
 * @copyright Copyright (C) 2026 by Edwin Elias Alvarez.
 * @license GPL-3.0-or-later
 */

namespace GlpiPlugin\Mod;

use Toolbox;
use Symfony\Component\HttpFoundation\File\UploadedFile;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

final class BrandManager
{
    public const FILES_DIR = GLPI_PLUGIN_DOC_DIR . '/mod';
    public const UPLOADS_DIR = self::FILES_DIR . '/uploads';
    private const CONFIG_FILE = self::FILES_DIR . '/settings.ini';
    private const LEGACY_UPLOAD_MIGRATION_MARKER = self::FILES_DIR . '/.legacy-upload-names-migrated';
    private const LEGACY_UPLOAD_FILENAMES = [
        'favicon.ico'                => 'sample-icon.ico',
        'logo-G-100.png'             => 'sample-logo-mark.png',
        'logo-GLPI-100.png'           => 'sample-logo-horizontal.png',
        'logo-GLPI-250.png'           => 'sample-logo-login.png',
        'logo-G-100-black.png'        => 'sample-logo-mark-dark.png',
        'logo-G-100-white.png'        => 'sample-logo-mark-light.png',
        'logo-GLPI-100-black.png'     => 'sample-logo-horizontal-dark.png',
        'logo-GLPI-100-white.png'     => 'sample-logo-horizontal-light.png',
        'logo-GLPI-250-black.png'     => 'sample-logo-login-dark.png',
        'logo-GLPI-250-white.png'     => 'sample-logo-login-light.png',
    ];

    private const DEFAULT_CONFIG = [
        'title'       => 'GLPI',
        'login'       => '0',
        'theme_logos' => '0',
        'show_logos'  => '0',
        'favicon'     => '0',
    ];

    private const RESOURCE_DEFINITIONS = [
        'background' => [
            'filename'       => 'background.jpg',
            'default'        => 'background.jpg',
            'extensions'     => ['jpg', 'jpeg'],
            'mime'           => 'image/jpeg',
            'max_size'       => 15 * 1024 * 1024,
            'min_dimensions' => [1920, 1080],
        ],
        'favicon' => [
            'filename'       => 'sample-icon.ico',
            'default'        => 'sample-icon.ico',
            'extensions'     => ['ico'],
            'mime'           => null,
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => null,
        ],
        'logo_s' => [
            'filename'       => 'sample-logo-mark.png',
            'default'        => 'sample-logo-mark.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [40, 40],
        ],
        'logo_m' => [
            'filename'       => 'sample-logo-horizontal.png',
            'default'        => 'sample-logo-horizontal.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [100, 55],
        ],
        'logo_l' => [
            'filename'       => 'sample-logo-login.png',
            'default'        => 'sample-logo-login.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [220, 130],
        ],
        'logo_s_black' => [
            'filename'       => 'sample-logo-mark-dark.png',
            'default'        => 'sample-logo-mark-dark.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [40, 40],
        ],
        'logo_s_white' => [
            'filename'       => 'sample-logo-mark-light.png',
            'default'        => 'sample-logo-mark-light.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [40, 40],
        ],
        'logo_m_black' => [
            'filename'       => 'sample-logo-horizontal-dark.png',
            'default'        => 'sample-logo-horizontal-dark.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [100, 55],
        ],
        'logo_m_white' => [
            'filename'       => 'sample-logo-horizontal-light.png',
            'default'        => 'sample-logo-horizontal-light.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [100, 55],
        ],
        'logo_l_black' => [
            'filename'       => 'sample-logo-login-dark.png',
            'default'        => 'sample-logo-login-dark.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [220, 130],
        ],
        'logo_l_white' => [
            'filename'       => 'sample-logo-login-light.png',
            'default'        => 'sample-logo-login-light.png',
            'extensions'     => ['png'],
            'mime'            => 'image/png',
            'max_size'       => 2 * 1024 * 1024,
            'min_dimensions' => [220, 130],
        ],
    ];

    public static function getResourceDir(): string
    {
        $pluginDir = \Plugin::getPhpDir('mod');
        if (!is_string($pluginDir) || $pluginDir === '') {
            throw new \RuntimeException('Unable to resolve the UI Branding plugin directory.');
        }

        return rtrim($pluginDir, '/') . '/resources/images';
    }

    private static function getResourceRootDir(): string
    {
        return dirname(self::getResourceDir());
    }

    public static function getImageResources(): array
    {
        $resources = [];

        $resourceDir = self::getResourceDir();
        foreach (self::RESOURCE_DEFINITIONS as $name => $definition) {
            $resources[$name] = [
                'default' => $resourceDir . '/' . $definition['default'],
                'current' => self::UPLOADS_DIR . '/' . $definition['filename'],
                'accept'  => $definition['extensions'],
            ];
        }

        return $resources;
    }

    public static function isValidResourceKey(string $resourceName): bool
    {
        return isset(self::RESOURCE_DEFINITIONS[$resourceName]);
    }

    public static function getResourceMimeType(string $resourceName): string
    {
        if (!self::isValidResourceKey($resourceName)) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        return self::RESOURCE_DEFINITIONS[$resourceName]['mime'] ?? 'image/x-icon';
    }

    private static function hasMinimumImageDimensions(string $resourceName, string $path): bool
    {
        $definition = self::RESOURCE_DEFINITIONS[$resourceName];
        $minimum = $definition['min_dimensions'] ?? null;
        if ($minimum === null) {
            return true;
        }

        $imageInfo = @getimagesize($path);
        if (
            !is_array($imageInfo)
            || (int) $imageInfo[0] < $minimum[0]
            || (int) $imageInfo[1] < $minimum[1]
        ) {
            return false;
        }

        $expectedType = match ($definition['mime'] ?? null) {
            'image/png' => IMAGETYPE_PNG,
            'image/jpeg' => IMAGETYPE_JPEG,
            default => null,
        };

        return $expectedType === null || ($imageInfo[2] ?? null) === $expectedType;
    }

    public static function getCurrentResourceFile(string $resourceName): ?string
    {
        if (!self::isValidResourceKey($resourceName)) {
            return null;
        }

        $resource = self::getImageResources()[$resourceName];

        // Retain administrator uploads, but do not serve files that fall
        // below GLPI's recommended minimum dimensions. Larger images are valid;
        // exact width/height equality is deliberately not required. Use the
        // bundled default when a legacy upload is below the minimum.
        if (
            is_file($resource['current'])
            && is_readable($resource['current'])
            && self::hasMinimumImageDimensions($resourceName, $resource['current'])
        ) {
            return $resource['current'];
        }

        if (
            is_file($resource['default'])
            && is_readable($resource['default'])
            && self::hasMinimumImageDimensions($resourceName, $resource['default'])
        ) {
            return $resource['default'];
        }

        return null;
    }

    public static function getResourceVersion(string $resourceName): string
    {
        $file = self::getCurrentResourceFile($resourceName);
        if ($file === null) {
            return '0';
        }

        return (string) ((int) @filemtime($file));
    }

    public static function getResourceFingerprint(string $resourceName): string
    {
        $file = self::getCurrentResourceFile($resourceName);
        if ($file === null) {
            return '0';
        }

        $hash = @hash_file('sha256', $file);
        return is_string($hash) ? $hash : self::getResourceVersion($resourceName);
    }

    /**
     * Return a stable fingerprint for the generated branding stylesheet.
     *
     * The fingerprint changes only when the plugin version, CSS-affecting
     * configuration, or resources referenced by the stylesheet change.
     */
    public static function getBrandingCssFingerprint(): string
    {
        $config = self::readConfigFile();

        $payload = [
            'plugin' => defined('PLUGIN_MOD_VERSION') ? PLUGIN_MOD_VERSION : '0',
            'login' => $config['login'],
            'show_logos' => $config['show_logos'],
            'theme_logos' => $config['theme_logos'],
            'resources' => [],
        ];

        if ($config['show_logos'] === '1') {
            $resourceNames = $config['theme_logos'] === '1'
                ? [
                    'logo_s_black', 'logo_s_white',
                    'logo_m_black', 'logo_m_white',
                    'logo_l_black', 'logo_l_white',
                ]
                : ['logo_s', 'logo_m', 'logo_l'];

            foreach ($resourceNames as $resourceName) {
                $payload['resources'][$resourceName] = self::getResourceFingerprint($resourceName);
            }
        }

        if ($config['login'] === '1') {
            $payload['resources']['background'] = self::getResourceFingerprint('background');
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded)) {
            return '0';
        }

        return substr(hash('sha256', $encoded), 0, 16);
    }

    private static function ensureDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $dir));
        }
    }

    private static function normalizeConfig(array $values): array
    {
        return [
            'title'       => self::sanitizeTitle($values['title'] ?? self::DEFAULT_CONFIG['title']),
            'login'       => self::toFlag($values['login'] ?? '0'),
            'theme_logos' => self::toFlag($values['theme_logos'] ?? '0'),
            'show_logos'  => self::toFlag($values['show_logos'] ?? '0'),
            'favicon'     => self::toFlag($values['favicon'] ?? '0'),
        ];
    }

    private static function sanitizeTitle(mixed $title): string
    {
        $title = trim(strip_tags((string) $title));
        $title = preg_replace('/[\x00-\x1F\x7F]/u', '', $title) ?? '';

        return mb_substr($title, 0, 255);
    }

    private static function toFlag(mixed $value): string
    {
        return (string) $value === '1' ? '1' : '0';
    }

    private static function readConfigFile(): array
    {
        if (!is_file(self::CONFIG_FILE)) {
            return self::DEFAULT_CONFIG;
        }

        $values = @parse_ini_file(self::CONFIG_FILE, false, INI_SCANNER_RAW);
        return is_array($values) ? self::normalizeConfig($values) : self::DEFAULT_CONFIG;
    }

    private static function writeConfigFile(array $values): void
    {
        self::ensureDir(self::FILES_DIR);
        $values = self::normalizeConfig($values);

        $content = sprintf(
            "title=\"%s\"\nlogin=\"%s\"\ntheme_logos=\"%s\"\nshow_logos=\"%s\"\nfavicon=\"%s\"\n",
            str_replace(['\\', '"'], ['\\\\', '\\"'], $values['title']),
            $values['login'],
            $values['theme_logos'],
            $values['show_logos'],
            $values['favicon'],
        );

        $tempFile = tempnam(self::FILES_DIR, 'mod_');
        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create temporary configuration file.');
        }

        $backupFile = self::CONFIG_FILE . '.backup-' . bin2hex(random_bytes(6));
        $hadPreviousFile = is_file(self::CONFIG_FILE);

        try {
            if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to write plugin configuration.');
            }

            // Windows does not consistently allow rename() to overwrite an
            // existing file. Move the old config aside on the same filesystem.
            if ($hadPreviousFile && !rename(self::CONFIG_FILE, $backupFile)) {
                throw new \RuntimeException('Unable to back up existing plugin configuration.');
            }

            if (!rename($tempFile, self::CONFIG_FILE)) {
                if ($hadPreviousFile && is_file($backupFile)) {
                    @rename($backupFile, self::CONFIG_FILE);
                }
                throw new \RuntimeException('Unable to replace plugin configuration.');
            }

            if ($hadPreviousFile && is_file($backupFile)) {
                @unlink($backupFile);
            }
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
            if (is_file($backupFile) && !is_file(self::CONFIG_FILE)) {
                @rename($backupFile, self::CONFIG_FILE);
            }
        }
    }

    public static function getConfiguration(): array
    {
        return self::readConfigFile();
    }

    public static function getCurrentTitle(): string
    {
        return self::readConfigFile()['title'];
    }

    public static function isLoginPageModified(): bool
    {
        return self::readConfigFile()['login'] === '1';
    }

    public static function isThemeLogosEnabled(): bool
    {
        return self::readConfigFile()['theme_logos'] === '1';
    }

    public static function isAnyLogoModified(): bool
    {
        return self::readConfigFile()['show_logos'] === '1';
    }

    public static function isFaviconEnabled(): bool
    {
        return self::readConfigFile()['favicon'] === '1';
    }

    /**
     * Preserve uploads made by the previous UI Branding builds.
     *
     * Earlier builds stored administrator images in the same persistent
     * uploads directory but used GLPI-derived filenames. Copy them to the
     * current neutral resource names without deleting the originals or
     * overwriting files already uploaded using the new names.
     *
     * This method is idempotent and runs from the GLPI install/update hook.
     */
    private static function migrateLegacyUploadedResources(): void
    {
        if (is_file(self::LEGACY_UPLOAD_MIGRATION_MARKER)) {
            return;
        }

        foreach (self::LEGACY_UPLOAD_FILENAMES as $legacyName => $currentName) {
            $legacyPath = self::UPLOADS_DIR . '/' . $legacyName;
            $currentPath = self::UPLOADS_DIR . '/' . $currentName;

            if (!is_file($legacyPath) || is_file($currentPath)) {
                continue;
            }

            if (!is_readable($legacyPath) || !@copy($legacyPath, $currentPath)) {
                throw new \RuntimeException(
                    sprintf('Unable to preserve legacy branding upload "%s".', $legacyName)
                );
            }
        }

        // Write the marker only after all copy operations complete. If one
        // copy fails, the next install/update attempt can safely retry.
        $temporaryMarker = tempnam(self::FILES_DIR, '.migration-');
        if ($temporaryMarker === false) {
            throw new \RuntimeException('Unable to create the branding migration marker.');
        }

        try {
            if (file_put_contents($temporaryMarker, "legacy-upload-names-v1\n", LOCK_EX) === false) {
                throw new \RuntimeException('Unable to write the branding migration marker.');
            }

            if (!@rename($temporaryMarker, self::LEGACY_UPLOAD_MIGRATION_MARKER)
                && !is_file(self::LEGACY_UPLOAD_MIGRATION_MARKER)
            ) {
                throw new \RuntimeException('Unable to finalize the branding migration marker.');
            }
        } finally {
            if (is_file($temporaryMarker)) {
                @unlink($temporaryMarker);
            }
        }
    }

    public function install(): void
    {
        self::ensureDir(self::FILES_DIR);
        self::ensureDir(self::UPLOADS_DIR);
        self::migrateLegacyUploadedResources();

        // Never rewrite an existing settings.ini during an install/update.
        if (!is_file(self::CONFIG_FILE)) {
            $values = self::DEFAULT_CONFIG;

            $defaultConfig = self::getResourceRootDir() . '/modifiers.ini';
            if (is_file($defaultConfig)) {
                $defaults = @parse_ini_file($defaultConfig, false, INI_SCANNER_RAW);
                if (is_array($defaults)) {
                    $values = array_merge($values, $defaults);
                }
            }

            self::writeConfigFile($values);
        }
    }

    public function uninstall(): void
    {
        // The plugin does not alter GLPI core files. Uninstall only removes
        // plugin-owned persistent data.
        if (is_dir(self::FILES_DIR)) {
            Toolbox::deleteDir(self::FILES_DIR);
        }
    }

    public function setConfiguration(array $values): void
    {
        self::writeConfigFile(array_merge(self::readConfigFile(), $values));
    }

    public function updateConfiguration(array $values): void
    {
        $current = self::readConfigFile();
        self::writeConfigFile(array_merge($current, $values));

        global $CFG_GLPI;
        $title = self::getCurrentTitle();
        $CFG_GLPI['app_name'] = $title !== '' ? $title : 'GLPI';
    }

    /**
     * Save settings and any uploaded resources as one recoverable operation.
     *
     * All files are validated and staged before existing resources are moved.
     * If replacement or settings persistence fails, previous files are restored.
     *
     * @param array<string, mixed> $values
     * @param array<string, UploadedFile> $uploads
     */
    public function saveConfiguration(array $values, array $uploads = []): void
    {
        $normalized = self::normalizeConfig(array_merge(self::readConfigFile(), $values));
        self::ensureDir(self::FILES_DIR);
        self::ensureDir(self::UPLOADS_DIR);

        $stagingDir = self::UPLOADS_DIR . '/.staging-' . bin2hex(random_bytes(8));
        if (!mkdir($stagingDir, 0700)) {
            throw new \RuntimeException('Unable to create temporary upload directory.');
        }

        $staged = [];
        $backups = [];
        $installed = [];
        $rollbackFailed = false;

        try {
            // Validate every upload before moving or replacing any existing file.
            foreach ($uploads as $resourceName => $file) {
                if (!is_string($resourceName) || !self::isValidResourceKey($resourceName)) {
                    throw new \InvalidArgumentException('Invalid branding resource.');
                }
                if (!$file instanceof UploadedFile) {
                    throw new \InvalidArgumentException('Invalid uploaded file.');
                }

                self::validateUploadedResource($resourceName, $file);
                $filename = self::RESOURCE_DEFINITIONS[$resourceName]['filename'];
                $stagedPath = $stagingDir . '/' . $filename;
                $file->move($stagingDir, $filename);
                $staged[$resourceName] = $stagedPath;
            }

            // Replace files only after all uploads have passed validation/staging.
            foreach ($staged as $resourceName => $stagedPath) {
                $filename = self::RESOURCE_DEFINITIONS[$resourceName]['filename'];
                $destination = self::UPLOADS_DIR . '/' . $filename;

                if (is_file($destination)) {
                    $backupPath = $stagingDir . '/backup-' . $filename;
                    if (!rename($destination, $backupPath)) {
                        throw new \RuntimeException(sprintf('Unable to back up existing resource "%s".', $resourceName));
                    }
                    $backups[$destination] = $backupPath;
                }

                if (!rename($stagedPath, $destination)) {
                    throw new \RuntimeException(sprintf('Unable to install uploaded resource "%s".', $resourceName));
                }
                $installed[] = $destination;
            }

            self::writeConfigFile($normalized);
        } catch (\Throwable $exception) {
            foreach (array_reverse($installed) as $destination) {
                if (is_file($destination) && !@unlink($destination)) {
                    $rollbackFailed = true;
                    error_log(sprintf('UI Branding: unable to remove partially installed file "%s".', $destination));
                }
            }

            foreach ($backups as $destination => $backupPath) {
                if (is_file($backupPath) && !@rename($backupPath, $destination)) {
                    $rollbackFailed = true;
                    error_log(sprintf('UI Branding: unable to restore previous resource "%s".', $destination));
                }
            }

            throw $exception;
        } finally {
            if (is_dir($stagingDir)) {
                if ($rollbackFailed) {
                    // Keep failed rollback backups available for manual recovery.
                    error_log(sprintf('UI Branding: transaction recovery files were left in "%s".', $stagingDir));
                } else {
                    Toolbox::deleteDir($stagingDir);
                }
            }
        }

        global $CFG_GLPI;
        $title = $normalized['title'];
        $CFG_GLPI['app_name'] = $title !== '' ? $title : 'GLPI';
    }

    /**
     * Validate the uploaded file's extension, content, size and dimensions.
     */
    private static function validateUploadedResource(string $resourceName, UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \RuntimeException(sprintf('The upload for "%s" failed.', $resourceName));
        }

        $definition = self::RESOURCE_DEFINITIONS[$resourceName];
        $extension = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($extension, $definition['extensions'], true)) {
            throw new \RuntimeException(sprintf('Invalid file extension for "%s".', $resourceName));
        }

        $size = $file->getSize();
        if (!is_int($size) || $size <= 0 || $size > $definition['max_size']) {
            throw new \RuntimeException(sprintf('The uploaded "%s" file is empty or exceeds the size limit.', $resourceName));
        }

        $path = $file->getPathname();
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('Unable to read uploaded resource "%s".', $resourceName));
        }

        if ($resourceName === 'favicon') {
            self::validateIcoFile($path);
            return;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!is_string($mime) || $mime !== $definition['mime']) {
            throw new \RuntimeException(sprintf('The uploaded "%s" content does not match its file type.', $resourceName));
        }

        $imageInfo = @getimagesize($path);
        $expectedType = $extension === 'png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG;
        if (!is_array($imageInfo) || ($imageInfo[2] ?? null) !== $expectedType) {
            throw new \RuntimeException(sprintf('The uploaded "%s" is not a valid supported image.', $resourceName));
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        if (
            $width < 1
            || $height < 1
            || $width > 8192
            || $height > 8192
            || ($width * $height) > 40000000
        ) {
            throw new \RuntimeException(sprintf('The uploaded "%s" dimensions are outside the allowed limits.', $resourceName));
        }

        $minimum = $definition['min_dimensions'];
        if ($minimum !== null && ($width < $minimum[0] || $height < $minimum[1])) {
            throw new \RuntimeException(sprintf(
                'The "%1$s" image must be at least %2$dx%3$d pixels.',
                $resourceName,
                $minimum[0],
                $minimum[1],
            ));
        }
    }

    /**
     * Validate an ICO directory and its embedded image payloads.
     */
    private static function validateIcoFile(string $path): void
    {
        $data = file_get_contents($path);
        if (!is_string($data) || strlen($data) < 22) {
            throw new \RuntimeException('The favicon is not a valid ICO file.');
        }

        $header = unpack('vreserved/vtype/vcount', substr($data, 0, 6));
        if (
            !is_array($header)
            || $header['reserved'] !== 0
            || $header['type'] !== 1
            || $header['count'] < 1
            || $header['count'] > 64
        ) {
            throw new \RuntimeException('The favicon is not a valid ICO directory.');
        }

        $directoryEnd = 6 + (16 * $header['count']);
        if (strlen($data) < $directoryEnd) {
            throw new \RuntimeException('The favicon ICO directory is truncated.');
        }

        for ($index = 0; $index < $header['count']; $index++) {
            $entryData = substr($data, 6 + (16 * $index), 16);
            $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbits/Vbytes/Voffset', $entryData);
            if (!is_array($entry)) {
                continue;
            }

            $width = $entry['width'] === 0 ? 256 : $entry['width'];
            $height = $entry['height'] === 0 ? 256 : $entry['height'];
            $offset = $entry['offset'];
            $bytes = $entry['bytes'];

            if (
                $width < 16
                || $height < 16
                || $width > 256
                || $height > 256
                || $bytes < 8
                || $offset < $directoryEnd
                || ($offset + $bytes) > strlen($data)
            ) {
                continue;
            }

            $payload = substr($data, $offset, $bytes);
            $isPng = str_starts_with($payload, "\x89PNG\r\n\x1a\n");
            $dibHeader = unpack('Vsize', substr($payload, 0, 4));
            $isDib = is_array($dibHeader) && in_array($dibHeader['size'], [40, 108, 124], true);

            if ($isPng || $isDib) {
                return;
            }
        }

        throw new \RuntimeException('The favicon does not contain a valid embedded image.');
    }

    public function setLoginEnabled(bool $enabled): void
    {
        $this->updateConfiguration(['login' => $enabled ? '1' : '0']);
    }

    public function setThemeLogosEnabled(bool $enabled): void
    {
        $this->updateConfiguration(['theme_logos' => $enabled ? '1' : '0']);
    }

    public function setLogosEnabled(bool $enabled): void
    {
        $this->updateConfiguration(['show_logos' => $enabled ? '1' : '0']);
    }

    public function setFaviconEnabled(bool $enabled): void
    {
        $this->updateConfiguration(['favicon' => $enabled ? '1' : '0']);
    }

}
