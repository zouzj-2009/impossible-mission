<?php
include_once('../models/mod.db.php');
include_once('../models/pharser.php');
class MOD_base extends MOD_db{

var $caller;
var $pconfigs = array(
/* sample
	'getlunmap'=>array(
		//cmd=>"cat /proc/scsi_target/iscsi_target/lunmapping", 
		cmd=>'cat /tmp/lunmap',
		pconfig=>array(
			//pharse config:
			type=>'one_record_per_line',
			ignore=>'/^ *$|^ena|^dis/',
			fieldsep=>'/ +/',
			fieldnames=>'_ignore_,sourceip,_,netmask,,targetid,access'
		)
	),	
*/
);
var $synctodb = array(); //sync configuration
var $defaultcmds = array(
	read=>null,
);

function getmod($modname, $newinstance=false){
	global $__caches;
	$mod = $__caches[mod][$modname];
	if (!$newinstance && $mod){
		$mod->caller = $this;
		return $mod;
	}
	//create new one!
	if (!file_exists("../models/mod.$modname.php")){
		//error!
		throw new Exception("mod $modname not found");
	}
	include_once("../models/mod.$modname.php");
	$modname = "MOD_$modname";
	$mod = new $modname($modname);
	$mod->caller = $this;
	return $mod;
}

//todo: call mod on other server!
function callmod_remote($server, $auth, $modname, $action, $params, $records, $simpleresult=true){
}

function callmod($modname, $action, $params, $records, $simpleresult=true){
	$mod = $this->getmode($modname);
	$r = $mod->$action($params, $records);
	if (!$simpleresult) return $r;
	if ($action == 'read') return $r[data];
	if ($r[data]) return $r[data];//created(new), updated(old), destroied(old)
	return $r[success];
}

function read($params, $records){
	$cmd = $this->defaultcmds[read];
	if (!$cmd){//dbonly
		return parent::read($params, $records);
	}
	$pcmd = $this->pconfigs[$cmd];
	$r = PHARSER::pharse_cmd($pcmd[cmd],$pcmd[pconfig], $presult, $cmdresult, $raw, $trace);
	if ($presult){
		return array(
			success=>false,
			msg=>"pharse cmd result error: $presult"
		);
	}
	$msg = null;
	if ($this->synctodb){
		$msg = $this->syncdb($r, $this->synctodb);
	}
	return array(
		success=>true,
		data=>$r,
		msg=>$msg?$msg:"$this->mid read done.",
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