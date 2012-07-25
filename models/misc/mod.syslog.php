<?php
include_once('../../models/core/mod.base.php');
class MOD_misc_syslog extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

function do_read($params){
	if ($params[_download]) return $this->callmod('download', 'read', array(_id=>'syslog'));
	$logfn = $params[_logname]?$params[_logname]:"";
	if (!$logfn) throw new Exception("no logname specified.");
	$logn = $this->logfiles[$logfn];
	if (!$logn && !preg_match('/syslog/', $logfn)) return array(
		success=>false,
		msg=>"visit log '$logfn' not supported.",
	);
	if (!$logn){
		$logn = preg_replace("/[^0-9]/", "", $logfn);
		$logn = $logn?"messages.$logn":"messages";
		exec("cat /var/log/$logn", $out, $ret);
		if ($ret) return array(success=>false, msg=>"log $logfn($logn) not found.");
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
			msg=>"visit log $logfn not implemented.",
		);
	}
}

}
?>
