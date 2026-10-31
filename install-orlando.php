<?php
/* ORLANDO GROUP ORDERS installer (Oct 31 2026).
   Adds: api/orlando.php (campaign engine), desk/orlando.js (Command Center tab), /group-orders/ (free-design page).
   Patches: desk/index.html, desk/navgroups.js. Backs everything up, checks syntax, rolls back on error, deletes itself.
   Open once with ?go=c5e706e535363440 */
if (($_GET['go'] ?? '') !== 'c5e706e535363440') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = preg_match('~/_inst~', __DIR__) ? dirname(__DIR__) : __DIR__;
$tag = '.bak-20261031-orlando'; $out = ''; $bad = 0;
if (!is_file($root . '/api/svc_lib.php')) exit("Wrong folder: api/svc_lib.php not found.\n");
$NEW = [];
$NEW['api/orlando.php'] = <<<'F78CC215E'
<?php
// Orlando Group Orders engine (Oct 31 2026 -> Dec 31 2026). The one campaign we run to close the year.
// Desk:   GET  ?a=today|all|tiers|scripts&id=..|board      POST {a:import|save|touch|next|campaign|archive|goal}
// Public: POST ?a=request   (free-mockup request from /group-orders/)
// Data:   tt_data/leads.json  (same file the Leads tab uses, so nothing is lost)
//         tt_data/orlando.json (goal + counters)   tt_data/campaigns/<id>.json (group pages, read by checkout.php)
require __DIR__ . '/svc_lib.php';
require_once __DIR__ . '/money_lib.php';

const OG_SEG = ['church' => 'Church', 'reunion' => 'Family reunion', 'tailgate' => 'Tailgate / alumni', 'youth' => 'Youth / school team', 'business' => 'Business / nonprofit'];
const OG_SEG_TYPE = ['church' => 'Church', 'reunion' => 'Family reunion', 'tailgate' => 'Other', 'youth' => 'Team', 'business' => 'Business'];
const OG_STAGES = ['new', 'contacted', 'interested', 'proof', 'quote', 'won', 'lost'];
const OG_STAGE_NAMES = ['new' => 'Not called yet', 'contacted' => 'Called, no yes yet', 'interested' => 'Interested', 'proof' => 'Designs sent', 'quote' => 'Page is live', 'won' => 'PAID', 'lost' => 'No'];
const OG_MIN_MARGIN = 35;
const OG_BULK_POST = 0.75;          // postage share per piece, one box to the leader
const OG_TARGET_PCT = 42;           // what we aim to keep, used to price crewnecks and hoodies off the tee tier

// Approved ladder (Oct 26). Tee price is the anchor; crew and hoodie are priced to hold the same margin.
function og_tiers() {
  return [
    ['id' => 'starter', 'name' => 'Starter',    'min' => 12,  'max' => 23, 'price' => 28.00, 'payout' => 0, 'best' => 'small reunions, youth groups'],
    ['id' => 'group',   'name' => 'Group',      'min' => 24,  'max' => 49, 'price' => 25.00, 'payout' => 0, 'best' => 'most churches and reunions'],
    ['id' => 'church',  'name' => 'Church',     'min' => 50,  'max' => 99, 'price' => 22.50, 'payout' => 0, 'best' => 'church-wide events'],
    ['id' => 'event',   'name' => 'Event',      'min' => 100, 'max' => 0,  'price' => 21.00, 'payout' => 0, 'best' => 'tailgates, conferences'],
    ['id' => 'fund',    'name' => 'Fundraiser', 'min' => 12,  'max' => 0,  'price' => 30.00, 'payout' => 5.00, 'best' => 'churches, schools, GGFG'],
  ];
}
function og_deadlines() {
  return [
    ['k' => 'classic_cut', 'name' => 'Florida Classic order cutoff', 'date' => '2026-11-10'],
    ['k' => 'classic',     'name' => 'Florida Classic game day',     'date' => '2026-11-21'],
    ['k' => 'goal',        'name' => '3 paid group orders',          'date' => '2026-11-30'],
    ['k' => 'xmas_cut',    'name' => 'Christmas order cutoff',       'date' => '2026-12-12'],
  ];
}
function og_margin($item, $price, $payout = 0, $set = null) {
  $set = $set ?: tts_settings();
  $cost = (float)(tts_cost_table($set, time())[$item] ?? 15);
  $stripe = $price * $set['stripe_pct'] / 100 + $set['stripe_fixed'];
  $tax = !empty($set['tax_absorbed']) ? $price * $set['sales_tax_pct'] / (100 + $set['sales_tax_pct']) : 0;
  $keep = $price - $cost - $stripe - $tax - OG_BULK_POST - $payout;
  return ['pct' => $price > 0 ? round($keep / $price * 100, 1) : 0, 'keep' => round($keep, 2), 'cost' => $cost];
}
// Smallest price (rounded up to the next 50c) that keeps $target% on this item.
function og_price_for($item, $target, $payout = 0, $set = null) {
  $set = $set ?: tts_settings();
  $cost = (float)(tts_cost_table($set, time())[$item] ?? 15);
  $k = 1 - $set['stripe_pct'] / 100 - (!empty($set['tax_absorbed']) ? $set['sales_tax_pct'] / (100 + $set['sales_tax_pct']) : 0) - $target / 100;
  if ($k <= 0.05) return 0;
  return ceil(($cost + $set['stripe_fixed'] + OG_BULK_POST + $payout) / $k * 2) / 2;
}
function og_tier_table() {
  $set = tts_settings(); $out = [];
  foreach (og_tiers() as $t) {
    $po = (float)$t['payout'];
    $tee = round((float)$t['price'], 2);
    $m = og_margin('tee', $tee, $po, $set);
    $target = max(OG_MIN_MARGIN + 1, min(OG_TARGET_PCT, $m['pct']));
    $t['items'] = [
      'tee'  => ['name' => 'T-shirt',   'price' => $tee,                                     'margin' => $m['pct'],                                           'keep' => $m['keep']],
      'crew' => ['name' => 'Crewneck',  'price' => og_price_for('crew', $target, $po, $set),  'margin' => og_margin('crew', og_price_for('crew', $target, $po, $set), $po, $set)['pct'], 'keep' => og_margin('crew', og_price_for('crew', $target, $po, $set), $po, $set)['keep']],
      'hood' => ['name' => 'Hoodie',    'price' => og_price_for('hood', $target, $po, $set),  'margin' => og_margin('hood', og_price_for('hood', $target, $po, $set), $po, $set)['pct'], 'keep' => og_margin('hood', og_price_for('hood', $target, $po, $set), $po, $set)['keep']],
    ];
    $t['label'] = $t['max'] ? $t['min'] . ' to ' . $t['max'] : $t['min'] . '+';
    $t['floor_ok'] = $m['pct'] >= OG_MIN_MARGIN;
    $out[] = $t;
  }
  return $out;
}
function og_tier($id) { foreach (og_tiers() as $t) if ($t['id'] === $id) return $t; return null; }
function og_tier_for_qty($q) { $q = (int)$q; $pick = og_tiers()[0]; foreach (og_tiers() as $t) { if ($t['id'] === 'fund') continue; if ($q >= $t['min']) $pick = $t; } return $pick; }

// ---------- the 7 scripts, filled in for one lead ----------
function og_scripts($L, $link = '') {
  $org = $L['org'] ?? 'your group';
  $first = trim(preg_split('/\s+/', trim((string)($L['contact'] ?? '')))[0] ?? '');
  $who = $first !== '' ? $first : 'there';
  $tier = og_tier_for_qty(max(24, (int)($L['qty'] ?? 24)));
  $price = '$' . number_format($tier['price'], 2);
  $seg = (string)($L['seg'] ?? 'church');
  $link = $link ?: ((string)($L['camp'] ?? '') !== '' ? tt_site() . '/fundraiser/?c=' . $L['camp'] : tt_site() . '/group-orders/');
  $S = [];
  $S[] = ['k' => 'call', 'name' => 'Phone script (30 seconds)', 'kind' => 'say', 'pick' => $seg === 'church',
    'body' => "Good morning, this is Dr. Graham with Urban Canvas Co. here in Orlando. We design custom shirts for churches free of charge, and each member pays for their own. Who handles events or the youth ministry there at " . $org . "?\n\n... Great. May I send them three design ideas for " . $org . " this week? There is no cost to look."];
  $S[] = ['k' => 'email', 'name' => 'Email (top pick: no upfront cost)', 'kind' => 'send', 'pick' => $seg === 'church',
    'subject' => 'Matching shirts for ' . $org . ', designed free',
    'body' => "Pastor " . ($L['contact'] ?: '[Last Name]') . ",\n\nI'm Dr. Robert Graham with Urban Canvas Co. here in the Orlando area. We make custom shirts for churches, and we design them for you at no charge.\n\nHere's how it works: we send you 3 design ideas within 24 hours. You pick one. Each member orders and pays for their own shirt on a simple page, so you never collect money or guess sizes. Groups of 24 or more start at " . $price . " a shirt.\n\nIs there an event coming up, like a homecoming, youth day, or Christmas service, where matching shirts would help?\n\nIf yes, reply with the event date and I'll have your designs to you tomorrow.\n\nIn service,\nDr. Robert D. Graham\nUrban Canvas Co.\n" . tt_site()];
  $S[] = ['k' => 'reunion', 'name' => 'Reunion DM', 'kind' => 'send', 'pick' => $seg === 'reunion',
    'body' => "Hi " . $who . "! I saw you're organizing the " . $org . " reunion. I make custom reunion shirts here in Orlando. I'll design 3 options for free (family name, year, location, whatever you want on it), and everybody orders their own size so you don't have to collect money. Want me to send the designs?"];
  $S[] = ['k' => 'tailgate', 'name' => 'Tailgate / alumni DM', 'kind' => 'send', 'pick' => $seg === 'tailgate',
    'body' => "Game day is Nov 21. Want your crew in matching shirts at the tailgate? I'll design 3 options free. Order closes Nov 10 so they're pressed in time. How many people are you bringing?"];
  $S[] = ['k' => 'follow', 'name' => 'Follow-up (day 3)', 'kind' => 'send', 'pick' => ($L['stage'] ?? '') === 'contacted',
    'body' => "Just checking in, " . $who . ". I have your 3 designs ready. Want me to send them over? No cost and no pressure."];
  $S[] = ['k' => 'fund', 'name' => 'Fundraiser pitch', 'kind' => 'say', 'pick' => false,
    'body' => "Your " . ($seg === 'church' ? 'church' : 'group') . " keeps $5 from every shirt sold, and you put up nothing. We only succeed when you do."];
  $S[] = ['k' => 'page', 'name' => 'Send the group page', 'kind' => 'send', 'pick' => ($L['stage'] ?? '') === 'quote',
    'body' => "Here it is, " . $who . ". This is the page for " . $org . ": " . $link . "\n\nShare that one link with everyone. Each person picks their size and pays for their own shirt, so you never have to collect money. I press them here in Orlando and ship the whole group in one box to you.\n\n- Dr. Robert D Graham, Urban Canvas Co."];
  $S[] = ['k' => 'thanks', 'name' => 'Thank-you + referral ask', 'kind' => 'send', 'pick' => ($L['stage'] ?? '') === 'won',
    'body' => "Thank you, " . $who . ". It was an honor to serve " . $org . ". Who else do you know planning a reunion, church event, or team season? I'll take care of them the same way."];
  return $S;
}

// ---------- store ----------
function og_meta() { $d = tt_read('orlando.json'); return ['goal_orders' => (int)($d['goal_orders'] ?? 3), 'goal_shirts' => (int)($d['goal_shirts'] ?? 72), 'calls' => (array)($d['calls'] ?? [])]; }
function og_is_ours($L) { return !empty($L['og']); }
function og_today() { return date('Y-m-d'); }
function og_plus($days) { return date('Y-m-d', strtotime('+' . (int)$days . ' day')); }

// units + money already paid on a campaign page
function og_camp_stats() {
  if (!function_exists('cp_stats')) { $f = __DIR__ . '/campaign.php'; if (!is_file($f)) return []; }
  $out = [];
  foreach (tts_load_orders(false)['orders'] as $o) {
    if (!empty($o['test'])) continue;
    foreach (($o['cart']['items'] ?? []) as $i) {
      $cid = (string)($i['camp'] ?? ''); if ($cid === '') continue;
      $q = max(1, (int)($i['qty'] ?? 1));
      if (!isset($out[$cid])) $out[$cid] = ['units' => 0, 'sales' => 0, 'orders' => 0, 'keys' => []];
      $out[$cid]['units'] += $q; $out[$cid]['sales'] += (int)($i['pc'] ?? 0) * $q;
      $k = (string)($o['key'] ?? ''); if ($k !== '' && !isset($out[$cid]['keys'][$k])) { $out[$cid]['keys'][$k] = 1; $out[$cid]['orders']++; }
    }
  }
  return $out;
}
function og_board($leads) {
  $b = ['total' => 0]; foreach (OG_STAGES as $s) $b[$s] = 0;
  $touch = 0; $proofs = 0;
  foreach ($leads as $L) { if (!empty($L['archived'])) continue; $b['total']++; $st = in_array($L['stage'] ?? 'new', OG_STAGES, true) ? $L['stage'] : 'new'; $b[$st]++; $touch += (int)($L['touches'] ?? 0); $proofs += (int)($L['proofs'] ?? 0); }
  $b['touches'] = $touch; $b['proofs'] = $proofs;
  return $b;
}

$a = (string)($_GET['a'] ?? '');
$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------- public: free-mockup request from the landing page ----------
if ($a === 'request' && $m === 'POST') {
  $b = tt_body();
  $org = tt_clean($b['org'] ?? '', 90);
  $contact = tt_clean($b['contact'] ?? '', 60);
  $phone = tt_clean($b['phone'] ?? '', 30);
  $email = filter_var($b['email'] ?? '', FILTER_VALIDATE_EMAIL) ? strtolower($b['email']) : '';
  if ($org === '' || $contact === '' || ($phone === '' && $email === '')) tt_json(['error' => 'Please add your name, your group, and a phone or email so we can send the designs.'], 400);
  $seg = isset(OG_SEG[$b['seg'] ?? '']) ? $b['seg'] : 'church';
  $rk = 'rate_' . substr(tt_ipkey(), 0, 16);
  $hit = tt_update('orlando.json', function ($d) use ($rk) {
    $n = (int)($d[$rk]['n'] ?? 0); $t = (int)($d[$rk]['t'] ?? 0);
    if (time() - $t > 3600) { $n = 0; $t = time(); }
    $d[$rk] = ['n' => $n + 1, 't' => $t];
    return ['data' => $d, 'result' => $n + 1];
  });
  if ($hit > 6) tt_json(['error' => 'We already have your request. We will be in touch today.'], 429);
  $id = substr(bin2hex(random_bytes(6)), 0, 10);
  tt_update('leads.json', function ($d) use ($id, $org, $contact, $phone, $email, $seg, $b) {
    $d['leads'] = $d['leads'] ?? [];
    $d['leads'][$id] = ['id' => $id, 'created' => time(), 'updated' => time(), 'og' => 1, 'seg' => $seg,
      'type' => OG_SEG_TYPE[$seg], 'org' => $org, 'contact' => $contact, 'phone' => $phone, 'email' => $email,
      'city' => tt_clean($b['city'] ?? '', 60), 'qty' => max(0, min(5000, (int)($b['qty'] ?? 0))),
      'event' => tt_clean($b['event'] ?? '', 80), 'evdate' => preg_match('~^\d{4}-\d{2}-\d{2}$~', (string)($b['evdate'] ?? '')) ? $b['evdate'] : '',
      'stage' => 'interested', 'next' => date('Y-m-d'), 'source' => 'group-orders page', 'touches' => 0, 'proofs' => 0,
      'notes' => [['at' => time(), 'text' => 'Asked for free designs online. ' . tt_clean($b['notes'] ?? '', 400)]]];
    return ['data' => $d, 'result' => true];
  });
  @tt_notify('FREE DESIGN REQUEST: ' . $org, "Group: $org\nName: $contact\nPhone: $phone\nEmail: $email\nType: " . OG_SEG[$seg] . "\nHeadcount: " . (int)($b['qty'] ?? 0) . "\nEvent: " . tt_clean($b['event'] ?? '', 80) . ' ' . (string)($b['evdate'] ?? '') . "\nNotes: " . tt_clean($b['notes'] ?? '', 400) . "\n\nOpen the Orlando tab in the Command Center and send 3 designs within 24 hours.", $email);
  tt_json(['ok' => true, 'msg' => 'Got it. Dr. Graham will send you 3 free designs within 24 hours.']);
}

// ---------- everything below needs the desk password ----------
tt_require_admin();
$set = tts_settings();
$D = tt_read('leads.json');
$LEADS = (array)($D['leads'] ?? []);

if ($m === 'GET') {
  if ($a === 'tiers') tt_json(['ok' => true, 'tiers' => og_tier_table(), 'floor' => OG_MIN_MARGIN, 'bulk_post' => OG_BULK_POST,
    'costs' => tts_cost_table($set, time()), 'stripe_pct' => $set['stripe_pct'], 'stripe_fixed' => $set['stripe_fixed'],
    'tax_pct' => !empty($set['tax_absorbed']) ? $set['sales_tax_pct'] : 0]);
  if ($a === 'scripts') { $L = $LEADS[preg_replace('/[^a-z0-9]/', '', (string)($_GET['id'] ?? ''))] ?? null; if (!$L) tt_json(['error' => 'Lead not found.'], 404); tt_json(['ok' => true, 'scripts' => og_scripts($L)]); }

  $cs = og_camp_stats();
  $today = og_today();
  $due = []; $fresh = []; $live = []; $won = [];
  foreach ($LEADS as $id => $L) {
    if (!empty($L['archived'])) continue;
    $L['id'] = $id;
    $L['stage'] = in_array($L['stage'] ?? 'new', OG_STAGES, true) ? $L['stage'] : 'new';
    $L['tier'] = og_tier_for_qty(max(1, (int)($L['qty'] ?? 0)))['id'];
    $L['last'] = (int)($L['updated'] ?? $L['created'] ?? 0);
    if (!empty($L['camp'])) { $s = $cs[$L['camp']] ?? ['units' => 0, 'sales' => 0, 'orders' => 0];
      $L['sold'] = $s['units']; $L['money'] = round($s['sales'] / 100, 2); $L['url'] = tt_site() . '/fundraiser/?c=' . $L['camp']; $live[] = $L; }
    if ($L['stage'] === 'won') $won[] = $L;
    if (in_array($L['stage'], ['won', 'lost'], true)) continue;
    if (($L['next'] ?? '') !== '' && $L['next'] <= $today) $due[] = $L;
    elseif ($L['stage'] === 'new' && ($L['next'] ?? '') === '') $fresh[] = $L;
  }
  usort($due, function ($x, $y) { $o = ['interested' => 0, 'proof' => 1, 'quote' => 2, 'contacted' => 3, 'new' => 4]; return [$o[$x['stage']] ?? 9, $x['next'] ?? ''] <=> [$o[$y['stage']] ?? 9, $y['next'] ?? '']; });
  usort($fresh, function ($x, $y) { return strcasecmp($x['org'] ?? '', $y['org'] ?? ''); });
  $dl = []; foreach (og_deadlines() as $d) { $d['days'] = (int)floor((strtotime($d['date']) - strtotime($today)) / 86400); $dl[] = $d; }
  $shirts = 0; $money = 0; foreach ($live as $L) { $shirts += (int)$L['sold']; $money += (float)$L['money']; }
  $mt = og_meta();
  $out = ['ok' => true, 'today' => $today, 'due' => $due, 'fresh' => array_slice($fresh, 0, 60), 'live' => $live,
    'board' => og_board($LEADS), 'deadlines' => $dl, 'segs' => OG_SEG, 'stages' => OG_STAGES, 'stage_names' => OG_STAGE_NAMES,
    'score' => ['paid_orders' => count($won), 'shirts' => $shirts, 'money' => round($money, 2), 'goal_orders' => $mt['goal_orders'], 'goal_shirts' => $mt['goal_shirts']],
    'tiers' => og_tier_table(), 'floor' => OG_MIN_MARGIN];
  if ($a === 'all') { $all = []; foreach ($LEADS as $id => $L) { $L['id'] = $id; $all[] = $L; } usort($all, function ($x, $y) { return strcasecmp($x['org'] ?? '', $y['org'] ?? ''); }); $out['all'] = $all; }
  tt_json($out);
}

$b = tt_body();
$op = (string)($b['a'] ?? '');
$id = preg_replace('/[^a-z0-9]/', '', (string)($b['id'] ?? ''));
$dt = function ($v) { $v = (string)$v; return preg_match('~^\d{4}-\d{2}-\d{2}$~', $v) ? $v : ''; };

if ($op === 'import') {
  $rows = (array)($b['rows'] ?? []);
  if (!$rows) tt_json(['error' => 'Nothing to import.'], 400);
  $r = tt_update('leads.json', function ($d) use ($rows, $dt) {
    $d['leads'] = $d['leads'] ?? [];
    $have = []; foreach ($d['leads'] as $L) $have[strtolower(trim((string)($L['org'] ?? '')))] = 1;
    $add = 0; $skip = 0;
    foreach ($rows as $row) {
      $org = tt_clean($row['org'] ?? '', 90); if ($org === '') continue;
      if (isset($have[strtolower($org)])) { $skip++; continue; }
      $seg = isset(OG_SEG[$row['seg'] ?? '']) ? $row['seg'] : 'church';
      $nid = substr(bin2hex(random_bytes(6)), 0, 10);
      $d['leads'][$nid] = ['id' => $nid, 'created' => time(), 'updated' => time(), 'og' => 1, 'seg' => $seg,
        'type' => OG_SEG_TYPE[$seg], 'org' => $org, 'contact' => tt_clean($row['contact'] ?? '', 60),
        'phone' => tt_clean($row['phone'] ?? '', 30), 'email' => filter_var($row['email'] ?? '', FILTER_VALIDATE_EMAIL) ? strtolower($row['email']) : '',
        'city' => tt_clean($row['city'] ?? '', 60), 'qty' => max(0, min(5000, (int)($row['qty'] ?? 0))),
        'event' => tt_clean($row['event'] ?? '', 80), 'evdate' => $dt($row['evdate'] ?? ''),
        'stage' => 'new', 'next' => '', 'source' => tt_clean($row['source'] ?? 'Orlando list', 40), 'touches' => 0, 'proofs' => 0, 'notes' => []];
      $have[strtolower($org)] = 1; $add++;
    }
    return ['data' => $d, 'result' => ['ok' => true, 'added' => $add, 'skipped' => $skip]];
  });
  tt_json($r);
}

if ($op === 'save') {
  $r = tt_update('leads.json', function ($d) use ($id, $b, $dt) {
    $d['leads'] = $d['leads'] ?? [];
    $nid = $id;
    if ($nid === '' || !isset($d['leads'][$nid])) {
      if (tt_clean($b['org'] ?? '', 90) === '') return ['data' => $d, 'result' => ['error' => 'Type the group name.']];
      $nid = substr(bin2hex(random_bytes(6)), 0, 10);
      $d['leads'][$nid] = ['id' => $nid, 'created' => time(), 'stage' => 'new', 'notes' => [], 'touches' => 0, 'proofs' => 0, 'og' => 1];
    }
    $L = $d['leads'][$nid]; $L['og'] = 1;
    if (isset($b['seg']) && isset(OG_SEG[$b['seg']])) { $L['seg'] = $b['seg']; $L['type'] = OG_SEG_TYPE[$b['seg']]; }
    foreach (['org' => 90, 'contact' => 60, 'phone' => 30, 'city' => 60, 'event' => 80, 'source' => 40] as $f => $mx) if (array_key_exists($f, $b)) $L[$f] = tt_clean($b[$f], $mx);
    if (array_key_exists('email', $b)) $L['email'] = filter_var($b['email'], FILTER_VALIDATE_EMAIL) ? strtolower($b['email']) : '';
    if (array_key_exists('qty', $b)) $L['qty'] = max(0, min(5000, (int)$b['qty']));
    if (array_key_exists('evdate', $b)) $L['evdate'] = $dt($b['evdate']);
    if (array_key_exists('next', $b)) $L['next'] = $dt($b['next']);
    if (isset($b['stage']) && in_array($b['stage'], OG_STAGES, true)) $L['stage'] = $b['stage'];
    if (!empty($b['note'])) $L['notes'][] = ['at' => time(), 'text' => tt_clean($b['note'], 500)];
    $L['notes'] = array_slice($L['notes'] ?? [], -100); $L['updated'] = time();
    $d['leads'][$nid] = $L;
    return ['data' => $d, 'result' => ['ok' => true, 'id' => $nid]];
  });
  tt_json($r, isset($r['error']) ? 400 : 200);
}

// One tap after a call. Sets the stage and the next follow-up date for you.
if ($op === 'touch') {
  $OUT = [
    'noanswer'  => ['stage' => 'contacted',  'days' => 2, 'say' => 'No answer'],
    'gatekeeper' => ['stage' => 'contacted', 'days' => 3, 'say' => 'Left word with the office'],
    'callback'  => ['stage' => 'contacted',  'days' => 1, 'say' => 'Call back'],
    'interested' => ['stage' => 'interested', 'days' => 1, 'say' => 'INTERESTED, wants to see designs'],
    'proofsent' => ['stage' => 'proof',      'days' => 3, 'say' => 'Sent 3 designs'],
    'pagesent'  => ['stage' => 'quote',      'days' => 3, 'say' => 'Group page sent'],
    'notnow'    => ['stage' => 'contacted',  'days' => 21, 'say' => 'Not now, try again later'],
    'won'       => ['stage' => 'won',        'days' => 0, 'say' => 'PAID. Group order won.'],
    'lost'      => ['stage' => 'lost',       'days' => 0, 'say' => 'No for this season'],
  ];
  $o = (string)($b['outcome'] ?? '');
  if (!isset($OUT[$o])) tt_json(['error' => 'Unknown outcome.'], 400);
  $r = tt_update('leads.json', function ($d) use ($id, $b, $o, $OUT, $dt) {
    if (!isset($d['leads'][$id])) return ['data' => $d, 'result' => ['error' => 'Lead not found.']];
    $L = $d['leads'][$id]; $c = $OUT[$o];
    $L['stage'] = $c['stage'];
    $L['next'] = $c['days'] > 0 ? ($dt($b['next'] ?? '') ?: date('Y-m-d', strtotime('+' . $c['days'] . ' day'))) : '';
    $L['touches'] = (int)($L['touches'] ?? 0) + 1;
    if ($o === 'proofsent') $L['proofs'] = (int)($L['proofs'] ?? 0) + 1;
    $txt = $c['say'] . (!empty($b['note']) ? ' - ' . tt_clean($b['note'], 400) : '');
    $L['notes'][] = ['at' => time(), 'text' => $txt];
    $L['notes'] = array_slice($L['notes'], -100); $L['updated'] = time(); $L['og'] = 1;
    $d['leads'][$id] = $L;
    return ['data' => $d, 'result' => ['ok' => true, 'id' => $id, 'stage' => $L['stage'], 'next' => $L['next']]];
  });
  tt_json($r, isset($r['error']) ? 400 : 200);
}

if ($op === 'archive') {
  $r = tt_update('leads.json', function ($d) use ($id) {
    if (!isset($d['leads'][$id])) return ['data' => $d, 'result' => ['error' => 'Lead not found.']];
    $d['leads'][$id]['archived'] = empty($d['leads'][$id]['archived']) ? time() : 0;
    $d['leads'][$id]['updated'] = time();
    return ['data' => $d, 'result' => ['ok' => true]];
  });
  tt_json($r, isset($r['error']) ? 400 : 200);
}

if ($op === 'goal') {
  tt_update('orlando.json', function ($d) use ($b) {
    if (isset($b['goal_orders'])) $d['goal_orders'] = max(1, min(500, (int)$b['goal_orders']));
    if (isset($b['goal_shirts'])) $d['goal_shirts'] = max(1, min(100000, (int)$b['goal_shirts']));
    return ['data' => $d];
  });
  tt_json(['ok' => true]);
}

// Build the live group-order page for a lead, straight off the approved ladder.
if ($op === 'campaign') {
  $L = $LEADS[$id] ?? null;
  if (!$L) tt_json(['error' => 'Lead not found.'], 404);
  $t = og_tier((string)($b['tier'] ?? '')) ?: og_tier_for_qty(max(24, (int)($L['qty'] ?? 24)));
  $tab = null; foreach (og_tier_table() as $x) if ($x['id'] === $t['id']) $tab = $x;
  $type = $t['id'] === 'fund' ? 'fund' : 'group';
  $ends = $dt($b['ends'] ?? '') ?: og_plus(14);
  $title = tt_clean($b['title'] ?? '', 80) ?: (($L['event'] ?? '') !== '' ? $L['org'] . ' - ' . $L['event'] : $L['org'] . ' Group Shirts');
  $goal = max(1, (int)($b['goal'] ?? max(24, (int)($L['qty'] ?? 24))));
  $on = (array)($b['items'] ?? ['tee']);
  $dir = TTS_DATA . '/campaigns'; if (!is_dir($dir)) @mkdir($dir, 0750, true);
  $base = trim(preg_replace('~[^a-z0-9]+~', '-', strtolower($L['org'])), '-'); if ($base === '') $base = 'group';
  if (strlen($base) > 36) $base = substr($base, 0, 36);
  $cid = $base; $i = 2; while (is_file($dir . '/' . $cid . '.json')) $cid = $base . '-' . $i++;
  $items = [];
  foreach (['tee', 'crew', 'hood'] as $g) {
    $isOn = in_array($g, $on, true);
    $items[$g] = ['on' => $isOn, 'price' => (float)($tab['items'][$g]['price'] ?? 0), 'payout' => $type === 'fund' ? (float)$t['payout'] : 0];
  }
  if (!$items['tee']['on'] && !$items['crew']['on'] && !$items['hood']['on']) $items['tee']['on'] = true;
  foreach ($items as $g => $it) { if (!$it['on']) continue; $mg = og_margin($g, $it['price'], $it['payout'], $set);
    if ($mg['pct'] < OG_MIN_MARGIN) tt_json(['error' => 'That would keep only ' . $mg['pct'] . '% on the ' . $g . '. Pick a higher tier.'], 400); }
  $C = ['id' => $cid, 'created' => time(), 'updated' => time(), 'status' => 'draft', 'paid_out' => 0, 'offline' => 0, 'imgs' => 0,
    'type' => $type, 'title' => $title, 'org' => $L['org'],
    'brand' => $type === 'fund' ? 'Grahams Global Fundraising Group' : 'Threaded Truths',
    'story' => tt_clean($b['story'] ?? '', 900) ?: ('Custom shirts for ' . $L['org'] . ', designed free and pressed by hand here in Orlando. Pick your size, pay for your own shirt, and we deliver the whole group in one box before the event.'),
    'goal' => $goal, 'ends' => $ends, 'delivery' => 'group', 'side' => 'back', 'colors' => ['black', 'gray', 'white'],
    'contact' => ['name' => (string)($L['contact'] ?? ''), 'email' => (string)($L['email'] ?? ''), 'phone' => (string)($L['phone'] ?? '')],
    'notes' => 'Orlando Group Orders - tier ' . $t['name'] . ' (' . $tab['label'] . ')', 'items' => $items, 'tier' => $t['id']];
  if (@file_put_contents($dir . '/' . $cid . '.json', json_encode($C, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false)
    tt_json(['error' => 'Could not save the page. Storage is not writable.'], 500);
  tt_update('leads.json', function ($d) use ($id, $cid, $t) {
    if (!isset($d['leads'][$id])) return ['data' => $d];
    $d['leads'][$id]['camp'] = $cid; $d['leads'][$id]['tier'] = $t['id']; $d['leads'][$id]['stage'] = 'quote';
    $d['leads'][$id]['next'] = date('Y-m-d', strtotime('+3 day')); $d['leads'][$id]['updated'] = time();
    $d['leads'][$id]['notes'][] = ['at' => time(), 'text' => 'Group page built as a draft: ' . $cid . ' (' . $t['name'] . ' tier)'];
    return ['data' => $d];
  });
  tt_json(['ok' => true, 'id' => $cid, 'url' => tt_site() . '/fundraiser/?c=' . $cid, 'status' => 'draft',
    'msg' => 'Page built as a DRAFT. Add the design image and set it to Open in the Group Pages tab, then send the link.']);
}
tt_json(['error' => 'Unknown action.'], 400);
F78CC215E;
$NEW['desk/orlando.js'] = <<<'F52CB0B17'
/* 3G's Command Center: ORLANDO GROUP ORDERS (TTOG).
   The one campaign we run from Oct 31 to Dec 31 2026. Call list, scripts, the approved price ladder,
   one-tap outcomes and a live group page per lead. Backend: ../api/orlando.php
   Built big and one-handed on purpose. */
(function(){
var TABS = {orl:'Orlando Group Orders'};
var S = {d:null, view:'calls', open:'', msg:'', busy:false, scripts:{}, loading:false};
var CHURCHES = [{"org":"Orlando Grace Church","city":"Altamonte Springs","phone":"407-660-1984","seg":"church","source":"Orlando church list"},{"org":"Grace Pointe Church","city":"Apopka","phone":"407-889-3383","seg":"church","source":"Orlando church list"},{"org":"Journey Christian Church","city":"Apopka","phone":"407-884-7223","seg":"church","source":"Orlando church list"},{"org":"St Paul AME Church","city":"Apopka","phone":"407-703-2901","seg":"church","source":"Orlando church list"},{"org":"Thrive Church Apopka","city":"Apopka","phone":"407-886-2806","seg":"church","source":"Orlando church list"},{"org":"Journey Church","city":"Fern Park","phone":"689-283-9798","seg":"church","source":"Orlando church list"},{"org":"New Hope Bible Church","city":"Kissimmee","phone":"407-791-5912","seg":"church","source":"Orlando church list"},{"org":"Church On The Living Edge","city":"Longwood","phone":"321-304-4111","seg":"church","source":"Orlando church list"},{"org":"King's Chapel","city":"Longwood","phone":"407-415-7447","seg":"church","source":"Orlando church list"},{"org":"LifePoint Christian Church","city":"Longwood","phone":"407-327-6468","seg":"church","source":"Orlando church list"},{"org":"WaterStone Church","city":"Longwood","phone":"407-339-8961","seg":"church","source":"Orlando church list"},{"org":"First AME Church","city":"Maitland","phone":"","seg":"church","source":"Orlando church list"},{"org":"All Nation Church of God of Prophecy","city":"Orlando","phone":"407-298-4411","seg":"church","source":"Orlando church list"},{"org":"Bethel Missionary Baptist Church","city":"Orlando","phone":"407-843-6612","seg":"church","source":"Orlando church list"},{"org":"Church at the Cross","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"Delaney Street Baptist Church","city":"Orlando","phone":"407-422-0758","seg":"church","source":"Orlando church list"},{"org":"Downtown Baptist Church Orlando","city":"Orlando","phone":"407-425-0200","seg":"church","source":"Orlando church list"},{"org":"Eastland Baptist Church","city":"Orlando","phone":"407-277-5858","seg":"church","source":"Orlando church list"},{"org":"Faith Baptist Church Orlando","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"First Orlando - John Young Campus","city":"Orlando","phone":"407-425-2555","seg":"church","source":"Orlando church list"},{"org":"Malone Memorial Church of God in Christ","city":"Orlando","phone":"407-295-5994","seg":"church","source":"Orlando church list"},{"org":"NEW LIFE CHURCH OF GOD","city":"Orlando","phone":"407-275-0563","seg":"church","source":"Orlando church list"},{"org":"New Bethel AME Church","city":"Orlando","phone":"407-295-6152","seg":"church","source":"Orlando church list"},{"org":"New Life Church COGIC","city":"Orlando","phone":"407-290-1467","seg":"church","source":"Orlando church list"},{"org":"Orlando Baptist Church","city":"Orlando","phone":"407-277-8671","seg":"church","source":"Orlando church list"},{"org":"South Orlando Baptist Church","city":"Orlando","phone":"407-859-1536","seg":"church","source":"Orlando church list"},{"org":"St MARK AME EAST CAMPUS","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"St. John Baptist Church","city":"Orlando","phone":"407-781-0700","seg":"church","source":"Orlando church list"},{"org":"Church of God Ministry of Jesus Christ Intl.","city":"Pine Castle","phone":"888-331-8197","seg":"church","source":"Orlando church list"},{"org":"Seminole Community Church","city":"Sanford","phone":"407-324-0199","seg":"church","source":"Orlando church list"},{"org":"Quay Church","city":"Windermere","phone":"407-505-4888","seg":"church","source":"Orlando church list"},{"org":"Discovery Church","city":"Winter Garden","phone":"407-855-3140","seg":"church","source":"Orlando church list"},{"org":"Garden City Church","city":"Winter Garden","phone":"407-499-8051","seg":"church","source":"Orlando church list"},{"org":"Hope Church Winter Garden","city":"Winter Garden","phone":"407-490-1988","seg":"church","source":"Orlando church list"},{"org":"Kingdom Culture Church","city":"Winter Garden","phone":"407-490-3958","seg":"church","source":"Orlando church list"},{"org":"Mosaic Church","city":"Winter Garden","phone":"407-654-8220","seg":"church","source":"Orlando church list"},{"org":"The Grove Bible Chapel","city":"Winter Garden","phone":"407-614-3255","seg":"church","source":"Orlando church list"},{"org":"Vida Church","city":"Winter Garden","phone":"407-436-5147","seg":"church","source":"Orlando church list"},{"org":"Vineland Road Christian Fellowship","city":"Winter Garden","phone":"407-656-3949","seg":"church","source":"Orlando church list"},{"org":"West Orlando Christian Center COGIC","city":"Winter Garden","phone":"407-656-5665","seg":"church","source":"Orlando church list"}];
var EXTRA = [
 {org:'Florida Classic tailgate crews (alumni + Divine Nine)', seg:'tailgate', city:'Orlando', source:'Florida Classic Nov 21', event:'Florida Classic tailgate', evdate:'2026-11-21', qty:30},
 {org:'Facebook family reunion groups - Central Florida', seg:'reunion', city:'Orlando', source:'Facebook groups', qty:24},
 {org:'Rec league + cheer + band boosters - Orange County', seg:'youth', city:'Orlando', source:'Youth sports', qty:24},
 {org:'Thanksgiving + Christmas family shirt groups', seg:'reunion', city:'Orlando', source:'Facebook parents groups', event:'Christmas', evdate:'2026-12-12', qty:12},
 {org:'GRBH + GGFG partner churches and nonprofits', seg:'business', city:'Orlando', source:'Our own partners', qty:24}
];
function $(i){ return document.getElementById(i); }
function e(s){ return String(s==null?'':s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function q(s){ return String(s==null?'':s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function mo(n){ return '$' + (+n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function K(){ try { return sessionStorage.getItem('ttDesk') || ''; } catch(x){ return ''; } }
function get(path){ return fetch('../api/orlando.php' + path, {headers:{'X-TT-Key':K()}, cache:'no-store'}).then(function(r){ return r.json(); }); }
function post(body){ return fetch('../api/orlando.php', {method:'POST', headers:{'Content-Type':'application/json','X-TT-Key':K()}, body:JSON.stringify(body)}).then(function(r){ return r.json(); }); }
function load(){ if (S.loading) return; S.loading = true; return get('?a=all').then(function(d){ S.loading=false; S.d = d; draw(); }).catch(function(){ S.loading=false; S.msg='Could not reach the server.'; draw(); }); }
function copy(txt, btn){
  var done = function(){ var o = btn.getAttribute('data-l') || btn.textContent; btn.setAttribute('data-l', o); btn.textContent = 'Copied ✓'; setTimeout(function(){ btn.textContent = o; }, 1500); };
  if (navigator.clipboard) navigator.clipboard.writeText(txt).then(done, function(){ window.prompt('Copy this:', txt); }); else window.prompt('Copy this:', txt);
}
function tel(p){ return String(p||'').replace(/[^0-9+]/g,''); }

/* ---------- pieces ---------- */
function clock(){
  var dl = (S.d && S.d.deadlines) || [];
  return '<div class="og-clock">' + dl.map(function(x){
    var cls = x.days < 0 ? 'gone' : x.days <= 7 ? 'hot' : '';
    return '<div class="og-cl ' + cls + '"><b>' + (x.days < 0 ? 'passed' : x.days + (x.days===1?' day':' days')) + '</b><span>' + e(x.name) + '</span><em>' + e(x.date) + '</em></div>';
  }).join('') + '</div>';
}
function score(){
  var s = (S.d && S.d.score) || {}, b = (S.d && S.d.board) || {};
  var po = s.paid_orders||0, go = s.goal_orders||3, sh = s.shirts||0, gs = s.goal_shirts||72;
  var pct = Math.min(100, Math.round(po/go*100));
  return '<div class="og-score"><div class="og-goal"><div class="og-row"><span class="og-big">' + po + ' of ' + go + ' paid group orders</span><span class="og-mute">' + pct + '%</span></div>' +
   '<div class="og-bar"><i style="width:' + pct + '%"></i></div>' +
   '<div class="og-mute">' + sh + ' of ' + gs + ' shirts · ' + mo(s.money||0) + ' collected · target Nov 30</div></div>' +
   '<div class="og-tiles">' + [['Calls made', b.touches||0],['Designs sent', b.proofs||0],['Interested', (b.interested||0)+(b.proof||0)],['Pages live', b.quote||0],['Not called yet', b.new||0]].map(function(t){
     return '<div class="og-t"><b>' + t[1] + '</b><span>' + t[0] + '</span></div>'; }).join('') + '</div></div>';
}
function badgeCount(){
  var d = S.d; if (!d) return 0; return (d.due||[]).length;
}
function stageChip(st){
  var nm = (S.d && S.d.stage_names && S.d.stage_names[st]) || st;
  return '<em class="og-st s-' + e(st) + '">' + e(nm) + '</em>';
}
function tierName(id){ var t = ((S.d&&S.d.tiers)||[]).filter(function(x){ return x.id===id; })[0]; return t ? t.name + ' · ' + '$' + t.items.tee.price.toFixed(2) : ''; }

function leadCard(L, mode){
  var open = S.open === L.id;
  var ph = tel(L.phone);
  var h = '<div class="og-card' + (open?' op':'') + '">';
  h += '<div class="og-head"><div><b class="og-org">' + e(L.org) + '</b>' +
   '<div class="og-sub">' + e(L.city||'') + (L.contact?' · ' + e(L.contact):'') + (L.qty?' · ' + L.qty + ' people':'') + '</div></div>' + stageChip(L.stage) + '</div>';
  if (L.notes && L.notes.length) { var n = L.notes[L.notes.length-1]; h += '<div class="og-last">Last: ' + e(n.text) + '</div>'; }
  h += '<div class="og-acts">';
  if (ph) h += '<a class="og-b call" href="tel:' + e(ph) + '">☎ Call ' + e(L.phone) + '</a>';
  else h += '<span class="og-b warn">No phone yet — look it up</span>';
  h += '<button class="og-b" onclick="TTOG.open(\'' + q(L.id) + '\')">' + (open?'Hide':'Script + log the call') + '</button>';
  h += '</div>';
  if (open) h += panel(L);
  return h + '</div>';
}
function panel(L){
  var sc = S.scripts[L.id];
  var h = '<div class="og-panel">';
  h += '<div class="og-out"><b>What happened?</b><div class="og-outs">' + [
    ['interested','⭐ Interested'],['proofsent','🎨 Sent 3 designs'],['noanswer','No answer'],['gatekeeper','Left word'],
    ['callback','Call back tomorrow'],['pagesent','🔗 Page sent'],['won','💰 PAID'],['notnow','Not now'],['lost','No']
  ].map(function(o){ return '<button class="og-o o-' + o[0] + '" onclick="TTOG.touch(\'' + q(L.id) + '\',\'' + o[0] + '\')">' + o[1] + '</button>'; }).join('') + '</div>' +
   '<input id="og-note-' + e(L.id) + '" placeholder="Add a note (who you spoke to, the event, the date)"></div>';
  h += '<div class="og-mk"><b>Build the group page</b><div class="og-row2">' +
    '<label>Tier<select id="og-tier-' + e(L.id) + '">' + ((S.d&&S.d.tiers)||[]).map(function(t){
      return '<option value="' + t.id + '"' + (L.tier===t.id?' selected':'') + '>' + e(t.name) + ' · ' + t.label + ' · $' + t.items.tee.price.toFixed(2) + ' tee</option>'; }).join('') + '</select></label>' +
    '<label>Closes<input type="date" id="og-ends-' + e(L.id) + '" value="' + e(S.d && S.d.deadlines ? defEnd() : '') + '"></label>' +
    '<button class="og-b gold" onclick="TTOG.page(\'' + q(L.id) + '\')">Build the page</button></div>' +
    (L.camp ? '<div class="og-live">Page: <a href="' + e(L.url||'') + '" target="_blank" rel="noopener">' + e(L.camp) + '</a> · ' + (L.sold||0) + ' sold · ' + mo(L.money||0) +
      ' <button class="og-b sm" onclick="TTOG.copyText(this,\'' + q(L.url||'') + '\')">Copy link</button></div>' : '') + '</div>';
  h += '<div class="og-sc"><b>Say this / send this</b>' + (sc ? sc.map(function(x,i){
    var body = (x.subject ? 'Subject: ' + x.subject + '\n\n' : '') + x.body;
    return '<div class="og-s' + (x.pick?' pick':'') + '"><div class="og-row"><span>' + e(x.name) + (x.pick?' <i>← use this one</i>':'') + '</span>' +
      '<button class="og-b sm" onclick="TTOG.copyIdx(this,\'' + q(L.id) + '\',' + i + ')">Copy</button></div><pre>' + e(body) + '</pre></div>'; }).join('')
    : '<div class="og-mute">Loading the scripts…</div>') + '</div>';
  return h + '</div>';
}
function defEnd(){
  var dl = (S.d&&S.d.deadlines)||[];
  for (var i=0;i<dl.length;i++) if (dl[i].days >= 3) return dl[i].date;
  var d = new Date(Date.now() + 14*864e5); return d.toISOString().slice(0,10);
}
function vCalls(){
  var d = S.d, h = '';
  var due = d.due||[], fresh = d.fresh||[];
  h += '<h2 class="og-h">☎ Call these now <span class="og-n">' + due.length + '</span></h2>';
  h += due.length ? due.map(function(L){ return leadCard(L,'due'); }).join('')
    : '<div class="og-empty">Nothing is due. Work the <b>Not called yet</b> list below — 10 calls a day is the whole job.</div>';
  h += '<h2 class="og-h">Not called yet <span class="og-n">' + fresh.length + '</span></h2>';
  if (!fresh.length && !(d.board||{}).total) h += '<div class="og-empty">No leads loaded. Tap <b>Load the Orlando list</b> at the top to put 40 Orlando churches and 5 other segments in here.</div>';
  else h += fresh.map(function(L){ return leadCard(L,'fresh'); }).join('');
  return h;
}
function vPages(){
  var live = (S.d&&S.d.live)||[];
  if (!live.length) return '<div class="og-empty">No group pages yet. Open a lead, pick the tier, and tap <b>Build the page</b>.</div>';
  return '<h2 class="og-h">Live group pages</h2>' + live.map(function(L){
    var goal = Math.max(1, L.qty||24), pct = Math.min(100, Math.round((L.sold||0)/goal*100));
    return '<div class="og-card"><div class="og-head"><div><b class="og-org">' + e(L.org) + '</b><div class="og-sub">' + e(L.camp) + ' · ' + tierName(L.tier) + '</div></div>' + stageChip(L.stage) + '</div>' +
      '<div class="og-bar"><i style="width:' + pct + '%"></i></div><div class="og-sub">' + (L.sold||0) + ' of ' + goal + ' shirts · ' + mo(L.money||0) + ' collected</div>' +
      '<div class="og-acts"><a class="og-b" href="' + e(L.url) + '" target="_blank" rel="noopener">Open the page</a>' +
      '<button class="og-b" onclick="TTOG.copyText(this,\'' + q(L.url) + '\')">Copy link</button>' +
      '<button class="og-b" onclick="TTOG.open(\'' + q(L.id) + '\')">Scripts</button></div>' +
      (S.open===L.id ? panel(L) : '') + '</div>';
  }).join('');
}
function vLadder(){
  var t = (S.d&&S.d.tiers)||[], f = (S.d&&S.d.floor)||35;
  return '<h2 class="og-h">The approved ladder</h2><p class="og-mute">Tee price is the one you quote. Crewneck and hoodie prices are worked out live so every item keeps the same share after blank cost, Stripe, the 7% tax we absorb and one-box postage. Nothing here goes under ' + f + '%.</p>' +
   '<div class="og-tbl"><table><thead><tr><th>Tier</th><th>Shirts</th><th>Tee</th><th>Keeps</th><th>Crewneck</th><th>Hoodie</th><th>Best for</th></tr></thead><tbody>' +
   t.map(function(x){ return '<tr' + (x.floor_ok?'':' class="bad"') + '><td><b>' + e(x.name) + '</b></td><td>' + e(x.label) + '</td><td><b>$' + x.items.tee.price.toFixed(2) + '</b>' + (x.payout? '<br><small>$' + x.payout.toFixed(2) + ' back</small>':'') + '</td><td>' + x.items.tee.margin + '%<br><small>' + mo(x.items.tee.keep) + '</small></td><td>$' + x.items.crew.price.toFixed(2) + '<br><small>' + x.items.crew.margin + '%</small></td><td>$' + x.items.hood.price.toFixed(2) + '<br><small>' + x.items.hood.margin + '%</small></td><td class="og-mute">' + e(x.best) + '</td></tr>'; }).join('') +
   '</tbody></table></div>' +
   '<h2 class="og-h">The rules we do not break</h2><ul class="og-rules"><li>Every group ships in <b>ONE box to the leader</b>. A single label is $7.46 and eats a $25 shirt.</li><li>Free design is the hook, never the shirt price.</li><li>3 designs within 24 hours of a yes.</li><li>No licensed characters, no team logos, no mascots.</li><li>Nothing promised after <b>Dec 12</b> for Christmas.</li><li>Real product photos on every mockup.</li></ul>';
}
function vAll(){
  var all = (S.d&&S.d.all)||[];
  return '<h2 class="og-h">Every lead <span class="og-n">' + all.length + '</span></h2><div class="og-tbl"><table><thead><tr><th>Group</th><th>City</th><th>Phone</th><th>Stage</th><th>Next</th><th>Calls</th></tr></thead><tbody>' +
   all.map(function(L){ return '<tr' + (L.archived?' class="og-arch"':'') + '><td><b>' + e(L.org) + '</b></td><td>' + e(L.city||'') + '</td><td>' + (L.phone? '<a href="tel:' + e(tel(L.phone)) + '">' + e(L.phone) + '</a>' : '<span class="og-mute">look up</span>') + '</td><td>' + stageChip(L.stage||'new') + '</td><td>' + e(L.next||'') + '</td><td>' + (L.touches||0) + '</td></tr>'; }).join('') +
   '</tbody></table></div>';
}
var VIEWS = [['calls','☎ Calls'],['pages','🔗 Group pages'],['ladder','💰 Prices + rules'],['all','📋 Every lead']];
function draw(){
  var b = $('ogBody'); if (!b) return;
  if (!S.d) { b.innerHTML = '<div class="og-empty">Loading the campaign… ' + e(S.msg) + '</div>'; return; }
  if (S.d.error) { b.innerHTML = '<div class="og-err">' + e(S.d.error) + '</div>'; return; }
  var h = clock() + score();
  h += '<div class="og-nav">' + VIEWS.map(function(v){ return '<button class="og-v' + (S.view===v[0]?' on':'') + '" onclick="TTOG.view(\'' + v[0] + '\')">' + v[1] + (v[0]==='calls'&&badgeCount()?' <span class="og-n">'+badgeCount()+'</span>':'') + '</button>'; }).join('') +
   '<button class="og-v" onclick="TTOG.importList(this)">➕ Load the Orlando list</button>' +
   '<button class="og-v" onclick="TTOG.refresh()">↻ Refresh</button>' +
   '<a class="og-v" href="/group-orders/" target="_blank" rel="noopener">🌐 Free-design page</a></div>';
  if (S.msg) h += '<div class="og-msg">' + e(S.msg) + '</div>';
  h += ({calls:vCalls, pages:vPages, ladder:vLadder, all:vAll})[S.view]();
  b.innerHTML = h;
}
var CSS = '<style>' +
'.og-wrap{font:16px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;color:#2A241E;max-width:1100px;margin:0 auto}' +
'.og-wrap h2.og-h{font-size:1.35rem;margin:22px 0 10px;display:flex;align-items:center;gap:9px}' +
'.og-n{background:#E11D48;color:#fff;border-radius:999px;font:800 13px/1 system-ui;padding:4px 9px}' +
'.og-mute{color:#6b5d4a}.og-wrap small{color:#6b5d4a}' +
'.og-clock{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}' +
'.og-cl{flex:1 1 150px;background:#FBF6EE;border:2px solid #efe4d0;border-radius:12px;padding:10px 12px}' +
'.og-cl b{display:block;font-size:1.4rem;line-height:1.1}.og-cl span{display:block;font-size:13px;font-weight:700;color:#5b4d3a}.og-cl em{font-style:normal;font-size:12px;color:#8b7d66}' +
'.og-cl.hot{border-color:#E11D48;background:#fff1f3}.og-cl.hot b{color:#E11D48}.og-cl.gone{opacity:.5}' +
'.og-score{display:grid;grid-template-columns:1.3fr 1fr;gap:12px;align-items:start}' +
'.og-goal{background:#14110D;color:#F3D27A;border-radius:14px;padding:14px 16px}' +
'.og-big{font-size:1.5rem;font-weight:800;line-height:1.2}' +
'.og-goal .og-mute{color:#c9b793}' +
'.og-bar{background:#2b2520;border-radius:999px;height:14px;overflow:hidden;margin:9px 0}.og-bar i{display:block;height:100%;background:linear-gradient(90deg,#C9A24E,#F3D27A)}' +
'.og-card .og-bar{background:#efe4d0}' +
'.og-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:8px}' +
'.og-t{background:#fff;border:2px solid #efe4d0;border-radius:12px;padding:9px;text-align:center}.og-t b{display:block;font-size:1.5rem;font-variant-numeric:tabular-nums}.og-t span{font-size:12px;font-weight:700;color:#6b5d4a}' +
'.og-nav{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0 4px}' +
'.og-v{appearance:none;border:2px solid #C9A24E;background:transparent;color:#2A241E;font:800 15px/1 system-ui;padding:11px 16px;border-radius:999px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px}' +
'.og-v.on{background:#C9A24E;color:#14110D}' +
'.og-msg{background:#e3f3ea;border:2px solid #2E7D5E;color:#1c5340;border-radius:12px;padding:11px 14px;margin:10px 0;font-weight:700}' +
'.og-err{background:#fff1f3;border:2px solid #E11D48;color:#9f1239;border-radius:12px;padding:12px 14px;font-weight:700}' +
'.og-empty{background:#FBF6EE;border:2px dashed #d9c9a8;border-radius:14px;padding:18px;color:#5b4d3a}' +
'.og-card{background:#fff;border:2px solid #e7dcc8;border-radius:14px;padding:13px 15px;margin-bottom:10px}' +
'.og-card.op{border-color:#C9A24E;box-shadow:0 0 0 3px rgba(201,162,78,.18)}' +
'.og-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}' +
'.og-org{font-size:1.2rem;line-height:1.25;display:block}' +
'.og-sub{font-size:14px;color:#6b5d4a;margin-top:2px}' +
'.og-last{background:#FBF6EE;border-left:4px solid #C9A24E;border-radius:8px;padding:7px 10px;margin:9px 0;font-size:14px}' +
'.og-st{font-style:normal;font-size:12px;font-weight:800;border-radius:999px;padding:4px 10px;white-space:nowrap;background:#f1ece2;color:#5b4d3a}' +
'.og-st.s-interested,.og-st.s-proof{background:#fef3c7;color:#92400e}.og-st.s-won{background:#e3f3ea;color:#1c5340}.og-st.s-lost{background:#f3f4f6;color:#6b7280}.og-st.s-quote{background:#e0e7ff;color:#3730a3}' +
'.og-acts{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}' +
'.og-b{appearance:none;border:2px solid #2A241E;background:#fff;color:#2A241E;font:800 15px/1 system-ui;padding:12px 15px;border-radius:12px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}' +
'.og-b.call{background:#1F8A4C;border-color:#1F8A4C;color:#fff;font-size:1.05rem}' +
'.og-b.gold{background:#C9A24E;border-color:#C9A24E;color:#14110D}' +
'.og-b.sm{font-size:13px;padding:7px 11px;border-width:1px}' +
'.og-b.warn{background:#fff1f3;border-color:#E11D48;color:#9f1239}' +
'.og-panel{margin-top:12px;border-top:2px dashed #e7dcc8;padding-top:12px}' +
'.og-panel b{display:block;margin-bottom:7px;font-size:15px}' +
'.og-outs{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:7px;margin-bottom:9px}' +
'.og-o{appearance:none;border:2px solid #d9c9a8;background:#FBF6EE;font:800 14px/1.2 system-ui;padding:12px 8px;border-radius:11px;cursor:pointer;color:#2A241E}' +
'.og-o.o-interested,.og-o.o-proofsent{border-color:#C9A24E;background:#fef3c7}.og-o.o-won{border-color:#1F8A4C;background:#e3f3ea;color:#14532d}.og-o.o-lost{border-color:#d1d5db;background:#f9fafb;color:#6b7280}' +
'.og-panel input,.og-panel select{font:inherit;font-size:15px;border:2px solid #e0d3c0;border-radius:10px;padding:10px 11px;width:100%;box-sizing:border-box}' +
'.og-row2{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end}.og-row2 label{display:flex;flex-direction:column;gap:4px;font-size:13px;font-weight:700;color:#5b4d3a}' +
'.og-mk{margin-top:14px}.og-live{margin-top:9px;font-size:14px;background:#e0e7ff;border-radius:10px;padding:8px 11px}' +
'.og-sc{margin-top:14px}' +
'.og-s{border:2px solid #e7dcc8;border-radius:12px;padding:10px 12px;margin-bottom:8px;background:#fff}' +
'.og-s.pick{border-color:#C9A24E;background:#FFFCF4}.og-s i{color:#9a7b2a;font-style:normal;font-weight:800;font-size:13px}' +
'.og-s pre{white-space:pre-wrap;font:15px/1.55 system-ui;margin:7px 0 0;color:#000;background:#FBF6EE;border-radius:9px;padding:10px 12px}' +
'.og-row{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}' +
'.og-tbl{overflow-x:auto;background:#fff;border:2px solid #e7dcc8;border-radius:14px}' +
'.og-tbl table{width:100%;border-collapse:collapse;font-size:15px}' +
'.og-tbl th,.og-tbl td{text-align:left;padding:10px 12px;border-bottom:1px solid #f1ece2;vertical-align:top}' +
'.og-tbl th{font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#6b5d4a;background:#FBF6EE}' +
'.og-tbl tr.bad td{background:#fff1f3}.og-arch{opacity:.45}' +
'.og-rules{font-size:16px;line-height:1.7;padding-left:22px}' +
'@media(max-width:820px){.og-score{grid-template-columns:1fr}.og-row2{grid-template-columns:1fr}.og-org{font-size:1.1rem}}' +
'</style>';
window.TTOG = {
  has: function(t){ return !!TABS[t]; },
  html: function(){ setTimeout(function(){ if (!S.d) load(); else draw(); }, 0); return CSS + '<div class="og-wrap"><div id="ogBody"><div class="og-empty">Loading the campaign…</div></div></div>'; },
  bind: function(){ draw(); },
  view: function(v){ S.view = v; S.msg = ''; draw(); window.scrollTo(0,0); },
  refresh: function(){ S.msg = ''; S.d = null; draw(); load(); },
  open: function(id){
    S.open = (S.open === id ? '' : id); S.msg = ''; draw();
    if (S.open && !S.scripts[id]) get('?a=scripts&id=' + encodeURIComponent(id)).then(function(d){ if (d.scripts){ S.scripts[id] = d.scripts; draw(); } });
  },
  copyText: function(btn, t){ copy(t, btn); },
  copyIdx: function(btn, id, i){ var s = (S.scripts[id]||[])[i]; if (!s) return; copy((s.subject ? 'Subject: ' + s.subject + '\n\n' : '') + s.body, btn); },
  touch: function(id, outcome){
    if (S.busy) return; S.busy = true;
    var el = $('og-note-' + id), note = el ? el.value.trim() : '';
    post({a:'touch', id:id, outcome:outcome, note:note}).then(function(r){
      S.busy = false;
      if (r.error){ S.msg = r.error; draw(); return; }
      S.msg = 'Logged. ' + (r.next ? 'Next follow-up ' + r.next + '.' : 'Closed out.');
      S.open = ''; S.d = null; draw(); load();
    });
  },
  page: function(id){
    if (S.busy) return;
    var t = $('og-tier-' + id), d = $('og-ends-' + id);
    S.busy = true; S.msg = 'Building the page…'; draw();
    post({a:'campaign', id:id, tier:t?t.value:'', ends:d?d.value:''}).then(function(r){
      S.busy = false;
      S.msg = r.error ? r.error : (r.msg + ' ' + r.url);
      S.d = null; draw(); load();
    });
  },
  importList: function(btn){
    if (S.busy) return; S.busy = true;
    var o = btn.textContent; btn.textContent = 'Loading…';
    post({a:'import', rows: CHURCHES.concat(EXTRA)}).then(function(r){
      S.busy = false; btn.textContent = o;
      S.msg = r.error ? r.error : ('Added ' + r.added + ' leads' + (r.skipped ? ', skipped ' + r.skipped + ' already there' : '') + '.');
      S.d = null; draw(); load();
    });
  }
};
/* put the tab in the Command Center nav, first thing he sees */
(function(){
  function add(){
    var nav = document.getElementById('tabs');
    if (!nav || !nav.querySelector('button[data-t]')) return setTimeout(add, 250);
    if (nav.querySelector('button[data-t="orl"]')) return;
    var b = document.createElement('button');
    b.type = 'button'; b.dataset.t = 'orl'; b.setAttribute('aria-pressed','false');
    b.innerHTML = '🏛 Orlando Group Orders <span class="n" id="n-orl" hidden></span>';
    nav.insertBefore(b, nav.firstChild);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
})();
})();
F52CB0B17;
$NEW['group-orders/index.html'] = <<<'F936C485F'
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Free Custom Group Shirts, Orlando | Urban Canvas Co.</title>
<meta name="description" content="Church, reunion, team or tailgate shirts for your Orlando group. We design 3 options free in 24 hours, each member pays for their own shirt, and we press and deliver here in Central Florida.">
<meta name="theme-color" content="#14110D">
<link rel="canonical" href="https://urbancanvasapparelco.com/group-orders/">
<meta property="og:type" content="website"><meta property="og:site_name" content="Urban Canvas Co.">
<meta property="og:title" content="Free Custom Group Shirts, Orlando">
<meta property="og:description" content="3 designs free in 24 hours. No upfront cost. Each member pays their own. Pressed by hand in Orlando.">
<meta property="og:url" content="https://urbancanvasapparelco.com/group-orders/">
<meta property="og:image" content="https://urbancanvasapparelco.com/go/img/tt05.jpg">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<style>
:root{--ink:#14110D;--ink2:#1d1813;--gold:#C9A24E;--gold2:#F3D27A;--cream:#F5EBDA;--mute:#b9a888;--line:#3a3022;--green:#1F8A4C}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--ink);color:var(--cream);font:17px/1.6 system-ui,-apple-system,"Segoe UI",Arial,sans-serif;-webkit-text-size-adjust:100%}
.w{max-width:1060px;margin:0 auto;padding:0 18px}
a{color:var(--gold2)}
h1,h2,h3{line-height:1.15;margin:0 0 12px;font-weight:800}
h1{font-size:clamp(2rem,6.4vw,3.5rem);letter-spacing:-.02em}
h2{font-size:clamp(1.5rem,4.2vw,2.3rem)}
h3{font-size:1.18rem}
p{margin:0 0 14px}
.band{border-top:1px solid var(--line)}
.band.alt{background:var(--ink2)}
.band>.w{padding-top:44px;padding-bottom:44px}
.eyebrow{display:inline-block;font:800 13px/1 system-ui;letter-spacing:.14em;text-transform:uppercase;color:var(--gold);border:1px solid var(--gold);border-radius:999px;padding:8px 14px;margin-bottom:16px}
.lead{font-size:clamp(1.05rem,2.6vw,1.3rem);color:#e6d9c0;max-width:40ch}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--gold);color:var(--ink);font:800 18px/1 system-ui;padding:18px 26px;border:0;border-radius:14px;text-decoration:none;cursor:pointer;box-shadow:0 6px 24px rgba(201,162,78,.3)}
.btn:hover{background:var(--gold2)}
.btn.ghost{background:transparent;color:var(--gold2);border:2px solid var(--gold);box-shadow:none}
.hero{display:grid;grid-template-columns:1.15fr .85fr;gap:30px;align-items:center}
.hero .cta{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
.hero .micro{font-size:14px;color:var(--mute);margin-top:14px}
.clock{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:26px}
.cl{background:#0d0a07;border:1px solid var(--line);border-radius:12px;padding:12px}
.cl b{display:block;font-size:1.5rem;color:var(--gold2);line-height:1.1}
.cl span{font-size:12.5px;color:var(--mute);font-weight:700}
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.step{background:#0d0a07;border:1px solid var(--line);border-left:4px solid var(--gold);border-radius:14px;padding:20px}
.step b.num{display:block;font-size:2.3rem;color:var(--gold);line-height:1;margin-bottom:6px}
table{width:100%;border-collapse:collapse;font-size:16px;background:#0d0a07;border-radius:14px;overflow:hidden}
th,td{padding:14px 12px;text-align:left;border-bottom:1px solid var(--line)}
th{font:800 13px/1.2 system-ui;text-transform:uppercase;letter-spacing:.06em;color:var(--gold);background:#17130e}
td b{color:var(--gold2);font-size:1.15rem}
tr.hi td{background:#1a1510}
.tw{overflow-x:auto;border-radius:14px}
.note{font-size:14.5px;color:var(--mute)}
.seals{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
.seal{display:inline-flex;align-items:center;gap:7px;background:#0d0a07;border:1px solid var(--line);border-radius:999px;padding:9px 15px;font:700 14px system-ui;color:#e6d9c0}
form{background:#0d0a07;border:2px solid var(--gold);border-radius:18px;padding:22px}
.f2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:flex;flex-direction:column;gap:5px;font:700 13.5px system-ui;color:var(--mute);margin-bottom:12px}
input,select,textarea{font:inherit;font-size:16px;background:#17130e;color:var(--cream);border:1px solid var(--line);border-radius:11px;padding:13px 13px;width:100%}
input:focus,select:focus,textarea:focus{outline:2px solid var(--gold);border-color:var(--gold)}
.wide{grid-column:1/-1}
.ok{background:#0f2c1d;border:2px solid var(--green);color:#b7f0cf;border-radius:14px;padding:18px;font-weight:700}
.bad{background:#2c0f14;border:2px solid #E11D48;color:#ffc9d3;border-radius:12px;padding:12px;font-weight:700;margin-bottom:12px}
.faq{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.faq div{background:#0d0a07;border:1px solid var(--line);border-radius:14px;padding:18px}
footer{border-top:1px solid var(--line);font-size:14px;color:var(--mute)}
footer .w{padding:26px 18px}
.sticky{position:fixed;left:0;right:0;bottom:0;background:rgba(20,17,13,.96);border-top:1px solid var(--gold);padding:11px 16px;display:none;z-index:20;backdrop-filter:blur(6px)}
.sticky a{width:100%}
@media(max-width:860px){.hero{grid-template-columns:1fr}.steps{grid-template-columns:1fr}.faq{grid-template-columns:1fr}.f2{grid-template-columns:1fr}.clock{grid-template-columns:1fr}.sticky{display:block}body{padding-bottom:78px}}
</style></head><body>

<section class="band"><div class="w">
  <div class="hero">
    <div>
      <span class="eyebrow">Orlando &amp; Central Florida</span>
      <h1>Your group's shirts.<br>Designed <span style="color:var(--gold2)">free</span>.</h1>
      <p class="lead">Church, family reunion, team, tailgate or staff. We send you <b>3 custom designs within 24 hours</b>. You pick one. Then every member orders and pays for their own shirt on one link, so you never collect money or guess sizes.</p>
      <div class="cta">
        <a class="btn" href="#ask">Get my 3 free designs</a>
        <a class="btn ghost" href="#prices">See group prices</a>
      </div>
      <p class="micro">No upfront cost. No deposit. If you don't love the designs, you owe nothing.</p>
    </div>
    <div>
      <div class="step" style="border-left-color:var(--gold2)">
        <h3 style="margin-bottom:10px">Why groups call us</h3>
        <p style="margin:0 0 10px"><b style="color:var(--gold2)">Free design.</b> Other shops charge $50 to $150 or hand you clip art.</p>
        <p style="margin:0 0 10px"><b style="color:var(--gold2)">No money to collect.</b> Each person checks out for themselves.</p>
        <p style="margin:0 0 10px"><b style="color:var(--gold2)">Pressed by hand in Orlando.</b> Not shipped in from overseas.</p>
        <p style="margin:0"><b style="color:var(--gold2)">One box to the leader.</b> Everything arrives together, before your event.</p>
      </div>
      <div class="clock" id="clock"></div>
    </div>
  </div>
</div></section>

<section class="band alt"><div class="w">
  <h2>Three steps, and you're done</h2>
  <div class="steps">
    <div class="step"><b class="num">1</b><h3>Tell us the group</h3><p>Your group's name, roughly how many people, and the date you need them. One minute on the form below.</p></div>
    <div class="step"><b class="num">2</b><h3>We design it free</h3><p>Three original designs in your hands within 24 hours. Change anything you want. You own the look.</p></div>
    <div class="step"><b class="num">3</b><h3>Everyone orders their own</h3><p>We send you one link. Each member picks a size and pays for their shirt. We press them and ship the whole group in one box to you.</p></div>
  </div>
  <div class="seals">
    <span class="seal">&#9989; No upfront cost</span>
    <span class="seal">&#9989; Designs in 24 hours</span>
    <span class="seal">&#9989; Sizes S to 5XL</span>
    <span class="seal">&#9989; Hand-pressed in Orlando</span>
    <span class="seal">&#9989; One box to your leader</span>
    <span class="seal">&#9989; Love it or owe nothing</span>
  </div>
</div></section>

<section class="band" id="prices"><div class="w">
  <h2>Group prices</h2>
  <p class="note">Price per shirt, the more the group orders. Design is free at every level. Hoodies, crewnecks, totes, hats and mugs follow the same ladder.</p>
  <div class="tw"><table>
    <thead><tr><th>Group size</th><th>Price per shirt</th><th>Best for</th></tr></thead>
    <tbody>
      <tr><td>12 to 23 shirts</td><td><b>$28.00</b></td><td>Small reunions, youth groups</td></tr>
      <tr class="hi"><td>24 to 49 shirts</td><td><b>$25.00</b></td><td>Most churches and reunions</td></tr>
      <tr><td>50 to 99 shirts</td><td><b>$22.50</b></td><td>Church-wide events</td></tr>
      <tr><td>100 or more</td><td><b>$21.00</b></td><td>Tailgates, conferences, schools</td></tr>
      <tr><td>Fundraiser</td><td><b>$30.00</b><br><span class="note">$5 back to your group on every shirt</span></td><td>Churches, schools, teams raising money</td></tr>
    </tbody>
  </table></div>
  <p class="note" style="margin-top:14px">2XL and up add a little, same as any shop. Christmas orders close <b style="color:var(--gold2)">December 12</b>. Florida Classic shirts close <b style="color:var(--gold2)">November 10</b> so they're pressed in time for game day.</p>
</div></section>

<section class="band alt" id="ask"><div class="w">
  <h2>Get your 3 free designs</h2>
  <p class="lead" style="max-width:52ch">Fill this in and Dr. Graham sends three designs for your group within 24 hours. No cost, no deposit, no obligation.</p>
  <form id="f" onsubmit="return send(event)">
    <div id="err" class="bad" hidden></div>
    <div class="f2">
      <label>Your name<input name="contact" required autocomplete="name" placeholder="Pastor James Hall"></label>
      <label>Group, church, family or team<input name="org" required placeholder="New Bethel AME"></label>
      <label>Phone<input name="phone" type="tel" autocomplete="tel" placeholder="407-555-0148"></label>
      <label>Email<input name="email" type="email" autocomplete="email" placeholder="you@email.com"></label>
      <label>What kind of group?<select name="seg">
        <option value="church">Church or ministry</option>
        <option value="reunion">Family reunion</option>
        <option value="tailgate">Tailgate or alumni crew</option>
        <option value="youth">Youth, school or sports team</option>
        <option value="business">Business or nonprofit</option>
      </select></label>
      <label>About how many people?<input name="qty" type="number" min="1" max="2000" placeholder="30"></label>
      <label>What is the shirt for?<input name="event" placeholder="Church anniversary, reunion, Christmas"></label>
      <label>When do you need them?<input name="evdate" type="date"></label>
      <label class="wide">Anything you want on the shirt? (optional)<textarea name="notes" rows="3" placeholder="Church name in gold, scripture on the back, our colors are royal blue and white"></textarea></label>
      <label class="wide">City<input name="city" placeholder="Orlando"></label>
    </div>
    <button class="btn" type="submit" id="sb" style="width:100%">Send me 3 free designs</button>
    <p class="note" style="margin:12px 0 0">We use your number only to send the designs. No spam, no list.</p>
  </form>
  <div id="done" hidden class="ok"></div>
</div></section>

<section class="band"><div class="w">
  <h2>Questions groups ask</h2>
  <div class="faq">
    <div><h3>Do I have to pay anything up front?</h3><p>No. The design is free and each member pays for their own shirt at checkout. You never front money or collect it.</p></div>
    <div><h3>What if we don't like the designs?</h3><p>Then you owe nothing and we part as friends. Most groups pick one of the three or ask for one change.</p></div>
    <div><h3>How do sizes work?</h3><p>Each person picks their own size when they order, S through 5XL. No size sheet, no guessing, no leftovers.</p></div>
    <div><h3>How fast can we get them?</h3><p>Designs in 24 hours. Once your order closes, most groups have shirts in hand inside a week. Tell us your date and we work backward from it.</p></div>
    <div><h3>Can you ship to each person instead?</h3><p>We can, but one box to the group leader keeps the price where it is. Shipping each shirt separately costs more than the discount.</p></div>
    <div><h3>Can our group raise money with this?</h3><p>Yes. On the fundraiser option your group keeps $5 from every shirt sold and still puts up nothing. That is Grahams Global Fundraising Group: we only succeed when you do.</p></div>
  </div>
</div></section>

<footer><div class="w">
  <b style="color:var(--gold2)">Urban Canvas Co.</b> &middot; Custom apparel, hand-pressed in Central Florida &middot; Dr. Robert D. Graham<br>
  <a href="tel:3529313768">352-931-3768</a> &middot; <a href="mailto:support@threadedtruthsapparelco.com">support@threadedtruthsapparelco.com</a> &middot; <a href="https://urbancanvasapparelco.com/">Shop the store</a><br>
  <span style="opacity:.75">Excellence Is A Habit &middot; Built By Faith</span>
</div></footer>

<div class="sticky"><a class="btn" href="#ask">Get my 3 free designs</a></div>

<script>
(function(){
  var D = [['2026-11-10','Florida Classic shirts close'],['2026-11-21','Florida Classic game day'],['2026-12-12','Christmas orders close']];
  var now = new Date(); now.setHours(0,0,0,0);
  document.getElementById('clock').innerHTML = D.map(function(x){
    var d = Math.round((new Date(x[0]+'T00:00:00') - now)/864e5);
    if (d < 0) return '';
    return '<div class="cl"><b>' + d + (d===1?' day':' days') + '</b><span>' + x[1] + '</span></div>';
  }).join('');
})();
function send(ev){
  ev.preventDefault();
  var f = document.getElementById('f'), b = document.getElementById('sb'), er = document.getElementById('err');
  var o = {}; [].forEach.call(f.elements, function(el){ if (el.name) o[el.name] = el.value.trim(); });
  if (!o.phone && !o.email){ er.hidden = false; er.textContent = 'Please add a phone number or an email so we can send your designs.'; return false; }
  er.hidden = true; b.disabled = true; b.textContent = 'Sending…';
  fetch('/api/orlando.php?a=request', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(o)})
   .then(function(r){ return r.json(); })
   .then(function(r){
     b.disabled = false; b.textContent = 'Send me 3 free designs';
     if (r.error){ er.hidden = false; er.textContent = r.error; return; }
     f.hidden = true;
     var d = document.getElementById('done'); d.hidden = false;
     d.innerHTML = '✅ ' + (r.msg || 'Got it.') + '<br><br>Watch your phone and your email. If you want to talk it through right now, call Dr. Graham at <a href="tel:3529313768">352-931-3768</a>.';
     d.scrollIntoView({behavior:'smooth', block:'center'});
   })
   .catch(function(){ b.disabled = false; b.textContent = 'Send me 3 free designs'; er.hidden = false; er.textContent = 'Could not send just now. Please call or text 352-931-3768.'; });
  return false;
}
</script>
</body></html>
F936C485F;

$P = json_decode('[["desk/index.html", "|| function(){ return window.TTGROW && TTGROW.has(TAB) ? TTGROW.html(TAB) :", "|| function(){ return window.TTOG && TTOG.has(TAB) ? TTOG.html(TAB) : window.TTGROW && TTGROW.has(TAB) ? TTGROW.html(TAB) :", "render: route the Orlando tab"], ["desk/index.html", "  if (window.TTAMB && TTAMB.has(TAB)) TTAMB.bind(TAB);", "  if (window.TTAMB && TTAMB.has(TAB)) TTAMB.bind(TAB);\\n  if (window.TTOG && TTOG.has(TAB)) TTOG.bind(TAB);", "render: bind the Orlando tab"], ["desk/index.html", "&& !(window.TTAMB && TTAMB.has(TAB))", "&& !(window.TTAMB && TTAMB.has(TAB)) && !(window.TTOG && TTOG.has(TAB))", "stop the 60s refresh wiping the Orlando tab"], ["desk/index.html", "<script src=\\"crm.js?v=3\\"></script>", "<script src=\\"crm.js?v=3\\"></script>\\n<script src=\\"orlando.js?v=1\\"></script>", "load desk/orlando.js"], ["desk/navgroups.js", "[\'start\',\'dash\',\'cal\',\'inbox\',\'play\',\'tools\']", "[\'orl\',\'start\',\'dash\',\'cal\',\'inbox\',\'play\',\'tools\']", "put Orlando first in the Home menu"]]', true);
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
foreach (['api/orlando.php'] as $pf) { $o = []; $rc = 0; @exec('php -l ' . escapeshellarg($root . '/' . $pf) . ' 2>&1', $o, $rc); $t = trim(implode(' ', $o)); if ($rc !== 0 && $t !== '') { $err = true; $lint[] = "ERR $pf: $t"; } else $lint[] = "ok  $pf syntax " . ($t ?: '(linter unavailable, skipped)'); }
$out .= implode("\n", $lint) . "\n";
if ($err) {
  foreach ($files as $f => $c) if (is_file($f . $tag)) copy($f . $tag, $f);
  foreach ($wrote as $f) if (is_file($f . $tag)) copy($f . $tag, $f); else @unlink($f);
  $out .= "ROLLED BACK because of a syntax error.\n";
} else { @unlink(__FILE__); $out .= "\nDONE. Open the Command Center and the Orlando Group Orders tab is first in the Home menu.\n"; }
echo $out;
