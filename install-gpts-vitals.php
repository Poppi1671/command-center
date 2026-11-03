<?php
/* GPTS Vitals per-site switch-off — Nov 3 2026
   Turns the apparel store tools OFF on the four transportation sites.
   Apparel stores are not touched. Backs up first. Idempotent. Self-deletes. */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

const TOKEN = 'gptsvitals1103';
if (($_GET['go'] ?? '') !== TOKEN) { http_response_code(403); exit("Forbidden\n"); }

$CFG = '/home/u732570899/domains/threadedtruthsapparelco.com/public_html/api/data/vitals/config.json';

$HIDE = ['shipbar','wishlist','recent','spin','carttimer','lowstock',
         'bundle','backinstock','sticky','popup_exit','popup_email','social'];

$DOMAINS = ['pristinedrive.online',
            'airporttransportationservices.online',
            'transportationservices.online',
            'executiveairporttransportationservices.online'];

if (!is_file($CFG))      exit("FAIL: config.json not found at $CFG\n");
if (!is_writable($CFG))  exit("FAIL: config.json is not writable\n");

$raw = (string)file_get_contents($CFG);
$c   = json_decode($raw, true);
if (!is_array($c) || !isset($c['tools'])) exit("FAIL: config.json did not parse as expected. Nothing changed.\n");

/* 1. Back up the original, once per run */
$bak = $CFG . '.bak-' . date('Ymd-His');
if (file_put_contents($bak, $raw, LOCK_EX) === false) exit("FAIL: could not write backup. Nothing changed.\n");
echo "Backup written: " . basename($bak) . "\n\n";

/* 2. Merge the per-site off lists, preserving anything already there */
$sites = $c['sites'] ?? [];
if (!is_array($sites)) $sites = [];
$sites = (array)$sites;                 // [] from JSON becomes a map below
foreach ($DOMAINS as $d) {
  $existing = (isset($sites[$d]['off']) && is_array($sites[$d]['off'])) ? $sites[$d]['off'] : [];
  $merged   = array_values(array_unique(array_merge($existing, $HIDE)));
  $sites[$d] = ['off' => $merged];
}
$c['sites'] = (object)$sites;           // force a JSON object, never an array

/* 3. Write atomically */
$json = json_encode($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) exit("FAIL: could not encode config. Nothing changed.\n");
$tmp = $CFG . '.tmp' . getmypid();
if (file_put_contents($tmp, $json, LOCK_EX) === false) exit("FAIL: could not write temp file. Nothing changed.\n");
if (!rename($tmp, $CFG)) { @unlink($tmp); exit("FAIL: could not replace config. Nothing changed.\n"); }

/* 4. Read it back and report what each site will now receive */
$v = json_decode((string)file_get_contents($CFG), true);
$allOn = [];
foreach (($v['tools'] ?? []) as $k => $t) if (!empty($t['on'])) $allOn[] = $k;

echo "TOOLS ON GLOBALLY (" . count($allOn) . "): " . implode(', ', $allOn) . "\n\n";
foreach ($DOMAINS as $d) {
  $off = $v['sites'][$d]['off'] ?? [];
  $live = array_values(array_diff($allOn, $off));
  echo str_pad($d, 46) . " -> " . implode(', ', $live) . "\n";
}
echo "\nAPPAREL CHECK (must still show all " . count($allOn) . "):\n";
foreach (['urbancanvasapparelco.com','threadedtruthsapparelco.com'] as $d) {
  $off = $v['sites'][$d]['off'] ?? [];
  echo str_pad($d, 46) . " -> " . count(array_diff($allOn, $off)) . " tools\n";
}

echo "\nDONE. To undo: rename " . basename($bak) . " back to config.json\n";
@unlink(__FILE__);
