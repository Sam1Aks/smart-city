<?php
$host = "127.127.126.25";
$port = 3306;
$dbname = "smart_city";
$username = "root";
$password = "";

$csvFile = __DIR__ . '/ml/new.csv';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT building_id, entrance_number, floor,
               MAX(CASE WHEN sensor_type = 'air_quality' THEN sensor_data END) AS air_quality,
               MAX(CASE WHEN sensor_type = 'pressure' THEN sensor_data END) AS pressure,
               MAX(CASE WHEN sensor_type = 'temperature' THEN sensor_data END) AS temperature
        FROM sensor_data
        GROUP BY building_id, entrance_number, floor
        ORDER BY building_id, entrance_number, floor
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "Нет данных для экспорта.";
        exit;
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

    fputcsv($fp, ['building_id', 'entrance_number', 'floor', 'air_quality', 'pressure', 'temperature']);

    foreach ($rows as $row) {
        fputcsv($fp, [
            $row['building_id'],
            $row['entrance_number'],
            $row['floor'],
            number_format($row['air_quality'], 2, '.', ''),
            number_format($row['pressure'], 2, '.', ''),
            number_format($row['temperature'], 2, '.', '')
        ]);
    }

    fclose($fp);

    header('Content-Type: text/plain; charset=utf-8');
    readfile($csvFile);

} catch (PDOException $e) {
    echo "Ошибка подключения к БД: " . $e->getMessage();
}
?>
