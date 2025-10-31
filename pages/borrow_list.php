<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
$u = $_SESSION['user']; 

$stmt = $mysqli->prepare(
  "SELECT b_id, u_name, u_phone, b_date_borrow, b_status
   FROM borrow_slip
   WHERE u_idp = ?
   ORDER BY b_id DESC"
);

$stmt->bind_param('s', $u['u_idp']);
$stmt->execute();
$rs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบยืม-คืนของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">

<h3>📋 ใบยืม-คืนของฉัน</h3>

<button class="btn btn-success mb-3" id="btnAddBorrow" type="button" data-bs-toggle="modal" data-bs-target="#borrowModal">+ สร้างใบยืม</button>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>ชื่อผู้ยืม</th>
      <th>เบอร์</th>
      <th>วันยืม</th>
      <th>สถานะ</th>
      <th>การกระทำ</th>
    </tr>
  </thead>
  <tbody>
  <?php while($r = $rs->fetch_assoc()):
      $json_item = htmlspecialchars(
        json_encode($r, JSON_UNESCAPED_UNICODE|JSON_HEX_QUOT|JSON_HEX_APOS),
        ENT_QUOTES, 'UTF-8'
      );
  ?>
    <tr>
      <td><?= (int)$r['b_id'] ?></td>
      <td><?= htmlspecialchars($r['u_name']) ?></td>
      <td><?= htmlspecialchars($r['u_phone']) ?></td>
      <td><?= htmlspecialchars($r['b_date_borrow']) ?></td>
      <td><?= htmlspecialchars($r['b_status']) ?></td>
      <td>
        <?php if(in_array($r['b_status'], ['รอเพิ่มอุปกรณ์','รอเจ้าหน้าที่อนุมัติ'])): ?>
          <button class="btn btn-sm btn-info edit " data-item='<?= $json_item ?>'>แก้ไข</button>
          <button class="btn btn-sm btn-danger del" data-id="<?= (int)$r['b_id'] ?>">ลบ</button>
        <?php endif; ?>
        <?php if($r['b_status'] === 'รอเพิ่มอุปกรณ์'): ?>
          <button class="btn btn-sm btn-secondary addEq" data-id="<?= (int)$r['b_id'] ?>">เพิ่มอุปกรณ์</button>
        <?php endif; ?>
        <?php if($r['b_status'] === 'กำลังยืม'): ?>
          <button class="btn btn-sm btn-warning ret" data-id="<?= (int)$r['b_id'] ?>">คืน</button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<div class="modal fade" id="borrowModal" tabindex="-1" aria-labelledby="borrowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="borrowForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="borrowModalLabel">สร้าง/แก้ไขใบยืม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="b_id" id="b_id" value="0">
                <div class="mb-2"><input  type="hidden" class="form-control" name="u_idp" id="u_idp" placeholder="รหัสประจำตัว" value="<?= htmlspecialchars($_SESSION['user']['u_idp'], ENT_QUOTES, 'UTF-8'); ?>" ></div>
                <div class="mb-2"><input class="form-control" name="u_name" id="u_name" placeholder="ชื่อผู้ยืม"  ></div>
                <div class="mb-2"><input class="form-control" name="u_phone" id="u_phone" placeholder="เบอร์โทร"></div>
                <div class="mb-2"><input class="form-control" name="u_phone" id="b_date" placeholder="วันที่ยืม"></div>
                <div class="mb-2"><input class="form-control" name="u_unit" id="u_unit" placeholder="ชื่อหน่วยผู้ยืม" ></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" id="saveBorrowForm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const borrowModal = new bootstrap.Modal(document.getElementById('borrowModal'));
const ajaxUrl = '/actions/borrow_action.php';

$('#btnAddBorrow').click(function(){
    $('#borrowForm')[0].reset();
    $('#b_id').val('');
    $('#saveBorrowForm').data('action', 'add'); // Set the action to 'add'
    borrowModal.show();
});

$('#saveBorrowForm').click(function(){
    let action = $(this).data('action'); // Get the action (add or edit)
    let formData = new FormData($('#borrowForm')[0]);
    for (let [key, value] of formData.entries()) {
      console.log(key + ': ' + value);
    }
    formData.append('action_type', 'save_borrow');
    $.ajax({
        url: 'actions/borrow_action.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res){
            if(res.ok){
                alert('✅ บันทึกสำเร็จ');
                location.reload();
            }else{
                alert('❌ บันทึกล้มเหลว: ' + res.error);
            }
        },
        error: function(xhr){
            alert('❌ บันทึกล้มเหลว: ' + xhr.statusText);
        }
    });
});

$(document).on('click', '.edit', function(){
    const item = JSON.parse($(this).attr('data-item'));

    console.log(item); // ✅ check in console what data you get
    $('#b_id').val(item.b_id);
    $('#u_phone').val(item.u_phone);
    $('#u_name').val(item.u_name);
    $('#b_date_borrow').val(item.b_date_borrow);
    $('#saveBorrowForm').data('action', 'edit'); // Set the action to 'edit'
    borrowModal.show();
});

$(document).on('click', '.del', function(){
    if(!confirm('ลบใบยืม?')) return;
    $.post(ajaxUrl, {action_type:'delete_borrow', b_id: $(this).data('id')}, null, 'json')
        .done(r => r.ok ? location.reload() : alert(r.msg||'เกิดข้อผิดพลาด'))
        .fail((_x, _s, err) => alert('เกิดข้อผิดพลาด: ' + err));
});

const addEquipModal = new bootstrap.Modal(document.getElementById('addEquipModal'));

// Open Add Equipment Modal
$(document).on('click', '.addEq', function() {
  const bId = $(this).data('id');
  $('#ae_b_id').val(bId);
  loadEquipList(bId);
  addEquipModal.show();
});

// Handle Add Equipment form submit
$('#addEquipForm').on('submit', function(e) {
  e.preventDefault();
  $.post('actions/borrow_action.php', $(this).serialize() + '&action_type=add_equip', null, 'json')
    .done(r => r.ok ? loadEquipList($('#ae_b_id').val()) : alert(r.msg || 'เกิดข้อผิดพลาด'))
    .fail((_x, _s, err) => alert('เกิดข้อผิดพลาด: ' + err));
});

// Load equipment list
function loadEquipList(bid) {
  $.getJSON('actions/borrow_action.php', { action_type: 'list_equip', b_id: bid })
    .done(arr => {
      if (!Array.isArray(arr) || arr.length === 0) {
        $('#ae_list').html('<div class="text-muted">ยังไม่มีอุปกรณ์</div>');
        return;
      }
      const html = arr.map(it => `
        <div class="d-flex justify-content-between mb-1">
          ${it.E_NAME}
          <button class="btn btn-sm btn-danger ae-del" data-bid="${bid}" data-eid="${it.e_id}">ลบ</button>
        </div>`).join('');
      $('#ae_list').html(html);
    });
}

// Delete equipment from slip
$(document).on('click', '.ae-del', function() {
  if (!confirm('เอาออกจากใบยืม?')) return;
  const bid = $(this).data('bid'), eid = $(this).data('eid');
  $.post('actions/borrow_action.php', { action_type: 'delete_equip', b_id: bid, e_id: eid }, null, 'json')
    .done(r => r.ok ? loadEquipList(bid) : alert(r.msg || 'เกิดข้อผิดพลาด'))
    .fail((_x, _s, err) => alert('เกิดข้อผิดพลาด: ' + err));
});


</script>

</body>
</html>
