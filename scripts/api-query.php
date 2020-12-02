<?php

// Работа с небольшим сторонним Api
class Api {


    //$res = $api->query('/dictionary/tariff');
    //$res = $api->query('/dictionary/city');
    //$res = $api->query('/dictionary/document');
    //echo '<pre>'; print_r($res); echo '</pre>';

    function __construct()
    {
        $token = file_get_contents('token.txt');
        if ($token) {
        	$this->token = json_decode($token, true)['access_token'];
        }
        //$this->token = 'xx';
    }

    function query($url, $params=false)
    {
        $url = 'https://1:4443/api/agent'.$url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 4443);

        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if ($params) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $header = array();
        //$header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json';
        if (strpos($url, '/oauth/token') === 0) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, '');
        } else {
            $header[] = 'Authorization: Bearer '.$this->token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $res = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->header = substr($res, 0, $headerSize);
        $res = substr($res, $headerSize);

        preg_match('~HTTP/1.1 (\d+)~i', $this->header, $a);
        $this->status = $a[1];

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $this->error = 'Curl error: '.$error;
        } else {
            if (strpos($res, '{') === 0 || strpos($res, '[') === 0) {
            	$res = json_decode($res, true);
            } else {
                if ($res) {
                	$this->error = 'Ошибка запроса: '.$res;
                } else {
                    $this->error = 'Ошибка запроса';
                }
            }
        }
        curl_close($ch);

        return $res;
    }

    function reloadToken()
    {

        $url = '/oauth/token?grant_type=client_credentials';

        $res = $this->query($url, []);

        if ($res['access_token']) {
            fwrite($a = fopen('token.txt', 'w+'), json_encode($res)); fclose($a);
            $this->token = $res['access_token'];
        } else {
            var_dump($res);
        }
    }
}


$api = new Api;

//$api->token = '';

if (!$api->token) {
    $api->reloadToken();
    echo '<div>Токен перезагружен</div>';
}



$_POST['phone'] = preg_replace('~[^\d]~i', '', $_POST['phone']);
if (strlen($_POST['phone']) == 11) {
	$_POST['phone'] = substr($_POST['phone'], 1);
}

?>

<form method="post">
    <input type="text" name="phone" placeholder="Телефон" value="<?=$_POST['phone']?>" />
    <input type="text" name="name" placeholder="Имя" value="<?=$_POST['name']?>" />
    <input type="text" name="note" placeholder="Заметка" value="<?=$_POST['note']?>" />
    <input type="submit" value="Отправить" />
</form>

<?php




if ($_POST['phone']) {

    $post = [
        [
            'phone' => $_POST['phone'],
            'name' => $_POST['name'],
            'note' => $_POST['note'],
        ]
    ];

    $res = $api->query('/lead/batch', $post);
    if (!is_array($res)) {
    	// Ошибка текстовая в $res
        if (strpos($res, '<') === 0) {
        	echo $res;
        } else {
            echo '<div style="color:red">'.$this->error.' / '.$res.'</div>';
        }
    } else {
        if (substr($api->status, 0, 1) != 2) {
            echo '<div>Ошибка добавления лида, status '.$api->status.'</div>';
            echo '<pre>'; print_r($res); echo '</pre>';
            /*
            status 400
            Array
            (
                [code] => INVALID_REQUEST
                [message] => `leads[0].phone` size must be between 10 and 10
                [trace] => 51ef7606b5a49818
                [at] => 2020-04-15T11:58:36+0300
            )
            */
        } else {
            echo '<div>Лид добавлен. Результат: </div>';
            echo '<pre>'; print_r($res); echo '</pre>';
        }

    }
}


// $res = $api->query('/dictionary/tariff');
// echo '<pre>'; print_r($res); echo '</pre>';

if ($api->header) {
    echo '<pre style="color:#aaa">'.$api->header.'</pre>';
}



/*
$lead = [
    'regno' => '1096952008504',
    'taxno' => '6950101400',
    'phone' => '9067773392',
    'email' => 'email@example.com',
    'name' => 'Иванов Иван',
    'city' => '101317',
    'tariff' => 'V.БКС_ЮЛ_M_MBv2',
    'number' => 'FZAP-262-3462',
    'promo' => 'Весна19',
    'agent' => [
        'id' => '7861012311',
        'title' => 'ООО Финтерра',
        'source_name' => 'finterra',
        'employee' => '7b673ed6-5832-42af-8077-15c0f15b58a6',
        'phone' => '9063265568',
        'name' => 'Соколова Ольга Анатольевна'
    ]
];

var_dump($api->query('/application'));

*/


