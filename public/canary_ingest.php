<?php
declare(strict_types=1);
header('Content-Type: application/json');
function fail($c,$m){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$m]); exit; }
$raw = file_get_contents('php://input'); if (!$raw) fail(400,'empty_body');
$env = parse_ini_file('/var/www/pixel-v2/.env', false, INI_SCANNER_RAW); if (!$env) fail(500,'env_read_fail');
$host=$env['DB_HOST']??'127.0.0.1'; $user=$env['DB_USER']??''; $pass=$env['DB_PASS']??''; $port=(int)($env['DB_PORT']??3306);
mysqli_report(MYSQLI_REPORT_OFF);
$db=@mysqli_connect($host,$user,$pass,'pixel',$port); if(!$db) fail(500,'db_connect_fail');
$sha = hash('sha256',$raw,true);
$stmt = $db->prepare("INSERT IGNORE INTO pixel.canary_ingest_raw (payload_sha256, raw_body) VALUES (?, JSON_QUOTE(?))");
if(!$stmt) fail(500,'stmt_prep_fail'); $stmt->bind_param('ss',$sha,$raw); $ok=$stmt->execute();
http_response_code($ok && $stmt->affected_rows===1 ? 201 : 200);
echo json_encode(['ok'=>true,'inserted'=>$stmt->affected_rows===1]);
