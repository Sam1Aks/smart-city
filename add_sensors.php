<?php
header('Content-Type: application/json; charset=utf-8');

$host = "127.127.126.25";
$port = 3306;
$dbname = "smart_city";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Ошибка подключения к БД: " . $e->getMessage()]);
    exit();
}

function generateSensorData($min, $max) {
    $value = round(mt_rand($min * 10, $max * 10) / 10, 1);
    
    // Случайно решаем, нужно ли выходить за норму 
    if (mt_rand(1, 100) <= 95) {
        $deviation = mt_rand(-10, 10) / 100; // Отклонение теперь от -10% до +10%
        $value = round($value * (1 + $deviation), 1);
    }

    return $value;
}

$stmt = $pdo->query("SELECT id FROM sensor_data");
$existing_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
$existing_ids = $existing_ids ? array_flip($existing_ids) : [];

$sensor_types = [
    "temperature" => ["unit" => "°C", "normal_min" => 18, "normal_max" => 25],
    "pressure" => ["unit" => "мм рт. ст.", "normal_min" => 750, "normal_max" => 770],
    "air_quality" => ["unit" => "AQI", "normal_min" => 0, "normal_max" => 50]
];

$entrances_status = [];
$buildings_status = [];

$sensors = [];
for ($building = 1; $building <= 3; $building++) {
    $entranceOffset = ($building - 1) * 3;
    for ($e = 1; $e <= 3; $e++) {
        $entrance = $entranceOffset + $e;
        $entrance_status = 'green'; 
        for ($floor = 1; $floor <= 9; $floor++) {
            foreach ($sensor_types as $type => $info) {
                $id = sprintf('%s-%s-%s-%s', $building, $entrance, $floor, $type);
                $data = generateSensorData($info["normal_min"], $info["normal_max"]);
                $name = "Датчик $id";
                $unit = $info["unit"];
                $normal_min = $info["normal_min"];
                $normal_max = $info["normal_max"];

                if ($data < $normal_min * 0.95 || $data > $normal_max * 1.05) {
                    $status = 'red';
                } elseif ($data < $normal_min || $data > $normal_max) {
                    $status = 'yellow';
                } else {
                    $status = 'green';
                }

                if ($status === 'red') {
                    $entrance_status = 'red';
                } elseif ($status === 'yellow' && $entrance_status !== 'red') {
                    $entrance_status = 'yellow';
                }

                if (isset($existing_ids[$id])) {
                    $stmt = $pdo->prepare("UPDATE sensor_data 
                    SET sensor_data = :data, status = :status, normal_min = :normal_min, normal_max = :normal_max 
                    WHERE id = :id");
                    $stmt->execute([
                        ':id' => $id,
                        ':data' => $data,
                        ':status' => $status,
                        ':normal_min' => $normal_min,
                        ':normal_max' => $normal_max
                    ]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO sensor_data (id, building_id, entrance_number, floor, sensor_type, sensor_data, status, name, unit, normal_min, normal_max) 
                    VALUES (:id, :building, :entrance, :floor, :type, :data, :status, :name, :unit, :normal_min, :normal_max)");
                    $stmt->execute([
                        ':id' => $id,
                        ':building' => $building,
                        ':entrance' => $entrance,
                        ':floor' => $floor,
                        ':type' => $type,
                        ':data' => $data,
                        ':status' => $status,
                        ':name' => $name,
                        ':unit' => $unit,
                        ':normal_min' => $normal_min,
                        ':normal_max' => $normal_max
                    ]);
                }

                $sensors[] = ['id' => $id, 'data' => $data, 'status' => $status];
            }
        }

        $entrances_status["$building-$entrance"] = $entrance_status;
    }

    $building_status = 'green';
    foreach ($entrances_status as $key => $status) {
        if (strpos($key, "$building-") === 0) {
            if ($status === 'red') {
                $building_status = 'red';
                break;
            } elseif ($status === 'yellow' && $building_status !== 'red') {
                $building_status = 'yellow';
            }
        }
    }
    $buildings_status[$building] = $building_status;
}

echo json_encode([
    "message" => "Данные обновлены",
    "entrances" => $entrances_status,
    "buildings" => $buildings_status
], JSON_UNESCAPED_UNICODE);

file_get_contents('https://jkh.system/export.php?nocache=' . time());