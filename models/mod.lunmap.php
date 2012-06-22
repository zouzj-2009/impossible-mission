<?php
include_once('../models/mod.db.php');
include_once('../models/pharser.php');
class MOD_lunmap extends MOD_db{

var $pconfigs = array(
	'getlunmap'=>array(
		//cmd=>"cat /proc/scsi_target/iscsi_target/lunmapping", 
		cmd=>'cat /tmp/lunmap',
		pconfig=>array(
			//pharse config:
			type=>'one_record_per_line',
			ignore=>'/^ *$|^ena|^dis/',
			fieldsep=>'/  */',
			fieldnames=>'_ignore_,sourceip,_,netmask,,targetid,access'
		)
	),	
);

function read($params, $records){
//	$r = PHARSER::pharse_cmd("cat /proc/scsi_target/iscsi_target/lunmapping", array(
	$cmd = $this->pconfigs[getlunmap];
	$r = PHARSER::pharse_cmd($cmd[cmd],$cmd[pconfig], $presult, $cmdresult, $raw, $trace);
	if ($presult){
		return array(
			success=>false,
			msg=>"pharse cmd result error: $presult"
		);
	}
	return array(
		success=>true,
		data=>$r
	);
}

function update($params, $records){
	return MOD_db::pending_test($params, $records);
}
function destroy($params, $records){
	return MOD_db::pending_test($params, $records);
}
function create($params, $records){
	return MOD_db::pending_test($params, $records);
}

function pending_test($params, $records){
	global $_REQUEST;
	$count = 2;
	if ($_REQUEST['seqid']) sleep(2);
	if (0 || $_REQUEST['seqid'] >= $count)
		$output=array(success=>false, msg=>'server job fail.');
	else
		$output=array(success=>false, pending=>array(
			seq=>$_REQUEST['seqid'],
			msg=>'big job pending...'.$_REQUEST['seqid'],
			text=>'server doing '.$_REQUEST['_act'].' '.($_REQUEST['seqid']/$count*100).'%',
			title=>'Server Doing Title',
			number=>$_REQUEST['seqid']/$count
			));
	return $output;
}

}
?>
