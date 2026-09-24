<?php
namespace Srms;
final class Application {
 public static function normalizeType(?string $type): ?string {
  if($type===null)return null;
  return match($type){'properties'=>'parcels','property'=>'parcels','accounts'=>'parcels','account'=>'parcels',default=>$type};
 }
 public function run(): void {
  try{
   $originalType=$_GET['type'] ?? null;
   $_GET['type']=self::normalizeType($originalType) ?? '';
   if(in_array((string)($originalType ?? ''),['properties','accounts','parcels'],true)){
 $legacyType=(string)$originalType;$_GET['type']='parcels';
 if(!empty($_GET['account_number']))$_GET['id']=AccountNumber::validate($_GET['account_number']);
 elseif(!empty($_GET['id']))$_GET['id']=AccountNumber::resolve($_GET['id'],$legacyType);
}
if(($_GET['r']??'')==='property-preview'&&!empty($_GET['account_number']))$_GET['id']=AccountNumber::validate($_GET['account_number']);
if(isset($_POST['type']))$_POST['type']=self::normalizeType((string)$_POST['type']);
$route=(string)($_GET['r']??'dashboard');$user=Auth::user();
   if($route==='health'){ $ready=extension_loaded('gd')&&extension_loaded('zip')&&extension_loaded('bcmath')&&class_exists(\Dompdf\Dompdf::class)&&Database::scalar("SELECT COUNT(*) FROM schema_migrations WHERE version='003_identity_templates'");json_response(['status'=>$ready?'ready':'unavailable'],$ready?200:503); }
   if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals(csrf(),(string)($_POST['csrf']??'')))throw new \DomainException('Your session expired. Refresh the page and try again.');
    if($route==='login'){if(Auth::login((string)($_POST['phone']??''),(string)($_POST['password']??'')))redirect('dashboard');throw new \DomainException('Phone number or password is incorrect.');}
    if(!$user)redirect('login');
    if($user['must_change_password']&&$route!=='password'&&$route!=='logout')redirect('password');
    $this->post($route);
   }
   if($route==='login'){if($user)redirect();$this->view('login',['title'=>'Welcome back']);return;}
   if(!$user)redirect('login');if($user['must_change_password']&&$route!=='password')redirect('password');
   if($route==='download')Documents::download(require_value($_GET,'id'),!empty($_GET['inline']));
   if($route==='export')Reports::export(require_value($_GET,'type'),require_value($_GET,'format'),$_GET);
   if($route==='template')Imports::template(require_value($_GET,'type'));
   if($route==='property-preview'){json_response(PropertyPreview::get(require_value($_GET,'id')));}
   if($route==='map-data'){json_response(MapRepository::features());}
   if($route==='geojson'){header('Content-Type: application/geo+json');header('Content-Disposition: attachment; filename="eddt-properties.geojson"');echo json_encode(MapRepository::features(),JSON_THROW_ON_ERROR);return;}
   if($route==='import-progress'){Auth::admin();$id=require_value($_GET,'id');json_response(Database::one('SELECT * FROM import_batches WHERE id=?',[$id]));}
   if($route==='letter-batch-download')redirect('letter-batches',['id'=>require_value($_GET,'id')]);
   if($route==='lookups'){json_response($this->lookups());}
   $this->page($route);
  }catch(\DomainException|\JsonException $e){$this->error($e->getMessage(),422);}catch(\Throwable $e){$ref=bin2hex(random_bytes(4));$dir=SRMS_ROOT.'/var/logs';if(!is_dir($dir))mkdir($dir,0700,true);error_log(date('c').' '.$ref.' '.$e."\n",3,$dir.'/application.log');$message=$e instanceof \PDOException&&$e->getCode()==='23000'?'That identifier is already used or the record is still linked to other records.':'The request could not be completed. Reference: '.$ref.'. Check the application log.';$this->error($message,500);}
 }
 private function error(string $message,int $code): void {http_response_code($code);if(in_array($_GET['r']??'',['map-data','property-preview','import-progress','import-run','lookups','letter-batch-run'],true))json_response(['error'=>$message],$code);$this->view('error',['title'=>'Unable to complete request','message'=>$message]);}
 private function view(string $file,array $data=[]): void {extract($data);$user=Auth::user();ob_start();require SRMS_ROOT.'/app/views/'.$file.'.php';$content=ob_get_clean();require SRMS_ROOT.'/app/views/layout.php';}
 private function post(string $route): never {$d=$_POST;switch($route){
  case 'logout':Audit::log('logout','users',Auth::user()['id']);$_SESSION=[];session_destroy();redirect('login');
  case 'password':Auth::changePassword(require_value($d,'current_password'),require_value($d,'new_password'));flash('Password updated.');redirect();
  case 'save':$type=require_value($d,'type');$id=Registry::save($type,$d,trim($d['id']??'')?:null);flash('Record saved.');if($type==='parcels'&&empty($d['id']))redirect('property-workflow',['account_number'=>$id]);redirect('detail',['type'=>$type,'id'=>$id]);
  case 'exemption':if(!in_array($d['is_exempt']??'', ['0','1'],true))throw new \DomainException('Choose Yes or No for exemption.');PropertyPayments::exemption(require_value($d,'account_number'),$d['is_exempt']==='1',require_value($d,'reason'));flash('Exemption updated. Existing bills and balances are preserved.');redirect('detail',['type'=>'properties','id'=>$d['account_number']]);
  case 'workflow-action':PropertyWorkflow::act($d);flash('Workflow step completed.');redirect('property-workflow',['account_number'=>$d['account_number']]);
  case 'ownership':Registry::ownership($d);flash('Ownership recorded. Account balances are preserved.');redirect('property-workflow',['account_number'=>$d['account_number']]);
  case 'phone':Registry::phone($d);flash('Phone saved.');redirect('detail',['type'=>'ratepayers','id'=>$d['ratepayer_id']]);
  case 'assessment':Finance::assessment($d);flash('Assessment submitted for Administrator approval.');redirect('billing');
  case 'approve':Finance::approve(require_value($d,'id'));flash('Assessment approved.');redirect('billing');
  case 'issue':Finance::issue(require_value($d,'id'),require_value($d,'due_date'));flash('Bill issued and available credit applied.');redirect('billing');
  case 'batch':$n=Finance::batch((int)$d['year'],require_value($d,'due_date'));flash("Processed $n approved assessments. Existing annual bills were preserved.");redirect('billing');
  case 'payment':$id=Finance::payment($d);flash('Payment posted. You can now generate its receipt.');redirect('payments');
  case 'reverse':Finance::reverse(require_value($d,'id'),require_value($d,'reason'));flash('Payment reversed and account reconciled.');redirect('payments');
  case 'adjust':Finance::adjust(require_value($d,'id'),require_value($d,'adjustment_type'),require_value($d,'amount'),require_value($d,'reason'));flash('Adjustment recorded.');redirect('billing');
  case 'upload':$id=Documents::upload($_FILES['file']??[],$d);flash('Document uploaded.');redirect('documents');
  case 'photo':Documents::photo($d);$photo=Database::one('SELECT account_number FROM property_photos WHERE id=?',[$d['id']]);flash('Photo updated.');redirect($photo?'detail':'documents',$photo?['type'=>'parcels','id'=>$photo['account_number']]:[]);
  case 'correspondence':$id=Correspondence::generate($d);flash('Correspondence record saved. Preview, print or download it from the sidebar.');redirect('detail',['type'=>'parcels','id'=>$d['account_number']]);
  case 'correspondence-output':CorrespondenceOutput::send(require_value($d,'id'),require_value($d,'action'),require_value($d,'request_key'));
  case 'letter-batch-download':$bytes=CorrespondenceBatch::merged(require_value($d,'id'),require_value($d,'request_key'),$d['action']??'download');header('Content-Type: application/pdf');header('Content-Disposition: '.(($d['action']??'download')==='download'?'attachment':'inline').'; filename="eddt-batch-letters.pdf"');echo $bytes;exit;
  case 'letter-batch-create':$id=CorrespondenceBatch::create($d);redirect('letter-batches',['id'=>$id]);
  case 'letter-batch-run':$result=CorrespondenceBatch::run(require_value($d,'id'));if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json'))json_response($result);redirect('letter-batches',['id'=>$d['id']]);
  case 'billing-roll-upload':$id=BillingRoll::upload($_FILES['file']??[]);flash('Billing Roll validation complete. Review conflicts before Administrator approval.');redirect('imports',['id'=>$id]);
  case 'import-upload':$id=Imports::upload($_FILES['file']??[],require_value($d,'type'));flash('Dry run complete. Review the results before committing.');redirect('imports',['id'=>$id]);
  case 'import-run':$remaining=Imports::commit(require_value($d,'id'));json_response(['remaining'=>$remaining]);
  case 'import-commit':$remaining=Imports::commit(require_value($d,'id'));flash($remaining?"Chunk committed. $remaining rows remain; continue this batch.":'Import completed.');redirect('imports',['id'=>$d['id']]);
  case 'settings':Database::transaction(fn()=>Settings::save($d));flash('Settings saved.');redirect('settings');
  case 'user-create':Users::create($d);flash('User created. A password change is required at first login.');redirect('users');
  case 'user-update':Users::update($d);flash('User updated; existing sessions revoked.');redirect('users');
  case 'geojson-import':Auth::staff();throw new \DomainException('Use Data imports to upload and validate GeoJSON before Administrator approval.');
  case 'backup':Auth::admin();Backup::download();
  default:throw new \DomainException('Unknown action.');
 }}
 private function page(string $route): void {switch($route){
  case 'workflows':if(Auth::owner())throw new \DomainException('Staff workflow only.');$rows=PropertyWorkflow::rows($_GET);$counts=array_count_values(array_column($rows,'stage'));$stage=$_GET['stage']??'';if($stage!==''&&!isset(PropertyWorkflow::STAGES[$stage]))throw new \DomainException('Unknown workflow stage.');$matching=array_values(array_filter($rows,fn($r)=>!$stage||$r['stage']===$stage));$page=max(1,min((int)($_GET['page']??1),max(1,(int)ceil(count($matching)/25))));$this->view('workflows',compact('matching','counts','stage','page')+['title'=>'Property workflows']);break;
  case 'property-workflow':$property=PropertyWorkflow::get(AccountNumber::validate(require_value($_GET,'account_number')));$this->view('workflows',compact('property')+['title'=>'Property workspace']);break;
  case 'dashboard':$this->view('dashboard',['title'=>'Overview','dashboard'=>DashboardRepository::data($_GET)]);break;
  case 'register':$type=require_value($_GET,'type');$this->view('register',RegisterRepository::page($type,$_GET)+['title'=>self::label($type)]);break;
  case 'edit':Auth::staff();$type=require_value($_GET,'type');if(!isset(Registry::FIELDS[$type]))throw new \DomainException('Unknown register.');$record=empty($_GET['id'])?[]:(Database::one('SELECT * FROM '.$type.' WHERE id=?',[$_GET['id']])??throw new \DomainException('Record not found.'));$this->view('edit',['title'=>($record?'Edit ':'New ').self::label($type),'type'=>$type,'record'=>$record]);break;
  case 'detail':$type=require_value($_GET,'type');$id=require_value($_GET,'id');if(!isset(Registry::FIELDS[$type]))throw new \DomainException('Unknown register.');if($type==='parcels')Auth::property($id);elseif($type==='accounts')Auth::account($id);elseif(Auth::owner())throw new \DomainException('Staff record only.');$record=Database::one('SELECT * FROM '.$type.' WHERE id=?',[$id])??throw new \DomainException('Record not found.');$this->view('detail',['title'=>self::label($type).' details','type'=>$type,'record'=>$record]);break;
  case 'billing':if(Auth::owner()){$this->view('reports',['title'=>'My bills','type'=>'billing','rows'=>Reports::rows('billing',$_GET)]);break;}$this->view('billing',BillingRepository::page($_GET)+['title'=>'Ground-rent billing']);break;
  case 'payment-new':
   Auth::staff();$query=mb_substr(trim((string)($_GET['q']??'')),0,100);$page=max(1,min(10000,(int)($_GET['page']??1)));$matches=PaymentPropertySearch::find($query,$page);$hasMore=count($matches)>20;$matches=array_slice($matches,0,20);$selected=null;$owners=[];$outstanding='0';
   if(!empty($_GET['account_number'])){$number=AccountNumber::validate($_GET['account_number']);$selected=Auth::account($number);$owners=PropertyPreview::get($number)['owners'];$outstanding=(string)Database::scalar('SELECT COALESCE(SUM(principal_amount+penalty_amount+adjustment_amount-paid_amount),0) FROM ground_rent_bills WHERE account_number=?',[$number]);}
   $this->view('payment-new',compact('query','page','matches','hasMore','selected','owners','outstanding')+['title'=>'Record payment']);break;
  case 'payments':$this->view('payments',PaymentRepository::page($_GET)+['title'=>Auth::owner()?'My payments':'Payments & receipts']);break;
  case 'letter-batches':CorrespondenceBatch::readAccess();$batch=empty($_GET['id'])?null:CorrespondenceBatch::get($_GET['id']);$items=$batch?Database::all('SELECT * FROM correspondence_batch_items WHERE batch_id=? ORDER BY account_number',[$batch['id']]):[];$preview=isset($_GET['preview'])?CorrespondenceBatch::candidates(CorrespondenceBatch::parameters($_GET)):null;$batches=Database::all('SELECT * FROM correspondence_batches ORDER BY created_at DESC LIMIT 50');$this->view('letter-batches',compact('batch','items','preview','batches')+['title'=>'Batch letters']);break;
  case 'documents':$this->view('documents',DocumentRepository::page($_GET)+['title'=>'Documents & correspondence']);break;
  case 'reports':$type=$_GET['type']??'balances';$this->view('reports',['title'=>'Reports & insights','type'=>$type,'rows'=>Reports::rows($type,$_GET)]);break;
  case 'imports':if(Auth::owner())throw new \DomainException('Staff access required.');$batch=empty($_GET['id'])?null:Database::one('SELECT * FROM import_batches WHERE id=?',[$_GET['id']]);$rows=$batch?Database::all(Database::page('SELECT * FROM import_rows WHERE batch_id=? ORDER BY row_number',max(1,(int)($_GET['page']??1)),100),[$batch['id']]):[];$this->view('imports',['title'=>'Data imports','batches'=>Database::all('SELECT * FROM import_batches ORDER BY created_at DESC'),'batch'=>$batch,'rows'=>$rows]);break;
  case 'users':Auth::admin();$this->view('users',['title'=>'Users & access','users'=>Database::all('SELECT u.*,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.full_name')]);break;
  case 'settings':Auth::admin();$this->view('settings',['title'=>'System settings','settings'=>Settings::all()]);break;
  case 'map':$this->view('map',['title'=>'Property map']);break;
  case 'password':$this->view('password',['title'=>'Change password']);break;
  default:http_response_code(404);$this->view('error',['title'=>'Page not found','message'=>'The requested page does not exist.']);
 }}
 public static function label(string $type): string {return match($type){'properties'=>'Parcels / Properties','parcels'=>'Parcels / Properties','ratepayers'=>'Property owners','spatial_layers'=>'Map layers','ground_rent_bills'=>'Bills',default=>ucwords(str_replace('_',' ',$type))};}
 public static function options(string $table): array {if(!in_array($table,['properties','parcels','ratepayers','accounts','spatial_layers','roles'],true))throw new \DomainException('Unknown lookup.');$field=match($table){'properties'=>'property_number','parcels'=>'parcel_number','ratepayers'=>'full_name','accounts'=>'account_number',default=>'name'};return array_column(Database::all("SELECT id,$field FROM $table ORDER BY $field"),$field,'id');}
 private function lookups(): array {if(Auth::owner())throw new \DomainException('Staff lookup only.');return self::options(require_value($_GET,'type'));}
}
