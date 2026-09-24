<?php
namespace Srms;

final class PaymentRepository
{
    public static function page(array $filters): array
    {
        [$scope,$params]=Reports::scope();$where=[$scope];$q=mb_substr(trim((string)($filters['q']??'')),0,100);$method=(string)($filters['method']??'');$status=(string)($filters['status']??'');$page=max(1,(int)($filters['page']??1));$size=50;
        if($method!==''&&!in_array($method,['cash','bank','cheque','mobile_money'],true))throw new \DomainException('Invalid payment method.');
        if($status!==''&&!in_array($status,['posted','reversed'],true))throw new \DomainException('Invalid payment status.');
        if($q!==''){$where[]='(pay.receipt_number LIKE ? OR pay.account_number LIKE ? OR pay.payer_name LIKE ? OR pay.external_reference LIKE ?)';array_push($params,"%$q%","%$q%","%$q%","%$q%");}
        if($method!==''){$where[]='pay.payment_method=?';$params[]=$method;}if($status!==''){$where[]='pay.status=?';$params[]=$status;}
        foreach(['from'=>'>=','to'=>'<='] as $key=>$operator)if(!empty($filters[$key])){$where[]='pay.payment_date'.$operator.'?';$params[]=valid_date($filters[$key]);}
        $base=' FROM payments pay JOIN properties p ON p.id=pay.account_number WHERE '.implode(' AND ',$where);
        $total=(int)Database::scalar('SELECT COUNT(*)'.$base,$params);
        $visible=Auth::owner()?' AND c.owner_visible=1':'';
        $receiptLink=",(SELECT c.id FROM correspondence c WHERE c.payment_id=pay.id".$visible." ORDER BY c.created_at DESC,c.id LIMIT 1) correspondence_id";
        $rows=Database::all('SELECT pay.id,pay.receipt_number,pay.account_number,p.locality,pay.payment_date,pay.amount,pay.payment_method,pay.payer_name,pay.external_reference,pay.status,pay.reversal_reason'.$receiptLink.$base.' ORDER BY pay.payment_date DESC,pay.created_at DESC LIMIT '.$size.' OFFSET '.(($page-1)*$size),$params);
        $summary=Database::one('SELECT COALESCE(SUM(CASE WHEN pay.status="posted" THEN pay.amount ELSE 0 END),0) posted_total,SUM(pay.status="posted") posted_count,SUM(pay.status="reversed") reversed_count'.$base,$params);
        return compact('rows','total','page','size','q','method','status','summary');
    }
}
