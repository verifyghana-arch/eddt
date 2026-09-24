<?php
putenv('SRMS_DB_NAME=eddt_workflow_v2_test');putenv('SRMS_STORAGE_PATH=C:/xampp/htdocs/srms/var/workflow-v2-test-storage');require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;
$base='http://127.0.0.1:8102/index.php';$cookie=tempnam(sys_get_temp_dir(),'srms-http-');$n=0;
function httpCall(string $route,?array $data=null):array{global $base,$cookie;$ch=curl_init($base.'?r='.$route);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_TIMEOUT=>60]);if($data!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data)]);$body=curl_exec($ch);if($body===false)throw new RuntimeException(curl_error($ch));$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return [$code,$body];}
function testHttp($ok,$label){global $n;if(!$ok)throw new RuntimeException('FAIL '.$label);$n++;echo "PASS $label\n";}
try{
 $id=DB::scalar('SELECT id FROM correspondence LIMIT 1');[$status]=httpCall('correspondence-output',['id'=>$id,'action'=>'preview','request_key'=>uuid()]);testHttp(in_array($status,[302,403,422]),'Anonymous PDF request blocked');
 [$status,$body]=httpCall('login');preg_match('/name="csrf" value="([^"]+)"/',$body,$csrf);$access=file_get_contents(SRMS_ROOT.'/var/eddt_workflow_v2_test-access.txt');preg_match('/Phone:\s*(\S+)/',$access,$phone);preg_match('/Temporary password:\s*(\S+)/',$access,$password);
 [$status]=httpCall('login',['csrf'=>$csrf[1],'phone'=>$phone[1],'password'=>$password[1]]);testHttp($status===302,'Test Administrator login');
 [$status,$body]=httpCall('documents');preg_match('/name="csrf" value="([^"]+)"/',$body,$csrf);testHttp($status===200&&str_contains($body,'correspondence-actions'),'Document library exposes on-demand actions');
 [$status]=httpCall('correspondence-output',['id'=>$id,'action'=>'print','request_key'=>uuid(),'csrf'=>'wrong']);testHttp($status===422,'Print request requires CSRF');
 $key=uuid();$post=['csrf'=>$csrf[1],'id'=>$id,'action'=>'print','request_key'=>$key];[$status,$body]=httpCall('correspondence-output',$post);testHttp($status===200&&str_starts_with($body,'%PDF-'),'Print POST streams authenticated PDF');
 [$status,$body]=httpCall('correspondence-output',$post);testHttp($status===200&&(int)DB::scalar('SELECT COUNT(*) FROM correspondence_events WHERE correspondence_id=? AND request_key=?',[$id,$key])===1,'HTTP retry counts one print request');
 [$status,$body]=httpCall('detail&type=parcels&id=EDDT99001001');testHttp($status===200&&str_contains($body,'property-correspondence'),'Property sidebar renders');
 [$status,$body]=httpCall('letter-batches');testHttp($status===200&&str_contains($body,'Choose recipients'),'Batch page renders');
 [$status,$body]=httpCall('reports&type=correspondence');testHttp($status===200&&str_contains($body,'Print Requests'),'Correspondence report route renders');
 [$status,$body]=httpCall('payments');testHttp($status===200&&str_contains($body,'correspondence-output'),'Payment rows link directly to saved correspondence');
 [$status,$body]=httpCall('geojson');$geo=json_decode($body,true);testHttp($status===200&&($geo['type']??'')==='FeatureCollection'&&count($geo['features'])>0,'Authenticated map GeoJSON export remains available');
 echo "$n HTTP correspondence checks passed.\n";
}finally{if(is_file($cookie))unlink($cookie);}
