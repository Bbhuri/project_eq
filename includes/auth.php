<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function current_user(){
    return $_SESSION['user'] ?? null;
}
function require_login(){
    if (!current_user()){
        header("Location: pages/login.php");
        exit;
    }
}
function require_role($roles = []){
    $u = current_user();
    if (!$u) { header("Location: pages/login.php"); exit; }
    $userRole = strtoupper($u['u_role']);
    $roles = array_map('strtoupper', (array)$roles);
    if (!in_array($userRole, $roles)){
        header("Location: index.php?err=forbidden");
        exit;
    }
}

function is_role($role){
    $u = current_user();
    return $u && strtoupper($u['u_role']) === strtoupper($role);
}
?>
