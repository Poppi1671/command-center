<?php
/* 3G's Companion upgrade 9 (Oct 30, 2026): narrator reads word for word. Open once with ?go=f2355ed7319124f9. */
if (($_GET['go'] ?? '') !== 'f2355ed7319124f9') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = (basename(__DIR__) === '_install') ? dirname(__DIR__) : __DIR__; $tag = '.bak-20261030-fix9'; $out = ''; $bad = 0;
$P = json_decode('[["api/companion_kb2.php", "\'narrator\' => [\'Narrator\', \'MODE NARRATOR: You read and narrate. When given text, read it faithfully with warmth; when asked about it, summarize and explain. Do not invent content that is not in the text.\'],", "\'narrator\' => [\'Narrator\', \'MODE NARRATOR: You are a narrator. When the person gives you text to read (after words like read this, read me, narrate, say this, read aloud), your ENTIRE reply is that text word for word, with no introduction, no commentary and no closing line. Only when they ask a question about a text do you summarize or explain. Never invent content that is not in the text.\'],", "narrator reads verbatim"]]', true); $files = [];
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) { if (!is_file($f)) { $out .= "MISSING $p[0]\n"; $bad++; continue; } $files[$f] = file_get_contents($f); } }
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) continue; $n = substr_count($files[$f], $p[1]);
  if ($n !== 1) { $out .= "ANCHOR x$n ($p[3]) in $p[0]\n"; $bad++; continue; }
  $files[$f] = str_replace($p[1], $p[2], $files[$f]); $out .= "ok  $p[3]\n"; }
if ($bad) { echo "STOPPED, nothing changed.\n$out"; exit; }
foreach ($files as $f => $c) { if (!is_file($f . $tag)) copy($f, $f . $tag); file_put_contents($f, $c, LOCK_EX); $out .= "ok  " . str_replace($root . '/', '', $f) . " patched\n"; }
@unlink(__FILE__); echo $out . "\nDONE.\n";
