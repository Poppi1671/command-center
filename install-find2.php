<?php
/* THE WALL installer - wall. Three new files plus one script line on the desk.
   No exec(), no shell: shared hosting disables them. Syntax was linted before it shipped.
   All-or-nothing on the patch; writes a report next to itself either way. */
@ini_set('display_errors','0');
$REPORT = __DIR__ . '/wall-report.txt';
$log = [];
function out($s) { global $log, $REPORT; $log[] = $s; @file_put_contents($REPORT, implode("\n", $log) . "\n"); echo $s . "\n"; }
set_error_handler(function($n,$m,$f,$l){ out("PHP ERROR: $m ($f:$l)"); return true; });
register_shutdown_function(function(){ $e = error_get_last(); if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR], true)) out('FATAL: ' . $e['message'] . ' (' . $e['file'] . ':' . $e['line'] . ')'); });

if (PHP_SAPI !== 'cli' && ($_GET['go'] ?? '') !== '2ecab97ba1f19d87') { http_response_code(404); exit; }
if (PHP_SAPI === 'cli' && ($_GET['go'] ?? '') !== '2ecab97ba1f19d87') { echo "bad token\n"; exit; }
header('Content-Type: text/plain; charset=utf-8');

$root = preg_match('~/_inst~', __DIR__) ? dirname(__DIR__) : __DIR__;
$tag  = 'find2';
$stamp = date('Ymd');
out('installer running. root=' . $root . ' sapi=' . PHP_SAPI);

$blocks  = json_decode(base64_decode('eyJhcGkvX2ZpbmQucGhwIjogIlBEOXdhSEFLTHlvZ1ZHaHliM2RoZDJGNUlHRnVZMmh2Y2lCbWFXNWtaWEl1SUVSbGMyc2dhMlY1SUhKbGNYVnBjbVZrTGlCUWNtbHVkSE1nYldGMFkyaHBibWNnYkdsdVpYTWdjMjhnWVNCd1lYUmphQ0JqWVc0Z1ltVWdkM0pwZEhSbGJnb2dJQ0JoWjJGcGJuTjBJSFJvWlNCeVpXRnNJR1pwYkdVZ2QybDBhRzkxZENCa2IzZHViRzloWkdsdVp5QnBkQzRnVW1WdGIzWmxaQ0JpZVNCMGFHVWdibVY0ZENCcGJuTjBZV3hzWlhJdUlDb3ZDbVJsWTJ4aGNtVW9jM1J5YVdOMFgzUjVjR1Z6UFRFcE93cHBaaUFvS0hOMGNtbHVaeWtvSkY5SFJWUmJKMmR2SjEwZ1B6OGdKeWNwSUNFOVBTQW5ZakE0TWpJd05qUTRZakl6TURZMVpDY3BJSHNnYUhSMGNGOXlaWE53YjI1elpWOWpiMlJsS0RRd05DazdJR1Y0YVhRN0lIMEthR1ZoWkdWeUtDZERiMjUwWlc1MExWUjVjR1U2SUhSbGVIUXZjR3hoYVc0N0lHTm9ZWEp6WlhROWRYUm1MVGduS1RzS0pHWWdQU0J3Y21WblgzSmxjR3hoWTJVb0ozNWJYbUV0ZWtFdFdqQXRPUzVmTFYxK0p5d2dKeWNzSUNoemRISnBibWNwS0NSZlIwVlVXeWRtSjEwZ1B6OGdKeWNwS1RzS0pIRWdQU0FvYzNSeWFXNW5LU2drWDBkRlZGc25jU2RkSUQ4L0lDY25LVHNLSkhCaGRHZ2dQU0JmWDBSSlVsOWZJQzRnSnk4bklDNGdKR1k3Q21sbUlDZ2taaUE5UFQwZ0p5Y2dmSHdnSVdselgyWnBiR1VvSkhCaGRHZ3BLU0I3SUdWamFHOGdJbTV2SUhOMVkyZ2dabWxzWlZ4dUlqc2daWGhwZERzZ2ZRcHBaaUFvSkhFZ1BUMDlJQ2NuS1NCN0lHVmphRzhnSW1kcGRtVWdjVnh1SWpzZ1pYaHBkRHNnZlFva2JHbHVaWE1nUFNCbWFXeGxLQ1J3WVhSb0xDQkdTVXhGWDBsSFRrOVNSVjlPUlZkZlRFbE9SVk1wT3dva2JpQTlJREE3Q21admNtVmhZMmdnS0NSc2FXNWxjeUJoY3lBa2FTQTlQaUFrVENrZ2V3b2dJR2xtSUNoemRISnBjRzl6S0NSTUxDQWtjU2tnUFQwOUlHWmhiSE5sS1NCamIyNTBhVzUxWlRzS0lDQWtiaXNyT3dvZ0lHVmphRzhnS0NScElDc2dNU2tnTGlBaU9pQWlJQzRnY25SeWFXMG9KRXdwSUM0Z0lseHVJanNLSUNCcFppQW9KRzRnUGowZ05EQXBJSHNnWldOb2J5QWlMaTR1YzNSdmNIQmxaQ0JoZENBME1GeHVJanNnWW5KbFlXczdJSDBLZlFwcFppQW9JU1J1S1NCbFkyaHZJQ0p1YnlCdFlYUmphQ0JtYjNJNklDUnhYRzRpT3dwbFkyaHZJQ0l0TFMwZ0pHWWdhR0Z6SUNJZ0xpQmpiM1Z1ZENna2JHbHVaWE1wSUM0Z0lpQnNhVzVsY3lBdExTMWNiaUk3Q2c9PSJ9'), true);
$patches = json_decode(base64_decode('W10='), true);
if (!is_array($blocks) || !is_array($patches)) { out('PAYLOAD UNREADABLE - nothing written'); exit; }

/* 1. every patch anchor must be there BEFORE anything is written */
$ready = [];
foreach ($patches as $p) {
  list($rel, $anchor, $repl, $note) = $p;
  $f = $root . '/' . $rel;
  if (!is_file($f)) { out("MISSING FILE: $rel - nothing written"); exit; }
  $cur = (string)file_get_contents($f);
  if (strpos($cur, $repl) !== false) { out("already applied: $rel ($note)"); continue; }
  $n = substr_count($cur, $anchor);
  if ($n !== 1) { out("ANCHOR x$n in $rel ($note) - nothing written"); exit; }
  $ready[] = array($f, $rel, str_replace($anchor, $repl, $cur), $note);
}

/* 2. the new files */
$baks = 0;
foreach ($blocks as $rel => $b64) {
  $f = $root . '/' . $rel;
  $dir = dirname($f);
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { out("CANNOT MAKE DIR: " . dirname($rel)); exit; }
  if (is_file($f)) { @copy($f, $f . '.bak-' . $stamp . '-' . $tag); $baks++; }
  $body = base64_decode($b64);
  if ($body === false || strlen($body) < 20) { out("BAD PAYLOAD for $rel"); exit; }
  if (@file_put_contents($f, $body) === false) { out("CANNOT WRITE: $rel"); exit; }
  out("wrote $rel (" . strlen($body) . " bytes)");
}

/* 3. the patches */
foreach ($ready as $r) {
  list($f, $rel, $new, $note) = $r;
  $bk = $f . '.bak-' . $stamp . '-' . $tag;
  if (!is_file($bk)) { @copy($f, $bk); $baks++; }
  if (@file_put_contents($f, $new) === false) { out("PATCH WRITE FAILED: $rel"); exit; }
  out("patched $rel ($note)");
}

out('--- DONE. backups made: ' . $baks . ' ---');
