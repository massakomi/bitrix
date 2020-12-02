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

class Log {

	public function __construct($filename, $mode='a+')
    {
        $this->logfile = fopen($filename, $mode);
	}

	public static function get()
    {
        static $log;
        if (!isset($log)) {
        	$filename = 'log.txt';
            register_shutdown_function(function() {
                Log::get()->log('register_shutdown_function finish!');
            });
        	$log = new Log($filename, 'w+');
        }
        return $log;
	}

    public function log($txt, $nl=1)
    {
        if ($nl) {
        	$txt = "\n".date('Y-m-d H:i:s').' '.$txt;
        }
        fwrite($this->logfile, $txt);
    }
}

function sections2xmlid()
{
    static $existSections;
    if (!isset($existSections)) {
        CModule::IncludeModule('iblock');
        $arFilter = Array(
            'IBLOCK_ID' => 5,
            '!XML_ID' => false
        );
        $res = CIBlockSection::GetList(Array('SORT' => 'ASC'), $arFilter, false, $arSelect);
        $existSections = [];
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();
            $existSections [$arFields['XML_ID']] = $arFields['ID'];
        }
    }
    return $existSections;
}

function elements2xmlid()
{
    static $existSections;
    if (!isset($existSections)) {
        CModule::IncludeModule('iblock');
        $arFilter = Array(
            'IBLOCK_ID' => 5,
            '!XML_ID' => false
        );
        $res = CIBlockElement::GetList(Array('SORT' => 'ASC'), $arFilter, false, $arSelect);
        $existSections = [];
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();
            $existSections [$arFields['XML_ID']] = $arFields['ID'];
        }
    }

    return $existSections;
}

function getElementIdByUid($productUid)
{
    static $els2id;
    if (!isset($els2id)) {
    	$els2id = elements2xmlid();
    }
    return $productId = $els2id[$productUid];
}

function getStoreId($xmlId)
{
    static $store2id;
    if (!$store2id) {
        $store2id = [];
        $res = CCatalogStore::GetList([], [], false, false);
        while ($item = $res->fetch()) {
            $store2id [$item['XML_ID']] = $item['ID'];
        }
    }
    return $store2id[$xmlId];
}

function msStocks($sklad)
{
    //preg_match('~\?id=(.*)~i', $item->meta->uuidHref, $a);
    //$productUid = $a[1];
    $list = StockReport::byStore($sklad, StockReportQuerySpecs::create([
        "offset" => 3000,
        "maxResults" => 1000
    ]));
    if ($list->meta->nextHref) {
    	//
    }
    //echo '<pre>'; print_r($list->meta); echo '</pre>';
    //var_dump(count($list->rows));
    //echo '<pre>'; print_r($list->meta); echo '</pre>';
    $stocks = [];
    foreach ($list->rows as $key => $item) {
        preg_match('~\?id=(.*)~i', $item->meta->uuidHref, $a);
        $productUid = $a[1];
        foreach ($item->stockByStore as $k => $v) {
            preg_match('~\?id=(.*)~i', $v->meta->uuidHref, $a);
            $stockUid = $a[1];
        	$stocks [$productUid][$stockUid] = $v->stock;
        }
    }
    return $stocks;
}


//ini_set('max_execution_time', 60);

$login = '';
$password = '';
define('MS_AUTH_HASH', '');

