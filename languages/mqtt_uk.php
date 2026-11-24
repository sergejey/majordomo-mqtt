<?php
$dictionary=array(
'NEW_MQTT_CLIENT' => 'Назва клієнта',
'NEW_MQTT_HOST' => 'Хост',
'NEW_MQTT_PORT' => 'Порт',
'NEW_MQTT_QUERY' => 'Шлях підписки',
'NEW_MQTT_REQ_AUTH' => 'потрібна авторизація',
'NEW_MQTT_USERNAME' => 'Ім\'я користувача',
'NEW_MQTT_PASSWORD' => 'Пароль',
'MQTT_WRITE_METHOD' => 'Спосіб запису:',
'MQTT_INSTANT' => 'Негайно (нове з\'єднання)',
'MQTT_QUEUE' => 'Черга',
'MQTT_SELECT' => 'вибрати',
'MQTT_QOS' => 'QoS:',
'MQTT_RETAIN' => 'Retain:',
'MQTT_REPLACE_LIST' => 'Замінити список:',
'MQTT_PAGES' => 'Сторінки:',
);

foreach ($dictionary as $k=>$v) { if (!defined("LANG_".$k)) { define("LANG_".$k, $v); } }
