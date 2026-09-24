<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;use Srms\Registry as R;use Srms\Finance as F;
if(PHP_SAPI!=='cli'||config('db_name')!=='eddt_srms_visual_test')throw new RuntimeException('Use the isolated visual-test database only.');
if(DB::scalar('SELECT COUNT(*) FROM properties')>0){echo "Visual fixtures already exist.\n";exit;}
$u=Srms\Users::create(['full_name'=>'Visual QA Administrator','phone_number'=>'+233200000099','role_id'=>'00000000-0000-0000-0000-000000000001','password'=>bin2hex(random_bytes(18))],true);DB::update('users',$u,['must_change_password'=>0]);$_SESSION=['user_id'=>$u,'session_version'=>1];
$layer=R::save('spatial_layers',['name'=>'Payment status QA','layer_type'=>'parcel','is_active'=>1,'is_visible_by_default'=>1]);
foreach(['unpaid','partial','paid','exempt','unbilled'] as $n=>$status){
 $lng=-0.14+$n*.002;$lat=5.605;$g=['type'=>'Polygon','coordinates'=>[[[$lng,$lat],[$lng+.0015,$lat],[$lng+.0015,$lat+.0015],[$lng,$lat+.0015],[$lng,$lat]]]];
 $parcel=R::save('parcels',['parcel_number'=>'QA-PARCEL-'.$n,'spatial_layer_id'=>$layer,'boundary_geojson'=>json_encode($g)]);$p=R::save('properties',['property_number'=>'QA-'.$status,'parcel_id'=>$parcel,'locality'=>'Visual test fixtures']);$rp=R::save('ratepayers',['ratepayer_number'=>'QA-OWNER-'.$n,'full_name'=>'QA Owner '.($n+1)]);R::ownership(['property_id'=>$p,'ratepayer_id'=>$rp,'start_date'=>'2024-01-01','ownership_percentage'=>'100']);
 if($status!=='unbilled'){$a=R::save('accounts',['account_number'=>'QA-ACCOUNT-'.$n,'property_id'=>$p,'opened_on'=>'2024-01-01']);foreach([(int)date('Y')-1,(int)date('Y')] as $year){$assessment=F::assessment(['property_id'=>$p,'assessment_year'=>$year,'annual_amount'=>'1000']);F::approve($assessment);F::issue($assessment,"$year-03-31");}
 if(in_array($status,['paid','partial'],true)){F::payment(['account_id'=>$a,'amount'=>$status==='paid'?'2000':'750','payment_date'=>today(),'payment_method'=>$status==='paid'?'bank':'mobile_money','request_key'=>uuid()]);}}
 if($status==='exempt')Srms\PropertyPayments::exemption($p,true,'Visual QA exemption fixture; not a real property.');
 if($n===0){foreach(['Primary gallery test','Second gallery test'] as $i=>$caption){$doc=Srms\Documents::store(file_get_contents(SRMS_ROOT.'/public/assets/eddt-logo.png'),'qa-image.png','property_photo','properties',$p,true);DB::insert('property_photos',['property_id'=>$p,'document_id'=>$doc,'caption'=>$caption.' (QA image)','is_primary'=>$i===0?1:0,'display_order'=>$i,'created_by'=>$u]);}}
}
echo "Created five payment-state fixtures with owners and two protected gallery images.\n";