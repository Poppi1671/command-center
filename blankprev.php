<?php
/* Builds /preview-blank/ : a copy of the root store with printed apparel
   swapped for the real blank photos. Does NOT touch the live store. */
@ini_set('display_errors','0'); @ini_set('memory_limit','512M');
if (($_GET['go'] ?? '') !== '194cc82aee9c5667') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);
$ROOT='/home/u732570899/domains/threadedtruthsapparelco.com/public_html';
$OUT=$ROOT.'/preview-blank';
if(!is_dir($OUT)) @mkdir($OUT,0755,true);

$src=@file_get_contents($ROOT.'/index.html');
if($src===false){ echo "cannot read index.html\n"; exit; }
$before=strlen($src);

/* marketing set shots -> real blank crewneck photo of the same colour */
$map=[];
foreach(['black','gray','white'] as $c){
  foreach(['REDEEMED','CHOSEN','FORGIVEN','UNASHAMED','NEW','YOURWORD'] as $w){
    $map["set_{$c}_{$w}.webp"] = "blank_crew_{$c}.webp";
  }
}
/* the sunset hero crewneck carries a printed word -> blank crewneck */
$map['photo_hero.webp'] = 'blank_crew_black.webp';

$report=[];
foreach($map as $from=>$to){
  $n = substr_count($src,$from);
  if($n){ $src=str_replace($from,$to,$src); $report[]=sprintf('  %-28s -> %-22s x%d',$from,$to,$n); }
}

/* make relative asset paths resolve from the subfolder */
$src = str_replace(
  ['href="assets/','src="assets/','url(assets/',"url('assets/",'href="custom/','src="custom/'],
  ['href="../assets/','src="../assets/','url(../assets/',"url('../assets/",'href="../custom/','src="../custom/'],
  $src);
$src = preg_replace('/<title>/', '<title>[PREVIEW — BLANK APPAREL] ', $src, 1);
$src = preg_replace('/<meta name="robots"[^>]*>/i','',$src);
$src = str_replace('</head>','<meta name="robots" content="noindex,nofollow"></head>',$src);

@file_put_contents($OUT.'/index.html',$src);
echo "PREVIEW BUILT  (".$before." -> ".strlen($src)." bytes)\n\n";
echo "IMAGE SWAPS APPLIED:\n".implode("\n",$report)."\n";
echo "\nswaps: ".count($report)."\n";
echo "remaining printed-set refs: ".preg_match_all('/set_(black|gray|white)_[A-Z]+\.webp/',$src)."\n";
echo "remaining photo_hero refs:  ".substr_count($src,'photo_hero.webp')."\n";
echo "\n--- live store untouched ---\n";
