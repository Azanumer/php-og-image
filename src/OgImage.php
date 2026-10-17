<?php
/**
 * OgImage — dependency-free Open Graph image generator (PHP GD).
 *
 * Renders a 1200x630 social card: gradient (or photo) background, accent
 * bar, wrapped title, subtitle and brand line. TTF fonts are used when a
 * font file is provided; otherwise it falls back to GD's bitmap fonts.
 *
 * Usage:
 *   $og = (new OgImage())
 *       ->setTitle('What If the Moon Was Made of Cheese?')
 *       ->setSubtitle('what-if series · episode 12')
 *       ->setBrand('azanumer.cv')
 *       ->setBackground('#7c3aed', '#1e1b4b');
 *   $og->render(__DIR__ . '/og.png'); // true on success
 */

declare(strict_types=1);

class OgImage
{
    private int $width;
    private int $height;
    private string $title = '';
    private string $subtitle = '';
    private string $brand = '';
    private string $bgFrom = '#1f2937';
    private string $bgTo = '#111827';
    private string $accent = '#f59e0b';
    private ?string $bgImage = null;
    private ?string $logo = null;
    private ?string $fontFile = null;
    private int $margin = 80;

    public function __construct(int $width = 1200, int $height = 630)
    {
        $this->width  = max(200, $width);
        $this->height = max(200, $height);
    }

    public function setTitle(string $title): self     { $this->title = $title; return $this; }
    public function setSubtitle(string $sub): self    { $this->subtitle = $sub; return $this; }
    public function setBrand(string $brand): self     { $this->brand = $brand; return $this; }
    public function setAccent(string $hex): self      { $this->accent = $hex; return $this; }
    public function setMargin(int $px): self          { $this->margin = max(16, $px); return $this; }

    /** Gradient background, top colour first. */
    public function setBackground(string $from, string $to): self
    {
        $this->bgFrom = $from;
        $this->bgTo   = $to;
        return $this;
    }

    /** Photo background (drawn cover-fit, dimmed for legibility). */
    public function setBackgroundImage(string $path): self
    {
        $this->bgImage = $path;
        return $this;
    }

    /** Small logo drawn top-left (PNG/JPG/GIF/WebP with transparency kept). */
    public function setLogo(string $path): self
    {
        $this->logo = $path;
        return $this;
    }

    /** TTF/OTF font for crisp text. Falls back to GD bitmap fonts. */
    public function setFontFile(string $path): self
    {
        $this->fontFile = $path;
        return $this;
    }

    /**
     * Render and save the image. The output format follows the file
     * extension (.png, .jpg/.jpeg); anything else defaults to PNG.
     *
     * @return bool true on success.
     */
    public function render(string $outputPath): bool
    {
        if (!extension_loaded('gd')) {
            trigger_error('OgImage: the GD extension is required', E_USER_WARNING);
            return false;
        }

        $img = imagecreatetruecolor($this->width, $this->height);
        if ($img === false) {
            return false;
        }

        $this->paintBackground($img);

        $text  = $this->alloc($img, '#ffffff');
        $muted = $this->alloc($img, '#d1d5db');
        $accent = $this->alloc($img, $this->accent);

        $m = $this->margin;
        $x = $m + 56; // text starts right of the accent bar

        // Accent bar on the left.
        imagefilledrectangle($img, $m, $m, $m + 14, $this->height - $m, $accent);

        // Optional logo, top-left.
        $cursorY = $m;
        if ($this->logo !== null && is_file($this->logo)) {
            $logo = $this->loadImage($this->logo);
            if ($logo !== null) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $targetH = 72;
                $targetW = (int) round($lw * ($targetH / max(1, $lh)));
                imagecopyresampled($img, $logo, $x, $cursorY, 0, 0, $targetW, $targetH, $lw, $lh);
                imagedestroy($logo);
                $cursorY += $targetH + 36;
            }
        }

        // Title (wrapped).
        $cursorY = $this->drawWrapped($img, $this->title, $x, $cursorY + 8, $this->width - $x - $m, $text, 64, 5);

        // Subtitle.
        if ($this->subtitle !== '') {
            $cursorY = $this->drawWrapped($img, $this->subtitle, $x, $cursorY + 28, $this->width - $x - $m, $muted, 32, 4);
        }

        // Brand, bottom-right.
        if ($this->brand !== '') {
            $this->drawLine($img, $this->brand, $muted, 26, 3, $m, $this->height - $m - 40, true);
        }

        $ok = $this->save($img, $outputPath);
        imagedestroy($img);
        return $ok;
    }

    // ------------------------------------------------------------------
    // internals
    // ------------------------------------------------------------------

    private function paintBackground($img): void
    {
        if ($this->bgImage !== null && is_file($this->bgImage)) {
            $src = $this->loadImage($this->bgImage);
            if ($src !== null) {
                // Cover-fit the canvas.
                $sw = imagesx($src);
                $sh = imagesy($src);
                $scale = max($this->width / $sw, $this->height / $sh);
                $dw = (int) round($sw * $scale);
                $dh = (int) round($sh * $scale);
                $dx = (int) round(($this->width - $dw) / 2);
                $dy = (int) round(($this->height - $dh) / 2);
                imagecopyresampled($img, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);
                imagedestroy($src);
                // Dim for text legibility.
                $dim = imagecolorallocatealpha($img, 0, 0, 0, 70);
                imagefilledrectangle($img, 0, 0, $this->width, $this->height, $dim);
                return;
            }
        }

        // Vertical gradient.
        [$r1, $g1, $b1] = $this->hexToRgb($this->bgFrom);
        [$r2, $g2, $b2] = $this->hexToRgb($this->bgTo);
        for ($y = 0; $y < $this->height; $y++) {
            $t = $y / max(1, $this->height - 1);
            $c = imagecolorallocate(
                $img,
                (int) round($r1 + ($r2 - $r1) * $t),
                (int) round($g1 + ($g2 - $g1) * $t),
                (int) round($b1 + ($b2 - $b1) * $t)
            );
            imageline($img, 0, $y, $this->width, $y, $c);
        }
    }

    /** Word-wrap and draw; returns the y position below the last line. */
    private function drawWrapped($img, string $text, int $x, int $y, int $maxWidth, int $color, int $ttfSize, int $gdFont): int
    {
        if ($text === '') {
            return $y;
        }
        $useTtf = $this->fontFile !== null && is_file($this->fontFile);
        $maxChars = $useTtf ? (int) floor($maxWidth / ($ttfSize * 0.55)) : (int) floor($maxWidth / imagefontwidth($gdFont));
        $maxChars = max(8, $maxChars);

        foreach ($this->wrapText($text, $maxChars) as $line) {
            if ($useTtf) {
                imagettftext($img, $ttfSize, 0, $x, $y + $ttfSize, $color, $this->fontFile, $line);
                $y += (int) round($ttfSize * 1.35);
            } else {
                imagestring($img, $gdFont, $x, $y, $line, $color);
                $y += imagefontheight($gdFont) + 10;
            }
        }
        return $y;
    }

    /** Single line, optionally right-aligned to ($x, $maxX). */
    private function drawLine($img, string $text, int $color, int $ttfSize, int $gdFont, int $x, int $y, bool $rightAlign): void
    {
        $useTtf = $this->fontFile !== null && is_file($this->fontFile);
        if ($rightAlign) {
            $w = $useTtf
                ? (int) ceil(max(array_map(function ($v) { return $v; }, imagettfbbox($ttfSize, 0, $this->fontFile, $text))) - min(array_map(function ($v) { return $v; }, imagettfbbox($ttfSize, 0, $this->fontFile, $text))))
                : strlen($text) * imagefontwidth($gdFont);
            $x = $this->width - $this->margin - $w;
        }
        if ($useTtf) {
            imagettftext($img, $ttfSize, 0, $x, $y + $ttfSize, $color, $this->fontFile, $text);
        } else {
            imagestring($img, $gdFont, $x, $y, $text, $color);
        }
    }

    private function wrapText(string $text, int $maxChars): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $try = $line === '' ? $word : $line . ' ' . $word;
            if (strlen($try) > $maxChars && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        return $lines;
    }

    private function alloc($img, string $hex): int
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        return imagecolorallocate($img, $r, $g, $b);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0, 0, 0];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }
        switch ($info['mime']) {
            case 'image/jpeg': return @imagecreatefromjpeg($path);
            case 'image/png':  return @imagecreatefrompng($path);
            case 'image/gif':  return @imagecreatefromgif($path);
            case 'image/webp': return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
            default:           return null;
        }
    }

    private function save($img, string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            return imagejpeg($img, $path, 90);
        }
        return imagepng($img, $path); // .png and everything else
    }
}
