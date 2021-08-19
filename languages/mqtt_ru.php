<?php

$dictionary=array(

'MQTT_CLIENT'=>'Имя клиента',
'MQTT_HOST'=>'Адрес сервера',
'MQTT_PORT'=>'Порт сервера',
'MQTT_QUERY'=>'Путь',
'MQTT_REQ_AUTH'=>'Требуется авторизация',
'MQTT_USERNAME'=>'Имя пользователя',
'MQTT_PASSWORD'=>'Пароль',


);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>