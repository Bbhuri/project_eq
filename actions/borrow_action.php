<?php
// actions/borrow_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action_type'] ?? '';
$u = $_SESSION['user'];

function json_resp($ok, $msg = '') {
    echo json_encode(['ok' => $ok, 'msg' => $msg]);
    exit;
}

// ----------------------------
// ✅ CREATE OR UPDATE BORROW SLIP
// ----------------------------
if ($action === 'save_borrow') {
    $b_id   = intval($_POST['b_id'] ?? 0);
    $u_idp  = $_POST['u_idp'] ?? '';
    $u_name = $_POST['u_name'] ?? '';
    $u_phone = $_POST['u_phone'] ?? '';
    $u_unit = $_POST['u_unit'] ?? '';
    $b_date_borrow = date('Y-m-d');
    $b_status = 'รอเพิ่มอุปกรณ์';

    if ($b_id > 0) {
        // 🔄 Update existing record
        $stmt = $mysqli->prepare("UPDATE borrow_slip 
            SET u_idp=?, u_name=?, u_phone=?, u_unit=?, b_date_borrow=?, b_status=? 
            WHERE b_id=?");
        $stmt->bind_param('ssssssi', $u_idp, $u_name, $u_phone, $u_unit, $b_date_borrow, $b_status, $b_id);
    } else {
        // 🆕 Insert new record
        $stmt = $mysqli->prepare("INSERT INTO borrow_slip 
            (u_idp, u_name, u_phone, u_unit, b_date_borrow, b_status)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $u_idp, $u_name, $u_phone, $u_unit, $b_date_borrow, $b_status);
    }
    if ($stmt->execute()) {
        json_resp(true);
    } else {
        json_resp(false, 'ไม่สามารถบันทึกข้อมูลได้');
    }
}

// ----------------------------
// ❌ DELETE BORROW SLIP
// ----------------------------
if ($action === 'delete_borrow') {
    $b_id = intval($_POST['b_id'] ?? 0);
    if ($b_id <= 0) json_resp(false, 'ไม่มี b_id');

    // Delete related equipment first
    $stmt = $mysqli->prepare("DELETE FROM borrow_equipment WHERE b_id=?");
    $stmt->bind_param('i', $b_id);
    $stmt->execute();

    // Then delete the slip
    $stmt = $mysqli->prepare("DELETE FROM borrow_slip WHERE b_id=? AND u_idp=?");
    $stmt->bind_param('is', $b_id, $u['u_idp']);

    if ($stmt->execute()) json_resp(true);
    else json_resp(false, 'ไม่สามารถลบได้');
}

// ----------------------------
// ➕ ADD EQUIPMENT
// ----------------------------
if ($action === 'add_equip') {
    $b_id = intval($_POST['b_id'] ?? 0);
    $e_id = intval($_POST['e_id'] ?? 0);
    if ($b_id <= 0 || $e_id <= 0) json_resp(false, 'ข้อมูลไม่ครบ');

    // Check duplicate
    $stmt = $mysqli->prepare("SELECT 1 FROM borrow_equipment WHERE b_id=? AND e_id=?");
    $stmt->bind_param('ii', $b_id, $e_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) json_resp(false, 'อุปกรณ์นี้มีแล้ว');

    // Insert new equipment
    $stmt = $mysqli->prepare("INSERT INTO borrow_equipment (b_id, e_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $b_id, $e_id);
    if ($stmt->execute()) json_resp(true);
    else json_resp(false, 'ไม่สามารถเพิ่มได้');
}

// ----------------------------
// 🗑️ DELETE EQUIPMENT
// ----------------------------
if ($action === 'delete_equip') {
    $b_id = intval($_POST['b_id'] ?? 0);
    $e_id = intval($_POST['e_id'] ?? 0);
    if ($b_id <= 0 || $e_id <= 0) json_resp(false, 'ข้อมูลไม่ครบ');

    $stmt = $mysqli->prepare("DELETE FROM borrow_equipment WHERE b_id=? AND e_id=?");
    $stmt->bind_param('ii', $b_id, $e_id);
    if ($stmt->execute()) json_resp(true);
    else json_resp(false, 'ไม่สามารถลบได้');
}

// ----------------------------
// 🔁 RETURN BORROW SLIP
// ----------------------------
if ($action === 'return_borrow') {
    $b_id = intval($_POST['b_id'] ?? 0);
    if ($b_id <= 0) json_resp(false, 'ไม่มี b_id');

    $stmt = $mysqli->prepare("UPDATE borrow_slip SET b_status='คืนแล้ว' WHERE b_id=? AND u_idp=?");
    $stmt->bind_param('is', $b_id, $u['u_idp']);
    if ($stmt->execute()) json_resp(true);
    else json_resp(false, 'ไม่สามารถคืนได้');
}

// ----------------------------
// 📋 LIST EQUIPMENT (JSON)
// ----------------------------
if ($action === 'list_equip') {
    $b_id = intval($_GET['b_id'] ?? 0);
    if ($b_id <= 0) json_resp(false, 'ไม่มี b_id');

    $sql = "SELECT e.E_ID as e_id, e.E_NAME 
            FROM borrow_equipment be 
            JOIN equipment e ON be.e_id = e.E_ID
            WHERE be.b_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $b_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $arr = [];
    while ($row = $res->fetch_assoc()) $arr[] = $row;
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

json_resp(false, 'ไม่รู้จัก action');
