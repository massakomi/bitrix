<?php

use MoySklad\Entities\Products\Product;
use MoySklad\Lists\EntityList;
use MoySklad\MoySklad;

use MoySklad\Components\Expand;
use MoySklad\Components\Specs\LinkingSpecs;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Employee;
use MoySklad\Utils\CommonDate;

use MoySklad\Entities\Reports\StockReport;
use MoySklad\Components\Specs\QuerySpecs\Reports\StockReportQuerySpecs;

use MoySklad\Entities\Folders\ProductFolder;

function msFolders($sklad)
{
    $offset = 0;
    $parentIds = [];
    $groups = [];
    while (true) {
        $list = ProductFolder::query($sklad, QuerySpecs::create([
            "offset" => $offset,
        ]))->getList();
        $offset += 100;
        $count = 0;
        foreach ($list as $k => $v) {
            if ($v->fields->pathName) {
            	$path = $v->fields->pathName.'/'.$v->name;
            } else {
            	$path = $v->name;
            }
            $parentIds [$path]= $v->fields->id;
            if ($v->fields->pathName) {
            	$parentId = $parentIds[$v->fields->pathName];
            } else {
            	$parentId = '';
            }
            $groups [$parentId][$v->id]= [
                'name' => $v->name,
                'id' => $v->id,
                'externalCode' => $v->externalCode,
            ];
            $count ++;
        }
        if (!$count) {
        	break;
        }
    }

    return $groups;
}

function msProcessImportFolders($groups, $idParent, &$existSections)
{
    $index = -1;
    foreach ($groups[$idParent] as $id => $group) {
        echo '<br />'.$group['name'];
        $index ++;

        // родительская
        if ($idParent) {
        	$IBLOCK_SECTION_ID = $existSections[$idParent];
        } else {
            $IBLOCK_SECTION_ID = '';
        }


        $bs = new CIBlockSection;
        $arFields = Array(
            "ACTIVE" => 'Y',
            "IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ID,
            "IBLOCK_ID" => 5,
            'XML_ID' => $id,
            "NAME" => $group['name'],
            "SORT" => 500 + $index,
            'CODE' => Cutil::translit($group['name'], LANGUAGE_ID, $params=array())
        );

        if ($existSections[$id]) {
        	$res = $bs->Update($existSections[$id], [
                "NAME" => $group['name'],
            ]);
            unset($existSections[$id]);
            echo ' exist';

        } else {
            echo ' add';
            //echo '<pre>'; print_r($arFields); echo '</pre>';
            //continue;
            $resultId = $bs->Add($arFields);
            if ($resultId) {
                echo ' OK ';
                $existSections[$id] = $resultId;
            } else {
                echo $bs->LAST_ERROR;
                break;
            }
        }
        if ($groups[$id]) {
            //echo ' addParents';
        	msProcessImportFolders($groups, $id, $existSections);
        }
    }
}

function msImportFolders($sklad)
{
    CModule::IncludeModule('iblock');
    $arFilter = Array(
        'IBLOCK_ID' => 5,
        '!XML_ID' => false
    );
    $res = CIBlockSection::GetList(Array('SORT' => 'ASC'), $arFilter, false, $arSelect);
    $existSections = [];
    while ($ob = $res->GetNextElement()){
        $arFields = $ob->GetFields();
        $existSections [$arFields['XML_ID']] = $arFields['ID'];
    }
	$groups = msFolders($sklad);

    msProcessImportFolders($groups, '6f80319d-5c9a-11ea-0a80-03c2000c436f', $existSections);

    if ($existSections) {
        echo '<hr />';
        $arFilter = Array(
            'IBLOCK_ID' => 5,
            'ACTIVE' => 'Y',
            'XML_ID' => array_keys($existSections)
        );
        $res = CIBlockSection::GetList(Array('SORT' => 'ASC'), $arFilter, false, $arSelect);
        while ($ob = $res->GetNextElement()){
            $arFields = $ob->GetFields();
            $el = new CIBlockSection;
            $el->Update($arFields['ID'], array(
                'ACTIVE' => 'N'
            ));
            echo '<br />deactivate '.$arFields['NAME'];
        }
    }

}

