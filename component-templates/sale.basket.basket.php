<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();


// Удаление из корзины
if ($_GET['del']) {
    CSaleBasket::Delete($_GET['del']);
    header('Location: /personal/cart/');
    exit;
}

// Изменение количества
if ($_GET['QUANTITY']) {
    CSaleBasket::Update((int)$_GET['id'], ['QUANTITY' => $_GET['QUANTITY']]);
    var_dump(1);
    exit;
}


// Добавление в корзину по ид товара, по свойству
if ($_POST['add-by-article']) {
    CModule::IncludeModule('iblock');
    $arFilter = Array(
        'IBLOCK_ID' => 2,
        'PROPERTY_ARTNUMBER' => $_POST['add-by-article']
    );
    $res = CIBlockElement::GetList(Array(), $arFilter, false);
    while ($ob = $res->GetNextElement()){
        $arFields = $ob->GetFields();
        Add2BasketByProductID($arFields['ID'], 1);
    }
}



dbg($arResult);

if (empty($arResult['ERROR_MESSAGE']))
{


/*


-------------------------------------------------------------------

Выводы по разбору шаблона

1) перебирать нужно $arResult["GRID"]["ROWS"]. С учетом правил в пункте 1 ниже. DELAY, CAN_BUY, NOT_AVAILABLE, SUBSCRIBE
2) гифты можно вставить, они работают сами по себе
		$APPLICATION->IncludeComponent(
			'bitrix:sale.gift.basket',
			'.default',
			$giftParameters,
			$component
		);
3) все js можно вырубить, только потом реализовать свое обновление количества

*/

foreach ($arResult["GRID"]["ROWS"] as $k => $arItem)  {
    if ($arItem["DELAY"] != "N" || $arItem["CAN_BUY"] != "Y") {
        continue;
    }
	if (strlen($arItem["PREVIEW_PICTURE_SRC"]) > 0):
		$url = $arItem["PREVIEW_PICTURE_SRC"];
	elseif (strlen($arItem["DETAIL_PICTURE_SRC"]) > 0):
		$url = $arItem["DETAIL_PICTURE_SRC"];
	else:
		$url = $templateFolder."/images/no_photo.png";
	endif;

    //
}


/*

-------------------------------------------------------------------

(примерный разбор того, что происходит в шаблоне, как структурированы данные в arResult)

1. перебор $arResult["GRID"]["ROWS"]

basket_items.php
        if ($arItem["DELAY"] == "N" && $arItem["CAN_BUY"] == "Y"):

basket_items_delayed.php
        if ($arItem["DELAY"] == "Y" && $arItem["CAN_BUY"] == "Y"):

basket_items_not_available.php
        if (isset($arItem["NOT_AVAILABLE"]) && $arItem["NOT_AVAILABLE"] == true):

basket_items_subscribed.php
	   if ($arItem["CAN_BUY"] == "N" && $arItem["SUBSCRIBE"] == "Y"):

2. перебор $arResult["ITEMS"]

GetMessage("SALE_OTLOG_TITLE")
basket_items_delay.php
    foreach($arResult["ITEMS"]["DelDelCanBuy"] as $arBasketItems)

GetMessage("SALE_UNAVAIL_TITLE")
basket_items_notavail.php
    foreach($arResult["ITEMS"]["nAnCanBuy"] as $arBasketItems)

GetMessage("SALE_NOTIFY_TITLE")
basket_items_subscribe.php
    foreach($arResult["ITEMS"]["ProdSubscribe"] as $arBasketItems)

-------------------------------------------------------------------


*/



}
else
{
	ShowError($arResult['ERROR_MESSAGE']);
}
