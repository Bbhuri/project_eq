<?php
require_once __DIR__ . '/config/db.php';
$eid = (int)($_GET['eid'] ?? 0);
$rs = $mysqli->query("SELECT E_IMG FROM equipment WHERE E_ID={$eid}");
if ($r = $rs->fetch_assoc()) {
    header("Content-Type: image/jpeg");
    echo $r['E_IMG'];
}
?>
