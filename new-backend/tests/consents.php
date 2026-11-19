<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Components\Privacy\ConsentRecorder;
use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\DriverManager;
use Slim\Psr7\Factory\ServerRequestFactory;
$db = DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
$db->executeStatement('CREATE TABLE privacy_consents (id TEXT PRIMARY KEY, subject_type TEXT, subject_id TEXT, kind TEXT, version TEXT, accepted INTEGER, created_at TEXT, evidence TEXT)');
$db->executeStatement('CREATE TABLE pages (system_key TEXT, page_type TEXT, content TEXT, deleted_at TEXT)');
$recorder = new ConsentRecorder($db);
foreach ([[], ['personalDataConsent'=>false], ['personalDataConsent'=>'false'], ['personalDataConsent'=>true,'consentVersion'=>'old']] as $payload) {
    try { $recorder->assertForm($payload); throw new RuntimeException('Invalid consent accepted'); } catch (DomainExceptionModule $e) { if ($e->getStatus()!==422) throw $e; }
}
$request=(new ServerRequestFactory())->createServerRequest('POST','/v1/users/create');
$payload=['personalDataConsent'=>true,'consentVersion'=>ConsentRecorder::VERSION];
$recorder->assertForm($payload);
$recorder->recordForm($request,$payload,'user','1');
$rows=$db->fetchAllAssociative('SELECT * FROM privacy_consents');
if (count($rows)!==1 || $rows[0]['kind']!=='personal_data' || $rows[0]['version']!==ConsentRecorder::VERSION) {
    throw new RuntimeException('Unexpected consent evidence');
}
if (str_contains($rows[0]['evidence'],'password')) throw new RuntimeException('Credentials must never be recorded');
if (!$recorder->personalDataIsCurrent(1) || $recorder->requirePersonalData([],1)) {
    throw new RuntimeException('Existing consent should be reused');
}
$edited = '<p>Новая редакция из админки.</p>';
$db->insert('pages', ['system_key' => 'personal-data-consent', 'page_type' => 'system', 'content' => $edited]);
if ($recorder->personalDataIsCurrent(1)) throw new RuntimeException('Consent for the previous document must expire after a CMS edit');
$recorder->recordForm($request,$payload,'user','1');
$latest = $db->fetchOne("SELECT evidence FROM privacy_consents WHERE kind='personal_data' ORDER BY created_at DESC LIMIT 1");
if (json_decode($latest, true)['documentSha256'] !== hash('sha256', $edited)) {
    throw new RuntimeException('The evidence must identify the exact document published by the CMS');
}
$recorder->record($request,'user','1','personal_data',false);
if ($recorder->personalDataIsCurrent(1)) throw new RuntimeException('Withdrawal did not invalidate consent');
$recorder->recordMarketing($request,1,'buyer@example.test',true);
if (!$recorder->marketingEmailSubscribed(1,'buyer@example.test') || $recorder->marketingEmailSubscribed(1,'other@example.test')) {
    throw new RuntimeException('Marketing consent should apply to the chosen address');
}
$recorder->recordMarketing($request,1,'buyer@example.test',false);
if ($recorder->marketingEmailSubscribed(1,'buyer@example.test')) throw new RuntimeException('Unsubscribe did not take effect');
echo "consents: all assertions passed\n";
