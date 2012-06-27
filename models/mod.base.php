<?php
include_once('../models/mod.db.php');
include_once('../models/pharser.php');
class MOD_base extends MOD_db{

var $caller;
/* sample
static $pconfigs = array(
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
);
*/
var $tableread = null;	//table or view for reading
var $tablewrite = null; //table for create/update/destroy
var $synctodb = array(); //sync configuration
var $defaultcmds = array(
	read=>null,
);
var $batchsupport = array(// false|true|'one_by_one'
	update=>true, create=>'one_by_one', destroy=>true,
);
function check_need_vars($arr, $needles, $title='read params'){
	$k = explode(",", $needles);
	foreach($k as $key) if (!isset($arr[$key])) throw new Exception(get_class($this)." $title need $needles, but $key not set.");
}

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


function get_pconfig($class, $cmd)
{
	if (is_object($class)) $modname = get_class($class); else $modname = $class;
	$pconfigs = $modname::$pconfigs;
	$pconfig = $pconfigs[$cmd];
	$refcmd = $pconfig['refcmd'];
	if (!$refcmd) return $pconfig;
	$r = explode('::', $refcmd);
	$rconfig = null;
	if (count($r)==2){
		$rconfig = $this->get_pconfig($r[0], $r[1]); //can get from other class, rescurively
	}else{
		$rconfig = $pconfigs[$refcmd];
	}	
	if (!$rconfig) throw new Exception("$cmd's reference cmd $refcmd's pharser config not exists!");
	$pconfig = array_merge($rconfig, $pconfig);
	return $pconfig;
}

function callcmd($cmd, $args=array(), &$data=null){
//call internal cmd in pconfigs
	if (!$this->get_pconfig($this, $cmd)) throw new Exception(get_class($this)." callcmd $cmd fail: cmd not configurated.");
	$pconfig = $this->get_pconfig($this, $cmd);
	$r = PHARSER::pharse_cmd($cmd, $pconfig, $args, $cmderror);
	if ($data !== null) $data = $r;
	return $cmderror;
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
	$pconfig = $this->get_pconfig($this, $cmd);
	$msg = null;
	try{
		if (method_exists($this, 'before_read')){
			$this->before_read($params, $records);	
		}
		if (!$cmd && !method_exists($this, 'do_read')){//dbonly
			$r = parent::read($params, $records);
		}else{
			if ($cmd){//get read result by cmd
				$r = PHARSER::pharse_cmd($cmd, $pconfig, $params, $cmderror);
			}else{// get read result by do_read of sub_classes
				$r = $this->do_read($params, $records);
			}
			//todo: howto
			if ($this->synctodb){
				$msg = $this->syncdb($r, 'read', $this->synctodb);
			}
		}
		if (method_exists($this, 'after_read')){
			$r = $this->after_read($params, $r, $records);	
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
	$old_records = array();
	try{
		if (!$this->batchsupport['update'] && count($records)>1) 
			throw new Exception("batch update not support, but ".count($records)." are supplied.");
		if (!$records)//so, the destroy has to do read before destroy records.
			throw new Exception("update, but null records supplied.");
		if ($this->tablewrite){//has db
			$old_records = parent::read($params, $records, $this->tablewrite);
		}
		if (method_exists($this, 'before_update')){
			$next = $this->before_update($params, $records, $old_records);	
			if ($next == 'return'){
				return array(
					success=>true, //if false, exp was thrown already.
					old=>$old_records,
					data=>$records,
				);
			}
		}
		if ($this->batchsupport['update'] == 'one_by_one'){
			//old_records mustbe same index as records!
			foreach($records as $i=>$record){
				$old = $old_records[$i];
				if (!$cmd && !method_exists($this, 'do_update')){//dbonly
					//can be skipped by set an empty do_update function in subclass
					parent::update($params, $record, $old);	 
				}else{
					$pconfig = $this->get_pconfig($this, $cmd);
					$oldx = array();
					if (is_array($old)) foreach($old as $k=>$v) $oldx["old_$k"] = $v;
					$p = $params;
					$p = array_merge($p, $record, $oldx);
					if ($cmd){//get update result by cmd
						$r = PHARSER::pharse_cmd($cmd, $pconfig, $p, $cmderror);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($r[msg]).");
						}
					}else{// get update result by do_update of sub_classes
						$this->do_update($params, $record, $old);
					}
				}
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_destroy')){//dbonly
				//can be skipped by set an empty do_update function in subclass
				parent::update($params, $records, $old_records);	 
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get update result by cmd
					foreach($records as $record){
						$old = $old_records[$i];
						$oldx = array();
						if (is_array($old)) foreach($old as $k=>$v) $oldx["old_$k"] = $v;
						$p = $params;
						$p = array_merge($p, $record, $oldx);
						$r = PHARSER::pharse_cmd($cmd, $pconfig, $p, $cmderror);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($r[msg]).");
						}
					}
				}else{// get update result by do_update of sub_classes
					$this->do_destroy($params, $records, $old_records);
				}
			}
		}
		if (method_exists($this, 'after_update')){
			$this->after_destroy($params, $records, $old_records);	
		}
	}catch(Exception $e){//rollback?
		return array(
			success=>false,
			msg=>$e->getMessage(),
			data=>$records,
			old=>$old_records,
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid update done.",
		data=>$records,
		old=>$old_records,
	);
}

function destroy($params, $records){
	$cmd = $this->defaultcmds[destroy];
	$msg = null;
	$next = 'continue';
	$old_records = $records;
	try{
		if (!$this->batchsupport['destroy'] && count($records)>1) 
			throw new Exception("batch destroy not support, but ".count($records)." are supplied.");
		if (!$records)//so, the destroy has to do read before destroy records.
			throw new Exception("destroy, but null records supplied.");
		if (method_exists($this, 'before_destroy')){
			$next = $this->before_destroy($params, $records);	
			if ($next == 'return'){
				return array(
					success=>true,
					msg=>$msg?$msg:"$this->mid destroy done.",
					data=>$old_records,
				);
			}
		}
		if ($this->batchsupport['destroy'] == 'one_by_one'){
			foreach($records as $record){
				$old = $record;
				if (!$cmd && !method_exists($this, 'do_destroy')){//dbonly
					//can be skipped by set an empty do_update function in subclass
					parent::destroy($params, $record);	 
				}else{
					$pconfig = $this->get_pconfig($this, $cmd);
					$p = $params;
					$p = array_merge($p, $record);
					if ($cmd){//get destroy result by cmd
						$r = PHARSER::pharse_cmd($cmd, $pconfig, $p, $cmderror);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($r[msg]).");
						}
					}else{// get destroy result by do_destroy of sub_classes
						$this->do_destroy($params, $record);
					}
				}
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_destroy')){//dbonly
				//can be skipped by set an empty do_destroy function in subclass
				parent::destroy($params, $records);	 
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get destroy result by cmd
					foreach($records as $record){
						$p = $params;
						$p = array_merge($p, $record);
						$r = PHARSER::pharse_cmd($cmd, $pconfig, $p, $cmderror);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($r[msg]).");
						}
					}
				}else{// get destroy result by do_destroy of sub_classes
					$this->do_destroy($params, $records);
				}
			}
		}
		if (method_exists($this, 'after_destroy')){
			$this->after_destroy($params, $old_records);	
		}
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
			old=>$old_records,
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid destroy done.",
		data=>$old_records,
	);
}

function create($params, $records){
	$cmd = $this->defaultcmds[create];
	$msg = null;
	$next = 'continue';
	$new_records = array();
	try{
		if (!$this->batchsupport['create'] && count($records)>1) 
			throw new Exception("batch create not support, but ".count($records)." are supplied.");
		if (!$records)
			throw new Exception("create, but null records supplied.");
		if (method_exists($this, 'before_create')){
			$next = $this->before_create($params, $records, $new_records);	
			if ($next == 'return'){
				return array(
					success=>true,
					msg=>$msg?$msg:"$this->mid create done.",
					data=>$new_records,
				);
			}
		}
		if ($this->batchsupport['create'] == 'one_by_one'){
			foreach($records as $record){
				$old = $record;
				if (!$cmd && !method_exists($this, 'do_create')){//dbonly
					//can be skipped by set an empty do_create function in subclass
					//send new_records(just created) for reference!
					parent::create($params, $record, $new_record, $new_records);	 
				}else{
					$pconfig = $this->get_pconfig($this, $cmd);
					$p = $params;
					$last_created = $new_records[count($new_records)-1];
					$lastx = array();
					if ($last_created) foreach($last_created as $k=>$v) $lastx["last_$k"]=$v;
					$p = array_merge($p, $record, $lastx);
					if ($cmd){//get create result by cmd
						$new_record = PHARSER::pharse_cmd($cmd, $pconfig, $p, $cmderror);
						if ($cmderror){
							throw new Exception(get_class($this)." create fail: $cmd return fail($r[msg]).");
						}
					}else{// get create result by do_destroy of sub_classes
						$this->do_create($params, $record, $new_record, $new_records);
					}
				}
				$new_records[] = $new_record;
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_create')){//dbonly
				//can be skipped by set an empty do_create function in subclass
				parent::create($params, $records, $new_records);	 
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get create result by cmd
					foreach($records as $record){
						$pconfig = $this->get_pconfig($this, $cmd);
						$p = $params;
						$last_created = $new_records[count($new_records)-1];
						$lastx = array();
						if ($last_created) foreach($last_created as $k=>$v) $lastx["last_$k"]=$v;
						$p = array_merge($p, $record, $lastx);
						$new_record = PHARSER::pharse_cmd($cmd, $pconfig, $p, $cmderror);
						if ($cmderror){
							throw new Exception(get_class($this)." create fail: $cmd return fail($r[msg]).");
						}
					}
				}else{// get create result by do_create of sub_classes
					$this->do_create($params, $records, $new_records);
				}
			}
		}
		if (method_exists($this, 'after_create')){
			$this->after_create($params, $old_records);	
		}
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid create done.",
		data=>$new_records,
	);
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
