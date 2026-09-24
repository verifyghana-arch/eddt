<?php
putenv('SRMS_DB_NAME=eddt_srms_test');
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;use Srms\BillingRoll as Roll;use Srms\Registry;use Srms\Auth;
$count=0;$path=SRMS_ROOT.'/var/qa/billing-roll-test-'.uuid().'.geojson';
function check(bool $ok,string $label):void{global $count;if(!$ok)throw new RuntimeException($label);$count++;echo "PASS $label\n";}
function reject(callable $fn,string $label):void{try{$fn();}catch(DomainException|JsonException $e){check(true,$label);return;}throw new RuntimeException('Not rejected: '.$label);}
function source(array $features):string{global $path;file_put_contents($path,json_encode(['type'=>'FeatureCollection','features'=>$features],JSON_THROW_ON_ERROR));return $path;}
DB::connection()->beginTransaction();
try{
 $tag=substr(uuid(),0,8);$admin=Srms\Users::create(['full_name'=>'Roll Test','role_id'=>'00000000-0000-0000-0000-000000000001','phone_number'=>'+23329'.random_int(1000000,9999999),'password'=>'Testing-Password-123'],true);$_SESSION=['user_id'=>$admin,'session_version'=>1];
 $features=array_slice(Roll::read(SRMS_ROOT.'/simages/BillingRoll.geojson'),0,3);$parcels=[];
 foreach($features as $i=>&$feature){$feature['properties']['Account']='ROLL-'.$tag.'-'.$i;$parcels[]=Registry::save('parcels',['parcel_number'=>$feature['properties']['Account'],'boundary_geojson'=>Registry::geometry(json_encode($feature['geometry'])),'notes'=>'Preserve original metadata']);}unset($feature);
 $before=DB::all('SELECT * FROM parcels WHERE id IN (?,?,?)',$parcels);$accounts=DB::scalar('SELECT COUNT(*) FROM accounts');
 DB::query("UPDATE settings SET setting_value='2' WHERE setting_key='import_chunk_rows'");
 $batch=Roll::prepare(source($features));check(DB::scalar('SELECT valid_rows FROM import_batches WHERE id=?',[$batch])==3,'Dry run validates all source rows');check(DB::scalar("SELECT COUNT(*) FROM properties WHERE property_number LIKE ?",['ROLL-'.$tag.'%'])==0,'Dry run creates no properties');
 check(Roll::commit($batch)===1,'First chunk leaves resumable remainder');
 $conflict=Registry::save('properties',['property_number'=>'CONFLICT-'.$tag,'parcel_id'=>$parcels[2]]);
 reject(fn()=>Roll::commit($batch),'Changed parcel relationship blocks continuation');check(DB::scalar("SELECT COUNT(*) FROM import_rows WHERE batch_id=? AND status='imported'",[$batch])==2,'Failed chunk preserves earlier committed progress');
 // Resolve this test conflict by unlinking; production import never overwrites it.
 DB::update('properties',$conflict,['parcel_id'=>null]);check(Roll::commit($batch)===0,'Resume completes remaining row');check(Roll::commit($batch)===0,'Repeated completed commit is harmless');
 check($before===DB::all('SELECT * FROM parcels WHERE id IN (?,?,?)',$parcels),'Existing geometry and all parcel metadata preserved');check(DB::scalar('SELECT COUNT(*) FROM accounts')===$accounts,'Import creates no financial accounts');
 check(DB::scalar("SELECT COUNT(*) FROM properties WHERE property_number LIKE ? AND land_id IS NULL",['ROLL-'.$tag.'%'])==3,'Account identifiers become property numbers, Land IDs unassigned');
 $repeat=Roll::prepare(source($features));while(Roll::commit($repeat)>0){}check(DB::scalar("SELECT COUNT(*) FROM import_rows WHERE batch_id=? AND status='skipped'",[$repeat])==3,'Repeat file skips all linked properties');
 $bad=$features;$bad[1]['geometry']['coordinates'][0][0]=[999,999];$invalid=Roll::prepare(source($bad));check(DB::scalar('SELECT invalid_rows FROM import_batches WHERE id=?',[$invalid])==1,'Invalid geometry reported per feature');reject(fn()=>Roll::commit($invalid),'Invalid batch cannot be approved');
 $duplicates=Roll::prepare(source([$features[0],$features[0]]));check(DB::scalar('SELECT invalid_rows FROM import_batches WHERE id=?',[$duplicates])==1,'Duplicate source identifiers reported');
 $missing=$features[0];$missing['properties']['Account']='MISSING-'.$tag;$invalid=Roll::prepare(source([$missing]));check(DB::scalar('SELECT invalid_rows FROM import_batches WHERE id=?',[$invalid])==1,'Missing parcels reported without creating records');
 $other=Registry::save('parcels',['parcel_number'=>'OTHER-'.$tag]);Registry::save('properties',['property_number'=>'OTHER-'.$tag,'parcel_id'=>$parcels[0]]);reject(fn()=>Roll::match('OTHER-'.$tag),'Property number pointing at another parcel is rejected');
 $rp=Registry::save('ratepayers',['ratepayer_number'=>'ROLL-OWNER-'.$tag,'full_name'=>'Roll Owner']);$owner=Srms\Users::create(['ratepayer_id'=>$rp,'full_name'=>'Roll Owner','role_id'=>'00000000-0000-0000-0000-000000000004','phone_number'=>'+23328'.random_int(1000000,9999999),'password'=>'Testing-Password-123']);
 $_SESSION=['user_id'=>$owner,'session_version'=>1];reject(fn()=>Roll::prepare($path),'Owner cannot upload Billing Roll');reject(fn()=>Roll::commit($batch),'Owner cannot approve Billing Roll');check(count(Srms\MapRepository::features()['features'])===0,'Unassigned properties are not visible to owners');
 $_SESSION=['user_id'=>$admin,'session_version'=>1];DB::update('users',$admin,['role_id'=>'00000000-0000-0000-0000-000000000002']);reject(fn()=>Roll::commit($batch),'Billing Officer cannot approve');
 echo "$count Billing Roll checks passed. All test database changes rolled back.\n";
}finally{DB::connection()->rollBack();if(is_file($path))unlink($path);}