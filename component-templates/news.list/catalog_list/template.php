<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

          <div class="list_offer flex flex-wrap flex-between">
<?php
$banks = listElements(4, $withProps=true, $arrFilter='', $arNavStartParams=false, $sort='');
foreach($arResult["ITEMS"] as $key => $arItem) {
    $props = simpleProps($arItem);

    $bank = $banks[$props['BANK']];


?>
            <div class="item border_block">
              <div class="header_offer flex flex-between flex-vcenter">
                <div class="title"><?=$bank['NAME']?></div>
                <div class="bank_logo right flex flex-vcenter flex-end">
                  <img src="<?=Vb::getIcon($props['BANK'])?>" alt="" class="bank_logo_img">
                </div>
              </div>
              <div class="body_offer">
                <div class="list flex flex-wrap flex-between">
                  <div class="item">
                    <div class="name subcolor">Открытие счета:</div>
                    <div class="medium"><?=Vb::prop($arItem, 'ACCOUNT_OPEN')?></div>
                  </div>
                  <div class="item">
                    <div class="name subcolor">Ведение счета:</div>
                    <div class="medium"><?=Vb::prop($arItem, 'BANKING_SERVICE')?></div>
                  </div>
                  <div class="item">
                    <div class="name subcolor">Стоимость платежки:</div>
                    <div class="medium">от <?=Vb::prop($arItem, 'PAYMENT', ['numeric' => 1])?></div>
                  </div>
                  <div class="item"></div>
                </div>
              </div>
              <div class="footer_offer flex flex-vcenter flex-between">
                <?php include dirname(__FILE__).'/icons.php'; ?>
                <div class="btn_block">
                  <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="btn small" data-mob="Оформить">Оформить счет</a>
                </div>
              </div>
            </div>
<?php
}
?>

          </div>