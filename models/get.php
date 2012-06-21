<?php
$env = getenv('MODTEST');
if ($env){
	$env = explode(',', $env);
	print_r($env);
	$_REQUEST['_act'] = $env[0];
	$_REQUEST['mid'] = $env[1];
}
$action = $_REQUEST['_act'];
$mid = strtolower($_REQUEST['mid']);
$data = array();
$records = json_decode(stripslashes($_REQUEST['records']), true);
$params = $_REQUEST;
$callback = $_REQUEST['callback'];
foreach(explode(',', 'seqid,callback,mid,_act,PHPSESSID') as $key){
	unset($params[$key]);
}

$modname="MOD_db";
if (file_exists("../models/mod.$mid.php")){
	include_once("../models/mod.$mid.php");
	$modname = "MOD_$mid";
}else{
	include_once("../models/mod.db.php");
}
$mod = new $modname($mid);
if ($env){
	print_r($mod);
}
$output = $mod->$action($params, $records);

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($output) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($output);
}
?>
