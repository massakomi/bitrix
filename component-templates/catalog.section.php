<?php

$psurl = $GLOBALS["APPLICATION"]->GetCurPageParam(
    'pagesize=xxx',
    ['pagesize']
);
$ssurl = $GLOBALS["APPLICATION"]->GetCurPageParam(
    'sort=xxx',
    ['sort']
);

?>

	<div class="sort_view">
		<div class="filter_by main_btn">Фильтры</div>
		<div class="sort_by">
			<span class="text">Сортировать по:</span>
			<select name="sort" onchange="url='<?=$ssurl?>'; url = url.replace('xxx', this.options[this.selectedIndex].value); location=url" >
<?php
$order = [
    'sort' => 'Умолчанию',
    'price' => 'Цене',
    'views' => 'Популярности',
    'date' => 'Дате'
];
foreach ($order as $k => $v) {
    $add = '';
    if ($_GET['sort'] == $k) {
    	$add = ' selected';
    }
    echo '<option'.$add.' value="'.$k.'">'.$v.'</option>';
}
?>
			</select>
		</div>
		<div class="view_by">
			<span class="text">Показывать по:</span>
			<select name="view" onchange="url='<?=$psurl?>'; url = url.replace('xxx', this.options[this.selectedIndex].value); location=url" >
<?php
for ($i = 15; $i <= 90; $i += 15) {
    $add = '';
    if ($pagesize == $i) {
    	$add = ' selected';
    }
    echo '<option'.$add.'>'.$i.'</option>';
}
?>
			</select>
		</div>
	</div>



        <div class="listing--container">
            <div class="listing" data-ajax-wishlist="true" data-compare-ajax="true">
                <?php
                foreach ($arResult['ITEMS'] as $arItem) {
                    $props = $arItem['PROPERTIES'];
                    $pic = $arItem['DETAIL_PICTURE'];
                    $pic200 = CFile::ResizeImageGet($pic['ID'], array('width'=>200, 'height'=>200), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
                    $pic400 = CFile::ResizeImageGet($pic['ID'], array('width'=>400, 'height'=>400), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];

                    $price = '';
                    if ($arItem['OFFERS']) {
                        $offer = $arItem['OFFERS'][0];
                        foreach ($arItem['OFFERS'] as $k => $v) {
                            if ($v['CAN_BUY']) {
                            	$offer = $v;
                                break;
                            }
                        }
                        $price = $offer['ITEM_PRICES'][0];
                    } else {
                        $price = $arItem['ITEM_PRICES'][0];
                    }
                    ?>

                <div class="product--box box--minimal" data-page-index="1" data-ordernumber="<?=$props['ARTNUMBER']['VALUE']?>" data-category-id="<?=$arItem['IBLOCK_SECTION_ID']?>">
                    <div class="box--content is--rounded">
                        <div class="product--badges">
                        </div>
                        <div class="product--info">
                            <a href="<?=$arItem['DETAIL_PAGE_URL']?>" title="<?=$arItem['DETAIL_PAGE_URL']?>" class="product--image">
                                <span class="image--element">
                                    <span class="image--media">
                                        <img srcset="<?=$pic200?>, <?=$pic400?> 2x" alt="<?=$pic['ALT']?>" title="<?=$pic['TITLE']?>">
                                    </span>
                                </span></a>
                            <div class="product--rating-container">
                            </div>
                            <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="product--title" title="<?=$arItem['NAME']?>"><?=$arItem['NAME']?></a>
                            <?php
                            if ($props['ARTNUMBER']['VALUE']) {
                                echo '<br />Артикул: '.$props['ARTNUMBER']['VALUE'];
                            }
                            if ($price) {
                                ?>
                            <div class="product--price-info">
                                <div class="price--unit">
                                </div>
                                <div class="product--price-outer">
                                <?php
                                if ($price['DISCOUNT'] > 0) {
                                ?>
                                    <div class="product--price">
                                    <span class="price--discount is--nowrap">
                                    <?=$price['PRINT_BASE_PRICE']?>
                                    </span>
                                    <span class="price--default is--nowrap is--discount">
                                    <?=$price['PRINT_PRICE']?>
                                    </span>
                                    </div>
                                <?php
                                } else {
                                ?>
                                    <div class="product--price">
                                        <span class="price--default is--nowrap"><?=$price['PRINT_BASE_PRICE']?></span>
                                    </div>
                                <?php
                                }
                                ?>
                                </div>
                            </div>
                                <?php
                            }
                            ?>

                        </div>
                    </div>
                </div>
                    <?php
                }
                ?>
            </div>
        </div>