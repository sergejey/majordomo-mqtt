<?php
/**
 * MQTT
 *
 * Mqtt
 *
 * @package project
 * @author Serge J. <jey@tut.by>
 * @copyright http://www.atmatic.eu/ (c)
 * @version 0.1 (wizard, 13:07:08 [Jul 19, 2013])
 */
//
//
class mqtt extends module
{
    /**
     * mqtt
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "mqtt";
        $this->title = "<#LANG_MODULE_MQTT#>";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 0)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        if (isset($this->location_id)) {
            $out['IS_SET_LOCATION_ID'] = 1;
        }
        if ($this->single_rec) {
            $out['SINGLE_REC'] = 1;
        }
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;

    }

    function prepareQueueTable()
    {
        //SQLExec ("DROP TABLE IF EXISTS `mqtt_queue`;");
        $sqlQuery = "CREATE TABLE IF NOT EXISTS `mqtt_queue`
               (`ID`  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `PATH` VARCHAR(255) NOT NULL,
                `VALUE` VARCHAR(255) NOT NULL,
                 PRIMARY KEY (`ID`)
               ) ENGINE = MEMORY DEFAULT CHARSET=utf8;";
        SQLExec($sqlQuery);
    }

    function pathToTree($array)
    {
        $tree = array();
        foreach ($array as $item) {
            //$pathIds = explode("/", ltrim($item["PATH"], "/") . '/' . $item["ID"]);
            $pathIds = explode("/", $item["PATH"] . '/' . $item["ID"]);
            if ($pathIds[0] == '') {
                array_shift($pathIds);
                $pathIds[0] = '/' . $pathIds[0];
            }
            $current = &$tree;
            $cp = '';
            foreach ($pathIds as $id) {
                if (!isset($current["CHILDS"][$id])) {
                    $current["CHILDS"][$id] = array('CP' => $cp);
                }
                $current = &$current["CHILDS"][$id];
                if ($id == $item["ID"]) {
                    $current = $item;
                }
            }
        }
        return ($this->childsToArray($tree['CHILDS'], ''));
    }

    function childsToArray($items, $prev_path = '')
    {
        global $session;
        $res = array();
        foreach ($items as $k => $v) {
            if (!isset($v['PATH'])) {
                $v['TITLE'] = trim($k . ' ' . $v['CP']);
                $pp = $k;
            } else {
                $v['TITLE'] = '';
                $pp = '';
            }
            if (isset($v['CHILDS'])) {
                $items = $this->childsToArray($v['CHILDS'], $prev_path != '' ? $prev_path . '/' . $pp : $pp);
                $total = count($items);
                if ($total == 1) {
                    $v = $items[0];
                    $v['TITLE'] = $pp . ($v['TITLE'] != '' ? '/' . $v['TITLE'] : '');
                } else {
                    $max_updated = 0;
                    $device_title = '';
                    $device_id = '';
                    for ($i = 0; $i < $total; $i++) {
                        if (isset($items[$i]['DEVICE_TITLE']) && $items[$i]['DEVICE_TITLE'] != '' && !$device_title) {
                            $device_title = $items[$i]['DEVICE_TITLE'];
                        }
                        if (isset($items[$i]['DEVICE_ID']) && $items[$i]['DEVICE_ID'] != '' && !$device_id) {
                            $device_id = $items[$i]['DEVICE_ID'];
                        }
                        if (isset($items[$i]['UPDATED'])) {
                            $tm = strtotime($items[$i]['UPDATED']);
                            if (!isset($items[$i]['COLOR'])) {
                                if ((time() - $tm) <= 60 * 60) {
                                    $items[$i]['COLOR'] = 'green';
                                } elseif ((time() - $tm) <= 24 * 60 * 60) {
                                    $items[$i]['COLOR'] = 'black';
                                } else {
                                    $items[$i]['COLOR'] = '#aaaaaa';
                                }
                            }
                            if ($tm > $max_updated) {
                                $max_updated = $tm;
                            }
                        }
                    }
                    if ($device_title) {
                        $v['DEVICE_TITLE'] = $device_title;
                    }
                    if ($device_id) {
                        $v['DEVICE_ID'] = $device_id;
                    }
                    $v['UPDATED'] = date('Y-m-d H:i:s', $max_updated);
                    if ((time() - $max_updated) <= 60 * 60) {
                        $v['COLOR'] = 'green';
                    } elseif ((time() - $max_updated) <= 24 * 60 * 60) {
                        $v['COLOR'] = 'black';
                    } else {
                        $v['COLOR'] = '#aaaaaa';
                    }
                    $v['RESULT'] = $items;
                }
                //$v['RESULT'] = $items;
                unset($v['CHILDS']);
            }
            if (isset($session->data['branches'][$v['TITLE']])) {
                $v['IS_VISIBLE'] = 1;
            }
            if (!isset($v['PATH'])) {
                if ($prev_path) {
                    $v['PATH'] = $prev_path . '/' . $v['TITLE'];
                } else {
                    $v['PATH'] = $v['TITLE'];
                }
            }
            $v['PATH_URL'] = urlencode(isset($v['PATH']) ? $v['PATH'] : '');
            $res[] = $v;
        }
        return $res;
    }


    function mqttPublish($topic, $value, $qos = 0, $retain = 0, $write_type = 0)
    {

        $this->getConfig();
        if ($write_type == 0 && $this->config['MQTT_WRITE_METHOD']) {
            $write_type = 2;
        }

        if ($write_type == 2) {
            $data = array('v' => $value);
            if ($qos) {
                $data['q'] = $qos;
            }
            if ($retain) {
                $data['r'] = $retain;
            }
            addToOperationsQueue('mqtt_queue', $topic, json_encode($data), true);
            return 1;
        }

        include_once(ROOT . "3rdparty/phpmqtt/phpMQTT.php");


        if ($this->config['MQTT_CLIENT']) {//
            $client_name = $this->config['MQTT_CLIENT'];
        } else {
            $client_name = "MajorDoMo MQTT";
        }

        if ($this->config['MQTT_AUTH']) {
            $username = $this->config['MQTT_USERNAME'];
            $password = $this->config['MQTT_PASSWORD'];
        }
        if ($this->config['MQTT_HOST']) {
            $host = $this->config['MQTT_HOST'];
        } else {
            $host = 'localhost';
        }
        if ($this->config['MQTT_PORT']) {
            $port = $this->config['MQTT_PORT'];
        } else {
            $port = 1883;
        }

        $mqtt_client = new Bluerhinos\phpMQTT($host, $port, $client_name . ' Client');

        if (!$mqtt_client->connect(true, NULL, $username, $password)) {
            return 0;
        }

        $mqtt_client->publish($topic, $value, $qos, $retain);

        $mqtt_client->close();
    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function setProperty($id, $value, $set_linked = 0)
    {
        $rec = SQLSelectOne("SELECT * FROM mqtt WHERE ID='" . $id . "'");

        if (!$rec['ID'] || !$rec['PATH']) {
            return 0;
        }


        if ($rec['REPLACE_LIST'] != '') {
            $list = explode(',', $rec['REPLACE_LIST']);
            foreach ($list as $pair) {
                $pair = trim($pair);
                list($new, $old) = explode('=', $pair);
                if ($value == $old) {
                    $value = $new;
                    break;
                }
            }
        }

        if ($rec['PATH_WRITE']) {
            if (preg_match('/^http:/', $rec['PATH_WRITE'])) {
                $url = $rec['PATH_WRITE'];
                $url = str_replace('%VALUE%', $value, $url);
                getURL($url, 0);
            } else {
                $this->mqttPublish($rec['PATH_WRITE'], $value, (int)$rec['QOS'], (int)$rec['RETAIN'], (int)$rec['WRITE_TYPE']);
            }
        } else {
            $this->mqttPublish($rec['PATH'], $value, (int)$rec['QOS'], (int)$rec['RETAIN'], (int)$rec['WRITE_TYPE']);
        }
        //$mqtt_client->close();

        $rec['VALUE'] = $value . '';
        $rec['UPDATED'] = date('Y-m-d H:i:s');
        SQLUpdate('mqtt', $rec);


        if ($set_linked && $rec['LINKED_OBJECT'] && $rec['LINKED_PROPERTY']) {
            setGlobal($rec['LINKED_OBJECT'] . '.' . $rec['LINKED_PROPERTY'], $value, array($this->name => '0'));
        }

    }

    /**
     * Title
     *
     * Description
     *
     * @access public
     */
    function processMessage($path, $value)
    {
        if (preg_match('/\#$/', $path)) {
            return 0;
        }

        if ($value === false) $value = 0;
        elseif ($value === true) $value = 1;

        $this->getConfig();

        if (preg_match('/^{/', $value)) {
            $ar = json_decode($value, true);
            foreach ($ar as $k => $v) {
                if (is_array($v))
                    $v = json_encode($v);
                if ($this->config['MQTT_STRIPMODE']) {
                    $rec = SQLSelectOne("SELECT ID FROM `mqtt` where `PATH` LIKE '$path/$k%' and LINKED_OBJECT>''");
                    if (!$rec['ID']) {
                        continue;
                    }
                }
                $this->processMessage($path . '/' . $k, $v);
            }
        }

        if (preg_match("/^\\\\u\\d+/", $path)) {
            $path = json_decode('"' . $path . '"');
        }
        if (preg_match("/^\\\\u\\d+/", $value)) {
            $value = json_decode('"' . $value . '"');
        }

        /* Search 'PATH' in database (db) */
        $rec = SQLSelectOne("SELECT * FROM mqtt WHERE PATH = '" . DBSafe($path) . "'");
        $old_value = $rec['VALUE'];

        if (!$rec['ID']) { /* If 'PATH' not found in db */
            /* New query to search 'PATH_WRITE' record in db */
            $rec = SQLSelectOne("SELECT * FROM mqtt WHERE PATH_WRITE = '" . DBSafe($path) . "'");

            if ($rec['ID']) { /* If path_write foud in db */
                if ($rec['DISP_FLAG'] != "0") { /* check disp_flag */
                    return 0; /* ignore message if flag checked */
                }
            }
            /* Insert new record in db */
            $rec = array();
            $rec['PATH'] = $path;
            $rec['TITLE'] = $path;
            $rec['VALUE'] = $value . '';
            $rec['UPDATED'] = date('Y-m-d H:i:s');
            SQLInsert('mqtt', $rec);
        } else {

            if (!$rec['ONLY_NEW_VALUE'] || ($value <> $old_value)) {

                /* Update values in db */
                $rec['VALUE'] = $value . '';
                $rec['UPDATED'] = date('Y-m-d H:i:s');
                SQLUpdate('mqtt', $rec);

                /* Update property in linked object if it exist */
                if ($rec['LINKED_OBJECT'] && $rec['LINKED_PROPERTY']) {
                    if ($rec['REPLACE_LIST'] != '') {
                        $list = explode(',', $rec['REPLACE_LIST']);
                        foreach ($list as $pair) {
                            $pair = trim($pair);
                            list($new, $old) = explode('=', $pair);
                            if ($value == $new) {
                                $value = $old;
                                break;
                            }
                        }
                    }
                    setGlobal($rec['LINKED_OBJECT'] . '.' . $rec['LINKED_PROPERTY'], $value, array('mqtt' => '0'));
                }

                if ($rec['LINKED_OBJECT'] && $rec['LINKED_METHOD'] &&
                    !(strtolower($rec['LINKED_PROPERTY']) == 'status' && strtolower($rec['LINKED_METHOD']) == 'switch')) {
                    callMethod($rec['LINKED_OBJECT'] . '.' . $rec['LINKED_METHOD'], array('VALUE' => $rec['VALUE'], 'NEW_VALUE' => $rec['VALUE'], 'OLD_VALUE' => $old_value));
                }

            }


        }
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {

        if (gr('ajax')) {
            $op = gr('op');
            if ($op == 'branch_status') {
                global $session;
                $status = gr('status');
                $branch = gr('branch');
                if (!$status) {
                    unset($session->data['branches'][$branch]);
                } else {
                    $session->data['branches'][$branch] = 1;
                }
                dprint($session, false);
                $session->save();
            }
            exit;
        }

        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }

        $this->getConfig();
        $out['MQTT_CLIENT'] = $this->config['MQTT_CLIENT'];
        $out['MQTT_HOST'] = $this->config['MQTT_HOST'];
        $out['MQTT_PORT'] = $this->config['MQTT_PORT'];
        $out['MQTT_QUERY'] = $this->config['MQTT_QUERY'];
        $out['MQTT_DELAY'] = $this->config['MQTT_DELAY'];
        $out['MQTT_WRITE_METHOD'] = isset($this->config['MQTT_WRITE_METHOD']) ? (int)$this->config['MQTT_WRITE_METHOD'] : 0;
        $out['MQTT_STRIPMODE'] = isset($this->config['MQTT_STRIPMODE']) ? $this->config['MQTT_STRIPMODE'] : 0;

        if (!$out['MQTT_HOST']) {
            $out['MQTT_HOST'] = 'localhost';
        }
        if (!$out['MQTT_PORT']) {
            $out['MQTT_PORT'] = '1883';
        }
        if (!$out['MQTT_QUERY']) {
            $out['MQTT_QUERY'] = '/var/now/#';
        }

        $out['MQTT_USERNAME'] = $this->config['MQTT_USERNAME'];
        $out['MQTT_PASSWORD'] = $this->config['MQTT_PASSWORD'];
        $out['MQTT_AUTH'] = $this->config['MQTT_AUTH'];

        if ($this->view_mode == 'update_settings') {

            $this->config['MQTT_CLIENT'] = gr('mqtt_client');
            $this->config['MQTT_HOST'] = gr('mqtt_host');
            $this->config['MQTT_USERNAME'] = gr('mqtt_username');
            $this->config['MQTT_PASSWORD'] = gr('mqtt_password');
            $this->config['MQTT_AUTH'] = gr('mqtt_auth', 'int');
            $this->config['MQTT_PORT'] = gr('mqtt_port', 'int');
            $this->config['MQTT_QUERY'] = gr('mqtt_query');
            $this->config['MQTT_WRITE_METHOD'] = gr('mqtt_write_method', 'int');
            $this->config['MQTT_STRIPMODE'] = gr('mqtt_stripmode', 'int');

            $mqtt_delay = gr('mqtt_delay');
            if ($mqtt_delay === '') {
                unset($this->config['MQTT_DELAY']);
            } else {
                $this->config['MQTT_DELAY'] = (int)$mqtt_delay;
            }

            $this->saveConfig();

            setGlobal('cycle_mqttControl', 'restart');

            $this->redirect("?");
        }

        if (!$this->config['MQTT_HOST']) {
            $this->config['MQTT_HOST'] = 'localhost';
            $this->saveConfig();
        }
        if (!$this->config['MQTT_PORT']) {
            $this->config['MQTT_PORT'] = '1883';
            $this->saveConfig();
        }
        if (!$this->config['MQTT_QUERY']) {
            $this->config['MQTT_QUERY'] = '/var/now/#';
            $this->saveConfig();
        }


        if ($this->data_source == 'mqtt' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_mqtt') {
                $this->search_mqtt($out);
            }
            if ($this->view_mode == 'edit_mqtt') {
                $this->edit_mqtt($out, $this->id);
            }
            if ($this->view_mode == 'delete_path') {
                $this->delete_mqtt_path(gr('path'));
                $this->redirect(ROOTHTML . "panel/mqtt.html");
            }
            if ($this->view_mode == 'delete_mqtt') {
                $this->delete_mqtt($this->id);
                $this->redirect(ROOTHTML . "panel/mqtt.html");
            }
            if ($this->view_mode == 'clear_trash') {
                $this->clear_trash();
                $this->redirect(ROOTHTML . "panel/mqtt.html");
            }
        }
    }

    function clear_trash()
    {
        $res = SQLSelect("SELECT ID FROM mqtt WHERE LINKED_OBJECT='' AND LINKED_PROPERTY=''");
        $total = count($res);
        for ($i = 0; $i < $total; $i++) {
            $this->delete_mqtt($res[$i]['ID']);
        }
        $res = SQLSelect("SELECT ID, LINKED_OBJECT FROM mqtt WHERE LINKED_OBJECT!=''");
        $total = count($res);
        for ($i = 0; $i < $total; $i++) {
            $obj = getObject($res[$i]['LINKED_OBJECT']);
            if (!is_object($obj)) {
                $this->delete_mqtt($res[$i]['ID']);
            }
        }
    }

    function api($params)
    {
        if (isset($_REQUEST['topic']) && $_REQUEST['topic']) {
            $this->processMessage($_REQUEST['topic'], $_REQUEST['msg']);
        }
        if (isset($params['publish']) && $params['publish']) {
            $this->mqttPublish($params['publish'], $params['msg']);
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        if ($this->ajax) {
            global $op;
            $result = array();
            if ($op == 'process') {
                $topic = gr('topic');
                $msg = gr('msg');
                $this->processMessage($topic, $msg);
            }
            if ($op == 'getvalues') {
                global $ids;
                $qry = '1';
                if (is_array($ids)) {
                    $qry .= " AND mqtt.ID IN (" . implode(',', $ids) . ")";
                } elseif ($ids == 'all') {
                    $qry = '1';
                } else {
                    $qry = 0;
                }
                $data = SQLSelect("SELECT ID,VALUE FROM mqtt WHERE " . $qry);
                $total = count($data);
                for ($i = 0; $i < $total; $i++) {
                    $data[$i]['VALUE'] = str_replace('":', '": ', $data[$i]['VALUE']);
                }
                $result['DATA'] = $data;
            }
            if ($op == 'send') {
                $topic = gr('topic');
                $value = gr('value');
            }
            echo json_encode($result);
            exit;
        }
        $this->admin($out);
    }

    /**
     * mqtt search
     *
     * @access public
     */
    function search_mqtt(&$out)
    {
        require(DIR_MODULES . $this->name . '/mqtt_search.inc.php');
    }

    /**
     * mqtt edit/add
     *
     * @access public
     */
    function edit_mqtt(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/mqtt_edit.inc.php');
    }

    function propertySetHandle($object, $property, $value)
    {
        $mqtt_properties = SQLSelect("SELECT ID FROM mqtt WHERE READONLY=0 AND LINKED_OBJECT LIKE '" . DBSafe($object) . "' AND LINKED_PROPERTY LIKE '" . DBSafe($property) . "'");
        $total = count($mqtt_properties);
        if ($total) {
            for ($i = 0; $i < $total; $i++) {
                $this->setProperty($mqtt_properties[$i]['ID'], $value);
            }
        }
    }


    /**
     * mqtt delete record
     *
     * @access public
     */

    function delete_mqtt_path($path)
    {
        if (!$path) return;
        $records = SQLSelect("SELECT ID FROM mqtt WHERE PATH LIKE '" . DBSafe($path) . "/%' OR PATH='" . DBSafe($path) . "'");
        foreach ($records as $rec) {
            $this->delete_mqtt($rec['ID']);
        }
    }

    function delete_mqtt($id)
    {
        $rec = SQLSelectOne("SELECT * FROM mqtt WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM mqtt WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS mqtt');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        mqtt - MQTT
        */
        $data = <<<EOD
 mqtt: ID int(10) unsigned NOT NULL auto_increment
 mqtt: TITLE varchar(255) NOT NULL DEFAULT ''
 mqtt: LOCATION_ID int(10) NOT NULL DEFAULT '0'
 mqtt: UPDATED datetime
 mqtt: VALUE varchar(1024) NOT NULL DEFAULT ''
 mqtt: PATH varchar(255) NOT NULL DEFAULT ''
 mqtt: PATH_WRITE varchar(255) NOT NULL DEFAULT ''
 mqtt: REPLACE_LIST varchar(255) NOT NULL DEFAULT ''
 mqtt: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 mqtt: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 mqtt: LINKED_METHOD varchar(255) NOT NULL DEFAULT ''
 mqtt: QOS int(3) NOT NULL DEFAULT '0'
 mqtt: RETAIN int(3) NOT NULL DEFAULT '0'
 mqtt: DISP_FLAG int(3) NOT NULL DEFAULT '0'
 mqtt: READONLY int(3) NOT NULL DEFAULT '0'
 mqtt: WRITE_TYPE int(3) NOT NULL DEFAULT '0'
 mqtt: ONLY_NEW_VALUE int(3) NOT NULL DEFAULT '0'
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}

/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVsIDE5LCAyMDEzIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
?>
