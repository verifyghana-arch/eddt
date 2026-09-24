<?php
// Render review artifacts in the isolated current-model fixture, never production storage.
putenv('SRMS_DB_NAME=eddt_workflow_v2_test');putenv('SRMS_STORAGE_PATH=C:/xampp/htdocs/srms/var/workflow-v2-test-storage');putenv('SRMS_BASE_URL=http://127.0.0.1:8102');
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;
if(PHP_SAPI!=='cli')exit(1);
$u=DB::one("SELECT u.* FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='Administrator' LIMIT 1");$_SESSION=['user_id'=>$u['id'],'session_version'=>$u['session_version']];
DB::connection()->beginTransaction();
try{
 $account='EDDT99001003';$bill=DB::one('SELECT * FROM ground_rent_bills WHERE account_number=? ORDER BY billing_year DESC LIMIT 1',[$account]);$payment=DB::one('SELECT * FROM payments WHERE account_number=? LIMIT 1',[$account]);
 foreach(['demand'=>'demand_notice','invitation'=>'invitation','receipt'=>'receipt'] as $name=>$type){$id=Srms\Correspondence::generate(['type'=>$type,'account_number'=>$account,'bill_id'=>$bill['id'],'payment_id'=>$payment['id'],'period_from'=>'2026-10-20','period_to'=>'2026-12-10']);$out=Srms\CorrespondenceOutput::output($id,'preview',uuid());file_put_contents(SRMS_ROOT.'/var/qa/'.$name.'.pdf',$out['bytes']);echo "$name review PDF rendered.\n";}
}finally{DB::connection()->rollBack();}
