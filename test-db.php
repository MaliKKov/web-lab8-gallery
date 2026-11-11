
<?php
require_once "config.php";

echo "<h1>Подключение успешно!</h1>";

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Таблицы базы данных:</h2>";

foreach ($tables as $table) {
    echo "<p>" . htmlspecialchars($table) . "</p>";
}
