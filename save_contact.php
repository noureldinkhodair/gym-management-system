<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "gym_project"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo "DB error";
    exit;
}

$name    = $_POST['name']         ?? '';
$email   = $_POST['email']        ?? '';
$phone   = $_POST['phone_number'] ?? '';
$message = $_POST['message']      ?? '';

if (!$name || !$email || !$phone || !$message) {
    http_response_code(400);
    echo "Missing fields";
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO contact (name, email, phone_number, message) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("ssss", $name, $email, $phone, $message);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Insert error";
}

$stmt->close();
$conn->close();
?>