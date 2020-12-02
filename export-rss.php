<?php

function listElements($iblockId, $withProps=false, $arrFilter='', $arNavStartParams=false, $sort='', $cash=true)
{
    $cacheId = 'listElements'.serialize(func_get_args());
    $obCache = new CPHPCache;
    if ($obCache->InitCache(3600, $cacheId, "/") && !$_GET['clear_cache_session'] && !$_GET['clear_cache'] && $cash) {
        $data = $obCache->GetVars();
    } else {
        $obCache->StartDataCache();

        $arFilter = Array(
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y'
        );
        if (!$sort) {
        	$sort = Array('SORT' => 'ASC');
        }
        if ($arrFilter) {
            foreach ($arrFilter as $k => $v) {
            	$arFilter [$k]= $v;
            }
        }
        CModule::IncludeModule('iblock');
        $res = CIBlockElement::GetList($sort, $arFilter, false, $arNavStartParams);
        $data = [];
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();
            if ($withProps) {
                $arProps = $ob->GetProperties();
                $arFields ['PROPERTIES'] = $arProps;
            }
            if ($arFields['ID']) {
            	$data [$arFields['ID']]= $arFields;
            } else {
                $data []= $arFields;
            }
        }

        $obCache->EndDataCache($data);
    }
    return $data;
}


// генерация rss фида для элементов инфоблока

function node($parent, $tag, $text='', $attrs='', $cdata=false)
{
    global $xml;
    $el = $parent->appendChild($xml->createElement($tag));
    if ($text) {
        if ($cdata) {
        	$el->appendChild($xml->createCDATASection($text));
        } else {
            $el->appendChild($xml->createTextNode($text));
        }
    }
    if ($attrs) {
        foreach ($attrs as $name => $value) {
            $attr = $xml->createAttribute($name);
            $attr->value = $value;
            $el->appendChild($attr);
        }
    }
    return $el;
}

function rssFeedNews($iblock=1)
{
    global $APPLICATION;
    $APPLICATION->RestartBuffer();

    global $xml;
    $xml = new DOMDocument('1.0', 'utf-8');

    $yml = node($xml, 'rss', '', ['version' => '2.0', 'xmlns:atom' => 'http://www.w3.org/2005/Atom']);
    $shop = node($yml, 'channel');
    //$yml = $xml->appendChild($xml->createElement('yml_catalog'));
    //attr($yml, 'date', date('Y-m-d H:i'));
    //$shop = $yml->appendChild($xml->createElement('shop'));
    node($shop, 'title', 'Новости');
    node($shop, 'link', 'httpы://'.$_SERVER['HTTP_HOST'].'/news.html');
    node($shop, 'lastBuildDate', date('r'));
    node($shop, 'generator', 'Bitrix');
    node($shop, 'language', 'ru-ru');

    $elements = listElements($iblock, $withProps=1, $arrFilter='', $arNavStartParams=['nPageSize'=>15], $sort=['ID' => 'DESC'], $cash=0);
    foreach ($elements as $element) {
        $path = CFile::getPath($element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE']);
        if ($path) {
        	$path = 'https://'.$_SERVER['HTTP_HOST'].$path;
        }
        //$attrs = ['id' => $element['ID'], 'available' => 'true'];
        //$element['PREVIEW_TEXT'] = preg_replace('~src="/~i', 'src="https://'.$_SERVER['HTTP_HOST'].'/', $element['PREVIEW_TEXT']);
    	$offer = node($shop, 'item', '', $attrs=[]);
        node($offer, 'title', $element['NAME']);
        node($offer, 'link', 'https://'.$_SERVER['HTTP_HOST']. $element['DETAIL_PAGE_URL']);
        node($offer, 'guid', 'https://'.$_SERVER['HTTP_HOST']. $element['DETAIL_PAGE_URL'], ['isPermaLink' => 'true']);
        node($offer, 'image', $path);
        node($offer, 'description', $element['PREVIEW_TEXT'], '', true);
        node($offer, 'category', 'Новости');
        node($offer, 'pubDate', date('r'));
    }

    header('Content-Type: text/xml; charset=utf-8');
    $xml->formatOutput = true;
    echo $xml->saveXML($title);

}