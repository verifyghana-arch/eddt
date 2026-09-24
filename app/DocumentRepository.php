<?php
namespace Srms;

final class DocumentRepository
{
    public static function page(array $filters): array
    {
        [$scope,$params]=Reports::scope();$where=[$scope];$q=mb_substr(trim((string)($filters['q']??'')),0,100);$type=mb_substr(trim((string)($filters['document_type']??'')),0,60);$page=max(1,(int)($filters['page']??1));$size=50;
        if(Auth::owner())$where[]='d.owner_visible=1';
        if($q!==''){$where[]='(d.original_filename LIKE ? OR d.account_number LIKE ? OR p.locality LIKE ?)';array_push($params,"%$q%","%$q%","%$q%");}
        if($type!==''){$where[]='d.document_type=?';$params[]=$type;}
        $base=' FROM documents d JOIN properties p ON p.id=d.account_number WHERE '.implode(' AND ',$where);
        $total=(int)Database::scalar('SELECT COUNT(*)'.$base,$params);
        $rows=Database::all('SELECT d.*,p.locality'.$base.' ORDER BY d.created_at DESC LIMIT '.$size.' OFFSET '.(($page-1)*$size),$params);
        $types=array_column(Database::all('SELECT DISTINCT document_type FROM documents ORDER BY document_type'),'document_type','document_type');
        return compact('rows','types','total','page','size','q','type');
    }
}
