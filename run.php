<?php
/* CLI runner for one installer step (temporary cron). Usage: php run.php 1|2|3 */
$n = (int)($argv[1] ?? 0); $T = [1 => '74aafa413e02a6ea', 2 => 'fd99f4cf975cf7ee', 3 => 'f59050a460283e43'];
$log = dirname(__DIR__) . '/companion-install-log.txt';
echo "run.php step $n as " . get_current_user() . " php " . PHP_VERSION . " at " . date('c') . "\n";
if (!isset($T[$n])) { echo "no step\n"; exit; }
$prev = (string)@file_get_contents($log);
if (strpos($prev, "=== step $n DONE") !== false) { echo "step $n already done\n"; exit; }
$_GET['go'] = $T[$n]; ob_start();
register_shutdown_function(function () use ($log, $n) { $o = (string)ob_get_clean(); $ok = strpos($o, 'DONE') !== false; $w = @file_put_contents($log, "=== step $n " . date('c') . "\n" . $o . "\n=== step $n " . ($ok ? 'DONE' : 'FAILED') . "\n", FILE_APPEND | LOCK_EX); echo $o, "\n[log write: ", $w === false ? 'FAILED' : $w . ' bytes', "]\n"; });
include __DIR__ . '/upgrade-companion' . ($n === 1 ? '' : '-' . $n) . '.php';
