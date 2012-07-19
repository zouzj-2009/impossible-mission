<?php
include_once('../models/mod.base.php');
class MOD_logfiles extends MOD_base{

function do_read($params){
	$mod = $this->getmod('syslog');
	$syslogs = explode("\n", shell_exec("ls /var/log/messages*|awk '{print \$1}'"));
	foreach ($syslogs as $i=>$syslog){
		$ln = str_replace("messages", "syslog", basename($syslog));
		$mod->logfiles[$ln] = $syslog;
	}
	$r = array();
	foreach ($mod->logfiles as $ln=>$fn){
		$r[] = array(logname=>$ln, filename=>$fn);
	}
	return array(
		success=>true,
		data=>$r,
	);
}

}
?>
