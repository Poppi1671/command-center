<?php
/* 3G's Companion upgrade installer (Oct 30, 2026): knowledge (Wikipedia), world news (Google News), voice talk + voice mode.
   Open this file once in the browser with ?go=74aafa413e02a6ea. It backs up, patches, verifies, then deletes itself. */
if (($_GET['go'] ?? '') !== '74aafa413e02a6ea') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = (basename(__DIR__) === '_install') ? dirname(__DIR__) : __DIR__; $tag = '.bak-20261030-kb'; $out = ''; $bad = 0;
$P = json_decode('[["api/companion.php", "require_once __DIR__ . \'/svc_lib.php\';", "require_once __DIR__ . \'/svc_lib.php\';\\nrequire_once __DIR__ . \'/companion_kb.php\';", "load knowledge layer"], ["api/companion.php", "Respect topics they asked to avoid.\\\\n\\"", "Respect topics they asked to avoid.\\\\n\\"\\n    . \\"KNOWLEDGE: You can answer questions about the world (history, science, places, people, words, how things work) clearly and accurately in plain words, like a friendly encyclopedia, then bring it back to the person. If reference material or news headlines are given below, prefer them and name the source in a few words. Never invent news.\\\\n\\"", "companion knowledge rule"], ["api/companion.php", "No medical, legal or financial advice. If the person seems in danger or mentions self-harm, urge them to call or text 988 or 911.\\\\n\\"", "No medical, legal or financial advice. If the person seems in danger or mentions self-harm, urge them to call or text 988 or 911.\\\\nKNOWLEDGE: You can answer general questions about the world and today\'s news when reference material or headlines are given below; use only those for news and name the source.\\\\n\\"", "host knowledge rule"], ["api/companion.php", "  $msgs = [[\'role\' => \'system\', \'content\' => $sys]];", "  $KB = kb_context($text); if ($KB[\'text\'] !== \'\') $sys .= \\"\\\\n\\" . $KB[\'text\'];\\n  $msgs = [[\'role\' => \'system\', \'content\' => $sys]];", "inject reference material"], ["api/companion.php", "  cp([\'ok\' => true, \'reply\' => $reply]);\\n}", "  cp([\'ok\' => true, \'reply\' => $reply, \'sources\' => $KB[\'sources\']]);\\n}", "return sources"], ["api/companion.php", "if ($a === \'remove\') {", "if ($a === \'listen\') {\\n  $key = ck(); if ($key === \'\') cp([\'ok\' => false, \'err\' => \'The voice engine is not switched on for this server yet.\']);\\n  $raw = base64_decode((string)($in[\'audio\'] ?? \'\'), true); $mime = explode(\';\', (string)($in[\'mime\'] ?? \'audio/webm\'))[0];\\n  if ($raw === false || strlen($raw) < 500 || strlen($raw) > 6000000) cp([\'ok\' => false, \'err\' => \'I did not catch that. Try again.\'], 400);\\n  $EXT = [\'audio/webm\' => \'webm\', \'audio/mp4\' => \'mp4\', \'audio/ogg\' => \'ogg\', \'audio/mpeg\' => \'mp3\', \'audio/mp3\' => \'mp3\', \'audio/wav\' => \'wav\', \'audio/x-m4a\' => \'m4a\', \'audio/aac\' => \'aac\'];\\n  if (!isset($EXT[$mime])) $mime = \'audio/webm\'; $ext = $EXT[$mime];\\n  $tmp = $T . \'/_voice_\' . bin2hex(random_bytes(4)) . \'.\' . $ext; file_put_contents($tmp, $raw);\\n  $ch = curl_init(\'https://api.openai.com/v1/audio/transcriptions\');\\n  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90, CURLOPT_POST => true, CURLOPT_POSTFIELDS => [\'model\' => \'gpt-4o-mini-transcribe\', \'file\' => new CURLFile($tmp, $mime, \'voice.\' . $ext), \'language\' => \'en\'], CURLOPT_HTTPHEADER => [\'Authorization: Bearer \' . $key]]);\\n  $r = (string)curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); @unlink($tmp);\\n  $j = json_decode($r, true); $txt = is_array($j) ? trim((string)($j[\'text\'] ?? \'\')) : \'\';\\n  if ($c !== 200 || $txt === \'\') cp([\'ok\' => false, \'err\' => \'I could not hear that. Try again.\']);\\n  cp([\'ok\' => true, \'text\' => mb_substr($txt, 0, 1500)]);\\n}\\nif ($a === \'remove\') {", "voice listen action"], ["desk/companion.html", "<div class=\\"card\\"><h2>Talk</h2>", "<div class=\\"card\\"><h2>Talk</h2><div class=\\"note\\">Type or tap <b>Talk</b> and speak. Ask it anything: your day, world facts, history, or today\'s news. It looks things up and names its sources. Turn on <b>Voice mode</b> to hear every reply.</div>", "talk note"], ["desk/companion.html", "<button id=\\"sd\\">Send</button>", "<button id=\\"sd\\">Send</button><button id=\\"mic\\" class=\\"ghost\\">🎤 Talk</button><label style=\\"display:inline-block;font-weight:600;margin-left:6px\\"><input id=\\"vm\\" type=\\"checkbox\\" style=\\"width:auto;margin-right:6px\\">Voice mode</label>", "mic + voice mode"], ["desk/companion.html", "function bub(cls,t,speak){", "function bub(cls,t,speak,src){", "bub signature"], ["desk/companion.html", "d.appendChild(s)}$(\'ch\').appendChild(d);", "d.appendChild(s)}if(src&&src.length){var sl=document.createElement(\'div\');sl.style.cssText=\'font-size:13px;opacity:.85;margin-top:6px\';sl.textContent=\'Sources: \';src.slice(0,4).forEach(function(x,i){var a=document.createElement(\'a\');a.href=x.u;a.target=\'_blank\';a.rel=\'noopener\';a.textContent=x.s+(x.t?\' \\\\u00b7 \'+x.t:\'\');sl.appendChild(a);if(i<Math.min(src.length,4)-1)sl.appendChild(document.createTextNode(\'  |  \'))});d.appendChild(sl)}$(\'ch\').appendChild(d);", "render sources"], ["desk/companion.html", "if(r.ok){bub(\'c\',r.reply,true);status()}", "if(r.ok){bub(\'c\',r.reply,true,r.sources);if($(\'vm\').checked)speakLast(r.reply);status()}", "auto voice reply"], ["desk/companion.html", "$(\'sd\').onclick=send;", "$(\'sd\').onclick=send;\\nfunction speakLast(t){call(\'speak\',{id:cur,text:t}).then(function(r){if(r.ok){var a=$(\'au\');a.src=\'data:audio/mpeg;base64,\'+r.audio;a.style.display=\'block\';a.play().catch(function(){});a.onended=function(){if($(\'vm\').checked&&!rec&&!(mrec&&mrec.state===\'recording\'))$(\'mic\').click()}}})}\\nvar SR=window.SpeechRecognition||window.webkitSpeechRecognition,rec=null,mrec=null,mchunks=[];\\n$(\'mic\').onclick=function(){if(!cur){say(\'m4\',\'Open a companion first.\',1);return}\\nif(rec||(mrec&&mrec.state===\'recording\')){if(rec)rec.stop();if(mrec&&mrec.state===\'recording\')mrec.stop();return}\\nif(SR){rec=new SR();rec.lang=\'en-US\';rec.interimResults=false;rec.maxAlternatives=1;$(\'mic\').textContent=\'Listening... tap to stop\';say(\'m4\',\'\');\\nrec.onresult=function(e){var t=e.results[0][0].transcript;$(\'tx\').value=t};rec.onerror=function(e){if(e.error!==\'no-speech\'&&e.error!==\'aborted\')say(\'m4\',\'Mic trouble (\'+e.error+\'). Try again or type.\',1)};\\nrec.onend=function(){rec=null;$(\'mic\').textContent=\'\\\\U0001F3A4 Talk\';if($(\'tx\').value.trim())send()};rec.start();return}\\nif(!navigator.mediaDevices||!window.MediaRecorder){say(\'m4\',\'This browser cannot record. Try Chrome.\',1);return}\\nnavigator.mediaDevices.getUserMedia({audio:true}).then(function(st){mchunks=[];mrec=new MediaRecorder(st);mrec.ondataavailable=function(e){if(e.data.size)mchunks.push(e.data)};\\nmrec.onstop=function(){st.getTracks().forEach(function(t){t.stop()});$(\'mic\').textContent=\'\\\\U0001F3A4 Talk\';var bl=new Blob(mchunks,{type:mrec.mimeType||\'audio/webm\'});var fr=new FileReader();fr.onload=function(){say(\'m4\',\'Listening...\');call(\'listen\',{audio:String(fr.result).split(\',\')[1],mime:bl.type}).then(function(r){if(r.ok){$(\'tx\').value=r.text;say(\'m4\',\'\');send()}else say(\'m4\',r.err||\'Could not hear that\',1)})};fr.readAsDataURL(bl)};\\nmrec.start();$(\'mic\').textContent=\'Listening... tap to stop\';setTimeout(function(){if(mrec&&mrec.state===\'recording\')mrec.stop()},30000)}).catch(function(){say(\'m4\',\'Microphone is blocked. Allow it in the browser.\',1)})};", "mic logic"]]', true);
$KB = <<<'KBFILE'
<?php
/* 3G's Companion - knowledge + world news layer (added Oct 30, 2026).
   Looks up Wikipedia (free encyclopedia) for factual questions and Google News headlines for current events,
   then hands the companion a short reference block with sources. Cached in tt_data/companion/kb. No secrets here. */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'companion_kb.php') { http_response_code(404); exit; }

function kb_dir(): string { $d = dirname(__DIR__, 2) . '/tt_data/companion/kb'; if (!is_dir($d)) @mkdir($d, 0750, true); return $d; }

function kb_get(string $url, int $ttl, string $accept = 'application/json'): string {
  $f = kb_dir() . '/' . md5($url) . '.cache';
  if (is_file($f) && time() - filemtime($f) < $ttl) return (string)file_get_contents($f);
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
    CURLOPT_HTTPHEADER => ['Accept: ' . $accept, 'User-Agent: 3GsCompanion/1.0 (urbancanvasapparelco.com; support@threadedtruthsapparelco.com)']]);
  $r = (string)curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($c === 200 && $r !== '') { @file_put_contents($f, $r, LOCK_EX); return $r; }
  return is_file($f) ? (string)file_get_contents($f) : '';
}

function kb_is_news(string $t): bool {
  return (bool)preg_match('/\b(news|headlines?|latest|today|tonight|this week|this morning|happening|going on|breaking|update on|current events|right now|stock market|markets?|wall street|election|weather|scores?|game last night)\b/i', $t);
}
function kb_is_fact(string $t): bool {
  if (mb_strlen($t) < 8) return false;
  if (preg_match('/\b(i feel|i am|i\'m|im |my day|my (wife|husband|son|daughter|mom|mother|dad|father|job|boss|kids|family)|lonely|sad|tired|anxious|pray for me|thank you|good morning|good night)\b/i', $t)) return false;
  return (bool)preg_match('/\b(who|what|when|where|why|how|which|define|definition|explain|tell me about|history of|facts? about|meaning of|capital of|population of|how many|how far|how old|how tall|what year|is it true|what is|what are|who was|who is)\b/i', $t) || substr(trim($t), -1) === '?';
}
function kb_topic(string $t): string {
  $t = preg_replace('/[^\p{L}\p{N}\s\'-]/u', ' ', $t);
  $t = preg_replace('/\b(please|can you|could you|would you|tell me|about|what|whats|who|whos|when|where|why|how|which|is|are|was|were|the|a|an|of|do|does|did|in|on|for|to|me|i|you|explain|define|definition|meaning|news|latest|today|tonight|happening|going|with|some|any|give|know|something|anything|s)\b/iu', ' ', $t);
  return trim(preg_replace('/\s+/', ' ', $t));
}

function kb_wiki(string $q): ?array {
  $topic = kb_topic($q); if ($topic === '') return null;
  $s = kb_get('https://en.wikipedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srsearch=' . rawurlencode($topic), 7 * 86400);
  $j = json_decode($s, true); $title = (string)($j['query']['search'][0]['title'] ?? ''); if ($title === '') return null;
  $r = kb_get('https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode(str_replace(' ', '_', $title)), 7 * 86400);
  $p = json_decode($r, true); $ex = trim((string)($p['extract'] ?? '')); if ($ex === '') return null;
  return ['title' => $title, 'text' => mb_substr($ex, 0, 900), 'url' => (string)($p['content_urls']['desktop']['page'] ?? ('https://en.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $title))))];
}

function kb_news(string $q): array {
  $topic = kb_topic($q);
  $url = $topic === '' ? 'https://news.google.com/rss?hl=en-US&gl=US&ceid=US:en' : 'https://news.google.com/rss/search?q=' . rawurlencode($topic) . '&hl=en-US&gl=US&ceid=US:en';
  $x = kb_get($url, 20 * 60, 'application/rss+xml, application/xml, text/xml'); if ($x === '') return [];
  $o = [];
  if (preg_match_all('#<item>(.*?)</item>#s', $x, $m)) {
    foreach (array_slice($m[1], 0, 6) as $it) {
      preg_match('#<title>(?:<!\[CDATA\[)?(.*?)(?:\]\]>)?</title>#s', $it, $t); preg_match('#<link>(.*?)</link>#s', $it, $l);
      preg_match('#<pubDate>(.*?)</pubDate>#s', $it, $d); preg_match('#<source[^>]*>(.*?)</source>#s', $it, $sn);
      $title = html_entity_decode(trim($t[1] ?? ''), ENT_QUOTES | ENT_HTML5); if ($title === '') continue;
      $o[] = ['title' => $title, 'url' => trim($l[1] ?? ''), 'date' => trim($d[1] ?? ''), 'source' => html_entity_decode(trim($sn[1] ?? ''), ENT_QUOTES | ENT_HTML5)];
    }
  }
  return $o;
}

/* Returns ['text' => system-prompt block, 'sources' => [[t,u,s],...]] */
function kb_context(string $text): array {
  $out = ['text' => '', 'sources' => []];
  $news = kb_is_news($text); $fact = kb_is_fact($text);
  if (!$news && !$fact) return $out;
  $ctx = '';
  if ($news) {
    $items = kb_news($text);
    if ($items) {
      $ctx .= "LIVE NEWS HEADLINES (Google News, fetched " . gmdate('M j, Y H:i') . " UTC). For anything about current events use ONLY these. Do not add facts, causes or predictions that are not here. Say which outlet reported it. No investment advice.\n";
      foreach ($items as $i) { $ctx .= '- ' . $i['title'] . ($i['source'] !== '' ? ' (' . $i['source'] . ')' : '') . ($i['date'] !== '' ? ' [' . substr($i['date'], 0, 16) . ']' : '') . "\n"; $out['sources'][] = ['t' => $i['title'], 'u' => $i['url'], 's' => $i['source'] !== '' ? $i['source'] : 'Google News']; }
    }
  }
  if ($fact && !$news) {
    $w = kb_wiki($text);
    if ($w) { $ctx .= "REFERENCE (Wikipedia, \"" . $w['title'] . "\"): " . $w['text'] . "\nIf this matches the question, use it and mention Wikipedia in a few words. If it does not match, ignore it and answer from general knowledge.\n"; $out['sources'][] = ['t' => $w['title'], 'u' => $w['url'], 's' => 'Wikipedia']; }
  }
  $out['text'] = $ctx; return $out;
}
KBFILE;
$files = [];
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) { if (!is_file($f)) { $out .= "MISSING $p[0]\n"; $bad++; continue; } $files[$f] = file_get_contents($f); } }
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) continue; $n = substr_count($files[$f], $p[1]);
  if ($n !== 1) { $out .= "ANCHOR x$n ($p[3]) in $p[0]\n"; $bad++; continue; }
  $files[$f] = str_replace($p[1], $p[2], $files[$f]); $out .= "ok  $p[3]\n"; }
if ($bad) { echo "STOPPED, nothing changed.\n$out"; exit; }
file_put_contents($root . '/api/companion_kb.php', $KB, LOCK_EX); $out .= "ok  api/companion_kb.php written\n";
foreach ($files as $f => $c) { if (!is_file($f . $tag)) copy($f, $f . $tag); file_put_contents($f, $c, LOCK_EX); $out .= "ok  " . str_replace($root . '/', '', $f) . " patched (backup " . basename($f) . "$tag)\n"; }
$lint = []; foreach (['api/companion.php', 'api/companion_kb.php'] as $pf) { $o = []; $rc = 0; @exec('php -l ' . escapeshellarg($root . '/' . $pf) . ' 2>&1', $o, $rc); $lint[] = ($rc === 0 ? 'ok  ' : 'ERR ') . $pf . ' syntax: ' . trim(implode(' ', $o)); }
$out .= implode("\n", $lint) . "\n";
if (strpos(implode($lint), 'ERR ') !== false) { foreach ($files as $f => $c) copy($f . $tag, $f); $out .= "ROLLED BACK because of a syntax error.\n"; }
else { @unlink(__FILE__); $out .= "\nDONE. Installer deleted itself.\nOpen: https://urbancanvasapparelco.com/desk/companion.html\n"; }
echo $out;
