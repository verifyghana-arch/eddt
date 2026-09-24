<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$dir=SRMS_ROOT.'/var/backups';if(!is_dir($dir))mkdir($dir,0700,true);
$name='srms-'.date('Ymd-His').'-'.substr(uuid(),0,8);$sql=$dir.'/'.$name.'.sql';$dest=$dir.'/'.$name.'.zip';
$mysql=getenv('SRMS_MYSQL_BIN')?:'C:/xampp/mysql/bin';$env=getenv();$env['MYSQL_PWD']=config('db_password');
$args=[$mysql.'/mysqldump.exe','--host='.config('db_host'),'--port='.config('db_port'),'--user='.config('db_user'),'--single-transaction','--skip-lock-tables','--hex-blob','--skip-add-drop-table','--result-file='.$sql,config('db_name')];
$process=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);if(!is_resource($process))throw new RuntimeException('Cannot start mysqldump.');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0)throw new RuntimeException('Backup failed: '.$err);
$zip=new ZipArchive();if($zip->open($dest,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Cannot create backup archive.');$zip->addFile($sql,'database.sql');$manifest=['format'=>'eddt-operational-backup-1','created_at'=>date('c'),'database'=>config('db_name'),'files'=>['database.sql'=>hash_file('sha256',$sql)]];
foreach(glob(rtrim(config('storage_path'),'/\\').'/*')?:[] as $file){if(!is_file($file))continue;$key=basename($file);if(!preg_match('/^[a-f0-9-]{36}\.(pdf|jpg|png)$/',$key))continue;$zip->addFile($file,'documents/'.$key);$manifest['files']['documents/'.$key]=hash_file('sha256',$file);}
foreach(array_merge(glob(SRMS_ROOT.'/app/views/letters/v*.php'),glob(SRMS_ROOT.'/app/assets/letters/v*/*.png')) as $file){$relative=str_replace('\\\\','/',substr($file,strlen(SRMS_ROOT)+1));$zip->addFile($file,'application/'.$relative);$manifest['files']['application/'.$relative]=hash_file('sha256',$file);}
$zip->addFromString('manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));if(!$zip->close())throw new RuntimeException('Backup finalization failed.');unlink($sql);echo "Operational backup created: $dest\nContains password hashes and private records. Restrict and encrypt offsite copies.\n";
