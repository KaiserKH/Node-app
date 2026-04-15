<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($title === '' || $description === '') {
        set_flash('error', 'Title and description are required.');
        redirect('complaint_new.php');
    }

    $stmt = db()->prepare('INSERT INTO complaints (user_id, title, description, status) VALUES (:user_id, :title, :description, :status)');
    $stmt->execute([
        'user_id' => $user['id'],
        'title' => $title,
        'description' => $description,
        'status' => 'open',
    ]);

    set_flash('success', 'Complaint submitted successfully.');
    redirect('complaints.php');
}

$pageTitle = APP_NAME . ' - Raise Complaint';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Raise Complaint</h2>
  <form method="post">
    <?= csrf_input() ?>
    <label for="title">Title</label>
    <input id="title" name="title" maxlength="190" required>

    <label for="description">Description</label>
    <textarea id="description" name="description" maxlength="4000" required></textarea>

    <button type="submit">Submit Complaint</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
