<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$admin = current_user();
$adminId = $admin['id'] ?? null;
$userId = max(1, (int) ($_GET['id'] ?? 0));

$stmt = db()->prepare('SELECT id, name, email, role, bio FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$target = $stmt->fetch();

if (!$target) {
    http_response_code(404);
    exit('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $role = (string) ($_POST['role'] ?? 'user');
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');

    if ($name === '' || $email === '') {
        set_flash('error', 'Name and email are required.');
        redirect('user_edit.php?id=' . $userId);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Invalid email format.');
        redirect('user_edit.php?id=' . $userId);
    }

    if (!in_array($role, ['user', 'manager', 'admin'], true)) {
        set_flash('error', 'Invalid role.');
        redirect('user_edit.php?id=' . $userId);
    }

    if ($newPassword !== '' && strlen($newPassword) < 8) {
        set_flash('error', 'Temporary password must be at least 8 characters.');
        redirect('user_edit.php?id=' . $userId);
    }

    $dup = db()->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
    $dup->execute(['email' => $email, 'id' => $userId]);
    if ($dup->fetch()) {
        set_flash('error', 'Email already in use by another user.');
        redirect('user_edit.php?id=' . $userId);
    }

    $changes = [];
    foreach (['name' => $name, 'email' => $email, 'role' => $role, 'bio' => $bio] as $field => $newValue) {
        $old = (string) ($target[$field] ?? '');
        if ($old !== (string) $newValue) {
            $changes[$field] = ['old' => $old, 'new' => (string) $newValue];
        }
    }

    if ($newPassword !== '') {
        $changes['password_hash'] = ['old' => '[hidden]', 'new' => '[reset by admin]'];
    }

    $sql = 'UPDATE users SET name = :name, email = :email, role = :role, bio = :bio, last_edited_by = :last_edited_by';
    $params = [
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'bio' => $bio,
        'last_edited_by' => $adminId,
        'id' => $userId,
    ];

    if ($newPassword !== '') {
        $sql .= ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';
    $update = db()->prepare($sql);
    $update->execute($params);

    if ($changes && $adminId !== null) {
        $audit = db()->prepare('INSERT INTO admin_edits (admin_id, user_id, field_name, old_value, new_value) VALUES (:admin_id, :user_id, :field_name, :old_value, :new_value)');
        foreach ($changes as $field => $change) {
            $audit->execute([
                'admin_id' => $adminId,
                'user_id' => $userId,
                'field_name' => $field,
                'old_value' => $change['old'],
                'new_value' => $change['new'],
            ]);
        }
    }

    set_flash('success', 'User updated. Watermark and audit log applied.');
    redirect('../profile.php?id=' . $userId);
}

$pageTitle = APP_NAME . ' - Edit User';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
  <h2>Edit User #<?= (int) $target['id'] ?></h2>
  <form method="post">
    <?= csrf_input() ?>

    <label for="name">Name</label>
    <input id="name" name="name" value="<?= e($target['name']) ?>" required maxlength="100">

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e($target['email']) ?>" required maxlength="190">

    <label for="role">Role</label>
    <select id="role" name="role">
      <option value="user" <?= $target['role'] === 'user' ? 'selected' : '' ?>>User</option>
      <option value="manager" <?= $target['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
      <option value="admin" <?= $target['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>

    <label for="bio">Bio</label>
    <textarea id="bio" name="bio" maxlength="1000"><?= e($target['bio'] ?? '') ?></textarea>

        <label for="new_password">Set Temporary Password (optional)</label>
        <input id="new_password" name="new_password" type="password" minlength="8" placeholder="Leave blank to keep existing password">

    <button type="submit">Save Changes</button>
  </form>
</section>

<?php require __DIR__ . '/../includes/footer.php';
