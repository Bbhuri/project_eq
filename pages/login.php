<?php
// pages/login.php
session_start();
if (isset($_SESSION['user'])) header("Location: ../index.php");
?>
<!DOCTYPE html>
<html lang="th">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>เข้าสู่ระบบ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow">
        <div class="card-body">
          <h4 class="text-center mb-3">ระบบยืม-คืนวัสดุและอุปกรณ์คอมพิวเตอร์</h4>
          <form method="post" action="../actions/login_action.php">
            <div class="mb-2"><input class="form-control" name="username" placeholder="Username" required></div>
            <div class="mb-2"><input class="form-control" type="password" name="password" placeholder="Password" required></div>
            <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
          </form>
          
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
