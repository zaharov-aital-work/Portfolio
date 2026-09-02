<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/shop_helpers.php';

$slug = preg_replace('/[^a-z0-9\-_]/i', '', $_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(404);
    exit('Страница не найдена');
}

$page = bm_cms_get($conn, $slug);
mysqli_close($conn);

if (!$page) {
    http_response_code(404);
    exit('Страница не найдена');
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?php echo htmlspecialchars($page['title']); ?> — BASEMOOD</title>
    <?php if (($page['meta_description'] ?? '') !== ''): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; margin: 0; padding: 28px 18px 56px; max-width: 920px; margin-left: auto; margin-right: auto; color: #222; line-height: 1.65; }
        h1 { font-size: 1.55rem; margin-bottom: 18px; font-weight: 800; }
        .cms-body { font-size: 1rem; }
        .cms-body img { max-width: 100%; height: auto; }
        .back { margin-top: 36px; font-size: 0.9rem; }
        .back a { color: #333; font-weight: 600; }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($page['title']); ?></h1>
    <div class="cms-body"><?php echo $page['body_html']; ?></div>
    <p class="back"><a href="index.php">← На главную</a></p>
</body>
</html>
