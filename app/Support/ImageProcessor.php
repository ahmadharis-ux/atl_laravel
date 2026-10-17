<?php

namespace App\Support;

use GdImage;

/**
 * Utilitas pemrosesan gambar ringan berbasis GD: memperkecil dan mengubah
 * gambar menjadi WebP agar berkas yang tersimpan lebih kecil dan konsisten.
 *
 * Bersifat best-effort: mengembalikan null bila GD/WebP tidak tersedia atau
 * bytes bukan gambar valid, sehingga pemanggil dapat menyimpan berkas asli.
 */
final class ImageProcessor
{
    /**
     * Ubah bytes gambar menjadi WebP, diperkecil agar muat dalam batas
     * dimensi (tidak diperbesar bila sudah lebih kecil).
     */
    public static function toWebp(string $bytes, int $maxWidth, int $maxHeight, int $quality = 82): ?string
    {
        if ($bytes === '' || ! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $image = @imagecreatefromstring($bytes);

        if (! $image instanceof GdImage) {
            return null;
        }

        $image = self::toTrueColor($image);
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            return null;
        }

        $scale = min(1.0, $maxWidth / $width, $maxHeight / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        if ($targetWidth !== $width || $targetHeight !== $height) {
            $resized = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC);

            if ($resized instanceof GdImage) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        ob_start();
        $encoded = imagewebp($image, null, $quality);
        $output = ob_get_clean();

        imagedestroy($image);

        if ($encoded !== true || $output === false || $output === '') {
            return null;
        }

        return $output;
    }

    /**
     * Pastikan gambar ber-mode truecolor (agar transparansi WebP terjaga dan
     * `imagescale` bekerja mulus pada gambar palet).
     */
    private static function toTrueColor(GdImage $image): GdImage
    {
        if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagesavealpha($image, true);

        return $image;
    }
}
