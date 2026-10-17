# PHP OG Image

Dependency-free Open Graph image generator (PHP GD only — no Composer, no
ImageMagick). Renders a 1200×630 social card for link previews: gradient or
photo background, accent bar, wrapped title, subtitle and brand line.

## Requirements

- PHP 7.4+
- `php-gd` extension

## Quick start

```php
require 'src/OgImage.php';

(new OgImage())
    ->setTitle('5 Caching Layers Every WordPress Site Needs')
    ->setSubtitle('a practical speed guide')
    ->setBrand('corelhost.com')
    ->setBackground('#7c3aed', '#1e1b4b')
    ->setAccent('#22c55e')
    ->render('og.png'); // .png, .jpg and .jpeg supported
```

Or from the CLI:

```bash
php generate.php --title "Hello World" --output og.png \
    --subtitle "my latest post" --brand "example.com" \
    --bg1 "#0ea5e9" --bg2 "#0c4a6e" --accent "#f59e0b"
```

| Flag | Default | Purpose |
|---|---|---|
| `--title` | (required) | main headline, auto-wrapped |
| `--output` | (required) | output file; extension picks PNG/JPEG |
| `--subtitle`, `--brand` | empty | secondary lines |
| `--bg1`, `--bg2` | `#1f2937`, `#111827` | gradient colours |
| `--accent` | `#f59e0b` | left bar colour |
| `--bg-image` | — | photo background (cover-fit, dimmed) |
| `--logo` | — | small logo drawn top-left |
| `--font` | — | TTF/OTF for crisp text; GD bitmap fonts otherwise |
| `--width`, `--height` | `1200`, `630` | canvas size |

## Notes

- Text is byte-wrapped (`strlen`), so very long non-ASCII words may wrap
  late — keep titles under ~60 characters for the classic layout.
- A photo background is darkened ~45% so white text stays legible.
- See `examples/og-example.php` for the programmatic API.

MIT licensed.
