<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$pdf=file_get_contents($root.'/samples/EDDT Demand Notice.pdf');preg_match_all('/(\d+)\s+0\s+obj\s*(.*?)endobj/s',$pdf,$matches,PREG_SET_ORDER);$objects=[];foreach($matches as $m)$objects[$m[1]]=$m[2];
$stream=function(string $body): string {$pos=strpos($body,'stream');$dict=substr($body,0,$pos);$start=$pos+6;if(substr($body,$start,2)==="\r\n")$start+=2;elseif(substr($body,$start,1)==="\n")$start++;preg_match('#/Length\s+(\d+)#',$dict,$length);return substr($body,$start,(int)$length[1]);};
$dir=$root.'/app/assets';if(!is_dir($dir))mkdir($dir,0755,true);
foreach([7=>'letter-logo',9=>'letter-watermark'] as $id=>$name){$image=imagecreatefromstring($stream($objects[$id]));preg_match('#/SMask\s+(\d+)#',$objects[$id],$m);$alpha=gzuncompress($stream($objects[$m[1]]));imagealphablending($image,false);imagesavealpha($image,true);$i=0;for($y=0;$y<imagesy($image);$y++)for($x=0;$x<imagesx($image);$x++){$rgb=imagecolorat($image,$x,$y)&0xffffff;$a=127-(int)round(ord($alpha[$i++])*127/255);imagesetpixel($image,$x,$y,($a<<24)|$rgb);}imagepng($image,$dir.'/'.$name.'.png');imagedestroy($image);echo "Extracted $name from original PDF.\n";}
