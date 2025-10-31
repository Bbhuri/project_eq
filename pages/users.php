<?php
// pages/users.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['ADMIN']);
?>
<h3>จัดการผู้ใช้</h3>
<button class="btn btn-success mb-3" id="btnAdd">+ เพิ่มผู้ใช้</button>
<table class="table table-bordered">
  <thead><tr><th>ลำดับ</th><th>Username</th><th>ชื่อ</th><th>Role</th><th>-</th></tr></thead>
  <tbody>
<?php
$rs = $mysqli->query("SELECT * FROM users ORDER BY u_id DESC");
$i = 1; // ตัวนับลำดับ
while($r = $rs->fetch_assoc()){
  echo "<tr>
    <td>{$i}</td>
    <td>".htmlspecialchars($r['u_username'])."</td>
    <td>".htmlspecialchars($r['u_name'])."</td>
    <td>".htmlspecialchars($r['u_role'])."</td>
    <td>
      <button class='btn btn-sm btn-warning edit'
        data-id='{$r['u_id']}'
        data-idp='{$r['u_idp']}'
        data-username='{$r['u_username']}'
        data-name='{$r['u_name']}'
        data-unit='{$r['u_unit']}'
        data-role='{$r['u_role']}'
      >แก้ไข</button>
      <button class='btn btn-sm btn-danger del' data-id='{$r['u_id']}'>ลบ</button>
    </td>
  </tr>";
  $i++;
}

?>
  </tbody>
</table>

<!-- Modal -->
<div class="modal" id="userModal" tabindex="-1"><div class="modal-dialog">
  <form id="userForm" class="modal-content" method="post" action="actions/user_action.php">
    <div class="modal-header"><h5 class="modal-title">เพิ่ม/แก้ไขผู้ใช้</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="u_id" id="u_id" value="0">
      รหัสประจำตัว
      <div class="mb-2"><input class="form-control" name="u_idp" id="u_idp" placeholder="รหัสประจำตัว" required></div>
      ชื่อผู้ใช้
      <div class="mb-2"><input class="form-control" name="u_username" id="u_username" placeholder="ชื่อผู้ใช้" required></div>
      รหัสผ่าน
      <div class="mb-2"><input class="form-control" name="u_password" id="u_password" placeholder="รหัสผ่าน"></div>
      ชื่อ-นามสกุล
      <div class="mb-2"><input class="form-control" name="u_name" id="u_name" placeholder="ชื่อ-นามสกุล"></div>
      หน่วย
      <div class="mb-2"><input class="form-control" name="u_unit" id="u_unit" placeholder="หน่วย"></div>
      สิทธิ์การใช้งาน
      <div class="mb-2">
        <select class="form-control" name="u_role" id="u_role">
          <option value="USER">กำลังพลทั่วไป</option>
          <option value="ADMIN">ผู้ดูแลระบบ</option>
          <option value="OPERATOR">เจ้าหน้าที่</option>
          <option value="DIRECTOR">หัวหน้าเจ้าหน้าที่</option>
        </select>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button class="btn btn-primary">บันทึก</button></div>
  </form>
</div></div>

<script>
$(function(){
  var userModal = new bootstrap.Modal(document.getElementById('userModal'));

  // เพิ่มผู้ใช้
  $('#btnAdd').click(function(){
    $('#u_id').val(0);
    $('#u_idp').val('');
    $('#u_username').val('');
    $('#u_password').val('');
    $('#u_name').val('');
    $('#u_unit').val('');
    $('#u_role').val('USER');
    $('.modal-title').text('เพิ่มผู้ใช้');
    userModal.show();
  });

  // แก้ไขผู้ใช้
  $('.edit').click(function(){
    $('#u_id').val($(this).data('id'));
    $('#u_idp').val($(this).data('idp'));
    $('#u_username').val($(this).data('username'));
    $('#u_password').val(''); // ไม่แสดงรหัสผ่าน
    $('#u_name').val($(this).data('name'));
    $('#u_unit').val($(this).data('unit'));
    $('#u_role').val($(this).data('role'));
    $('.modal-title').text('แก้ไขผู้ใช้');
    userModal.show();
  });

  // บันทึก
  $('#userForm').submit(function(e){
    e.preventDefault();
    $.post('actions/user_action.php', $(this).serialize(), function(resp){
      try{ var r = JSON.parse(resp); }catch(e){ alert('response error'); return; }
      if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด');
    });
  });

  // ลบ
  $('.del').click(function(){
    if(!confirm('ลบผู้ใช้?')) return;
    var id = $(this).data('id');
    $.post('actions/user_action.php',{act:'delete',u_id:id},function(resp){
      try{ var r = JSON.parse(resp);}catch(e){ alert('error'); return; }
      if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด');
    });
  });
});
</script>
