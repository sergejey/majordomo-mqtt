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
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab)) {
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
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        if (IsSet($this->location_id)) {
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
        foreach ($array AS $item) {
            $pathIds = explode("/", ltrim($item["PATH"], "/") . '/' . $item["ID"]);
            $current = &$tree;
            $cp = '';
            foreach ($pathIds AS $id) {
                if (!isset($current["CHILDS"][$id])) {
                    $current["CHILDS"][$id] = array('CP' => $cp);
                }
                $current = &$current["CHILDS"][$id];
                if ($id == $item["ID"]) {
                    $current = $item;
                }
            }
        }
        return ($this->childsToArray($tree['CHILDS']));
    }

    function childsToArray($items, $prev_path = '')
    {
        $res = array();
        foreach ($items as $k => $v) {
            if (!$v['PATH']) {
                $v['TITLE'] = $k . ' ' . $v['CP'];
                $pp = $k;
            } else {
                $v['TITLE'] = '';
                $pp = '';
            }
            if (isset($v['CHILDS'])) {
                $items = $this->childsToArray($v['CHILDS'], $prev_path != '' ? $prev_path . '/' . $pp : $pp);
                if (count($items) == 1) {
                    $v = $items[0];
                    $v['TITLE'] = $pp . ($v['TITLE'] != '' ? '/' . $v['TITLE'] : '');
                } else {
                    $v['RESULT'] = $items;
                }
                unset($v['CHILDS']);
            }
            $res[] = $v;
        }
        return $res;
    }


    function mqttPublish($topic, $value, $qos = 0, $retain = 0)
    {
        //include_once("./lib/mqtt/phpMQTT.php");
        include_once(ROOT . "3rdparty/phpmqtt/phpMQTT.php");

        $this->getConfig();
        if ($mqtt->config['MQTT_CLIENT']) {
            $client_name = $mqtt->config['MQTT_CLIENT'];
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
                $this->mqttPublish($rec['PATH_WRITE'], $value, (int)$rec['QOS'], (int)$rec['RETAIN']);
            }
        } else {
            $this->mqttPublish($rec['PATH'], $value, (int)$rec['QOS'], (int)$rec['RETAIN']);
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

        if (preg_match('/^{/', $value)) {
            $ar = json_decode($value, true);
            foreach ($ar as $k => $v) {
                if (is_array($v))
                    $v = json_encode($v);
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
            /* Update values in db */
            $rec['VALUE'] = $value . '';
            $rec['UPDATED'] = date('Y-m-d H:i:s');
            SQLUpdate('mqtt', $rec);
            /* Update property in linked object if it exist */
            if ($rec['LINKED_OBJECT'] && $rec['LINKED_PROPERTY']) {

                $value = $rec['VALUE'];
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
                !(strtolower($rec['LINKED_PROPERTY'])=='status' && strtolower($rec['LINKED_METHOD'])=='switch')) {
                callMethod($rec['LINKED_OBJECT'] . '.' . $rec['LINKED_METHOD'], array('VALUE'=>$rec['VALUE'],'OLD_VALUE'=>$old_value));
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
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }

        $this->getConfig();
        $out['MQTT_CLIENT'] = $this->config['MQTT_CLIENT'];
        $out['MQTT_HOST'] = $this->config['MQTT_HOST'];
        $out['MQTT_PORT'] = $this->config['MQTT_PORT'];
        $out['MQTT_QUERY'] = $this->config['MQTT_QUERY'];

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
            global $mqtt_client;
            global $mqtt_host;
            global $mqtt_username;
            global $mqtt_password;
            global $mqtt_auth;
            global $mqtt_port;
            global $mqtt_query;

            $this->config['MQTT_CLIENT'] = trim($mqtt_client);
            $this->config['MQTT_HOST'] = trim($mqtt_host);
            $this->config['MQTT_USERNAME'] = trim($mqtt_username);
            $this->config['MQTT_PASSWORD'] = trim($mqtt_password);
            $this->config['MQTT_AUTH'] = (int)$mqtt_auth;
            $this->config['MQTT_PORT'] = (int)$mqtt_port;
            $this->config['MQTT_QUERY'] = trim($mqtt_query);
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
            if ($this->view_mode == 'delete_mqtt') {
                $this->delete_mqtt($this->id);
                $this->redirect("?");
            }
            if ($this->view_mode == 'clear_trash') {
                $this->clear_trash();
                $this->redirect("?");
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

    function api($params) {
        if ($_REQUEST['topic']) {
            $this->processMessage($_REQUEST['topic'], $_REQUEST['msg']);
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
                if (!is_array($ids)) {
                    $ids = array(0);
                } else {
                    $ids[] = 0;
                }
                $data = SQLSelect("SELECT ID,VALUE FROM mqtt WHERE ID IN (" . implode(',', $ids) . ")");
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
 mqtt: VALUE varchar(255) NOT NULL DEFAULT ''
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
