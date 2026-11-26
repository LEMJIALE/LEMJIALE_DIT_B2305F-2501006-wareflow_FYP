<?php require_once __DIR__ . '/includes/auth.php'; header('Location: ' . (is_logged_in() ? '/warehouse-maintenance/dashboard.php' : '/warehouse-maintenance/login.php')); exit;
