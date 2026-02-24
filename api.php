<?php
header('Content-Type: application/json; charset=utf-8');

$host    = 'localhost';
$db      = 'gym_project';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}


$entity = $_GET['entity'] ?? '';
$action = $_GET['action'] ?? '';

if (!$entity || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing entity or action']);
    exit;
}

switch ($entity) {

    case 'gym':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'list') {
            $stmt = $pdo->query("SELECT gym_id, name, address, gym_phone FROM gym ORDER BY gym_id ASC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'search') {
            $q = $input['query'] ?? '';

            if ($q !== '' && ctype_digit($q)) {
                $stmt = $pdo->prepare("
                    SELECT gym_id, name, address, gym_phone
                    FROM gym
                    WHERE gym_id = :id
                ");
                $stmt->execute([':id' => (int)$q]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT gym_id, name, address, gym_phone
                    FROM gym
                    WHERE name      LIKE :q
                       OR address   LIKE :q
                       OR gym_phone LIKE :q
                ");
                $stmt->execute([':q' => "%{$q}%"]);
            }

            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $name      = $input['name']      ?? '';
            $address   = $input['address']   ?? '';
            $gym_phone = $input['gym_phone'] ?? '';

            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Name required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO gym (name, address, gym_phone) VALUES (?, ?, ?)");
            $stmt->execute([$name, $address, $gym_phone]);
            $id = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'gym' => [
                    'gym_id'    => $id,
                    'name'      => $name,
                    'address'   => $address,
                    'gym_phone' => $gym_phone
                ]
            ]);
            exit;
        }

        if ($action === 'update') {
            $gym_id    = $input['gym_id']    ?? null;
            $name      = $input['name']      ?? '';
            $address   = $input['address']   ?? '';
            $gym_phone = $input['gym_phone'] ?? '';

            if (!$gym_id) {
                echo json_encode(['success' => false, 'error' => 'gym_id required']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE gym SET name = ?, address = ?, gym_phone = ? WHERE gym_id = ?");
            $stmt->execute([$name, $address, $gym_phone, $gym_id]);

            echo json_encode([
                'success' => true,
                'gym' => [
                    'gym_id'    => $gym_id,
                    'name'      => $name,
                    'address'   => $address,
                    'gym_phone' => $gym_phone
                ]
            ]);
            exit;
        }

        if ($action === 'delete') {
            $gym_id = $input['gym_id'] ?? null;
            if (!$gym_id) {
                echo json_encode(['success' => false, 'error' => 'gym_id required']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM gym WHERE gym_id = ?");
            $stmt->execute([$gym_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;


        case 'members':

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM members ORDER BY member_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $q = "%$q%";
            $stmt = $pdo->prepare(
                "SELECT * FROM members
                 WHERE name LIKE :q
                    OR gmail LIKE :q
                    OR phone_number LIKE :q
                    OR CAST(member_id AS CHAR) LIKE :q"
            );
            $stmt->execute([':q' => $q]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO members
                        (name, phone_number, gmail, age, gym_id, subscription_id, start_date, end_date)
                    VALUES
                        (:name, :phone, :gmail, :age, :gymid, :subId, :start, :end)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'  => $input['name'] ?? '',
                ':phone' => $input['phone_number'] ?? $input['phonenumber'] ?? '',
                ':gmail' => $input['gmail'] ?? '',
                ':age'   => $input['age'] ?? null,
                ':gymid' => $input['gym_id'] ?? $input['gymid'] ?? null,
                ':subId' => $input['subscription_id'] ?? $input['subscriptionid'] ?? null,
                ':start' => $input['start_date'] ?? $input['startdate'] ?? null,
                ':end'   => $input['end_date'] ?? $input['enddate'] ?? null
            ]);

            if (!$ok) {
                echo json_encode(["success" => false, "error" => "Insert failed"]);
                exit;
            }

            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(["success" => true, "member" => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {

            if (empty($input['member_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing member_id']);
                exit;
            }

            $memberId = $input['member_id'];
            $gmail    = $input['gmail'] ?? '';

            $check = $pdo->prepare("
                SELECT member_id 
                FROM members 
                WHERE gmail = :gmail AND member_id <> :member_id
            ");
            $check->execute([
                ':gmail'     => $gmail,
                ':member_id' => $memberId
            ]);

            if ($check->fetch()) {
                echo json_encode([
                    'success' => false,
                    'error'   => 'Email already exists'
                ]);
                exit;
            }

            $sql = "UPDATE members
                    SET name = :name,
                        phone_number = :phone_number,
                        gmail = :gmail,
                        age = :age,
                        gym_id = :gym_id,
                        subscription_id = :subscription_id,
                        start_date = :start_date,
                        end_date = :end_date
                    WHERE member_id = :member_id";

            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'            => $input['name'] ?? '',
                ':phone_number'    => $input['phone_number'] ?? '',
                ':gmail'           => $gmail,
                ':age'             => $input['age'] ?? null,
                ':gym_id'          => $input['gym_id'] ?? null,
                ':subscription_id' => $input['subscription_id'] ?? null,
                ':start_date'      => $input['start_date'] ?? null,
                ':end_date'        => $input['end_date'] ?? null,
                ':member_id'       => $memberId,
            ]);

            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }

            $stmt2 = $pdo->prepare("
                SELECT 
                    m.member_id,
                    m.name,
                    m.phone_number,
                    m.gmail,
                    m.age,
                    m.gym_id,
                    g.name AS gym_name,
                    m.subscription_id,
                    m.start_date,
                    m.end_date,
                    mb.freeze_days_left      AS freeze_left,
                    mb.invitations_left      AS invitations_left,
                    mb.inbody_scans_left     AS inbody_left,
                    mb.private_sessions_left AS private_sessions_left
                FROM members m
                JOIN gym g ON m.gym_id = g.gym_id
                LEFT JOIN member_benefits mb ON m.member_id = mb.member_id
                WHERE m.member_id = ?
            ");
            $stmt2->execute([$memberId]);

            echo json_encode(['success' => true, 'member' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $memberId = $input['member_id'] ?? null;
            if (!$memberId) {
                echo json_encode(["success" => false, "error" => "Missing member_id"]);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
            $ok = $stmt->execute([$memberId]);
            echo json_encode(["success" => (bool)$ok]);
            exit;
        }

        echo json_encode(["success" => false, "error" => "Unknown action"]);
        exit;


    case 'subscriptions':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY subscription_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM subscriptions
                WHERE name LIKE :q
                   OR CAST(subscription_id AS CHAR) LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO subscriptions
                    (name, price, max_freeze_days, max_invitations, max_inbody_scans, max_private_sessions)
                    VALUES
                    (:name, :price, :max_freeze_days, :max_invitations, :max_inbody_scans, :max_private_sessions)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'                 => $input['name']                 ?? '',
                ':price'                => $input['price']                ?? 0,
                ':max_freeze_days'      => $input['max_freeze_days']      ?? 0,
                ':max_invitations'      => $input['max_invitations']      ?? 0,
                ':max_inbody_scans'     => $input['max_inbody_scans']     ?? 0,
                ':max_private_sessions' => $input['max_private_sessions'] ?? 0,
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM subscriptions WHERE subscription_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'subscription' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {
            if (empty($input['subscription_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing subscription_id']);
                exit;
            }
            $sql = "UPDATE subscriptions
                    SET name = :name,
                        price = :price,
                        max_freeze_days = :max_freeze_days,
                        max_invitations = :max_invitations,
                        max_inbody_scans = :max_inbody_scans,
                        max_private_sessions = :max_private_sessions
                    WHERE subscription_id = :subscription_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'                 => $input['name']                 ?? '',
                ':price'                => $input['price']                ?? 0,
                ':max_freeze_days'      => $input['max_freeze_days']      ?? 0,
                ':max_invitations'      => $input['max_invitations']      ?? 0,
                ':max_inbody_scans'     => $input['max_inbody_scans']     ?? 0,
                ':max_private_sessions' => $input['max_private_sessions'] ?? 0,
                ':subscription_id'      => $input['subscription_id'],
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }
            $stmt2 = $pdo->prepare("SELECT * FROM subscriptions WHERE subscription_id = ?");
            $stmt2->execute([$input['subscription_id']]);
            echo json_encode(['success' => true, 'subscription' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $subscription_id = $input['subscription_id'] ?? null;
            if (!$subscription_id) {
                echo json_encode(['success' => false, 'error' => 'Missing subscription_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE subscription_id = ?");
            $ok = $stmt->execute([$subscription_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'staff':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM staff ORDER BY staff_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM staff
                WHERE name LIKE :q
                   OR role LIKE :q
                   OR phone_number LIKE :q
                   OR email LIKE :q
                   OR CAST(staff_id AS CHAR) LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO staff
                    (name, role, phone_number, email, start_time, salary, gym_id)
                    VALUES
                    (:name, :role, :phone, :email, :start_time, :salary, :gym_id)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'       => $input['name']       ?? '',
                ':role'       => $input['role']       ?? '',
                ':phone'      => $input['phone']      ?? '',
                ':email'      => $input['email']      ?? '',
                ':start_time' => $input['start_time'] ?? null,
                ':salary'     => $input['salary']     ?? null,
                ':gym_id'     => $input['gym_id']     ?? null,
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'staff' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {
            if (empty($input['staff_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing staff_id']);
                exit;
            }
            $sql = "UPDATE staff
                    SET name = :name,
                        role = :role,
                        phone_number = :phone,
                        email = :email,
                        start_time = :start_time,
                        salary = :salary,
                        gym_id = :gym_id
                    WHERE staff_id = :staff_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'       => $input['name']       ?? '',
                ':role'       => $input['role']       ?? '',
                ':phone'      => $input['phone']      ?? '',
                ':email'      => $input['email']      ?? '',
                ':start_time' => $input['start_time'] ?? null,
                ':salary'     => $input['salary']     ?? null,
                ':gym_id'     => $input['gym_id']     ?? null,
                ':staff_id'   => $input['staff_id'],
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }
            $stmt2 = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
            $stmt2->execute([$input['staff_id']]);
            echo json_encode(['success' => true, 'staff' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $staff_id = $input['staff_id'] ?? null;
            if (!$staff_id) {
                echo json_encode(['success' => false, 'error' => 'Missing staff_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
            $ok = $stmt->execute([$staff_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'payment':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM payment ORDER BY payment_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM payment
                WHERE CAST(member_id  AS CHAR) LIKE :q
                   OR CAST(amount     AS CHAR) LIKE :q
                   OR date LIKE :q
                   OR CAST(payment_id AS CHAR) LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO payment (member_id, amount, date)
                    VALUES (:member_id, :amount, :date)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':member_id' => $input['member_id'] ?? null,
                ':amount'    => $input['amount']    ?? 0,
                ':date'      => $input['date']      ?? null,
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM payment WHERE payment_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'payment' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {
            if (empty($input['payment_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing payment_id']);
                exit;
            }
            $sql = "UPDATE payment
                    SET member_id = :member_id,
                        amount    = :amount,
                        date      = :date
                    WHERE payment_id = :payment_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':member_id'  => $input['member_id']  ?? null,
                ':amount'     => $input['amount']     ?? 0,
                ':date'       => $input['date']       ?? null,
                ':payment_id' => $input['payment_id'],
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }
            $stmt2 = $pdo->prepare("SELECT * FROM payment WHERE payment_id = ?");
            $stmt2->execute([$input['payment_id']]);
            echo json_encode(['success' => true, 'payment' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $payment_id = $input['payment_id'] ?? null;
            if (!$payment_id) {
                echo json_encode(['success' => false, 'error' => 'Missing payment_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM payment WHERE payment_id = ?");
            $ok = $stmt->execute([$payment_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'trainer':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM trainer ORDER BY trainer_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM trainer
                WHERE trainer_name LIKE :q
                   OR phone_number  LIKE :q
                   OR email         LIKE :q
                   OR CAST(trainer_id AS CHAR) LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO trainer (trainer_name, phone_number, email, start_time, salary, gym_id)
                    VALUES (:trainer_name, :phone_number, :email, :start_time, :salary, :gym_id)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':trainer_name' => $input['trainer_name'] ?? null,
                ':phone_number' => $input['phone_number'] ?? null,
                ':email'        => $input['email']        ?? null,
                ':start_time'   => $input['start_time']   ?? null,
                ':salary'       => $input['salary']       ?? 0,
                ':gym_id'       => $input['gym_id']       ?? null,
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM trainer WHERE trainer_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'trainer' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {
            if (empty($input['trainer_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing trainer_id']);
                exit;
            }
            $sql = "UPDATE trainer
                    SET trainer_name = :trainer_name,
                        phone_number = :phone_number,
                        email        = :email,
                        start_time   = :start_time,
                        salary       = :salary,
                        gym_id       = :gym_id
                    WHERE trainer_id = :trainer_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':trainer_name' => $input['trainer_name'] ?? null,
                ':phone_number' => $input['phone_number'] ?? null,
                ':email'        => $input['email']        ?? null,
                ':start_time'   => $input['start_time']   ?? null,
                ':salary'       => $input['salary']       ?? 0,
                ':gym_id'       => $input['gym_id']       ?? null,
                ':trainer_id'   => $input['trainer_id'],
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }
            $stmt2 = $pdo->prepare("SELECT * FROM trainer WHERE trainer_id = ?");
            $stmt2->execute([$input['trainer_id']]);
            echo json_encode(['success' => true, 'trainer' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $trainer_id = $input['trainer_id'] ?? null;
            if (!$trainer_id) {
                echo json_encode(['success' => false, 'error' => 'Missing trainer_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM trainer WHERE trainer_id = ?");
            $ok = $stmt->execute([$trainer_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'class':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM class ORDER BY class_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM class
                WHERE class_name LIKE :q
                   OR schedule   LIKE :q
                   OR CAST(class_id AS CHAR) LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO class (class_name, schedule, trainer_id, gym_id)
                    VALUES (:class_name, :schedule, :trainer_id, :gym_id)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':class_name' => $input['class_name'] ?? null,
                ':schedule'   => $input['schedule']   ?? null,
                ':trainer_id' => $input['trainer_id'] ?? null,
                ':gym_id'     => $input['gym_id']     ?? null,
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM class WHERE class_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'class' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {
            if (empty($input['class_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing class_id']);
                exit;
            }
            $sql = "UPDATE class
                    SET class_name = :class_name,
                        schedule   = :schedule,
                        trainer_id = :trainer_id,
                        gym_id     = :gym_id
                    WHERE class_id = :class_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':class_name' => $input['class_name'] ?? null,
                ':schedule'   => $input['schedule']   ?? null,
                ':trainer_id' => $input['trainer_id'] ?? null,
                ':gym_id'     => $input['gym_id']     ?? null,
                ':class_id'   => $input['class_id'],
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }
            $stmt2 = $pdo->prepare("SELECT * FROM class WHERE class_id = ?");
            $stmt2->execute([$input['class_id']]);
            echo json_encode(['success' => true, 'class' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $class_id = $input['class_id'] ?? null;
            if (!$class_id) {
                echo json_encode(['success' => false, 'error' => 'Missing class_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM class WHERE class_id = ?");
            $ok = $stmt->execute([$class_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'equipment':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM equipment ORDER BY equipment_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM equipment
                WHERE name   LIKE :q
                   OR type   LIKE :q
                   OR status LIKE :q
                   OR CAST(equipment_id AS CHAR) LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO equipment (name, type, status, purchase_date, gym_id)
                    VALUES (:name, :type, :status, :purchase_date, :gym_id)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'          => $input['name']          ?? null,
                ':type'          => $input['type']          ?? null,
                ':status'        => $input['status']        ?? null,
                ':purchase_date' => $input['purchase_date'] ?? null,
                ':gym_id'        => $input['gym_id']        ?? null,
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'equipment' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'update') {
            if (empty($input['equipment_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing equipment_id']);
                exit;
            }
            $sql = "UPDATE equipment
                    SET name          = :name,
                        type          = :type,
                        status        = :status,
                        purchase_date = :purchase_date,
                        gym_id        = :gym_id
                    WHERE equipment_id = :equipment_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'          => $input['name']          ?? null,
                ':type'          => $input['type']          ?? null,
                ':status'        => $input['status']        ?? null,
                ':purchase_date' => $input['purchase_date'] ?? null,
                ':gym_id'        => $input['gym_id']        ?? null,
                ':equipment_id'  => $input['equipment_id'],
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }
            $stmt2 = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
            $stmt2->execute([$input['equipment_id']]);
            echo json_encode(['success' => true, 'equipment' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $equipment_id = $input['equipment_id'] ?? null;
            if (!$equipment_id) {
                echo json_encode(['success' => false, 'error' => 'Missing equipment_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
            $ok = $stmt->execute([$equipment_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'contact':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM contact ORDER BY contact_id DESC");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'search') {
            $q = $input['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT * FROM contact
                WHERE name LIKE :q
                   OR email LIKE :q
                   OR phone_number LIKE :q
                   OR message LIKE :q
            ");
            $stmt->execute([':q' => "%{$q}%"]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($action === 'add') {
            $sql = "INSERT INTO contact (name, email, phone_number, message, date)
                    VALUES (:name, :email, :phone_number, :message, :date)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':name'         => $input['name']         ?? '',
                ':email'        => $input['email']        ?? '',
                ':phone_number' => $input['phone_number'] ?? '',
                ':message'      => $input['message']      ?? '',
                ':date'         => $input['date']         ?? date('Y-m-d'),
            ]);
            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Insert failed']);
                exit;
            }
            $id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT * FROM contact WHERE contact_id = ?");
            $stmt2->execute([$id]);
            echo json_encode(['success' => true, 'contact' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $contact_id = $input['contact_id'] ?? null;
            if (!$contact_id) {
                echo json_encode(['success' => false, 'error' => 'Missing contact_id']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM contact WHERE contact_id = ?");
            $ok = $stmt->execute([$contact_id]);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    case 'member_benefits':

        if ($action === 'list') {
            $stmt = $pdo->query("
                SELECT 
                    mb.member_id,
                    m.name,
                    mb.freeze_days_left,
                    mb.invitations_left,
                    mb.inbody_scans_left,
                    mb.private_sessions_left
                FROM member_benefits mb
                JOIN members m ON mb.member_id = m.member_id
                ORDER BY mb.member_id DESC
            ");
            echo json_encode($stmt->fetchAll());
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'update') {
            if (empty($input['member_id'])) {
                echo json_encode(['success' => false, 'error' => 'Missing member_id']);
                exit;
            }

            $sql = "UPDATE member_benefits
                    SET freeze_days_left      = :freeze_days_left,
                        invitations_left      = :invitations_left,
                        inbody_scans_left     = :inbody_scans_left,
                        private_sessions_left = :private_sessions_left
                    WHERE member_id = :member_id";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':freeze_days_left'      => $input['freeze_days_left']      ?? 0,
                ':invitations_left'      => $input['invitations_left']      ?? 0,
                ':inbody_scans_left'     => $input['inbody_scans_left']     ?? 0,
                ':private_sessions_left' => $input['private_sessions_left'] ?? 0,
                ':member_id'             => $input['member_id'],
            ]);

            if (!$ok) {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
                exit;
            }

            $stmt2 = $pdo->prepare("
                SELECT 
                    mb.member_id,
                    m.name,
                    mb.freeze_days_left,
                    mb.invitations_left,
                    mb.inbody_scans_left,
                    mb.private_sessions_left
                FROM member_benefits mb
                JOIN members m ON mb.member_id = m.member_id
                WHERE mb.member_id = ?
            ");
            $stmt2->execute([$input['member_id']]);
            echo json_encode(['success' => true, 'benefits' => $stmt2->fetch()]);
            exit;
        }

        if ($action === 'delete') {
            $member_id = $input['member_id'] ?? null;
            if (!$member_id) {
                echo json_encode(['success' => false, 'error' => 'Missing member_id']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM member_benefits WHERE member_id = ?");
            $ok   = $stmt->execute([$member_id]);

            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown entity']);
        exit;
}