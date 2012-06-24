<?php
include_once("../models/dbconnector.php");
$env = getenv('MODTEST');
if ($env){
	$env = explode(',', $env);
	//print_r($env);
	$_REQUEST['_act'] = $env[0];
	$_REQUEST['mid'] = $env[1];
}
$preq = getenv("_PREQ_");
$output = null;
if ($preq){//run as serice}
	$preq = unserialize($preq);
	$action = $preq[action];
	$mid = $preq[mid];
	$records = $preq[records];
	$params = $preq[params];
	$caller = $preq[clientip];
	$sid = $preq[sid];
	$jid = $preq[jid];
	//do security check?
	$modname="MOD_db";
	if (file_exists("../models/mod.$mid.php")){
		include_once("../models/mod.$mid.php");
		$modname = "MOD_$mid";
	}else{
		include_once("../models/mod.db.php");
	}
	$mod = new $modname($mid, $jid);
	$output = $mod->$action($params, $records);
	$mod->sendDone($output);
}else{
	$action = $_REQUEST['_act'];
	$mid = strtolower($_REQUEST['mid']);
	$data = array();
	$records = json_decode(stripslashes($env?trim(getenv('records'), '"'):$_REQUEST['records']), true);
	$params = !$env?$_REQUEST:json_decode(stripslashes(trim(getenv('params'), '"')), true);
	$callback = $_REQUEST['callback'];
	foreach(explode(',', 'seqid,callback,PHPSESSID') as $key){
		unset($params[$key]);
	}

	$jid=$params['jid'];
	//todo: do security check, or will be DOSed!
	if (!$jid){//todo: service maybe exit before we request, so check existense must be done.
		$modname="MOD_db";
		if (file_exists("../models/mod.$mid.php")){
			include_once("../models/mod.$mid.php");
			$modname = "MOD_$mid";
		}else{
			include_once("../models/mod.db.php");
		}
		$mod = new $modname($mid);
		if (is_a($mod, 'MOD_servable') && $mod->run_as_service($params, $records)){
			$jid =  md5($modname.$action.date('D M j G:i:s T Y'));
			$preq = serialize(array(
				action=>$action,
				mid=>$mid,
				records=>$records,
				params=>$params,
				jid=>$jid,
				sid=>$_REQUEST[PHPSESSID],
				caller=>$_SERVER['REMOTE_ADDR'],
			));
			putenv("_PREQ_=$preq");
			system("nohup php ../models/get.php");
			$output = array(
				success=>false,
				pending=>array(
					msg=>"$modname $action started",
					text=>"$modname is $action"."ing, wait please ...",
					title=>"$modname $action",
					number=>0,
					jid=>$jid,
				),
			);
			if ($env){
				echo "_PREQ_=\"".addslashes($preq)."\"\n";
				
			}
		}else{
			$output = $mod->$action($params, $records);
		}
	}else{
		$dbus = new DBConnector('CLIENT', $mid, $jid);
		$output = $dbus->watch('msg');
		if (!$output){
			$output = array(
				success=>false,
				msg=>$dbus->failmsg,
			);
		}
	}
}

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
