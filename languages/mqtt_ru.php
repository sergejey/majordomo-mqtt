<?php
$dictionary=array(
'NEW_MQTT_CLIENT'=>'Имя клиента',
'NEW_MQTT_HOST'=>'Адрес сервера',
'NEW_MQTT_PORT'=>'Порт сервера',
'NEW_MQTT_QUERY'=>'Путь',
'NEW_MQTT_REQ_AUTH'=>'Требуется авторизация',
'NEW_MQTT_USERNAME'=>'Имя пользователя',
'NEW_MQTT_PASSWORD'=>'Пароль'
);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>