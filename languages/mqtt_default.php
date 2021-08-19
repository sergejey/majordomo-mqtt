<?php
$dictionary=array(
'NEW_MQTT_CLIENT'=>'Client name',
'NEW_MQTT_HOST'=>'Host',
'NEW_MQTT_PORT'=>'Port',
'NEW_MQTT_QUERY'=>'Subscription path',
'NEW_MQTT_REQ_AUTH'=>'authorization required',
'NEW_MQTT_USERNAME'=>'Username',
'NEW_MQTT_PASSWORD'=>'Password'
);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}
?>