<?php
require_once __DIR__ . '/../config/db.php';

$act = $_POST['act'] ?? '';

if ($act == 'add' || $act == 'edit') {
    $E_ID = $_POST['E_ID'] ?? '';
    $E_NAME = $_POST['E_NAME'] ?? '';
    $E_TYPE_ID = $_POST['E_TYPE_ID'] ?? '';
    $E_SN = $_POST['E_SN'] ?? '';
    $E_STATUS = $_POST['E_STATUS'] ?? 'AVAILABLE';
    $E_ADDRESS = $_POST['E_ADDRESS'] ?? '';

    // จัดการไฟล์ภาพ (ถ้ามี)
    $imgData = null;
    if (isset($_FILES['E_IMG']) && $_FILES['E_IMG']['error'] == UPLOAD_ERR_OK) {
        $imgData = file_get_contents($_FILES['E_IMG']['tmp_name']);
    }
//     if (isset($_FILES['E_IMG']) && $_FILES['E_IMG']['error'] == UPLOAD_ERR_OK) {
//     $imgData = file_get_contents($_FILES['E_IMG']['tmp_name']);

//     // Debug: เขียนไฟล์ขึ้น server ดู
//     file_put_contents(__DIR__ . '/test_upload.jpg', $imgData);
// }


    if ($act == 'add') {
        // เพิ่มอุปกรณ์ใหม่
        $sql = "INSERT INTO equipments (E_NAME,E_TYPE_ID,E_SN,E_IMG,E_STATUS,E_ADDRESS)
                VALUES (?,?,?,?,?,?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssss", $E_NAME, $E_TYPE_ID, $E_SN, $imgData, $E_STATUS, $E_ADDRESS);
        $ok = $stmt->execute();
        echo json_encode(['ok' => $ok]);
        exit;
    } 


    if ($act == 'edit') {
    if ($imgData !== null) {
        $sql = "UPDATE equipments SET E_NAME=?, E_TYPE_ID=?, E_SN=?, E_IMG=?, E_STATUS=?, E_ADDRESS=? WHERE E_ID=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssssi", $E_NAME, $E_TYPE_ID, $E_SN, $imgData, $E_STATUS, $E_ADDRESS, $E_ID);
    } else {
        $sql = "UPDATE equipments SET E_NAME=?, E_TYPE_ID=?, E_SN=?, E_STATUS=?, E_ADDRESS=? WHERE E_ID=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssi", $E_NAME, $E_TYPE_ID, $E_SN, $E_STATUS, $E_ADDRESS, $E_ID);
    }
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok]);
    exit;
}

}

if ($act == 'delete') {
    $E_ID = $_POST['E_ID'] ?? '';
    $stmt = $mysqli->prepare("DELETE FROM equipments WHERE E_ID=?");
    $stmt->bind_param("i", $E_ID);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid action']);
