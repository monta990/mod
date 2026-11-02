<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $relative) use ($root): string {
    $path = $root . DIRECTORY_SEPARATOR . $relative;
    if (!is_file($path)) {
        throw new RuntimeException(sprintf('Required file not found: %s', $relative));
    }

    $content = file_get_contents($path);
    if (!is_string($content)) {
        throw new RuntimeException(sprintf('Unable to read file: %s', $relative));
    }

    return $content;
};

$expect = static function (bool $condition, string $message) use (&$errors): void {
    if (!$condition) {
        $errors[] = $message;
    }
};

$setup = $read('setup.php');
$xml = $read('mod.xml');

$expect(
    preg_match("/const PLUGIN_MOD_VERSION = '12\\.0\\.0';/", $setup) === 1,
    'setup.php must declare plugin version 12.0.0.'
);
$expect(
    preg_match("/const PLUGIN_MOD_MIN_GLPI_VERSION = '12\\.0\\.0';/", $setup) === 1
    && preg_match("/const PLUGIN_MOD_MAX_GLPI_VERSION = '12\\.1\\.0';/", $setup) === 1,
    'setup.php must restrict compatibility to GLPI 12.0.x.'
);
$expect(
    preg_match('/<id>mod<\\/id>/', $xml) === 1
    && preg_match('/<version>12\\.0\\.0<\\/version>/', $xml) === 1
    && preg_match('/<num>12\\.0\\.0<\\/num>/', $xml) === 1,
    'mod.xml must identify plugin mod and version 12.0.0.'
);
$expect(
    preg_match('/<glpi>12\\.0<\\/glpi>/', $xml) === 1
    && preg_match('/<php>8\\.3<\\/php>/', $xml) === 1
    && str_contains($xml, '<compatibility>~12.0.0</compatibility>'),
    'mod.xml must declare GLPI 12.0.x and PHP 8.3+ requirements.'
);

$expect(
    preg_match("/'min' => '8\\.3'/", $setup) === 1,
    'setup.php must declare PHP 8.3 as the minimum requirement.'
);

$required = [
    'setup.php',
    'hook.php',
    'mod.xml',
    'LICENSE.txt',
    'src/BrandManager.php',
    'src/BrandingHooks.php',
    'src/Paths.php',
    'src/UIBranding.php',
    'src/Controller/AssetController.php',
    'src/Controller/BrandingCssController.php',
    'src/Controller/UIBrandingController.php',
    'templates/uibranding.html.twig',
    'public/uibranding.js',
    'public/favicon.js',
    'resources/modifiers.ini',
];

foreach ($required as $relative) {
    $expect(is_file($root . DIRECTORY_SEPARATOR . $relative), sprintf('Missing required release file: %s', $relative));
}

$expect(basename($root) === 'mod', 'All plugin files must be located inside the mod/ directory.');
$expect(is_dir($root . '/resources/imagenes'), 'Bundled images must be stored in resources/imagenes/.');
$expect(!is_dir($root . '/resources/images'), 'Do not keep an English-named resources/images directory.');
$expect(!is_dir($root . '/front'), 'The modern GLPI 12 plugin must not add legacy front/ entry points.');
$expect(!is_dir($root . '/ajax'), 'The modern GLPI 12 plugin must not add legacy ajax/ entry points.');
$expect(!is_dir($root . '/vendor'), 'The plugin must not ship a private vendor directory.');
$expect(!is_file($root . '/composer.json') && !is_file($root . '/composer.lock'), 'Composer files are not part of this plugin package.');

foreach (glob($root . '/locales/*.po') ?: [] as $poPath) {
    $po = file_get_contents($poPath) ?: '';
    $expect(str_contains($po, 'Project-Id-Version: UI Branding 12.0.0'), basename($poPath) . ' has stale project metadata.');
    $expect(!str_contains($po, 'ORGANIZATION') && !str_contains($po, 'EMAIL@ADDRESS'), basename($poPath) . ' contains placeholder metadata.');
    $moPath = substr($poPath, 0, -3) . '.mo';
    $expect(is_file($moPath), basename($moPath) . ' compiled catalog is missing.');
    if (is_file($moPath)) {
        $mo = file_get_contents($moPath) ?: '';
        $expect(str_contains($mo, 'Project-Id-Version: UI Branding 12.0.0'), basename($moPath) . ' has stale compiled metadata.');
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Release validation failed:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

fwrite(STDOUT, "UI Branding 12.0.0 release metadata and package structure are valid.\n");
