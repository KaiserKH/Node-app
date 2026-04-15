<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

$user = current_user();
$canEdit = true;

$roleFilter = (string) ($_GET['role'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));

$allowedRoles = ['user', 'manager', 'admin'];
$params = [];
$conditions = [];

if (in_array($roleFilter, $allowedRoles, true)) {
  $conditions[] = 'role = :role';
  $params['role'] = $roleFilter;
}

if ($q !== '') {
  $conditions[] = '(name LIKE :q OR email LIKE :q)';
  $params['q'] = '%' . $q . '%';
}

$sql = 'SELECT id, name, email, role, updated_at FROM users';
if ($conditions) {
  $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = APP_NAME . ' - Manage Users';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
  <h2>User Directory</h2>
  <form method="get" class="grid">
    <div>
      <label for="role">Filter by role</label>
      <select id="role" name="role">
        <option value="">All roles</option>
        <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
        <option value="manager" <?= $roleFilter === 'manager' ? 'selected' : '' ?>>Manager</option>
        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </div>
    <div>
      <label for="q">Search name/email</label>
      <input id="q" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Search users...">
    </div>
    <div>
      <label>&nbsp;</label>
      <button type="submit">Apply Filters</button>
    </div>
  </form>

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
