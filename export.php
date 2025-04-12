<?php
$host = "127.127.126.25";
$port = 3306;
$dbname = "smart_city";
$username = "root";
$password = "";

$csvFile = _DIR_ . '/ml/sensor_data.csv';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT building_id, floor, sensor_type, sensor_data FROM sensor_data");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "Нет данных для экспорта.";
        exit;
    }

    $grouped = [];

    foreach ($rows as $row) {
        $key = $row['building_id'] . '-' . $row['floor'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'building_id' => $row['building_id'],
                'floor' => $row['floor'],
                'air_quality' => '',
                'pressure' => '',
                'temperature' => ''
            ];
        }

        $grouped[$key][$row['sensor_type']] = $row['sensor_data'];
    }

    $dir = dirname($csvFile);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $fp = fopen($csvFile, 'w');
    if (!$fp) {
        echo "Не удалось открыть файл для записи.";
        exit;
    }

    fputcsv($fp, ['building_id', 'floor', 'air_quality', 'pressure', 'temperature']);

    foreach ($grouped as $row) {
        fputcsv($fp, $row);
    }

    fclose($fp);

    header('Content-Type: text/plain; charset=utf-8');
    readfile($csvFile);

} catch (PDOException $e) {
    echo "Ошибка подключения к БД: " . $e->getMessage();
}
