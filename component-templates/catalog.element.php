<?php


// Разбираемся с картинками
// Таким кодом я сначала собираю полный массив картинок, а потом уже их дальше по верстке раскидываю
$imgItem = $actualItem;
if ($arResult['DETAIL_PICTURE']['ID'] && !$actualItem['DETAIL_PICTURE']['ID']) {
	$imgItem = $arResult;
}

$photos = [$imgItem['DETAIL_PICTURE']];
if ($imgItem['DISPLAY_PROPERTIES']['MORE_PHOTO']) {
    foreach ($imgItem['DISPLAY_PROPERTIES']['MORE_PHOTO']['FILE_VALUE'] as $k => $v) {
    	$photos []= $v;
    }
}
foreach ($photos as $k => $img) {
    $img_id = $img['ID'];
    $photos[$k]['p-large'] = CFile::ResizeImageGet($img_id, array('width'=>1280, 'height'=>1280), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
    $photos[$k]['p200'] = CFile::ResizeImageGet($img_id, array('width'=>200, 'height'=>200), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
    $photos[$k]['p400'] = CFile::ResizeImageGet($img_id, array('width'=>400, 'height'=>400), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
    $photos[$k]['p600'] = CFile::ResizeImageGet($img_id, array('width'=>600, 'height'=>600), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
    $photos[$k]['p1200'] = CFile::ResizeImageGet($img_id, array('width'=>1200, 'height'=>1200), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
}




?>


<h1 itemprop="name"><?=$name?></h1>

<div class="alert" style="display: <?=(!$actualItem['CAN_BUY'] ? '' : 'none')?>;">
    <?=$arParams['MESS_NOT_AVAILABLE']?> <!-- Этот предмет в настоящее время недоступен! -->
</div>
<?php if ($showSubscribe && !$actualItem['CAN_BUY']) { ?>
Подписаться на товар, уведомление о поступлении
<?php } ?>



<div itemprop="offers" itemscope="" itemtype="http://schema.org/Offer" class="buybox--inner">
    <meta itemprop="priceCurrency" content="EUR">

    <!-- PRICE текущая цена, BASE_PRICE цена без скидки (если есть скидка), DISCOUNT скидка в рублях (экономия)  -->
    <div class="product--price price--default price--discount">
        <meta itemprop="price" content="<?=$actualItem['ITEM_PRICES'][0]['PRICE']?>">
        <?=$price['PRINT_RATIO_PRICE']?>
        
		<?
		if ($arParams['SHOW_OLD_PRICE'] === 'Y' && $actualItem['ITEM_PRICES'][0]['PRINT_DISCOUNT'] > 0)
		{
			?>
        <div class="product--price-discount">
            <span class="price--discount is--nowrap">
            <?=$price['PRINT_RATIO_BASE_PRICE']?>
            </span>
            <span class="content--discount" id="<?=$itemIds['DISCOUNT_PRICE_ID']?>">
                <span class="price--discount-euro">
                Скидка:
                <? echo $actualItem['ITEM_PRICES'][0]['PRINT_DISCOUNT']; ?>
                </span>
            </span>
        </div>
			<?
		}
		?>
    </div>



    <div class="product--delivery">
        <link itemprop="availability" href="http://schema.org/LimitedAvailability">
        <p class="delivery--information">
            <?php
            if (!$actualItem['CAN_BUY']) {
                echo 'Нет в наличии';
            } else {
                echo 'Есть в наличии';
            }
            ?>

        </p>
    </div>


    <form method="post">
    <input type="hidden" name="sAdd" value="<?=$actualItem['ID']?>">
    <div class="buybox--button-container block-group">
    <?php if ($arParams['USE_PRODUCT_QUANTITY']) { ?>
    <select id="sQuantity" name="sQuantity" class="quantity--select" style="<?=(!$actualItem['CAN_BUY'] ? 'display: none;' : '')?>">
    <?php
    for ($i = $price['MIN_QUANTITY']; $i <= 100; $i ++) {
        echo '<option value="'.$i.'">'.$i.'</option>';
    }
    ?>
    </select>
    <?php } ?>
    <?php
    if ($showAddBtn) { ?>
    <button type="submit" id="<?=$itemIds['BASKET_ACTIONS_ID']?>" style="display: <?=($actualItem['CAN_BUY'] ? '' : 'none')?>;">
         Добавить в корзину
    </button> <?php } ?>
    </div>
    </form>





    <nav class="product--actions">
    <?php if ($arParams['DISPLAY_COMPARE']) { ?>
    <a href="#"  id="<?=$itemIds['COMPARE_LINK']?>"> <?=$arParams['MESS_BTN_COMPARE']?></a>
    <? } ?>
    </nav>

</div>



<?php
$countAll = countElements([
    'IBLOCK_ID' => $iblockId,
    'ACTIVE' => 'Y',
    '=PROPERTY_PRODUCT' => $arResult['ID']
]);
?>

Отзывы всего <?=$countAll?>

Описание
<?=$arResult['PREVIEW_TEXT']?>

<?
if (
	$arResult['PREVIEW_TEXT'] != ''
	&& (
		$arParams['DISPLAY_PREVIEW_TEXT_MODE'] === 'S'
		|| ($arParams['DISPLAY_PREVIEW_TEXT_MODE'] === 'E' && $arResult['DETAIL_TEXT'] == '')
	)
)
{
	echo $arResult['PREVIEW_TEXT_TYPE'] === 'html' ? $arResult['PREVIEW_TEXT'] : '<p>'.$arResult['PREVIEW_TEXT'].'</p>';
}

if ($arResult['DETAIL_TEXT'] != '')
{
	echo $arResult['DETAIL_TEXT_TYPE'] === 'html' ? $arResult['DETAIL_TEXT'] : '<p>'.$arResult['DETAIL_TEXT'].'</p>';
}
?>



<?php
// Свойства
if (!empty($arResult['DISPLAY_PROPERTIES']) || $arResult['SHOW_OFFERS_PROPS'])
{
	?>
    <div class="content--description--left" style="width: 100%; margin: 0; padding: 0;">
		<?
		if (!empty($arResult['DISPLAY_PROPERTIES']))
		{
			?>
        <h2>Спецификации</h2>
        <div class="product--properties panel has--border">
            <table class="product--properties-table">
                <tbody>

				<?
				foreach ($arResult['DISPLAY_PROPERTIES'] as $property)
				{
					?>
                    <tr class="product--properties-row">
                        <td class="product--properties-label is--bold"><?=$property['NAME']?></td>
                        <td class="product--properties-value"><?=(
						is_array($property['DISPLAY_VALUE'])
							? implode(' / ', $property['DISPLAY_VALUE'])
							: $property['DISPLAY_VALUE']
						)?></td>
                    </tr>
					<?
				}
				unset($property);
				?>
                </tbody>
            </table>
        </div>

			<?
		}

		if ($arResult['SHOW_OFFERS_PROPS'])
		{
			?>
			<div class="product-item-detail-properties" id="<?=$itemIds['DISPLAY_PROP_DIV']?>"></div>
			<?
		}
		?>
	</div>
	<?
}
?>




Нижние блоки просто копируются
<meta itemprop="name" content="<?=$name?>" />