<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = $_SESSION['user'];
$roles = [
    'ADMIN' => 'ผู้ดูแลระบบ',
    'DIRECTOR' => 'หัวหน้าเจ้าหน้าที่',
    'OPERATOR' => 'เจ้าหน้าที่',
    'USER' => 'กำลังพลทั่วไป'
];

$role = $user['u_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ระบบยืม-คืนวัสดุและอุปกรณ์</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">ระบบยืม-คืน</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard">หน้าแรก</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=equipment">รายการอุปกรณ์</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php?page=borrow_list">ใบยืม-คืน</a></li>
        <?php if(isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['เจ้าหน้าที่','หัวหน้าเจ้าหน้าที่'])): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?page=borrow_approve">อนุมัติการยืม</a></li>
        <?php endif; ?>
        <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'ผู้ดูแลระบบ'): ?>
          <li class="nav-item"><a class="nav-link" href="index.php?page=users">ผู้ใช้</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center text-white">
        <?php if(isset($_SESSION['user'])): ?>
          <div class="me-3">👤 <?=htmlspecialchars($_SESSION['user']['u_name'])?> (<?=htmlspecialchars($roles[$role] ?? 'ไม่ทราบสิทธิ')?>)</div>
          <a class="btn btn-outline-light btn-sm" href="actions/logout.php">ออกจากระบบ</a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="pages/login.php">เข้าสู่ระบบ</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<div class="container mt-4">
