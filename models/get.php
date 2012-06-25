<?php
include_once("../models/dbconnector.php");
declare(ticks=1);
$debugon = false;
$env = getenv('MODTEST');
$preq = getenv("_PREQ_");
if ($env){
	$env = explode(',', $env);
	//print_r($env);
	$_REQUEST['_act'] = $env[0];
	$_REQUEST['mid'] = $env[1];
	$_REQUEST['jid'] = $env[2];
	$debugpreq = $env[3];
	if ($debugpreq){
		$preq = serialize(array(
			action=>$env[0],
			mid=>$env[1],
			jid=>$env[2],
			params=>json_decode(stripslashes(getenv('params'))),
			records=>json_decode(stripslashes(getenv('records'))),
			caller=>'localconsole',
			sid=>'justfortest',
		));
	}
}

$output = null;
try{
	if ($preq){//run as serice
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
		@ob_start();
		ob_implicit_flush(true);
		echo "start service of $mid.$action@$jid ...\n";
		if ($debugon){
			print_r($preq);
		}
		//usleep(200);
		$mod = new $modname($mid, $jid);
		$mod->sendPending("$mid $action xstarted ...", 0);
		//usleep(200);
		$output = $mod->$action($params, $records);
		echo "$mid.$action.$jid done.\n";
		$mod->sendDone($output);
		ob_end_flush();
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

		$jid=$_REQUEST['jid'];
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
				//system("../models/fork.sh php ../models/get.php $mid $action $jid");
				system("/usr/bin/php ../models/get.php service $mid $action $jid >/dev/null 2>/dev/null&");
				if ($env){
					echo "_PREQ_=\"".addslashes($preq)."\"\n";
				}
			}else{
				$output = $mod->$action($params, $records);
			}
		}else{
			if ($env){
				echo "wait pending for $mid.$action.$jid ...\n";
			}
/*
			$dbc = new DBConnector('CLIENT', $mid, $jid);
			$output = $dbc->watch();
			if (!$output){
				$output = array(
					success=>false,
					msg=>"dbc time out!",
				);
			}
			if (!$output[pending]){
				if ($env) echo "send done ack.\n";
				$dbc->ackDone($output);
			}
*/
			$dif = "mod.$mid.j$jid";
			$dpath = "/mod/$mid/j$jid";
			$dbus = new Dbus( Dbus::BUS_SESSION );
			$dbus->addWatch( "mod.$mid.j$jid" );
			$output = null;
			$timeout = 60000;
			$t = 0;
			while ($t<$timeout && !$output) {
			if (0){
				$s = $dbus->waitLoopx(1000);
				if (!$s) continue;
				foreach($s as $signal){
					if (!$signal->matches("mod.$mid.j$jid", "msg0")
						&& !$signal->matches("mod.$mid.j$jid", "done")) continue;
					$output = unserialize($signal->getData());
					break;
				}
			}else{
				$signal = $dbus->waitLoop(1000);
				$t += 1000;
				if (!$signal) continue;
				if (!$signal->matches("mod.$mid.j$jid", "msg0")
					&& !$signal->matches("mod.$mid.j$jid", "done")) continue;
				$output = unserialize($signal->getData());
				break;
			}
			}
			if (!$output){
				$output = array(
					success=>false,
					msg=>"dbus fail",
				);
			}
			if ($output && !$output[pending]){
				$donesignal = new DbusSignal(
					$dbus,
					$dpath,
					$dif,
					'done'
				);
				$donesignal->send(serialize($output));
				if ($env){
					echo "send ack done.\n";
					print_r($output);
				}
			}else if ($env){ print_r ($output[output]); }
		}
	}
}catch(Exception $e){
	$output = array(
		success=>false,
		msg=>$e->getMessage(),
	);
}
if ($env) die("done\n");
//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($output) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($output);
}
if (0&& $env){
	echo "\n\nparams:\n";
	print_r($params);
	echo "\n\nrecords:\n";
	print_r($records);
	echo "\n\noutput:\n";
	print_r($output);
}
?>
