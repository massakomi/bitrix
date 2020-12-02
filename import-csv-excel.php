<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

// получаем идентификатор модуля
$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialchars($request['mid'] != '' ? $request['mid'] : $request['id']);

// подключаем наш модуль
Loader::includeModule($module_id);

$exportUrl = $GLOBALS["APPLICATION"]->GetCurPageParam(
    'export=1',
    array("dt_page","gdhtml","edit")
);

Loader::includeModule('iblock');
Loader::includeModule('catalog');

$output = '';

// Импорт цен из эксель файла в каталог (проблема в том, что phpexcel крашится с memory превышением или по тайм лимиту, хотя файл всего 400кб был, поэтому от этой идеи пришлось отказаться)
function importExcelPrices()
{
    require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/quartzauto/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/quartzauto/PHPExcel-1.8/Classes/PHPExcel.php';
    $filename = $_FILES['file']['tmp_name'];
    $xls = PHPExcel_IOFactory::load($filename);
    $sheet = $xls->getActiveSheet();
    $sheetData = $sheet->toArray();
    $header = array_shift($sheetData);
    $keyId = array_search('ID', $header);
    if ($keyId === false) {
        return 'Не нашел поле "ID"';
    }
    $keyPrice = array_search('Цена', $header);
    if ($keyPrice === false) {
        return 'Не нашел поле "Цена"';
    }

    $import = [];
    foreach ($sheetData as $k => $v) {
    	$import [$v[$keyId]] = $v[$keyPrice];
    }

    $res = CCatalogProduct::GetList(
        $arSort=array(),
        $arFilter=array(
            // "CAN_BUY_ZERO" => "Y" // разрешить покупку при отсутствии товара
        ),
        false,
        false
    );
    $export = [[
        'ID', 'Наименование', 'Артикул', 'Цена', 'Кол-во', 'Основной раздел'
    ]];
    $output = '';
    while ($p = $res->Fetch()) {
        $product = CCatalogProduct::GetByIDEx($p['ID'], true);
        $currentPrice = $product['PRICES'][1]['PRICE'];
        $newPrice = $import[$p['ID']];
        $output .= '<br />'.$p['ID'];
        if (!$newPrice) {
            $output .= ' - нет новой цены';
        } elseif ($currentPrice == $newPrice) {
            $output .= ' - цены не изменились';
        } else {
            $output .= ' - '.$currentPrice.' => '.$newPrice;
            CPrice::SetBasePrice($p['ID'], $newPrice, 'RUB');
        }
    }
    return $output;
}

if ($_FILES['file']) {

// это скрипты чтения csv файла, его разбора, а потом импорт в инфоблок (изменение цены и активация деактивация элеметов)

    function readCsv()
    {

        $filename = $_FILES['file']['tmp_name'];

        $row = 1;
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $csvData = [];
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $csvData []= $data;
            }
            fclose($handle);
        }

        $header = array_shift($csvData);
        foreach ($header as $k => $v) {
        	$header [$k] = iconv('windows-1251', 'utf-8', $v);
            $header [$k] = trim(preg_replace('~\s+~i', ' ', $header [$k]));
        }

        $import = [];
        foreach ($csvData as $vals) {
            $row = [];
            foreach ($vals as $k => $v) {
            	$row [$header[$k]] = iconv('windows-1251', 'utf-8', $v);
            }
        	$import []= $row;
        }
        return $import;
    }

    function importCsvPrice()
    {
        $csv = readCsv();
        $ids = [];
        foreach ($csv as $key => $import) {
            if (!isset($import['Код'])) {
                $output .= '<br />Не найдена колонка "Код"';
            	break;
            }
            if (!isset($import['Цена,руб'])) {
                $output .= '<br />Не найдена колонка "Цена,руб"';
            	break;
            }
        	$element = getElementIdByCode($import['Код']);
            if (!$element) {
                $output .= '<br /><span style="color:red">элемент "'.$import['Код'].'" не найден в базе</span>';
                $output .= productAdd($import, $PRODUCT_ID);
                $ids []= $PRODUCT_ID;
                continue;
            }
            $row = '';
            $id = $element['ID'];
            if ($element['ACTIVE'] == 'N') {
                $el = new CIBlockElement;
                if ($el->Update($id, ['ACTIVE' => 'Y'])) {
                    $row .= ' элемент активирован';
                }
            }
            $ids []= $id;

            $product = CCatalogProduct::GetByIDEx($id, true);

            $row .= changePrice($product, $import);
            if (!$row) {
                continue;
            }
            $output .= '<br />'.$import['Код'].' '.$row;
        }

        $output .= '<h3>Деактивация не найденных элементов</h3>';

        $res = CIBlockElement::GetList([], ['IBLOCK_ID' => 2, '!ID' => $ids, 'ACTIVE' => 'Y'], false, false, ['ID', 'CODE', 'NAME']);
        $ids = [];
        while ($arItem = $res->Fetch()){
            $el = new CIBlockElement;
            if ($el->Update($arItem['ID'], ['ACTIVE' => 'N'])) {
                $output .= "<br />Элемент снят спублиации: ".$arItem['NAME'].' ['.$arItem['ID'].']';
            } else {
                $output .= "<br /> Ошибка деактивации: ".$el->LAST_ERROR;
            }
        }

        return $output;
    }

    function changePrice($product, $import)
    {
        $currentPrice = $product['PRICES'][1]['PRICE'];
        $newPrice = $import['Цена,руб'];
        $newPrice = preg_replace('~\s+~i', '', str_replace(',', '.', $newPrice));

        if (!$newPrice) {
            $output .= ' - нет новой цены';
        } elseif ($currentPrice == $newPrice) {
            return false;
        } else {
            $output .= ' - '.$currentPrice.' => '.$newPrice;
            CPrice::SetBasePrice($product['ID'], $newPrice, 'RUB');
        }
        return $output;
    }

    function importFile()
    {
        if ($_FILES['file']['error']) {
            return 'Ошибка загрузки файла';
        }

        $name = $_FILES['file']['name'];
        if (strpos($name, '.csv')) {
        	$output = importCsvPrice();
        } else {
            $output = '<div>Требуется CSV файл</div>';
        }

        return $output;
    }


    function loadUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    function productAdd($import, &$PRODUCT_ID)
    {
        $measures = [
            'шт' => 5,
            'компл' => 6,
            'комплект' => 6
        ];

        $code = $import['Код'];
        $art = $import['Артикул'];
        $name = $import['Наименование'];
        $priozv = $import['Произ-во'];
        $ed = $import['Ед'];
        $price = $import['Цена,руб'];
        $ost = $import['Остаток'];
        $strih = $import['Штрихкод'];
        $new = $import['Новинка!'];

        $price = str_replace(',', '.', $price);
        $price = preg_replace('~[^\d,\.]~i', '', $price);

        $jsonText = loadUrl('http://quartzauto.ru/_bs4_jsonWO.asp?id='.$code, $cash=true, $expired=0);
        $json = json_decode($jsonText, true);

        $good = @$json['good'][0];
        $primenyaemost = @$good['ks'];
        if ($primenyaemost == 'Null') {
        	$primenyaemost = '';
        }

        $text = @$json['text'][0];

        $props = [
            'ARTNUMBER' => $art,
            'MANUFACTURER' => $priozv,
            'BARCODE' => $strih,
            'APPLICABILITY' => $primenyaemost,
            'UPAK' => $good['f']
        ];
        if ($json['images']) {
            $props ['IMAGES'] = [];
            foreach ($json['images'] as $v) {
                $url = 'http://quartzauto.ru/'.$v;
                $local = 'images/'.md5($url).'.jpg';
                if (!file_exists($local)) {
                    $content = loadUrl($url);
                    if ($content) {
                        fwrite($a = fopen($local, 'w+'), $content); fclose($a);
                    }
                }
            	$props ['IMAGES'][] = CFile::makeFileArray($local);
            }
            //fwrite($a = fopen('log.txt', 'a+'), "\n".date('Y-m-d H:i:s').' '.$k.'/'.count($csv)); fclose($a);
        }
        if ($new) {
        	$props ['NEWPRODUCT'] = 1;
        }

        $product = Array(
            // Эти 2 поля единственные обязательные
            'IBLOCK_ID'         => 2,
            'NAME'              => $name,
            'ACTIVE'            => 'Y',
            'PROPERTY_VALUES'   => $props,
            'IBLOCK_SECTION'    => 17,

            'CODE'              => $code,
            'TIMESTAMP_X'       => date('Y-m-d H:i:s'),

            'DETAIL_TEXT' => $text,
            'DETAIL_TEXT_TYPE' => 'html'
        );

        // Простое добавление
        $el = new CIBlockElement;
        if ($PRODUCT_ID = $el->Add($product)) {

        	$output .= "<br />New ID: ".$PRODUCT_ID;

            // Чтобы сделать обычный элемент инфоблока товаром, нужно вот такое сделать
            CCatalogProduct::Add([
                'ID' => $PRODUCT_ID,
                'TYPE' => \Bitrix\Catalog\ProductTable::TYPE_PRODUCT,
                'AVAILABLE' => 'Y',
                'QUANTITY' => $ost,
                'MEASURE' => $measures[$ed]
            ], $boolCheck = true);

            // И цену добавить
            CPrice::SetBasePrice($PRODUCT_ID, $price, 'RUB');

        } else {
            $output .= "<br />Error: ".$el->LAST_ERROR;
        }
        return $output;
    }

    function getElementIdByCode($code)
    {
        static $ids;
        if (!isset($ids)) {
            $res = CIBlockElement::GetList([], ['IBLOCK_ID' => 2], false, false, ['ID', 'CODE', 'ACTIVE']);
            $ids = [];
            while ($ob = $res->Fetch()){
                $ids [$ob['CODE']] = $ob;
            }
        }
        return $ids[$code];
    }

    $output = importFile();

}

// Это код который экспортирует инфоблок в excel, сохраняет в xlsx и в zip
if ($_GET['export']) {

    function priceExportGet()
    {
        $res = CCatalogProduct::GetList(
            $arSort=array(),
            $arFilter=array('ACTIVE' => 'Y'),
            false,
            false
        );
        $export = [[
            '№ п/п',
            'Код',
        ]];

        $key = 1;
        while ($p = $res->Fetch()) {
            $product = CCatalogProduct::GetByIDEx($p['ID'], true);
            $export []= [
                $key ++,
                $product['CODE'],
            ];
        }
        return $export;
    }


    function priceExcelCreate($export)
    {
       // echo '<pre>'; print_r($export); echo '</pre>';

        $filename = 'price_'.date('Y-m-d');

        require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/quartzauto/PHPExcel-1.8/Classes/PHPExcel.php';
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $symbs = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        $row = 1;
        foreach ($export as $vals) {
            foreach ($vals as $key => $val) {
                $sumb = $symbs[$key];
                /*if (is_numeric($val)) {
                	$val = '="'.$val.'"';
                }*/
                //$sheet->setCellValue($sumb.$row, $val);
                $sheet->setCellValueExplicit(
                    $sumb.$row,
                    $val,
                    PHPExcel_Cell_DataType::TYPE_STRING
                );
            }
            $row ++;
        }
        // ширины я подбирал после генерации первого эксель-файла, по текстам
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(5);

        // красивые яркие хеадеры
        $borderedHead = new PHPExcel_Style();
        $borderedHead->applyFromArray(
        array(
            'alignment' => array(
                'horizontal'    => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'      => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'rotation'      => 0,
                'wrap'          => true,
                'shrinkToFit'   => false,
                'indent'    => 5
            ),
            'font'=>array(
                'bold'      => true,
            ),
            'borders' => array(
                'bottom'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right'   => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'top'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'left'   => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            ),
            'fill' => array(
                'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                'color'   => array(
                    'rgb' => 'f6f6f6'
                )
            )
        ));
        $sheet->setSharedStyle($borderedHead, 'A1:K1');

        // Выравнивание столбца по правой
        $cnt = count($export);
        foreach (['B', 'G', 'H'] as $b) {
            $sheet->getStyle($b.'2:'.$b.$cnt)
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        }

    /*
        // Скачивание 2007
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    */

        $saveTo = '/upload/price.xlsx';
        $zipTo = '/upload/price.zip';
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($_SERVER['DOCUMENT_ROOT'].$saveTo);

        $zip = new ZipArchive;
        $zip->open($_SERVER['DOCUMENT_ROOT'].$zipTo, ZipArchive::CREATE);
        $zip->addFile($_SERVER['DOCUMENT_ROOT'].$saveTo, basename($saveTo));
        $zip->close();

        $output = '<p><a href="'.$saveTo.'">Excel</a>  <a href="'.$zipTo.'">Zip</a></p>';

        return $output;
    }

    $export = priceExportGet();
    $output = priceExcelCreate($export);
}

?>

<div><a href="<?=$exportUrl?>">Экспорт цен</a></div>

<h3>Импорт цен из CSV</h3>
<form enctype="multipart/form-data" method="post">
    <input type="file" name="file" />
    <input type="submit" value="Импорт файла" />
</form>

<p>Загрузите CSV файл</p>
<img src="/images/example.png" style="max-width:100%;" alt="" />

<?php
if ($output) {
    echo '<hr />';
    echo '<h3>Результат работы</h3>';
	echo $output;
}
?>
