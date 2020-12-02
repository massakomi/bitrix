<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>



          <div class="list_article flex flex-between flex-wrap">
          <?php
          foreach($arResult["ITEMS"] as $arItem) {
            ?>
            <div class="item">
              <div class="image radius_block" style="background-image: url('<?=$arItem["PREVIEW_PICTURE"]["SRC"] ?: $arItem["DETAIL_PICTURE"]["SRC"]?>');"></div>
              <a class="name medium" href="<?echo $arItem["DETAIL_PAGE_URL"]?>"><?echo $arItem["NAME"];?></a>
            </div>
            <?php
          }
          ?>
          </div>

