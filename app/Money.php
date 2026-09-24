<?php
namespace Srms;
final class Money {
 public static function parse(mixed $value,bool $signed=false): string { $v=trim((string)$value); if(!preg_match($signed?'/^-?\d{1,15}(\.\d{1,4})?$/':'/^\d{1,15}(\.\d{1,4})?$/',$v)) throw new \DomainException('Enter a valid amount with up to four decimal places.'); return bcadd($v,'0',4); }
 public static function add(string $a,string $b): string {return bcadd($a,$b,4);}
 public static function sub(string $a,string $b): string {return bcsub($a,$b,4);}
 public static function cmp(string $a,string $b): int {return bccomp($a,$b,4);}
 public static function min(string $a,string $b): string {return self::cmp($a,$b)<0?$a:$b;}
}
