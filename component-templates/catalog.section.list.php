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
/** @var CBitrixComponent $compo
nent */
$this->setFrameMode(true);

?>
                        <ul class="sidebar--navigation categories--navigation navigation--list is--drop-down is--level0 is--rounded" role="menu">
                        <?php
                        foreach ($arResult['SECTIONS'] as $section) {
                            ?>
                           <li class="navigation--entry has--sub-children" role="menuitem">
                              <a class="navigation--link link--go-forward" href="<?=$section['SECTION_PAGE_URL']?>" data-categoryid="<?=$section['ID']?>" data-fetchurl="" title="<?=$section['NAME']?>">
                              <?=$section['NAME']?><span class="is--icon-right"><i class="icon--arrow-right"></i></span>
                              </a>
                           </li>
                            <?php
                        }
                        ?>
                        </ul>
<?php
