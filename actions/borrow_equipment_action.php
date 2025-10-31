<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json');

$action_type = $_GET['action_type'] ?? $_POST['action_type'] ?? '';

// ========================
// list_items
// ========================
if($action_type === 'list_items'){
    $b_id = intval($_GET['b_id'] ?? 0);
    if($b_id === 0){
        echo json_encode(['ok'=>false,'error'=>'ไม่พบ ID ใบยืม']);
        exit;
    }

    $stmt = $mysqli->prepare("
        SELECT e.E_ID, e.E_NAME, e.E_STATUS
        FROM borrow_equipment be
        JOIN equipments e ON be.E_ID = e.E_ID
        WHERE be.B_ID=?
    ");
    $stmt->bind_param('i', $b_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok'=>true,'items'=>$items]);
    exit;
}

// ========================
// add_to_borrow
// ========================
if($action_type === 'add_to_borrow'){
    $b_id = intval($_POST['b_id'] ?? 0);
    $E_ID = intval($_POST['E_ID'] ?? 0);

    if($b_id === 0 || $E_ID === 0){
        echo json_encode(['ok'=>false,'error'=>'ข้อมูลไม่ครบ']);
        exit;
    }

    // ตรวจสอบซ้ำ
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM borrow_equipment WHERE B_ID=? AND E_ID=?");
    $stmt->bind_param('ii', $b_id, $E_ID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if($count > 0){
        echo json_encode(['ok'=>false,'error'=>'อุปกรณ์นี้มีอยู่แล้วในใบยืม']);
        exit;
    }

    // insert
    $stmt = $mysqli->prepare("INSERT INTO borrow_equipment (B_ID,E_ID) VALUES (?,?)");
    $stmt->bind_param('ii', $b_id, $E_ID);
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok, 'error'=>$stmt->error]);
    exit;
}

// ========================
// remove_item
// ========================
if($action_type === 'remove_item'){
    $b_id = intval($_POST['b_id'] ?? 0);
    $E_ID = intval($_POST['E_ID'] ?? 0);
    if($b_id === 0 || $E_ID === 0){
        echo json_encode(['ok'=>false,'error'=>'ข้อมูลไม่ครบ']);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM borrow_equipment WHERE B_ID=? AND E_ID=?");
    $stmt->bind_param('ii', $b_id, $E_ID);
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok]);
    exit;
}

// ถ้า action_type ไม่ตรง
echo json_encode(['ok'=>false,'error'=>'Action ไม่ถูกต้อง']);
