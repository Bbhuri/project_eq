<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$act = $_POST['act'] ?? '';



if ($_POST['act'] === 'add') {
    // Handle the "Add" action
    $E_NAME = $_POST['E_NAME'];
    $E_TYPE_ID = $_POST['E_TYPE_ID'];
    $E_SN = $_POST['E_SN'];
    $E_STATUS = $_POST['E_STATUS'];
    $E_ADDRESS = $_POST['E_ADDRESS'];
    $E_IMG = $_FILES['E_IMG']['tmp_name'] ? file_get_contents($_FILES['E_IMG']['tmp_name']) : null;

    $stmt = $mysqli->prepare("INSERT INTO equipments (E_NAME, E_TYPE_ID, E_SN, E_STATUS, E_ADDRESS, E_IMG) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssb", $E_NAME, $E_TYPE_ID, $E_SN, $E_STATUS, $E_ADDRESS, $E_IMG);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Unable to add item']);
    }
} elseif ($_POST['act'] === 'edit') {
    // Handle the "Edit" action
    $E_ID = $_POST['E_ID'];
    $E_NAME = $_POST['E_NAME'];
    $E_TYPE_ID = $_POST['E_TYPE_ID'];
    $E_SN = $_POST['E_SN'];
    $E_STATUS = $_POST['E_STATUS'];
    $E_ADDRESS = $_POST['E_ADDRESS'];
    $E_IMG = $_FILES['E_IMG']['tmp_name'] ? file_get_contents($_FILES['E_IMG']['tmp_name']) : null;

    $stmt = $mysqli->prepare("UPDATE equipments SET E_NAME = ?, E_TYPE_ID = ?, E_SN = ?, E_STATUS = ?, E_ADDRESS = ?, E_IMG = ? WHERE E_ID = ?");
    $stmt->bind_param("sssssbi", $E_NAME, $E_TYPE_ID, $E_SN, $E_STATUS, $E_ADDRESS, $E_IMG, $E_ID);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Unable to update item']);
    }
}
elseif ($act === 'delete') {
    $E_ID = $_POST['E_ID'] ?? '';
    if (!$E_ID) {
        echo json_encode(['ok' => false, 'error' => 'ไม่มี ID']);
        exit;
    }
    
    // Use a prepared statement for deletion
    $stmt = $mysqli->prepare("DELETE FROM equipments WHERE E_ID = ?");
    $stmt->bind_param("i", $E_ID);  // 'i' means integer type
    
    if ($stmt->execute()) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => $stmt->error]);
    }
}
else{
    echo json_encode(['ok'=>false,'error'=>'Action ไม่ถูกต้อง']);
}
