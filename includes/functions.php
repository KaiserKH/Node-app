<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

start_secure_session();

function ensure_schema_upgrades(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $queries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS village VARCHAR(120) NULL AFTER phone",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER village",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER address",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER gender",
    ];

    try {
        foreach ($queries as $sql) {
            db()->exec($sql);
        }
    } catch (Throwable $e) {
        // Ignore migration failures to avoid breaking requests on restricted DB users.
    }
}

ensure_schema_upgrades();

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function normalize_upload_path(string $absolutePath): string
{
    $base = realpath(__DIR__ . '/../uploads');
    $full = realpath($absolutePath);

    if (!$base || !$full || strpos($full, $base) !== 0) {
        return '';
    }

    return str_replace(realpath(__DIR__ . '/..') . '/', '', $full);
}

function save_media_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed.'];
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        return ['ok' => false, 'error' => 'File too large (max 50MB).'];
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        return ['ok' => false, 'error' => 'Invalid upload source.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpName) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!$mime || !isset(ALLOWED_MEDIA_MIME[$mime])) {
        return ['ok' => false, 'error' => 'Unsupported file type.'];
    }

    $ext = ALLOWED_MEDIA_MIME[$mime];
    $subdir = date('Y/m');
    $targetDir = rtrim(UPLOAD_DIR, '/') . '/' . $subdir;

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        return ['ok' => false, 'error' => 'Failed to create upload directory.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return ['ok' => false, 'error' => 'Failed to move upload.'];
    }

    $relativePath = 'uploads/media/' . $subdir . '/' . $filename;

    $mediaType = str_starts_with($mime, 'image/')
        ? 'image'
        : (str_starts_with($mime, 'video/') ? 'video' : 'audio');

    return [
        'ok' => true,
        'path' => $relativePath,
        'media_type' => $mediaType,
        'mime' => $mime,
    ];
}

function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9+\-\s]{7,20}$/', $phone);
}
