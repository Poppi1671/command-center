<?php
/* THE WALL installer - wall. Three new files plus one script line on the desk.
   No exec(), no shell: shared hosting disables them. Syntax was linted before it shipped.
   All-or-nothing on the patch; writes a report next to itself either way. */
@ini_set('display_errors','0');
$REPORT = __DIR__ . '/wall-report.txt';
$log = [];
function out($s) { global $log, $REPORT; $log[] = $s; @file_put_contents($REPORT, implode("\n", $log) . "\n"); echo $s . "\n"; }
set_error_handler(function($n,$m,$f,$l){ out("PHP ERROR: $m ($f:$l)"); return true; });
register_shutdown_function(function(){ $e = error_get_last(); if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR], true)) out('FATAL: ' . $e['message'] . ' (' . $e['file'] . ':' . $e['line'] . ')'); });

if (PHP_SAPI !== 'cli' && ($_GET['go'] ?? '') !== '5d723d70abc25a78') { http_response_code(404); exit; }
if (PHP_SAPI === 'cli' && ($_GET['go'] ?? '') !== '5d723d70abc25a78') { echo "bad token\n"; exit; }
header('Content-Type: text/plain; charset=utf-8');

$root = preg_match('~/_inst~', __DIR__) ? dirname(__DIR__) : __DIR__;
$tag  = 'refreshall';
$stamp = date('Ymd');
out('installer running. root=' . $root . ' sapi=' . PHP_SAPI);

$blocks  = json_decode(base64_decode('eyJkZXNrL3JlZnJlc2hhbGwuanMiOiAiTHlvZ00wY25jeUJEYjIxdFlXNWtJRU5sYm5SbGNqb2dVa1ZHVWtWVFNDQkZWa1ZTV1ZSSVNVNUhJQ2hVVkVGTVRDa3VDaUFnSUU5dVpTQmlkWFIwYjI0Z2FXNGdkR2hsSUdobFlXUmxjaUIwYUdGMElISmxiRzloWkhNZ1pYWmxjbmtnZEdGaUozTWdaR0YwWVNCM2FYUm9iM1YwSUhKbGJHOWhaR2x1WnlCMGFHVWdjR0ZuWlM0S0lDQWdTWFFnUmtsT1JGTWdkR2hsSUcxdlpIVnNaWE1nWVhRZ2NuVnVkR2x0WlNCeVlYUm9aWElnZEdoaGJpQm9iMnhrYVc1bklHRWdiR2x6ZEN3Z2MyOGdZU0IwWVdJZ1lXUmtaV1FnYm1WNGRDQnRiMjUwYUFvZ0lDQnBjeUJ3YVdOclpXUWdkWEFnZDJsMGFDQnVieUJqYUdGdVoyVWdhR1Z5WlM0Z1FtRmphMlZ1WkRvZ2JtOXVaU0F0SUdsMElHcDFjM1FnWVhOcmN5QmxZV05vSUcxdlpIVnNaU0IwYnlCeVpXWnlaWE5vTGlBcUx3b29ablZ1WTNScGIyNG9LWHNLYVdZZ0tIZHBibVJ2ZHk1VVZFRk1UQ2tnY21WMGRYSnVPd3AyWVhJZ1FsVlRXU0E5SUdaaGJITmxPd29LTHlvZ1pYWmxjbmtnVkZRcUlHMXZaSFZzWlNCMGFHRjBJR3R1YjNkeklHaHZkeUIwYnlCeVpXWnlaWE5vSUdsMGMyVnNaaTRnSjJsdWFYUW5JR0Z1WkNBbmJHOWhaQ2NnWVhKbElHeGxablFnWVd4dmJtVWdiMjRLSUNBZ2NIVnljRzl6WlRvZ2RHaHZjMlVnYzJWMElHRWdiVzlrZFd4bElIVndJSEpoZEdobGNpQjBhR0Z1SUhKbExYSmxZV1JwYm1jZ2FYUXNJR0Z1WkNCallXeHNhVzVuSUhSb1pXMGdZMkZ1SUhWdVpHOGdkMjl5YXk0Z0tpOEtablZ1WTNScGIyNGdabWx1WkdWeWN5Z3Bld29nSUhaaGNpQnZkWFFnUFNCYlhUc0tJQ0JtYjNJZ0tIWmhjaUJySUdsdUlIZHBibVJ2ZHlrZ2V3b2dJQ0FnYVdZZ0tHc3VhVzVrWlhoUFppZ25WRlFuS1NBaFBUMGdNQ2tnWTI5dWRHbHVkV1U3Q2lBZ0lDQjJZWElnZGpzS0lDQWdJSFJ5ZVNCN0lIWWdQU0IzYVc1a2IzZGJhMTA3SUgwZ1kyRjBZMmdnS0dVcElIc2dZMjl1ZEdsdWRXVTdJSDBLSUNBZ0lHbG1JQ2doZGlCOGZDQjBlWEJsYjJZZ2RpQWhQVDBnSjI5aWFtVmpkQ2NwSUdOdmJuUnBiblZsT3dvZ0lDQWdhV1lnS0hSNWNHVnZaaUIyTG5KbFpuSmxjMmdnUFQwOUlDZG1kVzVqZEdsdmJpY3BJRzkxZEM1d2RYTm9LSHR1WVcxbE9tc3NJR1p1T25ZdWNtVm1jbVZ6YUN3Z2IyNDZkbjBwT3dvZ0lDQWdaV3h6WlNCcFppQW9kSGx3Wlc5bUlIWXVjbVZzYjJGa0lEMDlQU0FuWm5WdVkzUnBiMjRuS1NCdmRYUXVjSFZ6YUNoN2JtRnRaVHByTENCbWJqcDJMbkpsYkc5aFpDd2diMjQ2ZG4wcE93b2dJSDBLSUNCeVpYUjFjbTRnYjNWME93cDlDbVoxYm1OMGFXOXVJR1ZzS0NsN0lISmxkSFZ5YmlCa2IyTjFiV1Z1ZEM1blpYUkZiR1Z0Wlc1MFFubEpaQ2duZEhSQmJHeENkRzRuS1RzZ2ZRcG1kVzVqZEdsdmJpQnpZWGtvZEN3Z1kyeHpLWHNLSUNCMllYSWdZaUE5SUdWc0tDazdJR2xtSUNnaFlpa2djbVYwZFhKdU93b2dJR0l1ZEdWNGRFTnZiblJsYm5RZ1BTQjBPd29nSUdJdVkyeGhjM05PWVcxbElEMGdKMkowYmljZ0t5QW9ZMnh6SUQ4Z0p5QW5JQ3NnWTJ4eklEb2dKeWNwT3dwOUNtWjFibU4wYVc5dUlIUnZZWE4wS0cxelp5bDdDaUFnZG1GeUlIUWdQU0JrYjJOMWJXVnVkQzVuWlhSRmJHVnRaVzUwUW5sSlpDZ25kSFJCYkd4VWIyRnpkQ2NwT3dvZ0lHbG1JQ2doZENrZ2V3b2dJQ0FnZENBOUlHUnZZM1Z0Wlc1MExtTnlaV0YwWlVWc1pXMWxiblFvSjJScGRpY3BPd29nSUNBZ2RDNXBaQ0E5SUNkMGRFRnNiRlJ2WVhOMEp6c0tJQ0FnSUhRdWMzUjViR1V1WTNOelZHVjRkQ0E5SUNkd2IzTnBkR2x2YmpwbWFYaGxaRHRzWldaME9qVXdKVHQwY21GdWMyWnZjbTA2ZEhKaGJuTnNZWFJsV0NndE5UQWxLVHRpYjNSMGIyMDZZMkZzWXlneE9IQjRJQ3NnWlc1MktITmhabVV0WVhKbFlTMXBibk5sZEMxaWIzUjBiMjBwS1RzbklDc0tJQ0FnSUNBZ0oySmhZMnRuY205MWJtUTZJekZDTVRjeE5EdGpiMnh2Y2pvalJqVkZRa1JCTzJKdmNtUmxjam94Y0hnZ2MyOXNhV1FnSTBNNVFUSTBSVHRpYjNKa1pYSXRjbUZrYVhWek9qazVPWEI0TzNCaFpHUnBibWM2TVRKd2VDQXlNbkI0T3ljZ0t3b2dJQ0FnSUNBblptOXVkRG8zTURBZ01UVndlQ0J6ZVhOMFpXMHRkV2s3ZWkxcGJtUmxlRG81T1RrNU8ySnZlQzF6YUdGa2IzYzZNQ0F4TUhCNElETXdjSGdnY21kaVlTZ3dMREFzTUN3dU15azdiV0Y0TFhkcFpIUm9Pamt5ZG5jN2RHVjRkQzFoYkdsbmJqcGpaVzUwWlhJbk93b2dJQ0FnWkc5amRXMWxiblF1WW05a2VTNWhjSEJsYm1SRGFHbHNaQ2gwS1RzS0lDQjlDaUFnZEM1MFpYaDBRMjl1ZEdWdWRDQTlJRzF6WnpzS0lDQjBMbWhwWkdSbGJpQTlJR1poYkhObE93b2dJR05zWldGeVZHbHRaVzkxZENoMExsOW9LVHNLSUNCMExsOW9JRDBnYzJWMFZHbHRaVzkxZENobWRXNWpkR2x2YmlncGV5QjBMbWhwWkdSbGJpQTlJSFJ5ZFdVN0lIMHNJRE15TURBcE93cDlDZ3AzYVc1a2IzY3VWRlJCVEV3Z1BTQjdDaUFnTHlvZ2NtVm1jbVZ6YUNCbGRtVnllU0J0YjJSMWJHVXNJRzl1WlNCaFpuUmxjaUJoYm05MGFHVnlJSE52SUhSb1pTQnpaWEoyWlhJZ2FYTWdibTkwSUdocGRDQXhOU0IwYVcxbGN5QmhkQ0J2Ym1ObElDb3ZDaUFnY25WdU9pQm1kVzVqZEdsdmJpZ3Bld29nSUNBZ2FXWWdLRUpWVTFrcElISmxkSFZ5YmpzS0lDQWdJSFpoY2lCc2FYTjBJRDBnWm1sdVpHVnljeWdwT3dvZ0lDQWdhV1lnS0NGc2FYTjBMbXhsYm1kMGFDa2dleUIwYjJGemRDZ25UbTkwYUdsdVp5QjBieUJ5WldaeVpYTm9JRzl1SUhSb2FYTWdjMk55WldWdUxpY3BPeUJ5WlhSMWNtNDdJSDBLSUNBZ0lFSlZVMWtnUFNCMGNuVmxPd29nSUNBZ2RtRnlJR2tnUFNBd0xDQnZheUE5SURBc0lHSmhaQ0E5SUZ0ZE93b2dJQ0FnYzJGNUtDZFNaV1p5WlhOb2FXNW5JREF2SnlBcklHeHBjM1F1YkdWdVozUm9JQ3NnSitLQXBpY3BPd29nSUNBZ0tHWjFibU4wYVc5dUlITjBaWEFvS1hzS0lDQWdJQ0FnYVdZZ0tHa2dQajBnYkdsemRDNXNaVzVuZEdncElIc0tJQ0FnSUNBZ0lDQkNWVk5aSUQwZ1ptRnNjMlU3Q2lBZ0lDQWdJQ0FnYzJGNUtDZmlocnNnVW1WbWNtVnphQ0JoYkd3bktUc0tJQ0FnSUNBZ0lDQjBiMkZ6ZENoaVlXUXViR1Z1WjNSb0NpQWdJQ0FnSUNBZ0lDQS9JQ2duVW1WbWNtVnphR1ZrSUNjZ0t5QnZheUFySUNjZ2IyWWdKeUFySUd4cGMzUXViR1Z1WjNSb0lDc2dKeTRnSnlBcklHSmhaQzVzWlc1bmRHZ2dLeUFuSUdScFpDQnViM1FnWVc1emQyVnlMaWNwQ2lBZ0lDQWdJQ0FnSUNBNklDZ25SWFpsY25sMGFHbHVaeUJ5WldaeVpYTm9aV1FnNG9DVUlDY2dLeUJ2YXlBcklDY2dkRzl2YkNjZ0t5QW9iMnNnUFQwOUlERWdQeUFuSnlBNklDZHpKeWtnS3lBbklIVndJSFJ2SUdSaGRHVXVKeWtwT3dvZ0lDQWdJQ0FnSUhKbGRIVnlianNLSUNBZ0lDQWdmUW9nSUNBZ0lDQjJZWElnYlNBOUlHeHBjM1JiYVNzclhUc0tJQ0FnSUNBZ2MyRjVLQ2RTWldaeVpYTm9hVzVuSUNjZ0t5QnBJQ3NnSnk4bklDc2diR2x6ZEM1c1pXNW5kR2dnS3lBbjRvQ21KeWs3Q2lBZ0lDQWdJSFJ5ZVNCN0lHMHVabTR1WTJGc2JDaHRMbTl1S1RzZ2Iyc3JLenNnZlNCallYUmphQ0FvWlNrZ2V5QmlZV1F1Y0hWemFDaHRMbTVoYldVcE95QjlDaUFnSUNBZ0lITmxkRlJwYldWdmRYUW9jM1JsY0N3Z01UUXdLVHNLSUNBZ0lIMHBLQ2s3Q2lBZ2ZTd0tJQ0F2S2lCMGFHVWdhR1ZoZG5rZ2IzQjBhVzl1T2lCMGFISnZkeUJoZDJGNUlIUm9aU0JpY205M2MyVnlKM01nWTI5d2VTQnZaaUJsZG1WeWVTQm1hV3hsSUdGdVpDQnpkR0Z5ZENCamJHVmhiaUFxTHdvZ0lHaGhjbVE2SUdaMWJtTjBhVzl1S0NsN0NpQWdJQ0JwWmlBb0lYZHBibVJ2ZHk1amIyNW1hWEp0S0NkU1pXeHZZV1FnZEdobElIZG9iMnhsSUVOdmJXMWhibVFnUTJWdWRHVnlJR1p5YjIwZ2RHaGxJSE5sY25abGNqOWNibHh1UVc1NWRHaHBibWNnZVc5MUlHaGhkbVVnZEhsd1pXUWdZVzVrSUc1dmRDQnpZWFpsWkNCM2FXeHNJR0psSUd4dmMzUXVKeWtwSUhKbGRIVnlianNLSUNBZ0lIUnllU0I3Q2lBZ0lDQWdJR2xtSUNoM2FXNWtiM2N1WTJGamFHVnpJQ1ltSUdOaFkyaGxjeTVyWlhsektTQmpZV05vWlhNdWEyVjVjeWdwTG5Sb1pXNG9ablZ1WTNScGIyNG9hM01wZXlCcmN5NW1iM0pGWVdOb0tHWjFibU4wYVc5dUtHc3BleUJqWVdOb1pYTXVaR1ZzWlhSbEtHc3BPeUI5S1RzZ2ZTazdDaUFnSUNBZ0lHbG1JQ2h1WVhacFoyRjBiM0l1YzJWeWRtbGpaVmR2Y210bGNpQW1KaUJ1WVhacFoyRjBiM0l1YzJWeWRtbGpaVmR2Y210bGNpNW5aWFJTWldkcGMzUnlZWFJwYjI1ektRb2dJQ0FnSUNBZ0lHNWhkbWxuWVhSdmNpNXpaWEoyYVdObFYyOXlhMlZ5TG1kbGRGSmxaMmx6ZEhKaGRHbHZibk1vS1M1MGFHVnVLR1oxYm1OMGFXOXVLSEp6S1hzZ2NuTXVabTl5UldGamFDaG1kVzVqZEdsdmJpaHlLWHNnY2k1MWNHUmhkR1VvS1RzZ2ZTazdJSDBwT3dvZ0lDQWdmU0JqWVhSamFDQW9aU2tnZTMwS0lDQWdJSE5sZEZScGJXVnZkWFFvWm5WdVkzUnBiMjRvS1hzZ2JHOWpZWFJwYjI0dWNtVnNiMkZrS0hSeWRXVXBPeUI5TENBeU1qQXBPd29nSUgwS2ZUc0tDaWhtZFc1amRHbHZiaUJoWkdRb0tYc0tJQ0IyWVhJZ2FHUnlJRDBnWkc5amRXMWxiblF1Y1hWbGNubFRaV3hsWTNSdmNpZ25hR1ZoWkdWeUp5azdDaUFnYVdZZ0tDRm9aSElwSUhKbGRIVnliaUJ6WlhSVWFXMWxiM1YwS0dGa1pDd2dNekF3S1RzS0lDQnBaaUFvWkc5amRXMWxiblF1WjJWMFJXeGxiV1Z1ZEVKNVNXUW9KM1IwUVd4c1FuUnVKeWtwSUhKbGRIVnlianNLSUNCMllYSWdkM0poY0NBOUlHUnZZM1Z0Wlc1MExtTnlaV0YwWlVWc1pXMWxiblFvSjJScGRpY3BPd29nSUhkeVlYQXVjM1I1YkdVdVkzTnpWR1Y0ZENBOUlDZGthWE53YkdGNU9tWnNaWGc3WjJGd09qWndlRHRoYkdsbmJpMXBkR1Z0Y3pwalpXNTBaWEk3Wm14bGVEb3dJREFnWVhWMGJ5YzdDaUFnZG1GeUlHSWdQU0JrYjJOMWJXVnVkQzVqY21WaGRHVkZiR1Z0Wlc1MEtDZGlkWFIwYjI0bktUc0tJQ0JpTG1sa0lEMGdKM1IwUVd4c1FuUnVKenNnWWk1MGVYQmxJRDBnSjJKMWRIUnZiaWM3SUdJdVkyeGhjM05PWVcxbElEMGdKMkowYmljN0NpQWdZaTV6ZEhsc1pTNWpjM05VWlhoMElEMGdKMjFoY21kcGJqb3dPM2RvYVhSbExYTndZV05sT201dmQzSmhjQ2M3Q2lBZ1lpNTBaWGgwUTI5dWRHVnVkQ0E5SUNmaWhyc2dVbVZtY21WemFDQmhiR3duT3dvZ0lHSXVkR2wwYkdVZ1BTQW5VbVZzYjJGa0lHVjJaWEo1SUhSaFl1S0FtWE1nWkdGMFlTYzdDaUFnWWk1dmJtTnNhV05ySUQwZ1puVnVZM1JwYjI0b0tYc2dWRlJCVEV3dWNuVnVLQ2s3SUgwN0NpQWdkbUZ5SUdnZ1BTQmtiMk4xYldWdWRDNWpjbVZoZEdWRmJHVnRaVzUwS0NkaWRYUjBiMjRuS1RzS0lDQm9MblI1Y0dVZ1BTQW5ZblYwZEc5dUp6c2dhQzVqYkdGemMwNWhiV1VnUFNBblluUnVJR2RvYjNOMElITnRKenNLSUNCb0xuTjBlV3hsTG1OemMxUmxlSFFnUFNBbmJXRnlaMmx1T2pBN2QyaHBkR1V0YzNCaFkyVTZibTkzY21Gd0p6c0tJQ0JvTG5SbGVIUkRiMjUwWlc1MElEMGdKMGhoY21Rbk93b2dJR2d1ZEdsMGJHVWdQU0FuVW1Wc2IyRmtJSFJvWlNCM2FHOXNaU0J3WVdkbElHWnliMjBnZEdobElITmxjblpsY2ljN0NpQWdhQzV2Ym1Oc2FXTnJJRDBnWm5WdVkzUnBiMjRvS1hzZ1ZGUkJURXd1YUdGeVpDZ3BPeUI5T3dvZ0lIZHlZWEF1WVhCd1pXNWtRMmhwYkdRb1lpazdJSGR5WVhBdVlYQndaVzVrUTJocGJHUW9hQ2s3Q2lBZ2RtRnlJSFJ2YjJ4eklEMGdaRzlqZFcxbGJuUXVaMlYwUld4bGJXVnVkRUo1U1dRb0oyTmpWRzl2YkhNbktUc0tJQ0JwWmlBb2RHOXZiSE1nSmlZZ2RHOXZiSE11Y0dGeVpXNTBUbTlrWlNBOVBUMGdhR1J5S1NCb1pISXVhVzV6WlhKMFFtVm1iM0psS0hkeVlYQXNJSFJ2YjJ4ektUc0tJQ0JsYkhObElHaGtjaTVoY0hCbGJtUkRhR2xzWkNoM2NtRndLVHNLZlNrb0tUc0tmU2tvS1RzSyJ9'), true);
$patches = json_decode(base64_decode('W1siZGVzay9pbmRleC5odG1sIiwgIjxzY3JpcHQgc3JjPVwicHJvZmlsZS5qcz92PTFcIj48L3NjcmlwdD4iLCAiPHNjcmlwdCBzcmM9XCJwcm9maWxlLmpzP3Y9MVwiPjwvc2NyaXB0PlxuPHNjcmlwdCBzcmM9XCJyZWZyZXNoYWxsLmpzP3Y9MVwiIGRlZmVyPjwvc2NyaXB0PiIsICJsb2FkIFJlZnJlc2ggYWxsIl1d'), true);
if (!is_array($blocks) || !is_array($patches)) { out('PAYLOAD UNREADABLE - nothing written'); exit; }

/* 1. every patch anchor must be there BEFORE anything is written */
$ready = [];
foreach ($patches as $p) {
  list($rel, $anchor, $repl, $note) = $p;
  $f = $root . '/' . $rel;
  if (!is_file($f)) { out("MISSING FILE: $rel - nothing written"); exit; }
  $cur = (string)file_get_contents($f);
  if (strpos($cur, $repl) !== false) { out("already applied: $rel ($note)"); continue; }
  $n = substr_count($cur, $anchor);
  if ($n !== 1) { out("ANCHOR x$n in $rel ($note) - nothing written"); exit; }
  $ready[] = array($f, $rel, str_replace($anchor, $repl, $cur), $note);
}

/* 2. the new files */
$baks = 0;
foreach ($blocks as $rel => $b64) {
  $f = $root . '/' . $rel;
  $dir = dirname($f);
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { out("CANNOT MAKE DIR: " . dirname($rel)); exit; }
  if (is_file($f)) { @copy($f, $f . '.bak-' . $stamp . '-' . $tag); $baks++; }
  $body = base64_decode($b64);
  if ($body === false || strlen($body) < 100) { out("BAD PAYLOAD for $rel"); exit; }
  if (@file_put_contents($f, $body) === false) { out("CANNOT WRITE: $rel"); exit; }
  out("wrote $rel (" . strlen($body) . " bytes)");
}

/* 3. the patches */
foreach ($ready as $r) {
  list($f, $rel, $new, $note) = $r;
  $bk = $f . '.bak-' . $stamp . '-' . $tag;
  if (!is_file($bk)) { @copy($f, $bk); $baks++; }
  if (@file_put_contents($f, $new) === false) { out("PATCH WRITE FAILED: $rel"); exit; }
  out("patched $rel ($note)");
}

out('--- DONE. backups made: ' . $baks . ' ---');
