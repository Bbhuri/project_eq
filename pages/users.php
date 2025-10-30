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
  <thead><tr><th>#</th><th>Username</th><th>ชื่อ</th><th>Role</th><th>-</th></tr></thead>
  <tbody>
<?php
$rs = $mysqli->query("SELECT * FROM users ORDER BY u_id DESC");
while($r = $rs->fetch_assoc()){
  echo "<tr>
    <td>{$r['u_id']}</td>
    <td>".htmlspecialchars($r['u_username'])."</td>
    <td>".htmlspecialchars($r['u_name'])."</td>
    <td>".htmlspecialchars($r['u_role'])."</td>
    <td>
      <button class='btn btn-sm btn-warning edit' data-item='".htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8')."'>แก้ไข</button>
      <button class='btn btn-sm btn-danger del' data-id='{$r['u_id']}'>ลบ</button>
    </td>
  </tr>";
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
      <div class="mb-2"><input class="form-control" name="u_idp" id="u_idp" placeholder="รหัสประจำตัว" required></div>
      <div class="mb-2"><input class="form-control" name="u_username" id="u_username" placeholder="ชื่อผู้ใช้" required></div>
      <div class="mb-2"><input class="form-control" name="u_password" id="u_password" placeholder="รหัสผ่าน"></div>
      <div class="mb-2"><input class="form-control" name="u_name" id="u_name" placeholder="ชื่อ-นามสกุล"></div>
      <div class="mb-2"><input class="form-control" name="u_unit" id="u_unit" placeholder="หน่วย"></div>
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
  $('#btnAdd').click(function(){
    $('#u_id').val(0); $('#u_username').val(''); $('#u_password').val(''); $('#u_name').val('');$('#u_unit').val(''); $('#u_role').val('');
    userModal.show();
  });
  $('.edit').click(function(){
    var item = JSON.parse($(this).data('item'));
    $('#u_id').val(item.u_id); $('#u_username').val(item.u_username); $('#u_password').val(''); $('#u_name').val(item.u_name);$('#u_unit').val(item.u_unit); $('#u_role').val(item.u_role);
    userModal.show();
  });
  $('#userForm').submit(function(e){
    e.preventDefault();
    $.post('actions/user_action.php', $(this).serialize(), function(resp){
      try{ var r = JSON.parse(resp); }catch(e){ alert('response error'); return; }
      if(r.ok) location.reload(); else alert(r.msg||'ผิดพลาด');
    });
  });
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
