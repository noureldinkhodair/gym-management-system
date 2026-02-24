<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "gym_project";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$usertype = $_POST['usertype'] ?? 'member';
$email    = trim($_POST['email'] ?? '');
$pass     = trim($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
    echo json_encode(["success" => false, "message" => "Missing email or password"]);
    exit;
}

if ($usertype === 'member') {
    $sql  = "SELECT 
                m.member_id,
                m.name,
                m.phone_number,
                m.gmail,
                m.age,
                m.gym_id,
                g.name AS gym_name,
                m.start_date,
                m.end_date,
                m.subscription_id,
                mb.freeze_days_left      AS freeze_left,
                mb.invitations_left      AS invitations_left,
                mb.inbody_scans_left     AS inbody_left,
                mb.private_sessions_left AS private_sessions_left,
                m.password_hash
             FROM members m
             JOIN gym g ON m.gym_id = g.gym_id
             LEFT JOIN member_benefits mb ON m.member_id = mb.member_id
             WHERE m.gmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $type = "user";
} else {
    $sql  = "SELECT 
                staff_id, 
                name, 
                email
             FROM staff
             WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $type = "admin";
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if ($usertype === 'member') {
        if (!password_verify($pass, $user['password_hash'])) {
            echo json_encode([
                "success" => false,
                "message" => "Wrong email or password"
            ]);
            exit;
        }
        unset($user['password_hash']);
    } else {
        if ($pass !== 'admin101010') {
            echo json_encode([
                "success" => false,
                "message" => "Wrong email or password"
            ]);
            exit;
        }
    }

    $_SESSION['type'] = $type;
    $_SESSION['user'] = $user;

    echo json_encode([
        "success" => true,
        "type"    => $type,
        "user"    => $user
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Wrong email or password"
    ]);
}

$stmt->close();
$conn->close();