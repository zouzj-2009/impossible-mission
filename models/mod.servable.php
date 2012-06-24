<?php
include_once('../models/mod.base.php');
include_once('../models/dbconnector.php');
class MOD_servable extends MOD_base{
var $dbconnector = null;
var $lastpending = array();

function __construct($mid, $jobid=null){
	if ($jobid){
		$this->dbconnector = new DBConnector('SERVER', $mid, $jobid);
		$this->lastpending = array(
			msg=>null,
			title=>str_replace("MOD_", "", get_class($this)).".".$jobid,
			number=>0,
		);
	}
	parent::__construct($mid);
}

function sendPending($msg, $detail=null, $number=null, $title=null){	
	if ($detail) $this->lastpending[detail] = $detail;
	if ($number) $this->lastpending[number] = $number;
	if ($title) $this->lastpending[title] = $title;
	$this->lastpending[msg] = $msg;
	if (!$this->dbconnector){
		print_r($this->lastpending);
		return;
	}
	$this->dbconnector->sendMsg(array(
		success=>false,
		pending=>$this->lastpending,
	));
}

function sendDone($result){
	if (!$this->dbconnector){
		print_r($result);
		return;
	}
	$this->dbconnector->sendMsg($result);
}

//end of class
}
?>
