<?php
/* Post Bank -> Command Center. Nov 5 2026.
   Copies bank.html + bank-data.json into /desk and adds a gold "Post Bank" link to the desk nav.
   Backs up index.html. Idempotent. Self-deletes.
   ?go=TOKEN        dry run
   ?go=TOKEN&live=1 install */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
const TOKEN='bank1105';
if (($_GET['go'] ?? '') !== TOKEN) { http_response_code(403); exit("Forbidden\n"); }

$DESK='/home/u732570899/domains/threadedtruthsapparelco.com/public_html/desk';
$IDX=$DESK.'/index.html';
$LIVE=($_GET['live'] ?? '')==='1';
$A='<!-- POSTBANK:START -->'; $B='<!-- POSTBANK:END -->';

foreach (['bank.html','bank-data.json'] as $f) {
  if (!is_file(__DIR__.'/'.$f)) exit("FAIL: $f not found beside the installer\n");
}
if (!is_dir($DESK))      exit("FAIL: desk folder not found\n");
if (!is_file($IDX))      exit("FAIL: desk/index.html not found\n");
if (!is_writable($IDX))  exit("FAIL: desk/index.html not writable\n");
if (!is_writable($DESK)) exit("FAIL: desk folder not writable\n");

$html=(string)file_get_contents($IDX);
$hasNav = strpos($html,'<nav id="tabs">')!==false;
$hasBody= stripos($html,'</body>')!==false;
echo "desk/index.html : ".number_format(strlen($html))." bytes\n";
echo "nav#tabs found  : ".($hasNav?'yes':'NO')."\n";
echo "closing body    : ".($hasBody?'yes':'NO')."\n";
echo "bank.html       : ".number_format(filesize(__DIR__.'/bank.html'))." bytes\n";
echo "bank-data.json  : ".number_format(filesize(__DIR__.'/bank-data.json'))." bytes\n";
if(!$hasNav || !$hasBody) exit("\nFAIL: desk page is not what we expected. Nothing changed.\n");

$block=$A."\n"
."<style id=\"pbLink\">#ccBankBtn{background:#C9A24E!important;color:#14110D!important;border:1px solid #F3D27A!important;"
."border-radius:10px;padding:8px 14px;font:700 14px system-ui,sans-serif;text-decoration:none;cursor:pointer;"
."display:inline-block;margin:2px 4px;box-shadow:0 0 10px rgba(243,210,122,.45)}</style>\n"
."<script>(function(){function go(){var n=document.getElementById('tabs');if(!n)return setTimeout(go,300);"
."if(document.getElementById('ccBankBtn'))return;var a=document.createElement('a');a.id='ccBankBtn';"
."a.href='bank.html';a.textContent='Post Bank';n.appendChild(a);}"
."if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',go);else go();"
."setInterval(go,2000);})();</script>\n".$B;

$had = strpos($html,$A)!==false;
if($had){ $out=preg_replace('/'.preg_quote($A,'/').'.*?'.preg_quote($B,'/').'/s',$block,$html,1); }
else { $p=strripos($html,'</body>'); $out=substr($html,0,$p).$block."\n".substr($html,$p); }
if($out===null||$out==='') exit("\nFAIL: rewrite failed. Nothing changed.\n");

echo "\nprevious install: ".($had?'yes, replacing':'no, first run')."\n";
if(!$LIVE){ echo "\nDRY RUN. Nothing written. Add &live=1 to install.\n"; exit; }

$bak=$IDX.'.bak-'.date('Ymd-His').'-bank';
if(file_put_contents($bak,$html,LOCK_EX)===false) exit("\nFAIL: backup failed.\n");
$ok=true;
foreach(['bank.html','bank-data.json'] as $f){
  if(!@copy(__DIR__.'/'.$f, $DESK.'/'.$f)){ echo "FAIL copying $f\n"; $ok=false; }
}
if(!$ok) exit("\nFAIL: file copy failed. index.html untouched.\n");
$tmp=$IDX.'.tmp'.getmypid();
if(file_put_contents($tmp,$out,LOCK_EX)===false){ @unlink($tmp); exit("\nFAIL: temp write failed.\n"); }
if(!rename($tmp,$IDX)){ @unlink($tmp); exit("\nFAIL: could not replace index.html.\n"); }

$chk=(string)file_get_contents($IDX);
echo "\nINSTALLED.\n";
echo "backup        : ".basename($bak)."\n";
echo "bank.html     : ".(is_file($DESK.'/bank.html')?'in place':'MISSING')."\n";
echo "bank-data.json: ".(is_file($DESK.'/bank-data.json')?'in place':'MISSING')."\n";
echo "nav link      : ".(strpos($chk,'ccBankBtn')!==false?'added':'MISSING')."\n";
echo "\nOpen it at /desk/bank.html\n";
echo "To undo: rename ".basename($bak)." back to index.html and delete desk/bank.html\n";
@unlink(__FILE__);
