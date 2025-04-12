<?php
$host = "127.127.126.25";
$port = 3306;
$dbname = "smart_city";
$username = "root";
$password = "";

$csvFile = __DIR__ . '/ml/sensor_data.csv';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT sensor_data, normal_min, normal_max, status FROM sensor_data");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        exit;
    }

    $dir = dirname($csvFile);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $fp = fopen($csvFile, 'w');
    if ($fp) {
        fputcsv($fp, ['sensor_data', 'normal_min', 'normal_max', 'status']);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
    echo json_encode(['status' => 'ok']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
