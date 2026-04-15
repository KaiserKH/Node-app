<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (!is_logged_in()) {
        $user = null;
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, bio, avatar_path, last_edited_by, updated_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $row = $stmt->fetch();

    $user = $row ?: null;
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        $prefix = rtrim(BASE_URL, '/');
        header('Location: ' . ($prefix === '' ? '' : $prefix) . '/login.php');
        exit;
    }
}

function has_role(string $role): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

function require_admin_or_manager(): void
{
    $user = current_user();

    if ($user === null || !in_array($user['role'], ['admin', 'manager'], true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function require_admin(): void
{
    if (!has_role('admin')) {
        http_response_code(403);
        exit('Admins only.');
    }
}
