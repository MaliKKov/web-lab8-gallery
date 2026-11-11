
<?php
require_once __DIR__ . "/config.php";

// Получаем фотографии в порядке популярности
$stmt = $pdo->query("
    SELECT
        photos.id,
        photos.thumbnail,
        photos.description,
        photos.views,
        photos.uploaded_at,
        users.name AS author
    FROM photos
    INNER JOIN users ON photos.user_id = users.id
    ORDER BY photos.views DESC, photos.id DESC
");

$photos = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoGallery — Главная</title>
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

    <div class="page-heading">
        <div>
            <p class="eyebrow">Лабораторная работа №8</p>
            <h1>Галерея фотографий</h1>
            <p>
                Фотографии отсортированы по количеству
                просмотров — от популярных к новым.
            </p>
        </div>

        <span class="photo-count">
            Всего фотографий: <?= count($photos) ?>
        </span>
    </div>

    <?php if (empty($photos)): ?>

        <div class="empty-state">
            <div class="empty-icon">🖼️</div>

            <h2>В галерее пока нет фотографий</h2>

            <p>
                Загрузите первое изображение,
                чтобы оно появилось здесь.
            </p>

            <a href="upload.php" class="button">
                Загрузить фотографию
            </a>
        </div>

    <?php else: ?>

        <div class="gallery-grid">

            <?php foreach ($photos as $photo): ?>

                <article class="photo-card">

                    <a href="photo.php?id=<?= (int) $photo['id'] ?>"
                       class="photo-card__image">

                        <img
                            src="thumbs/<?= e($photo['thumbnail']) ?>"
                            alt="<?= e($photo['description'] ?: 'Фотография') ?>"
                            loading="lazy"
                        >

                    </a>

                    <div class="photo-card__body">

                        <h2>
                            <?= e($photo['description'] ?: 'Без описания') ?>
                        </h2>

                        <p>
                            Автор: <?= e($photo['author']) ?>
                        </p>

                        <div class="photo-card__footer">

                            <span>
                                👁 <?= (int) $photo['views'] ?>
                                просмотров
                            </span>

                            <a href="photo.php?id=<?= (int) $photo['id'] ?>">
                                Открыть →
                            </a>

                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer class="site-footer">
    Лабораторная работа №8 · PHP + MySQL · 2026
</footer>

</body>
</html>
