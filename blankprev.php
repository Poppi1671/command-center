<?php
/* Builds /preview-blank/ : the root store with every printed garment
   swapped for the real blank photos. The live store is NOT touched. */
@ini_set('display_errors','0'); @ini_set('memory_limit','512M');
if (($_GET['go'] ?? '') !== '7a4d0e1eb4ad3cfc') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);
$ROOT='/home/u732570899/domains/threadedtruthsapparelco.com/public_html';
$OUT=$ROOT.'/preview-blank';
if(!is_dir($OUT)) @mkdir($OUT,0755,true);

$src=@file_get_contents($ROOT.'/index.html');
if($src===false){ echo "cannot read index.html\n"; exit; }
$before=strlen($src);
$rep=[];
function swap(&$s,$from,$to,$label,&$rep){
  $n=substr_count($s,$from);
  if($n){ $s=str_replace($from,$to,$s); $rep[]=sprintf('  %-46s x%d',$label,$n); }
  return $n;
}

/* 1. the JS that builds printed-set filenames on the fly */
swap($src, "IMG['set_' + S.color + '_' + key(S.w)]", "IMG['blank_crew_' + S.color]",
     'JS builder: matching-set image -> blank crew', $rep);
swap($src, "img('set_'+col+'_REDEEMED')", "img('blank_crew_'+col)",
     'JS builder: tile image -> blank crew', $rep);

/* 2. literal marketing shots */
foreach(['black','gray','white'] as $c){
  foreach(['REDEEMED','CHOSEN','FORGIVEN','UNASHAMED','NEW','YOURWORD'] as $w){
    swap($src, "set_{$c}_{$w}.webp", "blank_crew_{$c}.webp", "set_{$c}_{$w} -> blank_crew_{$c}", $rep);
  }
}
/* 3. the sunset hero crewneck carries a printed word */
swap($src, 'photo_hero.webp', 'blank_crew_black.webp', 'hero photo -> blank crew', $rep);

/* 4. make the copy run correctly from a subfolder, and keep it out of search */
$src = preg_replace('/<head([^>]*)>/i', '<head$1><base href="/">', $src, 1);
$src = preg_replace('/<meta\s+name="robots"[^>]*>/i', '', $src);
$src = preg_replace('/<title>/', '<title>[PREVIEW] ', $src, 1);
$src = str_replace('</head>', '<meta name="robots" content="noindex,nofollow"></head>', $src);

@file_put_contents($OUT.'/index.html',$src);

echo "PREVIEW BUILT  ($before -> ".strlen($src)." bytes)\n\n";
echo implode("\n",$rep)."\n\nchanges: ".count($rep)."\n\n";
echo "LEFTOVERS IN PREVIEW\n";
echo "  literal set_ refs : ".preg_match_all('/set_(black|gray|white)_[A-Z]+\.webp/',$src)."\n";
echo "  dynamic set_ build: ".preg_match_all("/set_['\\\"`]\s*\+/",$src)."\n";
echo "  photo_hero refs   : ".substr_count($src,'photo_hero.webp')."\n";
echo "\n--- live store untouched ---\n";
