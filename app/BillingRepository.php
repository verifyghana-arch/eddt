<?php
namespace Srms;

final class BillingRepository
{
    public static function page(array $filters): array
    {
        $year=(int)($filters['year']??date('Y'));if($year<1900||$year>2200)throw new \DomainException('Invalid billing year.');
        $status=(string)($filters['status']??'');if($status!==''&&!in_array($status,['pending','approved','issued'],true))throw new \DomainException('Invalid billing status.');
        $q=mb_substr(trim((string)($filters['q']??'')),0,100);$page=max(1,(int)($filters['page']??1));$size=50;
        $where=['g.assessment_year=?'];$params=[$year];if($status==='pending'||$status==='approved'){$where[]='g.status=?';$params[]=$status;}if($status==='issued')$where[]='b.id IS NOT NULL';
        if($q!==''){$where[]='(p.account_number LIKE ? OR p.locality LIKE ? OR b.bill_number LIKE ?)';array_push($params,"%$q%","%$q%","%$q%");}
        $base=' FROM ground_rent_assessments g JOIN properties p ON p.id=g.account_number LEFT JOIN ground_rent_bills b ON b.assessment_id=g.id WHERE '.implode(' AND ',$where);
        $total=(int)Database::scalar('SELECT COUNT(*)'.$base,$params);
        $rows=Database::all('SELECT g.id assessment_id,g.account_number,g.assessment_year,g.annual_amount,g.status assessment_status,g.approved_at,b.id bill_id,b.bill_number,b.issue_date,b.due_date,b.penalty_amount,b.adjustment_amount,b.paid_amount,(COALESCE(b.principal_amount,0)+COALESCE(b.penalty_amount,0)+COALESCE(b.adjustment_amount,0)-COALESCE(b.paid_amount,0)) outstanding,p.locality'.$base.' ORDER BY p.account_number LIMIT '.$size.' OFFSET '.(($page-1)*$size),$params);
        $summary=Database::one('SELECT COUNT(*) assessments,SUM(g.status="pending") pending,SUM(g.status="approved") approved,SUM(b.id IS NOT NULL) issued,COALESCE(SUM(CASE WHEN b.id IS NOT NULL THEN b.principal_amount+b.penalty_amount+b.adjustment_amount-b.paid_amount ELSE 0 END),0) outstanding FROM ground_rent_assessments g LEFT JOIN ground_rent_bills b ON b.assessment_id=g.id WHERE g.assessment_year=?',[$year]);
        return compact('year','status','q','page','size','total','rows','summary');
    }
}
