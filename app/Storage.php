<?php
namespace Srms;
interface Storage {public function put(string $key,string $bytes): void;public function path(string $key): string;}
