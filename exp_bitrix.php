<?php
define('NEED_AUTH', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('iblock');

if (file_exists('local/templates')) {
	define('DIR_TEMPLATES', 'local/templates');
} else {
	define('DIR_TEMPLATES', 'bitrix/templates');
}





// ---------------------------------------------------------------------------------------------------------------------

// Функции

function components()
{
    return scandirx('bitrix/components/bitrix');
}
function templates()
{
    return scandirx(DIR_TEMPLATES);
}
function scandirx($dir)
{
    $components = [];
    $a = scandir($dir);
    foreach ($a as $k => $v) {
        if ($v == '.' || $v == '..') {
            continue;
        }
        $components []= $v;
    }
    return $components;
}

function selector($data, $title, $attrs, $selected)
{
    $content = '<select'.$attrs.'>';
    if (count($data) > 1) {
    	$content .= '<option value="">'.$title.'</option>';
    }
    foreach ($data as $k => $v) {
        $add = '';
        if ($v == $selected) {
        	$add = ' selected';
        }
    	$content .= ' <option'.$add.'>'.$v.'</option>';
    }
    $content .= '</select>';
    return $content;
}
function copyFolder($from, $to, $skip=[])
{
    if (!file_exists($to)) {
        //echo "\n".' mkdir '.$to.'';
        mkdir($to, 0750);
    }
    $a = scandir($from);
    foreach ($a as $k => $v) {
        if ($v == '.' || $v == '..') {
            continue;
        }
        $copyFromPath = $from .'/'.$v;
        $copyToPath = $to .'/'.$v;
        if (file_exists($copyToPath)) {
            continue;
        }
        if (in_array($v, $skip)) {
            continue;
        }
        if (is_dir($copyFromPath)) {
        	copyFolder($copyFromPath, $copyToPath);
        } else {
            copy($copyFromPath, $copyToPath);
            //echo "\n".'copy '."$copyFromPath, $copyToPath";
        }
    }
}

function printTable($offersData, $opts=[])
{
    echo '
    <style type="text/css">
    table.tt {empty-cells:show; border-collapse:collapse; margin:10px 0}
    table.tt td {border-bottom:1px solid #ccc; padding: 3px; vertical-align: top; font-size:12px; font-family:Arial;}
    /*table.tt tr:nth-child(odd) {background-color:#eee}*/
    </style>
    <table class="tt">';
    foreach ($offersData as $vals) {
        if (!isset($headersPrinted) && is_array($vals)) {
        	$headersPrinted = 1;
            echo '<tr>';
            foreach ($vals as $k => $v) {
                if ($opts['hsc']) {
                	$k = htmlspecialchars($k);
                }
            	echo '<th>'.$k.'</th>';
            }
            echo '</tr>';
        }
        echo '<tr>';
        if (!is_array($vals)) {
            $vals = [$vals];
        }
        foreach ($vals as $k => $v) {
            if ($opts['hsc']) {
            	$v = htmlspecialchars($v);
            }
        	echo '<td>'.$v.'</td>';
        }

        echo '</tr>';
    }
    echo '</table>';
}

function curlLoad($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

function getApiHelp($url)
{
    $content = curlLoad($url);
    preg_match('~<table class="tnormal".*?</table>\s*<br/>~is', $content, $a);
    $table = iconv('windows-1251', 'utf-8', $a[0]);
    preg_match_all('~<tr[^>]*>.*?</tr>~is', $table, $trs);

    $params = [];
    $group = '';
    foreach ($trs[0] as $key => $tr) {
        if ($key == 0) {
            continue;
        }
        preg_match_all('~<t[dh][^>]*>(.*?)</t[dh]>~is', $tr, $tds);
        $tds = $tds[1];
        $tds = array_map('strip_tags', $tds);
        if (count($tds) == 1) {
        	$group = $tds[0];
            continue;
        }
        list($title, $param, $desc) = $tds;
        $params [$param]= compact('title', 'desc', 'group');
    }
    return $params;
}







// ---------------------------------------------------------------------------------------------------------------------

// Карта инфоблоков

function iblockMap()
{
    global $APPLICATION;
    ?>
    <style type='text/css'>
    .iblock:first-child h2 {margin-top:0px;}
    /*.iblock {width:49%; margin-right:1%; float:left;}*/
    .iblock h3 {margin:0 0 5px;}
    h2 span, h3 span, span.id, span.id a, h2 a.bitrix, h3 a.bitrix {font-size:12px; color:#ccc}
    /*.iblock .opts {background-color:#eee; margin:10px 0;}*/
    a.bitrix, h2 a.bitrix, h3 a.bitrix {color:#ccc; text-decoration:none; font-weight:normal;}
    h3:hover a.bitrix {color:red}
    </style>

<hr />

<div class="row">
    <div class="col-md-4">

    <?php
    $list = CIBlockType::GetList($arSort=array('NAME' => 'ASC'), $arFilter=array());
    $existTypes = array();
    while ($type = $list->Fetch()) {
        if (in_array($type['ID'], $existTypes)) {
            continue;
        }
        $existTypes []= $type['ID'];
        $title = CIBlockType::GetByIDLang($type['ID'], LANG);
        echo '<div class="iblock"><h2 title="Тип инфоблока">'.$title['NAME'].' <span>'.$type['ID'].'</span> <a href="/bitrix/admin/iblock_admin.php?type='.$type['ID'].'" title="Адмика - список инфоблоков" class="bitrix">list</a> <a href="/bitrix/admin/iblock_type_edit.php?ID='.$type['ID'].'" title="Адмика - редактировать тип" class="bitrix">e</a></h2>';
        $iblocks = GetIBlockList($type['ID']);
        $countIblocks = 0;
        while($iblock = $iblocks->GetNext()) {
            $countIblocks ++;
            $url = $APPLICATION->GetCurPageParam('iblock='.$iblock['ID'], ['iblock']);
            //echo '<pre>'; print_r($iblock); echo '</pre>'; exit;
            echo '<h3 title="Инфоблок ID='.$iblock['ID'].' CODE='.$iblock['CODE'].'">
            <a href="'.$url.'">'.$iblock['NAME'].'</a>
            <a class="bitrix" href="'.$iblock['LIST_PAGE_URL'].'" target="_blank">На сайте</a>
            <a class="bitrix" href="bitrix/admin/iblock_list_admin.php?IBLOCK_ID='.$iblock['ID'].'&type='.$type['ID'].'"  title="Админка - список элементов" target="_blank">Элементы</a>
            <a class="bitrix" title="Админка - редактировать инфоблок" href="/bitrix/admin/iblock_edit.php?type='.$type['ID'].'&ID='.$iblock['ID'].'" target="_blank">Настройки</a></h3>';
/*

*/

        }
        if ( !$countIblocks) {
            echo '<div class="alert alert-danger">Нет ни одного инфоблока этого типа</div>';
        }
        echo '</div>';
    }
    ?>
    </div>
    <div class="col-md-4">
    <?php
    if ($_GET['iblock']) {
        echo '<h4>Разделы</h4>';
        $items = CIBlockSection::GetList(
            $arOrder=array('left_margin'=>'asc'),
            $arFilter=array('IBLOCK_ID' => $_GET['iblock']),
            $returnElementCount=true,
            $arSelect=array()
        );
        while($section = $items->GetNext()) {
            $add = '';
            if ($section['ACTIVE'] == 'N') {
            	$add = ' style="color:red"';
            }
            $adminUrl = '/bitrix/admin/iblock_section_edit.php?IBLOCK_ID='.$_GET['iblock'].'&type='.$type['ID'].'&ID='.$section['ID'].'&lang=ru&find_section_section=0';
            echo '<div>'.str_repeat('--', $section['DEPTH_LEVEL']).'
            <a '.$add.' href="'.$section['SECTION_PAGE_URL'].'" target="_blank" title="ID='.$section['ID'].' CODE='.$section['CODE'].'">'.$section['NAME'].'</a>
            <span class="id"><a href="'.$adminUrl.'" target="_blank" title="Админка - редактировать раздел">'.$section['ELEMENT_CNT'].'</a></span>
            </div>';
        }


    }
    ?>
    </div>
    <div class="col-md-4">
    <?php
    if ($_GET['iblock']) {
        echo '<h4><a target="_blank" href="/bitrix/admin/iblock_property_admin.php?IBLOCK_ID='.$_GET['iblock'].'" title="Список свойств в админке">Свойства</a></h4>';

        $properties = CIBlockProperty::GetList(
            Array('sort'=>'asc'),
            Array('ACTIVE'=>'Y', 'IBLOCK_ID' => $_GET['iblock'])
        );
        while ($prop = $properties->GetNext()) {
            $url = '/bitrix/admin/iblock_edit_property.php?ID='.$prop['ID'].'&lang=ru&IBLOCK_ID='.$_GET['iblock'].'&admin=N';
            echo '<div>- '.$prop['NAME'].'
            <span class="id"><a title="Админка - редактировать свойство" href="'.$url.'" target="_blank">'.$prop['ID'].'</a> '.$prop['CODE'].'</span>
            <span style="color:blue">'.$prop['PROPERTY_TYPE'].'</span></div>';
        }
        echo '</div>';
    }

    ?>
    </div>
</div>
    <?php
}






// ---------------------------------------------------------------------------------------------------------------------

// Анализ папок
function analiseDir($dir, $level=0)
{
    if ($level > 1) {
        return ;
    }
    $a = scandir($dir);
    $files = array();
    $sort_order = array();
    foreach ($a as $k => $v) {
        if ($v == '.' || $v == '..') {
            continue;
        }
        if (in_array($v, array('bitrix', 'images', 'cgi-bin', '.htaccess'))) {
            continue;
        }
        $path = $dir != '.' ? $dir.'/'.$v : $v;
        $files []= array(
            'is_dir' => is_dir($path),
            'name' => $v,
            'path' => $path
        );
        $sort_order []= is_dir($path) .' - '. $v;
    }
    array_multisort($sort_order, SORT_DESC, SORT_NUMERIC, $files);

    $content = '';
    $isFirstFile = 0;
    foreach ($files as $k => $v) {
        $offset = str_repeat('--', $level);
        if ($v['is_dir']) {
            $dirContent = analiseDir($v['path'], $level+1);
            if (!$dirContent) {
                continue;
            }
        	$content .= ' <b style="background-color:#eee;">'.$v['name'].'</b><br />';
            $content .= $dirContent;
        } else {
            if ($isFirstFile === 1) {
            	$isFirstFile = false;
            }
            if ($isFirstFile === 0) {
            	$isFirstFile = 1;
            }
            if ($isFirstFile && $level == 0) {
            	$content .= '<hr />';
            }
            $style = $title = '';
            $fileInfo = $offset . $v['name'].'<br />';
            if ($v['name'] == '.section.php') {
                $style = 'font-size:11px; color:blue';
                continue;
                // $content .=  '<pre>'.htmlspecialchars(file_get_contents($v['path'])).'</pre>';

            } elseif (strpos($v['name'], 'sect') !== false || strpos($v['name'], 'menu') !== false) {
                $style = 'color:green';
                $title = 'Секция или меню';
            } elseif (strpos($v['name'], 'php'))  {
                $fileContent = file_get_contents($v['path']);
                preg_match_all('~\$APPLICATION->IncludeComponent\(\s*[\'"](.*?)[\'"],\s*[\'"](.*?)[\'"]~i', $fileContent, $inc);
                if ($v['name'] == 'index.php' && !$inc[1]) {
                    continue;
                }
                foreach ($inc[1] as $k => $component) {
                    if ($component == 'bitrix:catalog') {
                        $component = '<a href="?catalog='.$v['path'].'">'.$component.'</a>';
                    }
                	$fileInfo .= '<div style="color:red">---- IncludeComponent <b>'.$component.'</b> / '.$inc[2][$k].'</div>';
                }
            }
            if ($style) {
            	$fileInfo = '<span style="'.$style.'" title="'.$title.'">'.$fileInfo.'</span>';
            }
            $content .= $fileInfo;
        }
    }
    return $content;
}







// ---------------------------------------------------------------------------------------------------------------------

// Анализ каталога, api

function getApiCatalog()
{
    $url = 'https://dev.1c-bitrix.ru/user_help/components/content/catalog/catalog.php';
    $catalogApi = getApiHelp($url);
    $extra = [
        'AJAX_OPTION_ADDITIONAL' => [
            'title' => 'Этим параметром можно повлиять на то, чтобы ID компонентов были разные, если
            вызывается один компонент несколько раз на одной странице.',
            'desc' => 'Например, = $item[\'ID\']'
        ]
    ];
    foreach ($extra as $k => $v) {
    	$catalogApi [$k]= $v;
    }
    return $catalogApi;
}

function getComponentParams($filepath)
{
    $fileContent = file_get_contents($filepath);
    preg_match('~\$APPLICATION->IncludeComponent\(\s*[\'"](.*?)[\'"],\s*[\'"](.*?)[\'"],(.*?)\)\s*(;|\?)~is', $fileContent, $inc);
    $array = preg_replace('~[^\)]+$~i', '', $inc[3]);
    // echo '<pre>'; echo $array; echo '</pre>';

    eval('$params = '.$array.';');
    return $params;
}

function catalogAnalise($filepath)
{
    $catalogApi = getApiCatalog();

    $params = getComponentParams($filepath);

    ksort($params);

    $output = [];
    foreach ($params as $param => $values) {
        $api = $catalogApi[$param];
        if ($api) {
        	$param = '<span style="color:green">'.$param.'</span>';
        } else {
        	$param = '<span style="color:red; font-weight:bold;">'.$param.'</span>';
        }
        if (is_scalar($values)) {
        	$valuesHtml = $values;
            $valuesHtml = str_replace(',', ', ', $valuesHtml);
            $valuesHtml = wordwrap($valuesHtml, 50, '<br />', true);
        } else {
            $valuesHtml = '<a href="#" style="text-decoration:none;" onclick="$(this).next().slideToggle(); return false;">array <b>('.count ($values).'</b>)</a> <div style="display:none;">'.print_r($values, 1).'</div>';
        }
        $valuesHtml = '<span style="color:#aaa">'.$valuesHtml.'</span>';
        $row = [
            $param,
            '<span title="'.htmlspecialchars($api['desc']).'">'.$api['title'].'</span>',
            $valuesHtml,
        ];
    	$output []= $row;
    }

    printTable($output);

    exit;
}





// ---------------------------------------------------------------------------------------------------------------------

// Поиск по ИД

if ($_POST['findById']) {
    $ID = (int)$_POST['findById'];
    $res = CIBlockElement::GetByID($ID);
    $el = $res->GetNext();

    if ($el) {
        $iblock = CIBlock::GetById($el['IBLOCK_ID'])->Fetch();
        $url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$el['IBLOCK_ID'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$ID.'&lang=ru&find_section_section='.$el['IBLOCK_SECTION_ID'].'&WF=Y';
    } else {

        $res = CIBlockSection::GetByID($ID);
        $el = $res->GetNext();
        if (!$el) {
        	echo 'showError("Не нашел ничего");';
            return ;
        }

        $iblock = CIBlock::GetById($sect['IBLOCK_ID'])->Fetch();
        $url = '/bitrix/admin/iblock_section_edit.php?IBLOCK_ID='.$el['IBLOCK_ID'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$ID.'&lang=ru&find_section_section='.$ID.'';
    }

    echo ' console.log('.json_encode($el).');';
    echo ' window.open("'.$url.'");';
    echo ' $("#show-content").html("<a href=\"'.$url.'\" target=\"_blank\">'.htmlspecialchars($el['NAME']).'</a>");';
	exit;
}





// ---------------------------------------------------------------------------------------------------------------------

// Поиск по инфоблокам

if ($_POST['action'] == 'prop-list') {

    echo '<h3>Поиск по полям</h3>';
    $fields = ['ID', 'NAME'];
    foreach ($fields as $k => $v) {
        echo '
  <div class="form-group">
    <label for="field-'.$v.'" class="col-sm-4 control-label">'.$v.'</label>
    <div class="col-sm-8">
      <input type="text" class="form-control" name="FIELD['.$v.']" id="field-'.$v.'" placeholder="'.$v.'">
    </div>
  </div>
        ';
    }


    echo '<h3>Поиск по свойствам</h3>';
    $properties = CIBlockProperty::GetList(
        Array(),
        Array("ACTIVE"=>"Y", "IBLOCK_ID" => $_POST['iblock_id'])
    );
    $ids = array();
    while ($prop = $properties->GetNext())
    {
        echo '
  <div class="form-group">
    <label for="prop'.$prop["ID"].'" class="col-sm-4 control-label">'.$prop["NAME"].'</label>
    <div class="col-sm-8">
      <input type="text" class="form-control" name="PROP['.$prop["CODE"].']" id="prop'.$prop["ID"].'" placeholder="'.$prop["NAME"].'">
    </div>
  </div>
        ';
    }
    exit;
}


if ($_POST['action'] == 'search-results') {

    $arFilter = [
        'IBLOCK_ID' => $_POST['iblock_id']
    ];
    foreach ($_POST['PROP'] as $k => $v) {
        if ($v) {
            $arFilter ['PROPERTY_'.$k] = $v;
        }
    }
    foreach ($_POST['FIELD'] as $k => $v) {
        if ($v) {
            $arFilter [$k] = $v;
        }
    }

    $iblock = CIBlock::GetById($_POST['iblock_id'])->Fetch();

    echo '<b class="subtitle">Результаты поиска</b>';

    $res = CIBlockElement::GetList(
        Array(),
        $arFilter,
        false,
        Array(),
        $arSelect=Array()
    );
    ;
    $countAll = 0;
    while ($ob = $res->GetNextElement()) {
        $el = $ob->getFields();
        $countAll ++;
        if ($el['ACTIVE'] == 'E') {
        	$act = ' <span class="bg-danger">выключен</span>';
        } else {
        	$act = ' <span class="bg-success">активен</span>';
        }
        echo '<div>'.$el['ID'].' '.$act.' <a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$_POST['iblock_id'].'&type='.$iblock['IBLOCK_TYPE_ID'].'&ID='.$el['ID'].'&lang=ru"><i class="glyphicon glyphicon-pencil"></i></a> <a href="'.$el['DETAIL_PAGE_URL'].'">'.$el['NAME'].'</a></div>';
    }

    if (!$countAll) {
        echo ' <div class="alert alert-danger">В базе не найдено</div>';
    }
    exit;
}





// ---------------------------------------------------------------------------------------------------------------------

// Копирование компонента

// Получение списка шаблонов компонента
if ($_POST['action'] == 'ct-list') {
    $a = scandirx('bitrix/components/bitrix/'.$_POST['component'].'/templates');
    foreach ($a as $k => $v) {
        echo '<option>'.$v.'</option>';
    }
	exit;
}

// Получение списка шаблонов компонента
if ($_POST['action'] == 'ct-files') {
	$from = 'bitrix/components/bitrix/'.$_POST['component'].'/templates/'.$_POST['component-template'].'/';
    $a = scandirx($from);
    foreach ($a as $k => $v) {
        $path = $from.'/'.$v;
        if (is_dir($path)) {
        	$v = '<span style="font-weight:bold;">'.$v.'</span>';
        }
        echo '<div>'.$v.'</div>';
    }
	exit;
}

// Непосредственно копирование компонента
if ($_POST['component']) {
	$from = 'bitrix/components/bitrix/'.$_POST['component'].'/templates/'.$_POST['component-template'];
	$to = DIR_TEMPLATES.'/'.$_POST['site-template'].'/components/bitrix/'.$_POST['component'];
    if (!file_exists($to)) {
        mkdir($to);
    }
    $to .= '/'.$_POST['component-template'];
    if (file_exists($to)) {
        echo '<div class="alert alert-danger">Папка уже существует "'.$to.'"</div>';
    } else {
        mkdir($to);
        copyFolder($from, $to, explode(',', $_POST['skip']));
        echo 'Скопировал "'.$to.'"';
    }
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <title>EXP Bitrix</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <style type="text/css">
    .subtitle {font-weight:bold; font-size:18px;}
    #ctpl-list {display:none; margin:10px;}
    #error-info {display:none;}
    .loader {
        border: 5px solid #f3f3f3; /* Light grey */
        border-top: 5px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 52px;
        height: 52px;
        animation: spin 2s linear infinite;
        position:absolute;
        top:0; left:10px;
        display:none;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <script type="text/javascript">
    $(document).ready(function(){
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            console.log('ajax error ('+settings.url+')');
           // document.getElementById('errorBlock').innerHTML += 'Ajax error '+settings.url + '<br />';
        });
        $(document).ajaxStart(function() {
            $('.loader').show();
        });
        $(document).ajaxComplete(function() {
            $('.loader').hide();
        });
        $('.nsh').click(function() {
            $(this).next().toggle();
            if ($(this).hasClass('ns-hide')) {
            	$(this).hide()
            }
            return false;
        })
        $('form.auto-submit').submit(function() {
            $.post('', $(this).serialize(), function(data) {
                eval(data)
            });
            return false;
        })
    });
    function showError(txt)
    {
        if (!txt) {
            return ;
        }
        $('#error-info').show().html(txt)
        setTimeout(function() {
            $('#error-info').hide()
        }, 3000);
    }
    </script>
</head><body>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="exp_bitrix.php">Bitrix</a>
    </div>



    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="?analise=1">Анализ папок</a></li>
        <li><a href="?iblockMap=1">Карта инфоблоков</a></li>
        <!-- <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="#">Action</a></li>
            <li><a href="#">Another action</a></li>
            <li><a href="#">Something else here</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#">Separated link</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#">One more separated link</a></li>
          </ul>
        </li> -->
      </ul>
      <form class="navbar-form navbar-left auto-submit">
        <div class="form-group">
          <input type="text" class="form-control input-sm" name="findById" style="width:80px;" placeholder="ID">
        </div>
        <button type="submit" class="btn btn-default btn-sm">Найти</button>
        <span id="show-content"></span>
      </form>
      <!-- <ul class="nav navbar-nav navbar-right">
        <li><a href="#">Link</a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="#">Action</a></li>
            <li><a href="#">Another action</a></li>
            <li><a href="#">Something else here</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="#">Separated link</a></li>
          </ul>
        </li>
      </ul> -->
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<div class="loader"></div>

<div class="container-fluid">

<div class="alert alert-danger" id="error-info"></div>

<form method="post" class="form-inline">
    <?php
    echo selector(components(), 'Выберите компонент', ' required name="component" class="form-control input-sm"', $_POST['component']);
    ?>
    <select required name="component-template" class="form-control input-sm">
        <option value="">Выберите шаблон компонента</option>
    </select>
    <?php
    echo selector(templates(), 'Выберите шаблон сайта', ' required name="site-template" class="form-control input-sm"', $_POST['site-template']);
    ?>
    <input type="text" name="skip" placeholder="Пропустить папки" value="" class="form-control input-sm">
    <input type="submit" value="Копировать" class="btn btn-success btn-sm" />
    <div id="ctpl-list"></div>
</form>
<script type="text/javascript">
function loadExtraData()
{
    var c = $('select[name="component"]').val();
    if (!c) {
        return ;
    }
    $.post('', 'action=ct-list&component='+c, function(data) {
        $('select[name="component-template"]').html(data)
        var ctpl = $('select[name="component-template"]').val();
        $.post('', 'action=ct-files&component='+c+'&component-template='+ctpl, function(data) {
            $('#ctpl-list').show().html(data)
        });
    });
}
$(document).ready(function(){
    $('select[name="component"]').change(loadExtraData)
    loadExtraData()
});
</script>







<?php
if ($_GET['analise']) {
	echo analiseDir('.');
}
if ($_GET['iblockMap']) {
	iblockMap();
}
if ($_GET['catalog']) {
	catalogAnalise($_GET['catalog']);
}
?>

<hr />





<div class="row">
    <div class="col-md-4">

<form method="post" id="search-form" class="form-horizontal">
    <input type="hidden" name="action" value="search-results">
<p><b class="subtitle">Поиск по инфоблокам</b> &nbsp;&nbsp;&nbsp; <a href="#" onclick="loadProps(); return false;"><i class="glyphicon glyphicon-refresh"></i></a> &nbsp;&nbsp;&nbsp;
    <input type="submit" class="btn btn-info" value="Поиск" />
    </p>


    <select name="iblock_id" class="form-control" style="margin-bottom:10px;">
    <?php
    $res = CIBlock::GetList(
        $arSort=Array(),
        $arFilter=Array(
            "CHECK_PERMISSIONS" => "N"
        ), $returnElementsCount=false
    );
    $opts = '<option>Инфоблок</option>';
    while($item = $res->Fetch()) {
        $opts .= '<option value="'.$item['ID'].'">'.$item['NAME'].'</option>';
    }
    echo $opts;
    ?>
    </select>
    <div id="prop-list"></div>

</form>
    </div>
    <div class="col-md-8" id="search-results">



    </div>
</div>


<script type="text/javascript">
function loadProps()
{
    var iblock_id = $('select[name="iblock_id"]').val();
    if (!iblock_id) {
        return ;
    }
    $.post('', 'action=prop-list&iblock_id='+iblock_id, function(data) {
        /*$('select[name="component-template"]').html(data)
        var ctpl = $('select[name="component-template"]').val();
        $.post('', 'action=ct-files&component='+c+'&component-template='+ctpl, function(data) {
            $('#ctpl-list').show().html(data)
        });*/
        $('#prop-list').html(data)
    });
}
$(document).ready(function(){
    $('select[name="iblock_id"]').change(loadProps)
    $('#search-form').submit(function() {
        $.post('', $(this).serialize(), function(data) {
            $('#search-results').html(data)
        });
        return false;
    })
});
</script>


</div>
</body></html>