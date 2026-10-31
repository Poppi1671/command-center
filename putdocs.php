<?php
/* Put the operations docs into the Command Center Library (Docs & playbooks).
   Writes into the same folder the Library tab reads. Deletes itself when done. */
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root . '/api/svc_lib.php';
$log = '';
function say($s) { global $log, $root; $log .= $s . "\n"; echo $s . "\n"; @file_put_contents($root . '/_inst3/go-report.txt', $log); }

// the Library tab reads here (same rule auto.php uses)
$lib = strpos(TTS_DATA, '/public_html/') === false ? dirname(TTS_DATA) . '/tt_library' : TTS_DATA . '/library';
$dir = $lib . '/3_Docs_Playbooks';
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { say('STOP: could not make ' . $dir); exit; }
say('Library folder: ' . $dir);

$DOCS = [];
$DOCS['00-START-HERE-2026-10-31.md'] = <<<'DB5288FA1'
# 3G's Command Center — Start Here
### Operations manual, updated 31 October 2026

Everything the machine does, what it costs to run, and what to do each morning.

---

## THE DAILY RUN

**One job: ten calls.** Everything else in here exists to make those ten calls land.

1. Open the Command Center → **The Outreach Machine** (first tab, Home menu)
2. Pick the business at the top. Work **Call these now** from the top down.
3. Tap an outcome after **every** call. The next follow-up date sets itself.
4. Anyone who says yes gets what you promised **within 24 hours**.
5. Log it. The scoreboard is the only honest judge of the day.

**The number that should drive every decision:** one shirt sold alone keeps about **$16**. One group of 24 at $25 keeps about **$275**. One church is worth about **17 individual customers**, and it takes **one yes** instead of finding seventeen strangers.

So the question is never "will this sell online." It is **"will one group buy 24 of it."**

---

## THE THREE TOOLS

### 1. The Outreach Machine — where the money starts
Tab: **Home → The Outreach Machine**

One call list, one set of scripts, one scoreboard, pointed at whichever business you pick.

| Business | The offer | Goal | Its free-offer page |
|---|---|---|---|
| GPTS Airport Transfers | A flat rate held for your guests | 10 accounts / 40 rides | `/airport-transfers/` |
| Orlando Group Orders | 3 designs free in 24 hours, each member pays their own | 3 orders / 72 shirts | `/group-orders/` |
| GGFG Fundraising | The group keeps $5 a shirt and puts up nothing | 2 fundraisers / 100 shirts | `/fundraising/` |
| 3G's AI Web Solutions | A free written AI and website audit | 4 audits booked | `/web-audit/` |

**What it does:** sorts the call list by value · dials from the card · fills every script with their name and your price · nine one-tap outcomes that set the follow-up date · builds a live group page at the right tier · refuses any price under 35%.

**The Brief.** A lead can carry who to ask for, a reason to call today, what they already do, and a written opening line. The opener jumps to the top of the scripts. Eight Orlando churches already carry one.

**The Junk view.** Every public form is scored for spam. Junk is kept out of the call list and never emails you — and **is never deleted**. One tap puts a real one back.

**Everything is editable.** *Edit this business* changes the name, offer, segments, rates, rules, dates and every script. Start a new business from scratch, or put one back the way it shipped.

---

### 2. The Narrator — one voice engine for everything
Tab: **Home → The Narrator**

Paste any script. Pick a voice and how it should sound — Authority, Teacher, Preacher, News, Hype, Calm. Make the audio. Get a link.

**Anyone with the link can play it. No password.** That is what goes to a pastor or a hotel manager.

Audio is cached by content, so re-making a line already voiced **costs nothing**. It runs on the same engine as the Voices panel — no second account, no second bill.

Already made: the **14-slide narrated walkthrough** of this whole system. Send it to anyone who asks what you do.

---

### 3. The Product Lab — can *we* win this one?
Tab: **Home → The Product Lab**

**Three gates. Fail one and it is dead, whatever the trend says.**

1. **Can you press it in-house?** Your list, editable under *What you can press*. Not a guess.
2. **Does it hold 35% at your real blank cost?** Computed live from the cost table — blank, Stripe, the 7% tax you absorb, real postage. No cost on file means it says so instead of guessing.
3. **Is it a design or just a category?** It demands the exact words on the shirt.

Passing ideas get a 0–100 score, a price ladder, a **72-hour plan**, a ready Artistly prompt, and an offer line you push onto five leads as **their opening call for today**.

It does not end in a folder of designs. It ends in phone calls.

---

## WHAT IT COSTS TO RUN

See **Running-costs.md** in this folder for the detail. The short version:

- **One OpenAI key** pays for everything AI. There is no second AI vendor.
- **Voice is rounding error** — the entire 14-slide narration cost about a dime, once. The monthly cap is about 90 cents.
- **The crons are not waste.** `art_auto.php` and `auto.php` make **no AI calls** — they send customer proof reminders, review requests, abandoned-cart recovery and nightly backups. They *earn*.
- **The one unmetered thing is DataForSEO** — a separate paid account the Research panel calls. Nothing counts it today. That gets measured next.

**Measure first, cut second.**

---

## THE RULES THAT DO NOT BEND

- Every group ships in **ONE box to the leader**. A single label is $7.46 and eats a $25 shirt.
- **35% floor.** The tools refuse to go under it.
- Original designs only — no licensed characters, no team logos, no mascots.
- **Real product photos** on every mockup. Never generated ones.
- Nothing promised after **December 12** for Christmas.
- **Nothing is ever deleted.** Flagged, archived, quarantined — kept.
- Only buy buttons are green. Every other button is brand gold.
- Prices on the GPTS sites are final. Quote from the card, never off the top of your head.

---

## HARD DATES

| Date | What |
|---|---|
| **Nov 10** | Florida Classic shirt orders close |
| **Nov 21** | Florida Classic game day |
| **Nov 30** | The 3 paid group orders goal |
| **Dec 12** | Christmas order cutoff — nothing promised after this |
| **Dec 19 / Dec 29** | Christmas and New Year travel weeks (GPTS) |

---

## WHERE THINGS LIVE

| What | Where |
|---|---|
| Command Center | `threadedtruthsapparelco.com/desk` |
| Outreach engine | `api/machine.php` · packs in `tt_data/machine_packs.json` |
| Leads | `tt_data/leads.json` — shared with the Leads tab |
| Narrator | `api/narrator.php` · player at `/narrate/` |
| Product Lab | `api/lab.php` · data in `tt_data/lab.json` |
| Group pages | `api/campaign.php` → `/fundraiser/?c=<id>` |
| Real cost table | `api/money_lib.php` → Costs & settings |
| This Library | Library tab → Docs & playbooks |

---

## HOW CHANGES GET MADE SAFELY

Every change today followed the same path, and it caught **four real bugs** before they reached the live site:

1. Build it locally
2. **Dry run it against stub copies of the live files**
3. Deploy through GitHub → Hostinger → a one-minute cron
4. The installer backs up every file it touches, checks syntax, and **rolls back on any error**
5. Delete the temporary cron

Bugs this caught: a backup tag being substituted inside a constant name; a path check that matched only one folder name; a profit figure that overstated a 24-shirt group by **$67**; and an *Edit this business* screen that existed but had **no button to reach it**.

---

*Excellence Is A Habit · Built By Faith · Kings Don't Quit*
DB5288FA1;
$DOCS['Running-costs-2026-10-31.md'] = <<<'DABBD3013'
# What it costs to run the machine
### Checked against the live server on 31 October 2026 — not guessed

## Correction first

Twice on Oct 31 I told Dr. Graham that `art_auto.php` running **every 15 minutes** was the first cost leak to cut. **I was wrong.** I read it. It makes no AI calls at all.

| Cron | Runs | What it actually does | AI cost |
|---|---|---|---|
| `api/art_auto.php` | every 15 min | Emails a customer *"we got your artwork"* when a paid custom order lands, and sends one proof reminder if they haven't approved after 24 hours | **None** |
| `api/auto.php` | hourly | Nightly data backup, review-request email after shipping, Monday money report, come-back email for abandoned checkouts | **None** |
| `api/research.php` | daily 11:05 | Its stored output is literally `pulse ok` — a heartbeat, not a research run | **None observed** |

Both of the first two are **revenue-protecting**, not waste. Proof reminders get orders approved and shipped faster; the abandoned-checkout email recovers carts that were already lost. Cutting them would have cost money. The lesson is the one the socks error taught the same day: **read it before you call it waste.**

## What actually costs money

**One OpenAI key** (`TT_OPENAI_KEY` in `api/services-config.php`) pays for everything AI in the Command Center. There is no second AI vendor.

| Tool | File | Model | When it spends | Metered? |
|---|---|---|---|---|
| Voices | `api/voices.php` | `gpt-4o-mini-tts`, `tts-1`, `tts-1-hd` | when he makes a voiceover | **Yes** — `tt_data/voices/usage.json`, cap 60,000 chars/month |
| The Narrator | `api/narrator.php` | same engine | when he makes narration | **Yes** — shares the cap, caches by content so a repeat line is free |
| Product Research | `api/research.php` | `gpt-4.1-mini`, falls back to `gpt-4o-mini` | only on demand from the panel | results cached 3 days |
| Companion | `api/companion.php` | chat + `gpt-image-1-mini` for avatars | when he talks to it | counts messages |
| Images | `api/ai_image.php`, `api/replicate.php` | image models | when he generates art | partly |

**Voice prices, confirmed:** `tts-1` $15 per 1M characters · `tts-1-hd` $30 per 1M · `gpt-4o-mini-tts` token-based ($0.60/1M input text, $12/1M output audio) ≈ **$0.015 a minute of audio**.

**Measured:** the whole 14-slide narrated walkthrough was **4,209 characters** — about **6 to 11 cents, once**. The 60,000-character monthly cap is roughly **90 cents at tts-1 rates**. Voice is rounding error.

## The one that is not metered and should be

**DataForSEO.** `api/research.php` calls it for competitor overviews, ranked keywords, SERP data, backlinks and page audits. It is a **separate paid account with its own bill**, and nothing in the Command Center counts what it spends. Only called when the Research panel is used — but a few competitor lookups and SERP pulls is exactly the kind of thing that quietly runs up a bill.

## What to build next

A **Running Costs** page in the Command Center that:

1. Reads the real meters already on disk (`voices/usage.json`, `narration/usage.json`, the companion counter) rather than estimating
2. Converts at rates he can edit, since model prices change
3. Shows one number per tool per month, and the month-to-date total
4. Lets him set a **cap per tool**, the way voice already has one
5. Counts **DataForSEO** calls, which nothing counts today

**Measure first, cut second.** That order matters — the two crons nearly cut were both earning.

## Fixed costs outside the AI key

Hostinger shared hosting (all domains on one account), domain registrations, Stripe at 2.9% + 30¢ per transaction (modelled at 3.5% + 30¢ as a cushion), USPS postage, and blanks and film for the presses.
DABBD3013;


$n = 0;
foreach ($DOCS as $name => $body) {
  $f = $dir . '/' . $name;
  if (@file_put_contents($f, $body . "\n", LOCK_EX) === false) { say('FAILED ' . $name); continue; }
  say('wrote ' . $name . ' (' . strlen($body) . ' bytes)');
  $n++;
}
say('');
say($n . ' documents are now in the Command Center under Library > Docs & playbooks.');
@unlink(__FILE__);
