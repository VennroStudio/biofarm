<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';

use App\Components\Cacher\NullCacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Action\Admin\ProductTaxonomy\GetProductGroupAction;
use App\Http\Action\Admin\ProductTaxonomy\SaveProductGroupAction;
use App\Http\Action\Admin\ProductTaxonomy\SearchProductGroupProductsAction;
use App\Http\Action\v1\Product\GetProductsAction;
use App\Http\Action\v1\Product\GetProductByIdAction;
use App\Http\Unifier\Product\ProductCatalogDataProvider;
use App\Http\Unifier\Product\ProductUnifier;
use App\Modules\Content\Service\MaterialLibrary;
use App\Modules\Product\Query\Product\FindAll\ProductFindAllFetcher;
use App\Modules\Product\Query\Product\GetById\ProductGetByIdFetcher;
use App\Modules\Product\Service\ProductFacetSyncer;
use App\Modules\Product\Service\ProductGroupMembers;
use Doctrine\DBAL\DriverManager;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

$db=DriverManager::getConnection(['driver'=>'pdo_mysql','host'=>'biofarm-catalog-test-20261028','dbname'=>'catalog_fixture','user'=>'root','password'=>'catalog-fixture-only','charset'=>'utf8mb4']);
if($db->fetchOne('SELECT DATABASE()')!=='catalog_fixture') throw new RuntimeException('Isolated fixture only');
$c=require dirname(__DIR__).'/config/container.php';
$list=new GetProductsAction(new ProductFindAllFetcher($db,new NullCacher()),new ProductUnifier(),$c->get(Denormalizer::class),$c->get(Validator::class));
$detail=new GetProductByIdAction(new ProductGetByIdFetcher($db,new NullCacher()),new ProductUnifier());
$app=AppFactory::create();$app->get('/v1/products',$list);$app->get('/admin/api/products',$list)->setArgument('adminProducts','1');$app->get('/v1/products/{id}',$detail);$app->addRoutingMiddleware();
$groups = new ProductGroupMembers($db);
$saveGroup = new SaveProductGroupAction($db, new NullCacher(), $groups);
$app->get('/admin/api/product-groups/products', new SearchProductGroupProductsAction($groups));
$app->get('/admin/api/product-groups/{id:[0-9]+}', new GetProductGroupAction($groups, $db));
$app->post('/admin/api/product-groups', $saveGroup);
$app->patch('/admin/api/product-groups/{id:[0-9]+}', $saveGroup);
$groupRequest = static function (string $method, string $path = '', array $data = []) use ($app): array {
    $request = (new ServerRequestFactory())->createServerRequest($method, 'https://example.test/admin/api/product-groups' . $path);
    $response = $app->handle($method === 'GET' ? $request->withQueryParams($data) : $request->withParsedBody($data));
    return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR)['data'];
};
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

$db->beginTransaction();
try {
    $byWeight = array_column($variants, 'id', 'weight');
    $main = $byWeight['60 капсул'];
    $variant = $byWeight['120 капсул'];
    $group = (int) $db->fetchOne('SELECT group_id FROM product_group_items WHERE product_id = ?', [$main]);
    $slug = (string) $db->fetchOne('SELECT slug FROM products WHERE id = ?', [$variant]);
    $attribute = (int) $db->fetchOne("SELECT id FROM attributes WHERE filter_prefix = 'sostav'");
    $db->insert('attribute_values', [
        'attribute_id' => $attribute,
        'slug' => 'variant-only-component',
        'name' => 'Variant-only component',
        'created_at' => gmdate('Y-m-d H:i:s'),
    ]);
    $value = (int) $db->lastInsertId();
    $db->update('products', ['name' => 'Variant packaging only abc123'], ['id' => $variant]);
    $sync = new ProductFacetSyncer($db);
    $sync->sync($variant, [$value], $group, false);
    $total = $provider->countProducts();
    if ($provider->countProducts(search: 'abc123') !== 1) $errors[] = 'Unselected groups must list every published pack';
    if ($provider->countProducts(componentSlug: 'variant-only-component') !== 1) $errors[] = 'A pack in an unselected group must match its own attributes';

    $sync->sync($main, [], $group, true);
    if ($provider->countProducts() !== $total - count($variants) + 1) $errors[] = 'Exactly one published card must remain in a selected group';
    if ($provider->products(search: 'abc123') !== [] || $provider->countProducts(search: 'abc123') !== 0) $errors[] = 'Search must ignore a hidden pack';
    if ($provider->countProducts(componentSlug: 'variant-only-component') !== 0 || in_array('variant-only-component', array_column($provider->componentFilters(null), 'slug'), true)) $errors[] = 'Filters and facet counts must ignore a hidden pack';
    if ($provider->productBySlug($slug)?->id !== $variant || !in_array($variant, array_column($method->invoke($provider, $main), 'id'), true)) $errors[] = 'The hidden pack must remain reachable directly and inside the product page';
    foreach (['/v1/products' => 0, '/admin/api/products' => 1] as $path => $expected) {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test' . $path)->withQueryParams(['search' => 'abc123']);
        $data = json_decode((string) $app->handle($request)->getBody(), true)['data'];
        if ($data['count'] !== $expected) $errors[] = $path . ' visibility mismatch';
        if ($expected === 1 && $data['items'][0]['is_main_in_group'] !== false) $errors[] = 'Admin must report the unchecked status on a secondary pack';
    }

    $sync->sync($variant, [$value], $group, true);
    $sync->sync($main, [], $group, false);
    if ((int) $db->fetchOne('SELECT main_product_id FROM product_groups WHERE id = ?', [$group]) !== $variant) $errors[] = 'Saving another pack must not clear the chosen main product';
    if ($provider->countProducts(search: 'abc123') !== 1) $errors[] = 'Switching main products must switch search visibility';
    $db->update('products', ['is_active' => 0], ['id' => $variant]);
    if ($provider->countProducts() !== $total - 1) $errors[] = 'An unpublished main product must not hide published packs';
    $db->update('products', ['is_active' => 1], ['id' => $variant]);
    $sync->sync($variant, [$value], $group, false);
    if ($provider->countProducts() !== $total) $errors[] = 'Clearing the main flag must restore all published packs';
    $sync->sync($main, [], $group, true);
    $sync->sync($main, [], null, false);
    if ($db->fetchOne('SELECT main_product_id FROM product_groups WHERE id = ?', [$group]) !== null || $provider->countProducts() !== $total) $errors[] = 'Removing the main product from the group must clear its selection';

    $source = $groupRequest('GET', "/$group");
    $sourceIds = array_column($source['products'], 'id');
    $matches = $groupRequest('GET', '/products', ['search' => 'abc123'])['items'];
    $photo = $db->fetchOne('SELECT path FROM product_images WHERE product_id = ? AND is_main = 1 ORDER BY id LIMIT 1', [$variant]);
    if (!$photo || array_column($matches, 'image') !== [$photo] || array_column($matches, 'id') !== [$variant]) $errors[] = 'Product search must include the primary photo';
    if ($groupRequest('GET', '/products')['items'] !== []) $errors[] = 'Empty group product search must return no products';
    $groupRequest('PATCH', "/$group", ['name' => $source['name'], 'product_ids' => $sourceIds, 'main_product_id' => $variant, 'revision' => $source['revision']]);
    $sync->sync($variant, [$value], updateProductGroup: false);
    if ($groupRequest('GET', "/$group")['main_product_id'] !== $variant) $errors[] = 'Editing a product without group fields must preserve its membership and main selection';
    $destination = $groupRequest('POST', '', ['name' => 'Fixture group', 'product_ids' => [$variant], 'main_product_id' => $variant])['id'];
    $after = $groupRequest('GET', "/$group");
    if ($after['main_product_id'] !== null || in_array($variant, array_column($after['products'], 'id'), true)) $errors[] = 'Transferring a main product must remove it and clear the source selection';
    if ($groupRequest('GET', "/$destination")['main_product_id'] !== $variant) $errors[] = 'The transferred product must be the main product in its new group';
    try {
        $groupRequest('PATCH', "/$group", ['name' => $source['name'], 'product_ids' => $sourceIds, 'main_product_id' => $variant, 'revision' => $source['revision']]);
        $errors[] = 'A stale group edit must not overwrite the transfer';
    } catch (DomainExceptionModule $error) {
        if ($error->getStatus() !== 409) $errors[] = 'A stale group edit must return 409';
    }
} finally {
    $db->rollBack();
}

if($errors){echo implode("\n",$errors)."\n";exit(1);}
echo "Catalogue publication and admin groups: pack visibility, search, filters, switching, search thumbnails, group editing and transfers passed.\n";
