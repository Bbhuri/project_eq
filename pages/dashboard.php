<?php
// pages/dashboard.php
require_once __DIR__ . '/../config/db.php';
$user = $_SESSION['user'];
$roles = [
    'ADMIN' => 'ผู้ดูแลระบบ',
    'DIRECTOR' => 'หัวหน้าเจ้าหน้าที่',
    'OPERATOR' => 'เจ้าหน้าที่',
    'USER' => 'กำลังพลทั่วไป'
];

$role = $user['u_role'] ?? '';
?>
<h3>สวัสดี <?=htmlspecialchars($user['u_name'])?></h3>
<p>Role: <?= htmlspecialchars($roles[$role] ?? 'ไม่ทราบสิทธิ') ?></p>

<div class="row">
  <div class="col-md-4">
    <div class="card mb-3"><div class="card-body">
      <h5>รายการอุปกรณ์</h5>
      <a href="index.php?page=equipment" class="btn btn-sm btn-primary">ไปดู</a>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card mb-3"><div class="card-body">
      <h5>ใบยืม-คืน</h5>
      <a href="index.php?page=borrow_list" class="btn btn-sm btn-primary">จัดการใบยืม</a>
    </div></div>
  </div>
  <?php if($roles[$role] === 'ผู้ดูแลระบบ'): ?>
  <div class="col-md-4">
    <div class="card mb-3"><div class="card-body">
      <h5>ผู้ใช้</h5>
      <a href="index.php?page=users" class="btn btn-sm btn-primary">จัดการผู้ใช้</a>
    </div></div>
  </div>
  <?php endif; ?>
</div>
