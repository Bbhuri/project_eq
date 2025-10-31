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
      <th>ลำดับ</th>
      <th>ชื่อผู้ยืม</th>
      <th>เบอร์</th>
      <th>วันยืม</th>
      <th>สถานะ</th>
      <th>การกระทำ</th>
</tr>
</thead>
<tbody>
  <?php $i = 1; ?>
<?php while($r = $rs->fetch_assoc()): 
$statusMap = [
    0 => 'รอส่งคำขอ',
    1 => 'รอเจ้าหน้าที่ตรวจสอบ',
    2 => 'รอการอนุมัติจากหัวหน้า',
    11 => 'เจ้าหน้าที่ไม่อนุมัติ',
    12 => 'หัวหน้าไม่อนุมัติ',
    3 => 'กำลังยืม',
    4 => 'ส่งคืนกำลังตรวจสอบ',
    5 => 'คืนแล้ว',
];
    $json_item = htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE|JSON_HEX_QUOT|JSON_HEX_APOS), ENT_QUOTES, 'UTF-8');
?>

<tr>
  <td><?= $i ?></td> <!-- แสดงลำดับ -->
  <td><?= htmlspecialchars($r['u_name']) ?></td>
  <td><?= htmlspecialchars($r['u_phone']) ?></td>
  <td><?= htmlspecialchars($r['b_date_borrow']) ?></td>
  <td><?= $statusMap[$r['b_status']] ?? 'ไม่ทราบสถานะ' ?></td>
  <td>
    <button class="btn btn-sm btn-info edit" data-item='<?= $json_item ?>'>แก้ไข</button>
    <button class="btn btn-sm btn-danger del" data-id="<?= (int)$r['b_id'] ?>">ลบ</button>
    <button class="btn btn-sm btn-primary add-item" data-id="<?= (int)$r['b_id'] ?>">+ เพิ่มอุปกรณ์</button>
    <button class="btn btn-sm btn-secondary view-items" data-id="<?= (int)$r['b_id'] ?>">ดูอุปกรณ์</button>
       <?php if($r['b_status']==0): ?>
        <button class="btn btn-sm btn-warning send-request" data-id="<?= (int)$r['b_id'] ?>">ส่งคำขอ</button>
    <?php endif; ?>
  </td>
</tr>
<?php $i++; endwhile; ?>
</tbody>
</table>

<!-- Modal สร้าง/แก้ไขใบยืม -->
<div class="modal fade" id="borrowModal" tabindex="-1" aria-labelledby="borrowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="borrowForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="borrowModalLabel">สร้าง/แก้ไขใบยืม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="b_id" id="b_id" value="0">
                <input type="hidden" name="u_idp" value="<?= htmlspecialchars($u['u_idp'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="u_name" value="<?= htmlspecialchars($u['u_name'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="u_unit" value="<?= htmlspecialchars($u['u_unit'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-2">
                    <label>เบอร์โทร</label>
                    <input type="text" 
           class="form-control" 
           name="u_phone" 
           id="u_phone" 
           placeholder="เบอร์โทร" 
           required 
           maxlength="10"
           pattern="\d{10}" 
           title="กรุณากรอกตัวเลข 10 หลัก">
                </div>
                <p>ชื่อผู้ยืม: <?= htmlspecialchars($u['u_name']) ?></p>
                <p>หน่วย: <?= htmlspecialchars($u['u_unit']) ?></p>
                <p>วันที่ขอยืม: <?= date('Y-m-d') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" id="saveBorrowForm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ดูอุปกรณ์ -->
<div class="modal fade" id="itemsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">อุปกรณ์ในใบยืม</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="itemsTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>ชื่ออุปกรณ์</th>
              <th>สถานะ</th>
              <th>การกระทำ</th>
            </tr>
          </thead>
          <tbody>
            <!-- จะโหลดด้วย AJAX -->
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const borrowModal = new bootstrap.Modal(document.getElementById('borrowModal'));
const ajaxUrl = 'actions/borrow_action.php';

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
    formData.append('action', $(this).data('action'));

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
                alert('❌ บันทึกล้มเหลว: ' + (res.error || res.msg));
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
    $('#u_phone').val(item.u_phone);
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

// ปุ่มเพิ่มอุปกรณ์
$(document).on('click', '.add-item', function(){
    const b_id = $(this).data('id');
    // ตัวอย่าง redirect ไปหน้าเพิ่มอุปกรณ์
    window.location.href = 'pages/borrow_equipment.php?b_id=' + b_id;

});



const itemsModal = new bootstrap.Modal(document.getElementById('itemsModal'));

// เปิด modal ดูอุปกรณ์
$(document).on('click', '.view-items', function(){
    const b_id = $(this).data('id');
    $.getJSON('actions/borrow_equipment_action.php', {b_id: b_id, action_type:'list_items'}, function(res){
        if(res.ok){
            let tbody = '';
            res.items.forEach(item=>{
                tbody += `<tr>
                    <td>${item.E_ID}</td>
                    <td>${item.E_NAME}</td>
                    <td>${item.E_STATUS}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item" data-bid="${b_id}" data-eid="${item.E_ID}">ลบ</button>
                    </td>
                </tr>`;
            });
            $('#itemsTable tbody').html(tbody);
            itemsModal.show();
        } else {
            alert('❌ ไม่สามารถโหลดรายการอุปกรณ์: ' + (res.error || res.msg));
        }
    });
});


// ลบอุปกรณ์จากใบยืม
$(document).on('click', '.remove-item', function(){
    const b_id = $(this).data('bid');
    const E_ID = $(this).data('eid');
    const btn = $(this);

    if(!confirm('คุณต้องการลบอุปกรณ์นี้ออกจากใบยืมใช่หรือไม่?')) return;

    $.post('actions/borrow_equipment_action.php', {b_id:b_id, E_ID:E_ID, action_type:'remove_item'}, function(res){
        if(res.ok){
            btn.closest('tr').remove();
        } else {
            alert('❌ ไม่สามารถลบอุปกรณ์: ' + (res.error||res.msg));
        }
    }, 'json');
});


// ส่งคำขอ
$(document).on('click', '.send-request', function(){
    const b_id = $(this).data('id');
    if(!confirm('คุณต้องการส่งคำขอใบยืมนี้ใช่หรือไม่?')) return;

    $.post('actions/borrow_action.php', { action_type: 'send_request', b_id: b_id }, function(res){
        if(res.ok){
            alert('✅ ส่งคำขอเรียบร้อย');
            location.reload();
        } else {
            alert('❌ ส่งคำขอล้มเหลว: ' + (res.error || res.msg));
        }
    }, 'json');
});

</script>

</body>
</html>
