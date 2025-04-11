<?php  
header('Content-Type: application/json; charset=utf-8');

// Параметры подключения
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

// Получение параметров запроса
$building_id = isset($_GET['building']) ? (int) $_GET['building'] : 1;
$entrance_number = isset($_GET['entrance']) ? (int) $_GET['entrance'] : null;

// Формируем SQL-запрос
$sql = "SELECT id, name, sensor_type, unit, normal_min, normal_max, floor, time, sensor_data, status, entrance_number 
        FROM sensor_data 
        WHERE building_id = :building_id";

$params = [":building_id" => $building_id];

if ($entrance_number !== null) {
    $sql .= " AND entrance_number = :entrance_number";
    $params[":entrance_number"] = $entrance_number;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($sensors, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => "Ошибка выполнения запроса: " . $e->getMessage()]);
}
?>
