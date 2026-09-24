<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$z=new ZipArchive();if($z->open($argv[1]??'')!==true)throw new RuntimeException('Cannot open export.');$manifest=json_decode($z->getFromName('manifest.json'),true,512,JSON_THROW_ON_ERROR);foreach($manifest['files'] as $path=>$expected){$bytes=$z->getFromName($path);if($bytes===false||!hash_equals($expected,hash('sha256',$bytes)))throw new RuntimeException('Integrity failure: '.$path);}echo count($manifest['files'])." files verified.\n";
