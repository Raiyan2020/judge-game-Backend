<?php

$source = __DIR__ . '/../public/_dashboard/judge-logo.png';
$target = __DIR__ . '/../public/_dashboard/sidebar-logo.png';

$image = imagecreatefromjpeg($source);
$width = imagesx($image);
$height = imagesy($image);

$size = 182;
$cropX = (int) (($width - $size) / 2);
$cropY = 248;

$outputSize = 256;
$icon = imagecreatetruecolor($outputSize, $outputSize);
$black = imagecolorallocate($icon, 0, 0, 0);
imagefill($icon, 0, 0, $black);

$scale = $outputSize / $size;
$newW = (int) round($size * $scale);
$newH = (int) round($size * $scale);
$destX = (int) round(($outputSize - $newW) / 2);
$destY = (int) round(($outputSize - $newH) / 2) - 28;

imagecopyresampled($icon, $image, $destX, $destY, $cropX, $cropY, $newW, $newH, $size, $size);

imagepng($icon, $target, 9);

imagedestroy($image);
imagedestroy($icon);

echo "Created centered {$outputSize}x{$outputSize} -> {$target}\n";
