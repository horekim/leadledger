<?php
/** Photograph uploads from the admin drawer. */

const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;

function upload_dir(): string
{
    return dirname(__DIR__) . '/uploads';
}

/**
 * Take one entry from $_FILES and store it under uploads/.
 * @return array{0:?string,1:string} stored relative path, error message
 */
function upload_photo(array $file): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return [null, 'No file was sent.'];
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'That photograph is larger than the server accepts.'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return [null, 'The upload did not complete.'];
    }
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return [null, 'Keep photographs under 8 MB.'];
    }

    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return [null, 'The upload did not arrive.'];
    }

    // Trust the bytes, not the sent filename or content-type.
    $info = @getimagesize($tmp);
    if ($info === false) {
        return [null, 'That file is not an image.'];
    }

    $ext = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ][$info[2]] ?? null;

    if ($ext === null) {
        return [null, 'Use a JPEG, PNG, WebP or GIF.'];
    }

    $dir = upload_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'The uploads folder is missing and could not be created.'];
    }
    if (!is_writable($dir)) {
        return [null, 'The uploads folder is not writable.'];
    }

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        return [null, 'The photograph could not be saved.'];
    }
    @chmod($dir . '/' . $name, 0644);

    return ['uploads/' . $name, ''];
}

/**
 * Remove a stored photograph. Refuses anything that is not a plain file
 * directly inside uploads/, and leaves the four seed samples alone.
 */
function upload_delete(?string $stored): void
{
    if ($stored === null || !str_starts_with($stored, 'uploads/')) {
        return;
    }
    $name = basename($stored);
    if (!preg_match('/^[0-9a-f]{32}\.(jpg|png|gif|webp)$/', $name)) {
        return; // seed photographs and anything unexpected stay put
    }
    $path = upload_dir() . '/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}
