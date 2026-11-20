<?php
require dirname(__DIR__).'/vendor/autoload.php';
use App\Http\Web\Catalog\ThematicRedirect;
foreach([
 ['/catalog/dlya/mozg',[], '/catalog/dlya/nervnaya-sistema'],
 ['/catalog/produkciya/kapsuly/dlya/podzheludochnaya',['page'=>'2'], '/catalog/produkciya/kapsuly/dlya/pishchevaritelnaya-sistema?page=2'],
 ['/catalog',['dlya'=>'pechen','q'=>'экстракт'], '/catalog?dlya=pechen-i-zhelchevyvodyashchaya-sistema&q='.urlencode('экстракт')],
 ['/catalog/dlya/antiparazitarnaya-programma',[], '/catalog'],
 ['/catalog/dlya/nervnaya-sistema',[],null],
]as[$path,$query,$expected])if(ThematicRedirect::target($path,$query)!==$expected)throw new RuntimeException($path);
echo "thematic-redirect: all assertions passed\n";
