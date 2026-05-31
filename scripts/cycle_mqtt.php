<?php
chdir(dirname(__FILE__) . '/../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");

set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");

set_time_limit(0);

include_once(ROOT . "3rdparty/phpmqtt/phpMQTT.php");
include_once(DIR_MODULES . "mqtt/mqtt.class.php");

$latest_data_received = time();
$max_no_data_timeout = 5 * 60; // 5 minutes
$mqtt_reconnect_delay = 5;
$cycle_name = str_replace('.php', '', basename(__FILE__));

setGlobal($cycle_name . 'Run', time(), 1);
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

while (1) {
    if (mqttShouldStop()) {
        break;
    }
    try {
        mqttRunOnce();
    } catch (Exception $e) {
        DebMes("MQTT cycle error: " . $e->getMessage(), 'mqtt_error');
        echo date('Y-m-d H:i:s') . " MQTT cycle error: " . $e->getMessage() . PHP_EOL;
    }
    if (mqttShouldStop()) {
        break;
    }
    sleep($mqtt_reconnect_delay);
}

/**
 * Process message
 * @param mixed $topic Topic
 * @param mixed $msg Message
 * @return void
 */
function procmsg($topic, $msg)
{
    global $latest_data_received;
    $latest_data_received = time();

    if (!isset($topic) || !isset($msg)) return false;
    global $mqtt;
    global $stripmode;
    global $mqtt_delay;
    global $mqtt_repeating_cache;

    if ($mqtt_delay > 0 && isset($mqtt_repeating_cache[$topic]['msg']) && $mqtt_repeating_cache[$topic]['msg'] == $msg && (time() - $mqtt_repeating_cache[$topic]['received']) <= $mqtt_delay) {
        // processing cached
        return false;
    }

    //DebMes("Processing incoming $topic: $msg", 'mqtt');

    if ($stripmode) {
        if (!$mqtt->hasLinkedPathWithPrefix($topic)) {
            if (isset($mqtt->config['DEBUG_MODE']) && (int)$mqtt->config['DEBUG_MODE']) {
                echo date("Y-m-d H:i:s") . " Ignore received from {$topic} : $msg\n";
            }
            return false;
        }
    }

    $debug_mode = isset($mqtt->config['DEBUG_MODE']) ? (int)$mqtt->config['DEBUG_MODE'] : 0;
    if ($debug_mode) {
        echo date("Y-m-d H:i:s") . " Received from {$topic} : $msg\n";
    }

    if ($mqtt_delay > 0) {
        $mqtt_repeating_cache[$topic] = array('msg' => $msg, 'received' => time());
    }

    $source_url = '/api.php/module/mqtt?topic=' . urlencode($topic) . '&msg=' . urlencode($msg) . '&no_session=1';
    $has_request_uri = isset($_SERVER['REQUEST_URI']);
    $old_request_uri = $has_request_uri ? $_SERVER['REQUEST_URI'] : '';
    $_SERVER['REQUEST_URI'] = $source_url;
    $mqtt->processMessage($topic, $msg);
    if ($has_request_uri) {
        $_SERVER['REQUEST_URI'] = $old_request_uri;
    } else {
        unset($_SERVER['REQUEST_URI']);
    }
}

function mqttShouldStop()
{
    if (isset($_GET['onetime'])) {
        return true;
    }
    if (function_exists('isRebootRequired')) {
        return (bool)isRebootRequired();
    }
    return file_exists('./reboot');
}

function mqttRunOnce()
{
    global $mqtt;
    global $stripmode;
    global $mqtt_delay;
    global $latest_data_received;
    global $max_no_data_timeout;

    $mqtt = new mqtt();
    $mqtt->getConfig();

    $client_name = $mqtt->config['MQTT_CLIENT'] ? $mqtt->config['MQTT_CLIENT'] : "MajorDoMo MQTT Cycle";
    $client_name = $client_name . ' (#' . uniqid() . ')';

    $username = '';
    $password = '';
    if (!empty($mqtt->config['MQTT_AUTH'])) {
        $username = $mqtt->config['MQTT_USERNAME'];
        $password = $mqtt->config['MQTT_PASSWORD'];
    }

    $host = $mqtt->config['MQTT_HOST'] ? $mqtt->config['MQTT_HOST'] : 'localhost';
    $port = $mqtt->config['MQTT_PORT'] ? $mqtt->config['MQTT_PORT'] : 1883;
    $query = $mqtt->config['MQTT_QUERY'] ? $mqtt->config['MQTT_QUERY'] : '/var/now/#';
    $stripmode = !empty($mqtt->config['MQTT_STRIPMODE']) ? (int)$mqtt->config['MQTT_STRIPMODE'] : 0;
    $mqtt_delay = isset($mqtt->config['MQTT_DELAY']) ? (int)$mqtt->config['MQTT_DELAY'] : 5;

    $mqtt_client = new Bluerhinos\phpMQTT($host, $port, $client_name);
    if (!empty($mqtt->config['MQTT_AUTH'])) {
        $connect = $mqtt_client->connect(true, NULL, $username, $password);
    } else {
        $connect = $mqtt_client->connect();
    }
    if (!$connect) {
        throw new Exception("Failed to connect to MQTT broker {$host}:{$port}");
    }

    $topics = array();
    $query_list = explode(',', $query);
    $total = count($query_list);
    echo date('H:i:s') . " Topics to watch: $query (Total: $total)\n";
    for ($i = 0; $i < $total; $i++) {
        $path = trim($query_list[$i]);
        if ($path == '') {
            continue;
        }
        echo date('H:i:s') . " Path: $path\n";
        $topics[$path] = array("qos" => 0, "function" => "procmsg");
    }
    foreach ($topics as $k => $v) {
        echo date('H:i:s') . " Subscribing to: $k\n";
        $mqtt_client->subscribe(array($k => $v), 0);
    }

    $latest_data_received = time();
    $previous_heartbeat = 0;
    while ($mqtt_client->proc()) {
        if ((time() - $latest_data_received) > $max_no_data_timeout) {
            setGlobal('cycle_mqttControl', 'restart');
        }
        if (!empty($mqtt->config['MQTT_WRITE_METHOD']) && (int)$mqtt->config['MQTT_WRITE_METHOD'] == 2) {
            $queue = checkOperationsQueue('mqtt_queue');
            foreach ($queue as $mqtt_data) {
                $topic = $mqtt_data['DATANAME'];
                if ($topic == '') {
                    continue;
                }
                $data_value = json_decode($mqtt_data['DATAVALUE'], true);
                $value = isset($data_value['v']) ? $data_value['v'] : '';
                $qos = isset($data_value['q']) ? (int)$data_value['q'] : 0;
                $retain = isset($data_value['r']) ? (int)$data_value['r'] : 0;
                if (isset($mqtt->config['DEBUG_MODE']) && (int)$mqtt->config['DEBUG_MODE']) {
                    echo "Publishing to $topic : $value\n";
                }
                $result = $mqtt_client->publish($topic, $value, $qos, $retain);
                if (!is_null($result) && !$result) {
                    DebMes("Error writing from queue '$value' to $topic", 'mqtt_error');
                }
            }
        }

        if ((time() - $previous_heartbeat) >= 1) {
            $previous_heartbeat = time();
            setGlobal(str_replace('.php', '', basename(__FILE__)) . 'Run', time(), 1);
            if (mqttShouldStop()) {
                $mqtt_client->close();
                return;
            }
        }
    }
    $mqtt_client->close();
}

$db->Disconnect(); // closing database connection
