<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
// require_admin_or_manager();

$user = current_user();
$displayName = $user['name'] ?? 'Guest';
$pageTitle = APP_NAME . ' - Admin Panel';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid">
  <article class="card">
    <h2>Admin Center</h2>
    <p>Welcome <?= e($displayName) ?>. Use the panel to manage users, teams, and operational content.</p>
  </article>

  <article class="card">
    <h2>Users</h2>
    <p>View all user profiles and apply administrative corrections with audit watermark.</p>
    <a class="btn" href="users.php">Open Users</a>
  </article>

  <article class="card">
    <h2>Management Teams</h2>
    <p>Create and maintain task-oriented teams and assign members.</p>
    <a class="btn secondary" href="teams.php">Open Teams</a>
  </article>

  <article class="card">
    <h2>Complaints</h2>
    <p>Track and update complaint statuses.</p>
    <a class="btn" href="../complaints.php">Go to Complaints</a>
  </article>
</section>

<?php require __DIR__ . '/../includes/footer.php';
