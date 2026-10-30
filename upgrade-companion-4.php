<?php
/* 3G's Companion upgrade 4 (Oct 30, 2026): expert modes (teacher, narrator, trading mentor, e-com, marketing, writing, critical thinking,
   health librarian), dictionary, MedlinePlus health library, live stock data with chart, public-domain book reading.
   Needs upgrades 1-3 first. Open once with ?go=622fc54d8beb8da4 (or run.php 4). Backs up, patches, checks syntax, deletes itself. */
if (($_GET['go'] ?? '') !== '622fc54d8beb8da4') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = (basename(__DIR__) === '_install') ? dirname(__DIR__) : __DIR__; $tag = '.bak-20261030-expert'; $out = ''; $bad = 0;
if (!is_file($root . '/api/companion_kb.php') || strpos((string)file_get_contents($root . '/api/companion.php'), "'didkey'") === false) exit("Install upgrades 1-3 first.\n");
$P = json_decode('[["api/companion.php", "require_once __DIR__ . \'/companion_kb.php\';", "require_once __DIR__ . \'/companion_kb.php\';\\nrequire_once __DIR__ . \'/companion_kb2.php\';", "load knowledge layer 2"], ["api/companion.php", "if ($a === \'status\') cp([\'ok\' => true, \'ready\' => ck() !== \'\', \'video\' => didkey() !== \'\',", "if ($a === \'status\') cp([\'ok\' => true, \'ready\' => ck() !== \'\', \'video\' => didkey() !== \'\', \'modes\' => array_map(function ($m) { return $m[0]; }, kb_modes()),", "status: modes"], ["api/companion.php", "  $KB = kb_context($text); if ($KB[\'text\'] !== \'\') $sys .= \\"\\\\n\\" . $KB[\'text\'];", "  $mode = preg_replace(\'/[^a-z]/\', \'\', (string)($in[\'mode\'] ?? \'\')); $K2 = kb2_context($text, $d, $mode); if ($K2[\'text\'] !== \'\') $sys .= \\"\\\\n\\" . $K2[\'text\'];\\n  if ($mode !== \'\' && $mode !== \'companion\') $sys .= \\"\\\\nLENGTH: In this mode you may use up to 10 short sentences or a short list when the task needs it.\\\\n\\";\\n  $KB = kb_context($text); if ($KB[\'text\'] !== \'\') $sys .= \\"\\\\n\\" . $KB[\'text\']; $KB[\'sources\'] = array_merge($K2[\'sources\'], $KB[\'sources\']);", "inject layer 2"], ["api/companion.php", "  $reply = ai($key, $msgs, 350, 0.85);", "  $reply = ai($key, $msgs, ($mode !== \'\' && $mode !== \'companion\') || $K2[\'book\'] ? 900 : 350, $K2[\'book\'] ? 0.3 : 0.85);", "longer answers in expert modes"], ["api/companion.php", "  $d[\'last\'] = time(); csave($id, $d); use_add(\'msg\', 1);", "  if ($K2[\'book\']) $d[\'book\'] = $K2[\'book\'];\\n  $d[\'last\'] = time(); csave($id, $d); use_add(\'msg\', 1);", "remember book position"], ["api/companion.php", "  cp([\'ok\' => true, \'reply\' => $reply, \'sources\' => $KB[\'sources\'], \'mood\' => $mood]);", "  cp([\'ok\' => true, \'reply\' => $reply, \'sources\' => $KB[\'sources\'], \'mood\' => $mood, \'chart\' => $K2[\'chart\'], \'book\' => $K2[\'book\'] ? [\'title\' => $K2[\'book\'][\'title\'], \'author\' => $K2[\'book\'][\'author\'], \'pct\' => $K2[\'book\'][\'pct\'], \'done\' => $K2[\'book\'][\'done\']] : null]);", "return chart + book"], ["desk/companion.html", "<div class=\\"card\\"><h2>Talk</h2>", "<div class=\\"card\\"><h2>Talk</h2><div id=\\"modes\\" class=\\"pick\\" style=\\"margin:6px 0 10px\\"></div><canvas id=\\"chart\\" style=\\"display:none;width:100%;height:160px;border-radius:12px;background:#0f0c08;border:1px solid #3a3022;margin-bottom:8px\\"></canvas><div id=\\"bookbar\\" class=\\"note\\" style=\\"display:none\\"></div>", "mode chips + chart + book bar"], ["desk/companion.html", "call(\'chat\',{id:cur,text:t})", "call(\'chat\',{id:cur,text:t,mode:MODE})", "send mode"], ["desk/companion.html", "setMood(r.mood);if($(\'vd\')&&$(\'vd\').checked)videoReply(r.reply);else if($(\'vm\').checked)speakLast(r.reply);status()}", "setMood(r.mood);drawChart(r.chart);bookBar(r.book);if($(\'vd\')&&$(\'vd\').checked)videoReply(r.reply);else if($(\'vm\').checked)speakLast(r.reply);status()}", "chart + book on reply"], ["desk/companion.html", "if(!s.ready)$(\'off\').style.display=\'block\';meter(s.usage);", "if(!s.ready)$(\'off\').style.display=\'block\';meter(s.usage);if(s.modes&&!$(\'modes\').children.length){Object.keys(s.modes).forEach(function(k){var b=document.createElement(\'button\');b.className=\'ghost sp\';b.textContent=s.modes[k];b.setAttribute(\'data-m\',k);b.onclick=function(){setModeBtn(k)};$(\'modes\').appendChild(b)});setModeBtn(\'companion\')}", "render mode chips"], ["desk/companion.html", "$(\'sd\').onclick=send;", "$(\'sd\').onclick=send;\\nvar MODE=\'companion\';\\nfunction setModeBtn(k){MODE=k;[].forEach.call($(\'modes\').children,function(b){var on=b.getAttribute(\'data-m\')===k;b.style.background=on?\'var(--g)\':\'transparent\';b.style.color=on?\'var(--i)\':\'var(--g)\'});var hint={teacher:\'Teacher mode: ask it to teach you anything. It explains in steps and checks your understanding.\',narrator:\'Narrator mode: paste any text and it reads it to you. Say \\"read me Treasure Island\\" for a public-domain book.\',trader:\'Trading mentor: ask about any stock or concept. Live prices, moving averages and RSI appear with a chart. Education only, never buy or sell orders.\',ecom:\'E-com coach: stores, pricing, bundles, conversion, fulfillment.\',marketer:\'Marketing strategist: hooks, captions, ads, funnels, email.\',writer:\'Writing editor: paste a draft and ask for a rewrite or a polish.\',thinker:\'Critical thinker: bring a decision; it lays out assumptions, options and trade-offs.\',medical:\'Health librarian: plain-word health information from the U.S. National Library of Medicine. Not a diagnosis.\'}[k]||\'\';say(\'m4\',hint)}\\nfunction drawChart(ch){var c=$(\'chart\');if(!ch||!ch.series||ch.series.length<2){c.style.display=\'none\';return}c.style.display=\'block\';var W=c.width=c.clientWidth*2,H=c.height=320,x=c.getContext(\'2d\'),s=ch.series,mn=Math.min.apply(null,s),mx=Math.max.apply(null,s),pad=28;x.clearRect(0,0,W,H);x.strokeStyle=\'#3a3022\';x.lineWidth=1;for(var g=0;g<4;g++){var gy=pad+(H-2*pad)*g/3;x.beginPath();x.moveTo(pad,gy);x.lineTo(W-pad,gy);x.stroke()}var up=s[s.length-1]>=s[0];x.strokeStyle=up?\'#4ade80\':\'#f87171\';x.lineWidth=4;x.beginPath();s.forEach(function(v,i){var px=pad+(W-2*pad)*i/(s.length-1),py=H-pad-(H-2*pad)*(v-mn)/((mx-mn)||1);i?x.lineTo(px,py):x.moveTo(px,py)});x.stroke();x.fillStyle=\'#E0BE6A\';x.font=\'bold 28px system-ui\';x.fillText(ch.sym+\'  \'+ch.last.toFixed(2)+\'  \'+(ch.chg>=0?\'+\':\'\')+ch.chg.toFixed(2)+\'%\',pad,24);x.fillStyle=\'#8b7d66\';x.font=\'22px system-ui\';x.fillText(ch.dates[0],pad,H-6);var l=ch.dates[ch.dates.length-1];x.fillText(l,W-pad-x.measureText(l).width,H-6);x.fillText(\'60 trading days, daily close (Stooq)\',W/2-170,H-6)}\\nfunction bookBar(b){var e=$(\'bookbar\');if(!b){e.style.display=\'none\';return}e.style.display=\'block\';e.innerHTML=\'\\\\U0001F4D6 Reading <b>\'+esc(b.title)+\'</b> by \'+esc(b.author)+\' \\\\u00b7 \'+b.pct+\'% \'+(b.done?\'\\\\u00b7 The end.\':\'\\\\u00b7 <a href=\\"#\\" id=\\"bknext\\">Keep reading \\\\u2192</a>\');var n=document.getElementById(\'bknext\');if(n)n.onclick=function(ev){ev.preventDefault();$(\'tx\').value=\'Keep reading\';send()}}\\nfunction esc(s){return String(s==null?\'\':s).replace(/[&<>\\"\']/g,function(c){return{\'&\':\'&amp;\',\'<\':\'&lt;\',\'>\':\'&gt;\',\'\\"\':\'&quot;\',\\"\'\\":\'&#39;\'}[c]})}", "modes, chart, book bar logic"]]', true);
$KB2 = <<<'KB2FILE'
<?php
/* 3G's Companion - knowledge layer 2 (Oct 30, 2026): dictionary, medical library, stock quotes + history + indicators,
   public-domain books (Project Gutenberg), and expert modes. Free public sources only, cached in tt_data/companion/kb. No secrets. */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'companion_kb2.php') { http_response_code(404); exit; }

/* ---------- dictionary (Free Dictionary API; Merriam-Webster if a key is saved as TT_MW_KEY) ---------- */
function kb_is_word(string $t): bool { return (bool)preg_match('/\b(define|definition of|meaning of|what does .{1,40} mean|spell|synonym|antonym|pronounce|what is the word|dictionary)\b/i', $t); }
function kb_word(string $t): string { if (preg_match('/(?:define|definition of|meaning of|spell|synonyms? (?:of|for)|antonyms? (?:of|for)|pronounce)\s+["\']?([A-Za-z\-]{2,30})/i', $t, $m)) return strtolower($m[1]); if (preg_match('/what does\s+["\']?([A-Za-z\-]{2,30})["\']?\s+mean/i', $t, $m)) return strtolower($m[1]); return ''; }
function kb_dict(string $w): ?array {
  if ($w === '') return null;
  $mw = (string)tt_c('TT_MW_KEY', '');
  if ($mw !== '') { $r = kb_get('https://www.dictionaryapi.com/api/v3/references/collegiate/json/' . rawurlencode($w) . '?key=' . rawurlencode($mw), 30 * 86400); $j = json_decode($r, true);
    if (is_array($j) && isset($j[0]['shortdef'])) return ['word' => $w, 'src' => 'Merriam-Webster', 'url' => 'https://www.merriam-webster.com/dictionary/' . rawurlencode($w), 'text' => implode(' | ', array_slice($j[0]['shortdef'], 0, 3)) . (isset($j[0]['fl']) ? ' (' . $j[0]['fl'] . ')' : '')]; }
  $r = kb_get('https://api.dictionaryapi.dev/api/v2/entries/en/' . rawurlencode($w), 30 * 86400); $j = json_decode($r, true); if (!is_array($j) || !isset($j[0]['meanings'])) return null;
  $out = []; foreach (array_slice($j[0]['meanings'], 0, 3) as $mn) { $defs = array_slice(array_map(function ($d) { return $d['definition']; }, $mn['definitions'] ?? []), 0, 2); $out[] = ($mn['partOfSpeech'] ?? '') . ': ' . implode('; ', $defs); }
  $ph = (string)($j[0]['phonetic'] ?? ($j[0]['phonetics'][0]['text'] ?? ''));
  return ['word' => $w, 'src' => 'Dictionary', 'url' => 'https://en.wiktionary.org/wiki/' . rawurlencode($w), 'text' => ($ph ? $ph . ' ' : '') . implode(' | ', $out)];
}

/* ---------- medical library (MedlinePlus, U.S. National Library of Medicine, public domain) ---------- */
function kb_is_med(string $t): bool { return (bool)preg_match('/\b(symptom|symptoms|disease|diagnos|treatment|medic(?:ine|ation)|dose|side effect|blood pressure|diabetes|cancer|infection|virus|flu|covid|asthma|arthritis|cholesterol|heart|stroke|kidney|liver|thyroid|allergy|vitamin|anxiety|depression|insomnia|migraine|pain|injury|surgery|pregnan|doctor|nurse|hospital|health)\b/i', $t); }
function kb_med(string $q): ?array {
  $topic = kb_topic($q); if ($topic === '') return null;
  $x = kb_get('https://wsearch.nlm.nih.gov/ws/query?db=healthTopics&term=' . rawurlencode($topic) . '&retmax=1', 7 * 86400, 'application/xml');
  if ($x === '' || !preg_match('#<document[^>]*url="([^"]+)"[^>]*>(.*?)</document>#s', $x, $m)) return null;
  $doc = $m[2]; $title = preg_match('#<content name="title">(.*?)</content>#s', $doc, $t) ? strip_tags(html_entity_decode($t[1], ENT_QUOTES | ENT_HTML5)) : $topic;
  $sum = preg_match('#<content name="FullSummary">(.*?)</content>#s', $doc, $s) ? strip_tags(html_entity_decode($s[1], ENT_QUOTES | ENT_HTML5)) : '';
  $sum = trim(preg_replace('/\s+/', ' ', $sum)); if ($sum === '') return null;
  return ['title' => $title, 'url' => html_entity_decode($m[1]), 'text' => mb_substr($sum, 0, 1100)];
}

/* ---------- stocks (Stooq free quotes + daily history; indicators computed here) ---------- */
function kb_is_stock(string $t): bool { return (bool)preg_match('/\b(stock|stocks|shares?|ticker|trading|trade|swing|day trad|invest|portfolio|dividend|etf|nasdaq|dow|s&p|sp500|spy|qqq|options?|calls?|puts?|rsi|moving average|support|resistance|breakout|earnings|market cap|bull|bear|candle)\b/i', $t) || (bool)preg_match('/\$[A-Z]{1,5}\b/', $t); }
function kb_tickers(string $t): array {
  $names = ['apple' => 'AAPL', 'tesla' => 'TSLA', 'amazon' => 'AMZN', 'microsoft' => 'MSFT', 'google' => 'GOOGL', 'alphabet' => 'GOOGL', 'nvidia' => 'NVDA', 'meta' => 'META', 'facebook' => 'META', 'netflix' => 'NFLX', 'amd' => 'AMD', 'intel' => 'INTC', 'disney' => 'DIS', 'walmart' => 'WMT', 'costco' => 'COST', 'nike' => 'NKE', 'boeing' => 'BA', 'ford' => 'F', 'gm' => 'GM', 'palantir' => 'PLTR', 'coinbase' => 'COIN', 'shopify' => 'SHOP', 'spy' => 'SPY', 'qqq' => 'QQQ', 's&p' => 'SPY', 'sp500' => 'SPY', 'nasdaq' => 'QQQ', 'dow' => 'DIA', 'bitcoin' => 'BTC', 'ethereum' => 'ETH'];
  $o = []; if (preg_match_all('/\$([A-Za-z]{1,5})\b/', $t, $m)) foreach ($m[1] as $x) $o[] = strtoupper($x);
  if (preg_match_all('/\b([A-Z]{2,5})\b/', $t, $m)) foreach ($m[1] as $x) if (!in_array($x, ['RSI', 'ETF', 'IPO', 'CEO', 'USA', 'AI', 'OK', 'THE', 'AND', 'FOR', 'YOU', 'WHAT', 'HOW', 'WHY', 'ARE', 'IS'], true)) $o[] = $x;
  $lt = strtolower($t); foreach ($names as $k => $v) if (preg_match('/\b' . preg_quote($k, '/') . '\b/', $lt)) $o[] = $v;
  return array_values(array_unique(array_slice($o, 0, 3)));
}
function kb_stock(string $sym): ?array {
  $s = strtolower($sym); $isCrypto = in_array($sym, ['BTC', 'ETH'], true); $code = $isCrypto ? $s . 'usd' : $s . '.us';
  $hist = kb_get('https://stooq.com/q/d/l/?s=' . rawurlencode($code) . '&i=d', 3600, 'text/csv'); if ($hist === '' || stripos($hist, 'Date,Open') !== 0) return null;
  $rows = array_filter(array_map('trim', explode("\n", $hist))); array_shift($rows); $rows = array_slice($rows, -260);
  $c = []; $d = []; foreach ($rows as $r) { $p = explode(',', $r); if (count($p) >= 5 && is_numeric($p[4])) { $d[] = $p[0]; $c[] = (float)$p[4]; } }
  $n = count($c); if ($n < 30) return null;
  $last = $c[$n - 1]; $prev = $c[$n - 2]; $chg = ($last - $prev) / $prev * 100;
  $sma = function ($k) use ($c, $n) { if ($n < $k) return null; return array_sum(array_slice($c, -$k)) / $k; };
  $g = 0; $l = 0; for ($i = $n - 14; $i < $n; $i++) { $df = $c[$i] - $c[$i - 1]; if ($df >= 0) $g += $df; else $l -= $df; } $rsi = $l == 0 ? 100 : 100 - 100 / (1 + ($g / 14) / ($l / 14));
  $yr = array_slice($c, -252); $hi = max($yr); $lo = min($yr); $m1 = $n > 22 ? ($last / $c[$n - 22] - 1) * 100 : null; $m3 = $n > 64 ? ($last / $c[$n - 64] - 1) * 100 : null;
  return ['sym' => $sym, 'date' => end($d), 'last' => $last, 'chg' => $chg, 'sma20' => $sma(20), 'sma50' => $sma(50), 'sma200' => $sma(200), 'rsi14' => $rsi, 'hi52' => $hi, 'lo52' => $lo, 'm1' => $m1, 'm3' => $m3, 'series' => array_slice($c, -60), 'dates' => array_slice($d, -60)];
}
function kb_stock_text(array $q): string {
  $f = function ($v, $p = 2) { return $v === null ? 'n/a' : number_format($v, $p); };
  return $q['sym'] . ' close ' . $f($q['last']) . ' on ' . $q['date'] . ' (' . ($q['chg'] >= 0 ? '+' : '') . $f($q['chg']) . '% day; 1-month ' . ($q['m1'] === null ? 'n/a' : (($q['m1'] >= 0 ? '+' : '') . $f($q['m1'], 1) . '%') . '; 3-month ' . ($q['m3'] === null ? 'n/a' : (($q['m3'] >= 0 ? '+' : '') . $f($q['m3'], 1) . '%'))) . '). 20-day avg ' . $f($q['sma20']) . ', 50-day ' . $f($q['sma50']) . ', 200-day ' . $f($q['sma200']) . '. RSI(14) ' . $f($q['rsi14'], 0) . '. 52-week range ' . $f($q['lo52']) . ' to ' . $f($q['hi52']) . '.';
}

/* ---------- books (Project Gutenberg via Gutendex; public domain only) ---------- */
function kb_is_book(string $t): bool { return (bool)preg_match('/\b(read (?:me )?(?:the )?(?:book|chapter|story|poem|novel)|read .{2,60} by |book called|chapter \d+|next chapter|keep reading|continue reading|audiobook|narrate)\b/i', $t); }
function kb_book_find(string $q): ?array {
  $t = preg_replace('/\b(read|me|the|book|chapter|story|please|to|next|keep|continue|reading|a|an|aloud|out loud|audiobook|narrate|by)\b/i', ' ', $q); $t = trim(preg_replace('/\s+/', ' ', $t)); if ($t === '') return null;
  $r = kb_get('https://gutendex.com/books/?search=' . rawurlencode($t), 7 * 86400); $j = json_decode($r, true); $b = $j['results'][0] ?? null; if (!$b) return null;
  $fmt = $b['formats'] ?? []; $url = ''; foreach (['text/plain; charset=utf-8', 'text/plain; charset=us-ascii', 'text/plain'] as $k) if (!empty($fmt[$k])) { $url = $fmt[$k]; break; }
  if ($url === '') foreach ($fmt as $k => $v) if (strpos($k, 'text/plain') === 0) { $url = $v; break; }
  if ($url === '') return null;
  return ['id' => (int)$b['id'], 'title' => (string)$b['title'], 'author' => (string)($b['authors'][0]['name'] ?? 'Unknown'), 'url' => $url];
}
function kb_book_text(array $bk): string {
  $f = kb_dir() . '/book-' . $bk['id'] . '.txt'; if (is_file($f)) return (string)file_get_contents($f);
  $raw = kb_get($bk['url'], 365 * 86400, 'text/plain'); if ($raw === '') return '';
  if (preg_match('/\*\*\* ?START OF (?:THE|THIS) PROJECT GUTENBERG EBOOK.*?\*\*\*/is', $raw, $m, PREG_OFFSET_CAPTURE)) $raw = substr($raw, $m[0][1] + strlen($m[0][0]));
  if (($e = stripos($raw, '*** END OF')) !== false) $raw = substr($raw, 0, $e);
  $raw = trim(preg_replace("/\r\n?/", "\n", $raw)); file_put_contents($f, $raw, LOCK_EX); return $raw;
}
/* returns one reading chunk (~1,100 chars) and the next position */
function kb_book_chunk(array $bk, int $pos): array {
  $txt = kb_book_text($bk); $len = strlen($txt); if ($len === 0) return ['text' => '', 'next' => 0, 'done' => true];
  $pos = max(0, min($pos, $len)); $chunk = substr($txt, $pos, 1400); $cut = strrpos($chunk, "\n\n"); if ($cut === false || $cut < 700) $cut = strrpos(substr($chunk, 0, 1200), '. ') ?: strlen($chunk);
  $piece = trim(substr($chunk, 0, $cut + 1)); $next = $pos + strlen(substr($chunk, 0, $cut + 1));
  return ['text' => preg_replace('/\s*\n\s*/', ' ', $piece), 'next' => $next, 'done' => $next >= $len, 'pct' => round($next / $len * 100, 1)];
}

/* ---------- expert modes ---------- */
function kb_modes(): array { return [
  'companion' => ['Companion', ''],
  'teacher' => ['Teacher', 'MODE TEACHER: Teach like a patient master teacher. Structure: one-line big idea; three short steps or facts; one real example; then ask one check question. Plain words, no jargon without a one-line definition.'],
  'narrator' => ['Narrator', 'MODE NARRATOR: You read and narrate. When given text, read it faithfully with warmth; when asked about it, summarize and explain. Do not invent content that is not in the text.'],
  'trader' => ['Trading mentor', 'MODE TRADING MENTOR: You are a seasoned trading and investing educator (stocks, ETFs, options basics, swing and day trading, risk management, position sizing, technical and fundamental analysis). Explain concepts, read the numbers given (price, moving averages, RSI, 52-week range) and describe what they usually indicate. EDUCATION ONLY: never tell the person to buy or sell a specific security, never predict prices, and remind them once that trading carries risk of loss. Use the live data block when present and never make up prices.'],
  'ecom' => ['E-com coach', 'MODE E-COMMERCE COACH: Expert in online stores, print-on-demand, product research, pricing and margins, conversion rate, bundles and upsells, email flows, Shopify/Stripe checkout, fulfillment and customer service. Give concrete numbers, templates and next steps.'],
  'marketer' => ['Marketing strategist', 'MODE DIGITAL MARKETING STRATEGIST: Expert in organic social (Instagram, TikTok, Pinterest, YouTube), paid ads, hooks, offers, funnels, email and SMS, SEO, analytics. Give specific hooks, captions, scripts and a measurable plan.'],
  'writer' => ['Writing editor', 'MODE WRITING EDITOR: Expert editor and ghostwriter. Improve clarity, rhythm and persuasion; keep the person\'s voice; offer two versions when asked to rewrite; explain the one change that mattered most.'],
  'thinker' => ['Critical thinker', 'MODE CRITICAL THINKER: Help the person reason well. Separate facts from assumptions, name the strongest counter-argument, spot logical fallacies and biases, lay out options with trade-offs, and end with the single best next question to ask.'],
  'medical' => ['Health librarian', 'MODE HEALTH LIBRARIAN: Explain health topics in plain words using the reference block when present (MedlinePlus, U.S. National Library of Medicine). Information only: no diagnosis, no dosing, no treatment decisions; say when to see a doctor or call 911.'],
]; }

/* Builds the extra context for a message. Returns ['text'=>prompt block,'sources'=>[],'chart'=>null|[...],'book'=>null|[...]] */
function kb2_context(string $text, array $d, string $mode): array {
  $out = ['text' => '', 'sources' => [], 'chart' => null, 'book' => null];
  $modes = kb_modes(); if ($mode !== '' && isset($modes[$mode]) && $modes[$mode][1] !== '') $out['text'] .= $modes[$mode][1] . "\n";
  if (kb_is_word($text)) { $w = kb_dict(kb_word($text)); if ($w) { $out['text'] .= 'DICTIONARY (' . $w['src'] . ') ' . $w['word'] . ': ' . $w['text'] . "\n"; $out['sources'][] = ['t' => $w['word'], 'u' => $w['url'], 's' => $w['src']]; } }
  if ($mode === 'medical' || kb_is_med($text)) { $m = kb_med($text); if ($m) { $out['text'] .= 'HEALTH REFERENCE (MedlinePlus, NIH): ' . $m['title'] . '. ' . $m['text'] . "\nInformation only, not a diagnosis. Suggest a doctor for personal decisions.\n"; $out['sources'][] = ['t' => $m['title'], 'u' => $m['url'], 's' => 'MedlinePlus']; } }
  if ($mode === 'trader' || kb_is_stock($text)) { foreach (kb_tickers($text) as $sym) { $q = kb_stock($sym); if ($q) { $out['text'] .= 'LIVE MARKET DATA (Stooq, end of day): ' . kb_stock_text($q) . "\n"; $out['sources'][] = ['t' => $sym . ' quote', 'u' => 'https://stooq.com/q/?s=' . strtolower($sym) . '.us', 's' => 'Stooq']; if (!$out['chart']) $out['chart'] = ['sym' => $sym, 'series' => $q['series'], 'dates' => $q['dates'], 'last' => $q['last'], 'chg' => $q['chg']]; } }
    if (!$out['chart'] && preg_match('/\b(market|markets|stocks|wall street|dow|nasdaq|s&p)\b/i', $text)) { foreach (['SPY', 'QQQ', 'DIA'] as $sym) { $q = kb_stock($sym); if ($q) { $out['text'] .= 'LIVE MARKET DATA (Stooq, end of day): ' . kb_stock_text($q) . "\n"; if (!$out['chart']) $out['chart'] = ['sym' => $sym, 'series' => $q['series'], 'dates' => $q['dates'], 'last' => $q['last'], 'chg' => $q['chg']]; } } $out['sources'][] = ['t' => 'Index ETFs', 'u' => 'https://stooq.com/', 's' => 'Stooq']; }
    $out['text'] .= "Trading education only: no buy/sell instructions, no price predictions.\n"; }
  if (kb_is_book($text) || !empty($d['book'])) {
    $bk = !empty($d['book']) && !preg_match('/\b(read .{2,60} by |book called)\b/i', $text) ? $d['book'] : kb_book_find($text);
    if ($bk) { $pos = (!empty($d['book']) && ($d['book']['id'] ?? 0) === $bk['id']) ? (int)($d['book']['pos'] ?? 0) : 0; $ch = kb_book_chunk($bk, $pos);
      if ($ch['text'] !== '') { $out['book'] = ['id' => $bk['id'], 'title' => $bk['title'], 'author' => $bk['author'], 'url' => $bk['url'], 'pos' => $ch['next'], 'pct' => $ch['pct'], 'done' => $ch['done']];
        $out['text'] .= 'BOOK READING (public domain, Project Gutenberg): "' . $bk['title'] . '" by ' . $bk['author'] . ', ' . $ch['pct'] . "% in. Read this passage aloud to the person word for word, then stop and ask if they want you to continue:\n" . $ch['text'] . "\n";
        $out['sources'][] = ['t' => $bk['title'], 'u' => 'https://www.gutenberg.org/ebooks/' . $bk['id'], 's' => 'Project Gutenberg']; } }
  }
  return $out;
}
KB2FILE;
$files = [];
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) { if (!is_file($f)) { $out .= "MISSING $p[0]\n"; $bad++; continue; } $files[$f] = file_get_contents($f); } }
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) continue; $n = substr_count($files[$f], $p[1]);
  if ($n !== 1) { $out .= "ANCHOR x$n ($p[3]) in $p[0]\n"; $bad++; continue; }
  $files[$f] = str_replace($p[1], $p[2], $files[$f]); $out .= "ok  $p[3]\n"; }
if ($bad) { echo "STOPPED, nothing changed.\n$out"; exit; }
file_put_contents($root . '/api/companion_kb2.php', $KB2, LOCK_EX); $out .= "ok  api/companion_kb2.php written\n";
foreach ($files as $f => $c) { if (!is_file($f . $tag)) copy($f, $f . $tag); file_put_contents($f, $c, LOCK_EX); $out .= "ok  " . str_replace($root . '/', '', $f) . " patched (backup " . basename($f) . "$tag)\n"; }
$lint = []; foreach (['api/companion.php', 'api/companion_kb2.php'] as $pf) { $o = []; $rc = 0; @exec('php -l ' . escapeshellarg($root . '/' . $pf) . ' 2>&1', $o, $rc); $lint[] = ($rc === 0 ? 'ok  ' : 'ERR ') . $pf . ' syntax: ' . trim(implode(' ', $o)); }
$out .= implode("\n", $lint) . "\n";
if (strpos(implode($lint), 'ERR ') !== false) { foreach ($files as $f => $c) copy($f . $tag, $f); @unlink($root . '/api/companion_kb2.php'); $out .= "ROLLED BACK because of a syntax error.\n"; }
else { @unlink(__FILE__); $out .= "\nDONE.\n"; }
echo $out;
