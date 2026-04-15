<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$stmtComplaints = db()->prepare('SELECT COUNT(*) AS c FROM complaints WHERE user_id = :id');
$stmtComplaints->execute(['id' => $user['id']]);
$myComplaintCount = (int) $stmtComplaints->fetch()['c'];

$stmtMedia = db()->prepare('SELECT COUNT(*) AS c FROM profile_media WHERE user_id = :id');
$stmtMedia->execute(['id' => $user['id']]);
$myMediaCount = (int) $stmtMedia->fetch()['c'];

$allUsers = 0;
$allComplaints = 0;
if (in_array($user['role'], ['admin', 'manager'], true)) {
    $allUsers = (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    $allComplaints = (int) db()->query('SELECT COUNT(*) AS c FROM complaints')->fetch()['c'];
}

$pageTitle = APP_NAME . ' - Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="grid">
  <article class="card">
    <h2>Hello, <?= e($user['name']) ?></h2>
    <p><span class="role-pill"><?= e(strtoupper($user['role'])) ?></span></p>
    <p>This is your personal command center for profile, complaints, and media uploads.</p>
  </article>

  <article class="card">
    <h2>Your Stats</h2>
    <p>Complaints raised: <strong><?= $myComplaintCount ?></strong></p>
    <p>Media uploaded: <strong><?= $myMediaCount ?></strong></p>
    <a class="btn secondary" href="profile.php">Update Profile / Change Password</a>
  </article>

  <?php if (in_array($user['role'], ['admin', 'manager'], true)): ?>
    <article class="card">
      <h2>Management Stats</h2>
      <p>Total users: <strong><?= $allUsers ?></strong></p>
      <p>Total complaints: <strong><?= $allComplaints ?></strong></p>
      <?php if ($user['role'] === 'admin'): ?>
        <a class="btn" href="admin/index.php">Open Admin Panel</a>
      <?php endif; ?>
      <a class="btn secondary" href="complaints.php?status=open">Review Open Complaints</a>
    </article>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
