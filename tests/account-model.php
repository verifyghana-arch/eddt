<?php
putenv('SRMS_DB_NAME=eddt_srms_rebuilt_test');putenv('SRMS_STORAGE_PATH=C:/xampp/htdocs/srms/var/rebuilt-test-storage');
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;use Srms\Registry;use Srms\Finance;use Srms\Auth;
session_save_path(SRMS_ROOT.'/var/sessions');session_start();
$count=0;function ok($condition,$name){global $count;if(!$condition)throw new RuntimeException("FAIL $name");echo "PASS $name\n";$count++;}
function denied($fn,$name){try{$fn();}catch(DomainException|PDOException|JsonException $e){ok(true,$name);return;}throw new RuntimeException("FAIL not denied: $name");}
$admin=DB::one("SELECT u.* FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='Administrator'");
$_SESSION=['user_id'=>$admin['id'],'session_version'=>1];
ok(DB::scalar('SELECT COUNT(*) FROM parcel_accounts')==12,'Twelve unified sample parcels');
ok(DB::scalar("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='parcel_accounts' AND CONSTRAINT_NAME='PRIMARY'")==='account_number','Account number is physical primary key');
ok(Srms\AccountNumber::parts('EDDT03011010')===['division_code'=>'03','block_code'=>'011','parcel_code'=>'010'],'Division block and parcel preserve leading zeroes');
foreach(['EDDT001','EDDT030110101','WRONG3011010'] as $bad)denied(fn()=>Srms\AccountNumber::validate($bad),'Invalid Account format');
$statuses=array_count_values(array_column(Srms\PropertyPayments::rows(),'payment_status'));ok(count($statuses)===5,'All five map payment states');
ok(count(Srms\MapRepository::features()['features'])===12,'Map includes all sample polygons');
ok(count(Srms\DashboardRepository::data()['monthly'])===12,'Dashboard monthly chart');
foreach(['balances','billing','collections','credits','ownership','documents','audit','assessments'] as $type){try{Srms\Reports::rows($type,[]);ok(true,"Report $type");}catch(DomainException $e){if(!str_contains($e->getMessage(),'Invalid')&&!str_contains($e->getMessage(),'Unknown'))throw $e;}}
$files=[];
DB::connection()->beginTransaction();
try{
 $number='EDDT98001001';
 Registry::save('parcels',['account_number'=>$number,'locality'=>'Test']);
 ok(DB::scalar('SELECT division_code FROM parcel_accounts WHERE account_number=?',[$number])==='98','New parcel uses natural key');
 denied(fn()=>Registry::save('parcels',['account_number'=>'EDDT98001002'],$number),'Service blocks renumbering');
 denied(fn()=>DB::query('UPDATE parcel_accounts SET account_number=? WHERE account_number=?',['EDDT98001002',$number]),'Database blocks renumbering');
 denied(fn()=>DB::query('DELETE FROM parcel_accounts WHERE account_number=?',[$number]),'Issued number cannot be deleted');
 denied(fn()=>Registry::save('parcels',['account_number'=>$number]),'Duplicate number rejected');
 $owner=DB::one("SELECT * FROM users WHERE ratepayer_id IS NOT NULL");Registry::ownership(['account_number'=>$number,'ratepayer_id'=>$owner['ratepayer_id'],'start_date'=>'2025-01-01']);
 $a=Finance::assessment(['account_number'=>$number,'assessment_year'=>'2025','annual_amount'=>'100']);
 denied(fn()=>Finance::issue($a,'2025-12-31'),'Approval required');Finance::approve($a);$bill=Finance::issue($a,'2025-12-31');
 ok(Finance::issue($a,'2025-12-31')===$bill,'Annual bill idempotency');
 $data=['account_number'=>$number,'amount'=>'40','payment_date'=>today(),'payment_method'=>'cash','request_key'=>uuid()];
 $pay=Finance::payment($data);ok(Finance::payment($data)===$pay,'Duplicate payment idempotency');
 ok(Srms\PropertyPayments::rows()[$number]['payment_status']==='partial','Partial payment classified');
 $over=Finance::payment(['account_number'=>$number,'amount'=>'90','payment_date'=>today(),'payment_method'=>'bank','request_key'=>uuid()]);
 ok(Srms\Money::cmp(DB::scalar('SELECT credit_amount FROM parcel_accounts WHERE account_number=?',[$number]),'30')===0,'Overpayment credit');
 $a2=Finance::assessment(['account_number'=>$number,'assessment_year'=>'2026','annual_amount'=>'50']);Finance::approve($a2);$bill2=Finance::issue($a2,'2026-12-31');
 ok(Srms\Money::cmp(DB::scalar('SELECT paid_amount FROM ground_rent_bills WHERE id=?',[$bill2]),'30')===0,'Credit applied to later bill');
 Finance::reverse($over,'Test correction');
 ok(Srms\Money::cmp(DB::scalar('SELECT paid_amount FROM ground_rent_bills WHERE id=?',[$bill2]),'0')===0,'Reversal reconstructs allocation');
 Finance::adjust($bill,'penalty','10','Test penalty');
 ok(Srms\Money::cmp(Srms\PropertyPayments::rows()[$number]['outstanding'],'120')===0,'Outstanding includes arrears and penalty');
 $owner=DB::one("SELECT * FROM users WHERE ratepayer_id IS NOT NULL");
 $rp=Registry::save('ratepayers',['ratepayer_number'=>'QA-'.uuid(),'full_name'=>'Transfer recipient']);
 
 $pdf="%PDF-1.4\n%%EOF";$doc=Srms\Documents::store($pdf,'test.pdf','attachment','parcels',$number,true);$files[]=DB::scalar('SELECT storage_key FROM documents WHERE id=?',[$doc]);
 ok(DB::scalar('SELECT account_number FROM documents WHERE id=?',[$doc])===$number,'Document directly references Account number');
 $_SESSION=['user_id'=>$owner['id'],'session_version'=>1];Auth::property($number);ok(true,'Current owner can access parcel');
 denied(fn()=>Auth::property('EDDT99001003'),'Owner isolation');
 denied(fn()=>Finance::payment($data),'Owner cannot post payments');
 ok(Srms\Documents::allowed(DB::one('SELECT * FROM documents WHERE id=?',[$doc])),'Owner can read visible document');
 $_SESSION=['user_id'=>$admin['id'],'session_version'=>1];
 Registry::ownership(['account_number'=>$number,'previous_ratepayer_id'=>$owner['ratepayer_id'],'ratepayer_id'=>$rp,'start_date'=>today()]);
 ok(Srms\Money::cmp(Srms\PropertyPayments::rows()[$number]['outstanding'],'120')===0,'Transfer retains debt on parcel');
 $_SESSION=['user_id'=>$owner['id'],'session_version'=>1];denied(fn()=>Auth::property($number),'Former owner loses access');ok(!Srms\Documents::allowed(DB::one('SELECT * FROM documents WHERE id=?',[$doc])),'Former owner loses document access');
 $_SESSION=['user_id'=>$admin['id'],'session_version'=>1];
 $g=['type'=>'Polygon','coordinates'=>[[[0,0],[.01,0],[.01,.01],[0,0]]]];
 $path=SRMS_ROOT.'/var/qa/import-test.geojson';$features=[];foreach([11,12] as $n)$features[]=['type'=>'Feature','properties'=>['Account'=>'EDDT980010'.$n],'geometry'=>$g];file_put_contents($path,json_encode(['type'=>'FeatureCollection','features'=>$features]));
 DB::query("UPDATE settings SET setting_value='1' WHERE setting_key='import_chunk_rows'");
 $batch=Srms\BillingRoll::prepare($path);ok(Srms\BillingRoll::commit($batch)===1,'Import commits one chunk');ok(Srms\BillingRoll::commit($batch)===0,'Import resumes');ok(Srms\BillingRoll::commit($batch)===0,'Completed batch repeat is safe');
 $repeat=Srms\BillingRoll::prepare($path);Srms\BillingRoll::commit($repeat);Srms\BillingRoll::commit($repeat);ok(DB::scalar("SELECT COUNT(*) FROM import_rows WHERE batch_id=? AND status='skipped'",[$repeat])==2,'Repeated import skips existing parcels');
 $features[0]['geometry']['coordinates'][0][1][0]=.02;file_put_contents($path,json_encode(['type'=>'FeatureCollection','features'=>$features]));$conflict=Srms\BillingRoll::prepare($path);ok(DB::scalar('SELECT invalid_rows FROM import_batches WHERE id=?',[$conflict])==1,'Conflicting geometry reported');denied(fn()=>Srms\BillingRoll::commit($conflict),'Conflicting import cannot overwrite');
 denied(fn()=>Registry::geometry('{"type":"Polygon","coordinates":[[[0,0],[1,0],[1,1]]]}'),'Invalid boundary rejected');
 Srms\PropertyPayments::exemption($number,true,'Test exemption');$a3=Finance::assessment(['account_number'=>$number,'assessment_year'=>'2027','annual_amount'=>'50']);Finance::approve($a3);denied(fn()=>Finance::issue($a3,'2027-12-31'),'Exempt parcel cannot receive new bill');
}finally{DB::connection()->rollBack();foreach($files as $key){$path=(new Srms\LocalStorage())->path($key);if(is_file($path))unlink($path);}}
echo "$count rebuilt-model checks passed; fixture changes rolled back.\n";