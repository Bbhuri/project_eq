<?php
require_once __DIR__ . '/../config/db.php';
$equipments = $mysqli->query("SELECT * FROM equipments ORDER BY E_ID DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการอุปกรณ์</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">

<h3 class="mb-3">📦 ระบบจัดการอุปกรณ์</h3>

<button class="btn btn-primary mb-3" id="btnAdd">➕ เพิ่มอุปกรณ์</button>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>ชื่ออุปกรณ์</th>
            <th>ยี่ห้อ</th>
            <th>S/N</th>
            <th>รูป</th>
            <th>สถานะ</th>
            <th>ที่อยู่</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php while($r = $equipments->fetch_assoc()): ?>
        <tr>
            <td><?= $r['E_ID'] ?></td>
            <td><?= htmlspecialchars($r['E_NAME']) ?></td>
            <td><?= htmlspecialchars($r['E_TYPE_ID']) ?></td>
            <td><?= htmlspecialchars($r['E_SN']) ?></td>
            <td>
 <?php if (!empty($r['E_IMG'])): ?>
    <img src="data:image/jpeg;base64,<?= base64_encode($r['E_IMG']) ?>"
         style="max-width:200px; height:auto; border-radius:5px; box-shadow:0 0 5px rgba(0,0,0,0.2);">
<?php else: ?>
    <span class="text-muted">ไม่มีรูป</span>
<?php endif; ?>
            <td><?= htmlspecialchars($r['E_STATUS']) ?></td>
            <td><?= htmlspecialchars($r['E_ADDRESS']) ?></td>
            <td>
                <button class="btn btn-warning btn-sm btn-edit"
  data-id="<?= $r['E_ID'] ?>"
  data-name="<?= htmlspecialchars($r['E_NAME']) ?>"
  data-type="<?= htmlspecialchars($r['E_TYPE_ID']) ?>"
  data-sn="<?= htmlspecialchars($r['E_SN']) ?>"
  data-status="<?= htmlspecialchars($r['E_STATUS']) ?>"
  data-address="<?= htmlspecialchars($r['E_ADDRESS']) ?>"
  data-img="<?= !empty($r['E_IMG']) ? '1' : '0' ?>"

>แก้ไข</button>

                <button class="btn btn-danger btn-sm btn-del" data-id="<?= $r['E_ID'] ?>">ลบ</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<!-- Modal แสดงภาพใหญ่ -->
<div class="modal fade" id="imgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center bg-transparent border-0">
      <img id="modalImg" style="max-width:100%; border-radius:10px;">
    </div>
  </div>
</div>

<script>
$(document).on('click', '.img-click', function(){
  $('#modalImg').attr('src', $(this).attr('src'));
  $('#imgModal').modal('show');
});
</script>


<!-- Modal -->
<div class="modal fade" id="equipModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
    <h5 class="modal-title">จัดการอุปกรณ์</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<form id="equipForm" enctype="multipart/form-data">
    <input type="hidden" name="E_ID" id="E_ID">
    <div class="mb-2">
        <label>ชื่ออุปกรณ์</label>
        <input type="text" class="form-control" name="E_NAME" id="E_NAME" required>
    </div>
    <div class="mb-2">
        <label>ยี่ห้อ</label>
        <input type="text" class="form-control" name="E_TYPE_ID" id="E_TYPE_ID" required>
    </div>
    <div class="mb-2">
        <label>S/N</label>
        <input type="text" class="form-control" name="E_SN" id="E_SN" required>
    </div>
    <div class="mb-2">
  <label>รูป</label>
  <input type="file" class="form-control" name="E_IMG" id="E_IMG">
  <input type="hidden" name="E_HAS_IMG" id="E_HAS_IMG">
  <div class="mt-2 text-center">
    <img id="previewImg" src="" style="max-width:300px; display:none; border:1px solid #ccc; border-radius:5px;">
  </div>
</div>

     <div class="mb-2">
        <label>สถานะ</label>
        <select class="form-select" name="E_STATUS" id="E_STATUS" required>
            <option value="AVAILABLE">AVAILABLE</option>
            <option value="BORROW">BORROW</option>
            <option value="LOST">LOST</option>
            <option value="UNAVAILABLE">UNAVAILABLE</option>
        </select>
    </div>
    <div class="mb-2">
        <label>ที่อยู่</label>
        <input type="text" class="form-control" name="E_ADDRESS" id="E_ADDRESS">
    </div>
</form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
    <button type="button" class="btn btn-success" id="btnSave">💾 บันทึก</button>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const equipModal = new bootstrap.Modal(document.getElementById('equipModal'));

// เพิ่ม
$('#btnAdd').click(function(){
    $('#equipForm')[0].reset();
    $('#E_ID').val('');
    $('#btnSave').data('action', 'add'); // Set the action to 'add'
    equipModal.show();
});

// เมื่อคลิก "แก้ไข"
$('.btn-edit').click(function(){
    $('#E_ID').val($(this).data('id'));
    $('#E_NAME').val($(this).data('name'));
    $('#E_TYPE_ID').val($(this).data('type'));
    $('#E_SN').val($(this).data('sn'));
    $('#E_STATUS').val($(this).data('status'));
    $('#E_ADDRESS').val($(this).data('address'));
    $('#previewImg').hide(); // ถ้าอยากให้โชว์รูปเก่า ต้องเพิ่ม logic ต่ออีกนิด (ดูด้านล่าง)
    $('#btnSave').data('action', 'edit');
    equipModal.show();
});


// บันทึก
$('#btnSave').click(function(){
    let action = $(this).data('action'); // Get the action (add or edit)
    let formData = new FormData($('#equipForm')[0]);
    formData.append('act', action); // Send the action as part of the form data
    $.ajax({
        url: 'actions/equipment_action.php',
 // ✅ อยู่โฟลเดอร์เดียวกัน
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
            alert('❌ บันทึกล้มเหลว;;;;:'+ xhr.statusText);
            // alert('❌ ไม่สามารถเชื่อมต่อ Server ได้: ' + xhr.statusText);
        }
    });
});

// ลบ
$('.btn-del').click(function(){
    if(!confirm('ลบอุปกรณ์นี้ใช่หรือไม่?')) return;
    let id = $(this).data('id');

    console.log('ID being sent:', id); // Add this to debug

    $.post('actions/equipment_action.php', {act:'delete', E_ID:id}, function(res){
        if(res.ok) location.reload();
        else alert('❌ ลบไม่สำเร็จ: ' + res.error);
    }, 'json').fail(function(){
        alert('❌ ไม่สามารถเชื่อมต่อ Server ได้');
    });
});

// แสดงรูป preview ตอนเลือกไฟล์ใหม่
$('#E_IMG').on('change', function(){
    const file = this.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = e => {
            $('#previewImg').attr('src', e.target.result).show();
        };
        reader.readAsDataURL(file);
    }
});

// เมื่อคลิก "แก้ไข"
$('.btn-edit').click(function(){
    let item = $(this).data('item');
    $('#E_ID').val(item.E_ID);
    $('#E_NAME').val(item.E_NAME);
    $('#E_TYPE_ID').val(item.E_TYPE_ID);
    $('#E_SN').val(item.E_SN);
    $('#E_STATUS').val(item.E_STATUS);
    $('#E_ADDRESS').val(item.E_ADDRESS);

    // แสดงรูปเดิมใน modal
    if (item.E_IMG) {
        $('#previewImg')
          .attr('src', 'data:image/jpeg;base64,' + btoa(item.E_IMG))
          .show();
    } else {
        $('#previewImg').hide();
    }

    $('#btnSave').data('action', 'edit');
    equipModal.show();
});

$('#E_HAS_IMG').val($(this).data('img'));
if ($(this).data('img') == 1) {
    const imgTag = $(this).closest('tr').find('img').attr('src');
    $('#previewImg').attr('src', imgTag).show();
} else {
    $('#previewImg').hide();
}


</script>
</body>
</html>
