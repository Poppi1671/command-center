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

if (PHP_SAPI !== 'cli' && ($_GET['go'] ?? '') !== 'b08220648b23065d') { http_response_code(404); exit; }
if (PHP_SAPI === 'cli' && ($_GET['go'] ?? '') !== 'b08220648b23065d') { echo "bad token\n"; exit; }
header('Content-Type: text/plain; charset=utf-8');

$root = preg_match('~/_inst~', __DIR__) ? dirname(__DIR__) : __DIR__;
$tag  = 'find';
$stamp = date('Ymd');
out('installer running. root=' . $root . ' sapi=' . PHP_SAPI);

$blocks  = json_decode(base64_decode('eyJhcGkvX2ZpbmQucGhwIjogIlBEOXdhSEFLTHlvZ1ZHaHliM2RoZDJGNUlHRnVZMmh2Y2lCbWFXNWtaWEl1SUVSbGMyc2dhMlY1SUhKbGNYVnBjbVZrTGlCUWNtbHVkSE1nYldGMFkyaHBibWNnYkdsdVpYTWdjMjhnWVNCd1lYUmphQ0JqWVc0Z1ltVWdkM0pwZEhSbGJnb2dJQ0JoWjJGcGJuTjBJSFJvWlNCeVpXRnNJR1pwYkdVZ2QybDBhRzkxZENCa2IzZHViRzloWkdsdVp5QnBkQzRnVW1WdGIzWmxaQ0JpZVNCMGFHVWdibVY0ZENCcGJuTjBZV3hzWlhJdUlDb3ZDbVJsWTJ4aGNtVW9jM1J5YVdOMFgzUjVjR1Z6UFRFcE93cHlaWEYxYVhKbFgyOXVZMlVnWDE5RVNWSmZYeUF1SUNjdmMzWmpYMnhwWWk1d2FIQW5Pd3AwZEY5eVpYRjFhWEpsWDJGa2JXbHVLQ2s3Q21obFlXUmxjaWduUTI5dWRHVnVkQzFVZVhCbE9pQjBaWGgwTDNCc1lXbHVPeUJqYUdGeWMyVjBQWFYwWmkwNEp5azdDaVJtSUQwZ2NISmxaMTl5WlhCc1lXTmxLQ2QrVzE1aExYcEJMVm93TFRrdVh5MWRmaWNzSUNjbkxDQW9jM1J5YVc1bktTZ2tYMGRGVkZzblppZGRJRDgvSUNjbktTazdDaVJ4SUQwZ0tITjBjbWx1Wnlrb0pGOUhSVlJiSjNFblhTQS9QeUFuSnlrN0NpUndZWFJvSUQwZ1gxOUVTVkpmWHlBdUlDY3ZKeUF1SUNSbU93cHBaaUFvSkdZZ1BUMDlJQ2NuSUh4OElDRnBjMTltYVd4bEtDUndZWFJvS1NrZ2V5QmxZMmh2SUNKdWJ5QnpkV05vSUdacGJHVmNiaUk3SUdWNGFYUTdJSDBLYVdZZ0tDUnhJRDA5UFNBbkp5a2dleUJsWTJodklDSm5hWFpsSUhGY2JpSTdJR1Y0YVhRN0lIMEtKR3hwYm1WeklEMGdabWxzWlNna2NHRjBhQ3dnUmtsTVJWOUpSMDVQVWtWZlRrVlhYMHhKVGtWVEtUc0tKRzRnUFNBd093cG1iM0psWVdOb0lDZ2tiR2x1WlhNZ1lYTWdKR2tnUFQ0Z0pFd3BJSHNLSUNCcFppQW9jM1J5YVhCdmN5Z2tUQ3dnSkhFcElEMDlQU0JtWVd4elpTa2dZMjl1ZEdsdWRXVTdDaUFnSkc0ckt6c0tJQ0JsWTJodklDZ2thU0FySURFcElDNGdJam9nSWlBdUlISjBjbWx0S0NSTUtTQXVJQ0pjYmlJN0NpQWdhV1lnS0NSdUlENDlJRFF3S1NCN0lHVmphRzhnSWk0dUxuTjBiM0J3WldRZ1lYUWdOREJjYmlJN0lHSnlaV0ZyT3lCOUNuMEthV1lnS0NFa2Jpa2daV05vYnlBaWJtOGdiV0YwWTJnZ1ptOXlPaUFrY1Z4dUlqc0taV05vYnlBaUxTMHRJQ1JtSUdoaGN5QWlJQzRnWTI5MWJuUW9KR3hwYm1WektTQXVJQ0lnYkdsdVpYTWdMUzB0WEc0aU93bz0ifQ=='), true);
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
