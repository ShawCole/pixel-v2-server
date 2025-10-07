<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/TimeUtils.php';
require_once __DIR__ . '/../src/Utils/UlidGenerator.php';

use App\Utils\TimeUtils;
use App\Utils\UlidGenerator;

header('Content-Type: application/json');

$raw = file_get_contents('php://input') ?: '';
$parsed = json_decode($raw, true);
if ($raw !== '' && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$event = [
    'id' => UlidGenerator::newId(),
    'received_at' => TimeUtils::nowUtcIso(),
    'payload' => $parsed ?? new stdClass(),
    'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
];

$logFile = __DIR__ . '/../storage/logs/webhook.log';
@mkdir(dirname($logFile), 0775, true);
file_put_contents($logFile, json_encode($event) . PHP_EOL, FILE_APPEND);

echo json_encode(['ok' => true, 'id' => $event['id']]);
