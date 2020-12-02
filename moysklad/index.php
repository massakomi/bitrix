<?php

use MoySklad\MoySklad;
use MoySklad\Entities\Products\Product;
use MoySklad\Lists\EntityList;

use MoySklad\Components\Expand;
use MoySklad\Components\Specs\LinkingSpecs;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;

use MoySklad\Entities\Folders\ProductFolder;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/init.php';

try {
    $sklad = MoySklad::getInstance($login, $password);

    ?>
    <a href="?stock=1">Импорт остатков</a>
    <a href="?folders=1">Импорт категорий</a>
    <a href="?products=1">Импорт товаров</a>
    <a href="?test=1">Test</a>

    <?php

    if ($_GET['stock']) {
        include_once 'stock.php';
        msStocksUpdateAll($sklad);
    }

    if ($_GET['folders']) {
        include_once 'folders.php';
        msImportFolders($sklad);
    }

    if ($_GET['products']) {
        include_once 'products.php';
        msImportProducts($sklad);
    }

    if ($_GET['test']) {
    	$product = Product::query($sklad)->byId('35b80642-836c-11e8-9ff4-3150001d6e08');
        echo '<pre>'; print_r($product); echo '</pre>'; exit;
    }


} catch (Exception $e) {
    echo $e->getErrorText();
}

