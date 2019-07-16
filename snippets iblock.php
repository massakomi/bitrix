<?

CModule::IncludeModule('iblock'); // возвращает код в зависимости от результата поиска модуля



global $arrFilterMy;
$arrFilterMy = array(
    // '!ITEM_ID' => 'S%' // не начинается на "S"
    // '>ITEM_ID' => 'S%' // начинается на "S"
    // 'NAME' => false // пустое (вернул все, странно, ведь пустых нет)
    // 'NAME' => "" // пустое
    // "?BRAND" => "Скутер | Бензопил" // фиг там, не работает
    // '>BODY' => "Е" // да блин, тоже не работает, как будто не все поля участвуют....
);

"FILTER_NAME" => "arrFilterMy",






//                                      Сортировка

$arOrder = Array(
    // 'SHOW_COUNTER' => 'nulls,asc',
    // 'SHOW_COUNTER' => 'asc,nulls', // при одинаковых ключах берется последний
    // "RAND" => "ASC"
);

// Сортировка описана тут
http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php


//                                      Фильтр

// Маска - http://dev.1c-bitrix.ru/api_help/iblock/filters/string.php
$arFilter = Array(
    // "!" - не равно "<" - меньше ">" - больше
    // "<=" - меньше либо равно  ">=" - больше либо равно
    // "><" - между

    // 'IBLOCK_ID' => '', // отключить фильтр
    // 'IBLOCK_ID' => 6, // точное значение
    // 'IBLOCK_ID' => array(5, 6, 7),
    // '><IBLOCK_ID' => '5,8', // между

    // 'SHOW_COUNTER' => false, // только незаполненные значения. IBLOCK_SECTION_ID - не ср
    // '!SHOW_COUNTER' => false, // только заполненные

    // '?NAME' => 'Выставка' // содержат строку
    // "NAME" => "Головачева%", // начинающиеся на
    // "?NAME" => "(добавление || чпу) && (настройка || движка)", //
    // "!%NAME" => 'Выставка', // не содержит подстроку либо число

    // ">DATE_CREATE" => date('d.m.Y', time() - 86400*30),

    // Фильтр по свойству! Обязательно _VALUE в конце!! полчаса мучался, почему не фильтрует
    $arrFilter['=PROPERTY_SHOW_ON_HOMEPAGE_VALUE'] = ['Да', 'Y', 'Yes', 1];

    // 'PROPERTY_YEAR' => '2002' // по свойству YEAR
    // 'PROPERTY_AUTHOR.NAME' => // по параметру свойства

    // Фильтрация по нескольким значениям множественного свойства
    // array("ID" => CIBlockElement::SubQuery("ID", array("IBLOCK_ID" => 21, "PROPERTY_PKE" => 3))),

    // Исключение записей, заканчивающихся на..
    // "!NAME" => array("%.jpg%", "%.jpeg%", "%.png%")

    // несколько свойств сразу
    // array(
    //    "LOGIC" => "OR",
    //    array("<IBLOCK_ID" => 8),
    //    array(">=IBLOCK_ID" => 5, 'DETAIL_TEXT' => false),
    //),

    // не сработавшие
    // 'IBLOCK_SECTION_ID' => false, // выбирает все, хотя я хотел только пустые
    // '?DATE_CREATE' => '2015' // ничего не показывает хотя такие есть
    // '!?NAME' => 'Выставка' // думал "не содержит подстроку" - выбрал все
);


//                                      Select


$arSelect = Array("ID", "IBLOCK_ID", "PROPERTY_*"); // обязательно с PROPERTY_* id iblock_id

'CATALOG_GROUP_1' - чтобы вывести все свойства товара тоже, цены и т.п. 1 это код цены


/*--------------------------------------------------------------------------------------*/
// 1 ТИПЫ ИНФОБЛОКОВ
$list = CIBlockType::GetList($arSort=array('NAME' => 'ASC'), $arFilter=array());
while ($type = $list->Fetch()) {
    $title = CIBlockType::GetByIDLang($type["ID"], LANG);
    echo '<br />'.$title['NAME'].' - '.$type['ID'];
}



/*--------------------------------------------------------------------------------------*/
// 2 ИНФОБЛОКИ
$res = CIBlock::GetList(
    $arSort=Array(),
    $arFilter=Array(
        //'TYPE'=>'catalog',
        //'SITE_ID'=>SITE_ID,
        //'ACTIVE'=>'Y',
        //"CNT_ACTIVE"=>"Y",
        //"!CODE"=>'my_products'
        "CHECK_PERMISSIONS" => "N"
    ), $returnElementsCount=true
);
while($ar_res = $res->Fetch()) {
    echo '<br />'.$ar_res['NAME'].': '.$ar_res['ELEMENT_CNT'];
}

// Краткий метод - все инфоблоки по типу, без возможности особой фильтрации по свойствам
$iblocks = GetIBlockList('offers');
while($arIBlock = $iblocks->GetNext()) {
    echo "<div title='id=$arIBlock[ID]'>$arIBlock[NAME] $arIBlock[ID]</div>";
}

// Один инфоблок по ID с полями и свойствами
$res = CIBlock::GetArrayByID(19);
echo '<pre>'; print_r($res); echo '</pre>';

// Иблок по ид
$iblock = CIBlock::GetById($_POST['iblock_id'])->Fetch();

// Изменене настроек инфоблока в обход админки
$id = 5;
$fields = CIBlock::GetFields($id);
$fields["PREVIEW_PICTURE"]['DEFAULT_VALUE']["FROM_DETAIL"] = "Y";
$fields["PREVIEW_PICTURE"]['DEFAULT_VALUE']["SCALE"] = "Y";
$fields["PREVIEW_PICTURE"]['DEFAULT_VALUE']["WIDTH"] = "200";
$fields["PREVIEW_PICTURE"]['DEFAULT_VALUE']["COMPRESSION"] = 80;
$res = CIBlock::setFields($id, $fields);
var_dump($res);


// Свойства инфоблока
$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID" => 3));
$ids = array();
while ($prop_fields = $properties->GetNext())
{
    $ids []= $prop_fields["ID"];
}


/*--------------------------------------------------------------------------------------*/
// 3 РАЗДЕЛЫ

// Количество подразделов
$subSectionCount = CIBlockSection::GetCount(['IBLOCK_ID' => 2]);

// Обычный список разделов
$items = CIBlockSection::GetList(
    $arOrder=[],
    $arFilter=array('IBLOCK_ID'=>2),
    $returnElementCount=true, // вернуть ELEMENT_CNT
    $arSelect=[]
);
while($arItem = $items->GetNext()) {
    echo '<br />'.$arItem['NAME'].' ('.$arItem['ELEMENT_CNT'].')';
}

// Извлечь пользовательские поля
$res = CIBlockSection::GetList(
    $arOrder=Array('SORT' => 'ASC', 'ID' => 'DESC'),
    $arFilter=array('IBLOCK_ID'=>2),
    $returnElementCount=false,
    $arSelect=array('IBLOCK_ID', 'ID', 'NAME', 'UF_SEO_TITLE', 'UF_SEO_KEYWORDS', 'UF_SEO_DESCRIPTION')
);
while($arItem = $items->GetNext()) {
}

// Массовый перебор
$items = CIBlockSection::GetList(
    $arOrder=[],
    $arFilter=array('IBLOCK_ID'=>2, 'ACTIVE' => 'Y'),
    $ELEMENT_CNT=true,
    $arSelect=[]
);
while ($ob = $items->GetNextElement()){
    $arFields = $ob->GetFields();
    $updateFields = [];

    echo '<br />'.$arFields['ID'];


    // Сами операции проводить тут внутри, все сохранять внутрь $updateFields, а общая проверка внизу

/*      // Отключить разделы без элементов
    if (!$arFields['ELEMENT_CNT']) {
        echo ' ('.$arFields['ELEMENT_CNT'].')'
        $updateFields = ['ACTIVE' => 'N'];
    }
*/

/*      // Обновить сео-значения
    $updateFields = Array(
        "IPROPERTY_TEMPLATES" => [
            "SECTION_PAGE_TITLE" => $a[1]
        ]
    );
*/

    if ($updateFields && $arFields["ID"] > 0) {
        $bs = new CIBlockSection;
        $x = $bs->Update($arFields["ID"], $updateFields);
        if ($x) {
            echo ' +++ ';
        } else {
            echo ' ---- '.$bs->LAST_ERROR;
        }
    }
}

// В случае долгого выполнения (парсинг, лучше использовать такой лог вместо echo)
function flog($t)
{
    echo str_replace("\n", '<br />', $t);
    //fwrite($a = fopen('log.txt', 'a+'), $t); fclose($a);
}
flog("\n".$arFields['ID']);
flog(' - пустой exturl');


// Цепочка меню - путь по дереву от корня до раздела SECTION_ID (пользовательские поля не возвращаются).
// CIBlockSection::GetNavChain(IBLOCK_ID, SECTION_ID);

// Разделы и элементы сразу
// сначала все разделы, потом все элементы
$items = CIBlockSection::GetMixedList(
    $arOrder=array(),
    $arFilter=array('IBLOCK_ID'=>2),
    $returnElementCount=false,
    $arSelect=array()
);
while($arItem = $items->GetNext()) {
    if ($arItem['DEPTH_LEVEL']) {
    	echo '<br /><b>РАЗДЕЛ</b> '.$arItem['NAME'].' - '.$arItem['DEPTH_LEVEL'].' - <b>'.$arItem['ID'].'</b>';
    } else {
        echo '<br />- '.$arItem['NAME'].' - '.$arItem['IBLOCK_SECTION_ID'].'';
    }
    // bpre($arItem);
}

// Выборка произвольного поля из раздела
$arSelect = array('UF_FILTERS');
$arFilter = array('IBLOCK_ID' => 5, 'ID' => $arResult["ID"]);
$rsSect = CIBlockSection::GetList(array(), $arFilter, false, $arSelect);
$arSect = $rsSect->GetNext();


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
        $sections [$arFields['ID']] = $arFields['NAME'];
    }
    return $sections;
}



/*--------------------------------------------------------------------------------------*/
// 4 ЭЛЕМЕНТЫ

$res = CIBlockElement::GetList(
    $arOrder=['SORT' => 'ASC'],
    $arFilter=['IBLOCK_ID' => 2],
    $arGroup=false,
    $nav=false, // ['nPageSize'=>50]
    $arSelect=false
);
while($ob = $res->GetNextElement()){
    $arFields = $ob->GetFields(); // поля элемента ASSOC массив
    $arProps = $ob->GetProperties(); // ключ - код свойства
}


// Простая выборка элементов инфоблока
$res = CIBlockElement::GetList(Array(), ['IBLOCK_ID' => 2], false);
while ($ob = $res->GetNextElement()){
    $arFields = $ob->GetFields();

}

// Простая выборка 1 элемента по свойству
$arFilter = Array(
    'IBLOCK_ID' => 2,
    'PROPERTY_ARTNUMBER' => $article
);
$res = CIBlockElement::GetList(Array(), $arFilter, false);
if ($ob = $res->GetNextElement()){
    $product = $ob->GetFields();
} 


// Узнать кол-во элементов по условию
$res = CIBlockElement::GetList(Array(), ['IBLOCK_ID' => 2], Array());


// Получение 1 элемента
$res = CIBlockElement::GetByID(18);
// $ob = $res->GetNext(); // assoc array, без свойств, только поля
$ob = $res->GetNextElement(); // CIBElement
$arProps = $ob->GetProperties(); // так можно вытащить свойства
$arFields = $ob->GetFields();


// Массовый перебор, массовое удаление, изменение свойств
$res = CIBlockElement::GetList(
    $arOrder=['SORT' => 'ASC'],
    $arFilter=['IBLOCK_ID' => 2],
    $arGroup=false,
    $nav=false,
    $arSelect=false
);
while($ob = $res->GetNextElement()){
    $arFields = $ob->GetFields();

    // Удаление элементов
    CIBlockElement::delete($arFields['ID']);

    echo '<br />'.$arFields['ID'];
}


// Пример группировки по полю. Можно посчитать кол-во элементов по разным условиям
$res = CIBlockElement::GetList(
    $arOrder=['CNT' => 'DESC'],
    $arFilter=['IBLOCK_ID' => 2],
    $arGroupBy=['IBLOCK_SECTION_ID'],
    false,
    $arSelect=[]
);
while ($ob = $res->GetNextElement()){
    $arFields = $ob->GetFields();
    echo '<pre>'; print_r($arFields); echo '</pre>';
}



function listElements($iblockId, $withProps=false, $arrFilter='', $arNavStartParams=false)
{
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
        /*if ($func) {
        	$func($ob, $arFields);
        }*/
        $data []= $arFields;
    }
    return $data;
}



/*--------------------------------------------------------------------------------------*/
// 5 СВОЙСТВА

$IBLOCK_ID = 4;
$properties = CIBlockProperty::GetList(
    Array("sort"=>"asc", "name"=>"asc"),
    Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$IBLOCK_ID)
);
while ($prop_fields = $properties->GetNext()) {
    echo '<pre>'; print_r($prop_fields); echo '</pre>';
}


$property_enums = CIBlockPropertyEnum::GetList(Array("VALUE"=>"ASC"), Array("CODE"=>"fil_models_brand"));
while($enum_fields = $property_enums->GetNext()) {
    //
}



// свойства 1 элемента
$db_props = CIBlockElement::GetProperty($arParams["IBLOCK_ID"], $ElementID, array("sort" => "asc"), Array("CODE"=>"item_viewed"));
while($ar_props = $db_props->Fetch()){
    //
}



/*--------------------------------------------------------------------------------------*/
// Постраничная разбивка списков

$res = CIBlockElement::GetList(
    $arOrder=['SORT' => 'ASC'],
    $arFilter=['IBLOCK_ID' => 2],
    $arGroup=false,
    $nav=['nPageSize'=>50],
    $arSelect=false
);


$res->NavStart(20);
echo $res->NavPrint("Постранично");
echo '<hr />';

while ($res->NavNext(true, "f_")) {
    echo "[".$f_ID."] ".$f_NAME."<br>";
}

echo '<hr />';
echo $res->NavPrint("Постранично");



/*--------------------------------------------------------------------------------------*/

// Работа с SEO-свойствами инфоблоков при помощи классов "\Bitrix\Iblock\InheritedProperty".

// Инфоблок
$ipropIblockValues = new \Bitrix\Iblock\InheritedProperty\IblockValues($iblockId);
// Раздел
$ipropSectionValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($iblockId,$iblockSectionId);
// Элемент
$ipropElementValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId,$iblockSectionId);

use \Bitrix\Iblock\InheritedProperty;

// Изменить сео-параметры раздела краткая версия
$vals = new \Bitrix\Iblock\InheritedProperty\SectionValues($arFields['IBLOCK_ID'], $arFields['ID']);
$iprop = $vals->getValues();
$updateFields = Array(
    "IPROPERTY_TEMPLATES" => [
        "SECTION_PAGE_TITLE" => $iprop['SECTION_PAGE_TITLE'] .' 123 '
    ]
);


// Список всех свойств
$bs = new CIBlockSection;

$arFields = Array(
    "IPROPERTY_TEMPLATES" => [

        "SECTION_META_TITLE" => "",
        "SECTION_META_KEYWORDS" => "",
        "SECTION_META_DESCRIPTION" => "",
        "SECTION_PAGE_TITLE" => "",
   
        "ELEMENT_META_TITLE" => "",
        "ELEMENT_META_KEYWORDS" => "",
        "ELEMENT_META_DESCRIPTION" => "",
        "ELEMENT_PAGE_TITLE" => "",

        /*"SECTION_PICTURE_FILE_ALT" => "",
        "SECTION_PICTURE_FILE_TITLE" => "",
        "SECTION_PICTURE_FILE_NAME" => "",
        "SECTION_DETAIL_PICTURE_FILE_ALT" => "",
        "SECTION_DETAIL_PICTURE_FILE_TITLE" => "",
        "SECTION_DETAIL_PICTURE_FILE_NAME" => "",
        "ELEMENT_PREVIEW_PICTURE_FILE_ALT" => "",
        "ELEMENT_PREVIEW_PICTURE_FILE_TITLE" => "",
        "ELEMENT_PREVIEW_PICTURE_FILE_NAME" => "",
        "ELEMENT_DETAIL_PICTURE_FILE_ALT" => "",
        "ELEMENT_DETAIL_PICTURE_FILE_TITLE" => "",
        "ELEMENT_DETAIL_PICTURE_FILE_NAME" => "",*/
    ]
);

$res = $bs->Update($a[1], $arFields);

echo $bs->LAST_ERROR;



/*--------------------------------------------------------------------------------------*/

// МОДУЛЬ торговый каталог

CModule::IncludeModule('catalog');

// Получение списка товаров по условию
$res = CCatalogProduct::GetList(
    $arSort=array(),
    $arFilter=array(),
    false,
    false
);
while ($p = $res->Fetch())
{
    
}

// Список товаров с разными настройками
/*
Флаги (Y/N/D)* *D - значение берется из настроек модуля.

AVAILABLE - доступность товара к покупке (Y/N, поле обновляется автоматически);
TYPE - тип товара (простой товар \Bitrix\Catalog\ProductTable::TYPE_PRODUCT);

VAT_ID - код НДС;
VAT_INCLUDED - флаг (Y/N) включен ли НДС в цену;
QUANTITY - количество товара на складе;
QUANTITY_RESERVED - зарезервированное количество;
QUANTITY_TRACE - флаг (Y/N/D)* "включить количественный учет"
CAN_BUY_ZERO - флаг (Y/N/D)* "разрешить покупку при отсутствии товара";
SUBSCRIBE - флаг (Y/N/D)* "разрешить подписку при отсутствии товара";

PURCHASING_PRICE - закупочная цена.
PURCHASING_CURRENCY - валюта закупочной цены;

WEIGHT - вес единицы товара;
WIDTH - ширина товара (в мм);
LENGTH - длина товара (в мм);
HEIGHT - высота товара (в мм);
MEASURE - ID единицы измерения;
*/

$res = CCatalogProduct::GetList(
    $arSort=array(),
    $arFilter=array(
        // "CAN_BUY_ZERO" => "Y" // разрешить покупку при отсутствии товара
    ),
    false,
    false
);
while ($p = $res->Fetch())
{
    extract($p);
    echo "<br />$ID $ELEMENT_NAME $QUANTITY";
}


$arResult["PRICES"] = CIBlockPriceTools::GetCatalogPrices($arParams["IBLOCK_ID"], $code='BASE');

// После создания товаров надо обновить фасетный индекс, если используется
function fasetUpdate($iblockId)
{
    CModule::IncludeModule('iblock');
    Bitrix\Iblock\PropertyIndex\Manager::DeleteIndex($iblockId);
    Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($iblockId);

    $index = \Bitrix\Iblock\PropertyIndex\Manager::createIndexer($iblockId);
    $index->startIndex();
    $index->continueIndex(0); // создание без ограничения по времени
    $index->endIndex();
}

// Редактирование
$arFields = array('CAN_BUY_ZERO' => 'D');
$res = CCatalogProduct::Update($PRODUCT_ID, $arFields);


// Получение и свойств товара, и свойств элемента инфоблока сразу
$product = CCatalogProduct::GetByIDEx($arItem['PRODUCT_ID'], true);
// $product['PROPERTIES']
$art = $product['PROPERTIES']['CML2_ARTICLE']['VALUE'];


// Чтобы сделать обычный элемент инфоблока товаром, нужно вот такое сделать
CCatalogProduct::Add([
    'ID' => $arFields['ID'],
    'TYPE' => \Bitrix\Catalog\ProductTable::TYPE_PRODUCT,
    'AVAILABLE' => 'Y',
    'QUANTITY' => 1
], $boolCheck = true);

// И цену добавить
CPrice::SetBasePrice($arFields['ID'], $output['price'], 'EUR');

// Добавление скидки (это не сработало, вместо него - правила работы с корзиной CSaleDiscount)
// /bitrix/admin/cat_discount_admin.php?lang=ru
$ID = CCatalogDiscount::Add([
    'SITE_ID' => SITE_ID,
    'ACTIVE' => 'Y',
    'NAME' => 'Скидка',
    'CURRENCY' => 'EUR',
    'VALUE_TYPE' => 'P',
    'VALUE' => 10
]);
if ($ID < 1) {
    echo '<div style="color:red"> Ошибки при добавлении скидки:<br />';
    echo $APPLICATION->GetException()->GetString();
    echo '</div>';
}

// Правила работы с корзиной
$ID = CSaleDiscount::Add([
    'LID' => SITE_ID,
    'ACTIVE' => 'Y',
    'NAME' => 'Скидка '.$arFields['NAME'],
    'PRIORITY' => 1,
    'LAST_DISCOUNT' => 1,
    'USER_GROUPS' => [2],
    'CURRENCY' => 'EUR',
    //'DISCOUNT_TYPE' => 'P',
    //'DISCOUNT_VALUE' => 10,
    'CONDITIONS' => [
        'CLASS_ID' => 'CondGroup',
        'DATA' => array('All' => 'AND', 'True' => 'True'),
        'CHILDREN' => [[
            "CLASS_ID" => "CondIBElement",
            "DATA" => [
                "logic" => "Equal",
                "value" => $arFields['ID']
            ],
            'CHILDREN' => Array()
        ]]
    ],
    'ACTIONS' => array(
        'CLASS_ID' => 'CondGroup',
        'DATA' => Array(
            'All' => 'AND',
        ),
        'CHILDREN' => Array(
            Array(
                'CLASS_ID' => 'ActSaleBsktGrp',
                'DATA' => Array(
                    'Type' => 'Discount',
                    'Value' => $discount,
                    'Unit' => 'Perc',
                    'All' => 'AND',
                ),
                'CHILDREN' => Array()
            ),
        )
    ),
]);
if ($ID < 1) {
    echo '<div style="color:red"> Ошибки при добавлении скидки:<br />';
    echo $APPLICATION->GetException()->GetString();
    echo '</div>';
}
var_dump($ID);


/*--------------------------------------------------------------------------------------*/

// РЕДАКТИРОВАНИЕ, ДОБАВЛЕНИЕ


// Изменение свойств - делать надо так, чтобы не затерлись другие свойства.
CIBlockElement::SetPropertyValuesEx($arFields['ID'], false, $props);

// Часто для CODE нужен транслит
'CODE' => Cutil::translit($name, LANGUAGE_ID, $params=array()),

// Привязать элемент $id к разделу $groupId, добавив его к общий список
$db_old_groups = CIBlockElement::GetElementGroups($id);
$allGroups = [];
while($ar_group = $db_old_groups->Fetch()) {
    $allGroups[] = $ar_group["ID"];
}
$allGroups []= $groupId;
CIBlockElement::SetElementSection($id, $allGroups);



// Редактирование элемента
/*
$props = array(
    //'MARKA' => $marka,
);
$fields = Array(
    'NAME' => $row['Название']
);
editProduct($id, $fields, $props)
*/
function editProduct($fields='', $props='')
{
    if ($fields) {
        $el = new CIBlockElement;
        $res = $el->Update($id], $fields);
        if (!$res) {
            echo '<br />'.$el->LAST_ERROR;
        }
    }
    if ($props) {
    	CIBlockElement::SetPropertyValuesEx($id, false, $props);
    }
}




/*
// Добавление элемента - различные опции и варианты
$el = new CIBlockElement;

$PROP = array();
$PROP[21] = 123456;
$PROP['YEAR'] = "2015";
$PROP['AUTHORS'] = array('One', 'Two');
$PROP['FILES'] = array(
    // 'n0' =>  CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"].'/images/01.gif')
);
$PROP['YEAR'] = Array(
    "n0" => Array(
        "VALUE" => "value",
        "DESCRIPTION" => "description"
    )
);
// $PROP['FF'][0] = Array("VALUE" => Array ("TEXT" => "значение", "TYPE" => "html или text"));
// $PROP['FF'] = Array("VALUE" => $ENUM_ID); // значения типа списка, $ENUM_ID ид значения

$arLoadProductArray = Array(
    // Эти 2 поля единственные обязательные
    "IBLOCK_ID"      => 6,
    "NAME"           => "Тестовый элемент",

    "PROPERTY_VALUES"=> $PROP,
    "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
    "ACTIVE"         => "Y",
    "PREVIEW_TEXT"   => "текст для списка элементов",
    "DETAIL_TEXT"    => "текст для детального просмотра",
    // Путь должен существовать. Если файла нет - то никакой ошибки никто не покажет
    // 'DETAIL_PICTURE' => $_FILES['DETAIL_PICTURE']
    // "DETAIL_PICTURE" => CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"].'/images/01.gif')
);


if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
	echo "New ID: ".$PRODUCT_ID;
    dumpElement($PRODUCT_ID);
    CIBlockElement::Delete($PRODUCT_ID);
} else {
    echo "Error: ".$el->LAST_ERROR;
}
*/

// Упрощенный вариант
function addProduct()
{
    $props = array();
    $props = [
        'YEAR' => '2017'
    ];

    $product = Array(
        // Эти 2 поля единственные обязательные
        "IBLOCK_ID"      => 6,
        "NAME"           => "Тестовый элемент",

        "PROPERTY_VALUES" => $props,
        "ACTIVE"         => "Y"
    );

    $product ['CODE'] = Cutil::translit($a[1], LANGUAGE_ID, $params=array());
    $product ['DETAIL_TEXT'] = $a[1];
    $product ['DETAIL_TEXT_TYPE'] = 'html';

    $el = new CIBlockElement;
    if ($id = $el->Add($product)) {
        echo '<br />Added '.$id;

        /*CCatalogProduct::Add([
            'ID' => $id,
            'TYPE' => \Bitrix\Catalog\ProductTable::TYPE_PRODUCT,
            'AVAILABLE' => 'Y',
            'QUANTITY' => 1
        ], $boolCheck = true);

        CPrice::SetBasePrice($id, $price, 'EUR');*/
    } else {
        echo '<br />Error '.$el->LAST_ERROR;
    }
}


// Добавление оффера к продукту 46
$PROP = array();
// это свойство находится в свойствах инфоблока ТП Как Тип "Привязка к товарам"
$PROP['CML2_LINK'] = 46;

----------------------------------------------------------------------------------------------------

//  Пример обновления превьюшки


// $src это может быть и локальный путь, и даже http: ссылка на картинку!
function setDetailPic($id, $src)
{
    if (!$id) {
        return ;
    }
    if (!$src || !file_exists($src)) {
        echo ' нет фото или не существует';
        return ;
    }
    $el = new CIBlockElement;
    $fields = Array(
        'DETAIL_PICTURE' => CFile::MakeFileArray($src)
    );
    $res = $el->Update($id, $fields);
    if (!$res) {
        echo '<br />'.$el->LAST_ERROR;
    }
}


$width = 200;
$PRODUCT_ID = 1212;
$res = CIBlockElement::GetByID($PRODUCT_ID);
$product = $res->GetNext();

if ($product['DETAIL_PICTURE']) {

    // Если превьюшка есть, но она большая
    if ($product['PREVIEW_PICTURE']) {
        $path = CFile::GetPath($product['PREVIEW_PICTURE']);
        $path = $_SERVER['DOCUMENT_ROOT'].$path;
        list($w, $h) = getimagesize($path);
        if ($w > $width + 100) {
            CFile::Delete($product['PREVIEW_PICTURE']);
            $product['PREVIEW_PICTURE'] = '';
        }
    }

    // Здесь показан способ генерации превьюшки на основе детальной картинки, размер 200 на 200
    if (!$product['PREVIEW_PICTURE']) {
        $path = CFile::GetPath($product['DETAIL_PICTURE']);
        $path = $_SERVER['DOCUMENT_ROOT'].$path;

        $preview = CFile::ResizeImageGet($product['DETAIL_PICTURE'], array('width'=>200,
            'height'=>200), BX_RESIZE_IMAGE_PROPORTIONAL, true);

        $el = new CIBlockElement;
        $fields = Array(
            'PREVIEW_PICTURE' => CFile::MakeFileArray($preview['src'])
        );

        $res = $el->Update($PRODUCT_ID, $fields);
        if (!$res) {
        	echo $el->LAST_ERROR;
        } else {
            echo 'Успешно';
        }
    }
}

