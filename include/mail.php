<?php

use Bitrix\Main\Loader;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

foreach ($_POST as $k => $v) {
	$_POST [$k] = htmlspecialchars($v);
}

$errors = $message = [];

$params = [
    'name' => 'Имя',
    'city' => 'Город',
    'region' => 'Регион',
    'email' => 'Электронная почта',
    'phone' => 'Телефон',
    'message' => 'Сообщение',
    'subject' => 'Тема',

    'form_title' => 'Заголовок формы',
    'time' => 'Время звонка',
    'url' => 'Url'
];

foreach ($params as $k => $title) {
    if ($_POST[$k]) {
    	$message []= '<b>'.$title.'</b>: '.$_POST[$k];
    }
}

// Доп инфо
$message []= '<hr />';
$message []= 'IP: '.$_SERVER['REMOTE_ADDR'];


if ($errors) {
    exit(json_encode([
        'error' => implode(', ', $errors)
    ]));
}

// Сохраняем в результаты
Loader::includeModule("iblock");
$product = Array(
    'IBLOCK_ID'         => 6,
    'NAME'              => $_POST['phone'].', '.$_POST['name'].' : '.$_POST['form_title'],

    'PROPERTY_VALUES'   => [
        'FIO' => $_POST['name'],
        'REGION' => $_POST['region'],
        'CITY' => $_POST['city'],
        'EMAIL' => $_POST['email'],
        'PHONE' => $_POST['phone'],
        'SUBJECT' => $_POST['subject'],

        'URL' => $_POST['url'],
        'FORM' => $_POST['form_title'],
        'IP' => $_SERVER['REMOTE_ADDR'],
    ],
    'TIMESTAMP_X'       => date('Y-m-d H:i:s'),
    'ACTIVE'            => 'Y',

    'PREVIEW_TEXT'      => $_POST['message']
);
$el = new CIBlockElement;
if (!$el->Add($product) && isset($_COOKIE['dev'])) {
    exit($el->LAST_ERROR);
}



$message = '<div>'.implode('</div><div>', $message).'</div>';

$arEventFields = [];
foreach ($_POST as $k => $v) {
	$arEventFields [mb_strtoupper($k)] = $v;
}
$arEventFields ['THEME'] = 'Сообщение с формы сайта '.$_SERVER['HTTP_HOST'];
$arEventFields ['CONTENT'] = $message;

$arEventFields ['DEFAULT_EMAIL_FROM'] = 'osa@xx.ru';
if (isset($_COOKIE['dev'])) {
	$arEventFields ['DEFAULT_EMAIL_FROM'] = 'osa@xx.ru';
}
$idMessage = CEvent::Send('FORM_MAIL', SITE_ID, $arEventFields, $duplicate='Y', $template_id='', $files);



