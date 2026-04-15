<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Invalid credentials.');
        redirect('login.php');
    }

    login_user($user);
    set_flash('success', 'Welcome back!');
    redirect('dashboard.php');
}

$pageTitle = APP_NAME . ' - Login';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Login</h2>
  <form method="post">
    <?= csrf_input() ?>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="190">

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <button type="submit">Login</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
