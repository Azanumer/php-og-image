<?php
/**
 * Programmatic usage example for OgImage.
 *
 * Run: php examples/og-example.php
 */

require __DIR__ . '/../src/OgImage.php';

if (!extension_loaded('gd')) {
    echo "The GD extension is not installed — nothing to render.\n";
    exit(0);
}

// 1. Simple gradient card.
(new OgImage())
    ->setTitle('What If the Moon Was Made of Cheese?')
    ->setSubtitle('what-if series · episode 12')
    ->setBrand('azanumer.cv')
    ->setBackground('#7c3aed', '#1e1b4b')
    ->setAccent('#fbbf24')
    ->render(__DIR__ . '/og-gradient.png');

// 2. Photo background (dimmed automatically) + TTF font if you have one.
$og = (new OgImage())
    ->setTitle('5 Caching Layers Every WordPress Site Needs')
    ->setSubtitle('a practical speed guide')
    ->setBrand('corelhost.com')
    ->setAccent('#22c55e');

$photo = __DIR__ . '/server-room.jpg';
if (is_file($photo)) {
    $og->setBackgroundImage($photo);
}

$font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
if (is_file($font)) {
    $og->setFontFile($font);
}

$og->render(__DIR__ . '/og-photo.png');

echo "Done. Check og-gradient.png and og-photo.png\n";
