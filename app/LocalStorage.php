<?php
namespace Srms;
final class LocalStorage implements Storage {
 public function __construct(){if(!is_dir(config('storage_path'))&&!mkdir(config('storage_path'),0700,true))throw new \RuntimeException('Cannot create storage directory.');}
 public function path(string $key): string {if(!preg_match('#^[a-f0-9-]{36}\.(pdf|jpg|png)$#',$key))throw new \DomainException('Invalid storage key.');return rtrim(config('storage_path'),'/\\').'/'.$key;}
 public function put(string $key,string $bytes): void {$p=$this->path($key);$h=fopen($p,'xb');if(!$h)throw new \RuntimeException('Cannot store document.');try{if(fwrite($h,$bytes)!==strlen($bytes))throw new \RuntimeException('Incomplete file write.');}finally{fclose($h);}}
}
