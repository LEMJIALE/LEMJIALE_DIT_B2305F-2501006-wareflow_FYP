<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $language = $_POST['language'] ?? 'en';
    if (in_array($language, ['en', 'zh'], true)) {
        $_SESSION['language'] = $language;
    }
}

$returnTo = $_POST['return_to'] ?? '/warehouse-maintenance/dashboard.php';
if (!str_starts_with($returnTo, '/warehouse-maintenance/')) {
    $returnTo = '/warehouse-maintenance/dashboard.php';
}
header('Location: ' . $returnTo);
exit;
