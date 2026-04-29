<?php
function watermark_image(string $absPath, array $lines): bool
{
    if (!file_exists($absPath)) return false;

    $info = @getimagesize($absPath);
    if (!$info) return false;

    $mime = $info['mime'] ?? '';
    if ($mime === 'image/jpeg') {
        $img = @imagecreatefromjpeg($absPath);
    } elseif ($mime === 'image/png') {
        $img = @imagecreatefrompng($absPath);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($absPath);
    } else {
        return false;
    }
    if (!$img) return false;

    $w = imagesx($img);
    $h = imagesy($img);

    imagealphablending($img, true);
    imagesavealpha($img, true);

    $font = 3;
    $lineHeight = 14;
    $pad = 10;
    $boxH = $pad + (count($lines) * $lineHeight) + $pad;

    $blackAlpha = imagecolorallocatealpha($img, 0, 0, 0, 70);
    $white = imagecolorallocate($img, 255, 255, 255);

    imagefilledrectangle($img, 0, $h - $boxH, $w, $h, $blackAlpha);

    $y = $h - $boxH + $pad;
    foreach ($lines as $line) {
        imagestring($img, $font, $pad, $y, (string)$line, $white);
        $y += $lineHeight;
    }

    $ok = false;
    if ($mime === 'image/jpeg') {
        $ok = imagejpeg($img, $absPath, 85);
    } elseif ($mime === 'image/png') {
        $ok = imagepng($img, $absPath, 6);
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        $ok = imagewebp($img, $absPath, 85);
    }

    imagedestroy($img);
    return $ok;
}