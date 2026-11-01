<?php
/* READ-ONLY site auditor for 3G's. Writes nothing, changes nothing, deletes nothing.
   Walks every domain's public_html and reports what is unfinished or unreadable. */
@ini_set('display_errors','0');
if (($_GET['go'] ?? '') !== '68865f330555a82d') { http_response_code(404); exit; }
header('Content-Type: application/json; charset=utf-8');
@set_time_limit(280);

$BASE = '/home/u732570899/domains';
$SKIP = '~/(_inst\d+|node_modules|\.git|vendor|tt_data|cache|backups?|old|_bak|uploads)/~i';

function walk($dir, &$out, $depth = 0) {
  global $SKIP;
  if ($depth > 7) return;
  $d = @opendir($dir); if (!$d) return;
  while (($f = readdir($d)) !== false) {
    if ($f === '.' || $f === '..') continue;
    $p = $dir . '/' . $f;
    if (is_dir($p)) { if (preg_match($SKIP, $p . '/')) continue; walk($p, $out, $depth + 1); continue; }
    if (!preg_match('/\.(html?|php)$/i', $f)) continue;
    if (preg_match('/\.bak[-.]|\.orig$|~$/i', $f)) continue;
    $out[] = $p;
  }
  closedir($d);
}

/* -------- the checks -------- */
function title($s) { return preg_match('~<title[^>]*>(.*?)</title>~is', $s, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : ''; }

function whiteText($s) {
  /* Every place the page paints text white or near-white, with a little context, so a
     human can tell whether there is a dark background behind it. */
  $hits = [];
  if (preg_match_all('~[^;{}\s"\']{0,70}color\s*:\s*(#fff(?:fff)?\b|#f[0-9a-f]f[0-9a-f]f[0-9a-f]f\b|white\b|rgba?\(\s*2[45][0-9]\s*,\s*2[45][0-9]\s*,\s*2[45][0-9][^)]*\))[^;{}]{0,40}~i', $s, $m, PREG_OFFSET_CAPTURE)) {
    foreach ($m[0] as $x) {
      $off = $x[1];
      $ctx = substr($s, max(0, $off - 220), 320);
      $hits[] = ['at' => $off, 'ctx' => preg_replace('/\s+/', ' ', $ctx)];
      if (count($hits) >= 25) break;
    }
  }
  return $hits;
}

function placeholders($s) {
  $pat = ['TODO', 'FIXME', 'XXX', 'lorem ipsum', 'Lorem Ipsum', 'coming soon', 'Coming Soon',
          'placeholder', 'Placeholder', 'your text here', 'Your Text Here', 'sample text',
          'TBD', 'tbd', 'INSERT ', 'replace me', 'Replace Me', 'dummy', 'test test',
          'Untitled', 'untitled', 'example.com', 'lipsum'];
  $f = [];
  foreach ($pat as $p) { $n = substr_count($s, $p); if ($n) $f[$p] = $n; }
  return $f;
}

function deadLinks($s, $file) {
  $root = dirname($file);
  $bad = [];
  if (preg_match_all('~<a[^>]+href\s*=\s*["\']([^"\']*)["\']~i', $s, $m)) {
    foreach (array_unique($m[1]) as $h) {
      $h = trim($h);
      if ($h === '' ) { $bad[] = '(empty href)'; continue; }
      if ($h === '#') { $bad[] = 'href="#"'; continue; }
      if (preg_match('~^(https?:|mailto:|tel:|sms:|javascript:|data:|#)~i', $h)) continue;
      $q = preg_replace('~[?#].*$~', '', $h);
      if ($q === '') continue;
      $t = ($q[0] === '/') ? null : $root . '/' . $q;
      if ($t !== null && !file_exists($t) && !is_dir($t) && !file_exists(rtrim($t,'/') . '/index.html') && !file_exists(rtrim($t,'/') . '/index.php')) $bad[] = $h;
    }
  }
  return array_values(array_unique(array_slice($bad, 0, 25)));
}

function deadImages($s, $file) {
  $root = dirname($file);
  $bad = [];
  if (preg_match_all('~<img[^>]+src\s*=\s*["\']([^"\']*)["\']~i', $s, $m)) {
    foreach (array_unique($m[1]) as $h) {
      $h = trim($h);
      if ($h === '') { $bad[] = '(empty src)'; continue; }
      if (preg_match('~^(https?:|data:)~i', $h)) continue;
      $q = preg_replace('~[?#].*$~', '', $h);
      $t = ($q !== '' && $q[0] === '/') ? null : $root . '/' . $q;
      if ($t !== null && !file_exists($t)) $bad[] = $h;
    }
  }
  return array_values(array_unique(array_slice($bad, 0, 25)));
}

function textOf($s) {
  $s = preg_replace('~<script\b[^>]*>.*?</script>~is', ' ', $s);
  $s = preg_replace('~<style\b[^>]*>.*?</style>~is', ' ', $s);
  $s = preg_replace('~<!--.*?-->~s', ' ', $s);
  $s = strip_tags($s);
  $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  return trim(preg_replace('/\s+/', ' ', $s));
}

$files = [];
foreach (@scandir($BASE) ?: [] as $dom) {
  if ($dom === '.' || $dom === '..') continue;
  $ph = $BASE . '/' . $dom . '/public_html';
  if (!is_dir($ph)) continue;
  walk($ph, $files);
}
sort($files);

$only = (string)($_GET['only'] ?? '');
$want = (string)($_GET['part'] ?? '');     // 'list' | 'detail'
$rep = ['base' => $BASE, 'count' => count($files), 'files' => []];

foreach ($files as $p) {
  $rel = str_replace($BASE . '/', '', $p);
  if ($only !== '' && strpos($rel, $only) === false) continue;
  $sz = (int)@filesize($p);
  $row = ['f' => $rel, 'bytes' => $sz, 'mtime' => date('Y-m-d H:i', (int)@filemtime($p))];
  if ($want === 'list') { $rep['files'][] = $row; continue; }
  $s = (string)@file_get_contents($p);
  $txt = textOf($s);
  $row['title'] = title($s);
  $row['textLen'] = strlen($txt);
  $row['hasViewport'] = (int)(bool)preg_match('~name=["\']viewport~i', $s);
  $row['white'] = whiteText($s);
  $row['ph'] = placeholders($s);
  $row['deadLinks'] = deadLinks($s, $p);
  $row['deadImgs'] = deadImages($s, $p);
  $rep['files'][] = $row;
}
echo json_encode($rep, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
