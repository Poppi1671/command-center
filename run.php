<?php
/* CLI runner for the installers (used once by a temporary cron job). Runs steps 1, 2, 3 in order, each in its own process. */
$n = (int)($argv[1] ?? 0); $T = [1 => '74aafa413e02a6ea', 2 => 'fd99f4cf975cf7ee', 3 => 'f59050a460283e43'];
$log = dirname(__DIR__) . '/companion-install-log.txt';
if ($n === 0) { $php = PHP_BINARY ?: 'php'; foreach ([1, 2, 3] as $s) { $o = []; @exec(escapeshellarg($php) . ' ' . escapeshellarg(__FILE__) . ' ' . $s . ' 2>&1', $o); if (strpos((string)@file_get_contents($log), "=== step $s DONE") === false) { file_put_contents($log, "=== step $s did not run: " . implode(' ', $o) . "\n", FILE_APPEND); break; } } exit; }
if (!isset($T[$n])) exit;
if (strpos((string)@file_get_contents($log), "=== step $n DONE") !== false) exit; /* already ran */
$_GET['go'] = $T[$n]; ob_start();
register_shutdown_function(function () use ($log, $n) { $o = ob_get_clean(); file_put_contents($log, "=== step $n " . date('c') . "\n" . $o . "\n=== step $n DONE\n", FILE_APPEND | LOCK_EX); });
include __DIR__ . '/upgrade-companion' . ($n === 1 ? '' : '-' . $n) . '.php';
