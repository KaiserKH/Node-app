<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'dashboard.php');
if (!preg_match('/^[a-zA-Z0-9_\-\/\.\?=&%]+$/', $next) || str_contains($next, '://')) {
  $next = 'dashboard.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Invalid credentials.');
      redirect('login.php?next=' . urlencode($next));
    }

    login_user($user);
    if (($user['role'] ?? '') === 'admin') {
      mark_admin_authenticated();
    }
    set_flash('success', 'Welcome back!');
    redirect($next);
}

$pageTitle = APP_NAME . ' - Login';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Login</h2>
  <form method="post">
    <?= csrf_input() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="190">

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <button type="submit">Login</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
