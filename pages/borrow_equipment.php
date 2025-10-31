<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$b_id = isset($_GET['b_id']) ? (int)$_GET['b_id'] : 0;
if($b_id === 0){
    die("ไม่พบใบยืม");
}

// ดึงอุปกรณ์ทั้งหมด
$equipments = $mysqli->query("SELECT * FROM equipments ORDER BY E_ID DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เพิ่มอุปกรณ์ในใบยืม #<?= $b_id ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">

<h3>📦 เพิ่มอุปกรณ์ในใบยืม #<?= $b_id ?></h3>

<h5>อุปกรณ์ทั้งหมด</h5>
<table class="table table-bordered">
<thead>
<tr>
    <th>ID</th>
    <th>ชื่ออุปกรณ์</th>
    <th>สถานะ</th>
    <th>การกระทำ</th>
</tr>
</thead>
<tbody>
<?php while($r = $equipments->fetch_assoc()): ?>
<tr>
    <td><?= $r['E_ID'] ?></td>
    <td><?= htmlspecialchars($r['E_NAME']) ?></td>
    <td><?= htmlspecialchars($r['E_STATUS']) ?></td>
    <td>
        <button class="btn btn-primary btn-sm add-to-borrow"
            data-eid="<?= $r['E_ID'] ?>" 
            data-bid="<?= $b_id ?>">
            + เพิ่มอุปกรณ์
        </button>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<h5>รายการอุปกรณ์ในใบยืม</h5>
<table class="table table-bordered" id="borrowedItems">
<thead>
<tr>
    <th>ID</th>
    <th>ชื่ออุปกรณ์</th>
    <th>สถานะ</th>
    <th>การกระทำ</th>
</tr>
</thead>
<tbody>
<tr><td colspan="4" class="text-center">กำลังโหลด...</td></tr>
</tbody>
</table>

<script>
const ajaxUrl = '../actions/borrow_equipment_action.php';
const b_id = <?= $b_id ?>;

// โหลดรายการอุปกรณ์ในใบยืม
function loadBorrowedItems(){
    $.getJSON(ajaxUrl, {action_type:'list_items', b_id:b_id}, function(res){
        let tbody = $('#borrowedItems tbody');
        tbody.empty();
        if(res.ok && res.items.length){
            res.items.forEach(item=>{
                tbody.append(`
<tr>
    <td>${item.E_ID}</td>
    <td>${item.E_NAME}</td>
    <td>${item.E_STATUS}</td>
    <td>
        <button class="btn btn-danger btn-sm remove-item" data-eid="${item.E_ID}">ลบ</button>
    </td>
</tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="4" class="text-center">ยังไม่มีอุปกรณ์</td></tr>');
        }
    });
}

// เพิ่มอุปกรณ์
$(document).on('click', '.add-to-borrow', function(){
    const E_ID = $(this).data('eid');
    $.post(ajaxUrl, {action_type:'add_to_borrow', b_id:b_id, E_ID:E_ID}, function(res){
        if(res.ok){
            alert('✅ เพิ่มอุปกรณ์เรียบร้อย');
            loadBorrowedItems();
        } else {
            alert('❌ ไม่สามารถเพิ่มอุปกรณ์: ' + (res.error || res.msg));
        }
    }, 'json');
});
// ลบอุปกรณ์
$(document).on('click', '.remove-item', function(){
    const E_ID = $(this).data('eid');
    if(!confirm('คุณต้องการลบอุปกรณ์นี้ออกจากใบยืมหรือไม่?')) return;
    $.post(ajaxUrl, {action_type:'remove_item', b_id:b_id, E_ID:E_ID}, function(res){
        if(res.ok){
            loadBorrowedItems();
        } else {
            alert('❌ ไม่สามารถลบอุปกรณ์: ' + (res.error || res.msg));
        }
    }, 'json');
});

// โหลดครั้งแรก
loadBorrowedItems();
</script>

</body>
</html>
