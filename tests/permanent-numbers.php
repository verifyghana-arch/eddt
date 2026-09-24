<?php
declare(strict_types=1);
putenv('SRMS_DB_NAME=eddt_srms_test');
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;
use Srms\Registry;
session_save_path(SRMS_ROOT.'/var/sessions');session_start();
$checks=0;
function verify(bool $value,string $name): void {global $checks;if(!$value)throw new RuntimeException($name);$checks++;echo "PASS $name\n";}
function blocked(callable $fn,string $name): void {try{$fn();}catch(DomainException|PDOException $e){verify(str_contains($e->getMessage(),'permanent')||str_contains($e->getMessage(),'cannot be deleted'),$name);return;}throw new RuntimeException("Not blocked: $name");}
DB::connection()->beginTransaction();
try{
 $admin=Srms\Users::create(['full_name'=>'Permanent-number test','role_id'=>'00000000-0000-0000-0000-000000000001','phone_number'=>'+23329'.random_int(1000000,9999999),'password'=>'Testing-Password-123'],true);
 $_SESSION=['user_id'=>$admin,'session_version'=>1];
 $number='EDDT'.random_int(10000000,99999999);
 $parcel=Registry::save('parcels',['parcel_number'=>$number]);
 $property=Registry::save('properties',['property_number'=>$number,'parcel_id'=>$parcel]);
 $account=Registry::save('accounts',['account_number'=>$number,'property_id'=>$property,'opened_on'=>today()]);
 foreach(['parcels'=>[$parcel,'parcel_number',['parcel_number'=>$number]],'properties'=>[$property,'property_number',['property_number'=>$number,'parcel_id'=>$parcel]],'accounts'=>[$account,'account_number',['account_number'=>$number,'property_id'=>$property,'opened_on'=>today()]]] as $table=>[$id,$column,$data]){
  blocked(fn()=>Registry::save($table,[$column=>'EDDT99999999']+$data,$id),"$table service rejects renumbering");
  blocked(fn()=>DB::query("UPDATE $table SET $column=? WHERE id=?",['EDDT99999999',$id]),"$table database rejects renumbering");
  blocked(fn()=>DB::query("UPDATE $table SET $column=? WHERE id=?",[strtolower($number),$id]),"$table database rejects case changes");
  blocked(fn()=>DB::query("DELETE FROM $table WHERE id=?",[$id]),"$table cannot delete issued number");
  Registry::save($table,$data+['status'=>'archived'],$id);
  verify(DB::scalar("SELECT $column FROM $table WHERE id=?",[$id])===$number,"$table archival preserves number");
 }
 $owner=Registry::save('ratepayers',['ratepayer_number'=>'TEST-'.uuid(),'full_name'=>'First owner']);
 $next=Registry::save('ratepayers',['ratepayer_number'=>'TEST-'.uuid(),'full_name'=>'Next owner']);
 Registry::ownership(['property_id'=>$property,'ratepayer_id'=>$owner,'start_date'=>today()]);
 Registry::ownership(['property_id'=>$property,'ratepayer_id'=>$next,'previous_ratepayer_id'=>$owner,'start_date'=>today()]);
 verify(DB::scalar('SELECT account_number FROM accounts WHERE id=?',[$account])===$number,'Ownership transfer preserves Account number');
 echo "$checks permanent-number checks passed; fixture changes rolled back.\n";
}finally{DB::connection()->rollBack();}