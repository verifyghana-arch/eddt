<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;
if(PHP_SAPI!=='cli')exit(1);
if(DB::scalar('SELECT COUNT(*) FROM users')>0)throw new RuntimeException('Users already exist. Use the Administrator user-management screen.');
$phone=$argv[1]??null;if(!$phone){echo "Usage: php tools/setup.php +233240000000 [Administrator name]\nCreates a random temporary password in var/initial-access.txt (not on stdout).\n";exit(1);}
$password=bin2hex(random_bytes(12));
$id=Srms\Users::create(['full_name'=>$argv[2]??'Trust Administrator','role_id'=>'00000000-0000-0000-0000-000000000001','phone_number'=>$phone,'password'=>$password],true);
if(!is_dir(SRMS_ROOT.'/var'))mkdir(SRMS_ROOT.'/var',0700,true);
file_put_contents(SRMS_ROOT.'/var/initial-access.txt',"EDDT SRMS initial access\nURL: ".url('login')."\nPhone: ".Srms\Auth::phone($phone)."\nTemporary password: $password\nChange this password immediately after signing in. Delete this file after handover.\n");
chmod(SRMS_ROOT.'/var/initial-access.txt',0600);
echo "Administrator created. Read var/initial-access.txt locally for the temporary credentials.\n";
