<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main;
use Bitrix\Main\IO;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\RobotsFile;
use Bitrix\Seo\SitemapIblock;
use Bitrix\Seo\SitemapTable;
use Bitrix\Seo\SitemapIndex;
use Bitrix\Seo\SitemapRuntime;
use Bitrix\Seo\SitemapRuntimeTable;

function node($parent, $tag, $text='', $attrs='')
{
    global $xml;
    $el = $parent->appendChild($xml->createElement($tag));
    if ($text) {
    	$el->appendChild($xml->createTextNode($text));
    }
    if ($attrs) {
        foreach ($attrs as $name => $value) {
            $attr = $xml->createAttribute($name);
            $attr->value = $value;
            $el->appendChild($attr);
        }
    }
    return $el;
}

function addUrls(&$urls, $fromMenu)
{
    foreach ($fromMenu as $k => $v) {
        if (strlen($v[1]) > 1) {
        	$urls [preg_replace('~^/~i', '', $v[1])]= 0.9;
        }
        if (count($v[2])) {
        	addUrls($urls, $v[2]);
        }
    }
}

function addElements(&$urls, $iblockId, $weight=false, $skipIds=false)
{
    if (!$weight) {
    	$weight = 0.8;
    }
    if (ONLY_SECTION && $iblockId != ONLY_SECTION) {
        return ;
    }
    $elements = listElements($iblockId, false, '', false, '', $cash=false);
    foreach ($elements as $k => $v) {
        if (!$v['DETAIL_PAGE_URL']) {
            continue;
        }
        if ($skipIds && in_array($v['ID'], $skipIds)) {
            continue;
        }
    	$urls [$v['DETAIL_PAGE_URL']]= 0.8;
    }
}

function addVariants($add, &$variants)
{
    foreach ($add as $k1 => $v1) {
        if (!$v1) {
        	unset($add[$k1]);
            continue;
        }
        if (is_array($v1)) {
        	$add [$k1] = array_shift($v1);
        }
    }
    $add = implode('/', $add);
    if (!$add) {
        return ;
    }
    $variants [$add]= $add;
}

// Собрать все воможные варианты из трех свойств в разделе Гайки
function allVariantsSmart($sectionId)
{
    $elements = listElements(17, $props=1, ['ACTIVE' => 'Y', 'SECTION_ID' => $sectionId, 'INCLUDE_SUBSECTIONS' => 'Y'], false, '', $cash=0);
    $variants = [];
    foreach ($elements as $k => $v) {
    	$props = simpleProps($v);
        $p = [$props['STANDART'], $props['DIAMETR'], $props['MATERIAL']];
        $add = [];
        foreach ($p as $x) {
        	$add []= [$x];
        }
        $add []= $p;
        $add []= [$props['STANDART'], $props['DIAMETR']];
        $add []= [$props['STANDART'], $props['MATERIAL']];
        $add []= [$props['DIAMETR'], $props['MATERIAL']];
        foreach ($add as $v1) {
        	addVariants($v1, $variants);
        }
    }
    return $variants;
}

$ID = 1;

$dbSitemap = SitemapTable::getById($ID);
$arSitemap = $dbSitemap->fetch();

$dbSite = SiteTable::getByPrimary($arSitemap['SITE_ID']);
$arSitemap['SITE'] = $dbSite->fetch();

if(!is_array($arSitemap))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	ShowError(Loc::getMessage("SEO_ERROR_SITEMAP_NOT_FOUND"));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
else
{
	$arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);

	$arSitemapSettings = array(
		'SITE_ID' => $arSitemap['SITE_ID'],
		'PROTOCOL' => $arSitemap['SETTINGS']['PROTO'] == 1 ? 'https' : 'http',
		'DOMAIN' => $arSitemap['SETTINGS']['DOMAIN'],
	);
}


function fileslist($arSitemap, $dir='')
{
    static $robots;
    if (!isset($robots)) {
        preg_match_all('~Disallow: (.*?)/[\r\n]~mi', file_get_contents('robots.txt'), $a);
        $robots = array_unique($a[1]);
    }
    $a = scandir($dir);
    $files = [];
    foreach ($a as $k => $v) {
        if ($v == '.' || $v == '..' || $v == 'bitrix' || $v == 'ajax' || $v == 'desktop_app' || strpos($v, '_') === 0) {
            continue;
        }
        $path = $dir.'/'.$v;
        if (!is_dir($path) || !file_exists($path.'/index.php') || in_array($path, $robots)) {
            continue;
        }
        $dirKey = ($dir == '.' ? '' : $dir).'/'.$v;


        if (is_dir($path)) {
    		if(!isset($arSitemap['SETTINGS']['DIR'][$dirKey]) || $arSitemap['SETTINGS']['DIR'][$dirKey] == 'Y')
    		{
    			$files []= substr($path, 1);
                $files = array_merge($files, fileslist($arSitemap, $path));
    		}
        } else {
    		if(!isset($arSitemap['SETTINGS']['FILE'][$dirKey]) || $arSitemap['SETTINGS']['FILE'][$dirKey] == 'Y')
    		{
    			if(preg_match($arSitemap['SETTINGS']['FILE_MASK_REGEXP'], $v))
    			{
                    //$files []= $path;
    			}
    		}
        }

    }
    return $files;
}

$data = glob('sitemap*.xml');
foreach ($data as $k => $v) {
	unlink($v);
}

$urls = [
    '/' => 1
];

define('HOST', 'https://'.$_SERVER['HTTP_HOST'].'/');

$arDirList = fileslist($arSitemap, '.');

foreach ($arDirList as $k => $v) {
    $urls [$v.'/'] = 1;
}


foreach ($arSitemap['SETTINGS']['IBLOCK_ACTIVE'] as $iblock => $v) {
    if ($arSitemap['SETTINGS']['IBLOCK_SECTION'][$iblock] == 'Y') {
        $elements = listSections($iblock);
        $props = [];
        $propsDouble = [];
        foreach ($elements as $el) {
            $urls [$el['SECTION_PAGE_URL']] = 1;
        }
    }
    if ($arSitemap['SETTINGS']['IBLOCK_ELEMENT'][$iblock] == 'Y') {
        $elements = listElements($iblock, $props=0, ['ACTIVE' => 'Y'], false, '', $cash=0);
        $props = [];
        $propsDouble = [];
        foreach ($elements as $el) {
            $urls [$el['DETAIL_PAGE_URL']] = 1;
        }
    }
}

$variants = allVariantsSmart(124);
foreach ($variants as $k => $v) {
    $urls ['/catalog/gayki/'.str_replace('_', '-', $v).'/'] = 1;
}


global $xml;
$xml = new DOMDocument('1.0', 'utf-8');

$urlset = node($xml, 'urlset', '', [
    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
    'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
    'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
]);

$exclude = explode("\n", file_get_contents('exp_sitemap_noindex.txt'));

$doubles = [];
foreach ($urls as $url => $p) {
    $url = preg_replace('~^/~i', '', $url);
    $url = trim($url);
    if (in_array($url, $exclude)) {
        continue;
    }
    if (in_array($url, $doubles)) {
        continue;
    }
    $doubles []= $url;
    $sitemap = node($urlset, 'url');
    node($sitemap, 'loc', HOST.$url);
    // node($sitemap, 'lastmod', date('c'));
    node($sitemap, 'changefreq', 'weekly');
    node($sitemap, 'priority', $p);
}



header('Content-Type: text/xml; charset=utf-8');
$xml->formatOutput = true;
echo $xml->saveXML($title);

