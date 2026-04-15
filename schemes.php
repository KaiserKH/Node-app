<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$isManager = $user && in_array($user['role'], ['admin', 'manager'], true);
$scope = (string) ($_GET['scope'] ?? 'active');
$q = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isManager) {
    verify_csrf_or_fail();

  $action = (string) ($_POST['action'] ?? 'create');

  if ($action === 'toggle_status') {
    $id = max(1, (int) ($_POST['id'] ?? 0));
    $newStatus = isset($_POST['is_active']) ? 1 : 0;

    $stmt = db()->prepare('UPDATE schemes SET is_active = :is_active WHERE id = :id');
    $stmt->execute([
      'is_active' => $newStatus,
      'id' => $id,
    ]);

    set_flash('success', 'Scheme status updated.');
    redirect('schemes.php?scope=all');
  }

  if ($action === 'delete') {
    $id = max(1, (int) ($_POST['id'] ?? 0));
    $stmt = db()->prepare('DELETE FROM schemes WHERE id = :id');
    $stmt->execute(['id' => $id]);

    set_flash('success', 'Scheme deleted.');
    redirect('schemes.php?scope=all');
  }

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '' || $description === '') {
        set_flash('error', 'Title and description are required.');
        redirect('schemes.php');
    }

    $stmt = db()->prepare('INSERT INTO schemes (title, description, is_active) VALUES (:title, :description, :is_active)');
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'is_active' => $active,
    ]);

    set_flash('success', 'Scheme created successfully.');
    redirect('schemes.php?scope=all');
}

if ($isManager) {
    $sql = 'SELECT id, title, description, is_active, created_at FROM schemes';
    $conditions = [];
    $params = [];

    if ($scope === 'active') {
      $conditions[] = 'is_active = 1';
    } elseif ($scope === 'inactive') {
      $conditions[] = 'is_active = 0';
    }

    if ($q !== '') {
      $conditions[] = '(title LIKE :q OR description LIKE :q)';
      $params['q'] = '%' . $q . '%';
    }

    if ($conditions) {
      $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} else {
    $sql = 'SELECT id, title, description, is_active, created_at FROM schemes WHERE is_active = 1';
    $params = [];
    if ($q !== '') {
      $sql .= ' AND (title LIKE :q OR description LIKE :q)';
      $params['q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

$pageTitle = APP_NAME . ' - Schemes';
require __DIR__ . '/includes/header.php';
?>

<?php if ($isManager): ?>
<section class="card">
  <h2>Add Government Scheme</h2>
  <form method="post">
    <?= csrf_input() ?>
    <label for="title">Title</label>
    <input id="title" name="title" maxlength="190" required>

    <label for="description">Description</label>
    <textarea id="description" name="description" maxlength="4000" required></textarea>

    <label><input type="checkbox" name="is_active" checked> Active</label>

    <button type="submit">Save Scheme</button>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <h2>Available Schemes</h2>
  <form method="get" class="grid">
    <?php if ($isManager): ?>
      <div>
        <label for="scope">Scope</label>
        <select id="scope" name="scope">
          <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>All</option>
          <option value="active" <?= $scope === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $scope === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    <?php endif; ?>
    <div>
      <label for="q">Search</label>
      <input id="q" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Search schemes...">
    </div>
    <div>
      <label>&nbsp;</label>
      <button type="submit">Apply Filters</button>
    </div>
  </form>

  <?php if (!$rows): ?>
    <p>No scheme published yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Status</th>
            <th>Date</th>
            <?php if ($isManager): ?><th>Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['description']) ?></td>
            <td><?= ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive' ?></td>
            <td><?= e($row['created_at']) ?></td>
            <?php if ($isManager): ?>
              <td>
                <form method="post">
                  <?= csrf_input() ?>
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <label>
                    <input type="checkbox" name="is_active" <?= (int) $row['is_active'] === 1 ? 'checked' : '' ?>> Active
                  </label>
                  <button type="submit" class="btn secondary">Update</button>
                </form>
                <form method="post">
                  <?= csrf_input() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button type="submit" class="btn warning">Delete</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
