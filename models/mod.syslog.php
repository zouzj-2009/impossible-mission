<?php
include_once('../models/mod.base.php');
class MOD_syslog extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

function read($params){
	if ($params[_download]) return $this->callmod('download', 'read', array(_id=>'syslog'));
	$logname = $params[_logname];
	$fn = $this->logfiles[$logname];
	if (!$fn && !strstr($logname, 'syslog')) return array(
		success=>false,
		msg=>"visit log $logname not supported.",
	);
	if (!$fn){
		$fn = preg_replace("/[^0-9]/", "", $logname);
		$fn = $fn?"messages.$fn":"messages";
		exec("cat /var/log/$fn", $out, $ret);
		if ($ret) return array(success=>false, msg=>"log $logname($fn) not found.");
		$r = array();
		$last = array();
		foreach($out as $i=>$line){
			if (preg_match("/^([a-zA-Z]*) *([0-9]*) ([0-9:]*) ([^:]*): (.*)/", $line, $m)){
				$last = array(line=>$i+1, date=>$m[1]." ".$m[2]." ".$m[3], facility=>$m[4], message=>$m[5]);
			}else{
				$last[message] = $line;
				$last[facility] = 'unknown';
				$last[line] = $i+1;
			}
			$r[] = $last;
		}
		return array(
			success=>true,
			data=>$r,
		);
	}else{
		//todo: cmd pharser for other patter, and autocolumn in return data's meta
		return array(
			success=>false,
			msg=>"visit log $logname not implemented.",
		);
	}
}

}
?>
