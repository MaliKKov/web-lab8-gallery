
<?php
require_once __DIR__ . "/config.php";

$error = "";
$name = "";
$email = "";
$description = "";

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $description = trim($_POST["description"] ?? "");

    $file = $_FILES["photo"] ?? null;

    $maxSize = 5 * 1024 * 1024;

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if ($name === "" || strlen($name) > 100) {
        $error = "Введите имя (не более 100 символов).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)
              || strlen($email) > 150) {
        $error = "Введите корректный email.";
    } elseif (!$file || $file["error"] !== UPLOAD_ERR_OK) {
        $error = "Выберите изображение для загрузки.";
    } elseif ($file["size"] > $maxSize) {
        $error = "Максимальный размер файла — 5 МБ.";
    } elseif (!extension_loaded("gd")) {
        $error = "Для создания миниатюр требуется расширение PHP GD.";
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file["tmp_name"]);

        $imageInfo = @getimagesize($file["tmp_name"]);

        if (
            !isset($allowedTypes[$mime]) ||
            !$imageInfo ||
            $imageInfo["mime"] !== $mime
        ) {
            $error = "Разрешены только JPG, PNG и WEBP.";
        } else {
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if (
                $width < 1 || $height < 1 ||
                $width > 5000 || $height > 5000 ||
                $width * $height > 16000000
            ) {
                $error = "Размеры изображения слишком большие.";
            } else {
                $extension = $allowedTypes[$mime];

                $fileId = bin2hex(random_bytes(16));

                $filename = $fileId . "." . $extension;
                $thumbnail = $fileId . "_thumb.jpg";

                $originalPath = __DIR__ . "/uploads/" . $filename;
                $thumbnailPath = __DIR__ . "/thumbs/" . $thumbnail;

                // Загружаем изображение средствами GD
                $source = false;

                if ($mime === "image/jpeg") {
                    $source = @imagecreatefromjpeg($file["tmp_name"]);
                } elseif ($mime === "image/png") {
                    $source = @imagecreatefrompng($file["tmp_name"]);
                } elseif ($mime === "image/webp"
                          && function_exists("imagecreatefromwebp")) {
                    $source = @imagecreatefromwebp($file["tmp_name"]);
                }

                if (!$source) {
                    $error = "Не удалось обработать изображение.";
                } else {
                    // Размер миниатюры: не больше 400 × 300
                    $scale = min(
                        400 / $width,
                        300 / $height,
                        1
                    );

                    $thumbWidth = max(1, (int) round($width * $scale));
                    $thumbHeight = max(1, (int) round($height * $scale));

                    $thumb = imagecreatetruecolor(
                        $thumbWidth,
                        $thumbHeight
                    );

                    // Белый фон для прозрачных PNG и WEBP
                    $white = imagecolorallocate(
                        $thumb, 255, 255, 255
                    );

                    imagefill($thumb, 0, 0, $white);

                    imagecopyresampled(
                        $thumb,
                        $source,
                        0, 0, 0, 0,
                        $thumbWidth,
                        $thumbHeight,
                        $width,
                        $height
                    );

                    $thumbnailSaved = imagejpeg(
                        $thumb,
                        $thumbnailPath,
                        85
                    );

                    imagedestroy($thumb);
                    imagedestroy($source);

                    if (!$thumbnailSaved) {
                        $error = "Не удалось создать миниатюру.";
                    } elseif (!move_uploaded_file(
                        $file["tmp_name"],
                        $originalPath
                    )) {
                        @unlink($thumbnailPath);
                        $error = "Не удалось сохранить оригинал.";
                    } else {
                        try {
                            $pdo->beginTransaction();

                            // Добавляем пользователя
                            $userStmt = $pdo->prepare("
                                INSERT INTO users (name, email)
                                VALUES (?, ?)
                            ");

                            $userStmt->execute([$name, $email]);

                            $userId = $pdo->lastInsertId();

                            // Добавляем фотографию
                            $photoStmt = $pdo->prepare("
                                INSERT INTO photos
                                    (user_id, filename, thumbnail,
                                     description, views)
                                VALUES (?, ?, ?, ?, 0)
                            ");

                            $photoStmt->execute([
                                $userId,
                                $filename,
                                $thumbnail,
                                $description
                            ]);

                            $pdo->commit();

                            // Возвращаем пользователя в галерею
                            header("Location: index.php?uploaded=1");
                            exit;

                        } catch (Throwable $exception) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }

                            @unlink($originalPath);
                            @unlink($thumbnailPath);

                            error_log($exception->getMessage());

                            $error = "Ошибка сохранения данных в БД.";
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузить фотографию — PhotoGallery</title>
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

    <div class="form-panel">
        <p class="eyebrow">Добавление фотографии</p>
        <h1>Загрузить изображение</h1>

        <p>
            Укажите информацию о себе, выберите фотографию
            и добавьте описание.
        </p>

        <?php if ($error !== ""): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST"
              enctype="multipart/form-data"
              class="upload-form">

            <div class="form-field">
                <label for="name">Ваше имя</label>

                <input type="text"
                       id="name"
                       name="name"
                       maxlength="100"
                       value="<?= e($name) ?>"
                       placeholder="Введите имя"
                       required>
            </div>

            <div class="form-field">
                <label for="email">Электронная почта</label>

                <input type="email"
                       id="email"
                       name="email"
                       maxlength="150"
                       value="<?= e($email) ?>"
                       placeholder="example@mail.com"
                       required>
            </div>

            <div class="form-field">
                <label for="description">Описание фотографии</label>

                <textarea id="description"
                          name="description"
                          rows="4"
                          placeholder="Что изображено на фотографии?"><?= e($description) ?></textarea>
            </div>

            <div class="form-field">
                <label for="photo">Выберите фотографию</label>

                <input type="file"
                       id="photo"
                       name="photo"
                       accept=".jpg,.jpeg,.png,.webp"
                       required>

                <small>
                    JPG, PNG или WEBP. Максимум 5 МБ.
                </small>
            </div>

            <button type="submit" class="button">
                Загрузить фотографию
            </button>

        </form>
    </div>

</main>

<footer class="site-footer">
    Лабораторная работа №8 · PHP + MySQL · 2026
</footer>

</body>
</html>
