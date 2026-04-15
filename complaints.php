<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$isManager = in_array($user['role'], ['admin', 'manager'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isManager) {
    verify_csrf_or_fail();

    $complaintId = max(1, (int) ($_POST['complaint_id'] ?? 0));
    $status = (string) ($_POST['status'] ?? 'open');

    $validStatus = ['open', 'in_progress', 'closed'];
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
            JOIN users u ON u.id = c.user_id
            ORDER BY c.id DESC';
    $rows = db()->query($sql)->fetchAll();
} else {
    $stmt = db()->prepare('SELECT id, title, description, status, created_at FROM complaints WHERE user_id = :id ORDER BY id DESC');
    $stmt->execute(['id' => $user['id']]);
    $rows = $stmt->fetchAll();
}

$pageTitle = APP_NAME . ' - Complaints';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Complaint List</h2>
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
