<?php
/* CLI runner for the installers (used once by a temporary cron job). Usage: php run.php 1|2|3 */
$n = (int)($argv[1] ?? 0); $T = [1 => '74aafa413e02a6ea', 2 => 'fd99f4cf975cf7ee', 3 => 'f59050a460283e43'];
if (!isset($T[$n])) exit;
$log = dirname(__DIR__) . '/companion-install-log.txt';
if (strpos((string)@file_get_contents($log), "=== step $n DONE") !== false) exit; /* already ran */
$_GET['go'] = $T[$n]; ob_start();
register_shutdown_function(function () use ($log, $n) { $o = ob_get_clean(); file_put_contents($log, "=== step $n " . date('c') . "\n" . $o . "\n=== step $n DONE\n", FILE_APPEND | LOCK_EX); });
include __DIR__ . '/upgrade-companion' . ($n === 1 ? '' : '-' . $n) . '.php';
