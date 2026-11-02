<?php

declare(strict_types=1);

$testRoot = sys_get_temp_dir() . '/mod-upgrade-preservation-' . bin2hex(random_bytes(6));
if (!mkdir($testRoot, 0750, true)) {
    throw new RuntimeException('Unable to create the upgrade test directory.');
}

define('GLPI_ROOT', true);
define('GLPI_PLUGIN_DOC_DIR', $testRoot);

require dirname(__DIR__) . '/src/BrandManager.php';

$uploads = $testRoot . '/mod/uploads';
if (!mkdir($uploads, 0750, true)) {
    throw new RuntimeException('Unable to create the test upload directory.');
}

$settingsPath = $testRoot . '/mod/settings.ini';
$originalSettings = "title=\"Company identity\"\nlogin=\"1\"\ntheme_logos=\"1\"\nshow_logos=\"1\"\nfavicon=\"1\"\n";
if (file_put_contents($settingsPath, $originalSettings) === false) {
    throw new RuntimeException('Unable to create test settings.');
}

$legacyFiles = [
    'favicon.ico'            => 'sample-icon.ico',
    'logo-G-100.png'         => 'sample-logo-mark.png',
    'logo-GLPI-100.png'      => 'sample-logo-horizontal.png',
    'logo-GLPI-250.png'      => 'sample-logo-login.png',
    'logo-G-100-black.png'   => 'sample-logo-mark-dark.png',
    'logo-G-100-white.png'   => 'sample-logo-mark-light.png',
    'logo-GLPI-100-black.png' => 'sample-logo-horizontal-dark.png',
    'logo-GLPI-100-white.png' => 'sample-logo-horizontal-light.png',
    'logo-GLPI-250-black.png' => 'sample-logo-login-dark.png',
    'logo-GLPI-250-white.png' => 'sample-logo-login-light.png',
];

foreach ($legacyFiles as $legacyName => $currentName) {
    file_put_contents($uploads . '/' . $legacyName, 'original-upload:' . $legacyName);
}
file_put_contents($uploads . '/customer-extra.bin', 'preserve-this-file');

$manager = new \GlpiPlugin\Mod\BrandManager();
$manager->install();

if (file_get_contents($settingsPath) !== $originalSettings) {
    throw new RuntimeException('The existing settings.ini was modified during plugin update.');
}

foreach ($legacyFiles as $legacyName => $currentName) {
    $oldPath = $uploads . '/' . $legacyName;
    $newPath = $uploads . '/' . $currentName;
    if (!is_file($oldPath)) {
        throw new RuntimeException(sprintf('Legacy upload was removed: %s', $legacyName));
    }
    if (!is_file($newPath) || file_get_contents($newPath) !== 'original-upload:' . $legacyName) {
        throw new RuntimeException(sprintf('Legacy upload was not preserved as %s.', $currentName));
    }
}

if (file_get_contents($uploads . '/customer-extra.bin') !== 'preserve-this-file') {
    throw new RuntimeException('An unrelated file in the uploads directory was modified.');
}

// A second install/update call must not overwrite a newer upload or settings.
file_put_contents($uploads . '/sample-logo-mark.png', 'newer-admin-upload');
$manager->install();

if (file_get_contents($settingsPath) !== $originalSettings) {
    throw new RuntimeException('A repeated install/update modified settings.ini.');
}
if (file_get_contents($uploads . '/sample-logo-mark.png') !== 'newer-admin-upload') {
    throw new RuntimeException('A repeated migration overwrote a newer administrator upload.');
}
if (file_get_contents($uploads . '/logo-G-100.png') !== 'original-upload:logo-G-100.png') {
    throw new RuntimeException('The previous uploaded image was not retained for rollback.');
}

$removeDirectory = static function (string $directory) use (&$removeDirectory): void {
    if (!is_dir($directory)) {
        return;
    }
    foreach (scandir($directory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $directory . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            $removeDirectory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($directory);
};
$removeDirectory($testRoot);

fwrite(STDOUT, "Upgrade preservation test passed: settings and previous uploads remain intact.\n");
