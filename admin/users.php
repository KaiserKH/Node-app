<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_admin_or_manager();

$user = current_user();
$canEdit = $user['role'] === 'admin';

$rows = db()->query('SELECT id, name, email, role, updated_at FROM users ORDER BY id DESC')->fetchAll();

$pageTitle = APP_NAME . ' - Manage Users';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
  <h2>User Directory</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= (int) $row['id'] ?></td>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['email']) ?></td>
            <td><span class="role-pill"><?= e($row['role']) ?></span></td>
            <td><?= e($row['updated_at']) ?></td>
            <td>
              <a class="btn secondary" href="../profile.php?id=<?= (int) $row['id'] ?>">View</a>
              <?php if ($canEdit): ?>
                <a class="btn warning" href="user_edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php';
