<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        set_flash('error', 'All fields are required.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Invalid email address.');
        redirect('register.php');
    }

    if (strlen($password) < 8) {
        set_flash('error', 'Password must be at least 8 characters.');
        redirect('register.php');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        set_flash('error', 'Email already exists.');
        redirect('register.php');
    }

    $insert = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
    ]);

    set_flash('success', 'Registration successful. Please login.');
    redirect('login.php');
}

$pageTitle = APP_NAME . ' - Register';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Create Account</h2>
  <form method="post">
    <?= csrf_input() ?>
    <label for="name">Full Name</label>
    <input id="name" name="name" required maxlength="100">

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="190">

    <label for="password">Password</label>
    <input id="password" name="password" type="password" minlength="8" required>

    <button type="submit">Register</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
