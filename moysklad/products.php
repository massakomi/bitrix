<?php

use MoySklad\Entities\Products\Product;
use MoySklad\Lists\EntityList;
use MoySklad\MoySklad;

use MoySklad\Components\Expand;
use MoySklad\Components\Specs\LinkingSpecs;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Employee;
use MoySklad\Utils\CommonDate;

use MoySklad\Entities\Reports\StockReport;
use MoySklad\Components\Specs\QuerySpecs\Reports\StockReportQuerySpecs;

use MoySklad\Entities\Folders\ProductFolder;

CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');

/*
$product = Product::query($sklad)->byId('35b80642-836c-11e8-9ff4-3150001d6e08');
echo '<pre>'; print_r($product); echo '</pre>'; exit;
*/




function msImportProducts($sklad)
{
    $offset = 0;
    while (true) {
        $listQuery = Product::query($sklad, QuerySpecs::create([
            "offset" => $offset,
            "maxResults" => 100,
        ]));
        $list = $listQuery->getList();
        //echo '<pre>'; print_r($list->getAttribute('meta')); echo '</pre>'; exit;
        //echo '<pre>'; print_r($list); echo '</pre>'; exit;
        if (!count($list)) {
            break;
        }
        echo '<h3>FROM '.$offset.'</h3> '.
            $list->getAttribute('meta')->href.' next:'. $list->getAttribute('meta')->nextHref;
        Log::get()->log('from '.$offset.' - '.count($list));
        msAddProducts($list);
        $offset += 100;
        //break;
    }
    // Удаление не найденных товаров
    msAddProducts(false);
}

function msAddProducts($list)
{
    static $sec2xml, $el2xml;
    if (!isset($sec2xml)) {
        $sec2xml = sections2xmlid();
        $el2xml = elements2xmlid();
    }
    // Удаление не найденных товаров
    if ($list === false) {
        if (!$el2xml) {
            return ;
        }
        $arFilter = Array(
            'IBLOCK_ID' => 5,
            'ACTIVE' => 'Y',
            'XML_ID' => array_keys($el2xml)
        );
        $res = CIBlockElement::GetList([], $arFilter, false, $arSelect);
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();
            $el = new CIBlockElement;
            $el->Update($arFields['ID'], array(
                'ACTIVE' => 'N'
            ));
            echo '<br />deactivate '.$arFields['NAME'];
            Log::get()->log('deactivate '.$arFields['NAME'].' ['.$arFields['ID'].']');
        }
        return ;
    }
    $index = 1;
    $stat = [];
    foreach ($list as $item) {
        preg_match('~\?id=(.*)~i', $item->relations->productFolder->fields->meta->uuidHref, $a);
        $folderId = $a[1];
        $sectionId = $sec2xml[$folderId];

        if (!$sectionId) {
            //echo ' не найден $sectionId по $folderId "'.$folderId.'"';
            echo '<br />'.$index.') ['.$item->id.'] <span style="color:red">'.$item->name.' SKIP</span>';
            $stat ['skipped'] ++;
            continue;
        }

        echo '<br />'.$index.') ['.$item->id.'] '.$item->name.' <b>'.$item->salePrices[0]->value.'</b>';
        $index ++;

        //echo '<pre>'; print_r($item); echo '</pre>'; exit;
        $code = Cutil::translit($item->name, LANGUAGE_ID, $params=array());

        $res = msAddProduct($item, $code, $el2xml, $sectionId, $stat, $PRODUCT_ID);

        $purchasingPrice = ($item->buyPrice->value ?: $item->buyPrices[0]->value) / 100;

        // Если существует - то пропускаем апдейт каталога и цены
        if ($res === -1) {
            // И цену добавить
            if ($item->salePrices[0]->value) {
                CPrice::SetBasePrice($PRODUCT_ID, $item->salePrices[0]->value / 100, 'RUB');
            }

            CCatalogProduct::Update($PRODUCT_ID, [
                'PURCHASING_PRICE' => $purchasingPrice,
                'PURCHASING_CURRENCY' => 'RUB'
            ]);
            //var_dump($PRODUCT_ID);
            //var_dump($purchasingPrice); exit;
            continue;
        }

        // Чтобы сделать обычный элемент инфоблока товаром, нужно вот такое сделать
        CCatalogProduct::Add([
            'ID' => $PRODUCT_ID,
            'TYPE' => \Bitrix\Catalog\ProductTable::TYPE_PRODUCT,
            'AVAILABLE' => 'Y',
            'WEIGHT' => $item->weight,
            'PURCHASING_PRICE' => $purchasingPrice,
            'PURCHASING_CURRENCY' => 'RUB',
            'CAN_BUY_ZERO' => 'D',
            'SUBSCRIBE' => 'D',
            "ACTIVE"    => 'Y'
        ], $boolCheck = true);

        // И цену добавить
        if ($item->salePrices[0]->value) {
            CPrice::SetBasePrice($PRODUCT_ID, $item->salePrices[0]->value / 100, 'RUB');
        }
    }
    $res = '';
    foreach ($stat as $k => $v) {
        $res .= " $k:$v";
    }
    Log::get()->log($res, 0);
}

function msAddProduct($item, $code, &$el2xml, $sectionId, &$stat, &$PRODUCT_ID)
{
    $product = Array(
        // Эти 2 поля единственные обязательные
        'IBLOCK_ID'         => 5,
        'NAME'              => $item->name,
        'XML_ID'            => $item->id,
        'ACTIVE'            => 'Y',

        'CODE'              => $code,
        'PROPERTY_VALUES'   => [
            'ARTNUMBER' => $item->article,
            'CODE'      => $item->code,
            'BARCODE'   => $item->barcodes
        ],
        'IBLOCK_SECTION_ID' => $sectionId,
        'TIMESTAMP_X'       => $item->updated,

        // 'PREVIEW_TEXT'      => '',
        // 'DETAIL_TEXT'       => '',

        // Путь должен существовать. Если файла нет - то никакой ошибки никто не покажет
        //'PREVIEW_PICTURE'    => $_FILES['DETAIL_PICTURE'],
        //'DETAIL_PICTURE'    => CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].'/images/01.gif')
    );

    $PRODUCT_ID = $el2xml[$item->id];
    if ($PRODUCT_ID) {
        echo ' exist';
        $stat ['exist'] ++;
        unset($el2xml[$item->id]);

        $el = new CIBlockElement;
        $el->Update($PRODUCT_ID, array(
            'NAME' => $item->name
        ));

        return -1;
    } else {
        // Простое добавление
        $el = new CIBlockElement;
        if ($PRODUCT_ID = $el->Add($product)) {
            echo " New ID: ".$PRODUCT_ID;
            $stat ['add'] ++;
        } else {
            if (strpos($el->LAST_ERROR, 'Элемент с таким символьным кодом уже существует') !== false) {
                echo ' повтор! ';
                $code .= '-'.$item->code;
                msAddProduct($item, $code, $el2xml, $sectionId, $stat, $PRODUCT_ID);
                $stat ['повтор'] ++;
            } else {
                echo " Error: ".$el->LAST_ERROR;
                $stat ['error-add'] ++;
            }
            return false;
        }
    }
    return $PRODUCT_ID;
}
