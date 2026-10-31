<?php
/* Load the researched briefs into the Orlando call list. Reads the desk key from the
   server's own config; never prints it. Deletes itself when done. */
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/api/svc_lib.php';
$KEY = tt_admin_key(); $SITE = tt_site(); $log = '';
function say($s) { global $log, $root; $log .= $s . "\n"; echo $s . "\n"; @file_put_contents($root . '/_inst3/go-report.txt', $log); }
if ($KEY === '') { say('STOP: desk key not set.'); exit; }
$ROWS = json_decode(<<<'J'
[
{
 "org":"Discovery Church",
 "role":"whoever runs men's ministry or events",
 "why_now":"They run a men's breakfast every week and a men's cookout. Men's-ministry shirts are the easiest first order in a church this size.",
 "doing_now":"Three Sunday services (8:30, 10:00, 11:45) and 92 Google reviews - a big congregation, so they are almost certainly buying shirts somewhere already.",
 "brief":"4064 Winter Garden Vineland Rd, Winter Garden. Office open Mon-Thu 9-5 and Fri 9-1 only, so call before Friday lunchtime. Reviews name a Pastor Dale and a Pastor Zach, and describe a weekly men's breakfast and a men's cookout. Source: Google Places listing and its reviews - treat the names as a lead, not a fact.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. here in Orlando. I know you all run a men's breakfast every week over there - who handles the shirts when the men's ministry does an event? ... We design them free and every man pays for his own, so nobody has to collect money."
},
{
 "org":"Journey Christian Church",
 "role":"events or executive pastor",
 "why_now":"They host leadership summits for OTHER churches. Close them once and they introduce you to every church that walks through their doors.",
 "doing_now":"346 Google reviews, far the largest on the Orlando list. A review describes Majestic Life Church holding its Leadership Summit in their building.",
 "brief":"1965 S Orange Blossom Trail, Apopka. Office open Mon-Thu 9-5 and CLOSED FRIDAY - do not waste a Friday call here. They host other congregations for events, which makes this the highest-value referral door on the whole list. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. in Orlando. I know Journey hosts other churches for summits and conferences. When a group comes through and wants matching shirts, who do they end up calling? ... We design them free, each person pays their own, and we press them right here in Orlando."
},
{
 "org":"Mosaic Church",
 "role":"kids or student ministry lead",
 "why_now":"315 reviews and a children's ministry people single out by name. Kids and student ministries reorder shirts every season.",
 "doing_now":"Office Mon-Thu 9-4 plus Thursday evening 6:30-10, which usually means a midweek student or small-group night. Reviews name a Pastor Renault.",
 "brief":"14175 W Colonial Dr, Winter Garden. Thursday evening hours suggest a midweek youth or small-group program - that is the group that buys shirts. Reviews repeatedly praise the kids ministry and the worship band. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. here in Orlando. Your kids and student ministry is what people talk about when they talk about Mosaic. Who handles shirts for those groups? ... We design three options free in 24 hours and every family pays for their own shirt."
},
{
 "org":"Seminole Community Church",
 "role":"events coordinator or kids ministry",
 "why_now":"They run a yearly VBS called Power Up and reviews say their events are planned well and the kids look forward to them every year. VBS and Christmas events both need shirts.",
 "doing_now":"4.9 stars across 59 reviews - small but tight and clearly events-driven.",
 "brief":"5070 Orange Blvd, Sanford. No office hours published, so call mid-morning midweek. Reviews name a VBS program called Power Up and describe a yearly rhythm of hosted events. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. in Orlando. I know you all put real work into your events - the Power Up program came up more than once. Who handles shirts when you do one? ... We design them free and everyone orders their own size online."
},
{
 "org":"St Paul AME Church",
 "role":"Pastor Moss, or the church office",
 "why_now":"A community church with a big, engaged congregation and a Christmas season coming. Christmas orders close December 12.",
 "doing_now":"Reviews describe a funeral with standing room only and an usher team running it - a church that gathers large and often, and that serves the community.",
 "brief":"1012 S Park Ave, Apopka. Office open Mon-Fri 11-4, so call after 11. Reviews name Pastor Moss and First Lady Moss and say the church is always helping the community. The GGFG fundraiser angle fits this one better than a plain group order. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. and Grahams Global Fundraising Group here in Orlando. St Paul has a name in that community for helping people. I run shirt fundraisers where the church keeps five dollars a shirt and puts up nothing at all. Who would I talk to about that?"
},
{
 "org":"WaterStone Church",
 "role":"children's or young adult ministry lead",
 "why_now":"Two Sunday services and ministries for kids and young adults that members name directly. Young-adult groups are the fastest yes on a shirt.",
 "doing_now":"Office Mon-Fri 9-4, the widest window on this list, so this one is easy to reach. Reviews name a Pastor Ron.",
 "brief":"900 North St, Longwood. 4.7 stars. Members describe the church as generous to the community and point at the children's and young-adult ministries. Office is open five days, so no excuse not to reach someone. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. here in Orlando. I hear WaterStone's young adult and children's ministries are busy. Who handles shirts for those groups? ... We design three options free, and each family pays for their own, so nobody collects money."
},
{
 "org":"Orlando Grace Church",
 "role":"men's, women's or children's ministry lead",
 "why_now":"They run separate men's, women's and children's ministries plus community groups. That is four groups that can each order separately.",
 "doing_now":"Office Mon-Thu 9-5 and CLOSED FRIDAY. 4.7 stars over 48 reviews - smaller, but organised, with elders and named ministries.",
 "brief":"872 Maitland Ave, Altamonte Springs. Reviews describe strong elder leadership and distinct ministries for men, women and children, plus community groups. A smaller church, so start at the Starter tier, 12 to 23 shirts at $28. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. in Orlando. You all run separate ministries for the men, the women and the children, so I will ask it this way - who handles shirts when one of those groups does something together?"
},
{
 "org":"Church at the Cross",
 "role":"school or events office",
 "why_now":"They run a school as well as the church. A school means uniforms, spirit shirts and a yearly reorder, not a one-off.",
 "doing_now":"179 reviews. Wednesday evening service 6:30-8. Reviews mention a school for the children and events for all walks of life.",
 "brief":"700 Good Homes Rd, Orlando. Office Mon-Thu 8-5 plus Wednesday evening. NO PHONE NUMBER on the listing - look it up before calling or go through the website. The school is the real opportunity here: spirit shirts reorder every year. Source: Google Places listing and its reviews.",
 "opener":"Good morning, this is Dr. Graham with Urban Canvas Co. in Orlando. I understand you all run a school alongside the church. Who handles the spirit shirts for the students? ... We design them free, every family orders their own size online, and we press them here in Orlando."
}
]
J
, true);
if (!is_array($ROWS)) { say('STOP: briefs did not parse.'); exit; }
say('Loading ' . count($ROWS) . ' briefs into Orlando Group Orders...');
$ch = curl_init($SITE . '/api/machine.php');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_POST => true,
  CURLOPT_FOLLOWLOCATION => true, CURLOPT_POSTFIELDS => json_encode(['a' => 'briefs', 'biz' => 'orlando', 'rows' => $ROWS]),
  CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-TT-Key: ' . $KEY]]);
$r = (string)curl_exec($ch); $e = curl_error($ch); curl_close($ch);
if ($e !== '') { say('network error: ' . $e); exit; }
$j = json_decode($r, true);
if (!is_array($j)) { say('bad reply: ' . substr($r, 0, 300)); exit; }
if (!empty($j['error'])) { say('ERROR: ' . $j['error']); exit; }
say('briefed: ' . (int)($j['briefed'] ?? 0) . ', not matched: ' . (int)($j['missed'] ?? 0));
say('');
say('Open the Outreach Machine, switch to Orlando Group Orders, and these 8 now carry a brief and an opening line.');
@unlink(__FILE__);
