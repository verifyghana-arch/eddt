<?php
function config(string $key): mixed { return $GLOBALS['config'][$key] ?? null; }
function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function uuid(): string { $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128); return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
function url(string $route='dashboard', array $params=[]): string { if(in_array($params['type']??'',['properties','accounts','parcels'],true)){$params['type']='parcels';if(isset($params['id'])){$params['account_number']=$params['id'];unset($params['id']);}}if($route==='property-preview'&&isset($params['id'])){$params['account_number']=$params['id'];unset($params['id']);}return rtrim(config('base_url'),'/').'/index.php?'.http_build_query(['r'=>$route]+$params); }
function redirect(string $route='dashboard', array $params=[]): never { header('Location: '.url($route,$params)); exit; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function token_field(): string { return '<input type="hidden" name="request_key" value="'.uuid().'">'; }
function flash(string $text, string $type='success'): void { $_SESSION['flash']=['text'=>$text,'type'=>$type]; }
function money(mixed $amount): string { $v=(string)($amount??'0');$negative=bccomp($v,'0',4)<0;$rounded=bcadd($v,$negative?'-0.005':'0.005',2);[$whole,$fraction]=explode('.',$rounded);return preg_replace('/\B(?=(\d{3})+(?!\d))/',',',$whole).'.'.$fraction; }
function today(): string { return date('Y-m-d'); }
function require_value(array $data,string $key): string { $v=trim((string)($data[$key]??'')); if($v==='') throw new DomainException(ucwords(str_replace('_',' ',$key)).' is required.'); return $v; }
function valid_date(string $date): string { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date); if(!$d || $d->format('Y-m-d')!==$date) throw new DomainException('Enter a valid date.'); return $date; }
function json_response(mixed $data,int $status=200): never { http_response_code($status); header('Content-Type: application/json'); echo json_encode($data,JSON_THROW_ON_ERROR); exit; }
