<?php
/* 3G's Companion upgrade 6 (Oct 30, 2026): DALL-E 3 avatar fix (URL result). Open once with ?go=67545d936fe5b251. */
if (($_GET['go'] ?? '') !== '67545d936fe5b251') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = (basename(__DIR__) === '_install') ? dirname(__DIR__) : __DIR__; $tag = '.bak-20261030-fix6'; $out = ''; $bad = 0;
$P = json_decode('[["api/companion.php", "[\'model\' => \'dall-e-3\', \'prompt\' => $p, \'size\' => \'1024x1024\', \'quality\' => \'standard\', \'n\' => 1, \'response_format\' => \'b64_json\']", "[\'model\' => \'dall-e-3\', \'prompt\' => $p, \'size\' => \'1024x1024\', \'quality\' => \'standard\', \'n\' => 1]", "dall-e-3 params"], ["api/companion.php", "    $j = json_decode($r, true); $b64 = is_array($j) ? (string)($j[\'data\'][0][\'b64_json\'] ?? \'\') : \'\';\\n    if ($c === 200 && $b64 !== \'\') break;", "    $j = json_decode($r, true); $b64 = is_array($j) ? (string)($j[\'data\'][0][\'b64_json\'] ?? \'\') : \'\';\\n    if ($c === 200 && $b64 === \'\' && !empty($j[\'data\'][0][\'url\'])) { $ch2 = curl_init((string)$j[\'data\'][0][\'url\']); curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => true]); $bin = (string)curl_exec($ch2); curl_close($ch2); if (strlen($bin) > 1000) $b64 = base64_encode($bin); }\\n    if ($c === 200 && $b64 !== \'\') break;", "download url result"]]', true); $files = [];
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) { if (!is_file($f)) { $out .= "MISSING $p[0]\n"; $bad++; continue; } $files[$f] = file_get_contents($f); } }
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) continue; $n = substr_count($files[$f], $p[1]);
  if ($n !== 1) { $out .= "ANCHOR x$n ($p[3]) in $p[0]\n"; $bad++; continue; }
  $files[$f] = str_replace($p[1], $p[2], $files[$f]); $out .= "ok  $p[3]\n"; }
if ($bad) { echo "STOPPED, nothing changed.\n$out"; exit; }
foreach ($files as $f => $c) { if (!is_file($f . $tag)) copy($f, $f . $tag); file_put_contents($f, $c, LOCK_EX); $out .= "ok  " . str_replace($root . '/', '', $f) . " patched\n"; }
$o = []; $rc = 0; @exec('php -l ' . escapeshellarg($root . '/api/companion.php') . ' 2>&1', $o, $rc); $out .= ($rc === 0 ? 'ok  ' : 'ERR ') . 'syntax: ' . trim(implode(' ', $o)) . "\n";
if ($rc !== 0) { foreach ($files as $f => $c) copy($f . $tag, $f); $out .= "ROLLED BACK\n"; } else { @unlink(__FILE__); $out .= "\nDONE.\n"; }
echo $out;
