<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');


function node($offer, $tag, $text='', $attrs=[])
{
    global $xml;
    $node = $offer->appendChild($xml->createElement($tag));
    if ($text) {
    	$node->appendChild($xml->createTextNode($text));
    }
    foreach ($attrs as $attr => $value) {
    	attr($node, $attr, $value);
    }
    return $node;
}

function attr($rlf, $attr, $value)
{
    global $xml;
    $attr = $xml->createAttribute($attr);
    $attr->value = $value;
    $rlf->appendChild($attr);
}

function sections($iblock)
{
    $arFilter = Array(
        'IBLOCK_ID' => $iblock,
        'ACTIVE' => 'Y'
    );
    $sections = [];
    $res = CIBlockSection::GetList(Array(), $arFilter, false);
    while ($ob = $res->GetNextElement()){
        $arFields = $ob->GetFields();
        //$sections [$arFields['ID']] = $arFields['NAME'];
        $sections [] = $arFields;
    }
    return $sections;
}

// Склады
$dbResult = CCatalogStore::GetList(
   array(),
   array(),
   false,
   false
);
$stores = [];
while ($v = $dbResult->fetch()) {
    $stores [$v['ID']]= $v['TITLE'];
}

$rsStoreProduct = \Bitrix\Catalog\StoreProductTable::getList(array(
    //'filter' => array('STORE.ACTIVE'=>'Y')
));
$storeProductsIds = [];
while ($v = $rsStoreProduct->fetch()) {
    if ($v['AMOUNT']) {
    	$storeProductsIds [$v['PRODUCT_ID']][$v['STORE_ID']] = $v['AMOUNT'];
    }
}

// Закупочные цены
$res = CCatalogProduct::GetList(
    $arSort=array(),
    $arFilter=array(),
    false,
    false
);
$purchasing = [];
while ($p = $res->Fetch())
{
    $purchasing [$p['ID']]= $p['PURCHASING_PRICE'];
}

// Цены
$prices = [];
$res = CPrice::GetList(
    $arSort=array(),
    $arFilter=array(),
    false,
    false
);
while ($p = $res->Fetch()) {
    $prices [$p['PRODUCT_ID']]= $p;
}


global $xml;
$xml = new DOMDocument('1.0', 'utf-8');

//КОДИРОВКА ТОЛЬКО UTF-8
//Элемент realty-feed
$yml = $xml->appendChild($xml->createElement('yml_catalog'));
attr($yml, 'date', date('Y-m-d H:i'));
$shop = $yml->appendChild($xml->createElement('shop'));
node($shop, 'name', 'mrfon.ru');
node($shop, 'company', 'Мастерфон');

$currencies = node($shop, 'currencies');
node($currencies, 'currency', '', ['id' => 'RUR', 'rate' => 1]);

$sections = sections(5);
$categories = node($shop, 'categories');
foreach ($sections as $cat) {
    $attrs = ['id' => $cat['ID']];
    if ($cat['IBLOCK_SECTION_ID']) {
    	$attrs ['parentId'] = $cat['IBLOCK_SECTION_ID'];
    }
	node($categories, 'category', $cat['NAME'], $attrs);
}

$offers = node($shop, 'offers');

$elements = listElements(5, true, ['PROPERTY_YML' => false], $nav=false, $sort='', $arSelect='', $arGroup='', $cash=false);
//var_dump(count($elements)); exit;
foreach ($elements as $key => $element) {
    $attrs = ['id' => $element['ID']/*, 'available' => 'true'*/];
	$offer = node($offers, 'offer', '', $attrs);
    node($offer, 'url', 'https://mrfon.ru/'.$element['DETAIL_PAGE_URL']);
    node($offer, 'price', $prices[$element['ID']]['PRICE']);
    node($offer, 'price-purchising', $purchasing[$element['ID']]);
    node($offer, 'currencyId', 'RUR');
    node($offer, 'categoryId', $element['IBLOCK_SECTION_ID']);
    node($offer, 'name', $element['NAME']);

    //$pic = CFile::GetPath($element['PROP_MORE_PHOTO']['VALUE'][0]);
    node($offer, 'picture', 'https://mrfon.ru'.$element['PREVIEW_PICTURE']);

    if ($storeProductsIds[$element['ID']]) {
    	$storesNode = node($offer, 'store');
        foreach ($storeProductsIds[$element['ID']] as $k => $v) {
        	node($storesNode, 'store', $v, ['title' => $stores[$k]]);
        }
    }

    /*if ($element['PREVIEW_PICTURE'] && $_COOKIE['dev']) {
    	break;
    }*/
}

header('Content-Type: text/xml; charset=utf-8');
$xml->formatOutput = true;
echo $xml->saveXML($title);


