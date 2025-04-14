<?php
$host = "127.127.126.25";
$port = 3306;
$dbname = "smart_city";
$username = "root";
$password = "";

$csvFile = __DIR__ . '/ai_server/new.csv'; // Путь к файлу, куда будем записывать данные

try {
    // Подключаемся к базе данных
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Запрос для получения данных из таблицы sensor_data (без агрегации)
    $stmt = $pdo->query("
        SELECT building_id, entrance_number, floor, 
               CASE 
                   WHEN sensor_type = 'air_quality' THEN 0 
                   WHEN sensor_type = 'pressure' THEN 1
                   WHEN sensor_type = 'temperature' THEN 2
                   ELSE NULL 
               END AS sensor_type,
               sensor_data, normal_min, normal_max, status
        FROM sensor_data
        ORDER BY building_id, entrance_number, floor
    ");

    // Извлекаем все данные
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "Нет данных для экспорта.";
        exit;
    }

    // Создаем директорию, если её нет
    $dir = dirname($csvFile);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    // Открываем файл для записи
    $fp = fopen($csvFile, 'w');
    if (!$fp) {
        echo "Не удалось открыть файл для записи.";
        exit;
    }

    // Заголовки CSV
    fputcsv($fp, ['building_id', 'entrance_number', 'floor', 'sensor_type', 'sensor_data', 'normal_min', 'normal_max', 'risk']);

    // Проходим по всем строкам и записываем в CSV
    foreach ($rows as $row) {
        // Преобразуем статус в числовой формат
        if ($row['status'] == 'green') {
            $risk = 0; // Green -> 0
        } elseif ($row['status'] == 'yellow') {
            $risk = 1; // Yellow -> 1
        } elseif ($row['status'] == 'red') {
            $risk = 2; // Red -> 2
        }

        // Записываем данные в CSV
        fputcsv($fp, [
            $row['building_id'],
            $row['entrance_number'],
            $row['floor'],
            $row['sensor_type'],
            number_format($row['sensor_data'], 2, '.', ''),
            number_format($row['normal_min'], 2, '.', ''),
            number_format($row['normal_max'], 2, '.', ''),
            $risk,
        ]);
    }

    fclose($fp);

    // Отправляем файл для скачивания
    header('Content-Type: text/plain; charset=utf-8');
    readfile($csvFile);

} catch (PDOException $e) {
    echo "Ошибка подключения к БД: " . $e->getMessage();
}
?>
