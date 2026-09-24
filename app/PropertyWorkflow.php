<?php
namespace Srms;
final class PropertyWorkflow {
 public const STAGES=['owner'=>'Assign owner','assessment'=>'Assess rent','approval'=>'Approve assessment','billing'=>'Issue bill','payment'=>'Collect payment','complete'=>'Up to date','exempt'=>'Exempt'];
 public static function rows(array $filters=[]):array {
  if(!Auth::user())throw new \DomainException('Sign in required.');
  [$scope,$params]=Reports::scope();$year=(int)date('Y');
  $sql="SELECT p.account_number,p.locality,p.is_exempt,g.id assessment_id,g.status assessment_status,g.annual_amount,b.id bill_id,b.bill_number,COALESCE(t.outstanding,0) outstanding,
  CASE WHEN NOT EXISTS(SELECT 1 FROM property_ratepayers o WHERE o.account_number=p.id AND o.relationship_type='owner' AND o.start_date<=CURRENT_DATE AND (o.end_date IS NULL OR o.end_date>CURRENT_DATE)) THEN 'owner'
  WHEN p.is_exempt=1 THEN CASE WHEN COALESCE(t.outstanding,0)>0 THEN 'payment' ELSE 'exempt' END
  WHEN g.id IS NULL THEN 'assessment' WHEN g.status='pending' THEN 'approval' WHEN b.id IS NULL THEN 'billing' WHEN COALESCE(t.outstanding,0)>0 THEN 'payment' ELSE 'complete' END stage
  FROM properties p LEFT JOIN ground_rent_assessments g ON g.account_number=p.id AND g.assessment_year=$year LEFT JOIN ground_rent_bills b ON b.account_number=p.id AND b.billing_year=$year LEFT JOIN (SELECT account_number,SUM(principal_amount+penalty_amount+adjustment_amount-paid_amount) outstanding FROM ground_rent_bills GROUP BY account_number)t ON t.account_number=p.id WHERE $scope AND p.status='active'";
  if(!empty($filters['account_number'])){$sql.=' AND p.id=?';$params[]=AccountNumber::validate($filters['account_number']);}
  if(trim($filters['q']??'')!==''){$q='%'.str_replace(['!','%','_'],['!!','!%','!_'],mb_substr(trim($filters['q']),0,100)).'%';$sql.=" AND (p.account_number LIKE ? ESCAPE '!' OR p.locality LIKE ? ESCAPE '!')";array_push($params,$q,$q);}
  $rows=Database::all($sql.' ORDER BY p.account_number',$params);return $rows;
 }
 public static function get(string $number):array {Auth::property($number);return self::rows(['account_number'=>$number])[0]??throw new \DomainException('Select an active property.');}
 public static function act(array $d):void {
  Auth::staff();$number=AccountNumber::validate(require_value($d,'account_number'));
  Database::transaction(function()use($d,$number){Database::lock('properties',$number);$r=self::get($number);switch(require_value($d,'step')){
   case 'assessment':if($r['stage']!=='assessment')throw new \DomainException('Assign an owner before assessing rent, and use the existing assessment when one is present.');Finance::assessment(['account_number'=>$number,'assessment_year'=>date('Y'),'annual_amount'=>require_value($d,'annual_amount'),'notes'=>$d['notes']??null]);break;
   case 'approval':Auth::admin();if($r['stage']!=='approval')throw new \DomainException('No assessment awaits approval.');Finance::approve($r['assessment_id']);break;
   case 'billing':if($r['stage']!=='billing')throw new \DomainException('An approved unbilled assessment is required.');Finance::issue($r['assessment_id'],require_value($d,'due_date'));break;
   default:throw new \DomainException('Unknown workflow step.');
  }});
 }
}
