<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = APP_NAME . ' - Home';
require __DIR__ . '/includes/header.php';
?>

<section class="grid">
  <article class="card">
    <h2>Welcome</h2>
    <p>Village Connect SRP connects citizens, local officials, and management teams on one secure platform.</p>
    <p>Register, raise complaints, track public jobs, and browse village schemes with responsive access on mobile and desktop.</p>
  </article>

  <article class="card">
    <h2>Core Features</h2>
    <p>User profile with image/video/audio uploads.</p>
    <p>Complaint lifecycle with status tracking.</p>
    <p>Role-based admin controls and audit trail for edits.</p>
  </article>

  <article class="card">
    <h2>Security</h2>
    <p>Password hashing with <strong>password_hash</strong>, CSRF protection, role authorization, secure sessions, and upload validation.</p>
  </article>
</section>

<?php require __DIR__ . '/includes/footer.php';
