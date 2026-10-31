<?php
/* Fix + finish (Oct 31 2026): corrects the speed type in api/narrator.php, then makes the deck audio.
   Reads the desk key from the server's own config; never prints it. Deletes itself when done. */
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/api/svc_lib.php';
$KEY = tt_admin_key(); $SITE = tt_site(); $log = '';
function say($s) { global $log, $root; $log .= $s . "\n"; echo $s . "\n"; @file_put_contents($root . '/_inst3/go-report.txt', $log); }
if ($KEY === '') { say('STOP: desk key not set.'); exit; }

$f = $root . '/api/narrator.php';
$c = (string)@file_get_contents($f);
$old = "'response_format' => 'mp3', 'speed' => \$speed];";
$new = "'response_format' => 'mp3', 'speed' => (float)\$speed];";
if ($c === '') { say('STOP: could not read api/narrator.php'); exit; }
if (strpos($c, $new) !== false) say('patch: already applied');
elseif (substr_count($c, $old) !== 1) { say('STOP: anchor found ' . substr_count($c, $old) . ' times, expected 1'); exit; }
else {
  if (!is_file($f . '.bak-20261031-speed')) copy($f, $f . '.bak-20261031-speed');
  if (@file_put_contents($f, str_replace($old, $new, $c), LOCK_EX) === false) { say('STOP: could not write api/narrator.php'); exit; }
  $o = []; $rc = 0; @exec('php -l ' . escapeshellarg($f) . ' 2>&1', $o, $rc);
  if ($rc !== 0 && trim(implode(' ', $o)) !== '') { copy($f . '.bak-20261031-speed', $f); say('STOP: syntax error, rolled back'); exit; }
  say('patch: applied to api/narrator.php');
}

function call($path, $body, $key, $site) {
  $ch = curl_init($site . $path);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 240, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-TT-Key: ' . $key]]);
  $r = (string)curl_exec($ch); $e = curl_error($ch); $hc = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($e !== '') return ['error' => 'network: ' . $e];
  $j = json_decode($r, true);
  return is_array($j) ? $j : ['error' => 'bad reply (' . $hc . '): ' . substr($r, 0, 200)];
}

say('');
say('== Making the audio ==');
for ($pass = 1; $pass <= 8; $pass++) {
  $m = call('/api/narrator.php', ['a' => 'make', 'id' => 'outreach-machine'], $KEY, $SITE);
  if (!empty($m['error'])) { say('pass ' . $pass . ': ERROR ' . $m['error']); break; }
  say('pass ' . $pass . ': made ' . (int)$m['made'] . ', ' . (int)$m['left'] . ' left, ' . (int)$m['used'] . ' of ' . (int)$m['cap'] . ' characters used this month');
  if ((int)$m['left'] === 0) { say('ALL 14 LINES VOICED.'); break; }
  if ((int)$m['made'] === 0) { say('no progress, stopping'); break; }
}
say('');
say('PLAY IT: ' . $SITE . '/narrate/?p=outreach-machine');
@unlink(__FILE__);
