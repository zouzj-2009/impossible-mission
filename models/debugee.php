<?php
class DEBUGEE{

function trace_in($level, $msg, $varlist){
	//if (!($level & $this->dbglevel))  return;
	$args = func_get_args();
	array_shift($args);
	array_shift($args);
	$trace = "";
	foreach($args as $arg){
		if (isset($arg[0])) $indexed = true; else $indexed = false;
		if (is_array($args)){
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
			}
		}
	}
	$vars = trim($vars, ",");
	$class = get_class($this);
	echo "DBG@$level $class:$msg $vars\n";
}

//end of class
}
