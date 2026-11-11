
<?php
require_once __DIR__ . "/config.php";

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Получаем ID фотографии из URL
$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if (!$id) {
    http_response_code(400);
    exit("Некорректный идентификатор фотографии.");
}

// Увеличиваем счётчик только существующей фотографии
$update = $pdo->prepare("
    UPDATE photos
    SET views = views + 1
    WHERE id = ?
");

$update->execute([$id]);

// Получаем фотографию и её автора
$stmt = $pdo->prepare("
    SELECT
        photos.*,
        users.name AS author,
        users.email AS author_email
    FROM photos
    INNER JOIN users ON photos.user_id = users.id
    WHERE photos.id = ?
");

$stmt->execute([$id]);
$photo = $stmt->fetch();

if (!$photo) {
    http_response_code(404);
    exit("Фотография не найдена.");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр фотографии — PhotoGallery</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">📷 PhotoGallery</a>

        <nav class="nav">
            <a href="index.php">Галерея</a>
            <a href="upload.php" class="nav-button">
                + Загрузить фото
            </a>
        </nav>
    </div>
</header>

<main class="container main-content">

    <a href="index.php" class="back-link">
        ← Вернуться в галерею
    </a>

    <div class="photo-detail">

        <div class="photo-detail__image">
            <img
                src="uploads/<?= e($photo['filename']) ?>"
                alt="<?= e($photo['description'] ?: 'Фотография') ?>"
            >
        </div>

        <div class="photo-detail__info">

            <h1>
                <?= e($photo['description'] ?: 'Фотография без описания') ?>
            </h1>

            <div class="info-row">
                <span>Автор</span>
                <strong><?= e($photo['author']) ?></strong>
            </div>

            <div class="info-row">
                <span>Дата загрузки</span>
                <strong>
                    <?= e(date(
                        "d.m.Y H:i",
                        strtotime($photo['uploaded_at'])
                    )) ?>
                </strong>
            </div>

            <div class="views-box">
                <span>Количество просмотров</span>

                <strong>
                    👁 <?= (int) $photo['views'] ?>
                </strong>
            </div>

            <p class="hint">
                Счётчик увеличивается при открытии страницы
                фотографии.
            </p>

        </div>
    </div>

</main>

<footer class="site-footer">
    Лабораторная работа №8 · PHP + MySQL · 2026
</footer>

</body>
</html>
