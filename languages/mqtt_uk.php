<?php
$dictionary=array(
'NEW_MQTT_CLIENT'=>'Ім\`я клієнта',
'NEW_MQTT_HOST'=>'Адрес сервера',
'NEW_MQTT_PORT'=>'Порт сервера',
'NEW_MQTT_QUERY'=>'Шлях до підписаних топіків',
'NEW_MQTT_REQ_AUTH'=>'Потребує авторизацію',
'NEW_MQTT_USERNAME'=>'Ім\`я користувача',
'NEW_MQTT_PASSWORD'=>'Пароль'
);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>
