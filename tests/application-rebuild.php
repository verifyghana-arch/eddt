<?php
putenv('SRMS_DB_NAME=eddt_workflow_v2_test');
putenv('SRMS_STORAGE_PATH=C:/xampp/htdocs/srms/var/workflow-v2-test-storage');
require dirname(__DIR__).'/app/bootstrap.php';

use Srms\Auth;
use Srms\BillingRepository;
use Srms\Database as DB;
use Srms\DocumentRepository;
use Srms\MapConfig;
use Srms\Navigation;
use Srms\PaymentRepository;
use Srms\RegisterRepository;
use Srms\Settings;

$checks=0;
function rebuilt_ok(bool $condition,string $label):void{global $checks;if(!$condition)throw new RuntimeException('FAIL '.$label);$checks++;echo "PASS $label\n";}
function rebuilt_role(string $role):array{$u=DB::one('SELECT u.* FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name=? LIMIT 1',[$role]);if(!$u)throw new RuntimeException('Missing '.$role.' fixture');$_SESSION=['user_id'=>$u['id'],'session_version'=>$u['session_version']];return $u;}

Srms\AppSchema::migrate();
DB::connection()->beginTransaction();
try{
    rebuilt_role('Administrator');
    $map=MapConfig::public();
    rebuilt_ok(isset($map['basemaps']['osm'],$map['basemaps']['esri']),'OSM and Esri basemaps are configured');
    rebuilt_ok(str_contains($map['basemaps']['osm']['url'],'{z}')&&str_contains($map['basemaps']['esri']['url'],'{y}'),'Basemap URLs retain tile placeholders');
    Settings::save(['map_default_basemap'=>'esri','map_osm_tiles'=>MapConfig::DEFAULT_OSM,'map_osm_attribution'=>'OpenStreetMap contributors','map_esri_tiles'=>MapConfig::DEFAULT_ESRI,'map_esri_attribution'=>'Esri','map_orthophoto_name'=>'QA orthophoto','map_orthophoto_tiles'=>'/tiles/{z}/{x}/{y}.png','map_orthophoto_attribution'=>'EDDT','map_orthophoto_min_zoom'=>'10','map_orthophoto_max_zoom'=>'20','map_orthophoto_opacity'=>'0.65']);
    $map=MapConfig::public();
    rebuilt_ok($map['default']==='esri'&&$map['orthophoto']['opacity']===0.65,'Map choices and orthophoto are settings-backed');
    try{Settings::save(['map_osm_tiles'=>'javascript:bad/{z}/{x}/{y}']);throw new RuntimeException('Invalid URL allowed');}catch(DomainException $e){rebuilt_ok(true,'Unsafe tile URL is rejected');}

    $nav=array_column(Navigation::items(),'label');
    rebuilt_ok(in_array('Users & access',$nav,true)&&in_array('Settings',$nav,true),'Administrator navigation exposes administration');
    $properties=RegisterRepository::page('parcels',['q'=>'EDDT','page'=>1]);
    rebuilt_ok($properties['total']>=count($properties['rows'])&&count($properties['rows'])<=50,'Property register is searchable and paginated');
    $billing=BillingRepository::page(['year'=>date('Y')]);
    rebuilt_ok(isset($billing['summary']['outstanding'],$billing['rows']),'Billing repository provides summary and rows');
    $payments=PaymentRepository::page(['status'=>'posted']);
    rebuilt_ok(isset($payments['summary']['posted_total'],$payments['rows']),'Payment repository provides filtered summary and rows');
    $documents=DocumentRepository::page(['page'=>1]);
    rebuilt_ok($documents['total']>=count($documents['rows']),'Document repository is paginated');

    $owner=rebuilt_role('Property Owner');
    $ownedAccounts=array_column(DB::all('SELECT account_number FROM property_ratepayers WHERE ratepayer_id=? AND end_date IS NULL',[$owner['ratepayer_id']]),'account_number');
    $ownerNav=array_column(Navigation::items(),'label');
    rebuilt_ok(!in_array('Users & access',$ownerNav,true)&&!in_array('Data imports',$ownerNav,true),'Owner navigation hides staff administration');
    $owned=RegisterRepository::page('parcels',['page'=>1]);
    rebuilt_ok($owned['total']===count($owned['rows'])&&$owned['total']>=1,'Owner property register is isolated');
    $ownerPayments=PaymentRepository::page([]);
    rebuilt_ok(!array_filter($ownerPayments['rows'],fn($row)=>!in_array($row['account_number'],$ownedAccounts,true)),'Owner payment list is isolated');
    $ownerDocuments=DocumentRepository::page([]);
    rebuilt_ok(!array_filter($ownerDocuments['rows'],fn($row)=>empty($row['owner_visible'])||!in_array($row['account_number'],$ownedAccounts,true)),'Owner document list is isolated and visibility-filtered');
    echo "$checks application rebuild checks passed; fixture changes rolled back.\n";
}finally{DB::connection()->rollBack();}
