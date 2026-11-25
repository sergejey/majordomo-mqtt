<?php
$dictionary=array(
'NEW_MQTT_CLIENT' => 'Client name',
'NEW_MQTT_HOST' => 'Host',
'NEW_MQTT_PORT' => 'Port',
'NEW_MQTT_QUERY' => 'Subscription path',
'NEW_MQTT_REQ_AUTH' => 'authorization required',
'NEW_MQTT_USERNAME' => 'Username',
'NEW_MQTT_PASSWORD' => 'Password',
'MQTT_WRITE_METHOD' => 'Write method:',
'MQTT_INSTANT' => 'Instant (new connection)',
'MQTT_QUEUE' => 'Queue',
'MQTT_SELECT' => 'select',
'MQTT_QOS' => 'QoS:',
'MQTT_RETAIN' => 'Retain:',
'MQTT_REPLACE_LIST' => 'Replace list:',
'MQTT_PAGES' => 'Pages:',
);

foreach ($dictionary as $k=>$v) { if (!defined("LANG_".$k)) { define("LANG_".$k, $v); } }
