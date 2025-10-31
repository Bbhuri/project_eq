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

<button class="btn btn-success mb-3" id="btnAddBorrow">+ สร้างใบยืม</button>

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
      $json_item = htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE|JSON_HEX_QUOT|JSON_HEX_APOS), ENT_QUOTES, 'UTF-8');
  ?>
    <tr>
      <td><?= (int)$r['b_id'] ?></td>
      <td><?= htmlspecialchars($r['u_name']) ?></td>
      <td><?= htmlspecialchars($r['u_phone']) ?></td>
      <td><?= htmlspecialchars($r['b_date_borrow']) ?></td>
      <td><?= htmlspecialchars($r['b_status']) ?></td>
      <td>
        <?php if(in_array($r['b_status'], ['รอเพิ่มอุปกรณ์','รอเจ้าหน้าที่อนุมัติ'])): ?>
          <button class="btn btn-sm btn-info edit" data-item='<?= $json_item ?>'>แก้ไข</button>
          <button class="btn btn-sm btn-danger del" data-id="<?= (int)$r['b_id'] ?>">ลบ</button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<!-- Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1" aria-labelledby="borrowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="borrowForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="borrowModalLabel">สร้าง/แก้ไขใบยืม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="b_id" id="b_id" value="0">
                <input type="hidden" name="u_idp" value="<?= htmlspecialchars($_SESSION['user']['u_idp'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="mb-2">
                    <input type="text" class="form-control" name="u_name" id="u_name" placeholder="ชื่อผู้ยืม" required>
                </div>
                <div class="mb-2">
                    <input type="text" class="form-control" name="u_phone" id="u_phone" placeholder="เบอร์โทร" required>
                </div>
                <div class="mb-2">
                    <input type="date" class="form-control" name="b_date_borrow" id="b_date_borrow" placeholder="วันที่ยืม" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" id="saveBorrowForm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const borrowModal = new bootstrap.Modal(document.getElementById('borrowModal'));
const ajaxUrl = '/actions/borrow_action.php';

// เปิด modal เพิ่มใบยืม
$('#btnAddBorrow').click(function(){
    $('#borrowForm')[0].reset();
    $('#b_id').val('0');
    $('#saveBorrowForm').data('action', 'add');
    borrowModal.show();
});

// บันทึกใบยืม
$('#saveBorrowForm').click(function(){
    let formData = new FormData($('#borrowForm')[0]);
    formData.append('action_type', 'save_borrow');
    formData.append('action', $(this).data('action')); // add หรือ edit

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res){
            if(res.ok){
                alert('✅ บันทึกสำเร็จ');
                location.reload();
            } else {
                alert('❌ บันทึกล้มเหลว: ' + res.error);
            }
        },
        error: function(xhr){
            alert('❌ บันทึกล้มเหลว: ' + xhr.statusText);
        }
    });
});

// แก้ไขใบยืม
$(document).on('click', '.edit', function(){
    const item = JSON.parse($(this).attr('data-item'));
    $('#b_id').val(item.b_id);
    $('#u_name').val(item.u_name);
    $('#u_phone').val(item.u_phone);
    $('#b_date_borrow').val(item.b_date_borrow);
    $('#saveBorrowForm').data('action', 'edit');
    borrowModal.show();
});

// ลบใบยืม
$(document).on('click', '.del', function(){
    if(!confirm('คุณต้องการลบใบยืมนี้หรือไม่?')) return;
    $.post(ajaxUrl, { action_type:'delete_borrow', b_id: $(this).data('id') }, null, 'json')
        .done(r => r.ok ? location.reload() : alert(r.msg||'เกิดข้อผิดพลาด'))
        .fail((_x,_s,err) => alert('เกิดข้อผิดพลาด: ' + err));
});
</script>

</body>
</html>
