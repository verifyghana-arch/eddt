<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$name=$argv[1]??'';if(!preg_match('/^eddt_[a-z0-9_]+$/D',$name))throw new RuntimeException('Supply a NEW database name beginning eddt_.');
$pdo=new PDO('mysql:host='.config('db_host').';port='.config('db_port').';charset=utf8mb4',config('db_user'),config('db_password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$s->execute([$name]);if($s->fetchColumn())throw new RuntimeException('Database already exists. This fresh installer never replaces or resets a database.');
$schema=json_decode(file_get_contents(SRMS_ROOT.'/database/mysql/schema.json'),true,512,JSON_THROW_ON_ERROR);$pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$pdo->exec("USE `$name`");
try{$pdo->exec('SET FOREIGN_KEY_CHECKS=0');foreach($schema['statements'] as $ddl)$pdo->exec($ddl);}finally{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
foreach($schema['roles'] as $row){$cols=array_keys($row);$s=$pdo->prepare('INSERT INTO roles (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');$s->execute(array_values($row));}
foreach($schema['versions'] as $v)$pdo->prepare('INSERT INTO schema_migrations VALUES (?,CURRENT_TIMESTAMP)')->execute([$v]);
$defaults=[
 'organization_name'=>'East Dadekotopon Development Trust (EDDT)','address'=>'No. 9 Leshie Road, East La, Accra, Ghana','email'=>'admin@eddt.org','phone'=>'','digital_address'=>'GT-0433-5976','currency'=>'GHS','number_prefix'=>'EDDT','upload_mb'=>'15',
 'map_tiles'=>Srms\MapConfig::DEFAULT_OSM,'map_attribution'=>'© OpenStreetMap contributors','map_default_basemap'=>'osm','map_osm_tiles'=>Srms\MapConfig::DEFAULT_OSM,'map_osm_attribution'=>'© OpenStreetMap contributors','map_esri_tiles'=>Srms\MapConfig::DEFAULT_ESRI,'map_esri_attribution'=>'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics and the GIS User Community','map_orthophoto_name'=>'EDDT Orthophoto','map_orthophoto_tiles'=>'','map_orthophoto_attribution'=>'East Dadekotopon Development Trust','map_orthophoto_min_zoom'=>'0','map_orthophoto_max_zoom'=>'22','map_orthophoto_opacity'=>'1',
 'invitation_body'=>Srms\LetterTemplates::INVITATION,'demand_warning'=>Srms\LetterTemplates::WARNING,'signatory'=>'ADMINISTRATOR EDDT','retention_policy'=>'Retain financial and document history. Archive operational records.','import_max_rows'=>'5000','import_chunk_rows'=>'100'
];
foreach($defaults as $k=>$v)$pdo->prepare('INSERT INTO settings VALUES (?,?)')->execute([$k,$v]);echo "Created clean MySQL database $name. No old database was modified. Set SRMS_DB_NAME and a separate SRMS_STORAGE_PATH before creating an Administrator or running demo.php.\n";
