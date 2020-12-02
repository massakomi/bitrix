<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

          <?php
          foreach($arResult["ITEMS"] as $key => $arItem) {
            $tag = 'h2';
            if ($arItem['ID'] == 21) {
              $tag = 'div';
            }
            $row = '<div class="item border_block white_bg question_bg" data-id="'.$arItem['ID'].'">
                <'.$tag.' class="name flex flex-vcenter">
                 '.$arItem["NAME"].'
                  <span class="open"></span>
                </'.$tag.'>
                <div class="detail">
                 '.$arItem["PREVIEW_TEXT"].'
                </div>
              </div>';
            if ($key % 2 == 0) {
               $col1 .= $row;
            } else {
               $col2 .= $row;
            }
          }
          ?>
          <div class="lists flex flex-between flex-wrap">
            <div class="row">
            <?=$col1?>
            </div>
            <div class="row">
            <?=$col2?>
            </div>
          </div>


