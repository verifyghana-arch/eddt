<?php
return [
 'db_host'=>getenv('SRMS_DB_HOST') ?: '127.0.0.1',
 'db_port'=>getenv('SRMS_DB_PORT') ?: '3306',
 'db_name'=>getenv('SRMS_DB_NAME') ?: 'eddt_srms_rebuilt_demo',
 'db_user'=>getenv('SRMS_DB_USER') ?: 'root',
 'db_password'=>getenv('SRMS_DB_PASSWORD') ?: '',
 'base_url'=>getenv('SRMS_BASE_URL') ?: 'http://localhost/srms/public',
 'storage_path'=>getenv('SRMS_STORAGE_PATH') ?: dirname(__DIR__).'/var/rebuilt-storage',
 'timezone'=>'Africa/Accra',
 'session_name'=>'eddt_srms_rebuilt',
];
