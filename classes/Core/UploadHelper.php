<?php
namespace Core;

class UploadHelper {
    private const MAX_FILE_SIZE = 2097152; // 2 MB
    private const ALLOWED_TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    public static function validateImageFile(array $file): ?string {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            return null;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $imageType = @exif_imagetype($file['tmp_name']);
        if (!$imageType || !isset(self::ALLOWED_TYPES[$imageType])) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return null;
        }

        $contents = file_get_contents($file['tmp_name'], false, null, 0, 512);
        if ($contents !== false && preg_match('/<\?(php|=)/i', $contents)) {
            return null;
        }

        return self::ALLOWED_TYPES[$imageType];
    }

    public static function saveImage(array $file, string $uploadPath): ?string {
        $extension = self::validateImageFile($file);
        if (!$extension) {
            return null;
        }

        $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return $fileName;
    }
}
