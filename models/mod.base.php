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
var $tableread = null;	//table or view for reading
var $tablewrite = null; //table for create/update/destroy
var $synctodb = array(); //sync configuration
var $defaultcmds = array(
	read=>null,
);


function getmod($modname, $newinstance=false){
	global $__caches;
	if (!$modname){
		throw new Exception(get_class($this).' call getmod without modname.');
	}
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


function callcmd($cmd, $args=array(), &$data=null){
//call internal cmd in pconfigs
	if (!$this->pconfigs[$cmd]) throw new Exception(get_class($this)." callcmd $cmd fail: cmd not configurated.");
	$pconfig = $this->pconfigs[$cmd];
	$r = PHARSER::pharse_cmd($pconfig, $args, $cmdresult);
	if ($data !== null) $data = $r;
	return $cmdresult;
}

//todo: call mod on other server!
function callmod_remote($serverconfig, $modname, $action, $params, $records, $simpleresult=true){
}

function callmod($modname, $action, $params, $records, $simpleresult=true){
	$mod = $this->getmod($modname);
	$r = $mod->$action($params, $records);
	if (!$simpleresult) return $r;
	if ($action == 'read') return $r[data];
	if ($r[data]) return $r[data];//created(new), updated(old), destroied(old)
	return $r[success];
}

//subclass advice:
//If has db, use before_read to change or add params. use after_read to fix result.
//IF has not db, use do_read/cmd to get info. usually, no before/after_read needed, all in do_read.
//don't overwrite this method generally.
function read($params, $records){
	$cmd = $this->defaultcmds[read];
	$msg = null;
	try{
		if (method_exists($this, 'before_read')){
			$this->before_read($params, $records);	
		}
		if (!$cmd && !method_exists($this, 'do_read')){//dbonly
			$r = parent::read($params, $records);
		}else{
			$pconfig = $this->pconfigs[$cmd];
			if ($cmd){//get read result by cmd
				$r = PHARSER::pharse_cmd($pconfig, $params, $cmdresult);
			}else{// get read result by do_read of sub_classes
				$r = $this->do_read($params, $records);
			}
			//todo: howto
			if ($this->synctodb){
				$msg = $this->syncdb($r, 'read', $this->synctodb);
			}
		}
		if (method_exists($this, 'after_read')){
			$r = $this->after_read($params, $r[data], $records);	
		}
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
	return array(
		success=>true,
		data=>$r,
		msg=>$msg?$msg:"$this->mid read done.",
	);
}

//subclass advice:
//If has db, use before_update to validate the change, or do real change.
//	if partically fail, the params should carry out the failed records(_failed_records_), and records has been modifed as validated records.
//	and params[_skip_do_update_]/params[_skip_after_update_] tell what to do next
//	or an exception was thrown.
//IF has not db, use do_update/cmd to make change. usually, no before_update/after_update needed, all in do_update.
//don't overwrite this method generally.
function update($params, $records){
	$cmd = $this->defaultcmds[update];
	$msg = null;
	$old_records = null;
	try{
		if ($this->tablewrite){//has db
			$old_records = parent::read($params, $records, $this->tablewrite);
		}
		if (method_exists($this, 'before_update')){
			$this->before_update($params, $records, $old_records);	
		}
		if (!$cmd && !method_exists($this, 'do_update')){//dbonly
			//can be skipped by set an empty do_update function in subclass
			parent::update($params, $records);	 
		}else{
			$pconfig = $this->pconfigs[$cmd];
			if ($cmd){//get update result by cmd
				$r = PHARSER::pharse_cmd($pconfig, $params, $cmdresult);
				if (!$cmdresult){
					throw new Exception(get_class($this)." update fail: $cmd return fail($r[msg]).");
				}
			}else{// get update result by do_update of sub_classes
				$this->do_update($params, $records, $old_records);
			}
			//todo: howto
			if ($this->synctodb){
				$msg = $this->syncdb($r, 'update', $this->synctodb);
			}
		}
		if (method_exists($this, 'after_update')){
			$this->after_update($params, $records, $old_records);	
		}
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
			failed=>$params[_failed_records_],
			old=>$old_records,
			updated=>$records,
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid update done.",
		data=>$old_records,
	);
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
