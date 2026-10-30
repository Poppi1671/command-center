<?php
if (($_GET['go'] ?? '') !== 'bk7f2a') { http_response_code(403); exit; }
header('Content-Type: text/plain'); @set_time_limit(50);
function probe($u) { $t0 = microtime(true); $ch = curl_init($u); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => ['User-Agent: 3GsCompanion/1.0 (urbancanvasapparelco.com; support@threadedtruthsapparelco.com)']]); $r = (string)curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); $e = curl_error($ch); curl_close($ch); echo $u, " => code=$c len=", strlen($r), " err=$e time=", round(microtime(true) - $t0, 1), "s head=", json_encode(substr($r, 0, 120)), "\n"; }
probe('https://gutendex.com/books/?search=treasure%20island');
probe('https://www.gutenberg.org/ebooks/search/?query=treasure+island&submit_search=Go%21');
probe('https://www.gutenberg.org/cache/epub/120/pg120.txt');
probe('https://query1.finance.yahoo.com/v8/finance/chart/TSLA?range=5d&interval=1d');
probe('https://en.wikipedia.org/api/rest_v1/page/summary/Treasure_Island');
probe('https://wsearch.nlm.nih.gov/ws/query?db=healthTopics&term=diabetes&retmax=1');
probe('https://api.dictionaryapi.dev/api/v2/entries/en/legacy');
probe('https://news.google.com/rss/search?q=tesla&hl=en-US&gl=US&ceid=US:en');
