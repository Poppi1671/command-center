<?php
/* THE PRODUCT LAB installer (Oct 31 2026). Three gates before anything gets press time.
   Open once with ?go=b4d159133d3cbcde */
if (($_GET['go'] ?? '') !== 'b4d159133d3cbcde') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = preg_match('~/_inst~', __DIR__) ? dirname(__DIR__) : __DIR__;
$tag = '.bak-20261031-lab'; $out = ''; $bad = 0;
if (!is_file($root . '/api/svc_lib.php')) exit("Wrong folder: api/svc_lib.php not found.\n");
$NEW = [];
$NEW['api/lab.php'] = <<<'FEB2CC4C9'
<?php
/* 3G's Product Lab (Oct 31 2026). Takes a product idea and answers one question honestly:
   can WE win this one? Scored against his real blank costs, his actual presses and his own call list.
   No trend feed knows those three things, which is why this beats a paid tool for him.
   Desk:  GET ?a=list|get&p=..|blanks     POST {a:save|score|delete|offer|plan}
   Data:  tt_data/lab.json */
declare(strict_types=1);
require_once __DIR__ . '/svc_lib.php';
require_once __DIR__ . '/money_lib.php';

/* what he can actually make in his own house. Anything not here, he is buying from a factory and loses. */
const LAB_PRINT = ['tee' => 'DTF', 'crew' => 'DTF', 'hood' => 'DTF', 'tote' => 'DTF', 'jog' => 'DTF',
  'hat' => 'DTF, cap press', 'mug' => 'Sublimation', 'tum' => 'Sublimation', 'sock' => 'NOT in-house'];
const LAB_FLOOR = 35;          // same floor the Outreach Machine holds
const LAB_TARGET = 42;
const LAB_BULK_POST = 0.75;    // one box to a group leader
const LAB_VERDICT = ['build' => 'BUILD IT', 'test' => 'TEST IT', 'kill' => 'KILL IT'];

function lab_set() { return tts_settings(); }
function lab_cost($blank, $set = null) { $set = $set ?: lab_set(); return (float)(tts_cost_table($set, time())[$blank] ?? 0); }
function lab_names() { return TTS_NAMES; }

function lab_margin($blank, $price, $ship, $set = null) {
  $set = $set ?: lab_set();
  $cost = lab_cost($blank, $set);
  if ($cost <= 0 || $price <= 0) return ['pct' => 0, 'keep' => 0, 'cost' => $cost];
  $stripe = $price * $set['stripe_pct'] / 100 + $set['stripe_fixed'];
  $tax = !empty($set['tax_absorbed']) ? $price * $set['sales_tax_pct'] / (100 + $set['sales_tax_pct']) : 0;
  $keep = $price - $cost - $stripe - $tax - $ship;
  return ['pct' => round($keep / $price * 100, 1), 'keep' => round($keep, 2), 'cost' => $cost];
}
function lab_single_ship($blank, $set = null) {
  $set = $set ?: lab_set();
  $wt = (float)(TTS_WT[$blank] ?? 0.5) + (float)($set['box_wt'] ?? 0.3);
  return tts_postage($set, $wt);
}
// smallest price (up to the next 50c) that keeps $target on this blank
function lab_price_for($blank, $target, $ship, $set = null) {
  $set = $set ?: lab_set();
  $cost = lab_cost($blank, $set); if ($cost <= 0) return 0;
  $k = 1 - $set['stripe_pct'] / 100 - (!empty($set['tax_absorbed']) ? $set['sales_tax_pct'] / (100 + $set['sales_tax_pct']) : 0) - $target / 100;
  if ($k <= 0.05) return 0;
  return ceil(($cost + $set['stripe_fixed'] + $ship) / $k * 2) / 2;
}
function lab_ladder($blank) {
  $set = lab_set();
  $out = [];
  $sship = lab_single_ship($blank, $set);
  $single = (float)lab_price_for($blank, LAB_TARGET, $sship, $set);
  $out[] = ['k' => 'single', 'name' => 'One at a time', 'qty' => '1', 'price' => $single,
    'margin' => lab_margin($blank, $single, $sship, $set)['pct'], 'keep' => lab_margin($blank, $single, $sship, $set)['keep'], 'ship' => $sship];
  foreach ([['starter', 'Starter', '12 to 23'], ['group', 'Group', '24 to 49'], ['church', 'Church', '50 to 99'], ['event', 'Event', '100+']] as $t) {
    $tgt = ['starter' => LAB_TARGET + 6, 'group' => LAB_TARGET + 3, 'church' => LAB_TARGET, 'event' => LAB_FLOOR + 2][$t[0]];
    $p = (float)lab_price_for($blank, $tgt, LAB_BULK_POST, $set);
    $m = lab_margin($blank, $p, LAB_BULK_POST, $set);
    $out[] = ['k' => $t[0], 'name' => $t[1], 'qty' => $t[2], 'price' => $p, 'margin' => $m['pct'], 'keep' => $m['keep'], 'ship' => LAB_BULK_POST];
  }
  return $out;
}

/* ---------- the three gates ---------- */
function lab_gates($P) {
  $blank = (string)($P['blank'] ?? '');
  $g = [];
  $how = LAB_PRINT[$blank] ?? '';
  $g['print'] = ['ok' => $how !== '' && $how !== 'NOT in-house',
    'name' => 'Can we print it in-house?',
    'note' => $how === '' ? 'No blank picked.' : ($how === 'NOT in-house' ? 'Crew socks are not something you press at home. You would be buying from a factory and competing on price.' : 'Yes, on your own press: ' . $how . '.')];
  $lad = $blank !== '' ? lab_ladder($blank) : [];
  $grp = 0; $sing = 0;
  foreach ($lad as $r) { if ($r['k'] === 'group') $grp = $r['margin']; if ($r['k'] === 'single') $sing = $r['margin']; }
  $cost = $blank !== '' ? lab_cost($blank) : 0;
  $g['margin'] = ['ok' => $cost > 0 && $grp >= LAB_FLOOR && $sing >= LAB_FLOOR,
    'name' => 'Does it hold ' . LAB_FLOOR . '% at your real cost?',
    'note' => $blank === '' ? 'No blank picked.'
      : ($cost <= 0 ? 'No cost recorded for this blank yet. Put it in Costs & settings first, or the number here is a guess and guesses lose money.'
      : ('Blank costs you $' . number_format($cost, 2) . '. Keeps ' . $sing . '% one at a time, ' . $grp . '% at a group of 24.'))];
  $line = trim((string)($P['design_line'] ?? ''));
  $g['design'] = ['ok' => mb_strlen($line) >= 3,
    'name' => 'Is it a design, or just a category?',
    'note' => $line === '' ? 'Write the exact words that go on the shirt. "Faith apparel is trending" is a category. "EXCELLENCE IS A HABIT in gold on black" is a product.' : 'The design is: ' . $line];
  return $g;
}
function lab_score($P) {
  $g = lab_gates($P);
  foreach ($g as $x) if (empty($x['ok'])) return ['score' => 0, 'verdict' => 'kill', 'gates' => $g, 'why' => 'It fails a gate. Nothing else matters.'];
  $blank = (string)$P['blank'];
  $grp = 0; foreach (lab_ladder($blank) as $r) if ($r['k'] === 'group') $grp = $r['margin'];
  $parts = [];
  $head = max(0, min(25, round(($grp - LAB_FLOOR) / 20 * 25)));                 // margin headroom
  $dem  = max(0, min(25, (int)round((float)($P['demand'] ?? 0) / 5 * 25)));      // is anyone asking for it
  $comp = max(0, min(20, (int)round((5 - (float)($P['competition'] ?? 5)) / 5 * 20)));
  $fit  = max(0, min(20, (int)round((float)($P['group_fit'] ?? 0) / 5 * 20)));   // can a GROUP want it
  $dl = 0; $d = (string)($P['deadline'] ?? '');
  if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $d)) { $days = (int)floor((strtotime($d) - strtotime(date('Y-m-d'))) / 86400); if ($days >= 10 && $days <= 70) $dl = 10; elseif ($days > 70) $dl = 5; elseif ($days >= 0) $dl = 2; }
  $parts = ['margin headroom' => $head, 'demand' => $dem, 'room to compete' => $comp, 'a group can want it' => $fit, 'a date pulling it' => $dl];
  $score = (int)array_sum($parts);
  $v = $score >= 70 ? 'build' : ($score >= 45 ? 'test' : 'kill');
  $why = $v === 'build' ? 'Strong enough to print. Make the design today.'
    : ($v === 'test' ? 'Worth one phone call before you spend a minute on art. Offer it to a group first.'
    : 'Not worth your press time. Something else will pay more for the same hours.');
  return ['score' => $score, 'verdict' => $v, 'gates' => $g, 'parts' => $parts, 'why' => $why, 'group_margin' => $grp];
}

/* ---------- the 72 hour plan ---------- */
function lab_plan($P) {
  $blank = (string)($P['blank'] ?? 'tee');
  $name = lab_names()[$blank] ?? $blank;
  $line = (string)($P['design_line'] ?? '[the exact words]');
  $aud = (string)($P['audience'] ?? 'the group');
  $lad = lab_ladder($blank);
  $grp = 0; $grpKeep = 0; foreach ($lad as $r) if ($r['k'] === 'group') { $grp = $r['price']; $grpKeep = $r['keep']; }
  $s = lab_score($P);
  if ($s['verdict'] === 'kill') return [['h' => 'Stop here', 'b' => $s['why'] . ' Write down why, so you do not pick it up again in three weeks.']];
  return [
    ['h' => 'Hour 0 to 2 - make ONE design', 'b' => 'Artistly, Fast AI Image Designer, T-Shirt Designs, High Quality, Private, 1:1. One design, not twenty. Check the lettering at full size before you do anything else.'],
    ['h' => 'Hour 2 to 3 - mock it on a real photo', 'b' => 'Drop it on a real product photo in the Design Editor. Never a generated mockup. One image is all you need.'],
    ['h' => 'Hour 3 to 24 - offer it to a group, not the internet', 'b' => 'Open the Outreach Machine and send it to 5 leads who fit ' . $aud . '. Use the line below. This is the test. It costs you a phone call, not an ad budget.'],
    ['h' => 'Hour 24 to 48 - count the yeses', 'b' => 'One group saying "send me that" at $' . number_format($grp, 2) . ' a ' . strtolower($name) . ' for 24 pieces is $' . number_format($grp * 24, 0) . ' in, and about $' . number_format($grpKeep * 24, 0) . ' kept after the blank, Stripe, tax and postage. Zero interest after 5 real conversations means the design is wrong, not the price.'],
    ['h' => 'Hour 48 to 72 - decide out loud', 'b' => 'One yes: build the group page and press it. No yes: kill it and keep the design in the Vault. Do not build a store page for a product no group has asked for.'],
  ];
}
function lab_artistly($P) {
  $line = strtoupper(trim((string)($P['design_line'] ?? '[YOUR WORDS]')));
  $aud = (string)($P['audience'] ?? '');
  $ang = (string)($P['angle'] ?? '');
  return 'T-shirt graphic, bold modern design, centered composition, exact lettering "' . $line . '" as the hero'
    . ($ang !== '' ? ', the feeling is ' . $ang : '')
    . ($aud !== '' ? ', made for ' . $aud : '')
    . ', strong type hierarchy, colors gold, white and deep black, solid black background, high contrast, vector-style clean edges, print-ready for DTF.'
    . ' Negatives: no watermark, no extra text, no blur, no licensed characters, no team logos, no mascots, no photo backgrounds, no mockup.';
}
function lab_offer_line($P) {
  $line = trim((string)($P['design_line'] ?? ''));
  $aud = trim((string)($P['audience'] ?? 'your group'));
  $blank = (string)($P['blank'] ?? 'tee');
  $name = strtolower(lab_names()[$blank] ?? 'shirt');
  $grp = 0; foreach (lab_ladder($blank) as $r) if ($r['k'] === 'group') $grp = $r['price'];
  return 'I just made something for ' . $aud . ' and I thought of {org}. It says "' . $line . '" and I did the design myself, so it is yours free. '
    . 'If {org} wanted it on a ' . $name . ', 24 or more comes to $' . number_format($grp, 2) . ' each and every person pays for their own. Want me to send you the picture?';
}

function lab_all() { $d = tt_read('lab.json'); return (array)($d['products'] ?? []); }
function lab_get($id) { $id = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$id)); $a = lab_all(); return $a[$id] ?? null; }
function lab_full($P) {
  $s = lab_score($P);
  $P['score'] = $s['score']; $P['verdict'] = $s['verdict']; $P['gates'] = $s['gates'];
  $P['parts'] = $s['parts'] ?? []; $P['why'] = $s['why'];
  $P['ladder'] = ($P['blank'] ?? '') !== '' ? lab_ladder($P['blank']) : [];
  $P['plan'] = lab_plan($P); $P['artistly'] = lab_artistly($P); $P['offer'] = lab_offer_line($P);
  $P['print_how'] = LAB_PRINT[$P['blank'] ?? ''] ?? '';
  return $P;
}

tt_require_admin();
$a = (string)($_GET['a'] ?? '');
$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
  $blanks = [];
  foreach (lab_names() as $k => $n) {
    if (strpos($k, 'x') === 0) continue;   // the Tapstitch exclusives are not pressed here
    $blanks[$k] = ['name' => $n, 'cost' => lab_cost($k), 'how' => LAB_PRINT[$k] ?? 'NOT in-house'];
  }
  if ($a === 'blanks') tt_json(['ok' => true, 'blanks' => $blanks, 'floor' => LAB_FLOOR]);
  if ($a === 'get') { $P = lab_get($_GET['p'] ?? ''); if (!$P) tt_json(['error' => 'Not found.'], 404); tt_json(['ok' => true, 'product' => lab_full($P)]); }
  $list = [];
  foreach (lab_all() as $P) { $f = lab_full($P); $list[] = ['id' => $f['id'], 'title' => $f['title'], 'blank' => $f['blank'],
    'design_line' => $f['design_line'] ?? '', 'score' => $f['score'], 'verdict' => $f['verdict'],
    'audience' => $f['audience'] ?? '', 'deadline' => $f['deadline'] ?? '', 'updated' => (int)($P['updated'] ?? 0)]; }
  usort($list, function ($x, $y) { return [$y['score'], $y['updated']] <=> [$x['score'], $x['updated']]; });
  tt_json(['ok' => true, 'products' => $list, 'blanks' => $blanks, 'floor' => LAB_FLOOR, 'verdicts' => LAB_VERDICT]);
}

$b = tt_body();
$op = (string)($b['a'] ?? '');
$id = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($b['id'] ?? '')));

if ($op === 'score') {   // try one without saving it
  tt_json(['ok' => true, 'product' => lab_full((array)($b['product'] ?? []))]);
}
if ($op === 'save') {
  $d = (array)($b['product'] ?? []);
  $title = tt_clean($d['title'] ?? '', 90);
  if ($title === '') tt_json(['error' => 'Give the product a name.'], 400);
  $pid = $id;
  if ($pid === '') { $base = trim(preg_replace('~[^a-z0-9]+~', '-', strtolower($title)), '-') ?: 'product'; $pid = substr($base, 0, 40); $n = 2; while (lab_get($pid)) $pid = substr($base, 0, 36) . '-' . $n++; }
  $old = lab_get($pid);
  $P = ['id' => $pid, 'title' => $title,
    'blank' => isset(LAB_PRINT[$d['blank'] ?? '']) ? $d['blank'] : 'tee',
    'design_line' => tt_clean($d['design_line'] ?? '', 120),
    'angle' => tt_clean($d['angle'] ?? '', 120),
    'audience' => tt_clean($d['audience'] ?? '', 120),
    'source' => tt_clean($d['source'] ?? '', 200),
    'demand' => max(0, min(5, (float)($d['demand'] ?? 0))),
    'demand_src' => tt_clean($d['demand_src'] ?? '', 200),
    'competition' => max(0, min(5, (float)($d['competition'] ?? 5))),
    'comp_src' => tt_clean($d['comp_src'] ?? '', 200),
    'group_fit' => max(0, min(5, (float)($d['group_fit'] ?? 0))),
    'deadline' => preg_match('~^\d{4}-\d{2}-\d{2}$~', (string)($d['deadline'] ?? '')) ? $d['deadline'] : '',
    'notes' => tt_clean($d['notes'] ?? '', 600),
    'status' => in_array($d['status'] ?? '', ['idea', 'testing', 'won', 'dead'], true) ? $d['status'] : 'idea',
    'created' => (int)($old['created'] ?? time()), 'updated' => time()];
  tt_update('lab.json', function ($x) use ($pid, $P) { $x['products'][$pid] = $P; return ['data' => $x]; });
  tt_json(['ok' => true, 'id' => $pid, 'product' => lab_full($P)]);
}
if ($op === 'delete') {
  if (!lab_get($id)) tt_json(['error' => 'Not found.'], 404);
  tt_update('lab.json', function ($x) use ($id) { unset($x['products'][$id]); return ['data' => $x]; });
  tt_json(['ok' => true]);
}
/* turn a product into a real offer: write its line onto leads in the call list */
if ($op === 'offer') {
  $P = lab_get($id);
  if (!$P) tt_json(['error' => 'Save the product first.'], 404);
  $biz = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($b['biz'] ?? 'orlando')));
  $ids = array_map(function ($x) { return preg_replace('/[^a-z0-9]/', '', (string)$x); }, (array)($b['leads'] ?? []));
  $ids = array_values(array_filter($ids));
  if (!$ids) tt_json(['error' => 'Pick at least one lead.'], 400);
  $line = lab_offer_line($P);
  $r = tt_update('leads.json', function ($d) use ($ids, $line, $P, $biz) {
    $n = 0;
    foreach ($ids as $k) {
      if (!isset($d['leads'][$k])) continue;
      $L = $d['leads'][$k];
      $L['opener'] = $line;
      $L['why_now'] = 'New design ready for them: "' . $P['design_line'] . '"';
      $L['next'] = date('Y-m-d');
      if (($L['stage'] ?? 'new') === 'new') $L['stage'] = 'new';
      $L['notes'][] = ['at' => time(), 'text' => 'Product offer queued: ' . $P['title']];
      $L['notes'] = array_slice($L['notes'], -100);
      $L['updated'] = time();
      $d['leads'][$k] = $L; $n++;
    }
    return ['data' => $d, 'result' => ['ok' => true, 'queued' => $n]];
  });
  tt_update('lab.json', function ($x) use ($id) { if (isset($x['products'][$id])) { $x['products'][$id]['status'] = 'testing'; $x['products'][$id]['updated'] = time(); } return ['data' => $x]; });
  tt_json($r);
}
tt_json(['error' => 'Unknown action.'], 400);
FEB2CC4C9;
$NEW['desk/lab.js'] = <<<'FB1A0738E'
/* 3G's Command Center: THE PRODUCT LAB (TTLAB).
   One question, answered honestly: can WE win this product? Scored against his real blank
   costs, his own presses and his own call list. Backend: ../api/lab.php */
(function(){
var TABS = {lab:'The Product Lab'};
var S = {list:null, blanks:{}, cur:null, msg:'', busy:false, view:'list', offer:null, leads:null, pick:{}, biz:'orlando'};
function $(i){ return document.getElementById(i); }
function e(s){ return String(s==null?'':s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function q(s){ return String(s==null?'':s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function m2(n){ return '$' + (+n||0).toFixed(2); }
function K(){ try { return sessionStorage.getItem('ttDesk') || ''; } catch(x){ return ''; } }
function get(p){ return fetch('../api/lab.php' + p, {headers:{'X-TT-Key':K()}, cache:'no-store'}).then(function(r){ return r.json(); }); }
function post(b){ return fetch('../api/lab.php', {method:'POST', headers:{'Content-Type':'application/json','X-TT-Key':K()}, body:JSON.stringify(b)}).then(function(r){ return r.json(); }); }
function load(){ return get('?a=list').then(function(d){ S.list = d.products||[]; S.blanks = d.blanks||{}; draw(); }); }
function copy(t, btn){ var done=function(){ var o=btn.getAttribute('data-l')||btn.textContent; btn.setAttribute('data-l',o); btn.textContent='Copied ✓'; setTimeout(function(){btn.textContent=o;},1500); };
  if (navigator.clipboard) navigator.clipboard.writeText(t).then(done, function(){ window.prompt('Copy this:', t); }); else window.prompt('Copy this:', t); }
function blank(){ return {title:'', blank:'tee', design_line:'', angle:'', audience:'', source:'', demand:3, demand_src:'', competition:3, comp_src:'', group_fit:3, deadline:'', notes:'', status:'idea'}; }

function badge(v, score){
  var t = {build:'BUILD IT', test:'TEST IT', kill:'KILL IT'}[v] || v;
  return '<div class="lb-v lb-' + e(v) + '"><b>' + t + '</b><span>' + (score||0) + ' / 100</span></div>';
}
function vList(){
  var h = '<h2 class="lb-h">🧪 The Product Lab</h2>' +
    '<p class="lb-mute">Three gates before anything gets your press time: <b>can you print it</b>, <b>does it hold 35% at your real cost</b>, and <b>is it a design or just a category</b>. Fail one and it is dead, whatever the trend says.</p>' +
    '<div class="lb-acts"><button class="lb-b gold" onclick="TTLAB.make()">➕ New product idea</button>' +
    '<button class="lb-b" onclick="TTLAB.refresh()">↻ Refresh</button></div>';
  if (S.msg) h += '<div class="lb-msg">' + e(S.msg) + '</div>';
  var L = S.list || [];
  if (!L.length) return h + '<div class="lb-empty">Nothing in the lab yet. Tap <b>New product idea</b> and put one through the gates — it takes about a minute.</div>';
  return h + L.map(function(P){
    return '<div class="lb-card"><div class="lb-head"><div><b class="lb-t">' + e(P.title) + '</b>' +
      '<div class="lb-sub">' + e((S.blanks[P.blank]||{}).name || P.blank) + (P.design_line?' · “' + e(P.design_line) + '”':'') + (P.audience?' · ' + e(P.audience):'') + '</div></div>' +
      badge(P.verdict, P.score) + '</div>' +
      '<div class="lb-acts"><button class="lb-b" onclick="TTLAB.open(\'' + q(P.id) + '\')">Open</button>' +
      (P.verdict!=='kill'?'<button class="lb-b gold" onclick="TTLAB.offerStart(\'' + q(P.id) + '\')">Offer it to a group</button>':'') + '</div></div>';
  }).join('');
}
function fld(k, label, ph, type){
  var v = S.cur[k]; v = (v==null?'':v);
  return '<label>' + e(label) + '<input type="' + (type||'text') + '" value="' + e(v) + '" placeholder="' + e(ph||'') + '" oninput="TTLAB.set(\'' + k + '\',this.value)"></label>';
}
function rng(k, label, lo, hi){
  var v = +S.cur[k] || 0;
  return '<label>' + e(label) + ' <b class="lb-num">' + v + '</b>' +
    '<input type="range" min="0" max="5" step="1" value="' + v + '" oninput="TTLAB.set(\'' + k + '\',this.value)">' +
    '<span class="lb-ends">' + e(lo) + ' <i>·</i> ' + e(hi) + '</span></label>';
}
function vEdit(){
  var P = S.cur, R = S.res || {};
  var h = '<div class="lb-acts"><button class="lb-b" onclick="TTLAB.back()">← All products</button></div>';
  h += '<h2 class="lb-h">' + e(P.title || 'New product idea') + '</h2>';
  if (S.msg) h += '<div class="lb-msg">' + e(S.msg) + '</div>';

  h += '<div class="lb-card"><b class="lb-lb">What it is</b><div class="lb-form">' +
    fld('title','Call it something','Christmas family shirt') +
    '<label>On what<select onchange="TTLAB.set(\'blank\',this.value)">' + Object.keys(S.blanks).map(function(k){
      var b = S.blanks[k];
      return '<option value="' + e(k) + '"' + (P.blank===k?' selected':'') + '>' + e(b.name) + ' · blank ' + m2(b.cost) + ' · ' + e(b.how) + '</option>'; }).join('') + '</select></label>' +
    '<label class="wide">The exact words on it<input value="' + e(P.design_line) + '" placeholder="EXCELLENCE IS A HABIT" oninput="TTLAB.set(\'design_line\',this.value)"></label>' +
    fld('audience','Who it is for','church men’s ministry') +
    fld('angle','The feeling','bold, gold on black, legacy') +
    fld('deadline','Date pulling it','','date') +
    fld('source','Where the idea came from','TikTok, a customer asked, Trend Radar') +
    '</div></div>';

  h += '<div class="lb-card"><b class="lb-lb">What you honestly know</b>' +
    '<p class="lb-mute" style="margin-top:0">Guessing here makes the score lie to you. If you do not know, put 0 and go find out.</p>' +
    '<div class="lb-form">' +
    rng('demand','Is anyone actually asking for it?','nobody','people ask me') +
    rng('competition','How crowded is it?','nobody does it','everybody does it') +
    rng('group_fit','Could a whole group want the same one?','one person only','a church would buy 50') +
    '<label class="wide">Where that comes from<input value="' + e(P.demand_src) + '" placeholder="3 customers asked at the Oct market" oninput="TTLAB.set(\'demand_src\',this.value)"></label>' +
    '</div></div>';

  if (R.gates) {
    h += '<div class="lb-card lb-res">' + badge(R.verdict, R.score) +
      '<p class="lb-why">' + e(R.why) + '</p>' +
      '<div class="lb-gates">' + Object.keys(R.gates).map(function(k){
        var g = R.gates[k];
        return '<div class="lb-g ' + (g.ok?'ok':'no') + '"><b>' + (g.ok?'✓':'✕') + ' ' + e(g.name) + '</b><span>' + e(g.note) + '</span></div>';
      }).join('') + '</div>';
    if (R.parts && R.verdict!=='kill') h += '<div class="lb-parts">' + Object.keys(R.parts).map(function(k){
        return '<div><b>' + R.parts[k] + '</b><span>' + e(k) + '</span></div>'; }).join('') + '</div>';
    h += '</div>';
  }
  if (R.ladder && R.ladder.length) {
    h += '<div class="lb-card"><b class="lb-lb">What you can charge, and keep</b>' +
      '<div class="lb-tbl"><table><thead><tr><th>How many</th><th>Price each</th><th>You keep</th><th>Margin</th><th>Postage</th></tr></thead><tbody>' +
      R.ladder.map(function(r){ return '<tr' + (r.margin<35?' class="bad"':'') + '><td><b>' + e(r.name) + '</b><br><small>' + e(r.qty) + '</small></td><td><b>' + m2(r.price) + '</b></td><td>' + m2(r.keep) + '</td><td>' + r.margin + '%</td><td><small>' + m2(r.ship) + '</small></td></tr>'; }).join('') +
      '</tbody></table></div><p class="lb-mute">Worked out from your real blank cost, Stripe, the 7% tax you absorb, and postage — one box for a group, a single label on its own.</p></div>';
  }
  if (R.plan && R.plan.length) {
    h += '<div class="lb-card"><b class="lb-lb">The next 72 hours</b>' +
      R.plan.map(function(x,i){ return '<div class="lb-step"><span>' + (i+1) + '</span><div><b>' + e(x.h) + '</b><p>' + e(x.b) + '</p></div></div>'; }).join('') + '</div>';
  }
  if (R.artistly) {
    h += '<div class="lb-card"><b class="lb-lb">Ready to run in Artistly</b><pre class="lb-pre">' + e(R.artistly) + '</pre>' +
      '<div class="lb-acts"><button class="lb-b sm" onclick="TTLAB.copyText(this,\'' + q(R.artistly) + '\')">Copy the prompt</button></div></div>';
  }
  if (R.offer) {
    h += '<div class="lb-card"><b class="lb-lb">The line that offers it to a group</b><pre class="lb-pre">' + e(R.offer) + '</pre>' +
      '<div class="lb-acts"><button class="lb-b sm" onclick="TTLAB.copyText(this,\'' + q(R.offer) + '\')">Copy it</button></div>' +
      '<p class="lb-mute">{org} fills in with their name when it lands on a lead.</p></div>';
  }
  h += '<div class="lb-card"><div class="lb-acts">' +
    '<button class="lb-b gold" onclick="TTLAB.save()">Save</button>' +
    (P.id && R.verdict!=='kill' ? '<button class="lb-b green" onclick="TTLAB.offerStart(\'' + q(P.id) + '\')">Offer it to a group →</button>' : '') +
    (P.id ? '<button class="lb-b sm" onclick="TTLAB.remove()">Delete</button>' : '') +
    '</div></div>';
  return h;
}
function vOffer(){
  var O = S.offer || {}, L = S.leads || [];
  var h = '<div class="lb-acts"><button class="lb-b" onclick="TTLAB.back()">← Back</button></div>';
  h += '<h2 class="lb-h">Offer “' + e(O.title||'') + '” to a group</h2>';
  if (S.msg) h += '<div class="lb-msg">' + e(S.msg) + '</div>';
  h += '<div class="lb-card"><b class="lb-lb">Which business</b><div class="lb-acts">' +
    [['orlando','Orlando Group Orders'],['ggfg','GGFG Fundraising'],['gpts','GPTS'],['web','AI Web Solutions']].map(function(x){
      return '<button class="lb-b' + (S.biz===x[0]?' gold':'') + '" onclick="TTLAB.biz(\'' + x[0] + '\')">' + e(x[1]) + '</button>'; }).join('') + '</div></div>';
  if (!L.length) return h + '<div class="lb-empty">No leads loaded for that business yet. Load the starter list in the Outreach Machine first.</div>';
  var n = Object.keys(S.pick).filter(function(k){ return S.pick[k]; }).length;
  h += '<div class="lb-card"><b class="lb-lb">Pick who gets it <span class="lb-num">' + n + ' picked</span></b>' +
    '<p class="lb-mute" style="margin-top:0">Five is the right number. This is a test, not a blast.</p>' +
    '<div class="lb-picks">' + L.slice(0,80).map(function(x){
      return '<label class="lb-pk' + (S.pick[x.id]?' on':'') + '"><input type="checkbox"' + (S.pick[x.id]?' checked':'') + ' onchange="TTLAB.pick(\'' + q(x.id) + '\',this.checked)"> <b>' + e(x.org) + '</b><span>' + e(x.city||'') + '</span></label>';
    }).join('') + '</div>' +
    '<div class="lb-acts"><button class="lb-b green" onclick="TTLAB.send()"' + (n?'':' disabled') + '>Put it on ' + n + ' call' + (n===1?'':'s') + ' for today</button></div></div>';
  return h;
}
function draw(){ var b=$('lbBody'); if(!b) return; if(!S.list && !S.cur){ b.innerHTML='<div class="lb-empty">Loading…</div>'; return; }
  b.innerHTML = S.view==='offer' ? vOffer() : (S.cur ? vEdit() : vList()); }
function rescore(){
  clearTimeout(S.t);
  S.t = setTimeout(function(){
    post({a:'score', product:S.cur}).then(function(r){ if (r.product){ S.res = r.product; draw(); } });
  }, 250);
}
var CSS = '<style>' +
'.lb-wrap{font:16px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;color:#2A241E;max-width:1000px;margin:0 auto}' +
'.lb-h{font-size:1.5rem;margin:14px 0 8px}.lb-mute{color:#6b5d4a}.lb-wrap small{color:#6b5d4a}' +
'.lb-acts{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}' +
'.lb-b{appearance:none;border:2px solid #2A241E;background:#fff;color:#2A241E;font:800 15px/1 system-ui;padding:12px 15px;border-radius:12px;cursor:pointer;display:inline-flex;align-items:center}' +
'.lb-b.gold{background:#C9A24E;border-color:#C9A24E;color:#14110D}.lb-b.green{background:#1F8A4C;border-color:#1F8A4C;color:#fff}' +
'.lb-b.sm{font-size:13px;padding:8px 11px;border-width:1px}.lb-b:disabled{opacity:.4;cursor:default}' +
'.lb-card{background:#fff;border:2px solid #e7dcc8;border-radius:14px;padding:14px 16px;margin-bottom:12px}' +
'.lb-lb{display:block;font-size:1.05rem;margin-bottom:10px}' +
'.lb-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}' +
'.lb-t{font-size:1.2rem;display:block}.lb-sub{font-size:14px;color:#6b5d4a;margin-top:2px}' +
'.lb-v{text-align:center;border-radius:12px;padding:10px 14px;min-width:132px}' +
'.lb-v b{display:block;font:800 19px/1.1 system-ui}.lb-v span{font-size:13px;font-weight:700;opacity:.85}' +
'.lb-build{background:#1F8A4C;color:#fff}.lb-test{background:#C9A24E;color:#14110D}.lb-kill{background:#3f3f46;color:#fff}' +
'.lb-res{border-color:#C9A24E}.lb-res .lb-v{margin-bottom:10px}' +
'.lb-why{font-size:17px;font-weight:700;margin:0 0 12px}' +
'.lb-gates{display:grid;gap:8px}' +
'.lb-g{border-radius:11px;padding:11px 13px;border:2px solid}' +
'.lb-g.ok{background:#e3f3ea;border-color:#2E7D5E;color:#174535}.lb-g.no{background:#fff1f3;border-color:#E11D48;color:#9f1239}' +
'.lb-g b{display:block;font-size:15px;margin-bottom:3px}.lb-g span{font-size:14px;line-height:1.45}' +
'.lb-parts{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;margin-top:12px}' +
'.lb-parts div{background:#FBF6EE;border:1px solid #efe4d0;border-radius:10px;padding:9px;text-align:center}' +
'.lb-parts b{display:block;font-size:1.4rem}.lb-parts span{font-size:12px;color:#6b5d4a;font-weight:700}' +
'.lb-form{display:grid;grid-template-columns:1fr 1fr;gap:11px}.lb-form .wide{grid-column:1/-1}' +
'.lb-form label{display:flex;flex-direction:column;gap:5px;font-size:13px;font-weight:700;color:#5b4d3a}' +
'.lb-form input,.lb-form select{font:inherit;font-size:15px;border:2px solid #e0d3c0;border-radius:10px;padding:10px 11px;width:100%;box-sizing:border-box}' +
'.lb-form input[type=range]{padding:0;border:0;height:30px}' +
'.lb-ends{font-size:12px;color:#8b7d66;font-weight:600;display:flex;justify-content:space-between}.lb-ends i{font-style:normal}' +
'.lb-num{background:#14110D;color:#F3D27A;border-radius:8px;padding:3px 9px;font-size:14px}' +
'.lb-tbl{overflow-x:auto;border:2px solid #e7dcc8;border-radius:12px}' +
'.lb-tbl table{width:100%;border-collapse:collapse;font-size:15px}' +
'.lb-tbl th,.lb-tbl td{text-align:left;padding:10px 12px;border-bottom:1px solid #f1ece2}' +
'.lb-tbl th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b5d4a;background:#FBF6EE}' +
'.lb-tbl tr.bad td{background:#fff1f3}' +
'.lb-step{display:flex;gap:11px;margin-bottom:11px;align-items:flex-start}' +
'.lb-step span{background:#14110D;color:#F3D27A;font:800 14px/1 system-ui;border-radius:9px;padding:10px 12px;flex:0 0 auto}' +
'.lb-step b{display:block;font-size:15px;margin-bottom:2px}.lb-step p{margin:0;font-size:15px;line-height:1.45;color:#4a4036}' +
'.lb-pre{white-space:pre-wrap;font:15px/1.55 system-ui;margin:0;color:#000;background:#FBF6EE;border-radius:10px;padding:12px 13px}' +
'.lb-msg{background:#e3f3ea;border:2px solid #2E7D5E;color:#1c5340;border-radius:12px;padding:11px 14px;margin:10px 0;font-weight:700}' +
'.lb-empty{background:#FBF6EE;border:2px dashed #d9c9a8;border-radius:14px;padding:18px;color:#5b4d3a}' +
'.lb-picks{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:8px}' +
'.lb-pk{display:flex;gap:8px;align-items:center;border:2px solid #e7dcc8;border-radius:11px;padding:10px 12px;cursor:pointer;font-size:14px;background:#fff}' +
'.lb-pk.on{border-color:#1F8A4C;background:#e3f3ea}.lb-pk b{flex:1}.lb-pk span{color:#6b5d4a;font-size:12px}' +
'@media(max-width:760px){.lb-form{grid-template-columns:1fr}.lb-head{flex-direction:column}.lb-v{min-width:0;width:100%}}' +
'</style>';
window.TTLAB = {
  has: function(t){ return !!TABS[t]; },
  html: function(){ setTimeout(function(){ if (!S.list) load(); else draw(); }, 0); return CSS + '<div class="lb-wrap"><div id="lbBody"><div class="lb-empty">Loading…</div></div></div>'; },
  bind: function(){ draw(); },
  refresh: function(){ S.msg=''; S.list=null; S.cur=null; S.view='list'; draw(); load(); },
  back: function(){ S.cur=null; S.offer=null; S.view='list'; S.msg=''; S.list=null; draw(); load(); },
  make: function(){ S.cur = blank(); S.res=null; S.msg=''; S.view='edit'; draw(); rescore(); window.scrollTo(0,0); },
  open: function(id){ get('?a=get&p=' + encodeURIComponent(id)).then(function(r){ if (r.error){ S.msg=r.error; draw(); return; }
    S.cur = r.product; S.res = r.product; S.view='edit'; S.msg=''; draw(); window.scrollTo(0,0); }); },
  set: function(k,v){ S.cur[k]=v; if (k==='demand'||k==='competition'||k==='group_fit') draw(); rescore(); },
  copyText: function(btn,t){ copy(t,btn); },
  save: function(){
    if (S.busy) return;
    if (!String(S.cur.title||'').trim()){ S.msg='Give it a name first.'; draw(); return; }
    S.busy=true; S.msg='Saving…'; draw();
    post({a:'save', id:S.cur.id||'', product:S.cur}).then(function(r){ S.busy=false;
      if (r.error){ S.msg=r.error; draw(); return; }
      S.cur = r.product; S.res = r.product; S.msg='Saved.'; draw(); });
  },
  remove: function(){ if (!S.cur.id) return; if (!confirm('Delete this product idea?')) return;
    post({a:'delete', id:S.cur.id}).then(function(){ S.cur=null; S.list=null; S.msg='Deleted.'; S.view='list'; draw(); load(); }); },
  offerStart: function(id){
    get('?a=get&p=' + encodeURIComponent(id)).then(function(r){
      if (r.error){ S.msg=r.error; draw(); return; }
      S.offer = r.product; S.cur=null; S.pick={}; S.view='offer'; S.msg=''; draw(); TTLAB.biz(S.biz);
    });
  },
  biz: function(b){ S.biz=b; S.leads=null; S.pick={}; draw();
    fetch('../api/machine.php?a=all&b=' + encodeURIComponent(b), {headers:{'X-TT-Key':K()}, cache:'no-store'})
      .then(function(r){ return r.json(); })
      .then(function(d){ S.leads = (d.all||[]).filter(function(x){ return !x.archived && !x.spam && x.stage!=='lost'; }); draw(); }); },
  pick: function(id,on){ S.pick[id]=on; draw(); },
  send: function(){
    if (S.busy) return;
    var ids = Object.keys(S.pick).filter(function(k){ return S.pick[k]; });
    if (!ids.length) return;
    S.busy=true; S.msg='Queueing…'; draw();
    post({a:'offer', id:S.offer.id, biz:S.biz, leads:ids}).then(function(r){ S.busy=false;
      S.msg = r.error ? r.error : ('Done. ' + r.queued + ' lead' + (r.queued===1?'':'s') + ' now have this offer as their opening line, due today. Open the Outreach Machine and call them.');
      S.pick={}; draw(); });
  }
};
(function(){
  function add(){
    var nav = document.getElementById('tabs');
    if (!nav || !nav.querySelector('button[data-t]')) return setTimeout(add, 250);
    if (nav.querySelector('button[data-t="lab"]')) return;
    var b = document.createElement('button');
    b.type='button'; b.dataset.t='lab'; b.setAttribute('aria-pressed','false');
    b.innerHTML = '🧪 The Product Lab <span class="n" id="n-lab" hidden></span>';
    var after = nav.querySelector('button[data-t="narr"]') || nav.querySelector('button[data-t="orl"]');
    if (after && after.nextSibling) nav.insertBefore(b, after.nextSibling); else nav.insertBefore(b, nav.firstChild);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
})();
})();
FB1A0738E;

$P = json_decode('[["desk/index.html", "<script src=\\"narrator.js?v=1\\"></script>", "<script src=\\"narrator.js?v=1\\"></script>\\n<script src=\\"lab.js?v=1\\"></script>", "load the Product Lab"], ["desk/navgroups.js", "[\'orl\',\'narr\',\'start\',\'dash\',\'cal\',\'inbox\',\'play\',\'tools\']", "[\'orl\',\'narr\',\'lab\',\'start\',\'dash\',\'cal\',\'inbox\',\'play\',\'tools\']", "put the Product Lab in the Home menu"]]', true);
$files = [];
foreach ($P as $p) { $f = $root . '/' . $p[0]; if (!isset($files[$f])) { if (!is_file($f)) { $out .= "MISSING $p[0]\n"; $bad++; continue; } $files[$f] = file_get_contents($f); } }
foreach ($P as $p) {
  $f = $root . '/' . $p[0]; if (!isset($files[$f])) continue;
  if (strpos($files[$f], $p[2]) !== false && $p[1] !== $p[2]) { $out .= "already  $p[3]\n"; continue; }
  $n = substr_count($files[$f], $p[1]);
  if ($n !== 1) { $out .= "ANCHOR x$n ($p[3]) in $p[0]\n"; $bad++; continue; }
  $files[$f] = str_replace($p[1], $p[2], $files[$f]); $out .= "ok  $p[3]\n";
}
if ($bad) { echo "STOPPED, nothing changed.\n$out"; exit; }
$wrote = [];
foreach ($NEW as $rel => $body) {
  $f = $root . '/' . $rel; $dir = dirname($f);
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { echo "STOPPED: could not make $dir\n$out"; exit; }
  if (is_file($f) && !is_file($f . $tag)) copy($f, $f . $tag);
  if (@file_put_contents($f, $body . "\n", LOCK_EX) === false) { echo "STOPPED: could not write $rel\n$out"; exit; }
  $wrote[] = $f; $out .= "ok  $rel written (" . strlen($body) . " bytes)\n";
}
foreach ($files as $f => $c) { if (!is_file($f . $tag)) copy($f, $f . $tag); file_put_contents($f, $c, LOCK_EX); $out .= "ok  " . str_replace($root . '/', '', $f) . " patched\n"; }
$lint = []; $err = false;
foreach (['api/lab.php'] as $pf) { $o = []; $rc = 0; @exec('php -l ' . escapeshellarg($root . '/' . $pf) . ' 2>&1', $o, $rc); $t = trim(implode(' ', $o)); if ($rc !== 0 && $t !== '') { $err = true; $lint[] = "ERR $pf: $t"; } else $lint[] = "ok  $pf syntax " . ($t ?: '(linter unavailable, skipped)'); }
$out .= implode("\n", $lint) . "\n";
if ($err) {
  foreach ($files as $f => $c) if (is_file($f . $tag)) copy($f . $tag, $f);
  foreach ($wrote as $f) { if (is_file($f . $tag)) copy($f . $tag, $f); else @unlink($f); }
  $out .= "ROLLED BACK because of a syntax error.\n";
} else { @unlink(__FILE__); $out .= "\nDONE. The Product Lab sits with the Outreach Machine and the Narrator.\n"; }
echo $out;
