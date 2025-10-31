<?php
// pages/borrow_approve.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = $_SESSION['user'];
$role = $user['u_role'];

if (!in_array($role, ['OPERATOR','ADMIN'])) {
  echo "<div class='alert alert-danger'>สิทธิ์ไม่เพียงพอ</div>";
  return;
}

if ($role === 'ADMIN'){
  $rs = $mysqli->prepare("SELECT * FROM borrow_slip WHERE b_status = 1 ORDER BY b_id DESC");
  //  $rs->bind_param("s", $user['u_idp']);
  $rs->execute();
  $result = $rs->get_result();
} else {
  $rs = $mysqli->prepare("SELECT * FROM borrow_slip WHERE b_status = 1 ORDER BY b_id DESC");
  //  $rs->bind_param("s", $user['u_idp']);
  $rs->execute();
  $result = $rs->get_result();
}
?>

<h3>อนุมัติการยืม (<?=htmlspecialchars($role)?>)</h3>
<table class="table table-bordered">
  <thead><tr><th>#</th><th>ผู้ยืม</th><th>วันยืม</th><th>วันคืน</th><th>อุปกรณ์</th><th>-</th></tr></thead>
  <tbody>
<?php while($r = $result->fetch_assoc()){
  // load equipment list
  $qr = $mysqli->query("SELECT e.E_NAME FROM borrow_equipment be JOIN equipments e ON be.E_id=e.E_id WHERE be.B_ID={$r['b_id']}");
  $elist = [];
  while($ee = $qr->fetch_assoc()) $elist[] = htmlspecialchars($ee['E_NAME']);
  echo "<tr>
    <td>{$r['b_id']}</td>
    <td>".htmlspecialchars($r['u_name'])."</td>
    <td>".htmlspecialchars($r['b_date_borrow'])."</td>
    <td>".htmlspecialchars($r['b_date_receive'])."</td>
    <td>".implode('<br>',$elist)."</td>
    <td>
      <button class='btn btn-sm btn-success' onclick='approve({$r['b_id']})'>อนุมัติ</button>
      <button class='btn btn-sm btn-danger' onclick='reject({$r['b_id']})'>ไม่อนุมัติ</button>
    </td>
  </tr>";
} ?>
  </tbody>
</table>

<script>
function approve(id){
  $.post('actions/borrow_action.php',{action_type:'approve',b_id:id}, function(resp){
    try{ var r=JSON.parse(resp);}catch(e){ alert('error'); return; }
    if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด');
  });
}
function reject(id){
  if(!confirm('ไม่อนุมัติ?')) return;
  $.post('actions/borrow_action.php',{action_type:'reject',b_id:id}, function(resp){
    try{ var r=JSON.parse(resp);}catch(e){ alert('error'); return; }
    if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด');
  });
}
</script>
