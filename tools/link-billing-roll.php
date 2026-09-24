<?php
// CLI operator must select an existing active Administrator; all approval is audited.
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$options=getopt('',['admin:','file:','batch:','commit']);
if(empty($options['admin'])){fwrite(STDERR,"Usage: php tools/link-billing-roll.php --admin=USER_UUID [--file=PATH | --batch=BATCH_UUID] [--commit]\nWithout --commit this only validates. Commit creates a backup before processing.\n");exit(1);}
try{
 $user=Srms\Database::one("SELECT u.id,u.session_version FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1 AND r.name='Administrator'",[$options['admin']]);
 if(!$user)throw new DomainException('Select an existing active Administrator.');
 $_SESSION=['user_id'=>$user['id'],'session_version'=>$user['session_version']];
 $batch=$options['batch']??Srms\BillingRoll::prepare($options['file']??SRMS_ROOT.'/simages/BillingRoll.geojson');
 $row=Srms\Database::one('SELECT * FROM import_batches WHERE id=?',[$batch]);
 if(!$row||$row['entity_type']!=='billing_roll')throw new DomainException('Billing Roll batch not found.');
 echo "Batch $batch: {$row['valid_rows']} valid, {$row['invalid_rows']} invalid.\n";
 if($row['invalid_rows']){foreach(Srms\Database::all("SELECT row_number,validation_errors FROM import_rows WHERE batch_id=? AND status='invalid' ORDER BY row_number LIMIT 20",[$batch]) as $r)echo "Feature {$r['row_number']}: {$r['validation_errors']}\n";exit(2);}
 if(array_key_exists('commit',$options)){
  $process=proc_open([PHP_BINARY,SRMS_ROOT.'/tools/operational-backup.php'],[STDIN,STDOUT,STDERR],$pipes);
  if(!is_resource($process)||proc_close($process)!==0)throw new RuntimeException('Backup failed; import stopped.');
  do{$remaining=Srms\BillingRoll::commit($batch);echo "$remaining remaining\n";}while($remaining>0);
  echo "Linked properties complete. Existing parcels and financial records preserved.\n";
 }
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}