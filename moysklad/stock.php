<?php

use MoySklad\Entities\Reports\StockReport;
use MoySklad\Components\Specs\QuerySpecs\Reports\StockReportQuerySpecs;

CModule::IncludeModule('catalog');

function msStockUpdate($productId, $stockUid, $value, &$stat)
{
    static $storeProductsIds;
    if (!isset($storeProductsIds)) {
        $rsStoreProduct = \Bitrix\Catalog\StoreProductTable::getList(array(
            //'filter' => array('STORE.ACTIVE'=>'Y')
        ));
        $storeProductsIds = [];
        while ($v = $rsStoreProduct->fetch()) {
            $storeProductsIds [$v['STORE_ID']][$v['PRODUCT_ID']] = $v['ID'];
        }
        //Log::get()->log('loaded products');
    }
    $storeId = getStoreId($stockUid);
    if (!$storeId) {
        $stat ['storeId-no-exist']++;
        return false;
    }
    $arFields = Array(
        "PRODUCT_ID" => $productId,
        "STORE_ID" => $storeId,
        "AMOUNT" => $value
    );
    if ($storeProductsIds[$storeId][$productId]) {
        $id = $storeProductsIds[$storeId][$productId];
        if (CCatalogStoreProduct::Update($id, $arFields)) {
            $stat ['updated']++;
        } else {
            $stat ['update-error']++;
        }
    } else {
    	$id = CCatalogStoreProduct::Add($arFields);
        if ($id) {
        	$storeProductsIds[$storeId][$productId] = $id;
            $stat ['added']++;
        } else {
            $stat ['add-error']++;
        }
    }
}


// Остатки по складам
function msStocksUpdateAll($sklad)
{

    Log::get()->log('msStocksUpdateAll');
    $offset = 0;
    $limit = 100;
    $ids = [];
    while (true) {
        $list = StockReport::byStore($sklad, StockReportQuerySpecs::create([
            "offset" => $offset,
            "maxResults" => $limit
        ]));
        if (!count($list->rows) || $offset > 50000) {
        	break;
        }
        $offset += $limit;
        Log::get()->log('from '.$offset.' - '.count($list->rows));

        $stat = [];
        foreach ($list->rows as $key => $item) {
            preg_match('~product/([-\da-z]+)~i', $item->meta->href, $a);
            $productUid = $a[1];
            $productId = getElementIdByUid($productUid);
            if (!$productId) {
                $stat ['productId-no-exist']++;
                continue;
            }
            $ids []= $productId;

            $total = $totalReserve = 0;
            foreach ($item->stockByStore as $k => $v) {
                preg_match('~store/([-\da-z]+)~i', $v->meta->href, $a);
                $stockUid = $a[1];
                msStockUpdate($productId, $stockUid, $v->stock, $stat);
                $total += $v->stock;
                $totalReserve += $v->reserve;
            }

            // Обновляем общий остаток
            $res = CCatalogProduct::Add([
                'ID' => $productId,
                'QUANTITY' => $total,
                'QUANTITY_RESERVED' => $totalReserve,
                'TYPE' => \Bitrix\Catalog\ProductTable::TYPE_PRODUCT,
                'AVAILABLE' => 'Y',
                'CAN_BUY_ZERO' => 'D',
            ]);
            //Log::get()->log('total '.$productUid.' ['.$productId.'] '.$total.' / '.$totalReserve.' -'.$res);
        }
        $res = '';
        foreach ($stat as $k => $v) {
        	$res .= " $k:$v";
        }
        Log::get()->log($res, 0);
        //exit;
    }

    Log::get()->log('stock done ('.count($ids).'), deactivate...');

    // все активные ids продуктов
    $items = CIBlockElement::GetList(
        $arOrder=[],
        $arFilter=array(
            'IBLOCK_ID' => 5,
            'ACTIVE' => 'Y'
        ),
        false,
        false,
        $arSelect=['ID']
    );
    while($arItem = $items->GetNext()) {
        if (!in_array($arItem['ID'], $ids)) {
        	$bs = new CIBlockElement;
            $bs->Update($arItem['ID'], ['ACTIVE' => 'Y']); //need to check
            Log::get()->log('deactivate '.$arItem['ID']);
        }
    }

}



/*

*/
