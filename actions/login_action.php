<?php
session_start();

// --- Database connection ---
$mysqli = new mysqli("localhost", "root", "", "public");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// --- Get POST data safely ---
$u_username = trim($_POST['username'] ?? '');
$u_password = trim($_POST['password'] ?? '');
// --- Debug (optional) ---
// echo "<pre>"; print_r($_POST); echo "</pre>";

// --- Validate input ---
if (empty($u_username) || empty($u_password)) {
    echo "Please enter both username and password.";
    exit;
}

// --- Query user ---
$stmt = $mysqli->prepare("SELECT * FROM users WHERE u_username = ? LIMIT 1");
if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt->bind_param("s", $u_username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// --- Check user and password ---
if ($user && password_verify($u_password, $user['u_password'])) {
    // --- Login success ---
    $_SESSION['user'] = [
        'u_id' => $user['u_id'],
        'u_idp' => $user['u_idp'],
        'u_username' => $user['u_username'],
        'u_name' => $user['u_name'],
        'u_unit' => $user['u_unit'],
        'u_role' => $user['u_role']
    ];
    header("Location: ../index.php");
    exit;
} else {
    echo "Invalid username or password.";
    exit;
}
