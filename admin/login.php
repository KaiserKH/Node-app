<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_admin_authenticated()) {
    redirect('index.php');
}

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
if (!str_starts_with($next, '/admin/') && !str_starts_with($next, 'admin/')) {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        set_flash('error', 'Email and password are required.');
        redirect('login.php?next=' . urlencode($next));
    }

    $stmt = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin' || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Invalid admin credentials.');
        redirect('login.php?next=' . urlencode($next));
    }

    login_user($user);
    mark_admin_authenticated();
    set_flash('success', 'Admin login successful.');

    if (str_starts_with($next, '/')) {
        redirect('..' . $next);
    }

    redirect($next);
}

$pageTitle = APP_NAME . ' - Admin Login';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
  <h2>Admin Login</h2>
  <p>Use admin credentials to access admin controls and data pages.</p>
  <form method="post">
    <?= csrf_input() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">

    <label for="email">Admin Email</label>
    <input id="email" name="email" type="email" required maxlength="190">

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <button type="submit">Login to Admin Panel</button>
  </form>
</section>

<?php require __DIR__ . '/../includes/footer.php';
