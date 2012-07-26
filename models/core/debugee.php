<?php
class DEBUGEE{
var $__debugon = false;
var $__debuglevel = 0;
var $__debugsetting = '';
var $__levelmap = array(
	DBG 	=> 0x80000000,		//debug purpose
	INFO	=> 0x00000001,		//info, should be logged
	TRACE	=> 0x00000002,		//trace in,out,process
	TASK	=> 0x00000004,		//service task
      	TASKLOG	=> 0x00000008,		//log service task to file
      	TASKDBG	=> 0x00000010,		//debug task using taskid 'taskdebug'
	PHDBG	=> 0x00000020,		//debug pharser (change pconfig)
	PHDBGALL=> 0x00000040,		//debug pharser all(change pconfig)
	NETWORK	=> 0x00000080,		//show network request/response in proxy
	CMDDBG	=> 0x00000100,		//show cmd output for debug purpose
	TRACEALL=> 0x00000200,		//show cmd output for debug purpose
	TRACEDB	=> 0x00000400,		//show db sql state
	MODRET	=> 0x00000800,		//show callmod return
	CMDRET	=> 0x00001000,		//show callcmd return
);

function __construct($debugon, $level='INFO'){
	$this->__debugon = $debugon;
	$ls = explode(',', $level);
	foreach($ls as $l){
		$ln = $this->__levelmap[$l];
		$this->__debuglevel |= $ln?$ln:0;
	}
	$this->__debugsetting = $level;
}

function args_to_string($args){
	$vars = "";
	foreach($args as $arg){
		if (is_array($arg)){
			if (isset($arg[0])) $indexed = true; else $indexed = false;
			$vars .= "[";
			foreach($arg as $k=>$v){
				$av = "";
				if (is_array($v)){
					foreach($v as $vk=>$vv) $av .= "$vk:$vv,";
					$av = "{".trim($av, ",")."}";
				}else $av = $v;
				if ($indexed) $vars .= "$av,"; else $vars .= "$k:$av,";
			}
			$vars = trim($vars, ",");
			$vars .= "],";
		}else{
			if (is_object($arg)){
				$vars .= 'Object '.get_class($arg).","; 
			}else $vars .= "$arg,";
		}
	}
	$vars = trim($vars, ",");
	return $vars;
}

function test_debug($level){
	$ls = explode(',', $level);
	$ll = 0;
	foreach($ls as $l){
		$ln = $this->__levelmap[$l];
		if ($this->__debuglevel & $ln) return $l;
	}
	return false;
}

function tracemsg($level, $msg){
	if (!$this->__debugon) return;
	$dl = $this->test_debug($level);
	if (!$dl) return;
	$class = get_class($this);
	echo "DBGEE@$dl $class: $msg\n";
}

function trace_in($level, $msg){
	if (!$this->__debugon) return;
	$dl = $this->test_debug($level);
	if (!$dl) return;
	//if (!($level & $this->dbglevel))  return;
	$args = func_get_args();
	array_shift($args);
	array_shift($args);
	$trace = "";
	$vars = $this->args_to_string($args);
	$class = get_class($this);
	echo "DBGEE@$dl $class: $msg($vars)\n";
}

//end of class
}
