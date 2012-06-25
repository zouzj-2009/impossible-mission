<?php
include_once('../models/mod.base.php');
include_once('../models/dbconnector.php');
class MOD_servable extends MOD_base{
var $dbconnector = null;
var $lastpending = array();
var $lastflushtime = 0;
var $begintime = 0;

function __construct($mid, $jobid=null){
	if ($jobid){
		$this->dbconnector = new DBConnector('SERVER', $mid, $jobid);
		$this->lastpending = array(
			msg=>null,
			title=>str_replace("MOD_", "", get_class($this)).".".$jobid,
			number=>0,
		);
		$this->begintime = microtime(true);
	}
	parent::__construct($mid);
}


function getOutput(){
	$t = microtime(true);
	$output = ob_get_contents();
	$during = $t-$this->lastflushtime;
	$elapsed = $t-$this->begintime;
	if (1||$t-$this->lastflushtime>1) ob_flush();
	$this->lastflushtime = $t;
	return array(
		output=>$output,
		during=>$during,
		elapsed=>$elapsed,
	);
}

function sendPending($text, $number=null, $title=null, $msg=null){	
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
