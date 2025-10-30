<?php
// index.php
session_start();
require_once __DIR__ . '/includes/auth.php';

// If no session -> send to login page
if (!current_user()){
    header("Location: pages/login.php");
    exit;
}

include __DIR__ . '/includes/header.php';

$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','equipment','users','borrow_list','borrow_approve'];
if (!in_array($page, $allowed)){
    echo "<div class='alert alert-warning'>หน้านี้ไม่พบ</div>";
} else {
    include __DIR__ . "/pages/{$page}.php";
}

include __DIR__ . '/includes/footer.php';
?>
