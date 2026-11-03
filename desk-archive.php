<?php
/* Desk backup archiver — Nov 3 2026
   MOVES old .bak-* / .backup-* files out of /desk into /desk/_archive/.
   Nothing is deleted. Live files are never touched.
   ?go=TOKEN           -> list only, changes nothing
   ?go=TOKEN&move=1    -> move them, then self-delete */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

const TOKEN = 'deskclean1103';
if (($_GET['go'] ?? '') !== TOKEN) { http_response_code(403); exit("Forbidden\n"); }

$DESK = '/home/u732570899/domains/threadedtruthsapparelco.com/public_html/desk';
$ARCH = $DESK . '/_archive';
$MOVE = ($_GET['move'] ?? '') === '1';

if (!is_dir($DESK)) exit("FAIL: desk folder not found\n");

/* A file is a backup only if its name contains .bak- or .backup- .
   Anything else is treated as live and left alone. */
$all = scandir($DESK) ?: [];
$baks = []; $live = [];
foreach ($all as $f) {
  if ($f === '.' || $f === '..' || $f === '_archive') continue;
  if (is_dir($DESK . '/' . $f)) continue;
  if (preg_match('/\.(bak|backup)[-.]/i', $f)) $baks[] = $f; else $live[] = $f;
}
sort($baks); sort($live);

$bytes = 0; foreach ($baks as $f) $bytes += (int)@filesize($DESK . '/' . $f);

echo "DESK: " . count($all) - 2 . " entries\n";
echo "BACKUPS TO ARCHIVE: " . count($baks) . "  (" . round($bytes / 1048576, 1) . " MB)\n";
echo "LIVE FILES STAYING: " . count($live) . "\n\n";

echo "--- LIVE FILES (untouched) ---\n";
foreach ($live as $f) echo "  $f\n";

if (!$MOVE) {
  echo "\n--- WOULD MOVE (first 25 of " . count($baks) . ") ---\n";
  foreach (array_slice($baks, 0, 25) as $f) echo "  $f\n";
  echo "\nLIST ONLY. Nothing changed. Add &move=1 to actually move them.\n";
  exit;
}

if (!is_dir($ARCH) && !@mkdir($ARCH, 0755, true)) exit("\nFAIL: could not create _archive\n");
@file_put_contents($ARCH . '/.htaccess', "Require all denied\nDeny from all\n");

$ok = 0; $fail = [];
foreach ($baks as $f) {
  if (@rename($DESK . '/' . $f, $ARCH . '/' . $f)) $ok++; else $fail[] = $f;
}

echo "\n--- MOVED ---\n";
echo "moved   : $ok\n";
echo "failed  : " . count($fail) . "\n";
foreach (array_slice($fail, 0, 10) as $f) echo "  FAIL $f\n";
echo "\nAll of them are in /desk/_archive/ . Nothing was deleted.\n";
echo "To undo: move the files back out of _archive.\n";
@unlink(__FILE__);
