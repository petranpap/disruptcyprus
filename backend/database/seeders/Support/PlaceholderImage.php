<?php

namespace Database\Seeders\Support;

use RuntimeException;

/**
 * Generates abstract, brand-tinted JPEG placeholders locally with GD (no network, no licensing).
 */
class PlaceholderImage
{
    private const INK = [15, 23, 42];

    public static function make(string $hexColor, int $seed, int $width = 1600, int $height = 1000): string
    {
        mt_srand($seed);

        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('GD could not allocate the placeholder image.');
        }

        [$red, $green, $blue] = sscanf($hexColor, '#%02x%02x%02x') ?: [0, 165, 230];

        // Diagonal gradient from the accent color to brand ink.
        for ($y = 0; $y < $height; $y += 2) {
            $ratio = $y / $height;
            $color = imagecolorallocate(
                $image,
                (int) ($red + (self::INK[0] - $red) * $ratio),
                (int) ($green + (self::INK[1] - $green) * $ratio),
                (int) ($blue + (self::INK[2] - $blue) * $ratio),
            );
            imagefilledrectangle($image, 0, $y, $width, $y + 1, (int) $color);
        }

        // Soft translucent circles and hairlines for texture.
        for ($index = 0; $index < 7; $index++) {
            $alpha = mt_rand(96, 118);
            $light = imagecolorallocatealpha($image, 255, 255, 255, $alpha);
            $radius = mt_rand((int) ($height * 0.2), (int) ($height * 0.9));
            imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), $radius, $radius, (int) $light);
        }

        $line = imagecolorallocatealpha($image, 255, 255, 255, 110);
        for ($offset = -$height; $offset < $width; $offset += 64) {
            imageline($image, $offset, $height, $offset + $height, 0, (int) $line);
        }

        $path = tempnam(sys_get_temp_dir(), 'placeholder').'.jpg';
        imagejpeg($image, $path, 82);
        imagedestroy($image);

        return $path;
    }
}
