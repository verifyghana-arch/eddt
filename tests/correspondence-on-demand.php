<?php
putenv('SRMS_DB_NAME=eddt_workflow_v2_test');putenv('SRMS_STORAGE_PATH=C:/xampp/htdocs/srms/var/workflow-v2-test-storage');require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;use Srms\Correspondence as Letters;use Srms\CorrespondenceOutput as Output;use Srms\Auth;
Srms\CorrespondenceStorageSchema::migrate();$n=0;$files=[];
function verify($ok,$label){global $n;if(!$ok)throw new RuntimeException('FAIL '.$label);$n++;echo "PASS $label\n";}
function role($name){$u=DB::one('SELECT u.* FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name=? LIMIT 1',[$name]);$_SESSION=['user_id'=>$u['id'],'session_version'=>$u['session_version']];return $u;}
function reject($fn,$label){try{$fn();}catch(DomainException|RuntimeException $e){verify(true,$label);return;}throw new RuntimeException('FAIL allowed '.$label);}
DB::connection()->beginTransaction();
try{
 $owner=role('Property Owner');$account=DB::scalar("SELECT account_number FROM property_ratepayers WHERE ratepayer_id=? AND relationship_type='owner' AND end_date IS NULL LIMIT 1",[$owner['ratepayer_id']]);role('Administrator');
 $bill=DB::one('SELECT * FROM ground_rent_bills WHERE account_number=? ORDER BY billing_year DESC LIMIT 1',[$account]);
 $before=(int)DB::scalar('SELECT COUNT(*) FROM documents');$storageBefore=glob(config('storage_path').'/*');
 $payment=Srms\Finance::payment(['account_number'=>$account,'amount'=>'12.34','payment_date'=>today(),'payment_method'=>'cash','request_key'=>uuid(),'payer_name'=>'Snapshot payer']);
 $ids=[];foreach(['demand_notice','invitation','receipt'] as $type){$ids[$type]=Letters::generate(['account_number'=>$account,'type'=>$type,'bill_id'=>$bill['id'],'payment_id'=>$payment,'period_from'=>'2026-10-01','period_to'=>'2026-10-31']);$out=Output::output($ids[$type],'preview',uuid());verify(str_starts_with($out['bytes'],'%PDF-'),'Render '.$type);file_put_contents(SRMS_ROOT.'/var/qa/'.['demand_notice'=>'demand','invitation'=>'invitation','receipt'=>'receipt'][$type].'.pdf',$out['bytes']);}
 verify((int)DB::scalar('SELECT COUNT(*) FROM documents')===$before&&glob(config('storage_path').'/*')===$storageBefore,'Creation and rendering do not persist PDF files');
 $id=$ids['demand_notice'];$row=Output::record($id);$snapshot=$row['content_variables_json'];$key=uuid();Output::output($id,'print',$key);Output::output($id,'print',$key);
 verify((int)DB::scalar("SELECT COUNT(*) FROM correspondence_events WHERE correspondence_id=? AND action='print'",[$id])===1,'Repeated print token records one request');
 reject(fn()=>Output::output($id,'download',$key),'Action token cannot change action');
 $events=DB::all('SELECT action,user_name FROM correspondence_events WHERE correspondence_id=?',[$id]);verify(count($events)===2&&$events[0]['user_name']!==null,'Preview and print events separate with user attribution');
 $unicodeName='Akosua Nɔŋu '.str_repeat('Ɛsi Aŋ ',25);file_put_contents(SRMS_ROOT.'/var/qa/unicode-footer.pdf',Output::render($row,$unicodeName,'2026-09-23 16:00:00'));verify(true,'Long Unicode printer name renders within reserved footer');
 $saved=Output::render($row,'QA Printer','2026-09-23 16:00:00');file_put_contents(SRMS_ROOT.'/var/qa/snapshot-before.pdf',$saved);
 DB::query("UPDATE settings SET setting_value='CHANGED ORGANIZATION' WHERE setting_key='organization_name'");DB::query("UPDATE parcel_accounts SET locality='Changed locality',credit_amount=credit_amount+100 WHERE account_number=?",[$account]);DB::query("UPDATE ratepayers SET full_name='Changed Owner' WHERE id=?",[$owner['ratepayer_id']]);
 DB::query('UPDATE property_photos SET caption=?,is_primary=0 WHERE account_number=?',['Changed caption',$account]);$oldPhoto=json_decode($snapshot,true)['photo_document_id'];if($oldPhoto)DB::update('documents',$oldPhoto,['is_current_version'=>0]);
 file_put_contents(SRMS_ROOT.'/var/qa/snapshot-after.pdf',Output::render(Output::record($id),'QA Printer','2026-09-23 16:00:00'));
 verify(Output::record($id)['content_variables_json']===$snapshot,'Owner, property, settings and photo metadata do not alter saved snapshot');
 Srms\Finance::reverse($payment,'QA reversal');file_put_contents(SRMS_ROOT.'/var/qa/receipt-reversed.pdf',Output::output($ids['receipt'],'print',uuid())['bytes']);
 verify(json_decode(Output::record($ids['receipt'])['content_variables_json'],true)['payment']['status']==='posted','Reversal preserves original receipt snapshot');
 role('Read-Only Auditor');Output::output($id,'print',uuid());reject(fn()=>Letters::generate(['type'=>'invitation','account_number'=>$account]),'Auditor cannot create');
 role('Property Owner');verify(Output::record($id)['id']===$id,'Current owner can access visible correspondence');Output::output($id,'download',uuid());
 role('Administrator');DB::update('correspondence',$id,['owner_visible'=>0]);role('Property Owner');reject(fn()=>Output::record($id),'Owner cannot access internal correspondence');
 role('Administrator');DB::update('correspondence',$id,['owner_visible'=>1]);DB::query('UPDATE property_ratepayers SET end_date=? WHERE account_number=? AND ratepayer_id=?',[today(),$account,$owner['ratepayer_id']]);role('Property Owner');reject(fn()=>Output::record($id),'Former owner denied after ownership ends');
 role('Administrator');$tampered=$row;$tampered['content_variables_json']=str_replace('Snapshot payer','OTHER',$snapshot).' ';reject(fn()=>Output::render($tampered,'QA','2026-09-23 16:00:00'),'Snapshot tampering rejected');
 $legacyPdf=Letters::pdf('<h1>Preserved legacy original</h1><p>Historical amount GHS 77.00</p>');$doc=Srms\Documents::store($legacyPdf,'legacy.pdf','invitation','parcels',$account,true);$files[]=DB::scalar('SELECT storage_key FROM documents WHERE id=?',[$doc]);
 $legacy=DB::insert('correspondence',['account_number'=>$account,'correspondence_type'=>'invitation','reference_number'=>'QA-LEGACY-'.uuid(),'printed_at'=>'2025-01-01 00:00:00','content_variables_json'=>'{}','generated_document_id'=>$doc,'created_by'=>Auth::user()['id'],'template_version'=>'missing']);
 file_put_contents(SRMS_ROOT.'/var/qa/legacy-attributed.pdf',Output::output($legacy,'print',uuid())['bytes']);verify(hash_equals(hash('sha256',$legacyPdf),hash_file('sha256',(new Srms\LocalStorage())->path($files[0]))),'Legacy fallback retains original bytes');
 for($i=0;$i<11;$i++)Letters::generate(['type'=>'invitation','account_number'=>$account,'period_from'=>today(),'period_to'=>today()]);
 $page1=Output::listing($account,1);$page2=Output::listing($account,2);verify(count($page1['rows'])===10&&count($page2['rows'])>0&&!array_intersect(array_column($page1['rows'],'id'),array_column($page2['rows'],'id')),'Sidebar pagination has ten unique records per page');
 verify(count(Srms\Reports::rows('correspondence',['account_number'=>$account]))===$page1['total'],'Correspondence report includes saved records and print metadata');
 $empty=Letters::generate(['type'=>'invitation','account_number'=>$account,'period_from'=>today(),'period_to'=>today()]);DB::update('correspondence',$empty,['template_version'=>'unavailable']);$count=DB::scalar('SELECT COUNT(*) FROM correspondence_events');reject(fn()=>Output::output($empty,'print',uuid()),'Missing template fails safely');verify(DB::scalar('SELECT COUNT(*) FROM correspondence_events')===$count,'Failed rendering creates no print event');
 echo "$n on-demand correspondence checks passed.\n";
}finally{DB::connection()->rollBack();foreach($files as $file){$path=(new Srms\LocalStorage())->path($file);if(is_file($path))unlink($path);}}
