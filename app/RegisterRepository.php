<?php
namespace Srms;

final class RegisterRepository
{
    public static function page(string $type,array $filters): array
    {
        if(!isset(Registry::FIELDS[$type]))throw new \DomainException('Unknown register.');
        if(Auth::owner()&&$type!=='parcels')throw new \DomainException('Staff register only.');
        $q=mb_substr(trim((string)($filters['q']??'')),0,100);$page=max(1,(int)($filters['page']??1));
        [$scope,$params]=Reports::scope('p');$sql='SELECT t.* FROM '.$type.' t';
        if($type==='parcels')$sql.=' JOIN properties p ON p.id=t.id WHERE '.$scope;
        else{$sql.=' WHERE 1=1';$params=[];}
        $search=match($type){
            'ratepayers'=>['ratepayer_number','full_name','organization_name','email'],
            'parcels'=>['account_number','division_code','block_code','parcel_code','locality','land_id','address_line'],
            'spatial_layers'=>['name','description'],default=>['id']
        };
        if($q!==''){$sql.=' AND ('.implode(' OR ',array_map(fn($f)=>'t.'.$f.' LIKE ?',$search)).')';foreach($search as $field)$params[]='%'.$q.'%';}
        $total=(int)Database::scalar('SELECT COUNT(*) FROM ('.$sql.') counted',$params);
        $rows=Database::all(Database::page($sql.' ORDER BY t.created_at DESC',$page),$params);
        return compact('type','q','page','total','rows');
    }
}
