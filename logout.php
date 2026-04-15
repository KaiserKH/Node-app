<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    logout_user();
}

redirect('login.php');
