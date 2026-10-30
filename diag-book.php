<?php
if (($_GET['go'] ?? '') !== 'bk7f2a') { http_response_code(403); exit; }
header('Content-Type: text/plain');
$root = dirname(__DIR__); require_once $root . '/api/svc_lib.php'; require_once $root . '/api/companion_kb.php'; require_once $root . '/api/companion_kb2.php';
$q = (string)($_GET['q'] ?? 'Read me Treasure Island by Robert Louis Stevenson');
echo "is_book=", var_export(kb_is_book($q), true), "\n";
$t0 = microtime(true); $bk = kb_book_find($q); echo "find(", round(microtime(true) - $t0, 1), "s)=", json_encode($bk), "\n";
if ($bk) { $t0 = microtime(true); $txt = kb_book_text($bk); echo "text(", round(microtime(true) - $t0, 1), "s) len=", strlen($txt), "\n"; $ch = kb_book_chunk($bk, 0); echo json_encode($ch), "\n"; }
$r = kb_get('https://gutendex.com/books/?search=' . rawurlencode('treasure island'), 60); echo "gutendex raw len=", strlen($r), " head=", substr($r, 0, 200), "\n";
$s = kb_stock('TSLA'); echo "stock=", $s ? $s['src'] . ' ' . $s['last'] : 'null', "\n";
