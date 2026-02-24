<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "gym_project";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name            = $_POST['name']            ?? '';
$gmail           = $_POST['gmail']           ?? '';
$phone_number    = $_POST['phone_number']    ?? '';
$password_plain  = $_POST['password']        ?? '';
$age             = $_POST['age']             ?? null;
$subscription_id = $_POST['subscription_id'] ?? null;
$gym_id          = $_POST['gym_id']          ?? null;
$mode            = $_POST['mode']            ?? ''; 

function respond($ok, $message = '', $member_id = null, $mode = '') {
    if ($mode === 'api') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'   => $ok,
            'message'   => $message,
            'member_id' => $member_id
        ]);
    } else {
        echo $ok ? ($message ?: "Registration successful.") : ($message ?: "Registration failed.");
    }
    exit;
}

if (
    empty($name) || empty($gmail) || empty($phone_number) ||
    empty($password_plain) || empty($age) ||
    empty($subscription_id) || empty($gym_id)
) {
    respond(false, "Error: missing required data.", null, $mode);
}

$checkSql  = "SELECT COUNT(*) FROM members WHERE gmail = ?";
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    respond(false, "Prepare failed (email check): " . $conn->error, null, $mode);
}
$checkStmt->bind_param("s", $gmail);
$checkStmt->execute();
$checkStmt->bind_result($emailCount);
$checkStmt->fetch();
$checkStmt->close();

if ($emailCount > 0) {
    respond(false, "Email already exists. Please use another email.", null, $mode);
}

$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

$start = new DateTime();
$start_date = $start->format('Y-m-d');

$end = clone $start;
if ($subscription_id == 1) {
    $end->modify('+1 month');
} elseif ($subscription_id == 6) {
    $end->modify('+6 months');
} elseif ($subscription_id == 12) {
    $end->modify('+12 months');
}
$end_date = $end->format('Y-m-d');

$sql = "INSERT INTO members
        (name, phone_number, gmail, password_hash, age,
         subscription_id, gym_id, start_date, end_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    respond(false, "Prepare failed: " . $conn->error, null, $mode);
}

$stmt->bind_param(
    "ssssiiiss",
    $name,
    $phone_number,
    $gmail,
    $password_hash,
    $age,
    $subscription_id,
    $gym_id,
    $start_date,
    $end_date
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    respond(true, "Registration successful.", $newId, $mode);
} else {
    respond(false, "Error: " . $stmt->error, null, $mode);
}

$stmt->close();
$conn->close();
?>