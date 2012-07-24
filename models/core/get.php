<?php
@ob_start();
ob_implicit_flush(true);
if ($_GET['sid']) session_id($_GET['sid']);
session_start();
include_once("../../models/core/dbconnector.php");
include_once("../../models/core/map.php");
date_default_timezone_set('Asia/Chongqing');
declare(ticks=1);
$debugon = true;
$env = getenv('MODTEST');
$preq = getenv("_PREQ_");
if ($env){
	$env = explode(',', $env);
	//debug_print($env);
	$_REQUEST['_act'] = $env[0];
	$_REQUEST['mid'] = $env[1];
	$_REQUEST['taskid'] = $env[2];
	$_REQUEST['_debugsetting'] = getenv('_DEBUG_');
	$_REQUEST['__debugon'] = true; //getenv('_DEBUGON_'); //or just open
	$_SESSION['loginuser'] = array(username=>'admin');
	$debugpreq = $env[3];
	if ($debugpreq){
		$preq = serialize(array(
			action=>$env[0],
			mid=>$env[1],
			taskid=>$env[2],
			params=>json_decode(stripslashes(getenv('params'))),
			records=>json_decode(stripslashes(getenv('records'))),
			caller=>'localconsole',
			sid=>'justfortest',
		));
	}
}

$output = null;
function debug_print($var){
	global $debugon;
	if (!$debugon) return;
	print_r($var);
}
function shut_down_catcher(){
	global $callback; 	
	global $preq;
	if ($preq && $preq[taskid]){
		system("rm /tmp/.tdb/$preq[taskid]/ -rf");
	}
	$error = error_get_last();
	if (!$error) return;
	if ($error[type] != 1) return;
	$o = @ob_get_flush();
	@ob_clean();
	$trace = "File: ".basename($error['file'])."(line-".$error['line'].")";
	//start output
	$output = array(
		success=>false,
		msg=>$error[message],
		trace=>$trace,
		output=>$o,
	);
	if ($callback) {
	    header('Content-Type: text/javascript');
	    echo $callback . '(' . json_encode($output) . ');';
	} else {
	    header('Content-Type: application/x-json');
	    echo json_encode($output);
	}
}

register_shutdown_function('shut_down_catcher');

try{
	if ($preq){//run as serice
		$preq = unserialize($preq);
		$action = $preq[action];
		$mid = $preq[mid];
		$records = $preq[records];
		$params = $preq[params];
		$caller = $preq[clientip];
		$sid = $preq[sid];
		$taskid = $preq[taskid];
		//do security check?
		$modname="MOD_db";
		$modfile = mid2modfile($mid, $modname); 
		if (file_exists($modfile)){
			include_once($modfile);
		}else{
			include_once("../../models/core/mod.db.php");
			$modname = "MOD_db";
		}
		system("mkdir -p /tmp/.tdb/$taskid/");
		echo "start task on $action $mid @$preq[caller]#$taskid ...\n";
		//debug_print($preq);
		//usleep(200);
		//sleep(1);
		$modconfig = $preq[modconfig];
		$modconfig[_runastask] = true;
		$mod = new $modname($mid, $taskid, $modconfig);
		$mod->sendPending("$mid $action xstarted ...", 0);
		//usleep(200);
		if ($action == 'read')
			$output = $mod->$action($params, $records);
		else
			$output = $mod->$action($params, $records);
		echo "$mid.$action.$taskid done.\n";
		$mod->sendDone($output);
		ob_end_flush();
		system("rm /tmp/.tdb/$taskid/ -rf");
	}else{
		$action = $_REQUEST['_act'];
		$mid = strtolower($_REQUEST['mid']);
		@ob_start();
		$data = array();
		if (getenv('REQUEST_METHOD') == 'POST'){
			$r = $_POST;
			$r = array_merge($r, $_FILES);
			$records = array($r);
		}else{
			$records = json_decode(stripslashes($env?trim(getenv('records'), '"'):$_REQUEST['records']), true);
		}

		$params = !$env?$_REQUEST:json_decode(stripslashes(trim(getenv('params'), '"')), true);
		$callback = $_REQUEST['callback'];
		$modconfig = array(debugsetting=>$params[_debugsetting], debugon=>$params[_debugon]);
		foreach(explode(',', '_debugsetting,_debugon,records,seqid,callback,PHPSESSID') as $key){
			unset($params[$key]);
		}

		if (1 && $mid != 'login' && $mid != 'language'){
			if (1&& !$_SESSION['loginuser']){
				throw new Exception("user not login!", -1);
			}
		}

		$taskid=$_REQUEST['taskid'];
		//for test purpose
		if ($env && getenv('COND')){
			$params[_condition] = getenv('COND');
		}
//for debug 
		if ($env && $taskid && $mid == 'modcmd'){
			$params[_cmd] = $taskid;
			$taskid = '';
		}
		if ($env && $taskid && $mid == 'sysconfig'){
			$params[_mod] = $taskid;
			$taskid = '';
		}
//end for debug
		ob_implicit_flush(true);
		//todo: do security check, or will be DOSed!
		if (!$taskid){//todo: service maybe exit before we request, so check existense must be done.
			$modname="MOD_db";
			$modfile = mid2modfile($mid, $modname); 
			if (file_exists($modfile)){
				include_once($modfile);
			}else{
				include_once("../../models/core/mod.db.php");
				$modname = "MOD_db";
			}
			$mod = new $modname($mid, null, $modconfig);
			if (is_a($mod, 'MOD_servable') && $mod->run_as_service($params, $records)){
				$taskid = $mod->get_taskid();
				//$taskid =  md5($modname.$action.date('D M j G:i:s T Y').rand());
				if (is_a($mod, 'MOD_jobtest')) $taskid = 'abcd';
				$preq = serialize(array(
					action=>$action,
					mid=>$mid,
					records=>$records,
					params=>$params,
					modconfig=>$modconfig,
					taskid=>$taskid,
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
						taskid=>$taskid,
						caller=>$preq[caller],
					),
				);
				$caller = $_SERVER[REMOTE_ADDR];
				if ($mod->test_debug(TASKLOG)){
					//for persistent stor trace, link /tmp/.trace to stor dir
					system("mkdir -p /tmp/.trace/$caller/");
					$traceout = "/tmp/.trace/$caller/trace.out.$action.$mid.$taskid";
					$traceerr = "/tmp/.trace/$caller/trace.err.$action.$mid.$taskid";
				}else{
					$traceout = $traceerr = "/dev/null";
				}
				system("/usr/bin/php ../models/get.php task $action $mid by $caller#$taskid >$traceout 2>$traceerr &");
				if ($env){
					echo "_PREQ_=\"".addslashes($preq)."\"\n";
				}
			}else{
				if ($action == 'read')
					$output = $mod->$action($params, $records);
				else
					$output = $mod->$action($params, $records);
			}
		}else{
			if ($env){
				echo "wait pending for $mid.$action.$taskid ...\n";
			}
/*
			$dbc = new DBConnector('CLIENT', $mid, $taskid);
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
			$dif = "mod.task_$taskid";
			$dpath = "/mod/task_$taskid";
			$dbus = new Dbus( Dbus::BUS_SYSTEM );
			$dbus->addWatch($dif);
			$output = null;
			$timeout = 60000;
			$t = 0;
			while ($t<$timeout && !$output) {
				if (1){
					$s = $dbus->waitLoopx(1000);
					$t += 1000;
					if (!$s) continue;
					foreach($s as $signal){
						if (!$signal->matches($dif, "msg0") && !$signal->matches($dif, "done")) continue;
						$output = unserialize($signal->getData());
						echo $output[output];
						break;
					}
				}else{
					$signal = $dbus->waitLoop(1000);
					$t += 1000;
					if (!$signal) continue;
					if (!$signal->matches($dif, "msg0") && !$signal->matches($dif, "done")) continue;
					$output = unserialize($signal->getData());
					echo $output[output];
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
					debug_print($output);
				}
			}else if ($env){ debug_print ($output[output]); }
		}
	}
}catch(Exception $e){
	$output = array(
		success=>false,
		msg=>$e->getMessage(),
		authfail=>$e->getCode()==-1,
	);
	if ($env) debug_print($output);
}
$output[output] = ob_get_flush();
@ob_end_clean();
//start output
if ($callback) {
	header('Content-Type: text/javascript');
	echo $callback . '(' . json_encode($output) . ');';
} else {
	if (getenv('REQUEST_METHOD') == 'POST'){
		header('Content-Type: text/html');
	}else{
		header('Content-Type: application/x-json');
	}
	echo json_encode($output);
}
if ($env){
	echo "\n\nparams:\n";
	debug_print($params);
	echo "\n\nrecords:\n";
	debug_print($records);
	echo "\n\noutput:\n";
	debug_print($output);
}
?>
