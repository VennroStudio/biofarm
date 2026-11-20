<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Modules\Product\ReadModel\Product\ProductDetails;

$row = array_fill_keys(array_keys(ProductDetails::fields()), null);
$row = array_replace($row, [
    'id' => 1, 'slug' => 'fixture', 'name' => 'Fixture', 'category_id' => '1',
    'price' => 100, 'image' => '/fixture.png', 'weight' => '100 г',
    'description' => 'Fixture', 'created_at' => '2026-10-13 00:00:00',
]);
$row['certificate_ids'] = json_encode([
    ['id' => 8, 'sort_order' => 20],
    ['id' => 4, 'sort_order' => 10],
    ['id' => 6, 'sort_order' => 0],
    ['id' => 3, 'sort_order' => 10],
], JSON_THROW_ON_ERROR);
$product = ProductDetails::fromRow($row);
if ($product->certificateIds !== [6, 3, 4, 8]) {
    throw new RuntimeException('Certificate placements must retain sort order and ID tie-breaker across databases');
}
if ($product->toArray()['certificate_ids'] !== [6, 3, 4, 8]) {
    throw new RuntimeException('Public certificate IDs must remain a list of integers');
}
$row['certificate_ids'] = '[8,3]';
if (ProductDetails::fromRow($row)->certificateIds !== [8, 3]) {
    throw new RuntimeException('Legacy integer lists must remain supported');
}
$row['certificate_ids'] = null;
if (ProductDetails::fromRow($row)->certificateIds !== null) {
    throw new RuntimeException('No placements must remain null');
}
echo "Product certificates: ordering, tie-breaker, public IDs and legacy rows passed.\n";
