#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Components\Cacher\Cacher;
use App\Modules\Product\Service\CatalogPackagingUpdate;
use Doctrine\DBAL\Connection;

if (PHP_SAPI!=='cli') exit(1);
require dirname(__DIR__).'/vendor/autoload.php';
if (array_diff(array_slice($argv,1),['--apply','--help'])) {fwrite(STDERR,"Use --apply or --help\n");exit(2);}
if (in_array('--help',$argv,true)) {echo "Preview: php bin/apply-catalog-packaging.php\nApply with backup: php bin/apply-catalog-packaging.php --apply\n";exit(0);}
$app=dirname(__DIR__);Dotenv\Dotenv::createImmutable($app)->safeLoad();
$c=require $app.'/config/container.php';$db=$c->get(Connection::class);
$manifest=json_decode(file_get_contents($app.'/resources/updates/2026-10-28-catalog.json'),true,512,JSON_THROW_ON_ERROR);
$update=new CatalogPackagingUpdate($db,$app.'/public',$manifest);
$summary=$update->preview();echo json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
if (!in_array('--apply',$argv,true) || $summary['already_applied']) exit(0);
$backup=[];foreach (CatalogPackagingUpdate::TABLES as $table) $backup[$table]=$db->fetchAllAssociative('SELECT * FROM '.$table);
$folder=$app.'/var/backups';if (!is_dir($folder) && !mkdir($folder,0700,true)) throw new RuntimeException('Cannot create backup folder');
$file=$folder.'/catalog-before-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
$handle=fopen($file,'x');if ($handle===false) throw new RuntimeException('Cannot save backup');chmod($file,0600);
try {if (fwrite($handle,json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('Cannot write backup');}finally {fclose($handle);}
$result=$update->apply();foreach (['products','attributes','attribute_values'] as $tag) $c->get(Cacher::class)->deleteTag($tag);
echo 'Applied. Backup: '.$file."\n".json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
