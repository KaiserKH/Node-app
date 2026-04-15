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
        redirect('schemes.php');
    }

    $stmt = db()->prepare('INSERT INTO schemes (title, description, is_active) VALUES (:title, :description, :is_active)');
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'is_active' => $active,
    ]);

    set_flash('success', 'Scheme created successfully.');
    redirect('schemes.php');
}

if ($isManager) {
    $rows = db()->query('SELECT id, title, description, is_active, created_at FROM schemes ORDER BY id DESC')->fetchAll();
} else {
    $rows = db()->query('SELECT id, title, description, is_active, created_at FROM schemes WHERE is_active = 1 ORDER BY id DESC')->fetchAll();
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
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['description']) ?></td>
            <td><?= ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive' ?></td>
            <td><?= e($row['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
