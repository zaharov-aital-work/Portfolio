<?php

declare(strict_types=1);

/**
 * Прокси к DaData suggest/address: ключ на сервере, не в браузере.
 * Нужен BM_DADATA_TOKEN в config.php (или пусто — вернётся []).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if (mb_strlen($q, 'UTF-8') < 2) {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!defined('BM_DADATA_TOKEN') || BM_DADATA_TOKEN === '') {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = json_encode(['query' => $q, 'count' => 8], JSON_UNESCAPED_UNICODE);
$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . BM_DADATA_TOKEN,
        ]),
        'content' => $body,
        'timeout' => 4,
    ],
]);

$raw = @file_get_contents('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', false, $ctx);
if ($raw === false) {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($raw, true);
$suggestions = [];
if (is_array($data) && !empty($data['suggestions'])) {
    foreach ($data['suggestions'] as $s) {
        if (is_array($s) && !empty($s['value'])) {
            $suggestions[] = (string) $s['value'];
        }
    }
}

echo json_encode(['suggestions' => $suggestions], JSON_UNESCAPED_UNICODE);
