<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$isManager = in_array($user['role'], ['admin', 'manager'], true);

$statusFilter = (string) ($_GET['status'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$validStatus = ['open', 'in_progress', 'closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isManager) {
    verify_csrf_or_fail();

    $complaintId = max(1, (int) ($_POST['complaint_id'] ?? 0));
    $status = (string) ($_POST['status'] ?? 'open');

    if (!in_array($status, $validStatus, true)) {
        set_flash('error', 'Invalid complaint status.');
        redirect('complaints.php');
    }

    $stmt = db()->prepare('UPDATE complaints SET status = :status, resolved_by = :resolved_by WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'resolved_by' => $status === 'closed' ? $user['id'] : null,
        'id' => $complaintId,
    ]);

    set_flash('success', 'Complaint status updated.');
    redirect('complaints.php');
}

if ($isManager) {
  $sql = 'SELECT c.id, c.title, c.description, c.status, c.created_at, u.name AS user_name
      FROM complaints c
      JOIN users u ON u.id = c.user_id';
  $params = [];
  $conditions = [];

  if (in_array($statusFilter, $validStatus, true)) {
    $conditions[] = 'c.status = :status';
    $params['status'] = $statusFilter;
  }

  if ($q !== '') {
    $conditions[] = '(c.title LIKE :q OR c.description LIKE :q OR u.name LIKE :q)';
    $params['q'] = '%' . $q . '%';
  }

  if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
  }

  $sql .= ' ORDER BY c.id DESC';
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} else {
  $sql = 'SELECT id, title, description, status, created_at FROM complaints WHERE user_id = :id';
  $params = ['id' => $user['id']];

  if (in_array($statusFilter, $validStatus, true)) {
    $sql .= ' AND status = :status';
    $params['status'] = $statusFilter;
  }

  if ($q !== '') {
    $sql .= ' AND (title LIKE :q OR description LIKE :q)';
    $params['q'] = '%' . $q . '%';
  }

  $sql .= ' ORDER BY id DESC';
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

$pageTitle = APP_NAME . ' - Complaints';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Complaint List</h2>
  <form method="get" class="grid">
    <div>
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">All</option>
        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
      </select>
    </div>
    <div>
      <label for="q">Search</label>
      <input id="q" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Search complaints...">
    </div>
    <div>
      <label>&nbsp;</label>
      <button type="submit">Apply Filters</button>
    </div>
  </form>

  <?php if (!$rows): ?>
    <p>No complaints found.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <?php if ($isManager): ?><th>User</th><?php endif; ?>
            <th>Title</th>
            <th>Description</th>
            <th>Status</th>
            <th>Created</th>
            <?php if ($isManager): ?><th>Update</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= (int) $row['id'] ?></td>
              <?php if ($isManager): ?><td><?= e($row['user_name']) ?></td><?php endif; ?>
              <td><?= e($row['title']) ?></td>
              <td><?= e($row['description']) ?></td>
              <td><?= e($row['status']) ?></td>
              <td><?= e($row['created_at']) ?></td>
              <?php if ($isManager): ?>
                <td>
                  <form method="post">
                    <?= csrf_input() ?>
                    <input type="hidden" name="complaint_id" value="<?= (int) $row['id'] ?>">
                    <select name="status">
                      <option value="open" <?= $row['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                      <option value="in_progress" <?= $row['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                      <option value="closed" <?= $row['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                    <button type="submit">Save</button>
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
