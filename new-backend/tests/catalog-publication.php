<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';

use App\Components\Cacher\NullCacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Action\v1\Product\GetProductsAction;
use App\Http\Action\v1\Product\GetProductByIdAction;
use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\Unifier\Product\ProductUnifier;
use App\Modules\Content\Service\MaterialLibrary;
use App\Modules\Product\Query\Product\FindAll\ProductFindAllFetcher;
use App\Modules\Product\Query\Product\GetById\ProductGetByIdFetcher;
use Doctrine\DBAL\DriverManager;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

$db=DriverManager::getConnection(['driver'=>'pdo_mysql','host'=>'biofarm-catalog-test-20261028','dbname'=>'catalog_fixture','user'=>'root','password'=>'catalog-fixture-only','charset'=>'utf8mb4']);
if($db->fetchOne('SELECT DATABASE()')!=='catalog_fixture') throw new RuntimeException('Isolated fixture only');
$c=require dirname(__DIR__).'/config/container.php';
$list=new GetProductsAction(new ProductFindAllFetcher($db,new NullCacher()),new ProductUnifier(),$c->get(Denormalizer::class),$c->get(Validator::class));
$detail=new GetProductByIdAction(new ProductGetByIdFetcher($db,new NullCacher()),new ProductUnifier());
$app=AppFactory::create();$app->get('/v1/products',$list);$app->get('/admin/api/products',$list)->setArgument('adminProducts','1');$app->get('/v1/products/{id}',$detail);$app->addRoutingMiddleware();
$errors=[];
foreach(['/v1/products'=>74,'/admin/api/products'=>95] as $path=>$expected){
 $r=(new ServerRequestFactory())->createServerRequest('GET','https://example.test'.$path)->withQueryParams(['includeInactive'=>true]);
 $data=json_decode((string)$app->handle($r)->getBody(),true)['data'];
 if($data['count']!==$expected)$errors[]=$path.' expected '.$expected.', got '.$data['count'];
}
$hidden=(int)$db->fetchOne('SELECT id FROM products WHERE is_active=0 AND deleted_at IS NULL LIMIT 1');
try{$app->handle((new ServerRequestFactory())->createServerRequest('GET','https://example.test/v1/products/'.$hidden));$errors[]='Draft exposed by ID';}catch(DomainExceptionModule $e){if($e->getStatus()!==404)$errors[]='Draft must return 404';}
$response=$app->handle((new ServerRequestFactory())->createServerRequest('GET','https://example.test/v1/products/17'));
if($response->getStatusCode()!==200)$errors[]='Active product unavailable';
$provider=new ProductCatalogDataProvider($db,new MaterialLibrary($db));
$method=new ReflectionMethod($provider,'productVariants');
$variants=$method->invoke($provider,17);
if(array_column($variants,'weight')!==['30 капсул','60 капсул','90 капсул','120 капсул'])$errors[]='Pack switcher must use ascending quantity';
if($method->invoke($provider,34)!==[])$errors[]='Hidden sizes must not leak into switcher';
if($errors){echo implode("\n",$errors)."\n";exit(1);}
echo "Catalogue publication: public list ignores includeInactive, admin retains drafts, draft detail 404, active detail 200, sorted pack switcher and hidden pack exclusion passed.\n";
