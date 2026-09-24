<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$bad=0;foreach(['pdo_mysql','bcmath','fileinfo','gd','zip','dom','mbstring','openssl'] as $ext){$ok=extension_loaded($ext);echo ($ok?'OK ':'MISSING ').$ext."\n";if(!$ok)$bad++;}
try{echo 'Database: '.Srms\Database::scalar('SELECT VERSION()')."\n";echo 'Migrations: '.Srms\Database::scalar('SELECT COUNT(*) FROM schema_migrations')."\n";echo 'Users: '.Srms\Database::scalar('SELECT COUNT(*) FROM users')."\n";}catch(Throwable $e){echo 'Database unavailable: '.$e->getMessage()."\n";$bad++;}
if(!class_exists(Dompdf\Dompdf::class)){echo "Run Composer install.\n";$bad++;}
echo 'Storage: '.config('storage_path')."\n";exit($bad?1:0);
