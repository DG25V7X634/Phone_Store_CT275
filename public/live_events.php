<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');
set_time_limit(0);
$started=microtime(true);$previous='';
while(!connection_aborted() && microtime(true)-$started<55){
 try{$data=['products'=>(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),'customers'=>(int)$pdo->query('SELECT COUNT(*) FROM ps_customers')->fetchColumn(),'orders'=>(int)$pdo->query('SELECT COUNT(*) FROM ps_orders')->fetchColumn(),'time'=>date(DATE_ATOM)];$json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json!==$previous){echo 'event: stats'."\n".'data: '.$json."\n\n";$previous=$json;}else{echo ": heartbeat\n\n";} }
 catch(Throwable $e){echo 'event: app-error'."\n".'data: '.json_encode(['message'=>'Không thể đọc thống kê. Hãy chạy database/updates_ct275.sql.'])."\n\n";}
 @ob_flush();flush();sleep(5);
}
