<?php
/* THE OUTREACH MACHINE installer (Oct 31 2026).
   One engine for every business: GPTS, Orlando group orders, GGFG fundraising, AI Web Solutions.
   Packs are stored as editable data, so he can change any of it from the desk. Open once with ?go=e1c6ef61ccf06547 */
if (($_GET['go'] ?? '') !== 'e1c6ef61ccf06547') { http_response_code(403); exit('Not allowed.'); }
header('Content-Type: text/plain; charset=utf-8');
$root = preg_match('~/_inst~', __DIR__) ? dirname(__DIR__) : __DIR__;
$tag = '.bak-20261031-machine'; $out = ''; $bad = 0;
if (!is_file($root . '/api/svc_lib.php')) exit("Wrong folder: api/svc_lib.php not found.\n");
$NEW = [];
$NEW['api/machine.php'] = <<<'FB720FA26'
<?php
// 3G's Outreach Machine (Oct 31 2026). One engine, every business.
// Same call list, scripts, one-tap outcomes and scoreboard, re-aimed by which pack you pick.
// Desk:   GET  ?a=board&b=<pack>|packs|scripts&id=..|tiers      POST {a:import|save|touch|next|campaign|archive|goal}
// Public: POST ?a=request&b=<pack>   (the free-offer form on each landing page)
// Data:   tt_data/leads.json (shared with the Leads tab), tt_data/machine.json, tt_data/campaigns/<id>.json
require __DIR__ . '/svc_lib.php';
require_once __DIR__ . '/money_lib.php';

const MK_STAGES = ['new', 'contacted', 'interested', 'proof', 'quote', 'won', 'lost'];
const MK_MIN_MARGIN = 35;
const MK_BULK_POST = 0.75;
const MK_TARGET_PCT = 42;
const MK_PHONE = '352-931-3768';

/* ---------------- the packs ---------------- */
function mk_defaults() {
  $P = [];

  $P['gpts'] = [
    'id' => 'gpts', 'name' => 'GPTS Airport Transfers', 'short' => 'GPTS',
    'brand' => 'Grahams Pristine Transportation Service', 'phone' => MK_PHONE,
    'email' => 'bookings@pristinedrive.online', 'site' => 'https://transportationservices.online/',
    'land' => '/airport-transfers/', 'kind' => 'service',
    'hook' => 'A flat rate your guests can count on, held through the holidays. No app, no surge, no waiting.',
    'goal' => ['n' => 10, 'label' => 'accounts booked', 'units' => 40, 'unit_label' => 'rides'],
    'segs' => ['hotel' => 'Hotel / vacation-home manager', 'planner' => 'Wedding / event planner', 'corp' => 'Corporate office', 'senior' => 'Medical / senior living', 'travel' => 'Travel agent / concierge'],
    'stage_names' => ['new' => 'Not called yet', 'contacted' => 'Called, no yes yet', 'interested' => 'Interested', 'proof' => 'Rate card sent', 'quote' => 'Quoted a route', 'won' => 'BOOKED', 'lost' => 'No'],
    'out_names' => ['proofsent' => 'Sent the rate card', 'pagesent' => 'Quoted a route', 'won' => 'BOOKED'],
    'rates' => [['Orlando (MCO) airport transfer', 'from $49.95', 'flat, each way'], ['Tampa (TPA) airport transfer', 'same as Orlando', 'flat, each way'], ['Vacation-home transfer', 'from $49.95', 'Davenport, Kissimmee, Four Corners'], ['Hourly / as-directed', 'quoted', 'events, multi-stop, waiting time']],
    'deadlines' => [['k' => 'tg', 'name' => 'Thanksgiving travel week', 'date' => '2026-11-24'], ['k' => 'xm', 'name' => 'Christmas travel week', 'date' => '2026-12-19'], ['k' => 'ny', 'name' => 'New Year travel week', 'date' => '2026-12-29']],
    'rules' => ['Prices on the GPTS sites are final. Quote from the rate card, never off the top of your head.', 'Davenport to Tampa is priced the same as Davenport to Orlando, each way.', 'Every ad and message carries: Call or Text ' . MK_PHONE . '.', 'Log every lead: date, source, route, ride date, price, paid or not.'],
    'scripts' => [
      ['k' => 'call', 'name' => 'Phone script (30 seconds)', 'kind' => 'say', 'seg' => 'hotel',
       'body' => "Good morning, this is Dr. Graham with Grahams Pristine Transportation here in Davenport. I run airport transfers for Orlando and Tampa, and I'm picking up a few more property accounts before the holidays.\n\nWhen your guests ask how to get from the airport, what do you tell them right now?\n\n... I can hold a flat rate for your guests through the holidays, and they get my direct line instead of an app. May I send you the rate card and a card for your front desk?"],
      ['k' => 'planner', 'name' => 'Phone script for planners', 'kind' => 'say', 'seg' => 'planner',
       'body' => "Hi, this is Dr. Graham with Grahams Pristine Transportation in Davenport. I handle airport and event transfers around Central Florida.\n\nWhen you have an out-of-town couple or family flying into Orlando or Tampa, who are you using for the ground side right now?\n\n... I keep a flat rate, no surge, and the client gets my direct number. Can I send you my rate card to keep on file?"],
      ['k' => 'email', 'name' => 'Email: flat rate for your guests', 'kind' => 'send', 'seg' => '',
       'subject' => 'A flat airport rate for {org} guests',
       'body' => "{who},\n\nI'm Dr. Robert Graham with Grahams Pristine Transportation Service, here in Davenport. I drive airport transfers for Orlando (MCO) and Tampa, and vacation-home transfers all over Central Florida.\n\nI'd like to hold a flat rate for {org} guests through the holidays:\n\n- Orlando or Tampa airport transfer, from $49.95, flat, each way\n- No surge pricing, no app, no waiting on a stranger\n- Clean late-model SUV, professional chauffeur, text-to-book\n- Your guest gets my direct line: " . MK_PHONE . "\n\nIf a guest asks you how to get from the airport, you have one answer and I take it from there.\n\nWould you like the rate card and a few cards for the front desk?\n\nDr. Robert D. Graham\nGrahams Pristine Transportation Service\n" . MK_PHONE . " · bookings@pristinedrive.online\n{land}"],
      ['k' => 'dm', 'name' => 'Short DM / text', 'kind' => 'send', 'seg' => '',
       'body' => "Hi {who} — Dr. Graham with Grahams Pristine Transportation here in Davenport. I run Orlando and Tampa airport transfers, flat rate from $49.95, no surge, no app. I can hold that rate for {org} guests through the holidays. Want me to send the rate card? Call or text " . MK_PHONE . "."],
      ['k' => 'follow', 'name' => 'Follow-up (day 3)', 'kind' => 'send', 'seg' => '',
       'body' => "Just following up, {who}. The flat rate for {org} guests is still open. One text and I'm on it. " . MK_PHONE . "."],
      ['k' => 'quote', 'name' => 'Quote a route', 'kind' => 'send', 'seg' => '',
       'body' => "{who}, here's the quote:\n\nPickup: [address]\nDrop-off: [airport or address]\nDate and time: [date, pickup time]\nFlat rate: $[amount], each way. No surge, no extra for traffic.\n\nI track the flight, so a delay costs you nothing. Reply yes and it's on my calendar.\n\nDr. Graham · " . MK_PHONE],
      ['k' => 'thanks', 'name' => 'After the ride: thank-you + referral', 'kind' => 'send', 'seg' => '',
       'body' => "Thank you, {who}. It was a pleasure driving for {org}.\n\nIf a guest or a colleague needs Orlando or Tampa, send them straight to me and I'll take care of them the same way. Same flat rate, same number: " . MK_PHONE . "."],
    ],
    'seed' => [
      ['org' => 'Vacation-home property managers — Davenport / Four Corners', 'seg' => 'hotel', 'city' => 'Davenport', 'source' => 'Local property managers', 'qty' => 0],
      ['org' => 'Vacation-home property managers — Kissimmee / Celebration', 'seg' => 'hotel', 'city' => 'Kissimmee', 'source' => 'Local property managers', 'qty' => 0],
      ['org' => 'Hotels near Disney — 192 corridor', 'seg' => 'hotel', 'city' => 'Kissimmee', 'source' => 'Hotel front desks', 'qty' => 0],
      ['org' => 'Hotels — Lake Buena Vista', 'seg' => 'hotel', 'city' => 'Lake Buena Vista', 'source' => 'Hotel front desks', 'qty' => 0],
      ['org' => 'Wedding planners — Orlando', 'seg' => 'planner', 'city' => 'Orlando', 'source' => 'Wedding planners', 'qty' => 0],
      ['org' => 'Wedding venues — Winter Park / Celebration', 'seg' => 'planner', 'city' => 'Winter Park', 'source' => 'Venues', 'qty' => 0],
      ['org' => 'Corporate offices — Lake Nona / Medical City', 'seg' => 'corp', 'city' => 'Orlando', 'source' => 'Corporate', 'qty' => 0],
      ['org' => 'Corporate offices — downtown Orlando', 'seg' => 'corp', 'city' => 'Orlando', 'source' => 'Corporate', 'qty' => 0],
      ['org' => 'Senior living communities — Polk County', 'seg' => 'senior', 'city' => 'Davenport', 'source' => 'Senior living', 'qty' => 0],
      ['org' => 'Travel agents — Central Florida', 'seg' => 'travel', 'city' => 'Orlando', 'source' => 'Travel agents', 'qty' => 0],
      ['org' => 'Concierge desks — Reunion / ChampionsGate resorts', 'seg' => 'travel', 'city' => 'ChampionsGate', 'source' => 'Resort concierge', 'qty' => 0],
    ],
  ];

  $P['orlando'] = [
    'id' => 'orlando', 'name' => 'Orlando Group Orders', 'short' => 'Group shirts',
    'brand' => 'Urban Canvas Co.', 'phone' => MK_PHONE,
    'email' => 'support@threadedtruthsapparelco.com', 'site' => 'https://urbancanvasapparelco.com/',
    'land' => '/group-orders/', 'kind' => 'apparel',
    'hook' => 'Three custom designs free in 24 hours. Each member pays for their own shirt. One box to the leader.',
    'goal' => ['n' => 3, 'label' => 'paid group orders', 'units' => 72, 'unit_label' => 'shirts'],
    'segs' => ['church' => 'Church', 'reunion' => 'Family reunion', 'tailgate' => 'Tailgate / alumni', 'youth' => 'Youth / school team', 'business' => 'Business / nonprofit'],
    'stage_names' => ['new' => 'Not called yet', 'contacted' => 'Called, no yes yet', 'interested' => 'Interested', 'proof' => 'Designs sent', 'quote' => 'Page is live', 'won' => 'PAID', 'lost' => 'No'],
    'out_names' => ['proofsent' => 'Sent 3 designs', 'pagesent' => 'Group page sent', 'won' => 'PAID'],
    'rates' => [['12 to 23 shirts', '$28.00', 'small reunions, youth groups'], ['24 to 49 shirts', '$25.00', 'most churches and reunions'], ['50 to 99 shirts', '$22.50', 'church-wide events'], ['100 or more', '$21.00', 'tailgates, conferences'], ['Fundraiser', '$30.00, $5 back', 'churches, schools, GGFG']],
    'deadlines' => [['k' => 'cc', 'name' => 'Florida Classic order cutoff', 'date' => '2026-11-10'], ['k' => 'cd', 'name' => 'Florida Classic game day', 'date' => '2026-11-21'], ['k' => 'g', 'name' => '3 paid group orders', 'date' => '2026-11-30'], ['k' => 'x', 'name' => 'Christmas order cutoff', 'date' => '2026-12-12']],
    'rules' => ['Every group ships in ONE box to the leader. A single label is $7.46 and eats a $25 shirt.', '35% margin floor. The tool refuses to build a page below it.', 'Original designs only: no licensed characters, no team logos, no mascots.', 'Real product photos on every mockup, never generated ones.', 'Nothing promised after Dec 12 for Christmas.'],
    'scripts' => [
      ['k' => 'call', 'name' => 'Phone script (30 seconds)', 'kind' => 'say', 'seg' => 'church',
       'body' => "Good morning, this is Dr. Graham with Urban Canvas Co. in Orlando. We design custom shirts for churches free of charge, and each member pays for their own. Who handles events or the youth ministry there at {org}?\n\n... Great. May I send them three design ideas for {org} this week? There is no cost to look."],
      ['k' => 'email', 'name' => 'Email (top pick: no upfront cost)', 'kind' => 'send', 'seg' => 'church',
       'subject' => 'Matching shirts for {org}, designed free',
       'body' => "Pastor {contact},\n\nI'm Dr. Robert Graham with Urban Canvas Co. here in the Orlando area. We make custom shirts for churches, and we design them for you at no charge.\n\nHere's how it works: we send you 3 design ideas within 24 hours. You pick one. Each member orders and pays for their own shirt on a simple page, so you never collect money or guess sizes. Groups of 24 or more start at $25 a shirt.\n\nIs there an event coming up, like a homecoming, youth day, or Christmas service, where matching shirts would help?\n\nIf yes, reply with the event date and I'll have your designs to you tomorrow.\n\nIn service,\nDr. Robert D. Graham\nUrban Canvas Co.\n{land}"],
      ['k' => 'reunion', 'name' => 'Reunion DM', 'kind' => 'send', 'seg' => 'reunion',
       'body' => "Hi {who}! I saw you're organizing the {org} reunion. I make custom reunion shirts here in Orlando. I'll design 3 options for free (family name, year, location, whatever you want on it), and everybody orders their own size so you don't have to collect money. Want me to send the designs?"],
      ['k' => 'tailgate', 'name' => 'Tailgate / alumni DM', 'kind' => 'send', 'seg' => 'tailgate',
       'body' => "Game day is Nov 21. Want your crew in matching shirts at the tailgate? I'll design 3 options free. Order closes Nov 10 so they're pressed in time. How many people are you bringing?"],
      ['k' => 'follow', 'name' => 'Follow-up (day 3)', 'kind' => 'send', 'seg' => '',
       'body' => "Just checking in, {who}. I have your 3 designs ready. Want me to send them over? No cost and no pressure."],
      ['k' => 'fund', 'name' => 'Fundraiser pitch', 'kind' => 'say', 'seg' => '',
       'body' => "Your group keeps $5 from every shirt sold, and you put up nothing. We only succeed when you do."],
      ['k' => 'page', 'name' => 'Send the group page', 'kind' => 'send', 'seg' => '',
       'body' => "Here it is, {who}. This is the page for {org}: {camp}\n\nShare that one link with everyone. Each person picks their size and pays for their own shirt, so you never have to collect money. I press them here in Orlando and ship the whole group in one box to you.\n\n- Dr. Robert D Graham, Urban Canvas Co."],
      ['k' => 'thanks', 'name' => 'Thank-you + referral ask', 'kind' => 'send', 'seg' => '',
       'body' => "Thank you, {who}. It was an honor to serve {org}. Who else do you know planning a reunion, church event, or team season? I'll take care of them the same way."],
    ],
    'seed' => [],
  ];

  $P['ggfg'] = [
    'id' => 'ggfg', 'name' => 'GGFG Fundraising', 'short' => 'Fundraising',
    'brand' => 'Grahams Global Fundraising Group', 'phone' => MK_PHONE,
    'email' => 'support@threadedtruthsapparelco.com', 'site' => 'https://grahamsglobalfundraisinggroup.com/',
    'land' => '/fundraising/', 'kind' => 'apparel',
    'hook' => 'Your group keeps $5 from every shirt sold and puts up nothing. We only succeed when you do.',
    'goal' => ['n' => 2, 'label' => 'fundraisers running', 'units' => 100, 'unit_label' => 'shirts'],
    'segs' => ['church' => 'Church', 'youth' => 'School / team', 'business' => 'Nonprofit', 'reunion' => 'Community group'],
    'stage_names' => ['new' => 'Not called yet', 'contacted' => 'Called, no yes yet', 'interested' => 'Interested', 'proof' => 'Designs sent', 'quote' => 'Fundraiser page live', 'won' => 'RAISING', 'lost' => 'No'],
    'out_names' => ['proofsent' => 'Sent 3 designs', 'pagesent' => 'Fundraiser page sent', 'won' => 'RUNNING'],
    'rates' => [['Fundraiser shirt', '$30.00', '$5 back to the group on every shirt'], ['Group keeps', '$5 per shirt', 'paid out after the campaign closes'], ['Your cost', '$0', 'no upfront cost, no minimum to start']],
    'deadlines' => [['k' => 'x', 'name' => 'Christmas order cutoff', 'date' => '2026-12-12'], ['k' => 'ye', 'name' => 'Year-end giving push', 'date' => '2026-12-31']],
    'rules' => ['No upfront cost, ever. That is the whole pitch.', 'Never publish the profit split publicly. The group payout is between us and them.', 'The group keeps $5 a shirt; we hold a 35% floor after that.', 'Warm, simple, easy for a leader to say yes to.'],
    'scripts' => [
      ['k' => 'call', 'name' => 'Phone script (30 seconds)', 'kind' => 'say', 'seg' => '',
       'body' => "Good morning, this is Dr. Graham with Grahams Global Fundraising Group here in Central Florida. We run shirt fundraisers for churches and schools, and there's no upfront cost to you at all.\n\nHere's the whole thing: we design the shirt free, your people order online, and {org} keeps $5 from every shirt sold. You never buy inventory and you never collect money.\n\nWho handles fundraising there?"],
      ['k' => 'email', 'name' => 'Email: zero-risk fundraiser', 'kind' => 'send', 'seg' => '',
       'subject' => 'A fundraiser for {org} with nothing to put up front',
       'body' => "{who},\n\nI'm Dr. Robert Graham with Grahams Global Fundraising Group, here in Central Florida.\n\nWe run custom shirt fundraisers, and there is no upfront cost to {org}. Not a deposit, not a minimum, nothing.\n\nHow it works:\n\n1. We design the shirt free and send you 3 options in 24 hours.\n2. You share one link. Your people order and pay for their own shirts.\n3. {org} keeps $5 from every shirt sold. We send the check when the campaign closes.\n\nNo boxes in a closet, no order forms, no cash to count. We only succeed when you do.\n\nIs there a need coming up I could help with, or a date you'd want this to land before?\n\nIn service,\nDr. Robert D. Graham\nGrahams Global Fundraising Group\n" . MK_PHONE . "\n{land}"],
      ['k' => 'follow', 'name' => 'Follow-up (day 3)', 'kind' => 'send', 'seg' => '',
       'body' => "Checking back, {who}. The fundraiser for {org} costs nothing to start and I can have the designs to you tomorrow. Want me to put them together?"],
      ['k' => 'page', 'name' => 'Send the fundraiser page', 'kind' => 'send', 'seg' => '',
       'body' => "Here it is, {who}. This is the fundraiser page for {org}: {camp}\n\nShare that one link. Every shirt sold puts $5 toward {org}, and you never touch the money. I'll send you the total as it comes in.\n\n- Dr. Robert D Graham, Grahams Global Fundraising Group"],
      ['k' => 'thanks', 'name' => 'Thank-you + the next one', 'kind' => 'send', 'seg' => '',
       'body' => "Thank you, {who}. Here's what {org} raised, and it was a joy to do it.\n\nMost groups run one of these every season. Want me to pencil in the next one now, and who else should I be helping?"],
    ],
    'seed' => [],
  ];

  $P['web'] = [
    'id' => 'web', 'name' => "3G's AI Web Solutions", 'short' => 'AI Web',
    'brand' => "3G's AI Web Solutions", 'phone' => MK_PHONE,
    'email' => 'support@3gswebsolutions.shop', 'site' => 'https://3gswebsolutions.shop/packages.html',
    'land' => '/web-audit/', 'kind' => 'service',
    'hook' => 'A free AI and website audit. We show you what is costing you leads, then you decide.',
    'goal' => ['n' => 4, 'label' => 'audits booked', 'units' => 1, 'unit_label' => 'package sold'],
    'segs' => ['business' => 'Local business', 'corp' => 'Agency / multi-location', 'senior' => 'Medical / dental practice', 'travel' => 'Hospitality / service'],
    'stage_names' => ['new' => 'Not called yet', 'contacted' => 'Called, no yes yet', 'interested' => 'Interested', 'proof' => 'Audit sent', 'quote' => 'Package quoted', 'won' => 'CLIENT', 'lost' => 'No'],
    'out_names' => ['proofsent' => 'Sent the audit', 'pagesent' => 'Quoted a package', 'won' => 'SIGNED'],
    'rates' => [['Quick Fix', '$297', 'one problem, fixed fast'], ['Website Redesign + AI', '$3,497', 'most popular'], ['Premium + 90 Days', '$5,997', 'build plus 90 days of optimization'], ['Complete Build + Management', '$7,997', 'best value, 6 months managed'], ['Done With You', '$12,500', 'full AI implementation']],
    'deadlines' => [['k' => 'ye', 'name' => 'Year-end budget close', 'date' => '2026-12-31']],
    'rules' => ['30-day money-back guarantee on every package. Say it every time.', 'Lead with the free audit, never with a price.', 'Quote only the five real packages. Never invent a number.', 'Book the audit on Calendly: calendly.com/robert-graham1671'],
    'scripts' => [
      ['k' => 'call', 'name' => 'Phone script (30 seconds)', 'kind' => 'say', 'seg' => '',
       'body' => "Hi, this is Dr. Robert Graham with 3G's AI Web Solutions. I do a free AI and website audit for local businesses — no pitch, just a short report on what's costing you leads on your site.\n\nI pulled up {org} already. Who handles the website there?\n\n... It takes me about twenty minutes and you keep the report either way. What's the best email for it?"],
      ['k' => 'email', 'name' => 'Email: free audit', 'kind' => 'send', 'seg' => '',
       'subject' => 'A free look at the {org} website',
       'body' => "{who},\n\nI'm Dr. Robert Graham with 3G's AI Web Solutions. I run a free AI and website audit for local businesses and I'd like to do one for {org}.\n\nWhat you get, at no cost and no obligation:\n\n- What's slowing the site down and on which pages\n- Where visitors are leaving before they contact you\n- The three AI tools that would answer and capture leads for you around the clock\n- A short written report you keep, whether or not we work together\n\nIf you want to move on any of it afterward, packages start at $297 and everything carries a 30-day money-back guarantee. If you don't, you still keep the report.\n\nWant me to run it this week?\n\nDr. Robert D. Graham\n3G's AI Web Solutions\n" . MK_PHONE . "\nBook it directly: https://calendly.com/robert-graham1671"],
      ['k' => 'dm', 'name' => 'Short DM', 'kind' => 'send', 'seg' => '',
       'body' => "Hi {who} — Dr. Graham with 3G's AI Web Solutions. I do a free AI and website audit for local businesses: what's slowing your site down, where you're losing leads, and the AI that would catch them. You keep the report either way. Want me to run one for {org}?"],
      ['k' => 'follow', 'name' => 'Follow-up (day 3)', 'kind' => 'send', 'seg' => '',
       'body' => "Following up, {who}. The audit for {org} is still free and takes me about twenty minutes. Send me the go-ahead and I'll have it to you this week."],
      ['k' => 'quote', 'name' => 'After the audit: the package', 'kind' => 'send', 'seg' => '',
       'body' => "{who}, here's the audit for {org}. The three biggest items are marked at the top.\n\nIf you want me to handle them, the fit is [package] at [price]. Everything carries a 30-day money-back guarantee, and you get an onboarding kit and a kickoff call.\n\nNo rush either way — the report is yours.\n\nDr. Graham · " . MK_PHONE],
      ['k' => 'thanks', 'name' => 'Thank-you + referral', 'kind' => 'send', 'seg' => '',
       'body' => "Thank you, {who}. It's been a pleasure building with {org}.\n\nWho else do you know running a business on a site that isn't pulling its weight? I'll run them a free audit the same way."],
    ],
    'seed' => [],
  ];

  return $P;
}

/* Packs live in tt_data/machine_packs.json so they can be edited from the desk.
   The code above is only the starting point, written out the first time this runs. */
function mk_packs() {
  static $cache = null;
  if ($cache !== null) return $cache;
  $d = tt_read('machine_packs.json');
  $saved = (array)($d['packs'] ?? []);
  if (!$saved) { $saved = mk_defaults(); tt_update('machine_packs.json', function ($x) use ($saved) { $x['packs'] = $saved; $x['seeded'] = time(); return ['data' => $x]; }); }
  $def = mk_defaults();
  foreach ($saved as $k => $v) {
    $base = $def[$k] ?? ['id' => $k, 'kind' => 'service', 'segs' => [], 'stage_names' => [], 'out_names' => [], 'rates' => [], 'rules' => [], 'scripts' => [], 'deadlines' => [], 'seed' => [], 'goal' => ['n' => 1, 'label' => 'won', 'units' => 1, 'unit_label' => ''], 'land' => '/', 'site' => '', 'phone' => MK_PHONE, 'email' => '', 'brand' => '', 'hook' => '', 'name' => $k, 'short' => $k];
    $saved[$k] = array_merge($base, (array)$v);
    $saved[$k]['id'] = $k;
  }
  $cache = $saved;
  return $cache;
}
function mk_pack($id) { $P = mk_packs(); return $P[$id] ?? $P['gpts']; }
function mk_pack_id($id) { $P = mk_packs(); $id = (string)$id; return isset($P[$id]) ? $id : 'gpts'; }
function mk_menu() { $o = []; foreach (mk_packs() as $k => $p) $o[] = ['id' => $k, 'name' => $p['name'], 'short' => $p['short'], 'kind' => $p['kind']]; return $o; }

/* ---------------- money (apparel packs) ---------------- */
function mk_margin($item, $price, $payout = 0, $set = null) {
  $set = $set ?: tts_settings();
  $cost = (float)(tts_cost_table($set, time())[$item] ?? 15);
  $stripe = $price * $set['stripe_pct'] / 100 + $set['stripe_fixed'];
  $tax = !empty($set['tax_absorbed']) ? $price * $set['sales_tax_pct'] / (100 + $set['sales_tax_pct']) : 0;
  $keep = $price - $cost - $stripe - $tax - MK_BULK_POST - $payout;
  return ['pct' => $price > 0 ? round($keep / $price * 100, 1) : 0, 'keep' => round($keep, 2), 'cost' => $cost];
}
function mk_price_for($item, $target, $payout = 0, $set = null) {
  $set = $set ?: tts_settings();
  $cost = (float)(tts_cost_table($set, time())[$item] ?? 15);
  $k = 1 - $set['stripe_pct'] / 100 - (!empty($set['tax_absorbed']) ? $set['sales_tax_pct'] / (100 + $set['sales_tax_pct']) : 0) - $target / 100;
  if ($k <= 0.05) return 0;
  return ceil(($cost + $set['stripe_fixed'] + MK_BULK_POST + $payout) / $k * 2) / 2;
}
function mk_tiers($packId) {
  $T = [
    ['id' => 'starter', 'name' => 'Starter',    'min' => 12,  'max' => 23, 'price' => 28.00, 'payout' => 0, 'best' => 'small reunions, youth groups'],
    ['id' => 'group',   'name' => 'Group',      'min' => 24,  'max' => 49, 'price' => 25.00, 'payout' => 0, 'best' => 'most churches and reunions'],
    ['id' => 'church',  'name' => 'Church',     'min' => 50,  'max' => 99, 'price' => 22.50, 'payout' => 0, 'best' => 'church-wide events'],
    ['id' => 'event',   'name' => 'Event',      'min' => 100, 'max' => 0,  'price' => 21.00, 'payout' => 0, 'best' => 'tailgates, conferences'],
    ['id' => 'fund',    'name' => 'Fundraiser', 'min' => 12,  'max' => 0,  'price' => 30.00, 'payout' => 5.00, 'best' => 'churches, schools, GGFG'],
  ];
  if ($packId === 'ggfg') $T = [$T[4]];
  $set = tts_settings(); $out = [];
  foreach ($T as $t) {
    $po = (float)$t['payout']; $tee = round((float)$t['price'], 2);
    $m = mk_margin('tee', $tee, $po, $set);
    $target = max(MK_MIN_MARGIN + 1, min(MK_TARGET_PCT, $m['pct']));
    $cp = mk_price_for('crew', $target, $po, $set); $hp = mk_price_for('hood', $target, $po, $set);
    $t['items'] = ['tee' => ['name' => 'T-shirt', 'price' => $tee, 'margin' => $m['pct'], 'keep' => $m['keep']],
      'crew' => ['name' => 'Crewneck', 'price' => $cp, 'margin' => mk_margin('crew', $cp, $po, $set)['pct'], 'keep' => mk_margin('crew', $cp, $po, $set)['keep']],
      'hood' => ['name' => 'Hoodie', 'price' => $hp, 'margin' => mk_margin('hood', $hp, $po, $set)['pct'], 'keep' => mk_margin('hood', $hp, $po, $set)['keep']]];
    $t['label'] = $t['max'] ? $t['min'] . ' to ' . $t['max'] : $t['min'] . '+';
    $t['floor_ok'] = $m['pct'] >= MK_MIN_MARGIN;
    $out[] = $t;
  }
  return $out;
}
function mk_tier($packId, $id) { foreach (mk_tiers($packId) as $t) if ($t['id'] === $id) return $t; return null; }
function mk_tier_for_qty($packId, $q) { $q = (int)$q; $all = mk_tiers($packId); $pick = $all[0]; foreach ($all as $t) { if ($t['id'] === 'fund' && count($all) > 1) continue; if ($q >= $t['min']) $pick = $t; } return $pick; }

/* ---------------- scripts ---------------- */
function mk_scripts($P, $L) {
  $org = ($L['org'] ?? '') !== '' ? $L['org'] : 'your group';
  $contact = trim((string)($L['contact'] ?? ''));
  $parts = preg_split('/\s+/', $contact);
  $who = ($parts && $parts[0] !== '') ? $parts[0] : 'there';
  $land = rtrim(tt_site(), '/') . $P['land'];
  $camp = ((string)($L['camp'] ?? '') !== '') ? rtrim(tt_site(), '/') . '/fundraiser/?c=' . $L['camp'] : $land;
  $tok = ['{org}' => $org, '{who}' => $who, '{contact}' => ($contact !== '' ? $contact : '[name]'),
    '{city}' => (string)($L['city'] ?? ''), '{land}' => $land, '{camp}' => $camp];
  $stage = (string)($L['stage'] ?? 'new'); $seg = (string)($L['seg'] ?? '');
  $out = [];
  foreach ($P['scripts'] as $s) {
    $s['body'] = strtr($s['body'], $tok);
    if (isset($s['subject'])) $s['subject'] = strtr($s['subject'], $tok);
    $pick = false;
    if (($s['seg'] ?? '') !== '' && $s['seg'] === $seg) $pick = true;
    if ($s['k'] === 'follow' && $stage === 'contacted') $pick = true;
    if (in_array($s['k'], ['page', 'quote'], true) && in_array($stage, ['quote', 'proof'], true)) $pick = true;
    if ($s['k'] === 'thanks' && $stage === 'won') $pick = true;
    $s['pick'] = $pick;
    unset($s['seg']);
    $out[] = $s;
  }
  return $out;
}

/* ---------------- stats ---------------- */
function mk_camp_stats() {
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
function mk_biz($L) { $b = (string)($L['biz'] ?? ''); if ($b !== '') return $b; return !empty($L['og']) ? 'orlando' : ''; }

$a = (string)($_GET['a'] ?? '');
$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$bid = mk_pack_id($_GET['b'] ?? ($_POST['b'] ?? ''));

/* ---------------- public: the free-offer form ---------------- */
if ($a === 'request' && $m === 'POST') {
  $b = tt_body();
  $bid = mk_pack_id($b['biz'] ?? $bid);
  $P = mk_pack($bid);
  $org = tt_clean($b['org'] ?? '', 90);
  $contact = tt_clean($b['contact'] ?? '', 60);
  $phone = tt_clean($b['phone'] ?? '', 30);
  $email = filter_var($b['email'] ?? '', FILTER_VALIDATE_EMAIL) ? strtolower($b['email']) : '';
  if ($contact === '' || ($phone === '' && $email === '')) tt_json(['error' => 'Please add your name and a phone or email so we can reach you.'], 400);
  if ($org === '') $org = $contact;
  $seg = isset($P['segs'][$b['seg'] ?? '']) ? $b['seg'] : array_keys($P['segs'])[0];
  $rk = 'rate_' . substr(tt_ipkey(), 0, 16);
  $hit = tt_update('machine.json', function ($d) use ($rk) {
    $n = (int)($d[$rk]['n'] ?? 0); $t = (int)($d[$rk]['t'] ?? 0);
    if (time() - $t > 3600) { $n = 0; $t = time(); }
    $d[$rk] = ['n' => $n + 1, 't' => $t];
    return ['data' => $d, 'result' => $n + 1];
  });
  if ($hit > 6) tt_json(['error' => 'We already have your request. We will be in touch today.'], 429);
  $id = substr(bin2hex(random_bytes(6)), 0, 10);
  tt_update('leads.json', function ($d) use ($id, $org, $contact, $phone, $email, $seg, $b, $bid, $P) {
    $d['leads'] = $d['leads'] ?? [];
    $d['leads'][$id] = ['id' => $id, 'created' => time(), 'updated' => time(), 'biz' => $bid, 'og' => $bid === 'orlando' ? 1 : 0,
      'seg' => $seg, 'type' => 'Other', 'org' => $org, 'contact' => $contact, 'phone' => $phone, 'email' => $email,
      'city' => tt_clean($b['city'] ?? '', 60), 'qty' => max(0, min(5000, (int)($b['qty'] ?? 0))),
      'event' => tt_clean($b['event'] ?? '', 80), 'evdate' => preg_match('~^\d{4}-\d{2}-\d{2}$~', (string)($b['evdate'] ?? '')) ? $b['evdate'] : '',
      'stage' => 'interested', 'next' => date('Y-m-d'), 'source' => $P['short'] . ' page', 'touches' => 0, 'proofs' => 0,
      'notes' => [['at' => time(), 'text' => 'Asked online. ' . tt_clean($b['notes'] ?? '', 400)]]];
    return ['data' => $d, 'result' => true];
  });
  @tt_notify('NEW LEAD (' . $P['short'] . '): ' . $org, "Business: " . $P['name'] . "\nGroup: $org\nName: $contact\nPhone: $phone\nEmail: $email\nType: " . $P['segs'][$seg] . "\nHow many: " . (int)($b['qty'] ?? 0) . "\nWhat for: " . tt_clean($b['event'] ?? '', 80) . ' ' . (string)($b['evdate'] ?? '') . "\nNotes: " . tt_clean($b['notes'] ?? '', 400) . "\n\nOpen the Outreach Machine in the Command Center and call them today.", $email);
  tt_json(['ok' => true, 'msg' => (string)($P['reply'] ?? 'Got it. Dr. Graham will be in touch within 24 hours.')]);
}

/* ---------------- desk ---------------- */
tt_require_admin();
$set = tts_settings();
$P = mk_pack($bid);
$D = tt_read('leads.json');
$LEADS = (array)($D['leads'] ?? []);

if ($m === 'GET') {
  if ($a === 'packs') tt_json(['ok' => true, 'packs' => mk_menu()]);
  if ($a === 'tiers') tt_json(['ok' => true, 'tiers' => mk_tiers($bid), 'floor' => MK_MIN_MARGIN]);
  if ($a === 'scripts') {
    $L = $LEADS[preg_replace('/[^a-z0-9]/', '', (string)($_GET['id'] ?? ''))] ?? null;
    if (!$L) tt_json(['error' => 'Lead not found.'], 404);
    tt_json(['ok' => true, 'scripts' => mk_scripts($P, $L)]);
  }
  $cs = mk_camp_stats();
  $today = date('Y-m-d');
  $due = []; $fresh = []; $live = []; $won = []; $counts = [];
  foreach (mk_packs() as $k => $x) $counts[$k] = 0;
  foreach ($LEADS as $id => $L) {
    if (!empty($L['archived'])) continue;
    $lb = mk_biz($L); if ($lb === '') continue;
    if (isset($counts[$lb])) $counts[$lb]++;
    if ($lb !== $bid) continue;
    $L['id'] = $id;
    $L['stage'] = in_array($L['stage'] ?? 'new', MK_STAGES, true) ? $L['stage'] : 'new';
    if ($P['kind'] === 'apparel') $L['tier'] = mk_tier_for_qty($bid, max(1, (int)($L['qty'] ?? 0)))['id'];
    if (!empty($L['camp'])) {
      $s = $cs[$L['camp']] ?? ['units' => 0, 'sales' => 0, 'orders' => 0];
      $L['sold'] = $s['units']; $L['money'] = round($s['sales'] / 100, 2);
      $L['url'] = tt_site() . '/fundraiser/?c=' . $L['camp']; $live[] = $L;
    }
    if ($L['stage'] === 'won') $won[] = $L;
    if (in_array($L['stage'], ['won', 'lost'], true)) continue;
    if (($L['next'] ?? '') !== '' && $L['next'] <= $today) $due[] = $L;
    elseif ($L['stage'] === 'new' && ($L['next'] ?? '') === '') $fresh[] = $L;
  }
  usort($due, function ($x, $y) { $o = ['interested' => 0, 'proof' => 1, 'quote' => 2, 'contacted' => 3, 'new' => 4]; return [$o[$x['stage']] ?? 9, $x['next'] ?? ''] <=> [$o[$y['stage']] ?? 9, $y['next'] ?? '']; });
  usort($fresh, function ($x, $y) { return strcasecmp($x['org'] ?? '', $y['org'] ?? ''); });
  $dl = []; foreach ($P['deadlines'] as $d) { $d['days'] = (int)floor((strtotime($d['date']) - strtotime($today)) / 86400); $dl[] = $d; }
  $units = 0; $money = 0; foreach ($live as $L) { $units += (int)$L['sold']; $money += (float)$L['money']; }
  $board = ['total' => 0, 'touches' => 0, 'proofs' => 0]; foreach (MK_STAGES as $s) $board[$s] = 0;
  foreach ($LEADS as $L) {
    if (!empty($L['archived']) || mk_biz($L) !== $bid) continue;
    $board['total']++; $st = in_array($L['stage'] ?? 'new', MK_STAGES, true) ? $L['stage'] : 'new';
    $board[$st]++; $board['touches'] += (int)($L['touches'] ?? 0); $board['proofs'] += (int)($L['proofs'] ?? 0);
  }
  $out = ['ok' => true, 'biz' => $bid, 'pack' => ['id' => $bid, 'name' => $P['name'], 'short' => $P['short'], 'brand' => $P['brand'],
      'kind' => $P['kind'], 'hook' => $P['hook'], 'phone' => $P['phone'], 'land' => $P['land'], 'site' => $P['site'],
      'segs' => $P['segs'], 'stage_names' => $P['stage_names'], 'out_names' => (object)$P['out_names'], 'rates' => $P['rates'], 'rules' => $P['rules'],
      'goal' => $P['goal'], 'has_seed' => !empty($P['seed']), 'scripts' => $P['scripts'], 'deadlines_raw' => $P['deadlines'], 'email' => $P['email']],
    'packs' => mk_menu(), 'counts' => $counts, 'today' => $today,
    'due' => $due, 'fresh' => array_slice($fresh, 0, 80), 'live' => $live,
    'board' => $board, 'deadlines' => $dl, 'stages' => MK_STAGES,
    'score' => ['won' => count($won), 'units' => $units, 'money' => round($money, 2), 'goal_n' => $P['goal']['n'], 'goal_units' => $P['goal']['units']],
    'tiers' => $P['kind'] === 'apparel' ? mk_tiers($bid) : [], 'floor' => MK_MIN_MARGIN];
  if ($a === 'all') { $all = []; foreach ($LEADS as $id => $L) { if (mk_biz($L) !== $bid) continue; $L['id'] = $id; $all[] = $L; } usort($all, function ($x, $y) { return strcasecmp($x['org'] ?? '', $y['org'] ?? ''); }); $out['all'] = $all; }
  tt_json($out);
}

$b = tt_body();
$bid = mk_pack_id($b['biz'] ?? $bid);
$P = mk_pack($bid);
$op = (string)($b['a'] ?? '');
$id = preg_replace('/[^a-z0-9]/', '', (string)($b['id'] ?? ''));
$dt = function ($v) { $v = (string)$v; return preg_match('~^\d{4}-\d{2}-\d{2}$~', $v) ? $v : ''; };

if ($op === 'import') {
  $rows = (array)($b['rows'] ?? []);
  if (!$rows && !empty($P['seed'])) $rows = $P['seed'];
  if (!$rows) tt_json(['error' => 'Nothing to import.'], 400);
  $r = tt_update('leads.json', function ($d) use ($rows, $dt, $bid, $P) {
    $d['leads'] = $d['leads'] ?? [];
    $have = [];
    foreach ($d['leads'] as $L) if (mk_biz($L) === $bid) $have[strtolower(trim((string)($L['org'] ?? '')))] = 1;
    $add = 0; $skip = 0;
    foreach ($rows as $row) {
      $org = tt_clean($row['org'] ?? '', 90); if ($org === '') continue;
      if (isset($have[strtolower($org)])) { $skip++; continue; }
      $seg = isset($P['segs'][$row['seg'] ?? '']) ? $row['seg'] : array_keys($P['segs'])[0];
      $nid = substr(bin2hex(random_bytes(6)), 0, 10);
      $d['leads'][$nid] = ['id' => $nid, 'created' => time(), 'updated' => time(), 'biz' => $bid, 'og' => $bid === 'orlando' ? 1 : 0,
        'seg' => $seg, 'type' => 'Other', 'org' => $org, 'contact' => tt_clean($row['contact'] ?? '', 60),
        'phone' => tt_clean($row['phone'] ?? '', 30), 'email' => filter_var($row['email'] ?? '', FILTER_VALIDATE_EMAIL) ? strtolower($row['email']) : '',
        'city' => tt_clean($row['city'] ?? '', 60), 'qty' => max(0, min(5000, (int)($row['qty'] ?? 0))),
        'event' => tt_clean($row['event'] ?? '', 80), 'evdate' => $dt($row['evdate'] ?? ''),
        'stage' => 'new', 'next' => '', 'source' => tt_clean($row['source'] ?? $P['short'], 40), 'touches' => 0, 'proofs' => 0, 'notes' => []];
      $have[strtolower($org)] = 1; $add++;
    }
    return ['data' => $d, 'result' => ['ok' => true, 'added' => $add, 'skipped' => $skip]];
  });
  tt_json($r);
}

if ($op === 'save') {
  $r = tt_update('leads.json', function ($d) use ($id, $b, $dt, $bid, $P) {
    $d['leads'] = $d['leads'] ?? [];
    $nid = $id;
    if ($nid === '' || !isset($d['leads'][$nid])) {
      if (tt_clean($b['org'] ?? '', 90) === '') return ['data' => $d, 'result' => ['error' => 'Type the name.']];
      $nid = substr(bin2hex(random_bytes(6)), 0, 10);
      $d['leads'][$nid] = ['id' => $nid, 'created' => time(), 'stage' => 'new', 'notes' => [], 'touches' => 0, 'proofs' => 0];
    }
    $L = $d['leads'][$nid];
    $L['biz'] = $bid; $L['og'] = $bid === 'orlando' ? 1 : 0;
    if (isset($b['seg']) && isset($P['segs'][$b['seg']])) $L['seg'] = $b['seg'];
    foreach (['org' => 90, 'contact' => 60, 'phone' => 30, 'city' => 60, 'event' => 80, 'source' => 40] as $f => $mx) if (array_key_exists($f, $b)) $L[$f] = tt_clean($b[$f], $mx);
    if (array_key_exists('email', $b)) $L['email'] = filter_var($b['email'], FILTER_VALIDATE_EMAIL) ? strtolower($b['email']) : '';
    if (array_key_exists('qty', $b)) $L['qty'] = max(0, min(5000, (int)$b['qty']));
    if (array_key_exists('evdate', $b)) $L['evdate'] = $dt($b['evdate']);
    if (array_key_exists('next', $b)) $L['next'] = $dt($b['next']);
    if (isset($b['stage']) && in_array($b['stage'], MK_STAGES, true)) $L['stage'] = $b['stage'];
    if (!empty($b['note'])) $L['notes'][] = ['at' => time(), 'text' => tt_clean($b['note'], 500)];
    $L['notes'] = array_slice($L['notes'] ?? [], -100); $L['updated'] = time();
    $d['leads'][$nid] = $L;
    return ['data' => $d, 'result' => ['ok' => true, 'id' => $nid]];
  });
  tt_json($r, isset($r['error']) ? 400 : 200);
}

if ($op === 'touch') {
  $OUT = [
    'noanswer'   => ['stage' => 'contacted',  'days' => 2,  'say' => 'No answer'],
    'gatekeeper' => ['stage' => 'contacted',  'days' => 3,  'say' => 'Left word with the office'],
    'callback'   => ['stage' => 'contacted',  'days' => 1,  'say' => 'Call back'],
    'interested' => ['stage' => 'interested', 'days' => 1,  'say' => 'INTERESTED'],
    'proofsent'  => ['stage' => 'proof',      'days' => 3,  'say' => (string)($P['out_names']['proofsent'] ?? 'Sent it')],
    'pagesent'   => ['stage' => 'quote',      'days' => 3,  'say' => (string)($P['out_names']['pagesent'] ?? 'Quoted')],
    'notnow'     => ['stage' => 'contacted',  'days' => 21, 'say' => 'Not now, try again later'],
    'won'        => ['stage' => 'won',        'days' => 0,  'say' => (string)($P['out_names']['won'] ?? 'WON')],
    'lost'       => ['stage' => 'lost',       'days' => 0,  'say' => 'No for now'],
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
    $L['notes'][] = ['at' => time(), 'text' => $c['say'] . (!empty($b['note']) ? ' - ' . tt_clean($b['note'], 400) : '')];
    $L['notes'] = array_slice($L['notes'], -100); $L['updated'] = time();
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

if ($op === 'campaign') {
  if ($P['kind'] !== 'apparel') tt_json(['error' => 'Group pages are for the shirt businesses. Use the quote script here instead.'], 400);
  $L = $LEADS[$id] ?? null;
  if (!$L) tt_json(['error' => 'Lead not found.'], 404);
  $t = mk_tier($bid, (string)($b['tier'] ?? '')) ?: mk_tier_for_qty($bid, max(24, (int)($L['qty'] ?? 24)));
  $type = $t['id'] === 'fund' ? 'fund' : 'group';
  $ends = $dt($b['ends'] ?? '') ?: date('Y-m-d', strtotime('+14 day'));
  $title = tt_clean($b['title'] ?? '', 80) ?: (($L['event'] ?? '') !== '' ? $L['org'] . ' - ' . $L['event'] : $L['org'] . ' Group Shirts');
  $goal = max(1, (int)($b['goal'] ?? max(24, (int)($L['qty'] ?? 24))));
  $on = (array)($b['items'] ?? ['tee']);
  $dir = TTS_DATA . '/campaigns'; if (!is_dir($dir)) @mkdir($dir, 0750, true);
  $base = trim(preg_replace('~[^a-z0-9]+~', '-', strtolower($L['org'])), '-'); if ($base === '') $base = 'group';
  if (strlen($base) > 36) $base = substr($base, 0, 36);
  $cid = $base; $i = 2; while (is_file($dir . '/' . $cid . '.json')) $cid = $base . '-' . $i++;
  $items = [];
  foreach (['tee', 'crew', 'hood'] as $g) {
    $items[$g] = ['on' => in_array($g, $on, true), 'price' => (float)($t['items'][$g]['price'] ?? 0), 'payout' => $type === 'fund' ? (float)$t['payout'] : 0];
  }
  if (!$items['tee']['on'] && !$items['crew']['on'] && !$items['hood']['on']) $items['tee']['on'] = true;
  foreach ($items as $g => $it) {
    if (!$it['on']) continue;
    $mg = mk_margin($g, $it['price'], $it['payout'], $set);
    if ($mg['pct'] < MK_MIN_MARGIN) tt_json(['error' => 'That would keep only ' . $mg['pct'] . '% on the ' . $g . '. Pick a higher tier.'], 400);
  }
  $C = ['id' => $cid, 'created' => time(), 'updated' => time(), 'status' => 'draft', 'paid_out' => 0, 'offline' => 0, 'imgs' => 0,
    'type' => $type, 'title' => $title, 'org' => $L['org'],
    'brand' => $type === 'fund' ? 'Grahams Global Fundraising Group' : 'Threaded Truths',
    'story' => tt_clean($b['story'] ?? '', 900) ?: ('Custom shirts for ' . $L['org'] . ', designed free and pressed by hand here in Orlando. Pick your size, pay for your own shirt, and we deliver the whole group in one box before the event.'),
    'goal' => $goal, 'ends' => $ends, 'delivery' => 'group', 'side' => 'back', 'colors' => ['black', 'gray', 'white'],
    'contact' => ['name' => (string)($L['contact'] ?? ''), 'email' => (string)($L['email'] ?? ''), 'phone' => (string)($L['phone'] ?? '')],
    'notes' => $P['name'] . ' - tier ' . $t['name'] . ' (' . $t['label'] . ')', 'items' => $items, 'tier' => $t['id']];
  if (@file_put_contents($dir . '/' . $cid . '.json', json_encode($C, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false)
    tt_json(['error' => 'Could not save the page. Storage is not writable.'], 500);
  tt_update('leads.json', function ($d) use ($id, $cid, $t) {
    if (!isset($d['leads'][$id])) return ['data' => $d];
    $d['leads'][$id]['camp'] = $cid; $d['leads'][$id]['tier'] = $t['id']; $d['leads'][$id]['stage'] = 'quote';
    $d['leads'][$id]['next'] = date('Y-m-d', strtotime('+3 day')); $d['leads'][$id]['updated'] = time();
    $d['leads'][$id]['notes'][] = ['at' => time(), 'text' => 'Page built as a draft: ' . $cid . ' (' . $t['name'] . ' tier)'];
    return ['data' => $d];
  });
  tt_json(['ok' => true, 'id' => $cid, 'url' => tt_site() . '/fundraiser/?c=' . $cid, 'status' => 'draft',
    'msg' => 'Page built as a DRAFT. Add the design image and set it to Open in the Group Pages tab, then send the link.']);
}
if ($op === 'pack_save') {
  $d = (array)($b['pack'] ?? []);
  $pid = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($d['id'] ?? $bid)));
  if ($pid === '' || strlen($pid) > 24) tt_json(['error' => 'Give the business a short id: letters and numbers, no spaces.'], 400);
  $str = function ($v, $max) { return tt_clean($v, $max); };
  $rows = function ($v, $cols, $max) use ($str) {
    $o = [];
    foreach ((array)$v as $r) { $r = (array)$r; $line = []; $empty = true;
      foreach ($cols as $i => $len) { $line[$i] = $str($r[$i] ?? '', $len); if ($line[$i] !== '') $empty = false; }
      if (!$empty) $o[] = $line; if (count($o) >= $max) break; }
    return $o;
  };
  $segs = [];
  foreach ((array)($d['segs'] ?? []) as $k => $v) {
    $k = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$k)); $v = $str($v, 60);
    if ($k !== '' && $v !== '') $segs[$k] = $v;
    if (count($segs) >= 12) break;
  }
  if (!$segs) $segs = ['business' => 'Business'];
  $scripts = [];
  foreach ((array)($d['scripts'] ?? []) as $sc) {
    $sc = (array)$sc; $body = trim((string)($sc['body'] ?? ''));
    if ($body === '') continue;
    $scripts[] = ['k' => preg_replace('/[^a-z0-9_]/', '', strtolower((string)($sc['k'] ?? ''))) ?: 's' . count($scripts),
      'name' => $str($sc['name'] ?? 'Script', 70), 'kind' => ($sc['kind'] ?? '') === 'say' ? 'say' : 'send',
      'seg' => isset($segs[$sc['seg'] ?? '']) ? $sc['seg'] : '',
      'subject' => $str($sc['subject'] ?? '', 160), 'body' => mb_substr(strip_tags($body), 0, 4000)];
    if (count($scripts) >= 20) break;
  }
  $dls = [];
  foreach ((array)($d['deadlines'] ?? []) as $x) {
    $x = (array)$x; $dt2 = (string)($x['date'] ?? '');
    if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $dt2)) continue;
    $dls[] = ['k' => 'd' . count($dls), 'name' => $str($x['name'] ?? 'Date', 70), 'date' => $dt2];
    if (count($dls) >= 10) break;
  }
  $old = mk_packs()[$pid] ?? [];
  $P2 = array_merge($old, [
    'id' => $pid,
    'name' => $str($d['name'] ?? ($old['name'] ?? $pid), 60) ?: $pid,
    'short' => $str($d['short'] ?? ($old['short'] ?? $pid), 24) ?: $pid,
    'brand' => $str($d['brand'] ?? ($old['brand'] ?? ''), 70),
    'phone' => $str($d['phone'] ?? ($old['phone'] ?? MK_PHONE), 30),
    'email' => $str($d['email'] ?? ($old['email'] ?? ''), 90),
    'site' => $str($d['site'] ?? ($old['site'] ?? ''), 200),
    'land' => '/' . trim($str($d['land'] ?? ($old['land'] ?? '/'), 60), '/') . '/',
    'kind' => ($d['kind'] ?? ($old['kind'] ?? 'service')) === 'apparel' ? 'apparel' : 'service',
    'hook' => $str($d['hook'] ?? ($old['hook'] ?? ''), 240),
    'goal' => ['n' => max(1, min(10000, (int)($d['goal']['n'] ?? 1))), 'label' => $str($d['goal']['label'] ?? 'won', 40),
      'units' => max(0, min(1000000, (int)($d['goal']['units'] ?? 0))), 'unit_label' => $str($d['goal']['unit_label'] ?? '', 30)],
    'segs' => $segs,
    'stage_names' => (array)($d['stage_names'] ?? ($old['stage_names'] ?? [])),
    'out_names' => (array)($d['out_names'] ?? ($old['out_names'] ?? [])),
    'rates' => $rows($d['rates'] ?? [], [60, 40, 90], 12),
    'rules' => array_values(array_filter(array_map(function ($r) use ($str) { return $str($r, 240); }, (array)($d['rules'] ?? [])))),
    'deadlines' => $dls,
    'scripts' => $scripts,
    'seed' => (array)($old['seed'] ?? []),
  ]);
  $sn = []; foreach (MK_STAGES as $st) { $v = $str($P2['stage_names'][$st] ?? '', 40); $sn[$st] = $v !== '' ? $v : ucfirst($st); }
  $P2['stage_names'] = $sn;
  tt_update('machine_packs.json', function ($x) use ($pid, $P2) { $x['packs'][$pid] = $P2; $x['updated'] = time(); return ['data' => $x]; });
  tt_json(['ok' => true, 'id' => $pid]);
}
if ($op === 'pack_delete') {
  $pid = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($b['pack_id'] ?? '')));
  $n = 0; foreach ($LEADS as $L) if (mk_biz($L) === $pid && empty($L['archived'])) $n++;
  if ($n) tt_json(['error' => 'That business still has ' . $n . ' leads. Move or archive them first.'], 400);
  tt_update('machine_packs.json', function ($x) use ($pid) { unset($x['packs'][$pid]); $x['updated'] = time(); return ['data' => $x]; });
  tt_json(['ok' => true]);
}
if ($op === 'pack_reset') {
  $pid = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($b['pack_id'] ?? $bid)));
  $def = mk_defaults();
  if (!isset($def[$pid])) tt_json(['error' => 'That one was made here, so there is nothing to reset it to.'], 400);
  tt_update('machine_packs.json', function ($x) use ($pid, $def) { $x['packs'][$pid] = $def[$pid]; $x['updated'] = time(); return ['data' => $x]; });
  tt_json(['ok' => true]);
}
if ($op === 'move') {
  $to = mk_pack_id($b['to'] ?? '');
  $r = tt_update('leads.json', function ($d) use ($id, $to) {
    if (!isset($d['leads'][$id])) return ['data' => $d, 'result' => ['error' => 'Lead not found.']];
    $d['leads'][$id]['biz'] = $to; $d['leads'][$id]['og'] = $to === 'orlando' ? 1 : 0; $d['leads'][$id]['updated'] = time();
    $d['leads'][$id]['notes'][] = ['at' => time(), 'text' => 'Moved to ' . $to];
    return ['data' => $d, 'result' => ['ok' => true]];
  });
  tt_json($r, isset($r['error']) ? 400 : 200);
}
tt_json(['error' => 'Unknown action.'], 400);
FB720FA26;
$NEW['desk/machine.js'] = <<<'F92F8730E'
/* 3G's Command Center: THE OUTREACH MACHINE (TTOG).
   One call list, one set of scripts, one scoreboard - re-aimed at whichever business you pick.
   Backend: ../api/machine.php. Built big and one-handed on purpose. */
(function(){
var TABS = {orl:'The Outreach Machine'};
var S = {d:null, biz:'gpts', view:'calls', open:'', msg:'', busy:false, scripts:{}, loading:false};
try { S.biz = localStorage.getItem('ttMachBiz') || 'gpts'; } catch(e){}
var SEED = {orlando: [{"org":"Orlando Grace Church","city":"Altamonte Springs","phone":"407-660-1984","seg":"church","source":"Orlando church list"},{"org":"Grace Pointe Church","city":"Apopka","phone":"407-889-3383","seg":"church","source":"Orlando church list"},{"org":"Journey Christian Church","city":"Apopka","phone":"407-884-7223","seg":"church","source":"Orlando church list"},{"org":"St Paul AME Church","city":"Apopka","phone":"407-703-2901","seg":"church","source":"Orlando church list"},{"org":"Thrive Church Apopka","city":"Apopka","phone":"407-886-2806","seg":"church","source":"Orlando church list"},{"org":"Journey Church","city":"Fern Park","phone":"689-283-9798","seg":"church","source":"Orlando church list"},{"org":"New Hope Bible Church","city":"Kissimmee","phone":"407-791-5912","seg":"church","source":"Orlando church list"},{"org":"Church On The Living Edge","city":"Longwood","phone":"321-304-4111","seg":"church","source":"Orlando church list"},{"org":"King's Chapel","city":"Longwood","phone":"407-415-7447","seg":"church","source":"Orlando church list"},{"org":"LifePoint Christian Church","city":"Longwood","phone":"407-327-6468","seg":"church","source":"Orlando church list"},{"org":"WaterStone Church","city":"Longwood","phone":"407-339-8961","seg":"church","source":"Orlando church list"},{"org":"First AME Church","city":"Maitland","phone":"","seg":"church","source":"Orlando church list"},{"org":"All Nation Church of God of Prophecy","city":"Orlando","phone":"407-298-4411","seg":"church","source":"Orlando church list"},{"org":"Bethel Missionary Baptist Church","city":"Orlando","phone":"407-843-6612","seg":"church","source":"Orlando church list"},{"org":"Church at the Cross","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"Delaney Street Baptist Church","city":"Orlando","phone":"407-422-0758","seg":"church","source":"Orlando church list"},{"org":"Downtown Baptist Church Orlando","city":"Orlando","phone":"407-425-0200","seg":"church","source":"Orlando church list"},{"org":"Eastland Baptist Church","city":"Orlando","phone":"407-277-5858","seg":"church","source":"Orlando church list"},{"org":"Faith Baptist Church Orlando","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"First Orlando - John Young Campus","city":"Orlando","phone":"407-425-2555","seg":"church","source":"Orlando church list"},{"org":"Malone Memorial Church of God in Christ","city":"Orlando","phone":"407-295-5994","seg":"church","source":"Orlando church list"},{"org":"NEW LIFE CHURCH OF GOD","city":"Orlando","phone":"407-275-0563","seg":"church","source":"Orlando church list"},{"org":"New Bethel AME Church","city":"Orlando","phone":"407-295-6152","seg":"church","source":"Orlando church list"},{"org":"New Life Church COGIC","city":"Orlando","phone":"407-290-1467","seg":"church","source":"Orlando church list"},{"org":"Orlando Baptist Church","city":"Orlando","phone":"407-277-8671","seg":"church","source":"Orlando church list"},{"org":"South Orlando Baptist Church","city":"Orlando","phone":"407-859-1536","seg":"church","source":"Orlando church list"},{"org":"St MARK AME EAST CAMPUS","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"St. John Baptist Church","city":"Orlando","phone":"407-781-0700","seg":"church","source":"Orlando church list"},{"org":"Church of God Ministry of Jesus Christ Intl.","city":"Pine Castle","phone":"888-331-8197","seg":"church","source":"Orlando church list"},{"org":"Seminole Community Church","city":"Sanford","phone":"407-324-0199","seg":"church","source":"Orlando church list"},{"org":"Quay Church","city":"Windermere","phone":"407-505-4888","seg":"church","source":"Orlando church list"},{"org":"Discovery Church","city":"Winter Garden","phone":"407-855-3140","seg":"church","source":"Orlando church list"},{"org":"Garden City Church","city":"Winter Garden","phone":"407-499-8051","seg":"church","source":"Orlando church list"},{"org":"Hope Church Winter Garden","city":"Winter Garden","phone":"407-490-1988","seg":"church","source":"Orlando church list"},{"org":"Kingdom Culture Church","city":"Winter Garden","phone":"407-490-3958","seg":"church","source":"Orlando church list"},{"org":"Mosaic Church","city":"Winter Garden","phone":"407-654-8220","seg":"church","source":"Orlando church list"},{"org":"The Grove Bible Chapel","city":"Winter Garden","phone":"407-614-3255","seg":"church","source":"Orlando church list"},{"org":"Vida Church","city":"Winter Garden","phone":"407-436-5147","seg":"church","source":"Orlando church list"},{"org":"Vineland Road Christian Fellowship","city":"Winter Garden","phone":"407-656-3949","seg":"church","source":"Orlando church list"},{"org":"West Orlando Christian Center COGIC","city":"Winter Garden","phone":"407-656-5665","seg":"church","source":"Orlando church list"}]};
function $(i){ return document.getElementById(i); }
function e(s){ return String(s==null?'':s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function q(s){ return String(s==null?'':s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function mo(n){ return '$' + (+n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function K(){ try { return sessionStorage.getItem('ttDesk') || ''; } catch(x){ return ''; } }
function get(p){ return fetch('../api/machine.php' + p + (p.indexOf('?')<0?'?':'&') + 'b=' + encodeURIComponent(S.biz), {headers:{'X-TT-Key':K()}, cache:'no-store'}).then(function(r){ return r.json(); }); }
function post(body){ body.biz = S.biz; return fetch('../api/machine.php', {method:'POST', headers:{'Content-Type':'application/json','X-TT-Key':K()}, body:JSON.stringify(body)}).then(function(r){ return r.json(); }); }
function load(){ if (S.loading) return; S.loading = true; S.scripts = {};
  return get('?a=all').then(function(d){ S.loading=false; S.d = d; draw(); }).catch(function(){ S.loading=false; S.msg='Could not reach the server.'; draw(); }); }
function copy(txt, btn){
  var done = function(){ var o = btn.getAttribute('data-l') || btn.textContent; btn.setAttribute('data-l', o); btn.textContent = 'Copied ✓'; setTimeout(function(){ btn.textContent = o; }, 1500); };
  if (navigator.clipboard) navigator.clipboard.writeText(txt).then(done, function(){ window.prompt('Copy this:', txt); }); else window.prompt('Copy this:', txt);
}
function tel(p){ return String(p||'').replace(/[^0-9+]/g,''); }
function P(){ return (S.d && S.d.pack) || {}; }

function bizBar(){
  var packs = (S.d && S.d.packs) || [], c = (S.d && S.d.counts) || {};
  return '<div class="og-biz">' + packs.map(function(p){
    return '<button class="og-bz' + (S.biz===p.id?' on':'') + '" onclick="TTOG.biz(\'' + q(p.id) + '\')">' + e(p.name) +
      (c[p.id] ? ' <span class="og-c">' + c[p.id] + '</span>' : '') + '</button>'; }).join('') + '</div>';
}
function clock(){
  var dl = (S.d && S.d.deadlines) || [];
  if (!dl.length) return '';
  return '<div class="og-clock">' + dl.map(function(x){
    var cls = x.days < 0 ? 'gone' : x.days <= 7 ? 'hot' : '';
    return '<div class="og-cl ' + cls + '"><b>' + (x.days < 0 ? 'passed' : x.days + (x.days===1?' day':' days')) + '</b><span>' + e(x.name) + '</span><em>' + e(x.date) + '</em></div>';
  }).join('') + '</div>';
}
function score(){
  var s = (S.d && S.d.score) || {}, b = (S.d && S.d.board) || {}, g = P().goal || {};
  var won = s.won||0, gn = s.goal_n||1, pct = Math.min(100, Math.round(won/gn*100));
  return '<div class="og-score"><div class="og-goal"><div class="og-row"><span class="og-big">' + won + ' of ' + gn + ' ' + e(g.label||'won') + '</span><span class="og-mute">' + pct + '%</span></div>' +
   '<div class="og-bar"><i style="width:' + pct + '%"></i></div>' +
   '<div class="og-mute">' + (s.units||0) + ' of ' + (s.goal_units||0) + ' ' + e(g.unit_label||'') + (s.money ? ' · ' + mo(s.money) + ' collected' : '') + '</div>' +
   '<div class="og-hook">' + e(P().hook||'') + '</div></div>' +
   '<div class="og-tiles">' + [['Calls made', b.touches||0],['Sent out', b.proofs||0],['Interested', (b.interested||0)+(b.proof||0)],['Quoted', b.quote||0],['Not called yet', b.new||0]].map(function(t){
     return '<div class="og-t"><b>' + t[1] + '</b><span>' + t[0] + '</span></div>'; }).join('') + '</div></div>';
}
function stageChip(st){
  var nm = (P().stage_names || {})[st] || st;
  return '<em class="og-st s-' + e(st) + '">' + e(nm) + '</em>';
}
function leadCard(L){
  var open = S.open === L.id, ph = tel(L.phone);
  var h = '<div class="og-card' + (open?' op':'') + '">';
  h += '<div class="og-head"><div><b class="og-org">' + e(L.org) + '</b>' +
   '<div class="og-sub">' + e(L.city||'') + (L.contact?' · ' + e(L.contact):'') + (L.qty?' · ' + L.qty:'') + (L.seg && P().segs ? ' · ' + e(P().segs[L.seg]||'') : '') + '</div></div>' + stageChip(L.stage) + '</div>';
  if (L.notes && L.notes.length) h += '<div class="og-last">Last: ' + e(L.notes[L.notes.length-1].text) + '</div>';
  h += '<div class="og-acts">';
  if (ph) h += '<a class="og-b call" href="tel:' + e(ph) + '">☎ Call ' + e(L.phone) + '</a>';
  else h += '<span class="og-b warn">No phone yet — look it up</span>';
  h += '<button class="og-b" onclick="TTOG.open(\'' + q(L.id) + '\')">' + (open?'Hide':'Script + log the call') + '</button></div>';
  if (open) h += panel(L);
  return h + '</div>';
}
function panel(L){
  var sc = S.scripts[L.id], on = P().out_names || {};
  var h = '<div class="og-panel">';
  h += '<div class="og-out"><b>What happened?</b><div class="og-outs">' + [
    ['interested','⭐ Interested'],['proofsent','📤 ' + (on.proofsent||'Sent it')],['noanswer','No answer'],['gatekeeper','Left word'],
    ['callback','Call back tomorrow'],['pagesent','🔗 ' + (on.pagesent||'Quoted')],['won','💰 ' + (on.won||'WON')],['notnow','Not now'],['lost','No']
  ].map(function(o){ return '<button class="og-o o-' + o[0] + '" onclick="TTOG.touch(\'' + q(L.id) + '\',\'' + o[0] + '\')">' + e(o[1]) + '</button>'; }).join('') + '</div>' +
   '<input id="og-note-' + e(L.id) + '" placeholder="Add a note (who you spoke to, the date, the route)"></div>';
  if (P().kind === 'apparel') {
    h += '<div class="og-mk"><b>Build the group page</b><div class="og-row2">' +
      '<label>Tier<select id="og-tier-' + e(L.id) + '">' + ((S.d&&S.d.tiers)||[]).map(function(t){
        return '<option value="' + t.id + '"' + (L.tier===t.id?' selected':'') + '>' + e(t.name) + ' · ' + t.label + ' · $' + t.items.tee.price.toFixed(2) + ' tee</option>'; }).join('') + '</select></label>' +
      '<label>Closes<input type="date" id="og-ends-' + e(L.id) + '" value="' + e(defEnd()) + '"></label>' +
      '<button class="og-b gold" onclick="TTOG.page(\'' + q(L.id) + '\')">Build the page</button></div>' +
      (L.camp ? '<div class="og-live">Page: <a href="' + e(L.url||'') + '" target="_blank" rel="noopener">' + e(L.camp) + '</a> · ' + (L.sold||0) + ' sold · ' + mo(L.money||0) +
        ' <button class="og-b sm" onclick="TTOG.copyText(this,\'' + q(L.url||'') + '\')">Copy link</button></div>' : '') + '</div>';
  }
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
  return new Date(Date.now() + 14*864e5).toISOString().slice(0,10);
}
function vCalls(){
  var d = S.d, due = d.due||[], fresh = d.fresh||[], h = '';
  h += '<h2 class="og-h">☎ Call these now <span class="og-n">' + due.length + '</span></h2>';
  h += due.length ? due.map(leadCard).join('')
    : '<div class="og-empty">Nothing is due. Work the <b>Not called yet</b> list below — 10 calls a day is the whole job.</div>';
  h += '<h2 class="og-h">Not called yet <span class="og-n">' + fresh.length + '</span></h2>';
  if (!fresh.length && !(d.board||{}).total) h += '<div class="og-empty">No leads for ' + e(P().name) + ' yet. Tap <b>Load the starter list</b> at the top, or add one with <b>+ Add a lead</b>.</div>';
  else h += fresh.map(leadCard).join('');
  return h;
}
function vPages(){
  var live = (S.d&&S.d.live)||[];
  if (P().kind !== 'apparel') return '<div class="og-empty">Group pages are for the shirt businesses. For ' + e(P().name) + ', the quote goes out in the script.</div>';
  if (!live.length) return '<div class="og-empty">No pages yet. Open a lead, pick the tier, and tap <b>Build the page</b>.</div>';
  return '<h2 class="og-h">Live pages</h2>' + live.map(function(L){
    var goal = Math.max(1, L.qty||24), pct = Math.min(100, Math.round((L.sold||0)/goal*100));
    return '<div class="og-card"><div class="og-head"><div><b class="og-org">' + e(L.org) + '</b><div class="og-sub">' + e(L.camp) + '</div></div>' + stageChip(L.stage) + '</div>' +
      '<div class="og-bar"><i style="width:' + pct + '%"></i></div><div class="og-sub">' + (L.sold||0) + ' of ' + goal + ' · ' + mo(L.money||0) + ' collected</div>' +
      '<div class="og-acts"><a class="og-b" href="' + e(L.url) + '" target="_blank" rel="noopener">Open the page</a>' +
      '<button class="og-b" onclick="TTOG.copyText(this,\'' + q(L.url) + '\')">Copy link</button>' +
      '<button class="og-b" onclick="TTOG.open(\'' + q(L.id) + '\')">Scripts</button></div>' + (S.open===L.id ? panel(L) : '') + '</div>';
  }).join('');
}
function vPrices(){
  var p = P(), t = (S.d&&S.d.tiers)||[], f = (S.d&&S.d.floor)||35, h = '';
  h += '<h2 class="og-h">What we charge</h2><div class="og-tbl"><table><tbody>' +
    (p.rates||[]).map(function(r){ return '<tr><td><b>' + e(r[0]) + '</b></td><td><b class="og-price">' + e(r[1]) + '</b></td><td class="og-mute">' + e(r[2]||'') + '</td></tr>'; }).join('') +
    '</tbody></table></div>';
  if (p.kind === 'apparel' && t.length) {
    h += '<h2 class="og-h">Live margins</h2><p class="og-mute">Worked out from the real cost table every time this loads. Crewneck and hoodie prices are solved to hold the same share. Nothing goes under ' + f + '%.</p>' +
     '<div class="og-tbl"><table><thead><tr><th>Tier</th><th>Qty</th><th>Tee</th><th>Keeps</th><th>Crewneck</th><th>Hoodie</th></tr></thead><tbody>' +
     t.map(function(x){ return '<tr' + (x.floor_ok?'':' class="bad"') + '><td><b>' + e(x.name) + '</b></td><td>' + e(x.label) + '</td><td><b>$' + x.items.tee.price.toFixed(2) + '</b>' + (x.payout?'<br><small>$' + x.payout.toFixed(2) + ' back</small>':'') + '</td><td>' + x.items.tee.margin + '%<br><small>' + mo(x.items.tee.keep) + '</small></td><td>$' + x.items.crew.price.toFixed(2) + '<br><small>' + x.items.crew.margin + '%</small></td><td>$' + x.items.hood.price.toFixed(2) + '<br><small>' + x.items.hood.margin + '%</small></td></tr>'; }).join('') +
     '</tbody></table></div>';
  }
  h += '<h2 class="og-h">The rules we do not break</h2><ul class="og-rules">' + (p.rules||[]).map(function(r){ return '<li>' + e(r) + '</li>'; }).join('') + '</ul>';
  h += '<h2 class="og-h">Where leads come in</h2><div class="og-card"><p class="og-sub" style="font-size:16px">The free-offer page for ' + e(p.name) + ':</p>' +
   '<div class="og-acts"><a class="og-b gold" href="' + e(p.land||'/') + '" target="_blank" rel="noopener">Open the page</a>' +
   '<button class="og-b" onclick="TTOG.copyText(this,\'' + q(location.origin + (p.land||'/')) + '\')">Copy the link</button>' +
   '<a class="og-b" href="' + e(p.site||'#') + '" target="_blank" rel="noopener">Open the site</a></div></div>';
  return h;
}
function vAll(){
  var all = (S.d&&S.d.all)||[];
  return '<h2 class="og-h">Every lead <span class="og-n">' + all.length + '</span></h2><div class="og-tbl"><table><thead><tr><th>Name</th><th>City</th><th>Phone</th><th>Stage</th><th>Next</th><th>Calls</th></tr></thead><tbody>' +
   all.map(function(L){ return '<tr' + (L.archived?' class="og-arch"':'') + '><td><b>' + e(L.org) + '</b></td><td>' + e(L.city||'') + '</td><td>' + (L.phone? '<a href="tel:' + e(tel(L.phone)) + '">' + e(L.phone) + '</a>' : '<span class="og-mute">look up</span>') + '</td><td>' + stageChip(L.stage||'new') + '</td><td>' + e(L.next||'') + '</td><td>' + (L.touches||0) + '</td></tr>'; }).join('') +
   '</tbody></table></div>';
}
function vAdd(){
  var p = P();
  return '<h2 class="og-h">Add a lead to ' + e(p.name) + '</h2><div class="og-card"><div class="og-form">' +
   '<label>Name of the business or group<input id="ad-org" placeholder="Reunion Resort concierge"></label>' +
   '<label>Who you ask for<input id="ad-contact" placeholder="Front desk manager"></label>' +
   '<label>Phone<input id="ad-phone" type="tel" placeholder="407-555-0148"></label>' +
   '<label>Email<input id="ad-email" type="email" placeholder="name@place.com"></label>' +
   '<label>City<input id="ad-city" placeholder="ChampionsGate"></label>' +
   '<label>Type<select id="ad-seg">' + Object.keys(p.segs||{}).map(function(k){ return '<option value="' + e(k) + '">' + e(p.segs[k]) + '</option>'; }).join('') + '</select></label>' +
   '<label>How many (optional)<input id="ad-qty" type="number" min="0" placeholder="24"></label>' +
   '<label>Where it came from<input id="ad-source" placeholder="Google Maps, referral, walk-in"></label>' +
   '</div><div class="og-acts"><button class="og-b gold" onclick="TTOG.add()">Add it to the call list</button></div></div>';
}

/* ---- edit this business (packs are data, not code) ---- */
var ED = null;
function edLoad(){
  var p = P();
  ED = JSON.parse(JSON.stringify({
    id:p.id||'', name:p.name||'', short:p.short||'', brand:p.brand||'', phone:p.phone||'', email:p.email||'',
    site:p.site||'', land:p.land||'/', kind:p.kind||'service', hook:p.hook||'',
    goal:p.goal||{n:1,label:'won',units:0,unit_label:''},
    segs:p.segs||{}, stage_names:p.stage_names||{}, rates:p.rates||[], rules:p.rules||[],
    deadlines:p.deadlines_raw||[], scripts:p.scripts||[]
  }));
}
function edRow(label, path, ph, type){
  return '<label>' + e(label) + '<input type="' + (type||'text') + '" value="' + e(edGet(path)) + '" placeholder="' + e(ph||'') + '" oninput="TTOG.edSet(\'' + q(path) + '\',this.value)"></label>';
}
function edGet(path){ var o = ED; path.split('.').forEach(function(k){ o = (o||{})[k]; }); return o == null ? '' : o; }
function vEdit(){
  if (!ED) edLoad();
  var h = '<h2 class="og-h">Edit ' + e(ED.name || 'this business') + '</h2>' +
   '<p class="og-mute">Everything here is yours to change: the name, the offer, the segments, the rates, the rules and every script. Nothing is locked in the code.</p>';
  h += '<div class="og-card"><b class="og-lb">The basics</b><div class="og-form">' +
    edRow('Business name','name','GPTS Airport Transfers') + edRow('Short name (for the button)','short','GPTS') +
    edRow('Legal / brand name','brand','Grahams Pristine Transportation Service') +
    edRow('Phone','phone','352-931-3768') + edRow('Email','email','bookings@pristinedrive.online') +
    edRow('Website','site','https://transportationservices.online/') +
    edRow('Free-offer page path','land','/airport-transfers/') +
    '<label>Type<select onchange="TTOG.edSet(\'kind\',this.value)"><option value="service"' + (ED.kind==='service'?' selected':'') + '>Service (quote a price)</option><option value="apparel"' + (ED.kind==='apparel'?' selected':'') + '>Shirts (build a group page)</option></select></label>' +
    '<label class="wide2">The one-line hook<input value="' + e(ED.hook) + '" oninput="TTOG.edSet(\'hook\',this.value)" placeholder="What makes someone say yes"></label>' +
    '</div></div>';
  h += '<div class="og-card"><b class="og-lb">The goal</b><div class="og-form">' +
    edRow('How many to win','goal.n','10','number') + edRow('Called what','goal.label','accounts booked') +
    edRow('Units target','goal.units','40','number') + edRow('Units called what','goal.unit_label','rides') +
    '</div></div>';
  h += '<div class="og-card"><b class="og-lb">Who you call (segments)</b>' +
    '<div id="edSegs">' + Object.keys(ED.segs).map(function(k,i){
      return '<div class="og-r3"><input value="' + e(k) + '" onchange="TTOG.edSegKey(\'' + q(k) + '\',this.value)" placeholder="key"><input value="' + e(ED.segs[k]) + '" oninput="TTOG.edSegVal(\'' + q(k) + '\',this.value)" placeholder="What you call them"><button class="og-b sm" onclick="TTOG.edSegDel(\'' + q(k) + '\')">Remove</button></div>'; }).join('') + '</div>' +
    '<div class="og-acts"><button class="og-b sm" onclick="TTOG.edSegAdd()">+ Add a segment</button></div></div>';
  h += '<div class="og-card"><b class="og-lb">Rates / what you charge</b>' +
    ED.rates.map(function(r,i){ return '<div class="og-r3"><input value="' + e(r[0]||'') + '" oninput="TTOG.edCell(\'rates\',' + i + ',0,this.value)" placeholder="What it is"><input value="' + e(r[1]||'') + '" oninput="TTOG.edCell(\'rates\',' + i + ',1,this.value)" placeholder="Price"><input value="' + e(r[2]||'') + '" oninput="TTOG.edCell(\'rates\',' + i + ',2,this.value)" placeholder="Note"><button class="og-b sm" onclick="TTOG.edDel(\'rates\',' + i + ')">✕</button></div>'; }).join('') +
    '<div class="og-acts"><button class="og-b sm" onclick="TTOG.edAdd(\'rates\')">+ Add a rate</button></div></div>';
  h += '<div class="og-card"><b class="og-lb">Dates that matter</b>' +
    ED.deadlines.map(function(r,i){ return '<div class="og-r3"><input value="' + e(r.name||'') + '" oninput="TTOG.edObj(\'deadlines\',' + i + ',\'name\',this.value)" placeholder="What happens"><input type="date" value="' + e(r.date||'') + '" oninput="TTOG.edObj(\'deadlines\',' + i + ',\'date\',this.value)"><button class="og-b sm" onclick="TTOG.edDel(\'deadlines\',' + i + ')">✕</button></div>'; }).join('') +
    '<div class="og-acts"><button class="og-b sm" onclick="TTOG.edAdd(\'deadlines\')">+ Add a date</button></div></div>';
  h += '<div class="og-card"><b class="og-lb">Rules you do not break</b>' +
    ED.rules.map(function(r,i){ return '<div class="og-r3"><input class="wide2" value="' + e(r) + '" oninput="TTOG.edLine(\'rules\',' + i + ',this.value)"><button class="og-b sm" onclick="TTOG.edDel(\'rules\',' + i + ')">✕</button></div>'; }).join('') +
    '<div class="og-acts"><button class="og-b sm" onclick="TTOG.edAdd(\'rules\')">+ Add a rule</button></div></div>';
  h += '<div class="og-card"><b class="og-lb">Scripts</b><p class="og-mute" style="margin-top:0">Use {who} for their first name, {org} for the business, {contact} for the full name, {land} for the free-offer page, {camp} for the group page.</p>' +
    ED.scripts.map(function(sc,i){
      return '<div class="og-sed"><div class="og-form">' +
        '<label>Name<input value="' + e(sc.name||'') + '" oninput="TTOG.edObj(\'scripts\',' + i + ',\'name\',this.value)"></label>' +
        '<label>Kind<select onchange="TTOG.edObj(\'scripts\',' + i + ',\'kind\',this.value)"><option value="send"' + (sc.kind!=='say'?' selected':'') + '>Send it</option><option value="say"' + (sc.kind==='say'?' selected':'') + '>Say it</option></select></label>' +
        '<label>Use it for<select onchange="TTOG.edObj(\'scripts\',' + i + ',\'seg\',this.value)"><option value="">Any</option>' + Object.keys(ED.segs).map(function(k){ return '<option value="' + e(k) + '"' + (sc.seg===k?' selected':'') + '>' + e(ED.segs[k]) + '</option>'; }).join('') + '</select></label>' +
        '<label>Subject (email only)<input value="' + e(sc.subject||'') + '" oninput="TTOG.edObj(\'scripts\',' + i + ',\'subject\',this.value)"></label>' +
        '<label class="wide2">What it says<textarea rows="7" oninput="TTOG.edObj(\'scripts\',' + i + ',\'body\',this.value)">' + e(sc.body||'') + '</textarea></label>' +
        '</div><div class="og-acts"><button class="og-b sm" onclick="TTOG.edDel(\'scripts\',' + i + ')">Remove this script</button></div></div>'; }).join('') +
    '<div class="og-acts"><button class="og-b sm" onclick="TTOG.edAdd(\'scripts\')">+ Add a script</button></div></div>';
  h += '<div class="og-card"><div class="og-acts">' +
    '<button class="og-b gold" onclick="TTOG.edSave()">Save this business</button>' +
    '<button class="og-b" onclick="TTOG.edNew()">+ Start a new business</button>' +
    '<button class="og-b" onclick="TTOG.edReset()">Put it back the way it was</button>' +
    '<button class="og-b" onclick="TTOG.edCancel()">Cancel</button></div></div>';
  return h;
}

var VIEWS = [['calls','☎ Calls'],['pages','🔗 Pages'],['prices','💰 Prices + rules'],['all','📋 Every lead'],['add','➕ Add a lead']];
function draw(){
  var b = $('ogBody'); if (!b) return;
  if (!S.d) { b.innerHTML = bizBar() + '<div class="og-empty">Loading… ' + e(S.msg) + '</div>'; return; }
  if (S.d.error) { b.innerHTML = bizBar() + '<div class="og-err">' + e(S.d.error) + '</div>'; return; }
  var h = bizBar() + clock() + score();
  h += '<div class="og-nav">' + VIEWS.map(function(v){ return '<button class="og-v' + (S.view===v[0]?' on':'') + '" onclick="TTOG.view(\'' + v[0] + '\')">' + v[1] + (v[0]==='calls'&&(S.d.due||[]).length?' <span class="og-n">'+(S.d.due||[]).length+'</span>':'') + '</button>'; }).join('') +
   (SEED[S.biz] || P().has_seed ? '<button class="og-v" onclick="TTOG.importList(this)">⬇ Load the starter list</button>' : '') +
   '<button class="og-v" onclick="TTOG.refresh()">↻ Refresh</button></div>';
  if (S.msg) h += '<div class="og-msg">' + e(S.msg) + '</div>';
  h += ({calls:vCalls, pages:vPages, prices:vPrices, all:vAll, add:vAdd, edit:vEdit})[S.view]();
  b.innerHTML = h;
}
var CSS = '<style>' +
'.og-wrap{font:16px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;color:#2A241E;max-width:1100px;margin:0 auto}' +
'.og-wrap h2.og-h{font-size:1.35rem;margin:22px 0 10px;display:flex;align-items:center;gap:9px}' +
'.og-n{background:#E11D48;color:#fff;border-radius:999px;font:800 13px/1 system-ui;padding:4px 9px}' +
'.og-c{background:rgba(0,0,0,.18);border-radius:999px;font:800 12px/1 system-ui;padding:3px 7px;margin-left:4px}' +
'.og-mute{color:#6b5d4a}.og-wrap small{color:#6b5d4a}.og-price{color:#9a7b2a;font-size:1.1rem}' +
'.og-biz{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:12px;border-bottom:3px solid #efe4d0}' +
'.og-bz{appearance:none;border:2px solid #2A241E;background:#fff;color:#2A241E;font:800 16px/1 system-ui;padding:13px 18px;border-radius:12px;cursor:pointer}' +
'.og-bz.on{background:#14110D;color:#F3D27A;border-color:#14110D}' +
'.og-clock{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}' +
'.og-cl{flex:1 1 150px;background:#FBF6EE;border:2px solid #efe4d0;border-radius:12px;padding:10px 12px}' +
'.og-cl b{display:block;font-size:1.4rem;line-height:1.1}.og-cl span{display:block;font-size:13px;font-weight:700;color:#5b4d3a}.og-cl em{font-style:normal;font-size:12px;color:#8b7d66}' +
'.og-cl.hot{border-color:#E11D48;background:#fff1f3}.og-cl.hot b{color:#E11D48}.og-cl.gone{opacity:.5}' +
'.og-score{display:grid;grid-template-columns:1.3fr 1fr;gap:12px;align-items:start}' +
'.og-goal{background:#14110D;color:#F3D27A;border-radius:14px;padding:14px 16px}' +
'.og-big{font-size:1.5rem;font-weight:800;line-height:1.2}.og-goal .og-mute{color:#c9b793}' +
'.og-hook{color:#e8dcc0;font-size:14px;margin-top:9px;border-top:1px solid #3a3022;padding-top:9px}' +
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
'.og-org{font-size:1.2rem;line-height:1.25;display:block}.og-sub{font-size:14px;color:#6b5d4a;margin-top:2px}' +
'.og-last{background:#FBF6EE;border-left:4px solid #C9A24E;border-radius:8px;padding:7px 10px;margin:9px 0;font-size:14px}' +
'.og-st{font-style:normal;font-size:12px;font-weight:800;border-radius:999px;padding:4px 10px;white-space:nowrap;background:#f1ece2;color:#5b4d3a}' +
'.og-st.s-interested,.og-st.s-proof{background:#fef3c7;color:#92400e}.og-st.s-won{background:#e3f3ea;color:#1c5340}.og-st.s-lost{background:#f3f4f6;color:#6b7280}.og-st.s-quote{background:#e0e7ff;color:#3730a3}' +
'.og-acts{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}' +
'.og-b{appearance:none;border:2px solid #2A241E;background:#fff;color:#2A241E;font:800 15px/1 system-ui;padding:12px 15px;border-radius:12px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}' +
'.og-b.call{background:#1F8A4C;border-color:#1F8A4C;color:#fff;font-size:1.05rem}' +
'.og-b.gold{background:#C9A24E;border-color:#C9A24E;color:#14110D}' +
'.og-b.sm{font-size:13px;padding:7px 11px;border-width:1px}' +
'.og-b.warn{background:#fff1f3;border-color:#E11D48;color:#9f1239}' +
'.og-panel{margin-top:12px;border-top:2px dashed #e7dcc8;padding-top:12px}.og-panel b{display:block;margin-bottom:7px;font-size:15px}' +
'.og-outs{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:7px;margin-bottom:9px}' +
'.og-o{appearance:none;border:2px solid #d9c9a8;background:#FBF6EE;font:800 14px/1.2 system-ui;padding:12px 8px;border-radius:11px;cursor:pointer;color:#2A241E}' +
'.og-o.o-interested,.og-o.o-proofsent{border-color:#C9A24E;background:#fef3c7}.og-o.o-won{border-color:#1F8A4C;background:#e3f3ea;color:#14532d}.og-o.o-lost{border-color:#d1d5db;background:#f9fafb;color:#6b7280}' +
'.og-panel input,.og-panel select,.og-form input,.og-form select{font:inherit;font-size:15px;border:2px solid #e0d3c0;border-radius:10px;padding:10px 11px;width:100%;box-sizing:border-box}' +
'.og-row2{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end}.og-row2 label,.og-form label{display:flex;flex-direction:column;gap:4px;font-size:13px;font-weight:700;color:#5b4d3a}' +
'.og-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}' +
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
'.og-lb{display:block;font-size:1.05rem;margin-bottom:9px}' +
'.og-r3{display:grid;grid-template-columns:1fr 1fr auto;gap:7px;margin-bottom:7px;align-items:center}' +
'.og-r3 input,.og-form input,.og-form select,.og-form textarea{font:inherit;font-size:15px;border:2px solid #e0d3c0;border-radius:10px;padding:10px 11px;width:100%;box-sizing:border-box}' +
'.og-form .wide2{grid-column:1/-1}.og-r3 .wide2{grid-column:auto}' +
'.og-sed{border:2px solid #efe4d0;border-radius:12px;padding:11px;margin-bottom:10px;background:#FBF6EE}' +
'.og-form textarea{min-height:110px;line-height:1.5}' +
'@media(max-width:820px){.og-score{grid-template-columns:1fr}.og-row2,.og-form{grid-template-columns:1fr}.og-r3{grid-template-columns:1fr}.og-org{font-size:1.1rem}.og-bz{font-size:14px;padding:11px 13px}}' +
'</style>';
window.TTOG = {
  has: function(t){ return !!TABS[t]; },
  html: function(){ setTimeout(function(){ if (!S.d) load(); else draw(); }, 0); return CSS + '<div class="og-wrap"><div id="ogBody"><div class="og-empty">Loading…</div></div></div>'; },
  bind: function(){ draw(); },
  biz: function(b){ if (b === S.biz) return; ED = null; S.biz = b; try { localStorage.setItem('ttMachBiz', b); } catch(e){} S.open=''; S.msg=''; S.d=null; S.view='calls'; draw(); load(); window.scrollTo(0,0); },
  view: function(v){ if (v === 'edit') edLoad(); S.view = v; S.msg = ''; draw(); window.scrollTo(0,0); },
  refresh: function(){ S.msg = ''; S.d = null; draw(); load(); },
  open: function(id){
    S.open = (S.open === id ? '' : id); S.msg = ''; draw();
    if (S.open && !S.scripts[id]) get('?a=scripts&id=' + encodeURIComponent(id)).then(function(d){ if (d.scripts){ S.scripts[id] = d.scripts; draw(); } });
  },
  edSet: function(path, v){ var ks = path.split('.'), o = ED; for (var i=0;i<ks.length-1;i++) o = o[ks[i]] = o[ks[i]] || {}; o[ks[ks.length-1]] = v; },
  edCell: function(k, i, c, v){ ED[k][i] = ED[k][i] || []; ED[k][i][c] = v; },
  edLine: function(k, i, v){ ED[k][i] = v; },
  edObj: function(k, i, f, v){ ED[k][i] = ED[k][i] || {}; ED[k][i][f] = v; if (f === 'seg' || f === 'kind') draw(); },
  edAdd: function(k){ if (k === 'rates') ED.rates.push(['','','']); else if (k === 'rules') ED.rules.push(''); else if (k === 'deadlines') ED.deadlines.push({name:'',date:''}); else ED.scripts.push({k:'s'+Date.now(),name:'New script',kind:'send',seg:'',subject:'',body:''}); draw(); },
  edDel: function(k, i){ ED[k].splice(i,1); draw(); },
  edSegAdd: function(){ var n = 1; while (ED.segs['seg'+n]) n++; ED.segs['seg'+n] = 'New segment'; draw(); },
  edSegDel: function(k){ delete ED.segs[k]; draw(); },
  edSegVal: function(k, v){ ED.segs[k] = v; },
  edSegKey: function(k, nk){ nk = String(nk||'').toLowerCase().replace(/[^a-z0-9_]/g,''); if (!nk || nk === k || ED.segs[nk]) return draw(); var o = {}; Object.keys(ED.segs).forEach(function(x){ o[x===k?nk:x] = ED.segs[x]; }); ED.segs = o; draw(); },
  edNew: function(){ ED = {id:'', name:'New business', short:'New', brand:'', phone:'352-931-3768', email:'', site:'', land:'/new-offer/', kind:'service', hook:'', goal:{n:5,label:'clients won',units:0,unit_label:''}, segs:{business:'Business'}, stage_names:{}, rates:[['','','']], rules:[''], deadlines:[], scripts:[{k:'call',name:'Phone script',kind:'say',seg:'',subject:'',body:'Hi, this is Dr. Graham with [business]. '}]}; S.view='edit'; S.msg='New business. Give it a name and a short name, then save.'; draw(); window.scrollTo(0,0); },
  edCancel: function(){ ED = null; S.view = 'calls'; S.msg = ''; draw(); },
  edReset: function(btn){ if (!confirm('Put ' + (P().name||'this business') + ' back the way it shipped? Your edits to it are lost.')) return;
    post({a:'pack_reset', pack_id:S.biz}).then(function(r){ S.msg = r.error || 'Put back the way it was.'; ED = null; S.d = null; draw(); load(); }); },
  edSave: function(){
    if (S.busy) return;
    if (!String(ED.name||'').trim()){ S.msg = 'Give it a name first.'; draw(); return; }
    if (!String(ED.id||'').trim()) ED.id = String(ED.short || ED.name).toLowerCase().replace(/[^a-z0-9]/g,'').slice(0,20) || ('biz' + Date.now());
    S.busy = true; S.msg = 'Saving…'; draw();
    post({a:'pack_save', pack:ED}).then(function(r){
      S.busy = false;
      if (r.error){ S.msg = r.error; draw(); return; }
      S.msg = 'Saved.'; S.biz = r.id; try { localStorage.setItem('ttMachBiz', r.id); } catch(e){}
      ED = null; S.view = 'calls'; S.d = null; draw(); load();
    });
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
      S.busy = false; S.msg = r.error ? r.error : (r.msg + ' ' + r.url);
      S.d = null; draw(); load();
    });
  },
  add: function(){
    if (S.busy) return; S.busy = true;
    var g = function(k){ var el = $('ad-' + k); return el ? el.value.trim() : ''; };
    if (!g('org')){ S.busy = false; S.msg = 'Type the name first.'; draw(); return; }
    post({a:'save', org:g('org'), contact:g('contact'), phone:g('phone'), email:g('email'), city:g('city'), seg:g('seg'), qty:g('qty'), source:g('source')}).then(function(r){
      S.busy = false; S.msg = r.error ? r.error : 'Added. It is in the call list now.';
      if (!r.error) S.view = 'calls';
      S.d = null; draw(); load();
    });
  },
  importList: function(btn){
    if (S.busy) return; S.busy = true;
    var o = btn.textContent; btn.textContent = 'Loading…';
    post({a:'import', rows: SEED[S.biz] || []}).then(function(r){
      S.busy = false; btn.textContent = o;
      S.msg = r.error ? r.error : ('Added ' + r.added + ' leads' + (r.skipped ? ', skipped ' + r.skipped + ' already there' : '') + '.');
      S.d = null; draw(); load();
    });
  }
};
(function(){
  function add(){
    var nav = document.getElementById('tabs');
    if (!nav || !nav.querySelector('button[data-t]')) return setTimeout(add, 250);
    var b = nav.querySelector('button[data-t="orl"]');
    if (!b) { b = document.createElement('button'); b.type = 'button'; b.dataset.t = 'orl'; b.setAttribute('aria-pressed','false'); nav.insertBefore(b, nav.firstChild); }
    b.innerHTML = '🎯 The Outreach Machine <span class="n" id="n-orl" hidden></span>';
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
})();
})();
F92F8730E;
$NEW['group-orders/index.html'] = <<<'F83D3B453'
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
  o.biz='orlando';
  fetch('/api/machine.php?a=request', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(o)})
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
F83D3B453;
$NEW['airport-transfers/index.html'] = <<<'FF42DE7C7'
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orlando &amp; Tampa Airport Transfers, Flat Rate | GPTS</title>
<meta name="description" content="Flat-rate airport transfers from Orlando (MCO) and Tampa, and vacation-home transfers across Central Florida. No surge, no app. Call or text 352-931-3768.">
<meta name="theme-color" content="#14110D">
<link rel="canonical" href="https://transportationservices.online/airport-transfers/">
<meta property="og:type" content="website"><meta property="og:title" content="Orlando &amp; Tampa Airport Transfers, Flat Rate | GPTS">
<meta property="og:description" content="Flat-rate airport transfers from Orlando (MCO) and Tampa, and vacation-home transfers across Central Florida. No surge, no app. Call or text 352-931-3768."><meta property="og:url" content="https://transportationservices.online/airport-transfers/">
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
</style>
<style>h1 span{color:var(--gold2)}</style></head><body>

<section class="band"><div class="w">
  <div class="hero">
    <div>
      <span class="eyebrow">CENTRAL FLORIDA &middot; MCO &amp; TAMPA</span>
      <h1>Your guests land.<br>Someone they can <span>trust</span> is waiting.</h1>
      <p class="lead">Flat-rate airport transfers from Orlando and Tampa, and vacation-home transfers all over Central Florida. <b>No surge pricing, no app, no stranger.</b> One number, one price, a clean late-model SUV and a professional chauffeur.</p>
      <div class="cta"><a class="btn" href="#ask">Hold a flat rate for my guests</a><a class="btn ghost" href="#prices">See the rates</a></div>
      <p class="micro">Call or text <b>352-931-3768</b>. Flight tracked, so a delay costs you nothing.</p>
    </div>
    <div>
      <div class="step" style="border-left-color:var(--gold2)">
        <h3 style="margin-bottom:10px">Why properties call us</h3>
        <p style="margin:0 0 10px"><b style="color:var(--gold2)">One answer.</b> When a guest asks how to get from the airport, you have a name instead of a shrug.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Flat rate, locked.</b> No surge at 2am on a holiday. The price you quote is the price they pay.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Direct line, no app.</b> Your guest texts a person, not a dispatch screen.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Local, not a fleet.</b> Davenport-based. Orlando, Kissimmee, Four Corners, ChampionsGate, Tampa.</p>
      </div>
      <div class="clock" id="clock"></div>
    </div>
  </div>
</div></section>

<section class="band alt"><div class="w">
  <h2>Three steps, and it's handled</h2>
  <div class="steps"><div class="step"><b class="num">1</b><h3>Tell us the route</h3><p>Pickup, drop-off, date and time. A text is enough.</p></div><div class="step"><b class="num">2</b><h3>We lock the flat rate</h3><p>Written, in advance. Flight tracked, so a delay costs nothing.</p></div><div class="step"><b class="num">3</b><h3>Your guest gets a chauffeur</h3><p>Clean late-model SUV, on time, and a direct number if anything changes.</p></div></div>
  <div class="seals"><span class="seal">&#9989; Flat rate, no surge</span><span class="seal">&#9989; Flight tracked</span><span class="seal">&#9989; Orlando (MCO) &amp; Tampa</span><span class="seal">&#9989; Vacation-home transfers</span><span class="seal">&#9989; Text to book</span><span class="seal">&#9989; Professional chauffeur</span></div>
</div></section>

<section class="band" id="prices"><div class="w">
  <h2>Rates</h2>
  <p class="note">Prices on our sites are final. Davenport to Tampa is priced the same as Davenport to Orlando, each way.</p>
  <div class="tw"><table><tbody><tr><td>Orlando (MCO) airport transfer</td><td><b>from $49.95</b></td><td class="note">Flat, each way</td></tr><tr class="hi"><td>Tampa (TPA) airport transfer</td><td><b>same as Orlando</b></td><td class="note">Flat, each way</td></tr><tr><td>Vacation-home transfer</td><td><b>from $49.95</b></td><td class="note">Davenport, Kissimmee, Four Corners, ChampionsGate</td></tr><tr><td>Hourly / as-directed</td><td><b>quoted</b></td><td class="note">Events, multi-stop, waiting time</td></tr></tbody></table></div>
  <p class="note" style="margin-top:14px">A holiday rate is held for property and planner accounts through <b>New Year's week</b>. Ask and it's yours.</p>
</div></section>

<section class="band alt" id="ask"><div class="w">
  <h2>Hold a flat rate for your guests</h2>
  <p class="lead" style="max-width:52ch">Tell us who you are and Dr. Graham sends the rate card, plus cards for your front desk. No contract, no minimum.</p>
  <form id="f" onsubmit="return send(event)">
    <div id="err" class="bad" hidden></div>
    <div class="f2">
      <label>Your name<input name="contact" required autocomplete="name" placeholder="Maria Santos"></label>
      <label>Property, company or group<input name="org" required placeholder="Reunion Resort &mdash; concierge"></label>
      <label>Phone<input name="phone" type="tel" autocomplete="tel" placeholder="407-555-0148"></label>
      <label>Email<input name="email" type="email" autocomplete="email" placeholder="you@email.com"></label>
      <label>Type<select name="seg"><option value="hotel">Hotel or vacation-home manager</option><option value="planner">Wedding or event planner</option><option value="corp">Corporate office</option><option value="senior">Medical or senior living</option><option value="travel">Travel agent or concierge</option></select></label>
      <label>About how many guests a month?<input name="qty" type="number" min="0" max="20000" placeholder="20"></label>
      <label>What do you need most?<input name="event" placeholder="Airport pickups for guests"></label>
      <label>First date you'd need us<input name="evdate" type="date"></label>
      <label class="wide">Anything else? (optional)<textarea name="notes" rows="3" placeholder="Most of our guests fly into MCO on Saturdays"></textarea></label>
      <label class="wide">City<input name="city" placeholder="Orlando"></label>
    </div>
    <button class="btn" type="submit" id="sb" style="width:100%">Send me the rate card</button>
    <p class="note" style="margin:12px 0 0">We use your number to send the rate card. No spam, no list.</p>
  </form>
  <div id="done" hidden class="ok"></div>
</div></section>

<section class="band"><div class="w">
  <h2>Questions we get</h2>
  <div class="faq"><div><h3>Is the price really flat?</h3><p>Yes. Written in advance, each way. No surge, no extra for traffic, no holiday markup.</p></div><div><h3>What if the flight is late?</h3><p>We track it. The pickup moves with the flight and it costs you nothing.</p></div><div><h3>Do you cover Tampa?</h3><p>Yes. Davenport to Tampa is priced the same as Davenport to Orlando, each way.</p></div><div><h3>Can guests book directly?</h3><p>Yes. They call or text 352-931-3768, or you send them. Either way the rate holds.</p></div><div><h3>What's the vehicle?</h3><p>A clean late-model SUV with a professional chauffeur. Not a rideshare.</p></div><div><h3>Do I sign anything?</h3><p>No. No contract, no minimum, no monthly fee. We earn it one ride at a time.</p></div></div>
</div></section>

<footer><div class="w">
  <b style="color:var(--gold2)">Grahams Pristine Transportation Service</b> &middot; Airport and chauffeur transfers &middot; Central Florida &middot; Dr. Robert D. Graham<br>
  <a href="tel:3529313768">352-931-3768</a> &middot; <a href="mailto:bookings@pristinedrive.online">bookings@pristinedrive.online</a> &middot; <a href="https://transportationservices.online/">Book a ride</a><br>
  <span style="opacity:.75">Excellence Is A Habit &middot; Built By Faith</span>
</div></footer>

<div class="sticky"><a class="btn" href="#ask">Hold a flat rate for my guests</a></div>

<script>
(function(){
  var D = [['2026-11-24','Thanksgiving travel week'],['2026-12-12','Christmas order cutoff'],['2026-12-19','Christmas travel week']];
  var now = new Date(); now.setHours(0,0,0,0);
  var h = D.map(function(x){
    var d = Math.round((new Date(x[0]+'T00:00:00') - now)/864e5);
    return d < 0 ? '' : '<div class="cl"><b>' + d + (d===1?' day':' days') + '</b><span>' + x[1] + '</span></div>';
  }).join('');
  var el = document.getElementById('clock'); if (el) el.innerHTML = h;
})();
function send(ev){
  ev.preventDefault();
  var f = document.getElementById('f'), b = document.getElementById('sb'), er = document.getElementById('err');
  var o = {biz:'gpts'}; [].forEach.call(f.elements, function(el){ if (el.name) o[el.name] = el.value.trim(); });
  if (!o.phone && !o.email){ er.hidden = false; er.textContent = 'Please add a phone number or an email so we can reach you.'; return false; }
  er.hidden = true; b.disabled = true; var lbl = b.textContent; b.textContent = 'Sending\u2026';
  fetch('/api/machine.php?a=request', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(o)})
   .then(function(r){ return r.json(); })
   .then(function(r){
     b.disabled = false; b.textContent = lbl;
     if (r.error){ er.hidden = false; er.textContent = r.error; return; }
     f.hidden = true;
     var d = document.getElementById('done'); d.hidden = false;
     d.innerHTML = '\u2705 ' + (r.msg || 'Got it.') + '<br><br>Watch your phone and your email. To talk it through right now, call Dr. Graham at <a href="tel:3529313768">352-931-3768</a>.';
     d.scrollIntoView({behavior:'smooth', block:'center'});
   })
   .catch(function(){ b.disabled = false; b.textContent = lbl; er.hidden = false; er.textContent = 'Could not send just now. Please call or text 352-931-3768.'; });
  return false;
}
</script>
</body></html>
FF42DE7C7;
$NEW['fundraising/index.html'] = <<<'F2CBDBC76'
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Zero-Risk Shirt Fundraisers | Grahams Global Fundraising Group</title>
<meta name="description" content="Your church, school or team keeps $5 from every shirt sold and puts up nothing. Free design, online ordering, no inventory, no money to collect.">
<meta name="theme-color" content="#14110D">
<link rel="canonical" href="https://grahamsglobalfundraisinggroup.com/fundraising/">
<meta property="og:type" content="website"><meta property="og:title" content="Zero-Risk Shirt Fundraisers | Grahams Global Fundraising Group">
<meta property="og:description" content="Your church, school or team keeps $5 from every shirt sold and puts up nothing. Free design, online ordering, no inventory, no money to collect."><meta property="og:url" content="https://grahamsglobalfundraisinggroup.com/fundraising/">
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
</style>
<style>h1 span{color:var(--gold2)}</style></head><body>

<section class="band"><div class="w">
  <div class="hero">
    <div>
      <span class="eyebrow">CHURCHES &middot; SCHOOLS &middot; TEAMS</span>
      <h1>Raise money.<br>Put up <span>nothing</span>.</h1>
      <p class="lead">We design the shirt free, your people order online, and your group keeps <b>$5 from every shirt sold</b>. No inventory in a closet. No order forms. No cash to count. <b>We only succeed when you do.</b></p>
      <div class="cta"><a class="btn" href="#ask">Start my free fundraiser</a><a class="btn ghost" href="#prices">See how it works</a></div>
      <p class="micro">No upfront cost. No deposit. No minimum. If nobody orders, you owe nothing.</p>
    </div>
    <div>
      <div class="step" style="border-left-color:var(--gold2)">
        <h3 style="margin-bottom:10px">Why leaders say yes</h3>
        <p style="margin:0 0 10px"><b style="color:var(--gold2)">Nothing up front.</b> Not a deposit, not a minimum. You risk zero dollars.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">No money to collect.</b> Every person pays for their own shirt online. You never touch it.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Free design in 24 hours.</b> Three options, made for your group, at no charge.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">We send the check.</b> When the campaign closes, your payout comes to you.</p>
      </div>
      <div class="clock" id="clock"></div>
    </div>
  </div>
</div></section>

<section class="band alt"><div class="w">
  <h2>Three steps to a fundraiser</h2>
  <div class="steps"><div class="step"><b class="num">1</b><h3>Tell us the group</h3><p>Who you are and what you're raising for. One minute.</p></div><div class="step"><b class="num">2</b><h3>We design it free</h3><p>Three shirt designs in 24 hours. You pick one.</p></div><div class="step"><b class="num">3</b><h3>Share one link</h3><p>Your people order and pay. Your group keeps $5 a shirt. We press and deliver.</p></div></div>
  <div class="seals"><span class="seal">&#9989; $0 to start</span><span class="seal">&#9989; $5 back per shirt</span><span class="seal">&#9989; Free design</span><span class="seal">&#9989; No inventory</span><span class="seal">&#9989; Sizes S to 5XL</span><span class="seal">&#9989; Pressed in Orlando</span></div>
</div></section>

<section class="band" id="prices"><div class="w">
  <h2>What your group keeps</h2>
  <p class="note">One price, one payout, nothing hidden. 50 shirts puts $250 in your group's hands.</p>
  <div class="tw"><table><tbody><tr><td>Fundraiser shirt</td><td><b>$30.00</b></td><td class="note">What each supporter pays</td></tr><tr class="hi"><td>Your group keeps</td><td><b>$5.00 per shirt</b></td><td class="note">Paid out when the campaign closes</td></tr><tr><td>Your cost to start</td><td><b>$0.00</b></td><td class="note">No deposit, no minimum, no inventory</td></tr><tr><td>50 shirts sold</td><td><b>$250 raised</b></td><td class="note">A choir, a youth group, one Sunday</td></tr></tbody></table></div>
  <p class="note" style="margin-top:14px">Christmas orders close <b>December 12</b>. Start now and you still have a season.</p>
</div></section>

<section class="band alt" id="ask"><div class="w">
  <h2>Start your free fundraiser</h2>
  <p class="lead" style="max-width:52ch">Tell us about the group and Dr. Graham sends three shirt designs within 24 hours. No cost, no obligation.</p>
  <form id="f" onsubmit="return send(event)">
    <div id="err" class="bad" hidden></div>
    <div class="f2">
      <label>Your name<input name="contact" required autocomplete="name" placeholder="Pastor James Hall"></label>
      <label>Church, school, team or group<input name="org" required placeholder="New Bethel AME"></label>
      <label>Phone<input name="phone" type="tel" autocomplete="tel" placeholder="407-555-0148"></label>
      <label>Email<input name="email" type="email" autocomplete="email" placeholder="you@email.com"></label>
      <label>Type<select name="seg"><option value="church">Church or ministry</option><option value="youth">School or team</option><option value="business">Nonprofit</option><option value="reunion">Community group</option></select></label>
      <label>About how many people?<input name="qty" type="number" min="0" max="20000" placeholder="60"></label>
      <label>What are you raising for?<input name="event" placeholder="Youth mission trip"></label>
      <label>When do you need the money by?<input name="evdate" type="date"></label>
      <label class="wide">Anything you want on the shirt? (optional)<textarea name="notes" rows="3" placeholder="Church name in gold, our colors are royal blue and white"></textarea></label>
      <label class="wide">City<input name="city" placeholder="Orlando"></label>
    </div>
    <button class="btn" type="submit" id="sb" style="width:100%">Send me 3 free designs</button>
    <p class="note" style="margin:12px 0 0">We use your number only to send the designs. No spam, no list.</p>
  </form>
  <div id="done" hidden class="ok"></div>
</div></section>

<section class="band"><div class="w">
  <h2>Questions leaders ask</h2>
  <div class="faq"><div><h3>What does it cost us?</h3><p>Nothing. Not a deposit, not a minimum, not a setup fee. You risk zero dollars.</p></div><div><h3>What if only a few people order?</h3><p>Then you keep $5 for each of those and owe nothing. There is no floor to hit.</p></div><div><h3>Do we have to collect money?</h3><p>No. Each supporter pays for their own shirt online. You never handle cash.</p></div><div><h3>How do we get paid?</h3><p>When the campaign closes we total it up and send your group the payout.</p></div><div><h3>How long does it take?</h3><p>Designs in 24 hours. Most campaigns run two to three weeks, then we press and deliver.</p></div><div><h3>Can we do this again?</h3><p>Most groups run one every season. We keep your design on file.</p></div></div>
</div></section>

<footer><div class="w">
  <b style="color:var(--gold2)">Grahams Global Fundraising Group</b> &middot; Zero-risk fundraising for churches, schools and teams &middot; Dr. Robert D. Graham<br>
  <a href="tel:3529313768">352-931-3768</a> &middot; <a href="mailto:support@threadedtruthsapparelco.com">support@threadedtruthsapparelco.com</a> &middot; <a href="https://grahamsglobalfundraisinggroup.com/">About GGFG</a><br>
  <span style="opacity:.75">Excellence Is A Habit &middot; Built By Faith</span>
</div></footer>

<div class="sticky"><a class="btn" href="#ask">Start my free fundraiser</a></div>

<script>
(function(){
  var D = [['2026-11-24','Thanksgiving travel week'],['2026-12-12','Christmas order cutoff'],['2026-12-19','Christmas travel week']];
  var now = new Date(); now.setHours(0,0,0,0);
  var h = D.map(function(x){
    var d = Math.round((new Date(x[0]+'T00:00:00') - now)/864e5);
    return d < 0 ? '' : '<div class="cl"><b>' + d + (d===1?' day':' days') + '</b><span>' + x[1] + '</span></div>';
  }).join('');
  var el = document.getElementById('clock'); if (el) el.innerHTML = h;
})();
function send(ev){
  ev.preventDefault();
  var f = document.getElementById('f'), b = document.getElementById('sb'), er = document.getElementById('err');
  var o = {biz:'ggfg'}; [].forEach.call(f.elements, function(el){ if (el.name) o[el.name] = el.value.trim(); });
  if (!o.phone && !o.email){ er.hidden = false; er.textContent = 'Please add a phone number or an email so we can reach you.'; return false; }
  er.hidden = true; b.disabled = true; var lbl = b.textContent; b.textContent = 'Sending\u2026';
  fetch('/api/machine.php?a=request', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(o)})
   .then(function(r){ return r.json(); })
   .then(function(r){
     b.disabled = false; b.textContent = lbl;
     if (r.error){ er.hidden = false; er.textContent = r.error; return; }
     f.hidden = true;
     var d = document.getElementById('done'); d.hidden = false;
     d.innerHTML = '\u2705 ' + (r.msg || 'Got it.') + '<br><br>Watch your phone and your email. To talk it through right now, call Dr. Graham at <a href="tel:3529313768">352-931-3768</a>.';
     d.scrollIntoView({behavior:'smooth', block:'center'});
   })
   .catch(function(){ b.disabled = false; b.textContent = lbl; er.hidden = false; er.textContent = 'Could not send just now. Please call or text 352-931-3768.'; });
  return false;
}
</script>
</body></html>
F2CBDBC76;
$NEW['web-audit/index.html'] = <<<'FB053B811'
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Free AI &amp; Website Audit | 3G&rsquo;s AI Web Solutions</title>
<meta name="description" content="A free written audit of your website: what is slowing it down, where you are losing leads, and the AI that would catch them. You keep the report either way.">
<meta name="theme-color" content="#14110D">
<link rel="canonical" href="https://3gswebsolutions.shop/web-audit/">
<meta property="og:type" content="website"><meta property="og:title" content="Free AI &amp; Website Audit | 3G&rsquo;s AI Web Solutions">
<meta property="og:description" content="A free written audit of your website: what is slowing it down, where you are losing leads, and the AI that would catch them. You keep the report either way."><meta property="og:url" content="https://3gswebsolutions.shop/web-audit/">
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
</style>
<style>h1 span{color:var(--gold2)}</style></head><body>

<section class="band"><div class="w">
  <div class="hero">
    <div>
      <span class="eyebrow">FOR LOCAL BUSINESSES</span>
      <h1>Find out what your<br>website is <span>costing you</span>.</h1>
      <p class="lead">A free, written AI and website audit. What's slowing your site down, where visitors leave before they contact you, and the three AI tools that would answer and capture leads around the clock. <b>You keep the report either way.</b></p>
      <div class="cta"><a class="btn" href="#ask">Run my free audit</a><a class="btn ghost" href="#prices">See the packages</a></div>
      <p class="micro">No pitch on the call. Takes about twenty minutes of our time, none of yours.</p>
    </div>
    <div>
      <div class="step" style="border-left-color:var(--gold2)">
        <h3 style="margin-bottom:10px">What you get, free</h3>
        <p style="margin:0 0 10px"><b style="color:var(--gold2)">Speed report.</b> What's slow, on which pages, and what it's costing in lost visitors.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Leak map.</b> Where people leave before they call, book or buy.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Three AI moves.</b> The specific tools that would catch the leads you're losing now.</p><p style="margin:0 0 10px"><b style="color:var(--gold2)">Yours to keep.</b> A written report, whether or not we ever work together.</p>
      </div>
      <div class="clock" id="clock"></div>
    </div>
  </div>
</div></section>

<section class="band alt"><div class="w">
  <h2>How the audit works</h2>
  <div class="steps"><div class="step"><b class="num">1</b><h3>You send the address</h3><p>Your website and the best email. That's all we need.</p></div><div class="step"><b class="num">2</b><h3>We run it</h3><p>About twenty minutes of our time. No access to anything, no install.</p></div><div class="step"><b class="num">3</b><h3>You get the report</h3><p>Written, plain English, with the three biggest items marked at the top.</p></div></div>
  <div class="seals"><span class="seal">&#9989; Free, no obligation</span><span class="seal">&#9989; No access needed</span><span class="seal">&#9989; Written report</span><span class="seal">&#9989; 30-day guarantee on all work</span><span class="seal">&#9989; Built by faith</span><span class="seal">&#9989; Backed by results</span></div>
</div></section>

<section class="band" id="prices"><div class="w">
  <h2>If you want us to fix it</h2>
  <p class="note">Only if you want it. Every package carries a 30-day money-back guarantee, an onboarding kit and a kickoff call.</p>
  <div class="tw"><table><tbody><tr><td>Quick Fix</td><td><b>$297</b></td><td class="note">One problem, fixed fast</td></tr><tr class="hi"><td>Website Redesign + AI</td><td><b>$3,497</b></td><td class="note">Most popular: new site, AI chat, lead capture, SEO</td></tr><tr><td>Premium + 90 Days</td><td><b>$5,997</b></td><td class="note">The build plus 90 days of optimization</td></tr><tr><td>Complete Build + Management</td><td><b>$7,997</b></td><td class="note">Best value: 4-week build, 6 months managed</td></tr><tr><td>Done With You</td><td><b>$12,500</b></td><td class="note">Full AI implementation across site and operations</td></tr></tbody></table></div>
  <p class="note" style="margin-top:14px">Prefer to book it yourself? <a href="https://calendly.com/robert-graham1671">Grab a time on the calendar</a>.</p>
</div></section>

<section class="band alt" id="ask"><div class="w">
  <h2>Get your free audit</h2>
  <p class="lead" style="max-width:52ch">Send the website and Dr. Graham runs the audit this week. No cost, no obligation, no pitch on the call.</p>
  <form id="f" onsubmit="return send(event)">
    <div id="err" class="bad" hidden></div>
    <div class="f2">
      <label>Your name<input name="contact" required autocomplete="name" placeholder="Eric Hall"></label>
      <label>Business name and website<input name="org" required placeholder="Hall Dental &mdash; halldental.com"></label>
      <label>Phone<input name="phone" type="tel" autocomplete="tel" placeholder="407-555-0148"></label>
      <label>Email<input name="email" type="email" autocomplete="email" placeholder="you@email.com"></label>
      <label>Type<select name="seg"><option value="business">Local business</option><option value="corp">Agency or multi-location</option><option value="senior">Medical or dental practice</option><option value="travel">Hospitality or service</option></select></label>
      <label>How many leads a month would change things?<input name="qty" type="number" min="0" max="20000" placeholder="10"></label>
      <label>What bothers you most about the site?<input name="event" placeholder="Nobody calls from it"></label>
      <label>When would you want it handled by?<input name="evdate" type="date"></label>
      <label class="wide">Anything we should look at first? (optional)<textarea name="notes" rows="3" placeholder="The booking page never works on phones"></textarea></label>
      <label class="wide">City<input name="city" placeholder="Orlando"></label>
    </div>
    <button class="btn" type="submit" id="sb" style="width:100%">Run my free audit</button>
    <p class="note" style="margin:12px 0 0">We use your details only to send the audit. No spam, no list.</p>
  </form>
  <div id="done" hidden class="ok"></div>
</div></section>

<section class="band"><div class="w">
  <h2>Fair questions</h2>
  <div class="faq"><div><h3>Is it really free?</h3><p>Yes. You keep the written report whether or not you ever hire us.</p></div><div><h3>Do you need access to my site?</h3><p>No. The audit runs from the outside, the same way a customer sees it.</p></div><div><h3>Will I get sold on the call?</h3><p>No. We send the report. If you want help with it, you tell us.</p></div><div><h3>What if I like it and want the work?</h3><p>Packages start at $297 and all carry a 30-day money-back guarantee.</p></div><div><h3>How long does the audit take?</h3><p>We turn it around within the week, usually sooner.</p></div><div><h3>What if I don't have a website yet?</h3><p>Then say so on the form. We'll tell you honestly what it would take.</p></div></div>
</div></section>

<footer><div class="w">
  <b style="color:var(--gold2)">3G&rsquo;s AI Web Solutions</b> &middot; Strategic AI implementation &middot; Real revenue growth &middot; Dr. Robert D. Graham<br>
  <a href="tel:3529313768">352-931-3768</a> &middot; <a href="mailto:support@3gswebsolutions.shop">support@3gswebsolutions.shop</a> &middot; <a href="https://3gswebsolutions.shop/packages.html">See the packages</a><br>
  <span style="opacity:.75">Excellence Is A Habit &middot; Built By Faith</span>
</div></footer>

<div class="sticky"><a class="btn" href="#ask">Run my free audit</a></div>

<script>
(function(){
  var D = [['2026-11-24','Thanksgiving travel week'],['2026-12-12','Christmas order cutoff'],['2026-12-19','Christmas travel week']];
  var now = new Date(); now.setHours(0,0,0,0);
  var h = D.map(function(x){
    var d = Math.round((new Date(x[0]+'T00:00:00') - now)/864e5);
    return d < 0 ? '' : '<div class="cl"><b>' + d + (d===1?' day':' days') + '</b><span>' + x[1] + '</span></div>';
  }).join('');
  var el = document.getElementById('clock'); if (el) el.innerHTML = h;
})();
function send(ev){
  ev.preventDefault();
  var f = document.getElementById('f'), b = document.getElementById('sb'), er = document.getElementById('err');
  var o = {biz:'web'}; [].forEach.call(f.elements, function(el){ if (el.name) o[el.name] = el.value.trim(); });
  if (!o.phone && !o.email){ er.hidden = false; er.textContent = 'Please add a phone number or an email so we can reach you.'; return false; }
  er.hidden = true; b.disabled = true; var lbl = b.textContent; b.textContent = 'Sending\u2026';
  fetch('/api/machine.php?a=request', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(o)})
   .then(function(r){ return r.json(); })
   .then(function(r){
     b.disabled = false; b.textContent = lbl;
     if (r.error){ er.hidden = false; er.textContent = r.error; return; }
     f.hidden = true;
     var d = document.getElementById('done'); d.hidden = false;
     d.innerHTML = '\u2705 ' + (r.msg || 'Got it.') + '<br><br>Watch your phone and your email. To talk it through right now, call Dr. Graham at <a href="tel:3529313768">352-931-3768</a>.';
     d.scrollIntoView({behavior:'smooth', block:'center'});
   })
   .catch(function(){ b.disabled = false; b.textContent = lbl; er.hidden = false; er.textContent = 'Could not send just now. Please call or text 352-931-3768.'; });
  return false;
}
</script>
</body></html>
FB053B811;

$P = json_decode('[["desk/index.html", "<script src=\\"orlando.js?v=1\\"></script>", "<script src=\\"machine.js?v=1\\"></script>", "swap the Orlando tab for the Outreach Machine"]]', true);
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
foreach (['api/machine.php'] as $pf) { $o = []; $rc = 0; @exec('php -l ' . escapeshellarg($root . '/' . $pf) . ' 2>&1', $o, $rc); $t = trim(implode(' ', $o)); if ($rc !== 0 && $t !== '') { $err = true; $lint[] = "ERR $pf: $t"; } else $lint[] = "ok  $pf syntax " . ($t ?: '(linter unavailable, skipped)'); }
$out .= implode("\n", $lint) . "\n";
if ($err) {
  foreach ($files as $f => $c) if (is_file($f . $tag)) copy($f . $tag, $f);
  foreach ($wrote as $f) { if (is_file($f . $tag)) copy($f . $tag, $f); else @unlink($f); }
  $out .= "ROLLED BACK because of a syntax error.\n";
} else { @unlink(__FILE__); $out .= "\nDONE. The Outreach Machine is the first tab in the Home menu.\n"; }
echo $out;
