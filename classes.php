<?php
header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli("localhost", "root", "", "gym_project");
if ($mysqli->connect_error) {
    echo json_encode([]);
    exit;
}



$sql = "
  SELECT 
    c.class_name,
    c.schedule,
    t.trainer_name AS trainer_name,
    g.name         AS gym_name
  FROM class c
  JOIN trainer t ON c.trainer_id = t.trainer_id
  JOIN gym     g ON c.gym_id     = g.gym_id
";

$result = $mysqli->query($sql);

$classes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = [
            'class_name'   => $row['class_name'],
            'schedule'     => $row['schedule'],
            'trainer_name' => $row['trainer_name'],
            'gym_name'     => $row['gym_name'],
        ];
    }
}

echo json_encode($classes);