<?php
namespace Srms;

final class CorrespondenceOutput {
 public static function record(string $id):array {
  if(!Auth::user())throw new \DomainException('Sign in to view correspondence.');
  $row=Database::one('SELECT * FROM correspondence WHERE id=?',[$id])??throw new \DomainException('Correspondence not found.');
  Auth::property($row['account_number']);
  if(Auth::owner()&&!$row['owner_visible'])throw new \DomainException('Access denied.');
  return $row;
 }
 public static function listing(?string $account=null,int $page=1,string $q=''):array {
  if(!Auth::user())throw new \DomainException('Sign in to view correspondence.');
  [$scope,$params]=Reports::scope('p');$where=[$scope];
  if(Auth::owner())$where[]='c.owner_visible=1';
  if($account!==null){Auth::property($account);$where[]='c.account_number=?';$params[]=$account;}
  if($q!==''){$where[]='(c.account_number LIKE ? OR c.reference_number LIKE ? OR c.subject LIKE ?)';array_push($params,"%$q%","%$q%","%$q%");}
  $base=' FROM correspondence c JOIN properties p ON p.id=c.account_number JOIN users u ON u.id=c.created_by LEFT JOIN ground_rent_bills b ON b.id=c.bill_id WHERE '.implode(' AND ',$where);
  $total=(int)Database::scalar('SELECT COUNT(*)'.$base,$params);$page=max(1,min($page,max(1,(int)ceil($total/10))));
  $rows=Database::all("SELECT c.*,u.full_name creator,b.billing_year,(SELECT COUNT(*) FROM correspondence_events e WHERE e.correspondence_id=c.id AND e.action='print') print_count,(SELECT MAX(created_at) FROM correspondence_events e WHERE e.correspondence_id=c.id AND e.action='print') last_printed".$base.' ORDER BY c.created_at DESC,c.id LIMIT 10 OFFSET '.(($page-1)*10),$params);
  return compact('rows','total','page');
 }
 public static function render(array $row,string $userName,string $time):string {
  $json=$row['content_variables_json']??'';
  if(!empty($row['snapshot_sha256'])&&!hash_equals($row['snapshot_sha256'],hash('sha256',$json)))throw new \RuntimeException('Correspondence snapshot integrity failure.');
  $vars=json_decode($json,true);$bytes=null;
  try{
   if(!is_array($vars)||!in_array($row['template_version'],['2','3'],true))throw new \RuntimeException('Historical template unavailable.');
   if(empty($vars['template_sha256']))throw new \RuntimeException('Unversioned legacy assets: use preserved original.');
   $template=SRMS_ROOT.'/app/views/letters/v'.$row['template_version'].'.php';if(!is_file($template)||!hash_equals($vars['template_sha256'],hash_file('sha256',$template)))throw new \RuntimeException('Historical template integrity failure.');
   foreach(['letter-logo.png','letter-watermark.png'] as $asset){$assetPath=SRMS_ROOT.'/app/assets/letters/v'.$row['template_version'].'/'.$asset;if(!is_file($assetPath)||!hash_equals($vars['asset_hashes'][$asset]??'',hash_file('sha256',$assetPath)))throw new \RuntimeException('Historical branding asset integrity failure.');}
   foreach(['account','property','settings','reference','printed','current','arrears','total','qr_target'] as $key)if(!array_key_exists($key,$vars))throw new \RuntimeException('Historical snapshot incomplete.');
   $account=$vars['account'];$property=$vars['property'];$settings=$vars['settings'];$owner=$vars['owner']??null;$bill=$vars['bill']??null;$payment=$vars['payment']??null;
   $ref=$vars['reference'];$current=$vars['current'];$arrears=$vars['arrears'];$from=$vars['period_from']??null;$to=$vars['period_to']??null;$type=$row['correspondence_type'];$year=$bill['billing_year']??substr($vars['printed'],0,4);$photoData=null;
   if(!empty($vars['photo_document_id'])){$photo=Database::one('SELECT * FROM documents WHERE id=?',[$vars['photo_document_id']])??throw new \RuntimeException('Original photo unavailable.');$path=(new LocalStorage())->path($photo['storage_key']);if(!is_file($path)||!hash_equals($vars['photo_sha256']??'',hash_file('sha256',$path)))throw new \RuntimeException('Original photo integrity failure.');$photoData='data:'.$photo['mime_type'].';base64,'.base64_encode(file_get_contents($path));}
   $options=new \chillerlan\QRCode\QROptions(['outputType'=>\chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,'outputBase64'=>true]);$qr=(new \chillerlan\QRCode\QRCode($options))->render($vars['qr_target']);
   ob_start();try{require SRMS_ROOT.'/app/views/letters/v'.$row['template_version'].'.php';$html=ob_get_contents();}finally{ob_end_clean();}
   $html=str_replace(['DATE PRINTED:','Archived correspondence · Template 2'],['ISSUE DATE:','Correspondence record · Template '.$row['template_version']],$html);
   $bytes=Correspondence::pdf($html);
  }catch(\Throwable $e){
   if(empty($row['generated_document_id']))throw $e;
   $doc=Database::one('SELECT * FROM documents WHERE id=?',[$row['generated_document_id']])??throw $e;$path=(new LocalStorage())->path($doc['storage_key']);
   if(!is_file($path)||!hash_equals($doc['sha256_hash'],hash_file('sha256',$path)))throw new \RuntimeException('Historical original integrity failure.');$bytes=file_get_contents($path);
  }
  $paymentId=$row['payment_id']??($vars['payment']['id']??null);$reversed=$paymentId&&Database::scalar('SELECT status FROM payments WHERE id=?',[$paymentId])==='reversed';
  return self::stamp($bytes,$userName,$time,$reversed);
 }
 private static function stamp(string $bytes,string $name,string $time,bool $reversed):string {
  $pdf=new \setasign\Fpdi\Fpdi();$count=$pdf->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($bytes));$pages=[];
  for($n=1;$n<=$count;$n++){$tpl=$pdf->importPage($n);$pages[]=[$tpl,$pdf->getTemplateSize($tpl)];}
  $label='Printed by '.$name.' on '.$time.' (Africa/Accra)';
  foreach($pages as [$tpl,$size]){
   // Reserve a footer inside the original paper size, including long Unicode names.
   $footerHeight=max(16,ceil(mb_strlen($label)*2.83/($size['width']-20))*4+($reversed?5:0)+6);
   $scale=($size['height']-$footerHeight)/$size['height'];
   $pdf->AddPage($size['orientation'],[$size['width'],$size['height']]);$pdf->useTemplate($tpl,($size['width']*(1-$scale))/2,0,$size['width']*$scale,$size['height']*$scale);
   $html='<html><head><meta charset="utf-8"><style>@page{margin:2mm 10mm}body{margin:0;font-family:DejaVu Sans,sans-serif;font-size:8pt;line-height:1.25}p{margin:0 0 1mm}.reversed{color:#af0000;font-weight:bold}</style></head><body><p>'.e($label).'</p>'.($reversed?'<p class="reversed">REVERSED PAYMENT - not valid proof of payment</p>':'').'</body></html>';
   $footer=Correspondence::pdf($html,[0,0,$size['width']*72/25.4,$footerHeight*72/25.4]);
   $footerPages=$pdf->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($footer));if($footerPages!==1)throw new \RuntimeException('Print attribution did not fit its reserved footer.');
   $footerTemplate=$pdf->importPage(1);$pdf->useTemplate($footerTemplate,0,$size['height']-$footerHeight,$size['width'],$footerHeight);
  }
  return $pdf->Output('S');
 }
 public static function output(string $id,string $action,string $key):array {
  if(!in_array($action,['preview','print','download'],true)||!preg_match('/^[a-f0-9-]{36}$/D',$key))throw new \DomainException('Invalid correspondence action.');
  return Database::transaction(function()use($id,$action,$key){$row=self::record($id);Database::lock('correspondence',$id);$user=Auth::user();$old=Database::one('SELECT * FROM correspondence_events WHERE correspondence_id=? AND request_key=?',[$id,$key]);
   if($old&&($old['user_id']!==$user['id']||$old['action']!==$action))throw new \DomainException('Action token already used.');
   $time=$old['created_at']??(new \DateTimeImmutable('now',new \DateTimeZone('Africa/Accra')))->format('Y-m-d H:i:s');$name=$old['user_name']??$user['full_name'];$bytes=self::render($row,$name,$time);
   if(!$old){Database::insert('correspondence_events',['correspondence_id'=>$id,'user_id'=>$user['id'],'user_name'=>$name,'action'=>$action,'request_key'=>$key,'created_at'=>$time]);Audit::log('correspondence_'.$action,'correspondence',$id,null,['account_number'=>$row['account_number'],'user_name'=>$name,'requested_at'=>$time]);if($action==='print'&&$row['bill_id'])Database::update('ground_rent_bills',$row['bill_id'],['notice_printed_at'=>$time]);}
   return ['bytes'=>$bytes,'reference'=>$row['reference_number']];
  });
 }
 public static function send(string $id,string $action,string $key):never {
  $out=self::output($id,$action,$key);header('Content-Type: application/pdf');header('Content-Disposition: '.($action==='download'?'attachment':'inline').'; filename="'.preg_replace('/[^A-Za-z0-9_-]/','_',$out['reference']).'.pdf"');header('Content-Length: '.strlen($out['bytes']));echo $out['bytes'];exit;
 }
}
