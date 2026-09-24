<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;use Srms\Registry;use Srms\Finance;
if(PHP_SAPI!=='cli'||(!str_ends_with(config('db_name'),'_demo')&&!str_ends_with(config('db_name'),'_test')))throw new RuntimeException('Demo database required.');
if(DB::scalar('SELECT COUNT(*) FROM parcel_accounts')||DB::scalar('SELECT COUNT(*) FROM users'))throw new RuntimeException('Seed only a fresh, empty database.');
$access=[];$password=bin2hex(random_bytes(12));
DB::transaction(function()use(&$access,$password){
 $admin=Srms\Users::create(['full_name'=>'Justice','role_id'=>'00000000-0000-0000-0000-000000000001','phone_number'=>'+233543914765','password'=>$password],true);
 $access[]="Administrator: Justice\nPhone: +233543914765\nTemporary password: $password";
 $_SESSION=['user_id'=>$admin,'session_version'=>1];
 foreach(['Billing Officer'=>['00000000-0000-0000-0000-000000000002','+233200000002'],'Auditor'=>['00000000-0000-0000-0000-000000000003','+233200000003']] as $role=>[$roleId,$phone]){$pw=bin2hex(random_bytes(12));Srms\Users::create(['full_name'=>'Sample '.$role,'role_id'=>$roleId,'phone_number'=>$phone,'password'=>$pw]);$access[]="$role: $phone\nTemporary password: $pw";}
 $layer=Registry::save('spatial_layers',['name'=>'Sample parcels (fictional)','layer_type'=>'parcel','description'=>'Synthetic demonstration parcels; not surveyed boundaries.','is_active'=>1,'is_visible_by_default'=>1]);
 $names=['Ama Mensah','Kofi Asante','Akosua Owusu','Kwame Osei','Abena Boateng','Adjoa Darko','Yaw Addo','Efua Quaye','Nii Laryea','Adjeley Tetteh','Kojo Nyarko','Nana Sarpong'];
 foreach($names as $i=>$name){
  $number='EDDT99001'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT);
  $owner=Registry::save('ratepayers',['ratepayer_number'=>'SAMPLE-OWNER-'.($i+1),'full_name'=>$name,'ratepayer_type'=>'individual','notes'=>'Fictional demonstration owner']);
  $lat=5.604+floor($i/4)*.0012;$lng=-.113+($i%4)*.0015;
  $g=['type'=>'Polygon','coordinates'=>[[[$lng,$lat],[$lng+.0012,$lat],[$lng+.0012,$lat+.0009],[$lng,$lat+.0009],[$lng,$lat]]]];
  Registry::save('parcels',['account_number'=>$number,'spatial_layer_id'=>$layer,'locality'=>'Tse Addo (sample)','property_type'=>$i%3?'Residential':'Commercial','land_description'=>'Fictional sample property','plot_size'=>'0.25','plot_size_unit'=>'acre','latitude'=>$lat+.00045,'longitude'=>$lng+.0006,'boundary_geojson'=>json_encode($g),'notes'=>'SAMPLE DATA: synthetic boundary, owner and financial history.']);
  Registry::ownership(['account_number'=>$number,'ratepayer_id'=>$owner,'start_date'=>'2024-01-01','ownership_percentage'=>'100']);
  $doc=Srms\Documents::store(file_get_contents(SRMS_ROOT.'/samples/demo-property.jpg'),'sample-property.jpg','property_photo','parcels',$number,true);
  DB::insert('property_photos',['account_number'=>$number,'document_id'=>$doc,'caption'=>'Illustrative sample photograph; not this fictional parcel.','is_primary'=>1,'display_order'=>0,'created_by'=>$admin]);
  if($i===0){$pw=bin2hex(random_bytes(12));Srms\Users::create(['full_name'=>$name,'role_id'=>'00000000-0000-0000-0000-000000000004','ratepayer_id'=>$owner,'phone_number'=>'+233200000004','password'=>$pw]);$access[]="Property Owner: +233200000004\nTemporary password: $pw";}
  if($i%5===3){Srms\PropertyPayments::exemption($number,true,'Sample exemption for demonstration.');continue;}
  if($i%5===4)continue;
  foreach([2025,2026] as $year){$a=Finance::assessment(['account_number'=>$number,'assessment_year'=>$year,'annual_amount'=>(string)(900+$i*100)]);Finance::approve($a);$bill=Finance::issue($a,"$year-03-31");}
  if($i%5!==0){
   $total=(900+$i*100)*2;$amount=$i%5===1?intdiv($total,3):$total+($i===7?250:0);
   Finance::payment(['account_number'=>$number,'amount'=>(string)$amount,'payment_date'=>'2026-0'.(1+($i%8)).'-15','payment_method'=>['cash','bank','cheque','mobile_money'][$i%4],'payer_name'=>$name,'request_key'=>uuid(),'notes'=>'Fictional sample payment']);
  }
 }
 $bill=DB::scalar('SELECT id FROM ground_rent_bills WHERE account_number=? AND billing_year=2026',['EDDT99001001']);
 Srms\Correspondence::generate(['type'=>'demand_notice','account_number'=>'EDDT99001001','bill_id'=>$bill]);
 $payment=DB::scalar('SELECT id FROM payments WHERE account_number=?',['EDDT99001003']);
 Srms\Correspondence::generate(['type'=>'receipt','account_number'=>'EDDT99001003','payment_id'=>$payment]);
 Srms\Correspondence::generate(['type'=>'invitation','account_number'=>'EDDT99001001','period_from'=>'2026-10-01','period_to'=>'2026-10-31']);
});
file_put_contents(SRMS_ROOT.'/var/'.config('db_name').'-access.txt',"FICTIONAL SAMPLE DATABASE: ".config('db_name')."\nChange temporary passwords at first login.\n\n".implode("\n\n",$access)."\n");
echo "Seeded 12 sample parcels, owners, bills, payments, credits, exemptions and correspondence. Credentials saved privately in the database-specific access file under var/.\n";