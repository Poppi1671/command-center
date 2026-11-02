<?php
/* Pulls the client's own images off his live Hostinger Builder site into
   public_html/thd/img/c/ and reports what is on his Store page.
   Read-only against his site. Writes nothing outside thd/img/c/. */
@ini_set('display_errors','0');
if (($_GET['go'] ?? '') !== '85760786bca1841a') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);

$DEST = '/home/u732570899/domains/threadedtruthsapparelco.com/public_html/thd/img/c';
if(!is_dir($DEST)) @mkdir($DEST, 0755, true);

$SITE = 'https://ththeecannabiscompany.online';
$CDN  = 'https://assets.zyrosite.com';
$ID   = '55FxXYYYI2OvyQti';

$files = [
 'nav'      => 'chatgpt-image-apr-29-2026-09_09_42-pm-6APshBZe7fjEf6R5.png',
 'hero'     => 'chatgpt-image-apr-29-2026-12_28_51-am-2-kdunos3qYr55oZuW.png',
 'band'     => 'chatgpt-image-apr-29-2026-12_33_17-am-PtfXJ1IbFGrMX6nE.png',
 'feature'  => 'chatgpt-image-apr-29-2026-09_07_07-pm-2-7veQfyekwM92NsuE.png',
 'svc1'     => 'chatgpt-image-apr-29-2026-12_32_52-am-5XSQJviYoxPpTli2.png',
 'svc2'     => 'chatgpt-image-apr-29-2026-12_33_36-am-nIGmSwh00QSjpiEK.png',
 'svc3'     => 'chatgpt-image-apr-29-2026-12_34_13-am-0CXnXAQiRvVH6UIt.png',
];

function grab($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_TIMEOUT=>30, CURLOPT_CONNECTTIMEOUT=>12,
    CURLOPT_SSL_VERIFYPEER=>true,
    CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; site-migration/1.0)',
  ]);
  $b = curl_exec($ch);
  $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $e = curl_error($ch);
  curl_close($ch);
  return [$b, $c, $e];
}

echo "=== IMAGES ===\n";
foreach($files as $name => $f){
  $try = [
    "$CDN/$ID/$f",
    "$CDN/cdn-cgi/image/format=auto,w=2000/$ID/$f",
    "$CDN/cdn-cgi/image/format=auto,w=1920,fit=crop/$ID/$f",
  ];
  $done = false;
  foreach($try as $i => $u){
    list($b, $code, $err) = grab($u);
    if($code == 200 && $b !== false && strlen($b) > 3000){
      $info = @getimagesizefromstring($b);
      $ext  = 'jpg';
      if($info){
        $mime = $info['mime'];
        if($mime === 'image/png') $ext = 'png';
        elseif($mime === 'image/webp') $ext = 'webp';
      }
      $out = "$DEST/$name.$ext";
      if(@file_put_contents($out, $b) !== false){
        printf("OK   %-8s %-5s %6dx%-5d %7d bytes  (try %d)\n",
          $name, $ext, $info?$info[0]:0, $info?$info[1]:0, strlen($b), $i+1);
        $done = true; break;
      }
    }
  }
  if(!$done) echo "FAIL $name  (last code $code $err)\n";
}

echo "\n=== STORE PAGE ===\n";
foreach(['/store','/clone-genetics','/contact'] as $p){
  list($b,$c,$e) = grab($SITE.$p);
  if($c != 200 || !$b){ echo "$p -> HTTP $c $e\n"; continue; }
  $txt = preg_replace('/<script[\s\S]*?<\/script>/i',' ', $b);
  $txt = preg_replace('/<style[\s\S]*?<\/style>/i',' ', $txt);
  $txt = trim(preg_replace('/\s+/',' ', strip_tags($txt)));
  preg_match_all('/\/([a-z0-9_\-]+\.(?:jpe?g|png|webp))/i', $b, $m);
  $imgs = array_values(array_unique($m[1]));
  echo "$p -> HTTP $c | imgs: ".count($imgs)."\n";
  foreach(array_slice($imgs,0,14) as $f) echo "    $f\n";
  echo "  TEXT: ".substr($txt,0,700)."\n\n";
}

// phone / email hunting across the whole site
echo "=== CONTACT DETAILS FOUND ===\n";
$all = '';
foreach(['/','/contact','/store'] as $p){ list($b,$c)=grab($SITE.$p); if($b) $all .= ' '.$b; }
preg_match_all('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', $all, $em);
preg_match_all('/(?:\+?1[\s.\-]?)?\(?\d{3}\)?[\s.\-]\d{3}[\s.\-]\d{4}/', $all, $ph);
echo "emails: ".implode(', ', array_slice(array_unique($em[0]),0,8))."\n";
echo "phones: ".implode(', ', array_slice(array_unique($ph[0]),0,8))."\n";
echo "\n--- DONE ---\n";
