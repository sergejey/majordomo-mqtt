<?php
/*
* @version 0.1 (wizard)
*/
global $session;
if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}
$qry = "1";

$go_linked_object = gr('go_linked_object');
$go_linked_property = gr('go_linked_property');
if ($go_linked_object && $go_linked_property) {
    $tmp = SQLSelectOne("SELECT ID FROM mqtt WHERE LINKED_OBJECT = '" . DBSafe($go_linked_object) . "' AND LINKED_PROPERTY='" . DBSafe($go_linked_property) . "'");
    if ($tmp['ID']) {
        $this->redirect("?id=" . $tmp['ID'] . "&view_mode=edit_mqtt");
    }
}

// search filters
//searching 'TITLE' (varchar)
global $title;
if ($title != '') {
    $qry .= " AND (TITLE LIKE '%" . DBSafe($title) . "%' OR VALUE LIKE '%" . DBSafe($title) . "%' OR PATH LIKE '%" . DBSafe($title) . "%'";
    $qry .= " OR LINKED_OBJECT LIKE '" . DBSafe($title) . "'";
    $qry .= " OR LINKED_PROPERTY LIKE '" . DBSafe($title) . "'";
    $qry .= " OR LINKED_METHOD LIKE '" . DBSafe($title) . "'";
    $qry .= ")";
    $out['TITLE'] = $title;
}


global $searchpath;
if ($searchpath != '') {
    $qry .= " AND (TITLE LIKE '%" . DBSafe($searchpath) . "%' OR VALUE LIKE '%" . DBSafe($searchpath) . "%' OR PATH LIKE '%" . DBSafe($searchpath) . "%'";
    $qry .= " OR LINKED_OBJECT LIKE '" . DBSafe($searchpath) . "'";
    $qry .= " OR LINKED_PROPERTY LIKE '" . DBSafe($searchpath) . "'";
    $qry .= " OR LINKED_METHOD LIKE '" . DBSafe($searchpath) . "'";
    $qry .= ")";
    $out['SEARCH'] = $searchpath;
}

global $location_id;
if ($location_id) {
    $qry .= " AND LOCATION_ID='" . (int)$location_id . "'";
    $out['LOCATION_ID'] = (int)$location_id;
}

if (IsSet($this->location_id)) {
    $location_id = $this->location_id;
    $qry .= " AND LOCATION_ID='" . $this->location_id . "'";
} else {
    global $location_id;
}
// QUERY READY
global $save_qry;
if ($save_qry) {
    $qry = $session->data['mqtt_qry'];
} else {
    $session->data['mqtt_qry'] = $qry;
}
if (!$qry) $qry = "1";
// FIELDS ORDER
global $sortby_mqtt;
if (!$sortby_mqtt) {
    $sortby_mqtt = $session->data['mqtt_sort'];
} else {
    if ($session->data['mqtt_sort'] == $sortby_mqtt) {
        if (Is_Integer(strpos($sortby_mqtt, ' DESC'))) {
            $sortby_mqtt = str_replace(' DESC', '', $sortby_mqtt);
        } else {
            $sortby_mqtt = $sortby_mqtt . " DESC";
        }
    }
    $session->data['mqtt_sort'] = $sortby_mqtt;
}
//if (!$sortby_mqtt) $sortby_mqtt="ID DESC";
$sortby_mqtt = "UPDATED DESC";
$out['SORTBY'] = $sortby_mqtt;

global $tree;
if (!isset($tree)) {
    $tree = (int)$session->data['MQTT_TREE_VIEW'];
} else {
    $session->data['MQTT_TREE_VIEW'] = $tree;
}

if (isset($_GET['tree'])) {
    $tree = (int)$_GET['tree'];
    $this->config['TREE_VIEW'] = $tree;
    $this->saveConfig();
} else {
    $tree = $this->config['TREE_VIEW'];
}

if ($tree) {
    $out['TREE'] = 1;
}

// SEARCH RESULTS
if ($out['TREE']) {
    $sortby_mqtt = 'PATH';
}
$res = SQLSelect("SELECT * FROM mqtt WHERE $qry ORDER BY " . $sortby_mqtt);
if ($res[0]['ID']) {
    if (!$out['TREE']) {
        paging($res, 50, $out); // search result paging
    }
    $total = count($res);
    for ($i = 0; $i < $total; $i++) {
        // some action for every record if required
        //$tmp=explode(' ', $res[$i]['UPDATED']);
        //$res[$i]['UPDATED']=fromDBDate($tmp[0])." ".$tmp[1];
        $res[$i]['VALUE'] = str_replace('":', '": ', $res[$i]['VALUE']);
        if ($res[$i]['TITLE'] == $res[$i]['PATH'] && !$out['TREE']) $res[$i]['PATH'] = '';
        if ($res[$i]['LINKED_OBJECT'] != "") {
            $object_rec = SQLSelectOne("SELECT * FROM objects WHERE TITLE='" . DBSafe($res[$i]['LINKED_OBJECT']) . "'");
            //$res[$i]['LINKED_PROPERTY'] .= ' &mdash; ' . $object_rec['DESCRIPTION'];
        }
    }
    $out['RESULT'] = $res;

    if ($out['TREE']) {
        $out['RESULT'] = $this->pathToTree($res);
        //dprint($out['RESULT']);
    }

}


$out['LOCATIONS'] = SQLSelect("SELECT * FROM locations ORDER BY TITLE");

$out['PATHS'] = SQLSelect("SELECT DISTINCT SUBSTRING_INDEX(CASE SUBSTRING(path,1,1) WHEN '/' THEN SUBSTRING(path,2) ELSE  path END, '/', 1) path FROM mqtt");


?>
