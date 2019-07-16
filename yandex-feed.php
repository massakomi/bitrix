<?php


/*
Вариант 1
Экспорт в Яндекс-маркет

Настраивается здесь /bitrix/admin/cat_export_setup.php?lang=ru
В яндексе надо указать путь
http://dckomi.ru/bitrix/catalog_export/export.php
*/




// Вариант 2 - вручную своя реализация


// фид для яндекс маркет

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/load/yandex_run.php");

CModule::IncludeModule('iblock');


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

function listElements($iblockId, $withProps=false, $arrFilter='', $arNavStartParams=false)
{
    $obCache = new CPHPCache;
    if ($obCache->InitCache(3600, 'listElements-'.$iblockId.$withProps, "/") && $_GET['cash']) {
        $data = $obCache->GetVars();
    } else {
        $obCache->StartDataCache();

        $arFilter = Array(
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y'
        );
        if ($arrFilter) {
            foreach ($arrFilter as $k => $v) {
            	$arFilter [$k]= $v;
            }
        }
        CModule::IncludeModule('iblock');
        $res = CIBlockElement::GetList(Array('SORT' => 'ASC'), $arFilter, false, $arNavStartParams);
        $data = [];
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();
            if ($arFields['PREVIEW_PICTURE']) {
                $arFields['PREVIEW_PICTURE'] = CFile::GetPath($arFields['PREVIEW_PICTURE']);
            }
            if ($withProps) {
                $arProps = $ob->GetProperties();
                foreach ($arProps as $k => $v) {
                	$arFields ['PROP_'.$k] = $v;
                }
            }
            $data []= $arFields;
        }

        $obCache->EndDataCache($data);
    }

    return $data;
}


global $xml;
$xml = new DOMDocument('1.0', 'utf-8');

//КОДИРОВКА ТОЛЬКО UTF-8
//Элемент realty-feed
$yml = $xml->appendChild($xml->createElement('yml_catalog'));
attr($yml, 'date', date('Y-m-d H:i'));
$shop = $yml->appendChild($xml->createElement('shop'));
node($shop, 'name', 'lps-dom.ru');
node($shop, 'company', 'Леспромстрой');

$currencies = node($shop, 'currencies');
node($currencies, 'currency', '', ['id' => 'RUR', 'rate' => 1]);

$sections = sections(7);
$categories = node($shop, 'categories');
foreach ($sections as $cat) {
    $attrs = ['id' => $cat['ID']];
    if ($cat['IBLOCK_SECTION_ID']) {
    	$attrs ['parentId'] = $cat['IBLOCK_SECTION_ID'];
    }
	node($categories, 'category', $cat['NAME'], $attrs);
}

$offers = node($shop, 'offers');

$elements = listElements(7, true);
foreach ($elements as $element) {
    $attrs = ['id' => $element['ID'], 'available' => 'true'];
	$offer = node($offers, 'offer', '', $attrs);
    node($offer, 'url', 'https://www.lps-dom.ru'.$element['DETAIL_PAGE_URL']);
    node($offer, 'price', preg_replace('~[^\d]~i', '', $element['PROP_PRICE']['VALUE']));
    node($offer, 'currencyId', 'RUR');
    node($offer, 'categoryId', $element['IBLOCK_SECTION_ID']);
    node($offer, 'name', $element['NAME']);


    $pic = CFile::GetPath($element['PROP_MORE_PHOTO']['VALUE'][0]);
    node($offer, 'picture', 'https://www.lps-dom.ru'.$pic);

}

header('Content-Type: text/xml; charset=utf-8');
$xml->formatOutput = true;
echo $xml->saveXML($title);


?>