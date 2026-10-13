<?php
require dirname(__DIR__).'/vendor/autoload.php';
use App\Modules\Program\Service\ShoppingBonusExpiry;
use App\Modules\Program\Service\ProgramMath;
use Doctrine\DBAL\DriverManager;
function ok($v,$m) { if (!$v) throw new RuntimeException($m); }
$db=DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
$db->executeStatement('CREATE TABLE program_ledger (id TEXT PRIMARY KEY,user_id INTEGER,wallet TEXT,amount_minor INTEGER,kind TEXT,order_id TEXT,state TEXT,details TEXT,created_at TEXT,available_at TEXT)');
$expiry=new ShoppingBonusExpiry($db);
$add=function($id,$amount,$date,$expires=null,$state='available',$sequence=1)use($db){$db->insert('program_ledger',['id'=>$id,'user_id'=>1,'wallet'=>'shopping','amount_minor'=>$amount,'kind'=>$amount>0?'buyer':'order_spending','state'=>$state,'details'=>json_encode(['expiresAt'=>$expires,'sequence'=>$sequence]),'created_at'=>$date]);};
$add('a',10000,'2026-01-01 00:00:00','2027-01-01 00:00:00');
$add('b',20000,'2026-02-01 00:00:00','2027-02-01 00:00:00');
$add('spend',-4000,'2026-03-01 00:00:00',null,'reserved');
$summary=$expiry->refresh(1,'2026-12-31 23:59:59');ok($summary['nextExpiryMinor']===6000,'FEFO consumes earliest expiry');
$expiry->refresh(1,'2027-01-01 00:00:00');$expiry->refresh(1,'2027-01-01 00:00:00');
ok((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE state IN ('available','reserved')")===20000,'exact expiry boundary and idempotency');
$db->update('program_ledger',['state'=>'void'],['id'=>'spend']);
$expiry->refresh(1,'2027-01-02 00:00:00');ok((int)$db->fetchOne("SELECT amount_minor FROM program_ledger WHERE id='expire:a'")===-10000,'cancelled reservation does not resurrect expired bonuses');
$add('legacy',500,'2025-01-01 00:00:00');
$expiry->refresh(1,'2028-01-01 00:00:00');ok((int)$db->fetchOne("SELECT SUM(amount_minor) FROM program_ledger WHERE state='available'")===500,'legacy credits are not retroactively expired');
ok(ShoppingBonusExpiry::deadline('2024-02-29 12:00:00',12)==='2025-02-28 12:00:00','calendar year clamp');
ok(ProgramMath::rules([])['buyerBps']===300,'3 percent default');
ok(ProgramMath::rules([])['bonusLifetimeMonths']===12,'12 month default');
echo "bonus-expiry: all assertions passed\n";
