<?php

namespace Outl1ne\NovaMediaHub\MediaHandler\Support;

use Finfo;
use Exception;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileHelpers
{
    const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    public static function getHumanReadableSize(int $sizeInBytes): string
    {
        if ($sizeInBytes == 0) return '0 ' . static::UNITS[1];

        for ($i = 0; $sizeInBytes > 1024; $i++) {
            $sizeInBytes /= 1024;
        }

        return round($sizeInBytes, 2) . ' ' . static::UNITS[$i];
    }

    public static function getMimeType(string $path): string
    {
        $finfo = new Finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path);
    }

    public static function getBase64FileInfo($base64): ?array
    {
        $finfo = new Finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer(base64_decode($base64));
        if (!Str::startsWith($mimeType, 'image')) return null;
        $extension = static::getExtensionFromMimeType($mimeType);
        return [$mimeType, $extension];
    }

    public static function getExtensionFromMimeType(string $mimeType): ?string
    {
        if (!Str::startsWith($mimeType, 'image')) return null;
        return explode('/', $mimeType)[1] ?? null;
    }

    public static function getFileHash(string $path, string $disk = null): string
    {
        if (!$path) return null;
        if (!$disk && !is_file($path)) return null;

        try {
            $fileStream = ($disk) ? Storage::disk($disk)->readStream($path) : fopen($path, 'r');
            $fileSize = $disk ? Storage::disk($disk)->size($path) : filesize($path);
            $fileHash = md5($fileSize.fread($fileStream, 1000000));

            fclose($fileStream);
        } catch (Exception $e) {
            return null;
        }

        return $fileHash;
    }

    /**
     * Uses only the first 1000000 bytes of the file to generate a hash, which may cause collisions for files with the same beginning.
     *
     * @param  string  $path
     * @param  string|null  $disk
     *
     * @return string
     */
    public static function getLegacyFileHash(string $path, ?string $disk = null): string
    {
        if (! $path) {
            return null;
        }
        if (! $disk && ! is_file($path)) {
            return null;
        }

        try {
            $fileStream = ($disk) ? Storage::disk($disk)->readStream($path) : fopen($path, 'r');
            $fileHash = md5(fread($fileStream, 1000000));

            fclose($fileStream);
        } catch (Exception $e) {
            return null;
        }

        return $fileHash;
    }

    public static function sanitizeFileName($fileName)
    {
        try {
            $reverseFileName = strrev($fileName);

            if (str_contains($fileName, '.')) {
                [$extension, $name] = explode('.', $reverseFileName, 2);
            } else {
                $extension = null;
                $name = $reverseFileName;
            }

            if (empty($name)) $name = $extension;

            $sanitizedName = Str::replace(['#', '/', '\\', ' ', '?', '=', '.', '@', '%'], '-', $name);

            if (!empty($extension)) return strrev("{$extension}.{$sanitizedName}");
            return strrev($sanitizedName);
        } catch (Throwable $e) {
            report($e);
            return $fileName;
        }
    }

    // Returns [$fileName, $extension]
    public static function splitNameAndExtension(string $fileName): array
    {
        $name = pathinfo($fileName, PATHINFO_BASENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!empty($extension)) {
            $name = mb_substr($name, 0, - (mb_strlen($extension) + 1));
        }

        return [$name, $extension];
    }

    public static function getTemporaryFilePath($prefix = 'media-', $extension = null)
    {
        if (!$prefix) $prefix = '';
        if (!str_ends_with($prefix, '-')) $prefix = "{$prefix}-";
        $path = tempnam(sys_get_temp_dir(), "o1-nmh{$prefix}");
        if ($extension) return "{$path}.{$extension}";
        return $path;
    }
}
