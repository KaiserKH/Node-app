<?php

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}

if (!isset($basePath)) {
    $basePath = '';
}

$user = current_user();
$flash = get_flash();

function app_link(string $basePath, string $path): string
{
    return $basePath . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Supermercado+One&family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(app_link($basePath, 'assets/css/style.css')) ?>">
</head>
<body>
<header class="hero">
  <h1><?= e(APP_NAME) ?></h1>
  <p class="tagline">A rural system platform (RSP)</p>

  <div class="acc">
    <?php if ($user): ?>
      <a href="<?= e(app_link($basePath, 'dashboard.php')) ?>">Dashboard</a>
      <a href="<?= e(app_link($basePath, 'profile.php')) ?>">My Profile</a>
      <a href="<?= e(app_link($basePath, 'logout.php')) ?>">Logout</a>
    <?php else: ?>
      <a href="<?= e(app_link($basePath, 'register.php')) ?>">Registration</a>
      <a href="<?= e(app_link($basePath, 'login.php')) ?>">Login</a>
    <?php endif; ?>
  </div>
</header>

<nav>
  <ul>
    <li><a href="<?= e(app_link($basePath, 'index.php')) ?>">Home</a></li>
    <li><a href="<?= e(app_link($basePath, 'complaint_new.php')) ?>">Raise Complaint</a></li>
    <li><a href="<?= e(app_link($basePath, 'complaints.php')) ?>">View Complaint</a></li>
    <li><a href="<?= e(app_link($basePath, 'jobs.php')) ?>">Jobs</a></li>
    <li><a href="<?= e(app_link($basePath, 'schemes.php')) ?>">Scheme</a></li>
    <li><a href="<?= e(app_link($basePath, 'upload.php')) ?>">Media</a></li>
    <?php if ($user && in_array($user['role'], ['admin', 'manager'], true)): ?>
      <li><a href="<?= e(app_link($basePath, 'admin/index.php')) ?>">Admin</a></li>
    <?php endif; ?>
  </ul>
</nav>

<main class="container">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
  <?php endif; ?>
