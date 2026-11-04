<?php
/* 3G's AI Web Solutions — $297 hero seal + remove the old book card. Nov 3 2026.
   Injects one marked block before </body>. Backs up. Idempotent. Self-deletes.
   ?go=TOKEN        -> dry run, changes nothing
   ?go=TOKEN&live=1 -> write it */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
const TOKEN = 'seal297nov3';
if (($_GET['go'] ?? '') !== TOKEN) { http_response_code(403); exit("Forbidden\n"); }

$F = '/home/u732570899/domains/threadedtruthsapparelco.com/public_html/3gs-web-solutions/index.html';
$LIVE = ($_GET['live'] ?? '') === '1';
$MARK_A = '<!-- UC297SEAL:START -->';
$MARK_B = '<!-- UC297SEAL:END -->';

if (!is_file($F))     exit("FAIL: index.html not found\n");
if (!is_writable($F)) exit("FAIL: index.html is not writable\n");
$html = (string)file_get_contents($F);
echo "File: " . number_format(strlen($html)) . " bytes\n";

/* sanity: the things we are targeting must actually be there */
$hasFilm = (strpos($html, 'id="film"') !== false) || (strpos($html, "id='film'") !== false);
$hasHero = strpos($html, 'class="hero"') !== false;
$hasBody = stripos($html, '</body>') !== false;
echo "hero section found : " . ($hasHero ? 'yes' : 'NO') . "\n";
echo "book card (#film)  : " . ($hasFilm ? 'yes' : 'NO') . "\n";
echo "closing body tag   : " . ($hasBody ? 'yes' : 'NO') . "\n";
if (!$hasHero || !$hasFilm || !$hasBody) exit("\nFAIL: page is not what we expected. Nothing changed.\n");

$block = $MARK_A . "\n"
. "<style id=\"uc297\">\n"
. "#film{display:none!important}\n"
. ".uc-seal{position:absolute;top:clamp(14px,4vw,78px);right:clamp(12px,3.4vw,58px);z-index:6;\n"
. " width:clamp(104px,13vw,228px);height:clamp(104px,13vw,228px);border-radius:50%;\n"
. " border:3px solid #E8C877;background:#C0102A;color:#fff;text-decoration:none;\n"
. " display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;\n"
. " transform:rotate(-9deg);box-shadow:0 10px 40px rgba(0,0,0,.55);\n"
. " font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;\n"
. " transition:transform .22s ease,box-shadow .22s ease}\n"
. ".uc-seal:hover,.uc-seal:focus-visible{transform:rotate(-9deg) scale(1.05);box-shadow:0 14px 50px rgba(0,0,0,.65)}\n"
. ".uc-seal .k{font-size:clamp(7px,.85vw,13px);letter-spacing:.2em;text-transform:uppercase;color:#FFE7A8;font-weight:700}\n"
. ".uc-seal .amt{font-family:Lora,Georgia,serif;font-size:clamp(28px,3.6vw,66px);font-weight:600;line-height:1;margin:.08em 0 .05em}\n"
. ".uc-seal .nm{font-size:clamp(7px,.82vw,13px);letter-spacing:.1em;text-transform:uppercase;color:#F6DADF;font-weight:600;line-height:1.35}\n"
. "@media(max-width:620px){.uc-seal{transform:rotate(-7deg)}}\n"
. "</style>\n"
. "<script>(function(){function go(){var h=document.querySelector('section.hero');\n"
. "if(!h||h.querySelector('.uc-seal'))return;\n"
. "if(getComputedStyle(h).position==='static')h.style.position='relative';\n"
. "var a=document.createElement('a');a.className='uc-seal';a.href='#dvShop';\n"
. "a.setAttribute('aria-label','Start with the Quick Fix, 297 dollars');\n"
. "a.innerHTML='<span class=\"k\">Start here</span><span class=\"amt\">\$297</span><span class=\"nm\">Quick Fix<br>Done in one pass</span>';\n"
. "h.appendChild(a);}\n"
. "if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',go);else go();})();</script>\n"
. $MARK_B;

/* replace an earlier run, else insert before </body> */
$had = (strpos($html, $MARK_A) !== false);
if ($had) {
  $out = preg_replace('/' . preg_quote($MARK_A, '/') . '.*?' . preg_quote($MARK_B, '/') . '/s', $block, $html, 1);
} else {
  $pos = strripos($html, '</body>');
  $out = substr($html, 0, $pos) . $block . "\n" . substr($html, $pos);
}
if ($out === null || $out === '' || strlen($out) < strlen($html) - 10) exit("\nFAIL: rewrite looked wrong. Nothing changed.\n");

echo "\nprevious install   : " . ($had ? 'yes, replacing it' : 'no, first run') . "\n";
echo "block size         : " . number_format(strlen($block)) . " bytes\n";
echo "result size        : " . number_format(strlen($out)) . " bytes\n";

if (!$LIVE) { echo "\nDRY RUN. Nothing written. Add &live=1 to install.\n"; exit; }

$bak = $F . '.bak-' . date('Ymd-His') . '-seal';
if (file_put_contents($bak, $html, LOCK_EX) === false) exit("\nFAIL: backup failed. Nothing changed.\n");
$tmp = $F . '.tmp' . getmypid();
if (file_put_contents($tmp, $out, LOCK_EX) === false) { @unlink($tmp); exit("\nFAIL: temp write failed.\n"); }
if (!rename($tmp, $F)) { @unlink($tmp); exit("\nFAIL: could not replace index.html.\n"); }

$chk = (string)file_get_contents($F);
echo "\nINSTALLED.\n";
echo "backup   : " . basename($bak) . "\n";
echo "seal css : " . (strpos($chk,'uc-seal') !== false ? 'present' : 'MISSING') . "\n";
echo "film hide: " . (strpos($chk,'#film{display:none') !== false ? 'present' : 'MISSING') . "\n";
echo "\nTo undo: rename " . basename($bak) . " back to index.html\n";
@unlink(__FILE__);
