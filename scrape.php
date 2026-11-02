<?php
/* Read-only scrape of the client's live site: saves page text + image lists
   to thd/_scrape.txt, then serves it in slices. Writes nothing else. */
@ini_set('display_errors','0');
if (($_GET['go'] ?? '') !== '81fc29015ede084b') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);
$DEST='/home/u732570899/domains/threadedtruthsapparelco.com/public_html/thd';
$OUT=$DEST.'/_scrape.txt';
$SITE='https://ththeecannabiscompany.online';

function grab($url){
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>12,
    CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; site-migration/1.0)']);
  $b=curl_exec($ch); curl_close($ch); return $b;
}

if (($_GET['build'] ?? '') === '1' || !is_file($OUT)) {
  $buf='';
  foreach(['/','/clone-genetics','/contact'] as $p){
    $h=grab($SITE.$p);
    if(!$h){ $buf.="\n##### $p : FETCH FAILED\n"; continue; }
    $t=preg_replace('/<script[\s\S]*?<\/script>/i',' ',$h);
    $t=preg_replace('/<style[\s\S]*?<\/style>/i',' ',$t);
    $t=html_entity_decode(strip_tags($t), ENT_QUOTES|ENT_HTML5, 'UTF-8');
    $t=trim(preg_replace('/[ \t]+/',' ', preg_replace('/\n{3,}/',"\n\n",$t)));
    $buf.="\n\n##### PAGE $p #####\n".$t."\n";
    // alt text carries image meaning
    preg_match_all('/<img[^>]+alt="([^"]{3,120})"/i',$h,$a);
    if($a[1]) $buf.="\n--- ALTS $p ---\n".implode("\n",array_unique($a[1]))."\n";
  }
  // contact details
  preg_match_all('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/',$buf,$em);
  preg_match_all('/(?:\+?1[\s.\-]?)?\(?\d{3}\)?[\s.\-]?\d{3}[\s.\-]\d{4}/',$buf,$ph);
  $buf="##### CONTACT #####\nemails: ".implode(', ',array_slice(array_unique($em[0]),0,10))
      ."\nphones: ".implode(', ',array_slice(array_unique($ph[0]),0,10))."\n".$buf;
  @file_put_contents($OUT,$buf);
  echo "BUILT ".strlen($buf)." bytes\n";
  exit;
}
$s=@file_get_contents($OUT);
$n=max(0,(int)($_GET['p'] ?? 0));
$sz=2400;
echo "[slice $n of ".(int)ceil(strlen($s)/$sz)." | total ".strlen($s)."]\n";
echo substr($s,$n*$sz,$sz);
