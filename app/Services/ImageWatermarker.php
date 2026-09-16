<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Stamps the BookTrips watermark on partner photos with GD.
 *
 * Everything here degrades gracefully: if GD or the configured font is missing
 * the upload is simply left untouched instead of failing the request.
 */
class ImageWatermarker
{
    /**
     * Watermark an image file in place.
     */
    public function apply(string $path): bool
    {
        if (! config('booktrips.uploads.watermark.enabled')) {
            return false;
        }

        if (! extension_loaded('gd')) {
            Log::warning('Watermark skipped: the GD extension is not enabled.');

            return false;
        }

        if (! is_file($path) || ! is_writable($path)) {
            return false;
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return false;
        }

        [$width, $height] = $info;
        $mime = $info['mime'];

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image instanceof \GdImage) {
            return false;
        }

        try {
            imagealphablending($image, true);
            $this->draw($image, $width, $height);
            $this->save($image, $path, $mime);
        } finally {
            imagedestroy($image);
        }

        return true;
    }

    private function draw(\GdImage $image, int $width, int $height): void
    {
        $text = (string) config('booktrips.uploads.watermark.text', 'Booktrips.lk');
        $alpha = max(0, min(127, (int) config('booktrips.uploads.watermark.alpha', 100)));

        $fill = imagecolorallocatealpha($image, 255, 255, 255, $alpha);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, min(127, $alpha + 22));

        if ($fill === false || $shadow === false) {
            return;
        }

        $target = max(120, (int) round($width * 0.5));
        $font = $this->font();

        if ($font !== null) {
            $size = max(10, (int) round($width / 18));
            $box = imagettfbbox($size, 0, $font, $text);

            if (is_array($box)) {
                $textWidth = abs($box[2] - $box[0]);
                $textHeight = abs($box[7] - $box[1]);

                if ($textWidth > $target) {
                    $size = (int) max(8, floor($size * $target / max($textWidth, 1)));
                    $box = imagettfbbox($size, 0, $font, $text);
                    $textWidth = is_array($box) ? abs($box[2] - $box[0]) : 0;
                    $textHeight = is_array($box) ? abs($box[7] - $box[1]) : 0;
                }

                $x = (int) round(($width - $textWidth) / 2);
                $y = (int) round(($height + $textHeight) / 2);

                imagettftext($image, $size, 0, $x + 2, $y + 2, $shadow, $font, $text);
                imagettftext($image, $size, 0, $x, $y, $fill, $font, $text);

                return;
            }
        }

        $this->drawScaledBuiltIn($image, $text, $width, $height, $target, $fill);
    }

    /**
     * Fallback for hosts without a TrueType font: the built-in bitmap font,
     * scaled up pixel by pixel so it still reads on large photos.
     */
    private function drawScaledBuiltIn(\GdImage $image, string $text, int $width, int $height, int $target, int $fill): void
    {
        $fontWidth = max(1, imagefontwidth(5));
        $fontHeight = max(1, imagefontheight(5));
        $textWidth = max(1, $fontWidth * strlen($text));

        $tile = imagecreatetruecolor($textWidth, $fontHeight);
        $background = imagecolorallocate($tile, 0, 0, 0);
        $foreground = imagecolorallocate($tile, 255, 255, 255);

        if ($background === false || $foreground === false) {
            imagedestroy($tile);

            return;
        }

        imagefilledrectangle($tile, 0, 0, $textWidth, $fontHeight, $background);
        imagestring($tile, 5, 0, 0, $text, $foreground);

        $block = max(1, (int) floor($target / $textWidth));
        $offsetX = (int) round(($width - $textWidth * $block) / 2);
        $offsetY = (int) round(($height - $fontHeight * $block) / 2);

        for ($row = 0; $row < $fontHeight; $row++) {
            for ($column = 0; $column < $textWidth; $column++) {
                $pixel = imagecolorat($tile, $column, $row);

                if (((int) (($pixel >> 16) & 0xFF)) < 128) {
                    continue;
                }

                $left = $offsetX + $column * $block;
                $top = $offsetY + $row * $block;

                imagefilledrectangle($image, $left, $top, $left + $block - 1, $top + $block - 1, $fill);
            }
        }

        imagedestroy($tile);
    }

    private function save(\GdImage $image, string $path, string $mime): void
    {
        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, 88),
            'image/png' => imagepng($image, $path, 6),
            'image/webp' => imagewebp($image, $path, 86),
            default => null,
        };
    }

    private function font(): ?string
    {
        $configured = config('booktrips.uploads.watermark.font');

        if (is_string($configured) && $configured !== '' && is_readable($configured)) {
            return $configured;
        }

        foreach ((array) config('booktrips.uploads.watermark.font_candidates', []) as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
