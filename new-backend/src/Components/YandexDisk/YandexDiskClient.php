<?php

declare(strict_types=1);

namespace App\Components\YandexDisk;

interface YandexDiskClient
{
    /**
     * Upload a file to Yandex Disk.
     *
     * @param string $tmpFilePath  Absolute path to the temporary file
     * @param string $folder       Target folder on Yandex Disk (e.g. "iswowbanner")
     * @param string $fileName     Target file name with extension (e.g. "123_invoice.pdf")
     * @return string              Full disk path of the uploaded file (e.g. "iswowbanner/123_invoice.pdf")
     */
    public function upload(string $tmpFilePath, string $folder, string $fileName): string;

    /**
     * Get a direct download URL for a file.
     *
     * @param string $diskPath  Full path on Yandex Disk (e.g. "iswowbanner/123_invoice.pdf")
     */
    public function download(string $diskPath): string;

    /**
     * Delete a file from Yandex Disk.
     *
     * @param string $diskPath  Full path on Yandex Disk (e.g. "iswowbanner/123_invoice.pdf")
     */
    public function delete(string $diskPath): void;
}