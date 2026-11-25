<?php
$dictionary=array(
'NEW_MQTT_CLIENT' => 'Имя клиента',
'NEW_MQTT_HOST' => 'Хост',
'NEW_MQTT_PORT' => 'Порт',
'NEW_MQTT_QUERY' => 'Путь подписки',
'NEW_MQTT_REQ_AUTH' => 'требуется авторизация',
'NEW_MQTT_USERNAME' => 'Имя пользователя',
'NEW_MQTT_PASSWORD' => 'Пароль',
'MQTT_WRITE_METHOD' => 'Способ записи:',
'MQTT_INSTANT' => 'Немедленно (новое соединение)',
'MQTT_QUEUE' => 'Очередь',
'MQTT_SELECT' => 'выбрать',
'MQTT_QOS' => 'QoS:',
'MQTT_RETAIN' => 'Retain:',
'MQTT_REPLACE_LIST' => 'Заменить список:',
'MQTT_PAGES' => 'Страницы:',
);

foreach ($dictionary as $k=>$v) { if (!defined("LANG_".$k)) { define("LANG_".$k, $v); } }
