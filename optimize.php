<?php
/* Crops the client's logo out of nav.png and makes web-sized copies of his images.
   Reads and writes only inside thd/img/c/. Originals are kept. */
@ini_set('display_errors','0');
@ini_set('memory_limit','512M');
if (($_GET['go'] ?? '') !== 'b4cbc9b41d04f1b7') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(280);
$D='/home/u732570899/domains/threadedtruthsapparelco.com/public_html/thd/img/c';

function load($p){
  $i=@getimagesize($p); if(!$i) return null;
  if($i['mime']==='image/png')  return @imagecreatefrompng($p);
  if($i['mime']==='image/jpeg') return @imagecreatefromjpeg($p);
  if($i['mime']==='image/webp') return @imagecreatefromwebp($p);
  return null;
}
/* bounding box of pixels brighter than $thr */
function bbox($im,$thr=42){
  $w=imagesx($im); $h=imagesy($im);
  $x1=$w; $y1=$h; $x2=0; $y2=0;
  $step=max(1,(int)floor(min($w,$h)/400));
  for($y=0;$y<$h;$y+=$step){
    for($x=0;$x<$w;$x+=$step){
      $c=imagecolorat($im,$x,$y);
      $r=($c>>16)&255; $g=($c>>8)&255; $b=$c&255;
      if(max($r,$g,$b)>$thr){
        if($x<$x1)$x1=$x; if($x>$x2)$x2=$x;
        if($y<$y1)$y1=$y; if($y>$y2)$y2=$y;
      }
    }
  }
  if($x2<=$x1||$y2<=$y1) return null;
  return [$x1,$y1,$x2-$x1,$y2-$y1];
}
function saveJpg($im,$p,$q=84){ return @imagejpeg($im,$p,$q); }
function resize($im,$maxW){
  $w=imagesx($im); $h=imagesy($im);
  if($w<=$maxW) return $im;
  $nh=(int)round($h*$maxW/$w);
  $o=imagecreatetruecolor($maxW,$nh);
  imagecopyresampled($o,$im,0,0,0,0,$maxW,$nh,$w,$h);
  return $o;
}

/* 1. logo crop */
$src=load("$D/nav.png");
if($src){
  $b=bbox($src,40);
  if($b){
    list($x,$y,$w,$h)=$b;
    $pad=(int)round(min($w,$h)*0.03);
    $x=max(0,$x-$pad); $y=max(0,$y-$pad);
    $w=min(imagesx($src)-$x,$w+$pad*2); $h=min(imagesy($src)-$y,$h+$pad*2);
    $o=imagecreatetruecolor($w,$h);
    imagecopy($o,$src,0,0,$x,$y,$w,$h);
    $o=resize($o,700);
    @imagepng($o,"$D/logo.png",8);
    printf("logo.png  crop %d,%d %dx%d -> %dx%d  %d bytes\n",
      $x,$y,$w,$h,imagesx($o),imagesy($o),filesize("$D/logo.png"));
  } else echo "logo: no bbox found\n";
} else echo "logo: nav.png unreadable\n";

/* 2. web-sized jpegs */
$plan=['hero'=>1600,'band'=>1800,'feature'=>1200,'svc1'=>1000,'svc2'=>1000,'svc3'=>1000];
foreach($plan as $n=>$maxW){
  $im=load("$D/$n.png");
  if(!$im){ echo "FAIL $n\n"; continue; }
  $r=resize($im,$maxW);
  if(saveJpg($r,"$D/$n.jpg",84))
    printf("%-8s %dx%d  %d bytes (was %d)\n",$n.'.jpg',imagesx($r),imagesy($r),
      filesize("$D/$n.jpg"),filesize("$D/$n.png"));
  else echo "FAIL write $n\n";
}
echo "--- DONE ---\n";
