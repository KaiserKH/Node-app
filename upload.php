<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    if (empty($_FILES['media']['name'])) {
        set_flash('error', 'Please choose a file.');
        redirect('upload.php');
    }

    $title = trim((string) ($_POST['title'] ?? ''));

    $upload = save_media_upload($_FILES['media']);
    if (!$upload['ok']) {
        set_flash('error', $upload['error']);
        redirect('upload.php');
    }

    $stmt = db()->prepare('INSERT INTO profile_media (user_id, file_path, media_type, title) VALUES (:user_id, :file_path, :media_type, :title)');
    $stmt->execute([
        'user_id' => $user['id'],
        'file_path' => $upload['path'],
        'media_type' => $upload['media_type'],
        'title' => $title,
    ]);

    set_flash('success', 'Media uploaded successfully.');
    redirect('upload.php');
}

$stmt = db()->prepare('SELECT id, file_path, media_type, title, uploaded_at FROM profile_media WHERE user_id = :id ORDER BY id DESC');
$stmt->execute(['id' => $user['id']]);
$items = $stmt->fetchAll();

$pageTitle = APP_NAME . ' - Upload Media';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
  <h2>Upload Image, Video, or Audio</h2>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_input() ?>
    <label for="title">Title (optional)</label>
    <input id="title" name="title" maxlength="120">

    <label for="media">Choose file</label>
    <input id="media" name="media" type="file" accept="image/*,video/*,audio/*" required>

    <button type="submit">Upload</button>
  </form>
</section>

<section class="card">
  <h2>My Media Library</h2>
  <?php if (!$items): ?>
    <p>No media uploaded yet.</p>
  <?php else: ?>
    <div class="media-grid">
      <?php foreach ($items as $item): ?>
        <article class="media-item">
          <?php if ($item['media_type'] === 'image'): ?>
            <img src="<?= e($item['file_path']) ?>" alt="Media">
          <?php elseif ($item['media_type'] === 'video'): ?>
            <video controls src="<?= e($item['file_path']) ?>"></video>
          <?php else: ?>
            <audio controls src="<?= e($item['file_path']) ?>"></audio>
          <?php endif; ?>
          <p><strong><?= e($item['title'] ?: ucfirst($item['media_type'])) ?></strong></p>
          <p class="meta"><?= e($item['uploaded_at']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
