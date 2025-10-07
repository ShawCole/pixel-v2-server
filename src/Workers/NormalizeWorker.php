<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Utils/TimeUtils.php';

use App\Database;
use App\Utils\TimeUtils;

$logFile = __DIR__ . '/../../storage/logs/NormalizeWorker.log';
@mkdir(dirname($logFile), 0775, true);

file_put_contents($logFile, "[" . TimeUtils::nowUtcIso() . "] NormalizeWorker boot\n", FILE_APPEND);

$dbOk = false;
try {
    $db = new Database();
    $db->pdo()->query('SELECT 1');
    $dbOk = true;
    file_put_contents($logFile, "[" . TimeUtils::nowUtcIso() . "] DB connection OK\n", FILE_APPEND);
} catch (\Throwable $e) {
    file_put_contents($logFile, "[" . TimeUtils::nowUtcIso() . "] DB connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
}

$interval = (int)(getenv('WORKER_INTERVAL') ?: 5);
while (true) {
    file_put_contents($logFile, "[" . TimeUtils::nowUtcIso() . "] tick " . ($dbOk ? "(db ok)" : "(db not connected)") . "\n", FILE_APPEND);
    sleep($interval);
}
