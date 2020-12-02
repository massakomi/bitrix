<?php

define('NEED_AUTH', true);

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";

Cmodule::IncludeModule('iblock');
CModule::IncludeModule('highloadblock');


if (array_key_exists('menu', $_GET) || !count($_GET) || $_GET['login'] == 'yes') {
    $content = file_get_contents(__FILE__);
    preg_match_all('~(// .*?)?[\r\n]if \(array_key_exists\(\'(.*?)\', \$_GET\)\) \{~i', $content, $a);
    foreach ($a[1] as $k => $title) {
    	$title = substr($title, 3);
        $alias = $a[2][$k];
        if ($alias == 'menu') {
            continue;
        }
        if (!$title) {
        	$title = $alias;
        }
        echo '<div><a href="?'.$alias.'">'.$title.'</a></div>';
    }
    ?>
    <style type="text/css">
    a {text-decoration:none;}
    a, a:visited {color:#006666}
    </style>
    <?php
}

function getElementByUrl($url)
{
    static $ids;
    if (!isset($ids)) {
        $ids = [];
        $els = listElements(17, $withProps=1, $arrFilter='', $arNavStartParams=false, $sort='');
        foreach ($els as $k => $v) {
            $ids [$v['DETAIL_PAGE_URL']]= $v;
        }
    }
    $url = str_replace('https://'.$_SERVER['HTTP_HOST'], '', $url);
    return $ids[$url];
}

function getSectionByUrl($url)
{
    static $ids;
    if (!isset($ids)) {
        $ids = [];
        $els = listSections(17, $withProps=0, $arrFilter='', $arNavStartParams=false, $sort='');
        foreach ($els as $k => $v) {
            $ids [$v['SECTION_PAGE_URL']]= $v;
        }
    }
    $url = str_replace('https://'.$_SERVER['HTTP_HOST'], '', $url);
    return $ids[$url];
}


if (array_key_exists('metas', $_GET)) {

    // assoc json декодированный массив, с 5 ключами url title desc h1 keywords
    $data = json_decode(file_get_contents('metas.txt'), true);

    foreach ($data as $vals) {
        foreach ($vals as $k => $v) {
            $v = trim($v);
            if (mb_strlen($v) < 2) {
                unset($vals [$k]);
                continue;
            }
        	$vals [$k] = $v;
        }
        $title = $vals['title'];
        $desc = $vals['desc'];
        $keywords = $vals['keywords'];
        $url = $vals['url'];

    	$element = getElementByUrl($url);
        if ($element['ID']) {
            $updateFields = [];
            if ($title) {
                /*$titleSet = preg_replace('~[HН]1~iu', $element['NAME'], $title);
                if ($titleSet == $title) {
                    throw new Exception('==t');
                }*/
                $updateFields ['IPROPERTY_TEMPLATES']['ELEMENT_META_TITLE'] = $title;
            }
            if ($desc) {
                /*$descSet = preg_replace('~[HН]1~iu', $element['NAME'], $desc);
                if ($descSet == $desc) {
                    throw new Exception('==d');
                }*/
                $updateFields ['IPROPERTY_TEMPLATES']['ELEMENT_META_DESCRIPTION'] = $desc;
            }
            if ($keywords) {
                /*$descSet = preg_replace('~[HН]1~iu', $element['NAME'], $desc);
                if ($descSet == $desc) {
                    throw new Exception('==d');
                }*/
                $updateFields ['IPROPERTY_TEMPLATES']['ELEMENT_META_KEYWORDS'] = $keywords;
            }
            if (!$updateFields) {
                continue;
            }
            echo '<br />'.$url;

            //echo '<pre>'; print_r($updateFields); echo '</pre>';
            //continue;

            $bs = new CIBlockElement;
            $x = $bs->Update($element["ID"], $updateFields);
            if ($x) {
                $ipropValues = new Bitrix\Iblock\InheritedProperty\ElementValues(17, $element["ID"]);
                $ipropValues->clearValues();
                echo ' +++ ';
            } else {
                echo ' ---- '.$bs->LAST_ERROR;
            }
        } else {
            $section = getSectionByUrl($url);
            if ($section['ID']) {
                $updateFields = [];
                if ($title) {
                    $updateFields ['IPROPERTY_TEMPLATES']['SECTION_META_TITLE'] = $title;
                }
                if ($desc) {
                    $updateFields ['IPROPERTY_TEMPLATES']['SECTION_META_DESCRIPTION'] = $desc;
                }
                if ($keywords) {
                    $updateFields ['IPROPERTY_TEMPLATES']['SECTION_META_KEYWORDS'] = $keywords;
                }
                if (!$updateFields) {
                    continue;
                }
                echo '<br />'.$url;
                //echo '<pre>'; print_r($updateFields); echo '</pre>';
                //continue;

                $bs = new CIBlockSection;
                $x = $bs->Update($section["ID"], $updateFields);
                if ($x) {
                    $ipropValues = new Bitrix\Iblock\InheritedProperty\SectionValues(17, $section["ID"]);
                    $ipropValues->clearValues();
                    echo ' +++ ';
                } else {
                    echo ' ---- '.$bs->LAST_ERROR;
                }
            } else {
                echo '<hr />NOT FOUND <a target="_blank" href="'.$url.'">'.$url.'</a>';
                unset($vals['url']);
                foreach ($vals as $k => $v) {
                    echo '<div>'.$k.'</div><textarea name="content" style="width:100%; height:50px;">'.$v.'</textarea>';
                }
            }


        }



    }
}

// заполнение ключевых слов для массива урлов (только для элементов реализовано)
if (array_key_exists('keywords', $_GET)) {

    $data = file('keywords.txt');
    foreach ($data as $url) {
        $url = trim($url);
        if (!$url) {
            continue;
        }

    	$element = getElementByUrl($url);
        $updateFields = [];
        $updateFields ['IPROPERTY_TEMPLATES']['ELEMENT_META_KEYWORDS'] = '-';
        $bs = new CIBlockElement;
        $x = $bs->Update($element["ID"], $updateFields);
        if ($x) {
            $ipropValues = new Bitrix\Iblock\InheritedProperty\ElementValues(17, $element["ID"]);
            $ipropValues->clearValues();
            echo ' +++ ';
        } else {
            echo ' ---- '.$bs->LAST_ERROR;
        }
    }
}