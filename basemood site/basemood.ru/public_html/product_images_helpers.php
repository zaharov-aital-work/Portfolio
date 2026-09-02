<?php

/**
 * Текущий запрос идёт по HTTPS (учёт прокси, например у Beget).
 */
function bm_request_effectively_https(): bool
{
    if (!isset($_SERVER) || !is_array($_SERVER)) {
        return false;
    }
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/**
 * Приводит путь из БД к URL от корня сайта или сохраняет абсолютный http(s).
 * Относительные пути иначе ломаются на страницах вроде /catalog/ → запрос шёл бы в /catalog/uploads/...
 *
 * @return non-empty-string|string
 */
function bm_normalize_public_url(string $url): string
{
    $url = trim(str_replace('\\', '/', $url));
    if ($url === '') {
        return '';
    }
    if ($url[0] === '/' && strpos($url, '//') !== 0) {
        return $url;
    }
    if (preg_match('#^(https?:)?//#i', $url)) {
        if (preg_match('#^http://#i', $url) && bm_request_effectively_https()) {
            return preg_replace('#^http://#i', 'https://', $url, 1);
        }
        return $url;
    }
    $base = defined('BM_PUBLIC_BASE') ? (string) BM_PUBLIC_BASE : '';
    $base = $base === '' ? '' : rtrim($base, '/');
    $path = ltrim($url, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $base . '/' . $path;
}

/**
 * Разбор многострочного поля админки (пути к картинкам, по одному в строке).
 *
 * @return list<string>
 */
function bm_parse_image_lines(?string $text): array
{
    if ($text === null || $text === '') {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/**
 * До 4 картинок для карточки в каталоге (наведение / свайп).
 *
 * @return list<string>
 */
function bm_product_card_urls(array $product): array
{
    $urls = [];
    if (!empty($product['card_images'])) {
        $decoded = json_decode($product['card_images'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (!is_string($item)) {
                    continue;
                }
                $t = trim($item);
                if ($t !== '') {
                    $urls[] = $t;
                }
            }
        }
    }
    if (count($urls) === 0) {
        if (!empty($product['image_url'])) {
            $t = trim((string) $product['image_url']);
            if ($t !== '') {
                $urls[] = $t;
            }
        }
        if (!empty($product['image_back_url'])) {
            $t = trim((string) $product['image_back_url']);
            if ($t !== '') {
                $urls[] = $t;
            }
        }
    }
    $out = [];
    foreach (array_slice($urls, 0, 4) as $u) {
        $n = bm_normalize_public_url($u);
        if ($n !== '') {
            $out[] = $n;
        }
    }
    return $out;
}

/**
 * Дополнительные картинки только для страницы товара (не в карточке каталога).
 *
 * @return list<string>
 */
function bm_product_gallery_extra_urls(array $product): array
{
    if (empty($product['gallery_images'])) {
        return [];
    }
    $decoded = json_decode($product['gallery_images'], true);
    if (!is_array($decoded)) {
        return [];
    }
    $out = [];
    foreach ($decoded as $item) {
        if (!is_string($item)) {
            continue;
        }
        $t = trim($item);
        if ($t !== '') {
            $n = bm_normalize_public_url($t);
            if ($n !== '') {
                $out[] = $n;
            }
        }
    }
    return $out;
}

/**
 * Полная галерея на странице товара: сначала картинки карточки, затем доп. без дубликатов.
 *
 * @return list<string>
 */
function bm_product_full_gallery_urls(array $product): array
{
    $card = bm_product_card_urls($product);
    $extra = bm_product_gallery_extra_urls($product);
    $seen = array_flip($card);
    $out = $card;
    foreach ($extra as $u) {
        if (!isset($seen[$u])) {
            $out[] = $u;
            $seen[$u] = true;
        }
    }
    return $out;
}

/** @param list<string> $urls */
function bm_urls_to_textarea_lines(array $urls): string
{
    return implode("\n", $urls);
}
