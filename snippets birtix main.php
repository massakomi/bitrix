
Действия при первоначальной настройке битрикс
- templates, php_interface в local папке
- при первоначальном создании папок лучше сделать 2 файла .section.php index.php с базовым содержанием "в разработке", создаешь папку и туда аплоадишь эти 2 файла, так быстрее создаются эти разделы, чем вручную забивать одно и то же
- создания инфоблока - совместное отображение разделов и элементов. символьный код обязательный. не забыть Доступ для всех чтение. при добавлении свойств галочки использовать в умном фильтре, показать развернутым
- в настройках в список сайтов и главный модуль - поменять название сайта. отключить количественный учет и складской учет. цены на тестовые товары ставить разные, чтобы сортировку проверить
- для каталогов, статей, новостей и т.п. использовать комплексные компоненты (а не делать отдельно list-detail), т.к. иначе urlrewrite будет формироваться без редиректов
- пример реализации сортировки каталога - в /catalog /components/catalog.section.php
- примеры меню я скопировал .top.menu.php .top.menu_ext.php
- настройку компонентов (особенно catalog) лучше делать через режим редактирования компонента
- смарт фильтр подключается в section_vertical.php и в принципе это нормально, т.к. он чаще всего слева а каталог весь справа. обортечные коды каталога можно разместить в section_vertical.php (удалив оттуда коды бутстрапа), там же смарт-фильтр, а внутренний блок каталога - в catalog.section. section_horizontal.php можно вообще удалить
- в настройках каталога USE_STORE=N DETAIL_SHOW_POPULAR=N
- постраничная навигация не подключается, а указывается свойством PAGER_TEMPLATE, PAGE_ELEMENT_COUNT количество
- чаще всего нужны компоненты (сразу копировать их шаблоны)
    * breadcrumb
    * catalog (скопировал в /catalog код подключения)
    * catalog.element
    * catalog.section
    * catalog.section.list (хотя в catalog есть!)
    * catalog.smart.filter
    * catalog.top (если есть хиты продаж)
    * menu (для шапки horizontal_multilevel)
    * news (для раздела статей и новостей список+детально+реврайты)
    * news.line (для вывода на главной странице или в колонке)
    * sale.basket.basket (основная корзина)
    * sale.basket.basket.line (в шапке. редактировать top_template.php. ajax_template.php это то же самое + список продуктов, который можно отключить. template.php только технический код)
    * sale.order.ajax (оформление заказа)
    * search.title (форма поиска в шапке, простая)
    * system.pagenavigation (брал round но он сложноватый, 28 копий блоков было)

----------------------------------------------------------------------------------------------------

ШАБЛОН САМОЕ ОСНОВНОЕ

<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>

<html xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
  <head>
	<link rel="shortcut icon" type="image/x-icon" href="<?=SITE_DIR?>favicon.ico" />
	<?$APPLICATION->ShowHead();?>
	<title><?$APPLICATION->ShowTitle()?></title>
  </head>
  <body>
  <div id="panel"><?$APPLICATION->ShowPanel();?></div>




<img src="<?=SITE_TEMPLATE_PATH?>/images/developer.png" alt="MKS">

<?=inc('telephone1.php')?>

<?php webForm(1, 'call_popup') ?>

<?php

$curPage = $APPLICATION->GetCurPage(true);

$_SERVER["DOCUMENT_ROOT"].'/bitrix/templates/tgresurs/js/functions.js';

$APPLICATION->SetTitle('Медполимерпром');
$APPLICATION->SetPageProperty('title', '');
$APPLICATION->SetPageProperty('keywords', '');
$APPLICATION->SetPageProperty('description', '');

$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css');
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/functions.js');
?>




// ---------------------------------------------------------------------------------------------------------------------
                                        main.include



<?// Текстовые инклюд простой
$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
		"AREA_FILE_SHOW" => "file",
		"PATH" => SITE_DIR."include/viewed_product.php",
		//"AREA_FILE_RECURSIVE" => "N",
	   //"EDIT_MODE" => "html",
	),
	false,
	Array('HIDE_ICONS' => 'Y')
);
// Инклюд файла /sect_inc.php
// этот файл может находится и в подпапке - /catalog/sect_sidebar.php
$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
		"AREA_FILE_SHOW" => "sect",
		"AREA_FILE_SUFFIX" => "inc",
		"AREA_FILE_RECURSIVE" => "N",
		"EDIT_MODE" => "html",
	),
	false,
	Array('HIDE_ICONS' => 'Y')
);
?>





// ---------------------------------------------------------------------------------------------------------------------
                                        Все компонентов по алфавиту

кроме тех которые подключены стандартно personal, search





// ---------------------------------------------------------------------------------------------------------------------
                                        breadcrumb

<?$APPLICATION->IncludeComponent("bitrix:breadcrumb","",Array(
        "START_FROM" => "0",
        "PATH" => "",
        "SITE_ID" => "s1"
    )
);?>

<?php $APPLICATION->IncludeComponent("bitrix:breadcrumb", ""); ?>

<?if ($curPage != SITE_DIR."index.php"):?>
	<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "", array(
			"START_FROM" => "0",
			"PATH" => "",
			"SITE_ID" => "-"
		),
		false,
		Array('HIDE_ICONS' => 'Y')
	);?>
<?endif?>



// ---------------------------------------------------------------------------------------------------------------------
                                        catalog.top

<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.top",
	"top",
	array(
		"ACTION_VARIABLE" => "action",
		"ADD_PICT_PROP" => "MORE_PHOTO",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_TO_BASKET_ACTION" => "ADD",
		"BASKET_URL" => "/cart/",
		"BRAND_PROPERTY" => "BRAND_REF",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"COMPARE_NAME" => "CATALOG_COMPARE_LIST",
		"COMPARE_PATH" => "",
		"COMPATIBLE_MODE" => "N",
		"CONVERT_CURRENCY" => "Y",
		"CURRENCY_ID" => "RUB",
		"CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"OR\",\"True\":\"True\"},\"CHILDREN\":[]}",
		"DATA_LAYER_NAME" => "dataLayer",
		"DETAIL_URL" => "",
		"DISCOUNT_PERCENT_POSITION" => "bottom-right",
		"DISPLAY_COMPARE" => "N",
		"ELEMENT_COUNT" => "9",
		"ELEMENT_SORT_FIELD" => "sort",
		"ELEMENT_SORT_FIELD2" => "id",
		"ELEMENT_SORT_ORDER" => "asc",
		"ELEMENT_SORT_ORDER2" => "desc",
		"ENLARGE_PRODUCT" => "STRICT",
		"FILTER_NAME" => "",
		"HIDE_NOT_AVAILABLE" => "N",
		"HIDE_NOT_AVAILABLE_OFFERS" => "N",
		"IBLOCK_ID" => "2",
		"IBLOCK_TYPE" => "catalog",
		"LABEL_PROP" => array(
		),
		"LABEL_PROP_MOBILE" => "",
		"LABEL_PROP_POSITION" => "top-left",
		"LINE_ELEMENT_COUNT" => "",
		"MESS_BTN_ADD_TO_BASKET" => "В корзину",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_COMPARE" => "Сравнить",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"MESS_RELATIVE_QUANTITY_FEW" => "мало",
		"MESS_RELATIVE_QUANTITY_MANY" => "много",
		"MESS_SHOW_MAX_QUANTITY" => "Наличие",
		"OFFERS_CART_PROPERTIES" => array(
			0 => "COLOR_REF",
			1 => "SIZES_SHOES",
			2 => "SIZES_CLOTHES",
		),
		"OFFERS_FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"OFFERS_LIMIT" => "5",
		"OFFERS_PROPERTY_CODE" => array(
			0 => "SIZES_SHOES",
			1 => "SIZES_CLOTHES",
			2 => "MORE_PHOTO",
			3 => "",
		),
		"OFFERS_SORT_FIELD" => "sort",
		"OFFERS_SORT_FIELD2" => "id",
		"OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_ORDER2" => "desc",
		"OFFER_ADD_PICT_PROP" => "MORE_PHOTO",
		"OFFER_TREE_PROPS" => array(
			0 => "COLOR_REF",
			1 => "SIZES_SHOES",
		),
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PRICE_CODE" => array(
			0 => "BASE",
		),
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons,compare",
		"PRODUCT_DISPLAY_MODE" => "Y",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPERTIES" => array(
			0 => "NEWPRODUCT",
		),
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "",
		"PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
		"PRODUCT_SUBSCRIPTION" => "Y",
		"PROPERTY_CODE" => array(
			0 => "MANUFACTURER",
			1 => "MATERIAL",
			2 => "",
		),
		"PROPERTY_CODE_MOBILE" => "",
		"RELATIVE_QUANTITY_FACTOR" => "5",
		"ROTATE_TIMER" => "30",
		"SECTION_URL" => "",
		"SEF_MODE" => "N",
		"SEF_RULE" => "",
		"SHOW_CLOSE_POPUP" => "N",
		"SHOW_DISCOUNT_PERCENT" => "Y",
		"SHOW_MAX_QUANTITY" => "M",
		"SHOW_OLD_PRICE" => "Y",
		"SHOW_PAGINATION" => "Y",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_SLIDER" => "Y",
		"SLIDER_INTERVAL" => "3000",
		"SLIDER_PROGRESS" => "N",
		"TEMPLATE_THEME" => "blue",
		"USE_ENHANCED_ECOMMERCE" => "Y",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "Y",
		"VIEW_MODE" => "SECTION",
		"COMPONENT_TEMPLATE" => "top"
	),
	false
);?>




// ---------------------------------------------------------------------------------------------------------------------
                                        menu


Последний код который использовал 30.03.2019
<?$APPLICATION->IncludeComponent("bitrix:menu", "top", Array(
    "ALLOW_MULTI_SELECT" => "N",
    "CHILD_MENU_TYPE" => "sub",
    "DELAY" => "N",
    "MAX_LEVEL" => "5",
    "MENU_CACHE_GET_VARS" => "",
    "MENU_CACHE_TIME" => "3600",
    "MENU_CACHE_TYPE" => "N",
    "MENU_CACHE_USE_GROUPS" => "Y",
    "ROOT_MENU_TYPE" => "top",
    "USE_EXT" => "Y"
	),
	false
);?>


слишком много тут мусора, все по сути одинаково

<?$APPLICATION->IncludeComponent("bitrix:menu", "bottom_menu", array(
		"ROOT_MENU_TYPE" => "bottom",
		"MAX_LEVEL" => "1",
		"CACHE_SELECTED_ITEMS" => "N",
		"MENU_CACHE_TYPE" => "A",
		"MENU_CACHE_TIME" => "36000000",
		"MENU_CACHE_USE_GROUPS" => "Y",
		"MENU_CACHE_GET_VARS" => array(),
	),
	false
);?>
<?php
// имя файла .top.menu.php
$APPLICATION->IncludeComponent("bitrix:menu", "top_menu", array(
		"ROOT_MENU_TYPE" => "top",
		"MAX_LEVEL" => "2",
        // В подпапках могут быть под меню .left.menu.php. Они добавятся к меню
		"CHILD_MENU_TYPE" => "left",
        // Подключать .top.menu_ext.php файлы тоже
		// "USE_EXT" => "Y",
		"MENU_CACHE_TYPE" => "A",
		"MENU_CACHE_TIME" => "3600",
		"MENU_CACHE_USE_GROUPS" => "Y",
		"MENU_CACHE_GET_VARS" => Array()
	)
);
?>
<?php
// разные вызовы меню
// просто горизонтальное одноуровневое автоматическое меню
$APPLICATION->IncludeComponent('bitrix:menu', "top_horizontal", array(
		"ROOT_MENU_TYPE" => "top",
		"MENU_CACHE_TYPE" => "Y",
		"MENU_CACHE_TIME" => "36000000",
		"MENU_CACHE_USE_GROUPS" => "Y",
		"MENU_CACHE_GET_VARS" => array(),
		"MAX_LEVEL" => "1",
		"USE_EXT" => "N",
		"ALLOW_MULTI_SELECT" => "N"
	)
);
$APPLICATION->IncludeComponent("bitrix:menu", "left", $menuArray = array(
        "ROOT_MENU_TYPE" => "catalog_inc",
        "MENU_CACHE_TYPE" => "A",
        "MENU_CACHE_TIME" => "3600",
        "MENU_CACHE_USE_GROUPS" => "Y",
        "MENU_CACHE_GET_VARS" => array(),
        "MAX_LEVEL" => "1",
        "CHILD_MENU_TYPE" => "",
        "USE_EXT" => "Y",
        "DELAY" => "N",
        "ALLOW_MULTI_SELECT" => "N"
    ), false
);
$APPLICATION->IncludeComponent("bitrix:menu", "tree_horizontal", array(
    	"ROOT_MENU_TYPE" => "left",
    	"MENU_CACHE_TYPE" => "A",
    	"MENU_CACHE_TIME" => "36000000",
    	"MENU_CACHE_USE_GROUPS" => "Y",
    	"MENU_CACHE_GET_VARS" => array(
    	),
    	"MAX_LEVEL" => "2",
    	"CHILD_MENU_TYPE" => "left",
    	"USE_EXT" => "Y",
    	"DELAY" => "N",
    	"ALLOW_MULTI_SELECT" => "N"
    	),
	false
);
?>



// ---------------------------------------------------------------------------------------------------------------------
                                        news.line

<?$APPLICATION->IncludeComponent("bitrix:news.line", "slider-index", Array(
        "IBLOCK_TYPE" => "info",
        "IBLOCKS" => Array(5),
        "NEWS_COUNT" => 99,
        "FIELD_CODE" => Array("ID", "CODE", 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DATE_CREATE'),
        "SORT_BY1" => "ACTIVE_FROM",
        "SORT_ORDER1" => "DESC",
        "SORT_BY2" => "SORT",
        "SORT_ORDER2" => "ASC",
        "DETAIL_URL" => "articles/#ELEMENT_ID#",
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "300",
        "CACHE_GROUPS" => "Y"
    )
);?>

// ---------------------------------------------------------------------------------------------------------------------
                                        news.list

<?$APPLICATION->IncludeComponent("bitrix:news.list", "last_news", Array(
        "IBLOCK_TYPE" => "data",
        "IBLOCK_ID" => "2",
        "NEWS_COUNT" => "2",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "3600",
        "CACHE_FILTER" => "Y",
        "CACHE_GROUPS" => "Y",
        'SET_TITLE' => 'N',
        "SET_STATUS_404" => "N",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "ADD_SECTIONS_CHAIN" => "N",
        "SET_BROWSER_TITLE" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_META_DESCRIPTION" => "N"
    )
);?>



// ---------------------------------------------------------------------------------------------------------------------
                                        sale.basket.basket.line

более лучший код т.к. тут отключен список продуктов, и разные ссылки, отображения профиля
<?$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line","top",Array(
        "HIDE_ON_BASKET_PAGES" => "Y",
        "PATH_TO_BASKET" => SITE_DIR."personal/cart/",
        "PATH_TO_ORDER" => SITE_DIR."personal/order/make/",
        "PATH_TO_PERSONAL" => SITE_DIR."personal/",
        "PATH_TO_PROFILE" => SITE_DIR."personal/",
        "PATH_TO_REGISTER" => SITE_DIR."login/",
        "POSITION_FIXED" => "N",
        "POSITION_HORIZONTAL" => "right",
        "POSITION_VERTICAL" => "top",
        "SHOW_AUTHOR" => "N",
        "SHOW_DELAY" => "N",
        "SHOW_EMPTY_VALUES" => "Y",
        "SHOW_IMAGE" => "N",
        "SHOW_NOTAVAIL" => "N",
        "SHOW_NUM_PRODUCTS" => "Y",
        "SHOW_PERSONAL_LINK" => "N",
        "SHOW_PRICE" => "Y",
        "SHOW_PRODUCTS" => "N",
        "SHOW_SUMMARY" => "Y",
        "SHOW_TOTAL_PRICE" => "Y"
    )
);?>


<?php // Ссылка на корзину, либо сама корзина всплывающая видимо
$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line", ".default", array(
        "PATH_TO_BASKET" => SITE_DIR."personal/cart/",
        "PATH_TO_PERSONAL" => SITE_DIR."personal/",
        "SHOW_PERSONAL_LINK" => "N"
        ),
        false,
    Array('')
); ?>


// ---------------------------------------------------------------------------------------------------------------------
                                        search.title search.form

<?$APPLICATION->IncludeComponent("bitrix:search.title","top",Array(
        "SHOW_INPUT" => "Y",
        "INPUT_ID" => "title-search-input",
        "CONTAINER_ID" => "title-search",
        "PRICE_CODE" => array("BASE","RETAIL"),
        "PRICE_VAT_INCLUDE" => "Y",
        "PREVIEW_TRUNCATE_LEN" => "150",
        "SHOW_PREVIEW" => "Y",
        "PREVIEW_WIDTH" => "75",
        "PREVIEW_HEIGHT" => "75",
        "CONVERT_CURRENCY" => "Y",
        "CURRENCY_ID" => "RUB",
        "PAGE" => "#SITE_DIR#search/index.php",
        "NUM_CATEGORIES" => "3",
        "TOP_COUNT" => "10",
        "ORDER" => "date",
        "USE_LANGUAGE_GUESS" => "Y",
        "CHECK_DATES" => "Y",
        "SHOW_OTHERS" => "Y",
        "CATEGORY_0_TITLE" => "Новости",
        "CATEGORY_0" => array("iblock_news"),
        "CATEGORY_0_iblock_news" => array("all"),
        "CATEGORY_1_TITLE" => "Форумы",
        "CATEGORY_1" => array("forum"),
        "CATEGORY_1_forum" => array("all"),
        "CATEGORY_2_TITLE" => "Каталоги",
        "CATEGORY_2" => array("iblock_books"),
        "CATEGORY_2_iblock_books" => "all",
        "CATEGORY_OTHERS_TITLE" => "Прочее"
    )
);?>

<? $APPLICATION->IncludeComponent("bitrix:search.form","", Array(
		"USE_SUGGEST" => "N",
		"PAGE" => SITE_DIR."search/index.php"
	)
);?>


// ---------------------------------------------------------------------------------------------------------------------
                                        system.auth.form

<?php // Ссылка на авторизацию и всплывающая форма
$APPLICATION->IncludeComponent("bitrix:system.auth.form", "eshop", array(
    	"REGISTER_URL" => SITE_DIR."login/",
    	"PROFILE_URL" => SITE_DIR."personal/",
    	"SHOW_ERRORS" => "N"
    	), false,
	Array()
); ?>






// ---------------------------------------------------------------------------------------------------------------------
                                        form.result.new




<?php



// веб форма
$APPLICATION->IncludeComponent("bitrix:form.result.new", "call", Array(
        "WEB_FORM_ID" => 1,
        "SUCCESS_URL" => "/success.php",
    )
);
// в форме должна быть кнопка  <button type="submit" value="" name="web_form_submit"></button>
// у полей дожлны быть form_text_1 form_text_2 и т.п.


?>








// ---------------------------------------------------------------------------------------------------------------------
                                        Разная ерунда





<?php
//                                      ГОЛОСОВАНИЕ
// Голосование Числовое за элемент инфоблока (фотку, статью, товар и прочее)
// Выводится форма для голосования (простая). Результаты потом видны в просмотра элемента в админке.
$APPLICATION->IncludeComponent("bitrix:iblock.vote","",Array(
		"IBLOCK_TYPE" => "content",
		"IBLOCK_ID" => "10",
		"ELEMENT_ID" => 156,
		"MAX_VOTE" => "5",
		"VOTE_NAMES" => array("0","1","2","3","4"),
		"SET_STATUS_404" => "N",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600"
	)
);

// Голосование Мне нравится + bitrix:rating.result
$APPLICATION->IncludeComponent("bitrix:rating.vote", "", array(
    "IBLOCK_TYPE" => "catalog",
    "IBLOCK_ID" => "3",
    "SET_TITLE" => "Y",
    "AJAX_MODE" => "N",
    "CACHE_TYPE" => "A",
    "CACHE_TIME" => "3600"
));

// Карта сайта
$APPLICATION->IncludeComponent("bitrix:main.map","",Array(
		"LEVEL" => "3",
		"COL_NUM" => "1",
		"SHOW_DESCRIPTION" => "Y",
		"SET_TITLE" => "Y",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600"
	)
);
?>