<?php
/* Read-only scan for faith references and apparel/hero imagery. Changes nothing. */
@ini_set('display_errors','0'); @ini_set('memory_limit','512M');
if (($_GET['go'] ?? '') !== 'f5301bfa0afda35b') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);
$ROOT='/home/u732570899/domains/threadedtruthsapparelco.com/public_html';

$TERMS = ['faith','god','jesus','christ','bless','pray','scripture','bible','psalm',
          'amen','worship','church','holy','gospel','ministry','disciple','salvation',
          'grace','testimony','anointed','righteous','sermon','savior','saviour','lord',
          'heaven','spirit','believer','devotion','proverbs','corinthians','philippians',
          'romans 8','john 3','walk by faith','built by faith','kingdom'];

$SKIP_DIR = ['/_inst','/node_modules','/.git','/vendor','/public_html/public_html'];
$EXT = ['html','htm','php','js','json','css','txt','md'];

$files=[]; $imgs=[];
$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($ROOT, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST);
foreach($it as $f){
  $p = $f->getPathname();
  $rel = substr($p, strlen($ROOT));
  foreach($SKIP_DIR as $s){ if (strpos($rel, $s)===0 || strpos($rel, $s.'/')!==false) continue 2; }
  if (preg_match('/\.bak-|\.backup|-preview\.html|index-.*-preview/i', $rel)) continue;
  if (!$f->isFile()) continue;
  $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
  if (in_array($ext, ['png','jpg','jpeg','webp','svg','gif'], true)) { $imgs[]=[$rel,$f->getSize()]; continue; }
  if (!in_array($ext, $EXT, true)) continue;
  if ($f->getSize() > 3_000_000) continue;
  $files[] = $rel;
}

echo "=== PAGES SCANNED: ".count($files)."  IMAGES: ".count($imgs)." ===\n\n";
$totalHits=0; $hitFiles=0;
foreach($files as $rel){
  $txt = @file_get_contents($ROOT.$rel);
  if ($txt === false) continue;
  $low = strtolower($txt);
  $found=[];
  foreach($TERMS as $t){ $n = substr_count($low, $t); if($n) $found[$t]=$n; }
  if (!$found) continue;
  $hitFiles++;
  arsort($found);
  $sum = array_sum($found); $totalHits += $sum;
  echo "### $rel  (".$sum." hits)\n";
  $parts=[]; foreach($found as $t=>$n) $parts[]="$t:$n";
  echo "    ".implode('  ', $parts)."\n";
  // a few real lines of context
  $lines = preg_split('/\n/', $txt);
  $shown=0;
  foreach($lines as $i=>$ln){
    $l = strtolower($ln);
    foreach($found as $t=>$n){
      if (strpos($l,$t)!==false){
        $clip = trim(preg_replace('/\s+/',' ', strip_tags($ln)));
        if ($clip==='') $clip = trim(preg_replace('/\s+/',' ', $ln));
        if (mb_strlen($clip)>140) $clip = mb_substr($clip,0,140).'…';
        if ($clip!=='') { echo "    L".($i+1).": $clip\n"; $shown++; }
        break;
      }
    }
    if ($shown>=6) break;
  }
  echo "\n";
}
echo "=== $totalHits hits across $hitFiles files ===\n\n";

echo "=== IMAGE FILENAMES WORTH A LOOK (faith / apparel / hero) ===\n";
foreach($imgs as [$rel,$sz]){
  if (preg_match('/faith|god|jesus|christ|bless|pray|cross|church|holy|hero|hoodie|sweat|crew|tee|shirt|mock/i', $rel)){
    printf("  %-72s %6dKB\n", $rel, (int)round($sz/1024));
  }
}
echo "\n--- DONE ---\n";
