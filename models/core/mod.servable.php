<?php
include_once('../../models/core/mod.base.php');
include_once('../../models/core/dbconnector.php');
class MOD_servable extends MOD_base{
var $dbconnector = null;
var $lastpending = array();
var $lastflushtime = 0;
var $begintime = 0;

function __construct($mid, $taskid=null, $modconfig){
	if ($taskid){
		$this->dbconnector = new DBConnector('SERVER', $mid, $taskid);
		$this->lastpending = array(
			msg=>null,
			title=>str_replace("_", ".", str_replace("MOD_", "", get_class($this))),
			number=>0,
		);
		$this->begintime = microtime(true)-1;
	}
	parent::__construct($mid, $taskid, $modconfig);
}

//subclass can override it to assign a well-known taskid
function get_taskid(){
	if ($this->test_debug(TASKDBG)) return 'taskdbg';
	return md5($modname.$action.date('D M j G:i:s T Y').rand());
}

function getOutput(){
	$t = microtime(true);
	$output = @ob_get_contents();
	@ob_start();
	$during = $this->lastflushtime?$t-$this->lastflushtime:0;
	$elapsed = $this->begintime?$t-$this->begintime:0;
	//if (1||$t-$this->lastflushtime>1) ob_flush();
	$this->lastflushtime = $t;
	return array(
		output=>$output,
		during=>number_format($during,2),
		elapsed=>number_format($elapsed,2),
	);
}

function sendCmdStart($cmd, $params){
	//todo: show by predefined info...
	if ($this->test_debug(TASKLOG)) $info = "start $cmd(".$this->args_to_string(array($params)).") ...";
	else $info = "running $cmd";
	$this->sendPending($info);
}
function sendCmdEnd($cmd, $params, $cmderr){
	if (!$this->test_debug(TASKLOG)) return ;
	$info = "endof $cmd, return $cmderr.";  
	$this->sendPending($info);
}
function sendPending($text, $number=null, $title=null, $msg=null){	
	$this->tracemsg(TASKLOG, "$text, $number, $title, $msg");
	if ($number) $this->lastpending[number] = $number;
	if ($title) $this->lastpending[title] = $title;
	if ($msg) $this->lastpending[msg] = $msg;
	$this->lastpending[text] = $text;
	if (!$this->dbconnector){
		print_r($this->lastpending);
		return;
	}
	$output=$this->getOutput();
	$this->dbconnector->sendMsg(array(
		success=>false,
		pending=>$this->lastpending,
		output=>$output[output],
		during=>$output[during],
		elapsed=>$output[elapsed],
		//todo: should be debugon/off
	));
}

function sendDone($result){
	if (!$this->dbconnector){
		print_r($result);
		return;
	}
	$out = $this->getOutput();
	$result = array_merge($result, $out);
	//$this->dbconnector->sendMsg($result);
	$this->dbconnector->waitDone($result);
}

//end of class
}
?>
