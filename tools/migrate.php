<?php
require dirname(__DIR__).'/app/bootstrap.php';
use Srms\Database as DB;
if(PHP_SAPI!=='cli') exit(1);
$pdo=new PDO('mysql:host='.config('db_host').';port='.config('db_port'),config('db_user'),config('db_password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name=config('db_name'); if(!preg_match('/^[a-zA-Z0-9_]+$/',$name)) throw new RuntimeException('Invalid database name.');
$check=$pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$check->execute([$name]);if(!$check->fetchColumn())throw new RuntimeException('For a fresh database run tools/install-mysql.php NEW_DATABASE, then configure that database.');
DB::query('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, applied_at DATETIME NOT NULL)');
if(!DB::scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name='users'",[$name])) {
 $sql=file_get_contents(SRMS_ROOT.'/database/schema.sql');
 // Bootstrap only a fresh database. Never execute DROP TABLE statements.
 $sql=preg_replace('/CREATE DATABASE.*?;|USE eddt_srms;|SET FOREIGN_KEY_CHECKS\s*=\s*\d;|DROP TABLE IF EXISTS[^;]+;/s','',$sql);
 foreach(explode(';',$sql) as $statement) if(trim($statement)) DB::query($statement);
}
function column(string $table,string $name,string $definition): void {
 if(!DB::scalar('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?',[config('db_name'),$table,$name])) DB::query("ALTER TABLE $table ADD COLUMN $name $definition");
}
if(!DB::scalar("SELECT COUNT(*) FROM schema_migrations WHERE version='002_application'")) {
 column('accounts','credit_amount','DECIMAL(19,4) NOT NULL DEFAULT 0');
 column('users','must_change_password','TINYINT NOT NULL DEFAULT 1');
 column('users','session_version','INT NOT NULL DEFAULT 1');
 column('documents','owner_visible','TINYINT NOT NULL DEFAULT 0');
 column('documents','version_group_id','CHAR(36) NULL');
 column('documents','previous_version_id','CHAR(36) NULL');
 column('payments','request_key','CHAR(36) NULL UNIQUE');
 column('payments','reversal_reason','VARCHAR(500) NULL');
 column('payments','reversed_by','CHAR(36) NULL');
 column('payments','reversed_at','DATETIME NULL');
 column('ground_rent_bills','opening_balance','TINYINT NOT NULL DEFAULT 0');
 column('correspondence','template_version',"VARCHAR(30) NOT NULL DEFAULT '1'");
 column('import_batches','file_hash','CHAR(64) NULL');
 DB::query('CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL)');
 DB::query('CREATE TABLE IF NOT EXISTS login_attempts (id CHAR(36) PRIMARY KEY, identity_hash CHAR(64) NOT NULL, ip_address VARCHAR(45) NOT NULL, attempted_at DATETIME NOT NULL, INDEX ix_login_identity (identity_hash,attempted_at), INDEX ix_login_ip (ip_address,attempted_at))');
 DB::query('CREATE TABLE IF NOT EXISTS financial_adjustments (id CHAR(36) PRIMARY KEY, bill_id CHAR(36) NOT NULL, adjustment_type VARCHAR(30) NOT NULL, amount DECIMAL(19,4) NOT NULL, reason VARCHAR(500) NOT NULL, created_by CHAR(36) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (bill_id) REFERENCES ground_rent_bills(id), FOREIGN KEY (created_by) REFERENCES users(id))');
 DB::query("INSERT INTO schema_migrations VALUES ('002_application',CURRENT_TIMESTAMP)");
}
$defaults=['organization_name'=>'East Dadekotopon Development Trust (EDDT)','address'=>'No. 9 Leshie Road, East La, Accra, Ghana','email'=>'admin@eddt.org','phone'=>'','digital_address'=>'GT-0433-5976','currency'=>'GHS','number_prefix'=>'EDDT','upload_mb'=>'15','map_tiles'=>'https://tile.openstreetmap.org/{z}/{x}/{y}.png','map_attribution'=>'© OpenStreetMap contributors','invitation_body'=>file_get_contents(SRMS_ROOT.'/samples/EDDT Invitation Letter.txt'),'retention_policy'=>'Retain financial records, correspondence and document history. Archive operational records; no automatic deletion.'];
foreach($defaults as $k=>$v) if(!DB::scalar('SELECT COUNT(*) FROM settings WHERE setting_key=?',[$k])) DB::query('INSERT INTO settings VALUES (?,?)',[$k,$v]);
if(!DB::scalar("SELECT COUNT(*) FROM schema_migrations WHERE version='003_identity_templates'")) {
 DB::query('CREATE TABLE IF NOT EXISTS phone_identities (phone_number VARCHAR(40) PRIMARY KEY, holder_type VARCHAR(20) NOT NULL, holder_id CHAR(36) NOT NULL)');
 foreach(DB::all('SELECT phone_number,ratepayer_id FROM ratepayer_phone_numbers') as $r)if(!DB::scalar('SELECT COUNT(*) FROM phone_identities WHERE phone_number=?',[$r['phone_number']]))DB::query('INSERT INTO phone_identities VALUES (?,?,?)',[$r['phone_number'],'ratepayer',$r['ratepayer_id']]);
 foreach(DB::all('SELECT phone_number,user_id FROM user_phone_numbers') as $r)if(!DB::scalar('SELECT COUNT(*) FROM phone_identities WHERE phone_number=?',[$r['phone_number']]))DB::query('INSERT INTO phone_identities VALUES (?,?,?)',[$r['phone_number'],'user',$r['user_id']]);
 $original=file_get_contents(SRMS_ROOT.'/samples/EDDT Invitation Letter.txt');if(DB::scalar("SELECT setting_value FROM settings WHERE setting_key='invitation_body'")===$original)DB::query("UPDATE settings SET setting_value=? WHERE setting_key='invitation_body'",[Srms\LetterTemplates::INVITATION]);
 foreach(['demand_warning'=>Srms\LetterTemplates::WARNING,'signatory'=>'ADMINISTRATOR EDDT'] as $k=>$v)if(!DB::scalar('SELECT COUNT(*) FROM settings WHERE setting_key=?',[$k]))DB::query('INSERT INTO settings VALUES (?,?)',[$k,$v]);
 DB::query("INSERT INTO schema_migrations VALUES ('003_identity_templates',CURRENT_TIMESTAMP)");
}

foreach(['import_max_rows'=>'5000','import_chunk_rows'=>'100'] as $k=>$v)if(!DB::scalar('SELECT COUNT(*) FROM settings WHERE setting_key=?',[$k]))DB::query('INSERT INTO settings VALUES (?,?)',[$k,$v]);
if(!DB::scalar("SELECT COUNT(*) FROM schema_migrations WHERE version='004_property_exemptions'")) {
 column('properties','is_exempt','TINYINT NOT NULL DEFAULT 0');
 column('properties','exemption_reason','VARCHAR(500) NULL');
 DB::query("INSERT INTO schema_migrations VALUES ('004_property_exemptions',CURRENT_TIMESTAMP)");
}
echo json_encode(Srms\AccountKeyMigration::run(),JSON_PRETTY_PRINT)."\n";
Srms\CorrespondenceBatchSchema::migrate();
Srms\AppSchema::migrate();
Srms\CorrespondenceStorageSchema::migrate();
echo "Migrations applied without resetting existing tables.\n";
