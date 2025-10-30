<?php
// actions/user_action.php
require_once __DIR__ . '/../config/db.php';
session_start();

// Debug: log POST payload
file_put_contents('c:\xampp\htdocs\project_eq\logs\user_action_payload.log', date('Y-m-d H:i:s') . " " . print_r($_POST, true) . "\n", FILE_APPEND);

$act = $_POST['act'] ?? '';
if ($act === 'save'){
    $u_id = (int)($_POST['u_id'] ?? 0);
    $u_username = $_POST['u_username'] ?? '';
    $u_password = $_POST['u_password'] ?? '';
    $u_naem = $_POST['u_name'] ?? '';
    $u_unit = $_POST['u_unit'] ?? '';
    $u_role = $_POST['u_role'] ?? null;

    if ($u_id > 0){
        if ($password !== '') {
            $stmt = $mysqli->prepare("UPDATE users SET u_username=?, u_password=?, u_name=?, u_role=? WHERE u_id=?");
            $stmt->bind_param("ssssi", $u_username, $u_password, $u_name, $u_role,$u_unit, $u_id);
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET u_username=?, u_name=?, u_unit=?, u_role=? WHERE u_id=?");
            $stmt->bind_param("sssi", $u_username, $u_name, $u_role,u_unit, $u_id);
        }
        $ok = $stmt->execute();
        echo json_encode(['ok'=> (bool)$ok]);
        exit;
    } else {
        $stmt = $mysqli->prepare("INSERT INTO users (u_username,u_password,u_name,u_unit,_role) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssss", $u_username, $u_password, $u_name,$u_unit, $u_role);
        $ok = $stmt->execute();
        echo json_encode(['ok'=> (bool)$ok, 'id'=>$mysqli->insert_id]);
        exit;
    }
} elseif ($act === 'delete'){
    $u_id = (int)($_POST['u_id'] ?? 0);
    $stmt = $mysqli->prepare("DELETE FROM users WHERE u_id=?");
    $stmt->bind_param("i",$u_id);
    $ok = $stmt->execute();
    echo json_encode(['ok'=> (bool)$ok]);
    exit;
}
echo json_encode(['ok'=>false,'msg'=>'unknown action']);
exit;
