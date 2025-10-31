<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json');
$user = $_SESSION['user'];

$action = $_POST['action_type'] ?? '';

try {
    switch($action){

        // เพิ่ม/แก้ไขใบยืม
        case 'save_borrow':
            $b_id = intval($_POST['b_id'] ?? 0);
            $u_idp = $_POST['u_idp'] ?? '';
            $u_name = $_POST['u_name'] ?? '';
            $u_phone = $_POST['u_phone'] ?? '';
            $u_unit = $_POST['u_unit'] ?? '';

            if(empty($u_name) || empty($u_phone)){
                throw new Exception('กรุณากรอกชื่อและเบอร์โทร');
            }

            if($b_id > 0){
                // แก้ไขใบยืม
                $stmt = $mysqli->prepare("UPDATE borrow_slip SET u_name=?, u_phone=?, u_unit=? WHERE b_id=? AND u_idp=?");
                $stmt->bind_param('sssis', $u_name, $u_phone, $u_unit, $b_id, $u_idp);
                $stmt->execute();
            } else {
                // เพิ่มใบยืมใหม่
                $stmt = $mysqli->prepare("INSERT INTO borrow_slip (u_idp, u_name, u_phone, u_unit, b_date_borrow, b_status) VALUES (?, ?, ?, ?, NOW(), 'รอเพิ่มอุปกรณ์')");
                $stmt->bind_param('ssss', $u_idp, $u_name, $u_phone, $u_unit);
                $stmt->execute();
            }
            echo json_encode(['ok'=>true]);
            break;

        // ลบใบยืม
        case 'delete_borrow':
            $b_id = intval($_POST['b_id'] ?? 0);
            if($b_id <= 0) throw new Exception('ID ไม่ถูกต้อง');
            $mysqli->query("DELETE FROM borrow_equipment WHERE b_id=$b_id");
            $mysqli->query("DELETE FROM borrow_slip WHERE b_id=$b_id AND u_idp='{$user['u_idp']}'");
            echo json_encode(['ok'=>true]);
            break;

        // เพิ่มอุปกรณ์
        case 'add_equip':
            $b_id = intval($_POST['b_id'] ?? 0);
            $e_id = intval($_POST['e_id'] ?? 0);
            if($b_id<=0 || $e_id<=0) throw new Exception('ข้อมูลไม่ถูกต้อง');
            $chk = $mysqli->query("SELECT COUNT(*) as cnt FROM borrow_equipment WHERE b_id=$b_id AND e_id=$e_id")->fetch_assoc()['cnt'];
            if($chk==0){
                $stmt=$mysqli->prepare("INSERT INTO borrow_equipment (b_id, e_id) VALUES (?, ?)");
                $stmt->bind_param('ii', $b_id, $e_id);
                $stmt->execute();
            }
            echo json_encode(['ok'=>true]);
            break;

        // ลบอุปกรณ์
        case 'delete_equip':
            $b_id = intval($_POST['b_id'] ?? 0);
            $e_id = intval($_POST['e_id'] ?? 0);
            if($b_id<=0 || $e_id<=0) throw new Exception('ข้อมูลไม่ถูกต้อง');
            $stmt=$mysqli->prepare("DELETE FROM borrow_equipment WHERE b_id=? AND e_id=?");
            $stmt->bind_param('ii',$b_id,$e_id);
            $stmt->execute();
            echo json_encode(['ok'=>true]);
            break;

        // โหลดอุปกรณ์
        case 'list_equip':
            $b_id = intval($_GET['b_id'] ?? 0);
            $res = $mysqli->query("SELECT be.e_id, e.e_name AS E_NAME 
                                   FROM borrow_equipment be 
                                   JOIN equipment e ON be.e_id = e.e_id 
                                   WHERE be.b_id=$b_id");
            $arr=[];
            while($row=$res->fetch_assoc()) $arr[]=$row;
            echo json_encode($arr);
            break;

        // ส่งคำขออนุมัติ
        case 'submit_borrow':
            $b_id = intval($_POST['b_id'] ?? 0);
            if($b_id<=0) throw new Exception('ID ไม่ถูกต้อง');
            $stmt = $mysqli->prepare("UPDATE borrow_slip SET b_status='รอเจ้าหน้าที่อนุมัติ' WHERE b_id=? AND u_idp=?");
            $stmt->bind_param('is', $b_id, $user['u_idp']);
            $stmt->execute();
            echo json_encode(['ok'=>true]);
            break;
            //ส่งคำขอ
case  'send_request' :
    $b_id = intval($_POST['b_id'] ?? 0);
    $u_idp = $_SESSION['user']['u_idp'];
    if($b_id > 0){
        $stmt = $mysqli->prepare("UPDATE borrow_slip SET b_status=1 WHERE b_id=? AND u_idp=?");
        $stmt->bind_param('is', $b_id, $u_idp);
        $ok = $stmt->execute();
        echo json_encode(['ok'=>$ok, 'error'=>$stmt->error]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'ไม่พบ ID']);
    }
    break;

        default:
            throw new Exception('Action ไม่ถูกต้อง');
    }
}catch(Exception $e){
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}



