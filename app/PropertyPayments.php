<?php
namespace Srms;

/** One definition of payment status for dashboard, map and property details. */
final class PropertyPayments {
 public const LABELS=['unpaid'=>'Non-payment','partial'=>'Partial payment','paid'=>'Full payment','exempt'=>'Exempt','unbilled'=>'Not yet billed'];
 public const COLORS=['unpaid'=>'#dc2626','partial'=>'#facc15','paid'=>'#16a34a','exempt'=>'#ffffff','unbilled'=>'#94a3b8'];
 public static function classify(bool $exempt,int $bills,string $billed,string $paid): string {
  if($exempt)return 'exempt';
  if($bills===0)return 'unbilled';
  if(Money::cmp(Money::sub($billed,$paid),'0')<=0)return 'paid';
  return Money::cmp($paid,'0')>0?'partial':'unpaid';
 }
 public static function rows(): array {
  [$scope,$params]=Reports::scope();
  $rows=Database::all("SELECT p.id,p.property_number,p.is_exempt,COALESCE(t.bill_count,0) bill_count,COALESCE(t.billed,0) billed,COALESCE(t.paid,0) paid FROM properties p LEFT JOIN (SELECT a.account_number,COUNT(b.id) bill_count,SUM(b.principal_amount+b.penalty_amount+b.adjustment_amount) billed,SUM(b.paid_amount) paid FROM accounts a JOIN ground_rent_bills b ON b.account_number=a.id GROUP BY a.account_number) t ON t.account_number=p.id WHERE $scope",$params);
  $result=[];foreach($rows as $r){$r['outstanding']=Money::sub((string)$r['billed'],(string)$r['paid']);$r['payment_status']=self::classify((bool)$r['is_exempt'],(int)$r['bill_count'],(string)$r['billed'],(string)$r['paid']);$r['payment_label']=self::LABELS[$r['payment_status']];$result[$r['id']]=$r;}return $result;
 }
 public static function exemption(string $id,bool $exempt,string $reason): void {
  Auth::admin();$reason=trim($reason);if($reason===''||mb_strlen($reason)>500)throw new \DomainException('Provide an exemption decision reason of 1–500 characters.');
  Database::transaction(function()use($id,$exempt,$reason){$old=Database::lock('properties',$id);$values=['is_exempt'=>$exempt?1:0,'exemption_reason'=>$reason];Database::update('properties',$id,$values);Audit::log('property_exemption_changed','properties',$id,['is_exempt'=>$old['is_exempt'],'exemption_reason'=>$old['exemption_reason']],$values);});
 }
}