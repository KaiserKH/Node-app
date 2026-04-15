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
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $village = trim((string) ($_POST['village'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $gender = (string) ($_POST['gender'] ?? 'prefer_not_to_say');
    $dateOfBirth = trim((string) ($_POST['date_of_birth'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $village === '' || $address === '' || $dateOfBirth === '' || $password === '' || $confirmPassword === '') {
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

    if (!is_valid_phone($phone)) {
        set_flash('error', 'Invalid phone number format.');
        redirect('register.php');
    }

    if (!in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say'], true)) {
        set_flash('error', 'Invalid gender selection.');
        redirect('register.php');
    }

    $dobTs = strtotime($dateOfBirth);
    if ($dobTs === false || $dobTs > time()) {
        set_flash('error', 'Date of birth is invalid.');
        redirect('register.php');
    }

    if ($password !== $confirmPassword) {
        set_flash('error', 'Password and confirm password do not match.');
        redirect('register.php');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        set_flash('error', 'Email already exists.');
        redirect('register.php');
    }

    $insert = db()->prepare('INSERT INTO users (name, email, phone, village, address, gender, date_of_birth, password_hash, role) VALUES (:name, :email, :phone, :village, :address, :gender, :date_of_birth, :password_hash, :role)');
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'village' => $village,
        'address' => $address,
        'gender' => $gender,
        'date_of_birth' => date('Y-m-d', $dobTs),
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

        <label for="phone">Phone</label>
        <input id="phone" name="phone" required maxlength="20" placeholder="e.g. +91 9876543210">

        <label for="village">Village</label>
        <input id="village" name="village" required maxlength="120">

        <label for="address">Address</label>
        <textarea id="address" name="address" required maxlength="2000"></textarea>

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
            <option value="prefer_not_to_say" selected>Prefer not to say</option>
        </select>

        <label for="date_of_birth">Date of Birth</label>
        <input id="date_of_birth" name="date_of_birth" type="date" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" minlength="8" required>

        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>

    <button type="submit">Register</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
