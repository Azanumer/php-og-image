#!/usr/bin/env php
<?php
/**
 * generate.php — CLI wrapper for OgImage.
 *
 * Usage:
 *   php generate.php --title "Hello World" --output og.png \
 *       [--subtitle "..."] [--brand "example.com"] \
 *       [--bg1 "#7c3aed" --bg2 "#1e1b4b"] [--accent "#f59e0b"] \
 *       [--bg-image photo.jpg] [--logo logo.png] [--font /path/to/font.ttf] \
 *       [--width 1200 --height 630]
 */

require __DIR__ . '/src/OgImage.php';

$opts = getopt('', [
    'title:', 'subtitle::', 'brand::',
    'bg1::', 'bg2::', 'accent::',
    'bg-image::', 'logo::', 'font::',
    'width::', 'height::', 'output:',
]);

if (empty($opts['title']) || empty($opts['output'])) {
    fwrite(STDERR, "Usage: php generate.php --title \"...\" --output og.png [options]\n");
    exit(2);
}

$og = new OgImage(
    isset($opts['width']) ? (int) $opts['width'] : 1200,
    isset($opts['height']) ? (int) $opts['height'] : 630
);

$og->setTitle($opts['title'])
   ->setSubtitle($opts['subtitle'] ?? '')
   ->setBrand($opts['brand'] ?? '')
   ->setBackground($opts['bg1'] ?? '#1f2937', $opts['bg2'] ?? '#111827')
   ->setAccent($opts['accent'] ?? '#f59e0b');

if (!empty($opts['bg-image'])) { $og->setBackgroundImage($opts['bg-image']); }
if (!empty($opts['logo']))     { $og->setLogo($opts['logo']); }
if (!empty($opts['font']))     { $og->setFontFile($opts['font']); }

if ($og->render($opts['output'])) {
    echo 'Wrote ' . $opts['output'] . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "Failed to render image (is the GD extension installed?).\n");
exit(1);
