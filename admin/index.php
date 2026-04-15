<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$user = current_user();
$displayName = $user['name'] ?? 'Guest';

$stats = [
  'users' => (int) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'],
  'complaints' => (int) db()->query('SELECT COUNT(*) AS c FROM complaints')->fetch()['c'],
  'jobs' => (int) db()->query('SELECT COUNT(*) AS c FROM jobs')->fetch()['c'],
  'schemes' => (int) db()->query('SELECT COUNT(*) AS c FROM schemes')->fetch()['c'],
];

$recentComplaints = db()->query('SELECT c.id, c.title, c.status, c.updated_at, u.name AS user_name
                 FROM complaints c
                 JOIN users u ON u.id = c.user_id
                 ORDER BY c.updated_at DESC
                 LIMIT 6')->fetchAll();

$recentPosts = db()->query('SELECT id, title, "job" AS post_type, is_active, created_at FROM jobs
              UNION ALL
              SELECT id, title, "scheme" AS post_type, is_active, created_at FROM schemes
              ORDER BY created_at DESC
              LIMIT 8')->fetchAll();

$pageTitle = APP_NAME . ' - Admin Panel';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid">
  <article class="card">
    <h2>Admin Center</h2>
    <p>Welcome <?= e($displayName) ?>. Use this panel to manage users, complaints, teams, jobs, and scheme updates.</p>
    <p><strong>Logo:</strong> Village Connect SRP Admin</p>
  </article>

  <article class="card">
    <h2>System Stats</h2>
    <p>Total users: <strong><?= $stats['users'] ?></strong></p>
    <p>Total complaints: <strong><?= $stats['complaints'] ?></strong></p>
    <p>Total jobs: <strong><?= $stats['jobs'] ?></strong></p>
    <p>Total schemes: <strong><?= $stats['schemes'] ?></strong></p>
  </article>

  <article class="card">
    <h2>User & Team Controls</h2>
    <p>View profile details, edit users, manage roles, and assign team responsibilities.</p>
    <a class="btn" href="users.php">Manage Users</a>
    <a class="btn secondary" href="teams.php">Manage Teams</a>
  </article>

  <article class="card">
    <h2>Content & Complaints</h2>
    <p>Review public posts or updates and take action.</p>
    <a class="btn" href="../complaints.php?status=open">Open Complaints</a>
    <a class="btn secondary" href="../jobs.php?scope=all">Manage Jobs</a>
    <a class="btn secondary" href="../schemes.php?scope=all">Manage Schemes</a>
  </article>
</section>

<section class="card">
  <h2>Recent Complaint Updates</h2>
  <?php if (!$recentComplaints): ?>
    <p>No complaint updates found.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Title</th>
            <th>Status</th>
            <th>Updated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentComplaints as $row): ?>
            <tr>
              <td><?= (int) $row['id'] ?></td>
              <td><?= e($row['user_name']) ?></td>
              <td><?= e($row['title']) ?></td>
              <td><?= e($row['status']) ?></td>
              <td><?= e($row['updated_at']) ?></td>
              <td><a class="btn secondary" href="../complaints.php?status=<?= e($row['status']) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Recent Posts / Updates</h2>
  <?php if (!$recentPosts): ?>
    <p>No jobs or schemes available yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Title</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentPosts as $post): ?>
            <tr>
              <td><?= e($post['post_type']) ?></td>
              <td><?= e($post['title']) ?></td>
              <td><?= (int) $post['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
              <td><?= e($post['created_at']) ?></td>
              <td>
                <?php if ($post['post_type'] === 'job'): ?>
                  <a class="btn secondary" href="../jobs.php?scope=all">Manage Job</a>
                <?php else: ?>
                  <a class="btn secondary" href="../schemes.php?scope=all">Manage Scheme</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php';
