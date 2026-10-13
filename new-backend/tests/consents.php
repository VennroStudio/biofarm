<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Components\Privacy\ConsentRecorder;
use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\DriverManager;
use Slim\Psr7\Factory\ServerRequestFactory;
$db = DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
$db->executeStatement('CREATE TABLE privacy_consents (id TEXT PRIMARY KEY, subject_type TEXT, subject_id TEXT, kind TEXT, version TEXT, accepted INTEGER, created_at TEXT, evidence TEXT)');
$recorder = new ConsentRecorder($db);
foreach ([[], ['personalDataConsent'=>false], ['personalDataConsent'=>'false'], ['personalDataConsent'=>true,'consentVersion'=>'old']] as $payload) {
    try { $recorder->assertForm($payload); throw new RuntimeException('Invalid consent accepted'); } catch (DomainExceptionModule $e) { if ($e->getStatus()!==422) throw $e; }
}
$request=(new ServerRequestFactory())->createServerRequest('POST','/v1/users/create');
$payload=['personalDataConsent'=>true,'consentVersion'=>ConsentRecorder::VERSION,'marketingEmailConsent'=>false];
$recorder->assertForm($payload);
$recorder->recordForm($request,$payload,'user','1');
$rows=$db->fetchAllAssociative('SELECT * FROM privacy_consents');
if (count($rows)!==2 || (int)$rows[1]['accepted']!==0 || $rows[0]['version']!==ConsentRecorder::VERSION) throw new RuntimeException('Separate evidence missing');
if (str_contains($rows[0]['evidence'],'password')) throw new RuntimeException('Credentials must never be recorded');
echo "consents: all assertions passed\n";
