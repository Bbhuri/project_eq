<?php
/*
  Simple Borrow-Return System (single-file demo)
  Filename: index.php
  - Uses mysqli (MySQL) — adapt connection to your DB
  - Minimal routing via ?page=  , AJAX via ?action=
*/

/* ========= DB CONNECT ========= */
$mysqli = new mysqli("localhost","root","","equipment");
if($mysqli->connect_errno){ die("DB Fail: ".$mysqli->connect_error); }
$mysqli->set_charset("utf8");

/* ========= SESSION ========= */
session_start();
function current_user(){ return $_SESSION['user']??null; }
function require_login(){ if(!current_user()){ header("Location:?page=login"); exit; } }

function safe($v){ return htmlspecialchars($v,ENT_QUOTES,'UTF-8'); }

/* ========= API ========= */
if(isset($_GET['action'])){
  header("Content-Type: application/json; charset=utf-8");
  $act=$_GET['action'];

  // Login
  if($act=='login'){
    $u=$_POST['username']??''; $p=$_POST['password']??'';
    $stmt=$mysqli->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss",$u,$p); $stmt->execute();
    $res=$stmt->get_result()->fetch_assoc();
    if($res){ $_SESSION['user']=$res; echo json_encode(['ok'=>true]); }
    else echo json_encode(['ok'=>false,'msg'=>'ชื่อผู้ใช้หรือรหัสผ่านผิด']);
    exit;
  }

  // Logout
  if($act=='logout'){
    session_unset();
    session_destroy();
    echo json_encode(['ok'=>true]);
    exit;
  }

  // เพิ่มอุปกรณ์
  if($act=='add_equipment'){
    $E_NAME=$_POST['E_NAME']; $E_TYPE_ID=$_POST['E_TYPE_ID']; $E_SN=$_POST['E_SN']; $STATUS=$_POST['STATUS']; $ADDRESS=$_POST['ADDRESS'];
    $stmt=$mysqli->prepare('INSERT INTO equipment (E_NAME,E_TYPE_ID,E_SN,STATUS,ADDRESS) VALUES (?,?,?,?,?)');
    $stmt->bind_param('sssss',$E_NAME,$E_TYPE_ID,$E_SN,$STATUS,$ADDRESS); $stmt->execute();
    echo json_encode(['ok'=>true,'id'=>$mysqli->insert_id]); exit;
  }

  // แก้ไขอุปกรณ์
  if($act=='edit_equipment'){
    $E_ID=(int)$_POST['E_ID']; $E_NAME=$_POST['E_NAME']; $E_TYPE_ID=$_POST['E_TYPE_ID']; $E_SN=$_POST['E_SN']; $STATUS=$_POST['STATUS']; $ADDRESS=$_POST['ADDRESS'];
    $stmt=$mysqli->prepare('UPDATE equipment SET E_NAME=?,E_TYPE_ID=?,E_SN=?,STATUS=?,ADDRESS=? WHERE E_ID=?');
    $stmt->bind_param('sssssi',$E_NAME,$E_TYPE_ID,$E_SN,$STATUS,$ADDRESS,$E_ID); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

// ลบอุปกรณ์
if($act=='del_equipment'){
    $E_ID = (int)$_POST['E_ID'];
    $stmt=$mysqli->prepare("DELETE FROM equipment WHERE E_ID=?");
    $stmt->bind_param("i",$E_ID); 
    $stmt->execute();
    echo json_encode(['ok'=>true]);
    exit;
}
  // เพิ่ม/แก้ผู้ใช้
  if($act=='save_user'){
    $u_id = $_POST['u_id'] ? (int)$_POST['u_id'] : 0;
    $username = $_POST['username']; $password = $_POST['password'] ?? '1234';
    $full_name = $_POST['full_name']; $role = $_POST['role'] ?: null;
    if($u_id>0){
      $stmt=$mysqli->prepare('UPDATE users SET username=?,password=?,full_name=?,role=? WHERE u_id=?');
      $stmt->bind_param('ssssi',$username,$password,$full_name,$role,$u_id); $stmt->execute();
      echo json_encode(['ok'=>true]); exit;
    } else {
      $stmt=$mysqli->prepare('INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)');
      $stmt->bind_param('ssss',$username,$password,$full_name,$role); $stmt->execute();
      echo json_encode(['ok'=>true,'id'=>$mysqli->insert_id]); exit;
    }
  }

  // ลบผู้ใช้
  if($act=='del_user'){
    $u_id=(int)$_POST['u_id'];
    $stmt=$mysqli->prepare('DELETE FROM users WHERE u_id=?'); $stmt->bind_param('i',$u_id); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

  // สร้างใบยืม
  if($act=='create_borrow'){
    $u = current_user();
    if(!$u){ echo json_encode(['ok'=>false,'msg'=>'ต้องล็อกอิน']); exit; }
    $u_phone = $_POST['u_phone']; $u_name = $_POST['u_name'];
    $b_borrow_date = $_POST['b_borrow_date']; $b_return_date = $_POST['b_return_date'];
    $B_NAME_officer = $_POST['B_NAME_officer']; $B_NAME_APPROVER = $_POST['B_NAME_APPROVER'];
    $b_status = 'รอเพิ่มอุปกรณ์';
    $stmt = $mysqli->prepare('INSERT INTO borrow_slip (u_phone,u_name,u_id,b_borrow_date,b_return_date,B_NAME_officer,B_NAME_APPROVER,b_status) VALUES (?,?,?,?,?,?,?,?)');
    $uid = $u['u_id'];
    $stmt->bind_param('ssisssss',$u_phone,$u_name,$uid,$b_borrow_date,$b_return_date,$B_NAME_officer,$B_NAME_APPROVER,$b_status);
    $stmt->execute();
    echo json_encode(['ok'=>true,'id'=>$mysqli->insert_id]); exit;
  }

  // อัพเดตใบยืม (แก้ไข) - เฉพาะสถานะที่อนุญาต
  if($act=='update_borrow'){
    $b_id=(int)$_POST['b_id'];
    $u = current_user(); if(!$u){ echo json_encode(['ok'=>false,'msg'=>'ต้องล็อกอิน']); exit; }
    // check status
    $row = $mysqli->query("SELECT b_status,u_id FROM borrow_slip WHERE b_id={$b_id}")->fetch_assoc();
    $allowed = ['รอเพิ่มอุปกรณ์','รอเจ้าหน้าที่อนุมัติ'];
    if(!in_array($row['b_status'],$allowed) || $row['u_id'] != $u['u_id']){ echo json_encode(['ok'=>false,'msg'=>'ไม่สามารถแก้ไขได้']); exit; }
    $u_phone = $_POST['u_phone']; $u_name = $_POST['u_name'];
    $b_borrow_date = $_POST['b_borrow_date']; $b_return_date = $_POST['b_return_date'];
    $B_NAME_officer = $_POST['B_NAME_officer']; $B_NAME_APPROVER = $_POST['B_NAME_APPROVER'];
    $stmt = $mysqli->prepare('UPDATE borrow_slip SET u_phone=?,u_name=?,b_borrow_date=?,b_return_date=?,B_NAME_officer=?,B_NAME_APPROVER=? WHERE b_id=?');
    $stmt->bind_param('ssssssi',$u_phone,$u_name,$b_borrow_date,$b_return_date,$B_NAME_officer,$B_NAME_APPROVER,$b_id); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

  // ลบใบยืม
  if($act=='del_borrow_slip'){
    $b_id=(int)$_POST['b_id'];
    $u=current_user(); if(!$u){ echo json_encode(['ok'=>false]); exit; }
    $row = $mysqli->query("SELECT b_status,u_id FROM borrow_slip WHERE b_id={$b_id}")->fetch_assoc();
    $allowed = ['รอเพิ่มอุปกรณ์','รอเจ้าหน้าที่อนุมัติ'];
    if(!$row || $row['u_id']!=$u['u_id'] || !in_array($row['b_status'],$allowed)) { echo json_encode(['ok'=>false,'msg'=>'ไม่สามารถลบได้']); exit; }
    $stmt=$mysqli->prepare('DELETE FROM borrow_equipment WHERE b_id=?'); $stmt->bind_param('i',$b_id); $stmt->execute();
    $stmt=$mysqli->prepare('DELETE FROM borrow_slip WHERE b_id=?'); $stmt->bind_param('i',$b_id); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

  // เพิ่มอุปกรณ์ในใบยืม
  if($act=='add_borrow_equipment'){
    $b_id=(int)$_POST['b_id']; $e_id=(int)$_POST['e_id'];
    // check status
    $row=$mysqli->query("SELECT b_status FROM borrow_slip WHERE b_id={$b_id}")->fetch_assoc();
    if(!$row || $row['b_status']!='รอเพิ่มอุปกรณ์'){ echo json_encode(['ok'=>false,'msg'=>'ไม่สามารถเพิ่มได้']); exit; }
    $stmt=$mysqli->prepare("INSERT INTO borrow_equipment (b_id,e_id) VALUES (?,?)");
    $stmt->bind_param("ii",$b_id,$e_id); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

  // ลบอุปกรณ์จากใบยืม
  if($act=='del_borrow_equipment'){
    $b_id=(int)$_POST['b_id']; $e_id=(int)$_POST['e_id'];
    $row=$mysqli->query("SELECT b_status FROM borrow_slip WHERE b_id={$b_id}")->fetch_assoc();
    if(!$row || $row['b_status']!='รอเพิ่มอุปกรณ์'){ echo json_encode(['ok'=>false,'msg'=>'สถานะไม่อนุญาต']); exit; }
    $stmt=$mysqli->prepare('DELETE FROM borrow_equipment WHERE b_id=? AND e_id=? LIMIT 1'); $stmt->bind_param('ii',$b_id,$e_id); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

  // อนุมัติใบยืม (โดยเจ้าหน้าที่ / หัวหน้าเจ้าหน้าที่)
  if($act=='approve_borrow'){
    $b_id=(int)$_POST['b_id']; $u=current_user(); if(!$u){ echo json_encode(['ok'=>false]); exit; }
    $role=$u['role'];
    if($role=='เจ้าหน้าที่'){
      // verify officer matches and current status
      $row=$mysqli->query("SELECT B_NAME_officer,b_status FROM borrow_slip WHERE b_id={$b_id}")->fetch_assoc();
      if(!$row || $row['b_status']!='รอเจ้าหน้าที่อนุมัติ' || $row['B_NAME_officer']!=$u['username']){ echo json_encode(['ok'=>false,'msg'=>'ไม่อนุญาต']); exit; }
      $stmt=$mysqli->prepare("UPDATE borrow_slip SET b_status='รอหัวหน้าเจ้าหน้าที่อนุมัติ' WHERE b_id=?");
    } elseif($role=='หัวหน้าเจ้าหน้าที่'){
      $row=$mysqli->query("SELECT B_NAME_APPROVER,b_status FROM borrow_slip WHERE b_id={$b_id}")->fetch_assoc();
      if(!$row || $row['b_status']!='รอหัวหน้าเจ้าหน้าที่อนุมัติ' || $row['B_NAME_APPROVER']!=$u['username']){ echo json_encode(['ok'=>false,'msg'=>'ไม่อนุญาต']); exit; }
      $stmt=$mysqli->prepare("UPDATE borrow_slip SET b_status='กำลังยืม' WHERE b_id=?");
    } else {
      echo json_encode(['ok'=>false,'msg'=>'สิทธิ์ไม่เพียงพอ']); exit;
    }
    $stmt->bind_param("i",$b_id); $stmt->execute(); echo json_encode(['ok'=>true]); exit;
  }

  // ไม่อนุมัติ
  if($act=='reject_borrow'){
    $b_id=(int)$_POST['b_id']; $u=current_user(); if(!$u){ echo json_encode(['ok'=>false]); exit; }
    $stmt=$mysqli->prepare("UPDATE borrow_slip SET b_status='ไม่อนุมัติ' WHERE b_id=?");
    $stmt->bind_param("i",$b_id); $stmt->execute(); echo json_encode(['ok'=>true]); exit;
  }

  // คืนอุปกรณ์
  if($act=='return_borrow'){
    $b_id=(int)$_POST['b_id'];
    $stmt=$mysqli->prepare("UPDATE borrow_slip SET b_status='คืนแล้ว' WHERE b_id=?");
    $stmt->bind_param("i",$b_id); $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
  }

  echo json_encode(['ok'=>false,'msg'=>'unknown action']); exit;
}

/* ========= HTML HEADER ========= */
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ระบบยืม-คืนวัสดุ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
body{ background:#f8f9fa; }
.navbar{ margin-bottom:20px; }
.container{ max-width:1000px; }
.card{ margin-bottom:20px; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="?">ระบบยืม-คืนวัสดุและอุปกรณ์คอมพิวเตอร์</a>
    <div>
      <?php if(current_user()): ?>
      <span class="navbar-text text-white me-3">👤 <?=safe(current_user()['full_name'])?></span>
      <button class="btn btn-outline-light btn-sm" onclick="logout()">Logout</button>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container">

<?php
$page=$_GET['page']??'home';
if($page=='login'){ ?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">เข้าสู่ระบบ</div>
      <div class="card-body">
        <form id="loginForm">
          <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username"></div>
          <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password"></div>
          <button class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
$("#loginForm").submit(function(e){
 e.preventDefault();
 $.post("?action=login",$(this).serialize(),function(r){
   if(r.ok) location='?'; else alert(r.msg);
 });
});
</script>
<?php
}else{
  require_login();
  $role=current_user()['role'];
  if($page=='home'){
?>
<div class="card">
  <div class="card-header">เมนูหลัก</div>
  <div class="card-body">
    <a href="?page=equipment" class="btn btn-secondary">รายการอุปกรณ์</a>
    <a href="?page=borrow" class="btn btn-secondary">ใบยืม-คืน</a>
    <?php if($role=='ผู้ดูแลระบบ'){ ?>
      <a href="?page=users" class="btn btn-secondary">ผู้ใช้</a>
    <?php } ?>
    <?php if($role=='เจ้าหน้าที่'||$role=='หัวหน้าเจ้าหน้าที่'){ ?>
      <a href="?page=approve" class="btn btn-warning">อนุมัติการยืม</a>
    <?php } ?>
  </div>
</div>
<?php
  }elseif($page=='equipment'){
?>
<div class="card">
  <div class="card-header">อุปกรณ์
    <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#equipModal" onclick="openEquipModal()">เพิ่มอุปกรณ์</button>
  </div>
  <div class="card-body">
    <table class="table table-bordered mt-3">
  <tr><th>ID</th><th>ชื่อ</th><th>ยี่ห้อ</th><th>S/N</th><th>สถานะ</th><th>ที่อยู่</th><th>-</th></tr>
  <?php 
  $rs=$mysqli->query("SELECT * FROM equipment ORDER BY E_ID DESC");
  while($r=$rs->fetch_assoc()){ 
    echo "<tr>
      <td>{$r['E_ID']}</td>
      <td>{$r['E_NAME']}</td>
      <td>{$r['E_TYPE_ID']}</td>
      <td>{$r['E_SN']}</td>
      <td>{$r['STATUS']}</td>
      <td>{$r['ADDRESS']}</td>
      <td>
        <button class='btn btn-sm btn-info me-1' onclick='openEquipModal(".json_encode($r, JSON_UNESCAPED_UNICODE).")'>แก้ไข</button>
        <button class='btn btn-sm btn-danger' onclick='delEq({$r['E_ID']})'>ลบ</button>
      </td>
    </tr>"; 
  } ?>
</table>

   
  </div>
</div>

<!-- Equip Modal -->
<div class="modal fade" id="equipModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="equipForm">
      <div class="modal-header"><h5 class="modal-title">จัดการอุปกรณ์</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="E_ID" id="E_ID" value="0">
        <div class="mb-2"><input name="E_NAME" id="E_NAME" class="form-control" placeholder="ชื่ออุปกรณ์"></div>
        <div class="mb-2"><input name="E_TYPE_ID" id="E_TYPE_ID" class="form-control" placeholder="ยี่ห้อ"></div>
        <div class="mb-2"><input name="E_SN" id="E_SN" class="form-control" placeholder="S/N"></div>
        <div class="mb-2"><input name="STATUS" id="STATUS" class="form-control" placeholder="สถานะ"></div>
        <div class="mb-2"><input name="ADDRESS" id="ADDRESS" class="form-control" placeholder="ที่อยู่"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button class="btn btn-primary">บันทึก</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function openEquipModal(data){
  if(!data){ // new
    $('#E_ID').val(0); $('#E_NAME').val(''); $('#E_TYPE_ID').val(''); $('#E_SN').val(''); $('#STATUS').val(''); $('#ADDRESS').val('');
  } else {
    $('#E_ID').val(data.E_ID); $('#E_NAME').val(data.E_NAME); $('#E_TYPE_ID').val(data.E_TYPE_ID); $('#E_SN').val(data.E_SN); $('#STATUS').val(data.STATUS); $('#ADDRESS').val(data.ADDRESS);
  }
  var myModal = new bootstrap.Modal(document.getElementById('equipModal'));
  myModal.show();
}

$('#equipForm').submit(function(e){
  e.preventDefault();
  var id = $('#E_ID').val();
  var url = id>0? '?action=edit_equipment' : '?action=add_equipment';
  $.post(url, $(this).serialize(), function(r){ if(r.ok) location.reload(); else alert('ผิดพลาด'); });
});

function delEquip(id){ if(!confirm('ลบอุปกรณ์?')) return; $.post('?action=del_equipment',{E_ID:id},function(r){ if(r.ok) location.reload(); else alert('ผิดพลาด'); }); }
</script>

<?php
  }elseif($page=='users'){
?>
<div class="card">
  <div class="card-header">ผู้ใช้
    <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()">เพิ่มผู้ใช้</button>
  </div>
  <div class="card-body">
    <table class="table table-bordered mt-3">
      <tr><th>ID</th><th>Username</th><th>ชื่อ</th><th>Role</th><th>-</th></tr>
      <?php $rs=$mysqli->query("SELECT * FROM users ORDER BY u_id DESC");
      while($r=$rs->fetch_assoc()){ echo "<tr><td>{$r['u_id']}</td><td>".safe($r['username'])."</td><td>".safe($r['full_name'])."</td><td>".safe($r['role'])."</td><td>";
        echo "<button class='btn btn-sm btn-info me-1' onclick='openUserModal(".json_encode($r, JSON_UNESCAPED_UNICODE).")'>แก้ไข</button>";
        echo "<button class='btn btn-sm btn-danger' onclick='delUser({$r['u_id']})'>ลบ</button>";
        echo "</td></tr>"; }
      ?>
    </table>
  </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="userForm">
      <div class="modal-header"><h5 class="modal-title">จัดการผู้ใช้</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="u_id" id="u_id" value="0">
        <div class="mb-2"><input name="username" id="username" class="form-control" placeholder="Username"></div>
        <div class="mb-2"><input name="password" id="password" class="form-control" placeholder="Password"></div>
        <div class="mb-2"><input name="full_name" id="full_name" class="form-control" placeholder="ชื่อ-นามสกุล"></div>
        <div class="mb-2"><select name="role" id="role" class="form-control"><option value="กำลังพลทั่วไป">กำลังพล</option><option>ผู้ดูแลระบบ</option><option>เจ้าหน้าที่</option><option>หัวหน้าเจ้าหน้าที่</option></select></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button class="btn btn-primary">บันทึก</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function openUserModal(data){
  if(!data){ $('#u_id').val(0); $('#username').val(''); $('#password').val(''); $('#full_name').val(''); $('#role').val(''); }
  else { $('#u_id').val(data.u_id); $('#username').val(data.username); $('#password').val(''); $('#full_name').val(data.full_name); $('#role').val(data.role); }
  var myModal = new bootstrap.Modal(document.getElementById('userModal'));
  myModal.show();
}
$('#userForm').submit(function(e){ e.preventDefault(); $.post('?action=save_user',$(this).serialize(),function(r){ if(r.ok) location.reload(); else alert('ผิดพลาด'); }); });
function delUser(id){ if(!confirm('ลบผู้ใช้?')) return; $.post('?action=del_user',{u_id:id},function(r){ if(r.ok) location.reload(); else alert('ผิดพลาด'); }); }
</script>

<?php
  }elseif($page=='borrow'){
?>
<div class="card">
  <div class="card-header">ใบยืม-คืน
    <button class="btn btn-sm btn-success float-end">สร้างใบยืม</button>
  </div>
  <div class="card-body">
    <table class="table table-bordered mt-3">
      <tr><th>ID</th><th>ชื่อ</th><th>เบอร์โทร</th><th>วันยืม</th><th>วันคืน</th><th>สถานะ</th><th>-</th></tr>
      <?php $u_id = current_user()['u_id'];
      $rs=$mysqli->query("SELECT * FROM borrow_slip WHERE u_id={$u_id} ORDER BY b_id DESC");
      while($r=$rs->fetch_assoc()){
        echo "<tr><td>{$r['b_id']}</td><td>".safe($r['u_name'])."</td><td>".safe($r['u_phone'])."</td><td>".safe($r['b_borrow_date'])."</td><td>".safe($r['b_return_date'])."</td><td>".safe($r['b_status'])."</td><td>";
        // actions depending on status
        if(in_array($r['b_status'], ['รอเพิ่มอุปกรณ์','รอเจ้าหน้าที่อนุมัติ'])){
          echo "<button class='btn btn-sm btn-info me-1' onclick='openBorrowModal(".json_encode($r, JSON_UNESCAPED_UNICODE).")'>แก้ไข</button>";
          echo "<button class='btn btn-sm btn-danger me-1' onclick='delBorrow({$r['b_id']})'>ลบ</button>";
        }
        if($r['b_status']=='รอเพิ่มอุปกรณ์'){
          echo "<button class='btn btn-sm btn-secondary me-1' onclick='openAddEquipModal({$r['b_id']})'>เพิ่มอุปกรณ์</button>";
        }
        if($r['b_status']=='กำลังยืม'){
          echo "<button class='btn btn-sm btn-warning me-1' onclick='returnBorrow({$r['b_id']})'>คืน</button>";
        }
        echo "</td></tr>";
      }
      ?>
    </table>
  </div>
</div>

<!-- Borrow Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="borrowForm">
      <div class="modal-header"><h5 class="modal-title">สร้าง/แก้ไข ใบยืม</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="b_id" id="b_id" value="0">
        <div class="mb-2"><input name="u_phone" id="u_phone" class="form-control" placeholder="เบอร์โทรศัพท์"></div>
        <div class="mb-2"><input name="u_name" id="u_name" class="form-control" placeholder="ชื่อผู้ยืม"></div>
        วันที่ยืม<div class="mb-2"><input type="date" name="b_borrow_date" id="b_borrow_date"  class="form-control" ></div>
        วันที่คืน<div class="mb-2"><input type="date" name="b_return_date" id="b_return_date"  class="form-control"></div>
        <div class="mb-2"><input name="B_NAME_officer" id="B_NAME_officer" class="form-control" placeholder="เจ้าหน้าที่"></div>
        <div class="mb-2"><input name="B_NAME_APPROVER" id="B_NAME_APPROVER" class="form-control" placeholder="ผู้อนุมัติ"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button class="btn btn-primary">บันทึก</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Add Equip to Borrow Modal -->
<div class="modal fade" id="addEquipModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addEquipForm">
      <div class="modal-header"><h5 class="modal-title">เพิ่มอุปกรณ์ในใบยืม</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="ae_b_id" name="b_id">
        <div class="mb-2"><select id="ae_e_id" name="e_id" class="form-control">
          <?php $er=$mysqli->query("SELECT * FROM equipment ORDER BY E_NAME"); while($e=$er->fetch_assoc()) echo "<option value='{$e['E_ID']}'>".safe($e['E_NAME'])." ({".safe($e['E_TYPE_ID'])."})</option>";
          ?>
        </select></div>
        <div id="borrowEquipList" class="mb-2"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button><button class="btn btn-primary">เพิ่ม</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function openBorrowModal(data){
  if(!data){ $('#b_id').val(0); $('#u_phone').val(''); $('#u_name').val(''); $('#b_borrow_date').val(''); $('#b_return_date').val(''); $('#B_NAME_officer').val(''); $('#B_NAME_APPROVER').val(''); }
  else { $('#b_id').val(data.b_id); $('#u_phone').val(data.u_phone); $('#u_name').val(data.u_name); $('#b_borrow_date').val(data.b_borrow_date); $('#b_return_date').val(data.b_return_date); $('#B_NAME_officer').val(data.B_NAME_officer); $('#B_NAME_APPROVER').val(data.B_NAME_APPROVER); }
  var myModal = new bootstrap.Modal(document.getElementById('borrowModal'));
  myModal.show();
}
$('#borrowForm').submit(function(e){ e.preventDefault(); var id = $('#b_id').val(); var url = id>0? '?action=update_borrow' : '?action=create_borrow'; $.post(url,$(this).serialize(),function(r){ if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด'); }); });
function delBorrow(id){ if(!confirm('ลบใบยืม?')) return; $.post('?action=del_borrow_slip',{b_id:id},function(r){ if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด'); }); }
function returnBorrow(id){ if(!confirm('ยืนยันการคืน?')) return; $.post('?action=return_borrow',{b_id:id},function(r){ if(r.ok) location.reload(); else alert('ผิดพลาด'); }); }

function openAddEquipModal(bid){ $('#ae_b_id').val(bid); loadBorrowEquipList(bid); var myModal = new bootstrap.Modal(document.getElementById('addEquipModal')); myModal.show(); }

$('#addEquipForm').submit(function(e){ e.preventDefault(); $.post('?action=add_borrow_equipment',$(this).serialize(),function(r){ if(r.ok){ loadBorrowEquipList($('#ae_b_id').val()); } else alert(r.msg||'ผิดพลาด'); }); });

function loadBorrowEquipList(bid){ $.getJSON('?action=__list_borrow_equip&b_id='+bid, function(r){ // we'll implement this helper via ajax endpoint below
    // but since we don't have separate endpoint, fetch via dynamic call
  });
  // simple implementation: request HTML snippet
  $.post('?action=__html_borrow_equip',{b_id:bid},function(html){ $('#borrowEquipList').html(html); });
}

function delBorrowEquip(bid,eid){ if(!confirm('เอาออกจากใบยืม?')) return; $.post('?action=del_borrow_equipment',{b_id:bid,e_id:eid},function(r){ if(r.ok) loadBorrowEquipList(bid); else alert(r.msg||'ผิดพลาด'); }); }
</script>

<?php
  }elseif($page=='approve'){
    $u=current_user();
    if($role=='เจ้าหน้าที่'){
      $rs=$mysqli->query("SELECT * FROM borrow_slip WHERE B_NAME_officer='{$u['username']}' AND b_status='รอเจ้าหน้าที่อนุมัติ'");
    }elseif($role=='หัวหน้าเจ้าหน้าที่'){
      $rs=$mysqli->query("SELECT * FROM borrow_slip WHERE B_NAME_APPROVER='{$u['username']}' AND b_status='รอหัวหน้าเจ้าหน้าที่อนุมัติ'");
    } else { $rs = null; }
?>
<div class="card">
  <div class="card-header">อนุมัติการยืม</div>
  <div class="card-body">
    <table class="table table-bordered">
      <tr><th>ID</th><th>ผู้ยืม</th><th>วันยืม</th><th>วันคืน</th><th>อุปกรณ์</th><th>การทำงาน</th></tr>
      <?php if($rs) while($r=$rs->fetch_assoc()){
        $equip=$mysqli->query("SELECT e.E_NAME,e.E_ID FROM borrow_equipment be JOIN equipment e ON be.e_id=e.E_ID WHERE be.b_id={$r['b_id']}");
        $elist=[]; while($ee=$equip->fetch_assoc()) $elist[]="<div>".safe($ee['E_NAME'])."</div>";
        $elist_txt=implode("",$elist);
        echo "<tr>
          <td>{$r['b_id']}</td>
          <td>".safe($r['u_name'])."</td>
          <td>".safe($r['b_borrow_date'])."</td>
          <td>".safe($r['b_return_date'])."</td>
          <td>{$elist_txt}</td>
          <td>
            <button class='btn btn-success btn-sm me-1' onclick='approve({$r['b_id']})'>อนุมัติ</button>
            <button class='btn btn-danger btn-sm' onclick='reject({$r['b_id']})'>ไม่อนุมัติ</button>
          </td>
        </tr>";
      } ?>
    </table>
  </div>
</div>
<script>
function approve(id){ $.post('?action=approve_borrow',{b_id:id},function(r){ if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด'); }); }
function reject(id){ if(!confirm('ไม่อนุมัติ?')) return; $.post('?action=reject_borrow',{b_id:id},function(r){ if(r.ok) location.reload(); else alert('ผิดพลาด'); }); }
</script>

<?php
  } else {
    echo "<h3>หน้านี้ไม่พบ</h3>";
  }
}
?>
</div>
<script>
function logout(){
    $.post("?action=logout", {}, function(r){
        if(r.ok){
            window.location.href = "?page=login";
        } else {
            alert("ออกจากระบบไม่สำเร็จ");
        }
    });
}
function delEq(id){
  if(confirm("คุณแน่ใจว่าจะลบอุปกรณ์นี้?")){
    $.post("?action=del_equipment",{E_ID:id},function(r){
      if(r.ok) location.reload();
      else alert("ลบไม่สำเร็จ");
    });
  }
}

</script>
</body>
</html>

<!-- Helper endpoints rendered server-side to support UI (not through ?action in top block) -->
<?php
// small handlers for AJAX HTML snippets (kept outside header because earlier header already handled action param)
if(isset($_POST['action']) && ($_POST['action']=='__html_borrow_equip')){
  $b_id=(int)$_POST['b_id'];
  $out='';
  $q=$mysqli->query("SELECT be.e_id,e.E_NAME,e.E_TYPE_ID FROM borrow_equipment be JOIN equipment e ON be.e_id=e.E_ID WHERE be.b_id={$b_id}");
  while($ee=$q->fetch_assoc()){
    $out .= "<div class='d-flex justify-content-between align-items-center mb-1'>".safe($ee['E_NAME'])." <button class='btn btn-sm btn-danger' onclick='delBorrowEquip({$b_id},{$ee['e_id']})'>เอาออก</button></div>";
  }
  if($out=='') $out='<div class="text-muted">ยังไม่มีอุปกรณ์</div>';
  echo $out; exit;
}

// Also support small JSON list if wanted
if(isset($_GET['action']) && $_GET['action']=='__list_borrow_equip'){
  $b_id=(int)$_GET['b_id'];
  $arr=[]; $q=$mysqli->query("SELECT be.e_id,e.E_NAME,e.E_TYPE_ID FROM borrow_equipment be JOIN equipment e ON be.e_id=e.E_ID WHERE be.b_id={$b_id}");
  while($ee=$q->fetch_assoc()) $arr[]=$ee;
  header('Content-Type: application/json; charset=utf-8'); echo json_encode($arr); exit;
}
?>
