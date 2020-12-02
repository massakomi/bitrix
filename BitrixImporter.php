<?php


/*
в нем нужно 2 доработки
1) автосоздание свойств, допустим в property_values все складываем. далее собираем, анализируем, собираем списки. определяем какие типы будут и добавляем. а то я половину времени потратил на создание этих новых свойств.
2) чтобы он мог обработать тысячу данных, за несколько запросов (для этого иметь настройку "обновлять" если дата изменения более 6 часов например + быстрее "пролетать" эти записи, чтобы не нагружать очередь.. В общем нужно сразу определить, какие новые, а какие нужно обновить. и за шаг сколько сможет, остальное на следующем шаге точно так же. уже меньше должно быть.)
*/

/*

// Пример сборки output массива для импорта

$output = [];

foreach ($data as $item) {

    $row = [];

    $row ['IBLOCK_ID'] = "";
    $row ['NAME'] = $item['title'];
    $row ['CODE'] = Cutil::translit($row['NAME'], LANGUAGE_ID, $params=array());
    $row ['ACTIVE_FROM'] = date('d.m.Y H:i:s');
    $row ['IBLOCK_SECTION_ID'] = 'alias'; // число или код в базе
    $row ['DETAIL_PICTURE'] = $props['preview'];

    $row ['PROPERTY_VALUES'] = [];
    $row ['PROPERTY_VALUES']['LINK'] = $link;

    foreach ($props as $k => $v) {

        // Массив фотографий в мульти файловое свойство
        $row ['PROPERTY_VALUES']['GALLERY']= $props ['gallery_data']['image_url'];

        // Массив значений для типа полея Список (латинские коды либо рус, поиск везде идет)
        $row ['PROPERTY_VALUES']['STATUS_OPTION'] = $props['status_option'];

        // Строки, числа либо списочные одиночные значения, либо одиночные файлы
        $row ['PROPERTY_VALUES']['HOMESIZE']= $homesize;

    }

    $row ['CATALOG_VALUES'] = [
        'HEIGHT' => (int)$product['attrs']['Высота'],
        'WIDTH' => (int)$product['attrs']['Ширина'],
        'LENGTH' => (int)$product['attrs']['Глубина'],
        'WEIGHT' => (float)str_replace(',', '.', str_replace(',00', '', $product['weight']))*1000
    ];

    // Сборка для автоматическиого заполнения свойств типа Привязка к элементам с автосозданием этих элементов
    if ($props['video_video_end']) {
        $row ['PROPERTY_VALUES']['VIDEO']=  Array(
            'IBLOCK_ID' => 4,
            'NAME' => $row['NAME'],
            'CODE' => Cutil::translit($row['NAME'], LANGUAGE_ID, $params=array()),
            'ACTIVE' => 'Y',

            'DETAIL_PICTURE' => $props['video_cover'],
            'DETAIL_TEXT' => $props['video_detail'],
            'PREVIEW_TEXT' => $props['video_preview'],

            'PROPERTY_VALUES' => [
                'VIDEO' => $props['video_video_end']
            ]
        );
    }

    $output []= $row;
}
*/


/**
 *
 */
class BitrixImporter {

    public function clearLog()
    {
        if ($this->log && file_exists($this->log)) {
            unlink($this->log);
        }
    }

    public $iblockId = false;

    public $searchBy = 'CODE';

    /**
     * Основной запуск процесса импорта из массива в битрикс
     */
	public function go(array $output)
    {
        $this->timeStart = array_sum(explode(' ', microtime()));

        if ($this->log && file_exists($this->log)) {
        	unlink($this->log);
        }

        if (!$this->iblockId) {
            throw new Exception('Не указан iblockId');
        }

        foreach ($output as $index => $row) {
            echo '<div>'.$index.') '.$row['NAME'].' ';

            try {
               	$this->checkItem($row);
                $action = $this->action;
                if ($this->action == 'save') {
                    if ($this->skipExistProcess($row)) {
                        $action = '';
                    }
                }

                if ($action == 'process' || $action == 'save') {
                    $this->processItem($row);
                }
                if ($action == 'save') {
                	$PRODUCT_ID = $this->saveItem($row);
                }
                if ($this->log) {
                	$this->logw(' '.$index.' / '.count($output).' | '.$PRODUCT_ID.' ['.$this->time().']'."\n");
                }
                echo ' <b>OK</b>';
            } catch (Exception $e) {
                echo ' <span style="color:red">'   . $e->getMessage().'</span> ';
            } finally {
                echo '</div>';
            }

            if ($this->onlyFirst) {
                $this->onlyFirst --;
                if (!$this->onlyFirst) {
                	break;
                }
            }
        }
        // code

        echo '<div>Заняло времени: '.$this->time().'</div>';

        if ($this->lastPrint) {
        	echo '<pre>'; print_r($row); echo '</pre>'; exit;
        }
    }

    /**
     * Первый этап обработки элемента - проверка основных полей
     */
    public function checkItem(&$product)
    {
        if (empty($product['NAME'])) {
            throw new Exception('Отсутствует NAME');
        }
        if (empty($product['IBLOCK_ID'])) {
            throw new Exception('Отсутствует IBLOCK_ID');
        }
        if ($product['IBLOCK_SECTION_ID']) {
            if (!is_numeric($product['IBLOCK_SECTION_ID'])) {
                $sec = $this->getSectionByCode($product['IBLOCK_ID'], $product['IBLOCK_SECTION_ID'], $field=null);
                if ($sec) {
                	$product['IBLOCK_SECTION_ID'] = $sec;
                } else {
                    throw new Exception('Не найден раздел по коду "'.$product['IBLOCK_SECTION_ID'].'" в инфоблоке '.$product['IBLOCK_ID']);
                }
            }
        }
        if (!$product['CODE']) {
        	throw new Exception('Отсутствует CODE');
        }
        if (!$product['ACTIVE']) {
        	$product['ACTIVE'] = 'Y';
        }
        foreach ($product as $field => $v) {
            if (!in_array($field, ['ACTIVE', 'NAME', 'CODE', 'ID', 'PROPERTY_VALUES', 'CATALOG_VALUES', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'DETAIL_TEXT', 'PREVIEW_TEXT', 'TIMESTAMP_X', 'ACTIVE_FROM'])) {
            	throw new Exception('Неизвестное поле "'.$field.'" в выборке');
            }
        }
        $props = $this->getProps($product['IBLOCK_ID']);
        if ($product['PROPERTY_VALUES']) {
            foreach ($product['PROPERTY_VALUES'] as $code => &$value) {
                if (!array_key_exists($code, $props)) {
                	throw new Exception('Неизвестное свойство "'.$code.'" в списке');
                }
            }
        }
        if ($product['TIMESTAMP_X']) {
            throw new Exception('Поле TIMESTAMP_X изменить нельзя');
        }
        // todo еще можно проверить, все ли обязательные IS_REQUIRED свойства добавлены
        // соответствие PROPERTY_TYPE типу. например строка и массив. или нужен файл, а там число
    }

    /**
     * Второй этап после проверки - более глубокая обработка элементов плюс еще проверки
     */
	public function processItem(&$product)
    {

        $code = $product['CODE'];
        if (!$code) {
        	throw new Exception('Отсутствует CODE');
        }


        // Преобразование свойств
        $props = $this->getProps($product['IBLOCK_ID']);
        if ($product['PROPERTY_VALUES']) {
            foreach ($product['PROPERTY_VALUES'] as $code => &$value) {
                $opts = $props[$code];
                if (!$value) {
                    continue;
                }

                // Привязка к элементам
                if ($opts['PROPERTY_TYPE'] == 'E') {
                    // Автосоздание привязанных элементов из массива
                    if (is_array($value)) {
                    	$value = $this->getElementIdOrCreate($value, $code);
                    } elseif (!is_numeric($value)) {
                    	throw new Exception('Для свойства "'.$code.'" ожидается либо число либо массив');
                    }
                } else {
                    $shift = false;
                    if (!is_array($value)) {
                        $shift = true;
                        $value = [$value];
                    }
                    foreach ($value as $k => $v) {
                        if ($opts['PROPERTY_TYPE'] == 'F') {
                            $value [$k]= $this->makeFile($v);
                        }
                        if ($opts['PROPERTY_TYPE'] == 'L') {
                            $x = $this->findEnumOrCreate($v, 2, $code);
                            if ($x === false) {
                                if ($_COOKIE['dev']) {
                                	throw new Exception('Не нашел enum значения для "'.$v.'" свойства  "'.$code.'"');
                                }
                            }
                            $value [$k] = $x;
                        }
                        if ($opts['PROPERTY_TYPE'] == 'N') {
                            $v = trim($v);
                            $v = str_replace(',', '.', $v);
                            if ($v && !is_numeric(str_replace('.', '', $v))) {
                            	throw new Exception('Не число "'.$v.'" у "'.$code.'"');
                            }
                            $value [$k] = $v;
                        }
                        if ($opts['PROPERTY_TYPE'] == 'S') {
                            $value [$k] = trim($v);
                        }
                    }
                    if ($shift) {
                    	$value = array_shift($value);
                    }
                }
            }
        }

        $PRODUCT_ID = $element['ID'];

        if ($product ['DETAIL_PICTURE']) {
        	$product ['DETAIL_PICTURE'] = $this->makeFile($product ['DETAIL_PICTURE']);
        }
        if ($product ['PREVIEW_PICTURE']) {
        	$product ['PREVIEW_PICTURE'] = $this->makeFile($product ['PREVIEW_PICTURE']);
        }

	}

    /**
     * Завершающий этап - добавление или обновление продукта
     */
    public function saveItem($product)
    {

        if ($this->searchBy == 'CODE') {
            $code = $product['CODE'];
            $element = $this->getElementByCode($this->iblockId, $code, true);
        }
        else if ($this->searchBy == 'NAME') {
            $name = $product['NAME'];
            $element = $this->getElementByName($this->iblockId, trim($name), true);
        }
        else {
            throw new Exception('Неизвестный searchBy');
        }

        if ($element) {
        	$PRODUCT_ID = $element['ID'];
        }

        // Сохраняем

        $el = new CIBlockElement;
        if ($PRODUCT_ID) {

            echo ' <a href="'.$element['DETAIL_PAGE_URL'].'">exist</a> (<a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$product['IBLOCK_ID'].'&type=catalog&ID='.$PRODUCT_ID.'&lang=ru&find_section_section=0&WF=Y">'.$PRODUCT_ID.'</a>) ';
            $this->logw(' exist!');

            if ($this->onexist == 'update') {
                $this->update($product, $element);
            }

            if ($this->onexist == 'compare') {
                $this->compare($product);
            }

        } else {

            if ($this->onadd == 'add') {
                if ($PRODUCT_ID = $el->Add($product)) {
                    echo ' added ';
                } else {
                    throw new Exception('Ошибка создания '.$el->LAST_ERROR);
                }
            } else {
                echo ' <span style="color:red">skip add</span>';
            }
        }

        return $PRODUCT_ID;
    }


    /**
     * Пропускать ли обработку этого элемента
     */
    public function skipExistProcess($row)
    {
        if (!$row['CODE']) {
            return false;
        }
        $element = $this->getElementByCode(2, $row['CODE'], true);
        // Если код есть и при существовании ничего делать не надо, то можно сразу пропуск делать, без обработки
        if ($this->onexist == '' && $element) {
        	$PRODUCT_ID = $element['ID'];
            echo ' <a href="'.$element['DETAIL_PAGE_URL'].'">EXIST</a> (<a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$product['IBLOCK_ID'].'&type=catalog&ID='.$PRODUCT_ID.'&lang=ru&find_section_section=0&WF=Y">'.$PRODUCT_ID.'</a>) skip!';
            return true;
        }
        // если же нужно делать обновление, но все равно будет прпоуск по дате - тоже можно скипать
        if ($this->onexist == 'update' && $element) {
            if ($this->skipByTimestamp($element)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Пропустить ли обновление этого элемента, из-за недостаточной разницы от времени последнего обновления
     */
    public function skipByTimestamp($element)
    {
        if ($this->updateMoreThen) {
            $lastUpdate = strtotime($element['TIMESTAMP_X']);
            $this->lastUpdateTime = time() - $lastUpdate;
            if ($this->lastUpdateTime < $this->updateMoreThen) {
                echo $txt = ' skip ('.$this->lastUpdateTime.'s) ';
                $this->logw($txt);
                return true;
            }
        }
        return false;
    }

    /**
     * Запуск процесса обновления элемента $element данными из $product
     */
    public function update($product, $element)
    {
        // дата последнего обновления
        if ($this->skipByTimestamp($element)) {
            return ;
        }

        $props = $product['PROPERTY_VALUES'];
        $catalogProps = $product['CATALOG_VALUES'];

        if ($this->updateFields) {
            unset($product['PROPERTY_VALUES'], $product['CATALOG_VALUES']);
            $this->updateFields($product, $element);
        }

        if ($this->updateProps) {
            $this->updateProps($props, $element);
            if ($catalogProps) {
            	$this->updateCatalogProps($catalogProps, $element);
            }
        }
    }

    /**
     * Обновление полей элемента
     */
    public function updateFields($product, $element)
    {
        foreach ($product as $field => $value) {
            // одно поле всегда будем обновлять, чтобы дата обновления менялась
            if ($field == 'ACTIVE_FROM') {
                // но делать это будем только в режиме ограниченного обновления по дате
                if ($this->updateMoreThen) {
                	continue;
                }
            }

            if ($value == $element['~'.$field]) {
            	unset($product[$field]);
            } else {
                if ($field == 'DETAIL_TEXT') {

                }
            }
        }
        if (is_array($this->updateFields)) {
            foreach ($product as $field => $value) {
                if (!in_array($field, $this->updateFields)) {
                	unset($product[$field]);
                }
            }
        }
        if ($product) {
            $el = new CIBlockElement;
            if ($el->Update($element['ID'], $product)) {
            	echo $txt = ' fields ('.implode(',', array_keys($product)).') updated!';
                $this->logw($txt);
            } else {
                throw new Exception('Ошибка обновления '.$el->LAST_ERROR);
            }
        } else {
            echo ' fields не изменились!';
        }
    }

    /**
     * Обновление свойств элемента инфоблока
     */
    public function updateProps($props, $element)
    {
        if ($this->checkEqual) {
        	$props = checkEqual($props, $element);
            if (!count($props)) {
                echo ' props не изменились!';
                return ;
            }
        }
        if (is_array($this->updateProps)) {
            foreach ($props as $k => $v) {
                if (!in_array($k, $this->updateProps)) {
                	unset($props[$k]);
                }
            }
        }
        CIBlockElement::SetPropertyValuesEx($element['ID'], false, $props);
        echo $txt=' props ('.implode(',', array_keys($props)).') updated!';
        $this->logw($txt);
    }

    public function checkEqual($props, $element)
    {
        foreach ($props as $code => $value) {
            $property = $element['PROPERTIES'][$code];
            if (!isset($property)) {
                continue;
            }
            if ($property['PROPERTY_TYPE'] == 'S') {
                if (is_array($value) && count($value) == 0) {
                    $value = '';
                }
            }
            $valueCurrent = $property['~VALUE'];
            $skip = false;

            if (is_array($value) && is_array($valueCurrent)) {
                if ($property['PROPERTY_TYPE'] == 'F') {
                    if ($this->isFilesEq($value, $valueCurrent)) {
                    	$skip = true;
                    }

                } else {
                    /*$log = "\n".'<b>'.$code.'</b> array';
                    $log .= '<pre>'.print_r($value, 1).'</pre>';
                    $log .= '<pre>'.print_r($property, 1).'</pre>';
                    $this->logw($log);*/
                    if (!count(array_diff($value, $valueCurrent))) {
                        $skip = true;
                    }
                }
            } else {
                // Списки надо сравнивать по-другому
                if ($property['PROPERTY_TYPE'] == 'L') {
                    // в xml - простое значение, в базе - массив
                    if (is_array($property['VALUE_ENUM_ID'])) {
                        if ($value == implode(',', $property['VALUE_ENUM_ID'])) {
                            $skip = true;
                        }
                    // оба простых значений
                    } else {
                        if ($value == $property['VALUE_ENUM_ID']) {
                            $skip = true;
                        }
                    }

                } else {
                    if ($value == $valueCurrent) {
                        $skip = true;
                    // в xml - простое значение, в базе - массив
                    } else if (is_array($valueCurrent)) {
                        if ($value == implode(',', $valueCurrent)) {
                            $skip = true;
                        }
                    }
                }
                if (!$skip) {
                    $log = "\n".'<b>'.$code.'</b>';
                    $log .= "\n".'<pre>'.print_r($value, 1).'</pre>';
                    $log .= "\n".'<pre>'.print_r($property['~VALUE'], 1).'</pre>';
                    $this->logw($log);
                }
            }

            if ($skip) {
                // echo '<div style="color:#ccc">'.$code.'</div>';
                unset($props[$code]);
                continue;
            }

            //echo '<br />'.$code.') '.$property['PROPERTY_TYPE'].' val="'.$value.'" valCur="'.$valueCurrent.'"';
            echo '<div>'.$code.') type="'.$property['PROPERTY_TYPE'].'"</div>';
            echo '<pre>'.print_r($value, 1).'</pre>';
            echo '<pre>'.print_r($property, 1).'</pre>';

        }
        return $props;
    }


    public function updateCatalogProps($props, $element)
    {
        $res = CCatalogProduct::Update($element['ID'], $props);
        if ($res) {
            echo ' <span style="color:green">cc ok!</span>';
        } else {
            echo ' <span style="color:red">cc error!</span>';
        }
    }


    /**
     * Сравниваю массивы файлов
     * $values массивы новых файлов с ключами [name] [size] [type] [tmp_name]
     * $valueCurrentIds - ids файлов которые есть в базе сейчас
     */
    function isFilesEq($values, $valueCurrentIds)
    {
        $fileNewNames = [];
        if ($values[0]['name']) {
            foreach ($values as $k => $v) {
                if (!$v['name']) {
                    continue;
                }
            	$fileNewNames []= $v['name'];
            }
        } elseif ($values['name']) {
            $fileNewNames = [$values['name']];
        }


        // Список файлов по IDs
        $fileCurrentNames = $fulls =[];
        $res = CFile::GetList(['TIMESTAMP_X' => 'ASC'], ['@ID' => $valueCurrentIds]);
        while ($v = $res->Fetch()) {
            $fileCurrentNames []= $v['ORIGINAL_NAME'];
            $fulls [] = $v;
        }

        // Если все совсем разное - то сразу false
        if (count($fileNewNames) != count($fileCurrentNames)) {
            return false;
        }

        $eq = !count(array_diff($fileNewNames, $fileCurrentNames));

        if (!$eq && $this->showFilesEqFaults) {
            echo '<hr />';
            echo '<h2>Новые полные</h2>';
            echo '<pre>'; print_r($values); echo '</pre>';
            echo '<h2>Новые список</h2>';
            echo '<pre>'; print_r($fileNewNames); echo '</pre>';
            echo '<h2>Текущие список</h2>';
            echo '<pre>'; print_r($fileCurrentNames); echo '</pre>';
            echo '<h2>Текущие полные</h2>';
            echo '<pre>'; print_r($fulls); echo '</pre>';
            echo '<hr />';
        }
        return $eq;
    }

    /**
     * Сравнение
     */
    public function compare($product)
    {

        $code = $product['CODE'];
        $productOld = $this->getElementByCode(2, $code, true);

        $info = '';
        foreach ($product as $key => $value) {

            // Сравнение свойств
            if ($key == 'PROPERTY_VALUES') {
                foreach ($value as $code => $val) {
                    $property = $productOld['PROPERTIES'][$code];
                    $valOld = $property['VALUE'];
                    // Массивы
                    if (is_array($val) && is_array($valOld)) {
                        if ($property['PROPERTY_TYPE'] == 'F') {
                            // массивы файлов хз как сравнивать

                        } else {
                            // Сравниваем три разных массива свойства "список" с текущим "новым"
                            // Нужно чтобы хотя бы 1 массив совпадал
                            $founded = false;
                            foreach (['VALUE', 'VALUE_XML_ID', 'VALUE_ENUM_ID'] as $param) {
                            	$old = $property[$param];
                                if ($old && !count(array_diff($old, $val))) {
                                	$founded = true;
                                }
                            }
                            if (!$founded) {
                                $info .= '* '.$code.' не совпадает<br />';
                            }
                        }
                    } else {
                        // Списки надо сравнивать по-другому
                        if ($property['PROPERTY_TYPE'] == 'L') {

                        } else {
                            if ($val != $valOld) {
                                $style = $add = '';
                                if (trim($val) == trim($valOld)) {
                                	$style = 'color:#ccc; font-size:12px;';
                                    $add = ' - только пробел!';
                                }
                                $info .= '<span style="'.$style.'">* '.$code.' new "'.$val.'" != old "'.$valOld.'"'.$add.'</span><br />';
                            }
                        }
                    }
                }

            // Сравнение полей
            } else {
                /*if ($key == 'ACTIVE_FROM') {
                	$valueOld = $productOld['DATE_ACTIVE_FROM'];
                } elseif ($key == 'ACTIVE_FROM') {
                	$valueOld = $productOld['DATE_ACTIVE_FROM'];
                } else {
                }*/
                    $valueOld = $productOld[$key];

                // Не могу проверить, изменились ли значения картинок
                if (!in_array($key, ['DETAIL_PICTURE', 'PREVIEW_PICTURE'])) {
                    if ($value != $valueOld) {
                        $info .= $key.' new "'.$value.'" != old "'.$valueOld.'"<br />';
                    }
                } else {
                    if ($valueOld && !$value) {
                    	$info .= $key.' old есть, а нового нет<br />';
                    }
                    if ($value && !$valueOld) {
                    	$info .= $key.' old нет, новое есть<br />';
                    }
                }
            }
        }

        if ($info) {
            echo '<div style="padding-left:10px; margin-left:10px; color:#aaa; border-left:1px solid green;">'.$info.'</div>';
        }
    }

    /**
     * Лог в файл
     */
    function logw($txt)
    {
        if (!$this->log) {
            return ;
        }
        if (!isset($this->logFile)) {
        	$this->logFile = fopen($this->log, 'a+');
            fwrite($this->logFile, "\n".date('Y-m-d H:i:s').' ');
        }
        fwrite($this->logFile, $txt);
    }

    /**
     * Время tms между началом запуска go и текущей точкой
     */
    public function time()
    {
        $timeEnd = array_sum(explode(' ', microtime()));
        $result = round($timeEnd - $this->timeStart, 3);
        return $result;
    }

    /**
     * Создать битрикс-файл либо взять из кеша либо пустой
     */
    function makeFile($path)
    {
        if (!$this->makeFiles) {
            return $path;
        }
        if ($this->makeFiles == 'normal') {
            return CFile::MakeFileArray($path);
        }
        if (!file_exists('cash-img')) {
        	mkdir('cash-img');
        }
        if (!file_exists('cash-img')) {
        	throw new Exception('Ошибка создания cash-img');
        }

       	$name_explode = explode('.', $path);
    	$extension = strtolower($name_explode[count($name_explode) - 1]);

        $local = 'cash-img/'.md5($path).'.'.$extension;
        if (file_exists($local)) {
        	return CFile::MakeFileArray($local);
        } else {
            $array = CFile::MakeFileArray($path);
            if ($array['tmp_name']) {
            	copy($array['tmp_name'], $local);
            }
            return $array;
        }
    }

    /**
     * Найти или содать этот элемент инфоблока
     */
    function getElementIdOrCreate($product, $propCode='')
    {
        static $elements;
        $iblockId = $product['IBLOCK_ID'];
        if (!$iblockId) {
        	throw new Exception('Не указан IBLOCK_ID для свойства "'.$propCode.'"');
        }
        $code = $product['CODE'];
        if (!$code) {
            echo '<pre>'; print_r($product); echo '</pre>';
        	throw new Exception('Не указан CODE для свойства "'.$propCode.'"');
        }
        if (!isset($elements)) {
            $res = CIBlockElement::GetList(Array(), ['IBLOCK_ID' => $iblockId], false);
            $elements = [];
            while ($el = $res->fetch()){
                $elements [$el['CODE']] = $el['ID'];
            }
        }

        if (!$elements[$code]) {
            $el = new CIBlockElement;
            if ($product['DETAIL_PICTURE']) {
            	$product['DETAIL_PICTURE'] = CFile::MakeFileArray($product['DETAIL_PICTURE']);
            }
            if ($product['PREVIEW_PICTURE']) {
            	$product['PREVIEW_PICTURE'] = CFile::MakeFileArray($product['PREVIEW_PICTURE']);
            }
            if (!$PRODUCT_ID = $el->Add($product)) {
                throw new Exception('Ошибка создания элемента (iblock '.$iblockId.') для свойства ('.$el->LAST_ERROR.')');
            }
            $elements[$code] = $PRODUCT_ID;
        }

        return $elements[$code];
    }

    /**
     * Загрузка массива элементов инфоблока со всеми свойствами
     */
    function elementsLoad($iblockId, $arrFilter=[], $propsFilter=[])
    {
        $arFilter = ['IBLOCK_ID' => $iblockId];
        if ($arrFilter) {
            foreach ($arrFilter as $k => $v) {
                $arFilter [$k] = $v;
            }
        }
        $timeStart = array_sum(explode(' ', microtime()));
        $res = CIBlockElement::GetList(Array(), $arFilter, false);
        $data = [];
        while ($el = $res->getNextElement()){
            $fields = $el->getFields();

            $props = $el->GetProperties([], $propsFilter);
            foreach ($props as $code => $propFields) {
                foreach (['VALUE_ENUM_ID', 'PROPERTY_TYPE', 'VALUE', '~VALUE'] as $k) {
                    $fields["PROPERTIES"][$code][$k] = $propFields[$k];
                }
            }
            // echo '<pre>'; print_r($fields); echo '</pre>'; exit;

            $data[$fields["PROPERTIES"]['NUMBER']['~VALUE']] = $fields;
        }
        $this->timeElementsLoad = round(array_sum(explode(' ', microtime())) - $timeStart, 2);
        return $data;
    }

    /**
     * Получение элемента по его коду (кеш static)
     */
    function getElementByCode($iblockId, $code, $withProps=false, $field=null)
    {
        static $cache;
        if (!is_numeric($iblockId)) {
        	throw new Exception('$iblockId не указан или не число');
        }
        if (empty($code)) {
       	    throw new Exception('Пустой $code');
        }
        $gid = $iblockId.'-'.$withProps;
        if (!isset($cache[$gid])) {
            $cache[$gid] = $this->elementsLoad($iblockId);
            echo '<div>Время загрузки элементов: '.$this->timeElementsLoad.'</div>';
        }
        if ($field) {
        	return $cache[$gid][$code][$field];
        } else {
            return $cache[$gid][$code];
        }
    }

    /**
     * Получение элемента по его имени (кеш static)
     */
    function getElementByName($iblockId, $name, $withProps=false, $field=null)
    {
        static $cache;
        if (!is_numeric($iblockId)) {
        	throw new Exception('$iblockId не указан или не число');
        }
        if (empty($name)) {
       	    throw new Exception('Пустой $name');
        }
        $gid = $iblockId.'-'.$withProps;
        if (!isset($cache[$gid])) {
            $cache[$gid] = $this->elementsLoad($iblockId, 'NAME');
            echo '<div>Время загрузки элементов: '.$this->timeElementsLoad.'</div>';
        }
        if ($field) {
        	return $cache[$gid][$name][$field];
        } else {
            return $cache[$gid][$name];
        }
    }

    /**
     * Получение раздела по его коду (кеш static)
     */
    function getSectionByCode($iblockId, $code, $field=null)
    {
        static $cache;
        if (!isset($cache [$iblockId])) {
            $res = CIBlockSection::GetList(Array(), ['IBLOCK_ID' => $iblockId], false);
            $cache [$iblockId] = [];
            while ($el = $res->GetNext()){
                $cache [$iblockId] [$el['CODE']] = $el;
            }
        }
        if (!is_numeric($iblockId)) {
        	throw new Exception('$iblockId не указан или не число');
        }
        if (empty($code)) {
       	    throw new Exception('Пустой $code');
        }
        if ($field) {
        	return $cache [$iblockId][$code][$field];
        } else {
            return $cache [$iblockId][$code];
        }
    }

    /**
     * Массив всех свойств инфоблока
     */
    public function getProps($iblockId)
    {
        static $cache;
        if (!isset($cache[$iblockId])) {
            $properties = CIBlockProperty::GetList([], Array("IBLOCK_ID" => $iblockId));
            $cache[$iblockId] = array();
            while ($prop = $properties->fetch()) {
                $cache[$iblockId][$prop['CODE']]= $prop;
            }
        }
        return $cache[$iblockId];
    }

    /**
     *
     */
    function propertyExists($keyBx)
    {
        static $props;
        if (!isset($props)) {
            $props = $this->getProps(2);
        }
        return array_key_exists($keyBx, $props);
    }

    /**
     * Поиск enum значения в инфоблоке по коду свойства
     */
    function findEnum($value, $iblock, $code, $create=false)
    {

        $value = trim($value);
        if ($value == '+') {
        	$value = 'true';
        }
        $statusCodes = $this->enumByCode($iblock, $code);

        if ($statusCodes[$value]) {
            return $statusCodes[$value]['ID'];
        } else {
            foreach ($statusCodes as $k => $v) {
                if ($v['VALUE'] == $value) {
                	return $v['ID'];
                }
                if (mb_strtolower($v['VALUE']) == mb_strtolower($value)) {
                	return $v['ID'];
                }
            }
        }

        if ($create) {
            static $cache;
            if (!isset($cache[$value][$iblock][$code])) {
                $pid = $this->propertyEnumId($iblock, $code);
                if ($pid) {
                    $params = Array(
                       'max_len' => '100',
                       'change_case' => 'L',
                       'replace_space' => '_',
                       'replace_other' => '_',
                       'delete_repeat_replace' => 'true',
                       'use_google' => 'false',
                    );
                    $value = trim($value);
                    $xmlId = Cutil::translit($value, LANGUAGE_ID, $params=array());
                    $pe = new CIBlockPropertyEnum;
                    $cache[$value][$iblock][$code] = $pe->Add(Array(
                        'PROPERTY_ID' => $pid,
                        'VALUE' => $value,
                        'XML_ID' => $xmlId
                    ));
                    $this->logw(' added enum "'.$value.'"['.$xmlId.']');
                }
            }
            return $cache[$value][$iblock][$code];
        }
        return false;
    }

    function findEnumOrCreate($value, $iblock, $code)
    {
        return $this->findEnum($value, $iblock, $code, true);
    }

    function propertyEnumId($iblock, $code)
    {
        $property = $this->propertyById($iblock, $code);
        return $property['ID'];
    }

    function propertyById($iblock, $code)
    {
        static $cache;
        if (isset($cache[$iblock][$code])) {
            return $cache[$iblock][$code];
        }
        $property = CIBlockProperty::GetByID($code, $iblock)->GetNext();
        $cache[$iblock][$code] = $property;
        return $property;
    }

    /**
     * Все enum значения свойства по его коду
     */
    function enumByCode($iblock, $code)
    {
        static $cache;
        if (isset($cache[$iblock][$code])) {
            return $cache[$iblock][$code];
        }
        $property_enums = CIBlockPropertyEnum::GetList([], Array("IBLOCK_ID"=>$iblock, "CODE" => $code));
        $enums = [];
        while($enum_fields = $property_enums->GetNext()) {
            $enums [$enum_fields["XML_ID"]] = $enum_fields; // ID VALUE
        }
        $cache[$iblock][$code] = $enums;
        return $enums;
    }

    // $enum = $bitrixImporter->referenceByCode('b_diametr', $vals['DIAMETR']);
    function referenceByCode($tableName, $code)
    {
        $reference = $this->getReference($tableName);
        $enum = array_search($code, $reference);
        if (!$enum) {
            var_dump($code);
            var_dump($enum);
            echo '<pre>'; print_r($reference); echo '</pre>';
            exit;
        }
        return $enum;
    }

    // Значения справочника, на основе tableName. Если не можете передать tableName, передавайте иблок и код свойства
    // $tableName = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
    function getReference($tableName, $iblockId='', $code='')
    {
        static $cache;
        if (!$tableName) {
            $prop = $this->propertyById($iblockId, $code);
            $tableName = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
        }
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(
            array("filter" => array(
                'TABLE_NAME' => $tableName
            ))
        )->fetch();
        $reference = [];
        if (isset($hlblock['ID'])) {
            $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();
            $res = $entity_data_class::getList();
            while ($item = $res->fetch()) {
                $reference [$item['UF_XML_ID']] = $item['UF_NAME'];
                //$reference [$item['ID']] = $item['UF_NAME'];
            }
        }
        $cache[$tableName] = $reference;
        return $reference;
    }


    /**
     * После создания товаров надо обновить фасетный индекс, если используется
     */
    function fasetUpdate($iblockId)
    {
        CModule::IncludeModule('iblock');
        Bitrix\Iblock\PropertyIndex\Manager::DeleteIndex($iblockId);
        Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($iblockId);

        $index = \Bitrix\Iblock\PropertyIndex\Manager::createIndexer($iblockId);
        $index->startIndex();
        $index->continueIndex(0); // создание без ограничения по времени
        $index->endIndex();
    }

    public static function test($output)
    {

        require 'BitrixImporter.php';
        $bx = new BitrixImporter;

        // Только проверка, проверка с обработкой или полный процесс с добавлением
        $bx->action = 'check';
        $bx->action = 'process';
        $bx->action = 'save';

        // Как искать элементы по CODE NAME
        $bx->searchBy = 'NAME';
        $bx->iblockId = 18;

        // Проверять ли свойства на существование, пропускать уже существующие
        // нужно если тысячи элементов и запускаем на кроне, чтобы быстрее выполнялось
        $bx->checkEqual = false;

        // Режим загрузки файлов  none cache normal
        $bx->makeFiles = 'cache';

        // Что делать если такой элемент уже существует (action должен быть save)
        // - update - обновление данных при обнаружении элемента
        // - compare - вывести сравнение
        $bx->onexist = 'update';

        // Обновлять только те которые старше Х секунд
        $bx->updateMoreThen = 86400; // 86400

        // Что делать если элемент новый
        // - add - добавить (или ничего не делать при пустом значении)
        $bx->onadd = 'add';

        // В случае если элемент существует - обновлять ли свойства и поля. И какие конкретно поля и свойства обновлять
        $bx->updateProps = true;
        $bx->updateFields = true;

        // Файл куда вести лог если нужно
        $bx->log = 'log.txt';


        if ($_COOKIE['dev']) {

            // Обработать только первые X
            $bx->onlyFirst = false;

            // Распечатать последний
            $bx->lastPrint = false;

            // Показывать случаи, когда при обновлении списки файлов отличаются (для отладки)
            $bx->showFilesEqFaults = false;
        }

        $bx->go($output);

        $bx->fasetUpdate(2);
    }
}

