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
var $readold = array('id'); 	//using this keys as read before update key indexes
function check_need_vars($arr, $needles, $title='read params'){
	$k = explode(",", $needles);
	foreach($k as $key) if (!isset($arr[$key])) throw new Exception(get_class($this)." $title need $needles, but $key not set.");
}

function getmod($modname, $loadonly=false, $newinstance=false){
	global $__caches;
	if (!$modname){
		throw new Exception(get_class($this).' call getmod without modname.');
	}
	if (!$loadonly){
		$mod = $__caches[mod][$modname];
		if (!$newinstance && $mod){
			$mod->caller = $this;
			return $mod;
		}
	}
	//create new one!
	if (!file_exists("../models/mod.$modname.php")){
		//error!
		throw new Exception("mod $modname not found");
	}
	include_once("../models/mod.$modname.php");
	$name = "MOD_$modname";
	if ($loadonly) return $name;
	$mod = new $name($modname);
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

function log($level, $log){
	echo "LOG@$level $log";
}

function callcmd($cmd, &$cmderror, &$params, &$records, &$extra=null){
//call internal cmd in pconfigs
//extra args using array('prefix'=>array_data or 'key'=>value);
//$cmd canbe "MOD::cmd"
	$cx = explode('::', $cmd);
	if (count($cx)==2){
		$mod = $this->getmod($cx[0]);
		$c = $cx[1];
	}else{
		$mod = $this;
		$c = $cmd;
	}
	$pconfig = $this->get_pconfig($mod, $c);
	if (!$pconfig) throw new Exception(get_class($this)." callcmd $cmd fail: cmd not configurated.");
	$p = $params;
	$p = array_merge($p, $records);
	if ($extra && is_array($extra)) foreach($extra as $k=>$v){
		if (is_array($v)) foreach($v as $name=>$value) $p[$k."_".$name] = $value;
		else $p[$name] = $value;
	}
	$logs = array();
	$r = PHARSER::pharse_cmd($c, $pconfig, $p, $cmderror, $mod, $logs);
	if ($logs){
		foreach($logs as $log){
			$level = str_replace("LOG@", '', $log[record_id]);
			$this->log($level, $log['log']);
		}
	}
	return $r;
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

function getid(&$record){
//return value by $this->readold configed keys
	$v = '';
	foreach($this->readold as $k) $v .= $record[$k];
	return $v;
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
			if ($cmd){//get read result by cmd
				$r = $this->callcmd($cmd, $cmderror, $params, $records);
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
	//kick out unused old records if indeed
	if ($params[_readold])
	if ($records && $this->readold){
		$old = array();
		foreach($records as $i=>$record){
			$v = $this->getid($record);
			foreach($r as $got) if ($v == $this->getid($got)){ $old[$i] = $got; break; }
		}
		$r = $old;
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
		if ($this->readold){
			if ($this->tablewrite) $r = parent::read($params, $records, $this->tablewrite);
			else $r = $this->read(array_merge($params,array(_readold=>true)), $records);
			if ($r[success]) $old_records = $r[data];
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
					if ($cmd){//get update result by cmd
						$extra = array(old=>$old);
						$r = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($r[msg]).");
						}
					}else{// get update result by do_update of sub_classes
						$this->do_update($params, $record, $old);
					}
				}
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_update')){//dbonly
				//can be skipped by set an empty do_update function in subclass
				parent::update($params, $records, $old_records);	 
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get update result by cmd
					foreach($records as $record){
						$old = $old_records[$i];
						$extra = array(old=>$old);
						$r = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($r[msg]).");
						}
					}
				}else{// get update result by do_update of sub_classes
					$this->do_update($params, $records, $old_records);
				}
			}
		}
		if (method_exists($this, 'after_update')){
			$this->after_update($params, $records, $old_records);	
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
					if ($cmd){//get destroy result by cmd
						$r = $this->callcmd($cmd, $cmderror, $params, $record);
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
						$r = $this->callcmd($cmd, $cmderror, $params, $record);
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
					$extra = array(last=>$new_records[count($new_records)-1]);
					if ($cmd){//get create result by cmd
						$new_record = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
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
						$extra = array(last=>$new_records[count($new_records)-1]);
						$new_record = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
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
