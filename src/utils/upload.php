<?php

/**
 * Upload Handler
 * Handles file uploads for studio cover images and equipment images.
 * Usage: $filename = uploadImage($_FILES['cover_image'], 'studios');
 */

define('UPLOAD_BASE', __DIR__ . '/../../public/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Upload an image file to public/uploads/{folder}/
 *
 * @param array  $file    The $_FILES['field'] array
 * @param string $folder  Subfolder: 'studios' | 'equipments' | 'profiles'
 * @param string|null $old_file  Old filename to delete on success (optional)
 * @return string  The saved filename (store this in DB)
 * @throws RuntimeException on validation or upload failure
 */
function uploadImage(array $file, string $folder, ?string $old_file = null): string
{

    // Validation
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(uploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE));
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new RuntimeException("File is too large. Maximum size is 5MB.");
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES)) {
        throw new RuntimeException("Invalid file type. Only JPG, PNG, WEBP are allowed.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXTENSIONS)) {
        throw new RuntimeException("Invalid file extension.");
    }

    // Destination folder
    $dir = UPLOAD_BASE . $folder . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Generate unique filename to prevent collisions
    $filename = uniqid($folder . '_', true) . '.' . $ext;
    $destination = $dir . $filename;

    // Move file to destination
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException("Failed to save the uploaded file. Check folder permissions.");
    }

    // Delete old file if provided
    if ($old_file) {
        deleteUploadedImage($old_file, $folder);
    }

    return $filename;
}

/**
 * Delete an uploaded image safely (won't delete default placeholders)
 */
function deleteUploadedImage(string $filename, string $folder)
{
    $defaults = ['default_profile.png', 'no_image.png'];
    if (in_array($filename, $defaults)) return;

    $path = UPLOAD_BASE . $folder . '/' . $filename;
    if (file_exists($path)) {
        unlink($path);
    }
}

/**
 * Get the public URL path for an uploaded image
 */
function uploadUrl(string $filename, string $folder): string
{
    $path = '/public/uploads/' . $folder . '/' . $filename;
    // Fallback to default if file doesn't exist
    if (!file_exists(UPLOAD_BASE . $folder . '/' . $filename)) {
        return match ($folder) {
            'studios'    => '/public/images/no_image.png',
            'equipments' => '/public/images/no_image.png',
            'profiles'   => '/public/images/default_profile.png',
            default      => '/public/images/no_image.png',
        };
    }
    return $path;
}

/**
 * Human-readable upload error messages
 */
function uploadErrorMessage(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File exceeds maximum allowed size.",
        UPLOAD_ERR_PARTIAL   => "File was only partially uploaded.",
        UPLOAD_ERR_NO_FILE   => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder on server.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION  => "Upload blocked by server extension.",
        default               => "Unknown upload error.",
    };
}
