<?php

$dictionary=array(

'MQTT_CLIENT'=>'Client name',
'MQTT_HOST'=>'Host',
'MQTT_PORT'=>'Port',
'MQTT_QUERY'=>'Subscription path',
'MQTT_REQ_AUTH'=>'authorization required',
'MQTT_USERNAME'=>'Username',
'MQTT_PASSWORD'=>'Password',

);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>