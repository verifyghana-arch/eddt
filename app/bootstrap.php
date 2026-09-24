<?php
declare(strict_types=1);
define('SRMS_ROOT', dirname(__DIR__));
$config = require SRMS_ROOT.'/config/default.php';
if (is_file(SRMS_ROOT.'/config/local.php')) $config = array_replace($config, require SRMS_ROOT.'/config/local.php');
$environment = [
 'SRMS_DB_HOST'=>'db_host','SRMS_DB_PORT'=>'db_port','SRMS_DB_NAME'=>'db_name',
 'SRMS_DB_USER'=>'db_user','SRMS_DB_PASSWORD'=>'db_password',
 'SRMS_BASE_URL'=>'base_url','SRMS_STORAGE_PATH'=>'storage_path',
];
foreach ($environment as $variable => $key) {
 $value = getenv($variable);
 if ($value !== false && $value !== '') $config[$key] = $value;
}
$GLOBALS['config'] = $config;
date_default_timezone_set($config['timezone']);
if (is_file(SRMS_ROOT.'/vendor/autoload.php')) require SRMS_ROOT.'/vendor/autoload.php';
spl_autoload_register(function ($class) {
 if (str_starts_with($class, 'Srms\\')) { $file=SRMS_ROOT.'/app/'.str_replace('\\','/',substr($class,5)).'.php'; if(is_file($file)) require $file; }
});
require __DIR__.'/helpers.php';
if (PHP_SAPI !== 'cli') {
 ini_set('display_errors','0');
 $sessionDir=SRMS_ROOT.'/var/sessions';if(!is_dir($sessionDir))mkdir($sessionDir,0700,true);session_save_path($sessionDir);
 ini_set('session.use_strict_mode','1');
 session_name($config['session_name']);
 session_set_cookie_params(['httponly'=>true,'secure'=>
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https',
'samesite'=>'Lax','path'=>'/']);
 session_start();
 if(isset($_SESSION['last_activity'])&&time()-$_SESSION['last_activity']>1800){$_SESSION=[];session_regenerate_id(true);}
 $_SESSION['last_activity']=time();
 header('X-Content-Type-Options: nosniff');
 header('X-Frame-Options: DENY');
 header('Referrer-Policy: strict-origin-when-cross-origin');
 header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
 header('Cache-Control: no-store');
}
