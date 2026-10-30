<?php
/* CLI runner for one installer step (temporary cron). Usage: php run.php 1|2|3   |   php run.php 9 <installer#> = anchor diagnostics */
$n = (int)($argv[1] ?? 0); $T = [1 => '74aafa413e02a6ea', 2 => 'fd99f4cf975cf7ee', 3 => 'f59050a460283e43'];
$log = dirname(__DIR__) . '/companion-install-log.txt';
echo "run.php step $n as " . get_current_user() . " php " . PHP_VERSION . " at " . date('c') . "\n";
if ($n === 9) {
  $root = dirname(__DIR__); $which = (int)($argv[2] ?? 2);
  $src = (string)file_get_contents(__DIR__ . '/upgrade-companion' . ($which === 1 ? '' : '-' . $which) . '.php');
  if (!preg_match("/json_decode\\('.*?', true\\)/s", $src, $mm)) { echo "no patch block\n"; exit; }
  $P = eval('return ' . $mm[0] . ';'); if (!is_array($P)) { echo "parse failed\n"; exit; }
  echo "patches=" . count($P) . "\n";
  foreach ($P as $p) { $c = (string)file_get_contents($root . '/' . $p[0]); $n0 = substr_count($c, $p[1]); echo "[$n0] $p[3]";
    if ($n0 !== 1) { $probe = substr($p[1], 0, 28); $i = strpos($c, $probe); echo "\n   anchor=" . json_encode($p[1]) . "\n   live  =" . ($i === false ? 'probe missing' : json_encode(substr($c, $i, strlen($p[1]) + 40))); }
    echo "\n"; }
  exit;
}
if (!isset($T[$n])) { echo "no step\n"; exit; }
$prev = (string)@file_get_contents($log);
if (strpos($prev, "=== step $n DONE") !== false) { echo "step $n already done\n"; exit; }
$_GET['go'] = $T[$n]; ob_start();
register_shutdown_function(function () use ($log, $n) { $o = (string)ob_get_clean(); $ok = strpos($o, 'DONE') !== false; $w = @file_put_contents($log, "=== step $n " . date('c') . "\n" . $o . "\n=== step $n " . ($ok ? 'DONE' : 'FAILED') . "\n", FILE_APPEND | LOCK_EX); echo $o, "\n[log write: ", $w === false ? 'FAILED' : $w . ' bytes', "]\n"; });
include __DIR__ . '/upgrade-companion' . ($n === 1 ? '' : '-' . $n) . '.php';
