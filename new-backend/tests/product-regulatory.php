<?php
require dirname(__DIR__).'/vendor/autoload.php';
use App\Modules\Product\Entity\Product\Product;
$p=Product::create('fixture','Fixture','1',100,'/fixture.png','60 капсул','Description');
if($p->legalStatus!=='unknown')throw new RuntimeException('Never infer legal status from a product name.');
$p->setRegulatoryInformation('bad','Дополнительный источник витамина C.','СГР fixture; reviewed 2026-10-11');
if($p->legalStatus!=='bad'||$p->officialPurpose!=='Дополнительный источник витамина C.')throw new RuntimeException('Independent regulatory fields');
try{$p->setRegulatoryInformation('medicine',null,null);throw new RuntimeException('Invalid status accepted');}catch(DomainException){}
echo "product-regulatory: all assertions passed\n";
