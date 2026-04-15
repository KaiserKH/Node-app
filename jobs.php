<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$isManager = $user && in_array($user['role'], ['admin', 'manager'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isManager) {
    verify_csrf_or_fail();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '' || $description === '') {
        set_flash('error', 'Title and description are required.');
        redirect('jobs.php');
    }

    $stmt = db()->prepare('INSERT INTO jobs (title, description, posted_by, is_active) VALUES (:title, :description, :posted_by, :is_active)');
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'posted_by' => $user['id'],
        'is_active' => $active,
    ]);

    set_flash('success', 'Job posted successfully.');
    redirect('jobs.php');
}

if ($isManager) {
    $rows = db()->query('SELECT j.id, j.title, j.description, j.is_active, j.created_at, u.name AS posted_by_name FROM jobs j LEFT JOIN users u ON u.id = j.posted_by ORDER BY j.id DESC')->fetchAll();
} else {
    $rows = db()->query('SELECT id, title, description, is_active, created_at FROM jobs WHERE is_active = 1 ORDER BY id DESC')->fetchAll();
}

$pageTitle = APP_NAME . ' - Jobs';
require __DIR__ . '/includes/header.php';
?>

<?php if ($isManager): ?>
<section class="card">
  <h2>Post New Job</h2>
  <form method="post">
    <?= csrf_input() ?>
    <label for="title">Title</label>
    <input id="title" name="title" maxlength="190" required>

    <label for="description">Description</label>
    <textarea id="description" name="description" maxlength="4000" required></textarea>

    <label><input type="checkbox" name="is_active" checked> Active</label>

    <button type="submit">Publish Job</button>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <h2>Jobs Board</h2>
  <?php if (!$rows): ?>
    <p>No jobs available right now.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Status</th>
            <th>Date</th>
            <?php if ($isManager): ?><th>Posted By</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['description']) ?></td>
            <td><?= ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive' ?></td>
            <td><?= e($row['created_at']) ?></td>
            <?php if ($isManager): ?><td><?= e($row['posted_by_name'] ?? '-') ?></td><?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
