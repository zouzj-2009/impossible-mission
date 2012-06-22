<?php
$env = getenv('MODTEST');
if ($env){
	$env = explode(',', $env);
	//print_r($env);
	$_REQUEST['_act'] = $env[0];
	$_REQUEST['mid'] = $env[1];
}
$action = $_REQUEST['_act'];
$mid = strtolower($_REQUEST['mid']);
$data = array();
$records = json_decode(stripslashes($env?trim(getenv('records'), '"'):$_REQUEST['records']), true);
$params = !$env?$_REQUEST:json_decode(stripslashes(trim(getenv('params'), '"')), true);
print_r($params);
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
if ($env){
	echo "\n\nparams:\n";
	print_r($params);
	echo "\n\nrecords:\n";
	print_r($records);
	echo "\n\noutput:\n";
	print_r($output);
}
?>
