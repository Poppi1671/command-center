<?php
/* One-shot runner (Oct 31 2026): loads the starter lists and makes the deck narration.
   Runs on the server as the site's own user. It reads the desk key from the server's own
   config and uses it only for these calls; the key is never printed. Deletes itself when done. */
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/api/svc_lib.php';
$KEY = tt_admin_key();
$SITE = tt_site();
$log = '';
function say($s) { global $log; $log .= $s . "\n"; echo $s . "\n"; }
if ($KEY === '') { say('STOP: the desk key is not set on this server.'); exit; }

function call($path, $body, $key, $site) {
  $ch = curl_init($site . $path);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 240, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-TT-Key: ' . $key]]);
  $r = (string)curl_exec($ch); $e = curl_error($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($e !== '') return ['error' => 'network: ' . $e, 'http' => $c];
  $j = json_decode($r, true);
  return is_array($j) ? $j + ['http' => $c] : ['error' => 'bad reply (' . $c . '): ' . substr($r, 0, 200), 'http' => $c];
}

/* ---------- 1. the starter lists ---------- */
$CHURCHES = json_decode(<<<'J'
[{"org":"Orlando Grace Church","city":"Altamonte Springs","phone":"407-660-1984","seg":"church","source":"Orlando church list"},{"org":"Grace Pointe Church","city":"Apopka","phone":"407-889-3383","seg":"church","source":"Orlando church list"},{"org":"Journey Christian Church","city":"Apopka","phone":"407-884-7223","seg":"church","source":"Orlando church list"},{"org":"St Paul AME Church","city":"Apopka","phone":"407-703-2901","seg":"church","source":"Orlando church list"},{"org":"Thrive Church Apopka","city":"Apopka","phone":"407-886-2806","seg":"church","source":"Orlando church list"},{"org":"Journey Church","city":"Fern Park","phone":"689-283-9798","seg":"church","source":"Orlando church list"},{"org":"New Hope Bible Church","city":"Kissimmee","phone":"407-791-5912","seg":"church","source":"Orlando church list"},{"org":"Church On The Living Edge","city":"Longwood","phone":"321-304-4111","seg":"church","source":"Orlando church list"},{"org":"King's Chapel","city":"Longwood","phone":"407-415-7447","seg":"church","source":"Orlando church list"},{"org":"LifePoint Christian Church","city":"Longwood","phone":"407-327-6468","seg":"church","source":"Orlando church list"},{"org":"WaterStone Church","city":"Longwood","phone":"407-339-8961","seg":"church","source":"Orlando church list"},{"org":"First AME Church","city":"Maitland","phone":"","seg":"church","source":"Orlando church list"},{"org":"All Nation Church of God of Prophecy","city":"Orlando","phone":"407-298-4411","seg":"church","source":"Orlando church list"},{"org":"Bethel Missionary Baptist Church","city":"Orlando","phone":"407-843-6612","seg":"church","source":"Orlando church list"},{"org":"Church at the Cross","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"Delaney Street Baptist Church","city":"Orlando","phone":"407-422-0758","seg":"church","source":"Orlando church list"},{"org":"Downtown Baptist Church Orlando","city":"Orlando","phone":"407-425-0200","seg":"church","source":"Orlando church list"},{"org":"Eastland Baptist Church","city":"Orlando","phone":"407-277-5858","seg":"church","source":"Orlando church list"},{"org":"Faith Baptist Church Orlando","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"First Orlando - John Young Campus","city":"Orlando","phone":"407-425-2555","seg":"church","source":"Orlando church list"},{"org":"Malone Memorial Church of God in Christ","city":"Orlando","phone":"407-295-5994","seg":"church","source":"Orlando church list"},{"org":"NEW LIFE CHURCH OF GOD","city":"Orlando","phone":"407-275-0563","seg":"church","source":"Orlando church list"},{"org":"New Bethel AME Church","city":"Orlando","phone":"407-295-6152","seg":"church","source":"Orlando church list"},{"org":"New Life Church COGIC","city":"Orlando","phone":"407-290-1467","seg":"church","source":"Orlando church list"},{"org":"Orlando Baptist Church","city":"Orlando","phone":"407-277-8671","seg":"church","source":"Orlando church list"},{"org":"South Orlando Baptist Church","city":"Orlando","phone":"407-859-1536","seg":"church","source":"Orlando church list"},{"org":"St MARK AME EAST CAMPUS","city":"Orlando","phone":"","seg":"church","source":"Orlando church list"},{"org":"St. John Baptist Church","city":"Orlando","phone":"407-781-0700","seg":"church","source":"Orlando church list"},{"org":"Church of God Ministry of Jesus Christ Intl.","city":"Pine Castle","phone":"888-331-8197","seg":"church","source":"Orlando church list"},{"org":"Seminole Community Church","city":"Sanford","phone":"407-324-0199","seg":"church","source":"Orlando church list"},{"org":"Quay Church","city":"Windermere","phone":"407-505-4888","seg":"church","source":"Orlando church list"},{"org":"Discovery Church","city":"Winter Garden","phone":"407-855-3140","seg":"church","source":"Orlando church list"},{"org":"Garden City Church","city":"Winter Garden","phone":"407-499-8051","seg":"church","source":"Orlando church list"},{"org":"Hope Church Winter Garden","city":"Winter Garden","phone":"407-490-1988","seg":"church","source":"Orlando church list"},{"org":"Kingdom Culture Church","city":"Winter Garden","phone":"407-490-3958","seg":"church","source":"Orlando church list"},{"org":"Mosaic Church","city":"Winter Garden","phone":"407-654-8220","seg":"church","source":"Orlando church list"},{"org":"The Grove Bible Chapel","city":"Winter Garden","phone":"407-614-3255","seg":"church","source":"Orlando church list"},{"org":"Vida Church","city":"Winter Garden","phone":"407-436-5147","seg":"church","source":"Orlando church list"},{"org":"Vineland Road Christian Fellowship","city":"Winter Garden","phone":"407-656-3949","seg":"church","source":"Orlando church list"},{"org":"West Orlando Christian Center COGIC","city":"Winter Garden","phone":"407-656-5665","seg":"church","source":"Orlando church list"}]
J
, true);

say('== Loading the lists ==');
$r = call('/api/machine.php', ['a' => 'import', 'biz' => 'gpts'], $KEY, $SITE);
say('GPTS            : ' . (isset($r['error']) ? 'ERROR ' . $r['error'] : 'added ' . $r['added'] . ', skipped ' . $r['skipped']));
$r = call('/api/machine.php', ['a' => 'import', 'biz' => 'orlando', 'rows' => $CHURCHES], $KEY, $SITE);
say('Orlando churches: ' . (isset($r['error']) ? 'ERROR ' . $r['error'] : 'added ' . $r['added'] . ', skipped ' . $r['skipped']));

/* ---------- 2. the narration ---------- */
$ITEMS = json_decode(<<<'J'
[
{"k":"cover","title":"The Outreach Machine","img":"/narrate/slides/cover.jpg","text":"This is the Outreach Machine. One call list, one set of scripts, one scoreboard, pointed at whichever business you pick. It was built on October thirty-first, twenty twenty-six, and from here to the end of the year it is how we go and get customers."},
{"k":"problem","title":"Posting was never the problem","img":"/narrate/slides/problem.jpg","text":"Posting was never the problem. Ads and graphics are conversion assets. They work on people who already found you. What we did not have was a way to go and get a customer on purpose. Forty Orlando churches sat in a spreadsheet nobody opened. Now, you open the tab, and the next call is already at the top."},
{"k":"one-machine","title":"One machine, four businesses","img":"/narrate/slides/one-machine.jpg","text":"One machine runs four businesses. G P T S airport transfers, where we hold a flat rate for a property's guests. Orlando group orders, three shirt designs free in twenty-four hours. G G F G fundraising, where the group keeps five dollars a shirt and puts up nothing. And three G's A I Web Solutions, a free written website audit. Each one carries its own goal."},
{"k":"switcher","title":"Tap the name, everything re-aims","img":"/narrate/slides/switcher.jpg","text":"You tap the name at the top, and everything re-aims. Different leads. Different scripts. Different prices. Different deadlines. Different goal. The number beside each name is how many leads that business has waiting on you."},
{"k":"scoreboard","title":"The number, not the feeling","img":"/narrate/slides/scoreboard.jpg","text":"Every business has one number that tells you whether it worked. Underneath it: calls made, how many got something sent, who is interested, who has been quoted, and how many are still untouched. If the top number is not moving, nothing else on the screen matters."},
{"k":"calllist","title":"Call these now","img":"/narrate/slides/calllist.jpg","text":"Call these now. The list sorts itself. Interested first, then anyone waiting on a quote, then everyone due a follow-up. The green button dials. The last note is right there, so you remember who you spoke to before they even pick up. Ten calls a day is the whole job."},
{"k":"outcomes","title":"One tap after every call","img":"/narrate/slides/outcomes.jpg","text":"After every call, you tap what happened. You never pick a date. No answer sets you back in two days. Call back, one day. Interested, one day. Sent it, three days. Not now, three weeks. Nothing falls through, and nothing has to be remembered. That is the part that makes follow-up actually happen."},
{"k":"script","title":"The words are already written","img":"/narrate/slides/script.jpg","text":"The words are already written. Their name, their business, your price, all filled in before you open it. And the right one is flagged for you. Never called them? The phone script. Interested? The email. Already sent it? The day-three follow-up. Copy, paste, send, then tap what happened."},
{"k":"prices","title":"It cannot quote you into a loss","img":"/narrate/slides/prices.jpg","text":"It cannot quote you into a loss. Margins are worked out from your real cost table every single time the page loads. Blank cost, Stripe, the seven percent tax we absorb, and one-box postage. Crewneck and hoodie prices are solved backwards to hold the same share. Try to build a page under thirty-five percent, and it refuses, and tells you which tier to pick instead."},
{"k":"rates","title":"Every business carries its own card","img":"/narrate/slides/rates.jpg","text":"Every business carries its own card. Switch to G P T S and the ladder becomes the rate card. Switch to A I Web Solutions and it becomes the five packages. Every price came off your own live pages. None were invented. And underneath each card sit the rules you do not break."},
{"k":"inbound","title":"They can come to you too","img":"/narrate/slides/inbound.jpg","text":"They can come to you, too. Four free-offer pages are live, one per business. A flat rate for your guests. Three shirt designs, free. A fundraiser that costs the group nothing. A free website audit. Every form lands in that business's call list and emails you the moment it is sent. The flyers carry a Q R code straight to the page."},
{"k":"editable","title":"Every word is yours to change","img":"/narrate/slides/editable.jpg","text":"Every word is yours to change. The businesses are data, not code. Open Edit this business and change the name, the brand, the phone, the offer line, who you call, the rate card, the rules, the dates, and every script, word for word. Start a brand new business from scratch, or put any one back the way it shipped. A fifth business takes minutes, not a rebuild."},
{"k":"nextbuild","title":"What we build next","img":"/narrate/slides/nextbuild.jpg","text":"Here is what we build next, and what it replaces. A Lead Finder instead of Apollo. Sequences instead of Close and HubSpot. Send from the tool instead of Lemlist. Source tracking instead of CallRail. A money forecast instead of Pipedrive. That stack runs four hundred to nine hundred dollars a month. We build it once, and we own it."},
{"k":"firstmove","title":"Ten calls. Today.","img":"/narrate/slides/firstmove.jpg","text":"The single next move is ten calls today. Open the Outreach Machine. Work Call these now from the top down, and tap what happened after every single one. The machine does not make the call. It makes sure the right call is always on top, the words are ready, and nobody gets forgotten. Kings don't quit."}
]
J
, true);

say('');
say('== Narration ==');
$r = call('/api/narrator.php', ['a' => 'save', 'id' => 'outreach-machine', 'title' => 'The Outreach Machine',
  'sub' => 'Narrated walkthrough  ·  14 slides', 'voice' => 'onyx', 'model' => 'gpt-4o-mini-tts',
  'speed' => 1, 'tone_key' => 'authority', 'live' => true, 'items' => $ITEMS], $KEY, $SITE);
if (isset($r['error'])) { say('SAVE FAILED: ' . $r['error']); exit; }
say('Saved as "' . $r['id'] . '" with ' . count($ITEMS) . ' lines.');

$pass = 0;
while ($pass < 8) {
  $pass++;
  $m = call('/api/narrator.php', ['a' => 'make', 'id' => 'outreach-machine'], $KEY, $SITE);
  if (!empty($m['error'])) { say('VOICE ERROR on pass ' . $pass . ': ' . $m['error']); break; }
  say('pass ' . $pass . ': made ' . (int)$m['made'] . ', ' . (int)$m['left'] . ' left, ' . (int)$m['used'] . ' of ' . (int)$m['cap'] . ' characters used this month');
  if ((int)$m['left'] === 0) break;
  if ((int)$m['made'] === 0) { say('No progress on this pass, stopping.'); break; }
}
say('');
say('PLAY IT: ' . $SITE . '/narrate/?p=outreach-machine');

@file_put_contents($root . '/_inst3/go-report.txt', $log);
@unlink(__FILE__);
