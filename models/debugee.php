<?php
class DEBUGEE{
var $__debugon = false;
var $__debuglevel = 0;
var $__debugsetting = '';
var $__levelmap = array(
	DBG 	=> 0x80000000,
	INFO	=> 0x00000001,
	TRACE	=> 0x00000002,
	BGTRACE	=> 0x00000004,
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
		$ll |= $ln?$ln:0;
	}
	return $this->__debuglevel & $ll;
}

function trace($leve, $msg){
	if (!$this->__debugon) return;
	if (!$this->test_debug($level)) return;
	$class = get_class($this);
	echo "DBGEE@$level $class: $msg\n";
}

function trace_in($level, $msg, $varlist){
	if (!$this->__debugon) return;
	if (!$this->test_debug($level)) return;
	//if (!($level & $this->dbglevel))  return;
	$args = func_get_args();
	array_shift($args);
	array_shift($args);
	$trace = "";
	$vars = $this->args_to_string($args);
	$class = get_class($this);
	echo "DBGEE@$level $class: $msg($vars)\n";
}

//end of class
}
