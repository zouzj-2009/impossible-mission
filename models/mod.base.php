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
var $readbeforeupdate = true;	//weather read old data before update
var $readbeforedestroy = false;	//weather read old data before destroy

var $savechangeconfig = array(
	tablename=>'sysconfig', //table for store changing config data.
	autocreate=>true,	//auto create records for not-existed update.
	//configuration data will be stored in sysconfig or other table
	//if in 'sysconfig' table, these fields will be used, and operation will be done by mod.base
	//mod:		name of the mod, also uniqid of configuation data
	//lastboot:	value of lastbootup config, will using read after bootup
	//lastshutdown:	value of lastshutdow(stored before shutdown)
	//last:		value of last change
	//current:	value of current setting
);

function strip_unsaving(&$records){
//strip needn't save fields not configed in $this->saving_fields

	if (!$this->saving_fields) return;
	$f = array_flip(explode(",", $this->saving_fields));
	foreach($records as $i=>$record){
		foreach($record as $k=>$v) if (!array_key_exists($k, $f)) unset($records[$i][$k]);
	}
}

function savechanges($action, $params, $changed, $oldif=null){
//we just read current config and save!
//sub class can override this.
	//not save to sysconfig, should be done by subclass's savechanges or before/do/after_$action
	if (!$this->savechangeconfig) return;
	$r = $this->read(array(), array());//get all and store it!
	$new = $r[data];
	$this->strip_unsaving($new);
	$mod = $this->mid;
	$old = array_shift($this->dbquery("SELECT rowid,* FROM sysconfig WHERE mod='$mod'"));
	if (!$old){
		if (!$this->savechangeconfig[autocreate]) throw new Exception("sysconfig.$mod not found, autocreate was hibited neither.");
		$r = array(
			mod=>$mod,
			'current'=>serialize($new),
			'currenttime'=>date('Y-m-d H:i:s', time()),
			byaction=>'create auto',
		);
		parent::create(array(_writetable=>'sysconfig'), array($r));
		return $new;
	}
	$r = array(
		rowid=>$old[rowid],
		mod=>$mod,
		last=>$old['current'],
		lasttime=>$old['currenttime'],
		'current'=>serialize($new),
		'currenttime'=>date('Y-m-d H:i:s', time()),
		byaction=>$action,
	);
	parent::update(array(_writetable=>'sysconfig'), array($r));
	// subclass can using parent::savechanges to get all readed data
	return $new;	
}
	

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
	if (is_array($records)) $p = array_merge($p, $records);
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
function read($params, $records=null){
	$cmd = $this->defaultcmds[read];
	$msg = null;
	$next = 'continue';
	try{
		if (method_exists($this, 'before_read')){
			if (!$records) $records = array();
			//records carry out
			$next = $this->before_read($params, $records);	
			if ($next == 'return'){
				return array(
					success=>true,
					data=>$records,
					msg=>$msg?$msg:"$this->mid read done.",
				);
			}
		}
		if (!$cmd && !method_exists($this, 'do_read')){//dbonly
			$r = parent::read($params, $records);
			$r = $r[data];
		}else{
			if ($cmd){//get read result by cmd
				$r = $this->callcmd($cmd, $cmderror, $params, $records);
			}else{// get read result by do_read of sub_classes
				$r = $this->do_read($params, $records);
				$r = $r[data];
			}
			//todo: howto
			if ($this->synctodb){
				$msg = $this->syncdb($r, 'read', $this->synctodb);
			}
		}
		if (method_exists($this, 'after_read')){
			$this->after_read($params, $r);	
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
	$old_records = array(); //read before whole updates
	$changes = 0;
	$updated = array();
	$retold = array(); //for return
	try{
		if (!$this->batchsupport['update'] && count($records)>1) 
			throw new Exception("batch update not support, but ".count($records)." are supplied.");
		if (!$records)//so, the destroy has to do read before destroy records.
			throw new Exception("update, but null records supplied.");
		if ($this->readbeforeupdate){
			$p = $params;
			$p[_readold] = true;
			//incase write in some table, read in view!
			if ($this->tablewrite) $p[_writetable] = $this->tablewrite;
			if (!$cmd && !function_exists($this, 'do_update')){
				$r = parent::read($p, $records);
			}else{
				$r = $this->read($p, $records);
			}
			if ($r[success]) $old_records = $r[data];
			else throw new Exception("fail to read old data before update.");
		}
		if (method_exists($this, 'before_update')){
			$next = $this->before_update($params, $records, $old_records);	
			if ($next == 'return'){
				$this->savechanges('update', $params, $records, $old_records);
				return array(
					success=>true, //if false, exp was thrown already.
					old=>$old_records,
					updated=>$records,
					changes=>count($updated),
				);
			}
		}
		if ($this->batchsupport['update'] == 'one_by_one'){
			//old_records mustbe same index as records!
			foreach($records as $i=>$record){
				$old = $old_records[$i];
				if (!$cmd && !method_exists($this, 'do_update')){//dbonly
					//can be skipped by set an empty do_update function in subclass
					$r = parent::update($params, array($record));	 
					$changes += $r[changes];
					$updated = array_merge(updated, $r[updated]);
					$retold[] = $old;
				}else{
					if ($cmd){//get update result by cmd
						$extra = array(old=>$old);
						$this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($r[msg]).");
						}
						$changes ++;
						$updated[] = $record;
						$retold[] = $old;
					}else{// get update result by do_update of sub_classes
						$r = $this->do_update($params, array($record), $old);
						$changes += $r[changes];
						$updated = array_merge(updated, $r[updated]);
						$retold[] = $old;
					}
				}
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_update')){//dbonly
				//can be skipped by set an empty do_update function in subclass
				$r = parent::update($params, $records);	 
				$changes = $r[changes];
				$updated = $r[updated];
				$retold = $old_records;
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get update result by cmd
					foreach($records as $record){
						$old = $old_records[$i];
						$extra = array(old=>$old);
						$this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($r[msg]).");
						}
						$changes ++;
						$updated[] = $record;
						$retold[] = $old;
					}
				}else{// get update result by do_update of sub_classes
					$r = $this->do_update($params, $records, $old_records);
					$changes = $r[changes];
					$updated = $r[updated];
					$retold = $old_records;
				}
			}
		}
		if (method_exists($this, 'after_update')){
			$this->after_update($params, $updated, $retold);	
		}
		if ($cmd || method_exists($this, 'do_update')){
			$this->savechanges('update', $params, $updated, $retold);
		}
	}catch(Exception $e){//rollback?
		return array(
			success=>false,
			msg=>$e->getMessage(),
			updated=>$updated,
			changes=>$changes,
			old=>$retold,
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid update done.",
		updated=>$updated,
		changes=>$changes,
		old=>$retold,
	);
}

function destroy($params, $records){
	$cmd = $this->defaultcmds[destroy];
	$msg = null;
	$next = 'continue';
	$old_records = $records;
	$destroied = array();
	$changes = 0;
	try{
		if (!$this->batchsupport['destroy'] && count($records)>1) 
			throw new Exception("batch destroy not support, but ".count($records)." are supplied.");
		if (!$records)//so, the destroy has to do read before destroy records.
			throw new Exception("destroy, but null records supplied.");
		if ($this->readbeforedestroy){
			$p = $params;
			$p[_readold] = true;
			//incase write in some table, read in view!
			if ($this->tablewrite) $p[_writetable] = $this->tablewrite;
			if (!$cmd && !function_exists($this, 'do_destroy')){
				$r = parent::read($p, $records);
			}else{
				$r = $this->read($p, $records);
			}
			if ($r[success]) $old_records = $r[data];
			else throw new Exception("fail to read old data before destroy.");
		}
		if (method_exists($this, 'before_destroy')){
			$next = $this->before_destroy($params, $records);	
			if ($next == 'return'){
				$this->savechanges('destroy', $params, $records, array());
				return array(
					success=>true,
					msg=>$msg?$msg:"$this->mid destroy done.",
					destroied=>$old_records,
					changes=>count($old_records),
				);
			}
		}
		if ($this->batchsupport['destroy'] == 'one_by_one'){
			foreach($records as $record){
				$old = $record;
				if (!$cmd && !method_exists($this, 'do_destroy')){//dbonly
					//can be skipped by set an empty do_update function in subclass
					parent::destroy($params, array($record));	 
				}else{
					if ($cmd){//get destroy result by cmd
						$this->callcmd($cmd, $cmderror, $params, $record);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($r[msg]).");
						}
					}else{// get destroy result by do_destroy of sub_classes
						$this->do_destroy($params, array($record));
					}
				}
				$destroied[] = $old;
				$changes ++;
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_destroy')){//dbonly
				//can be skipped by set an empty do_destroy function in subclass
				$r = parent::destroy($params, $records);	 
				$destroied = $r[destroied];
				$changes = $r[changes];
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get destroy result by cmd
					foreach($records as $record){
						$this->callcmd($cmd, $cmderror, $params, $record);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($r[msg]).");
						}
						$destroied[] = $record;
						$changes ++;
					}
				}else{// get destroy result by do_destroy of sub_classes
					$r = $this->do_destroy($params, $records);
					$destroied = $r[destroied];
					$changes = $r[changes];
				}
			}
		}
		if (method_exists($this, 'after_destroy')){
			$this->after_destroy($params, $old_records);	
		}
		if ($cmd || method_exists($this, 'do_destroy')){
			$this->savechanges('destroy', $params, $records, array());
		}
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
			destroied=>$destroied,
			changes=>$changes,
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid destroy done.",
		destroied=>$destroied,
		changes=>$changes,
	);
}

function create($params, $records){
	$cmd = $this->defaultcmds[create];
	$msg = null;
	$next = 'continue';
	$new_records = array();
	$created = array();
	$changes = 0;
	try{
		if (!$this->batchsupport['create'] && count($records)>1) 
			throw new Exception("batch create not support, but ".count($records)." are supplied.");
		if (!$records)
			throw new Exception("create, but null records supplied.");
		if (method_exists($this, 'before_create')){
			$next = $this->before_create($params, $records, $new_records);	
			if ($next == 'return'){
				$this->savechanges('create', $params, $new_records, array());
				return array(
					success=>true,
					msg=>$msg?$msg:"$this->mid create done.",
					created=>$new_records,
					changes=>count($new_records),
				);
			}
		}
		if ($this->batchsupport['create'] == 'one_by_one'){
			foreach($records as $record){
				$old = $record;
				if (!$cmd && !method_exists($this, 'do_create')){//dbonly
					//can be skipped by set an empty do_create function in subclass
					//send new_records(just created) for reference!
					$r = parent::create($params, array($record));
					$changes += $r[changes];
					$created = array_merge($created, $r[created]);
				}else{
					$extra = array(last=>$created[count($created)-1]);
					if ($cmd){//get create result by cmd
						$r = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." create fail: $cmd return fail($r[msg]).");
						}
						$changes ++;
						$created[] = array_shift($r);
					}else{// get create result by do_destroy of sub_classes
						$p = $params;
						$p = array_merge($p, array(_created=>$created));
						$r = $this->do_create($p, array($record));
						$changes += $r[changes];
						$created = array_merge($created, $r[created]);
					}
				}
			}
		}else{//do it batchly
			if (!$cmd && !method_exists($this, 'do_create')){//dbonly
				//can be skipped by set an empty do_create function in subclass
				$r = parent::create($params, $records);	 
				$created = $r[created];
				$changes = $r[changes];
			}else{
				//cmd has to be one_by_one!
				if ($cmd){//get create result by cmd
					foreach($records as $record){
						$extra = array(last=>$created[count($created)-1]);
						$r = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." create fail: $cmd return fail($r[msg]).");
						}
						$changes ++;
						$created[] = array_shift($r);
					}
				}else{// get create result by do_create of sub_classes
					$r = $this->do_create($params, $records, $new_records);
					$created = $r[created];
					$changes = $r[changes];
				}
			}
		}
		if (method_exists($this, 'after_create')){
			$this->after_create($params, $old_records);	
		}
		if ($cmd || method_exists($this, 'do_create')){
			$this->savechanges('create', $params, $new_records, array());
		}
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
			created=>$created,
			changes=>$changes,
		);
	}
	return array(
		success=>true,
		msg=>$msg?$msg:"$this->mid create done.",
		created=>$created,
		changes=>$changes,
	);
}

//end of class
}
?>
