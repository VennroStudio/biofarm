<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Components\Exception\DomainExceptionModule;
use App\Components\Storage\FileUploaderService;
use App\Components\Storage\ImageCompressor;
use App\Components\Storage\ImageFileValidator;
use App\Components\Storage\LocalStorage;

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$avif = function_exists('imageavif');
if (in_array('--without-avif', $argv, true)) check(!$avif, 'Run this regression with imageavif unavailable');
$folder = sys_get_temp_dir() . '/biofarm-image-upload-' . bin2hex(random_bytes(6));
mkdir($folder, 0700);
$storage = new LocalStorage($folder . '/stored', '/uploads');
$uploader = new FileUploaderService($storage, new ImageCompressor(75, 2048, 2048));
$validator = new ImageFileValidator(['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','image/gif'=>'gif','image/avif'=>'avif'], 1024 * 1024);
$image = imagecreatetruecolor(32, 24);
imagefill($image, 0, 0, imagecolorallocate($image, 40, 125, 112));
try {
    foreach (['png'=>'imagepng','jpeg'=>'imagejpeg','webp'=>'imagewebp','gif'=>'imagegif'] as $extension=>$encoder) {
        if (!function_exists($encoder)) continue;
        $source = $folder . '/fixture.' . $extension;
        check($encoder($image, $source), 'Create image fixture');
        $hash = hash_file('sha256', $source);
        $uploaded = $uploader->uploadWithMetadata($source, 'products/fixture', $validator);
        $stored = $storage->absolutePath($uploaded->path);
        check(is_file($stored) && filesize($stored) > 0, 'Uploaded image exists');
        check($uploaded->width === 32 && $uploaded->height === 24, 'Dimensions remain correct');
        check($uploaded->url === '/uploads/' . $uploaded->path, 'Public upload URL');
        check((new finfo(FILEINFO_MIME_TYPE))->file($stored) === $uploaded->mimeType, 'Declared MIME matches stored bytes');
        if ($avif && $extension !== 'gif') {
            check($uploaded->mimeType === 'image/avif' && str_ends_with($stored, '.avif'), 'AVIF remains enabled on capable servers: ' . $extension . ' -> ' . $uploaded->mimeType);
        } else {
            check($uploaded->mimeType === 'image/' . $extension, 'Unavailable conversion retains original format');
            check(hash_file('sha256', $stored) === $hash, 'Fallback preserves original image bytes');
        }
        unlink($stored);
        unlink($source);
    }
    $source = $folder . '/invalid.txt';file_put_contents($source, 'not an image');
    try {$uploader->uploadWithMetadata($source, 'products/fixture', $validator);throw new RuntimeException('Non-image accepted');}
    catch (DomainExceptionModule $e) {check($e->getStatus() === 422, 'MIME validation remains enforced');}
    unlink($source);
    echo 'Image upload: PNG/JPEG/WebP/GIF, metadata, public URL, MIME validation and ' . ($avif ? 'AVIF conversion' : 'original-format fallback without AVIF') . " passed.\n";
} finally {
    imagedestroy($image);
    // Only the private fixture directory created above is removed.
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());}
    rmdir($folder);
}
