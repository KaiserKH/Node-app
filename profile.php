<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$viewer = current_user();
$targetUserId = $viewer['id'];

if (isset($_GET['id']) && in_array($viewer['role'], ['admin', 'manager'], true)) {
    $targetUserId = max(1, (int) $_GET['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $targetUserId === (int) $viewer['id']) {
    verify_csrf_or_fail();

  $action = (string) ($_POST['action'] ?? 'update_profile');

  if ($action === 'change_password') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
      set_flash('error', 'All password fields are required.');
      redirect('profile.php');
    }

    if (strlen($newPassword) < 8) {
      set_flash('error', 'New password must be at least 8 characters.');
      redirect('profile.php');
    }

    if ($newPassword !== $confirmPassword) {
      set_flash('error', 'New password and confirm password must match.');
      redirect('profile.php');
    }

    $stmtPass = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $stmtPass->execute(['id' => $viewer['id']]);
    $passRow = $stmtPass->fetch();

    if (!$passRow || !password_verify($currentPassword, $passRow['password_hash'])) {
      set_flash('error', 'Current password is incorrect.');
      redirect('profile.php');
    }

    $stmtUpdatePass = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmtUpdatePass->execute([
      'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
      'id' => $viewer['id'],
    ]);

    set_flash('success', 'Password changed successfully.');
    redirect('profile.php');
  }

    $name = trim((string) ($_POST['name'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $village = trim((string) ($_POST['village'] ?? ''));
  $address = trim((string) ($_POST['address'] ?? ''));

    if ($name === '') {
        set_flash('error', 'Name is required.');
        redirect('profile.php');
    }

  if ($phone !== '' && !is_valid_phone($phone)) {
    set_flash('error', 'Invalid phone number format.');
    redirect('profile.php');
  }

    $avatarPath = null;
    if (!empty($_FILES['avatar']['name'])) {
        $uploaded = save_media_upload($_FILES['avatar']);
        if (!$uploaded['ok']) {
            set_flash('error', $uploaded['error']);
            redirect('profile.php');
        }

        if ($uploaded['media_type'] !== 'image') {
            set_flash('error', 'Avatar must be an image.');
            redirect('profile.php');
        }

        $avatarPath = $uploaded['path'];
    }

    $sql = 'UPDATE users SET name = :name, bio = :bio, phone = :phone, village = :village, address = :address';
    $params = [
        'name' => $name,
        'bio' => $bio,
      'phone' => $phone !== '' ? $phone : null,
      'village' => $village !== '' ? $village : null,
      'address' => $address !== '' ? $address : null,
        'id' => $viewer['id'],
    ];

    if ($avatarPath !== null) {
        $sql .= ', avatar_path = :avatar_path';
        $params['avatar_path'] = $avatarPath;
    }

    $sql .= ' WHERE id = :id';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    set_flash('success', 'Profile updated successfully.');
    redirect('profile.php');
}

$stmtUser = db()->prepare('SELECT u.id, u.name, u.email, u.phone, u.village, u.address, u.gender, u.date_of_birth, u.role, u.bio, u.avatar_path, u.last_edited_by, u.updated_at, editor.name AS editor_name
                          FROM users u
                          LEFT JOIN users editor ON editor.id = u.last_edited_by
                          WHERE u.id = :id LIMIT 1');
$stmtUser->execute(['id' => $targetUserId]);
$targetUser = $stmtUser->fetch();

if (!$targetUser) {
    http_response_code(404);
    exit('User not found.');
}

$stmtMedia = db()->prepare('SELECT id, file_path, media_type, title, uploaded_at FROM profile_media WHERE user_id = :id ORDER BY id DESC');
$stmtMedia->execute(['id' => $targetUser['id']]);
$mediaItems = $stmtMedia->fetchAll();

$pageTitle = APP_NAME . ' - Profile';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>User Profile</h2>
  <div class="profile-box">
    <div>
      <?php if (!empty($targetUser['avatar_path'])): ?>
        <img class="avatar" src="<?= e($targetUser['avatar_path']) ?>" alt="Avatar">
      <?php else: ?>
        <img class="avatar" src="https://placehold.co/110x110/png" alt="Avatar">
      <?php endif; ?>
    </div>
    <div>
      <p><strong><?= e($targetUser['name']) ?></strong></p>
      <p class="meta"><?= e($targetUser['email']) ?> | <?= e($targetUser['role']) ?></p>
      <p class="meta">Phone: <?= e($targetUser['phone'] ?? '-') ?> | Village: <?= e($targetUser['village'] ?? '-') ?></p>
      <p class="meta">Gender: <?= e($targetUser['gender'] ?? '-') ?> | DOB: <?= e($targetUser['date_of_birth'] ?? '-') ?></p>
      <p class="meta">Address: <?= e($targetUser['address'] ?? '-') ?></p>
      <p><?= e($targetUser['bio'] ?? '') ?></p>
      <?php if (!empty($targetUser['editor_name'])): ?>
        <p class="watermark">Edited by <?= e($targetUser['editor_name']) ?> on <?= e($targetUser['updated_at']) ?></p>
      <?php endif; ?>
      <?php if ((int) $targetUser['id'] !== (int) $viewer['id'] && in_array($viewer['role'], ['admin', 'manager'], true)): ?>
        <p><a class="btn warning" href="admin/user_edit.php?id=<?= (int) $targetUser['id'] ?>">Edit This User</a></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ((int) $targetUser['id'] === (int) $viewer['id']): ?>
  <section class="card">
    <h2>Edit My Profile</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="update_profile">

      <label for="name">Name</label>
      <input id="name" name="name" value="<?= e($targetUser['name']) ?>" maxlength="100" required>

      <label for="phone">Phone</label>
      <input id="phone" name="phone" value="<?= e($targetUser['phone'] ?? '') ?>" maxlength="20">

      <label for="village">Village</label>
      <input id="village" name="village" value="<?= e($targetUser['village'] ?? '') ?>" maxlength="120">

      <label for="address">Address</label>
      <textarea id="address" name="address" maxlength="2000"><?= e($targetUser['address'] ?? '') ?></textarea>

      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" maxlength="1000"><?= e($targetUser['bio'] ?? '') ?></textarea>

      <label for="avatar">Avatar Image</label>
      <input id="avatar" name="avatar" type="file" accept="image/*">

      <button type="submit">Save Profile</button>
    </form>
  </section>

  <section class="card">
    <h2>Change Password</h2>
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="change_password">

      <label for="current_password">Current Password</label>
      <input id="current_password" name="current_password" type="password" required>

      <label for="new_password">New Password</label>
      <input id="new_password" name="new_password" type="password" minlength="8" required>

      <label for="confirm_new_password">Confirm New Password</label>
      <input id="confirm_new_password" name="confirm_new_password" type="password" minlength="8" required>

      <button type="submit">Update Password</button>
    </form>
  </section>
<?php endif; ?>

<section class="card">
  <h2>Uploaded Media</h2>
  <?php if (!$mediaItems): ?>
    <p>No media uploaded yet.</p>
  <?php else: ?>
    <div class="media-grid">
      <?php foreach ($mediaItems as $item): ?>
        <article class="media-item">
          <?php if ($item['media_type'] === 'image'): ?>
            <img src="<?= e($item['file_path']) ?>" alt="Media">
          <?php elseif ($item['media_type'] === 'video'): ?>
            <video controls src="<?= e($item['file_path']) ?>"></video>
          <?php else: ?>
            <audio controls src="<?= e($item['file_path']) ?>"></audio>
          <?php endif; ?>
          <p><strong><?= e($item['title'] ?: ucfirst($item['media_type'])) ?></strong></p>
          <p class="meta"><?= e($item['uploaded_at']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
