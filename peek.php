<?php
/* READ-ONLY file viewer for the audit. Writes nothing, changes nothing.
   ?f=<path under domains>&find=<needle>  -> line numbers + context
   ?f=<path>&from=<line>&to=<line>        -> that slice */
@ini_set('display_errors','0');
if (($_GET['go'] ?? '') !== '3a93477faf666b08') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
$BASE='/home/u732570899/domains';
$f=(string)($_GET['f'] ?? '');
if ($f==='' || strpos($f,'..')!==false) { echo "bad path\n"; exit; }
$p=$BASE.'/'.$f;
if (!is_file($p)) { echo "not a file: $f\n"; exit; }
$lines=@file($p, FILE_IGNORE_NEW_LINES);
if ($lines===false) { echo "unreadable\n"; exit; }
echo "FILE $f  lines=".count($lines)."  bytes=".filesize($p)."\n";
$needle=(string)($_GET['find'] ?? '');
if ($needle!=='') {
  $n=0;
  foreach ($lines as $i=>$L) {
    if (stripos($L,$needle)!==false) {
      $n++; echo "--- line ".($i+1)." ---\n";
      for ($j=max(0,$i-1); $j<=min(count($lines)-1,$i+1); $j++) echo ($j+1).": ".substr($lines[$j],0,400)."\n";
      if ($n>=(int)($_GET['max'] ?? 12)) break;
    }
  }
  if (!$n) echo "no match for: $needle\n";
  exit;
}
$from=max(1,(int)($_GET['from'] ?? 1));
$to=min(count($lines), (int)($_GET['to'] ?? ($from+60)));
for ($i=$from-1; $i<$to; $i++) echo ($i+1).": ".substr($lines[$i],0,500)."\n";
