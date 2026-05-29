<?php

namespace Afterburner\Documents\Support;

use Symfony\Component\Mime\MimeTypes;

class DocumentUploadRules
{
    public static function maxFileSizeBytes(): int
    {
        return (int) config('afterburner-documents.upload.max_file_size', 2147483648);
    }

    public static function maxFileSizeKilobytes(): int
    {
        return (int) ceil(self::maxFileSizeBytes() / 1024);
    }

    public static function chunkSizeBytes(): int
    {
        return (int) config('afterburner-documents.upload.chunk_size', 5242880);
    }

    public static function chunkSizeKilobytes(): int
    {
        return (int) ceil(self::chunkSizeBytes() / 1024);
    }

    public static function maxChunks(): int
    {
        return (int) config('afterburner-documents.upload.max_chunks', 5000);
    }

    public static function sessionTtlHours(): int
    {
        return (int) config('afterburner-documents.upload.session_ttl_hours', 24);
    }

    public static function allowedMimeTypes(): array
    {
        return config('afterburner-documents.upload.allowed_mime_types', []);
    }

    public static function fileRules(string $attribute = 'file'): array
    {
        $rules = [
            $attribute => [
                'required',
                'file',
                'max:'.self::maxFileSizeKilobytes(),
            ],
        ];

        $allowedMimeTypes = self::allowedMimeTypes();

        if (! empty($allowedMimeTypes)) {
            $rules[$attribute][] = 'mimetypes:'.implode(',', $allowedMimeTypes);
        }

        return $rules;
    }

    public static function exceedsMaxChunks(int $totalSize): bool
    {
        $chunkSize = self::chunkSizeBytes();

        if ($chunkSize <= 0) {
            return true;
        }

        return (int) ceil($totalSize / $chunkSize) > self::maxChunks();
    }

    public static function livewireFileRules(bool $required = true): array
    {
        $rules = self::fileRules()['file'];

        if (! $required) {
            $rules[0] = 'nullable';
        }

        return $rules;
    }

    public static function mimeTypeAllowed(?string $mimeType): bool
    {
        $allowedMimeTypes = self::allowedMimeTypes();

        if (empty($allowedMimeTypes)) {
            return true;
        }

        if ($mimeType === null || $mimeType === '') {
            return false;
        }

        return in_array($mimeType, $allowedMimeTypes, true);
    }

    public static function resolveMimeType(string $filename, ?string $contentTypeHeader = null): ?string
    {
        if ($contentTypeHeader !== null && $contentTypeHeader !== '' && ! self::isTransportContentType($contentTypeHeader)) {
            return self::normalizeContentType($contentTypeHeader);
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($extension === '') {
            return null;
        }

        $guessed = (new MimeTypes)->getMimeTypes(strtolower($extension));

        return $guessed[0] ?? null;
    }

    public static function isTransportContentType(?string $contentType): bool
    {
        if ($contentType === null || $contentType === '') {
            return false;
        }

        $normalized = self::normalizeContentType($contentType);

        return in_array($normalized, [
            'multipart/form-data',
            'application/x-www-form-urlencoded',
            'application/offset+octet-stream',
        ], true);
    }

    public static function normalizeContentType(string $contentType): string
    {
        return strtolower(trim(strtok($contentType, ';')));
    }
}
