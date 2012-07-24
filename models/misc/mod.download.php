<?php
include_once('../../models/core/debugee.php');
class MOD_download extends DEBUGEE{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

function read($params){
	$id = $params[_id];
	switch ($id){
	case 'syslog':
		$psn = trim(file_get_contents('/etc/sysconfig/psn'));
		$date = trim(shell_exec("date -Iminutes|sed 's/:/_/g'"));
		$fn = "log.$psn.$date.tgz";
		@ob_clean();
		@ob_end_clean();
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=$fn");
		header("Content-Description: DMS log file $psn @ $date");
		system("/sbin/preparelog.sh 2>/dev/null >/dev/null");
		if (file_exists("/mnt/ServerVol/zulu/zulu.db")){
			$zuludb = "/mnt/ServerVol/zulu/zulu.db /mnt/ServerVol/filestore/* ";
		}else{
			$zuludb = "/mnt/ResVol0/zulu/zulu.db /mnt/ResVol0/filestore/* ";
		}
		system("tar zcf - /var/log/* /usr/odybk/server/log/* /usr/odybk/vsnap/log/* /etc/mdslog/* $zuludb 2>/dev/null");
		break;
	default:
		throw new Exception("download $id not supported.");
	}
	die(0);
}

}
?>
