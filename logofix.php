<?php
@ini_set('display_errors','0'); @ini_set('memory_limit','512M');
if (($_GET['go'] ?? '') !== '4e418241746d75dd') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
$D='/home/u732570899/domains/threadedtruthsapparelco.com/public_html/thd/img/c';
$src=@imagecreatefrompng("$D/logo.png");
if(!$src){ echo "cannot read logo.png\n"; exit; }
$w=imagesx($src); $h=imagesy($src);
$nw=640; $nh=(int)round($h*$nw/$w);
$o=imagecreatetruecolor($nw,$nh);
imagecopyresampled($o,$src,0,0,0,0,$nw,$nh,$w,$h);
imagejpeg($o,"$D/logo.jpg",88);
printf("logo.jpg %dx%d  %d bytes (png was %d)\n",$nw,$nh,filesize("$D/logo.jpg"),filesize("$D/logo.png"));
