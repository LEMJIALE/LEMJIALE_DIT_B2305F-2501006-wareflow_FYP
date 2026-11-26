<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function is_logged_in() { return isset($_SESSION['user']); }
function require_login() { if (!is_logged_in()) { header('Location: /warehouse-maintenance/login.php'); exit; } }
function require_roles($roles) { require_login(); if (!in_array($_SESSION['user']['role'], (array)$roles, true)) { http_response_code(403); exit('Access denied.'); } }
function is_system_admin() { return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'Admin'; }
function require_system_admin() { require_login(); if (!is_system_admin()) { http_response_code(403); exit('Access denied. This page is only available to Admin users.'); } }
function can_access_clothing_warehouse() { return is_logged_in() && ($_SESSION['user']['role'] ?? '') !== 'Maintenance Staff'; }
function require_clothing_warehouse_access() { require_login(); if (!can_access_clothing_warehouse()) { http_response_code(403); exit('Access denied. Clothing Warehouse is not available to Maintenance Staff.'); } }
function can_submit_requests() { return is_system_admin() || (is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'Warehouse Staff'); }
function require_request_access() { require_login(); if (!can_submit_requests()) { http_response_code(403); exit('Access denied. Maintenance requests can only be submitted by Warehouse Staff or the system administrator.'); } }
function csrf_token() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) exit('Invalid request token.'); }
function flash($message = null) { if ($message !== null) $_SESSION['flash'] = $message; $m = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return $m; }
