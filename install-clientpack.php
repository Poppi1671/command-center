<?php
/* Wires the Client Pack tab into desk/index.html.
   Idempotent: running it twice changes nothing. Backs up before touching. */
@ini_set('display_errors','0');
if (($_GET['go'] ?? '') !== 'b769159658fc0ec4') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
$ROOT='/home/u732570899/domains/threadedtruthsapparelco.com/public_html';
$F=$ROOT.'/desk/index.html';

$s=@file_get_contents($F);
if($s===false){ echo "CANNOT READ desk/index.html\n"; exit; }
$before=strlen($s);
echo "read desk/index.html ($before bytes)\n";

if (strpos($s,'data-t="clients"')!==false) { echo "ALREADY INSTALLED — nothing changed\n"; exit; }

$steps=[];

/* 1. nav button, right after the CRM button */
if (preg_match('/<button\s+data-t="crm"[^>]*>.*?<\/button>/s', $s, $m)) {
  $s = str_replace($m[0], $m[0]."\n  <button data-t=\"clients\" aria-pressed=\"false\">Clients</button>", $s);
  $steps[] = 'nav button added after CRM';
} else { echo "FAIL: could not find the CRM nav button\n"; exit; }

/* 2. render map entry, before the crm entry */
if (preg_match('/crm:\s*function\s*\(\)\s*\{\s*return\s+window\.TTCRM/', $s, $m2, PREG_OFFSET_CAPTURE)) {
  $at = $m2[0][1];
  $ins = "clients: function(){ return window.TTCLIENTS ? TTCLIENTS.html() : '<div class=\"empty\">Loading…</div>'; },\n  ";
  $s = substr($s,0,$at) . $ins . substr($s,$at);
  $steps[] = 'render map entry added';
} else { echo "FAIL: could not find the crm render entry\n"; exit; }

/* 3. script tag, next to crm.js */
if (preg_match('/<script[^>]+src="crm\.js[^"]*"[^>]*><\/script>/', $s, $m3)) {
  $s = str_replace($m3[0], $m3[0]."\n<script src=\"clients.js?v=1\"></script>", $s);
  $steps[] = 'clients.js script tag added';
} else { echo "FAIL: could not find the crm.js script tag\n"; exit; }

$bk = $ROOT.'/desk/index.html.bak-'.date('Ymd-His').'-clientpack';
if(!@copy($F,$bk)){ echo "FAIL: could not back up\n"; exit; }
echo "backup: ".basename($bk)."\n";

if(@file_put_contents($F,$s)===false){ echo "FAIL: could not write\n"; exit; }
echo "wrote desk/index.html (".strlen($s)." bytes, +".(strlen($s)-$before).")\n";
foreach($steps as $t) echo "  - $t\n";
echo "--- DONE ---\n";
