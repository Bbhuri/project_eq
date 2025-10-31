<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['ADMIN']);

$act = $_POST['act'] ?? '';
$u_id = intval($_POST['u_id'] ?? 0);
$u_idp = trim($_POST['u_idp'] ?? '');
$u_username = trim($_POST['u_username'] ?? '');
$u_password = trim($_POST['u_password'] ?? '');
$u_name = trim($_POST['u_name'] ?? '');
$u_unit = trim($_POST['u_unit'] ?? '');
$u_role = trim($_POST['u_role'] ?? 'USER');

if ($act == 'save') {

    if ($u_id == 0) {
        // ✅ เพิ่มผู้ใช้ใหม่
        if ($u_username == '' || $u_password == '') {
            echo json_encode(['ok' => false, 'msg' => 'กรอกชื่อผู้ใช้และรหัสผ่าน']);
            exit;
        }

        // ตรวจซ้ำ username
        $stmt = $mysqli->prepare("SELECT u_id FROM users WHERE u_username=?");
        $stmt->bind_param("s", $u_username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['ok' => false, 'msg' => 'ชื่อผู้ใช้ซ้ำ']);
            exit;
        }

        $hashed = password_hash($u_password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (u_idp,u_username,u_password,u_name,u_unit,u_role) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $u_idp, $u_username, $hashed, $u_name, $u_unit, $u_role);
        $ok = $stmt->execute();

        echo json_encode(['ok' => $ok]);
        exit;
    } else {
        // ✅ แก้ไขผู้ใช้เดิม
        if ($u_password != '') {
            $hashed = password_hash($u_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET u_idp=?, u_username=?, u_password=?, u_name=?, u_unit=?, u_role=? WHERE u_id=?");
            $stmt->bind_param("ssssssi", $u_idp, $u_username, $hashed, $u_name, $u_unit, $u_role, $u_id);
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET u_idp=?, u_username=?, u_name=?, u_unit=?, u_role=? WHERE u_id=?");
            $stmt->bind_param("sssssi", $u_idp, $u_username, $u_name, $u_unit, $u_role, $u_id);
        }
        $ok = $stmt->execute();
        echo json_encode(['ok' => $ok]);
        exit;
    }

} elseif ($act == 'delete') {
    // ✅ ลบผู้ใช้
    if ($u_id == 0) {
        echo json_encode(['ok' => false, 'msg' => 'ไม่พบ ID']);
        exit;
    }
    $stmt = $mysqli->prepare("DELETE FROM users WHERE u_id=?");
    $stmt->bind_param("i", $u_id);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok]);
    exit;
} else {
    echo json_encode(['ok' => false, 'msg' => 'Invalid action']);
}
