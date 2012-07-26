<?php
include_once('../../models/core/mod.db.php');
include_once('../../models/core/pharser.php');
include_once('../../models/core/proxy.php');
include_once('../../models/executor/exec.ssh.php');
//todo:
//cool feature!
//run as ... using an other db and read,update,create,destroy on that database only?
//run as means db only running, so all subclass feature will be ignored...

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
	update=>'one_by_one', create=>'one_by_one', destroy=>true,
);
var $keyids = array('id'); 	//using this keys as record identify
var $readbeforeupdate = true;	//weather read old data before update
var $readbeforedestroy = false;	//weather read old data before destroy

var $savechangeconfig = array(
	usingfile=>null,	//if named(file[.section]), changes will be stored in /etc/sysconfig/syscfg_$file, and in section $section
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

static $syscfgpconfig = array(
	//for pharse records from record
	type=>'records_span_lines',
	recordstart=>'/^record: /',
	recordid=>'/^record: ([0-9]+)/',
	fieldstype=>'simple',
	fieldsmode=>array(
		type=>'keyvalues_span_lines',
		matcher=>'/([^:]+): (.*)/',
		trimkey=>"\t",
		trimvalue=>"\n",
	),
	//debug=>true,
	//debugall=>true,
);

function strip_unsaving(&$records){
//strip needn't save fields not configed in $this->saving_fields

	if (!$this->saving_fields) return;
	$f = array_flip(explode(",", $this->saving_fields));
	foreach($records as $i=>$record){
		foreach($record as $k=>$v) if (!array_key_exists($k, $f)) unset($records[$i][$k]);
	}
}

protected  function get_record_id($record){
	$id='';
	foreach($this->keyids as $key) $id .= "^".$record[$key];
	return $id;
}

protected function is_exclude_condition($condition){
	//todo:
	return false;
}

protected function prepare_condition($condition){
	//todo: date, like ...
	$c = str_replace('AND', '&&', $condition);
	$c = str_replace('OR', '||', $c);
/*
	$c = str_replace('>=', '>>' $c);
	$c = str_replace('<=', '<<' $c);
	$c = str_replace('=', '==', $c);
	$c = str_replace('>>', '>=', $c);
	$c = str_replace('<<', '<=', $c);
*/
	$c = preg_replace('/([a-zA-Z][^ <=>]*) *(>=|<=|<|>|=) */', '$${1} ${2} ', $c);
	$c = str_replace(' = ', ' == ', $c);
	//$c = addslashes($c);
	$c = "\$___conditionresult=($c);";
	$this->loginfo(DBG, 'base', "prepared condition($c).");
	return $c;
}

protected function record_match_condition($___rec, $___condition){
	$___conditionresult = false;
	foreach($___rec as $___key=>$___v){
		$$___key = $___v;
	//	echo "$___key=".$$___key."\n";
	}
	if (eval($___condition) === false){
		$this->loginfo(TRACE, 'base', "bad condition string($___condition).");
		print_r($___rec);
		return false;
	}
	return $___conditionresult;
}

protected function filter_result_by_condition($r, $condition)
{
	$this->loginfo(DBG, 'base', "filter result by ($condition).");
	$ret = array();
	$isExclude = $this->is_exclude_condition($condition);
	$condition = $this->prepare_condition($condition);
	foreach($r as $record){
		if ($this->record_match_condition($record, $condition))
			if ($isExclude) continue; else $ret[] = $record;
	}
	return $ret;
}
protected function filter_result_by_records($r, $records){
	$ret = array();
	foreach($records as $i=>$record){
		$v = $this->get_record_id($record);
		foreach($r as $got) if ($v == $this->get_record_id($got)){ $ret[] = $got; break; }
	}
	return $ret;
}

function get_sysconfig($which, $records=null)
{
	if (!$this->savechangeconfig) throw new Exception (get_class($this).".".__FUNCTION__.", but mod not configured by sysconfig.");
	$mod = str_replace("MOD_", "", get_class($this));
	$table = $this->savechangeconfig[talbename];
	if (!$table) $table = 'sysconfig';
	$old = array_shift($this->dbquery("SELECT $which FROM $table WHERE mod='$mod'"));
	$old = unserialize($old[$which]);
	if (!$old || !$record) return $old;
	return $this->filter_result_by_record($old, $records);
}

private function write_config($fn, $data){
	$retry = 3;	
	$ok = false;
	while($retry-- && !($ok = file_put_contents($fn, $data, LOCK_EX))) sleep(1);
	if (!$ok) throw new Exception("write config $fn fail!");
}

private function read_config($fn, $section){
	$n = explode("\n", file_get_contents($fn));
	$o = array();
	if (!$n) return $o;
	$insection = false;
	foreach($n as $line){
		if (preg_match("/^\{\[$section\] *$/", $line)){//section start
			$insection = true;
			continue;
		}
		if (!$insection) continue;
		if (preg_match("/^}/", $line)) return $o;
		$o[] = $line;
	}
	return $o;
}

function loadcfg_in_file($usingfile, $mod, $type='current'){
	$f = explode(".", $usingfile);
	$fn = "/etc/sysconfig/syscfg_$f[0]";
	if ($type != 'current') $fn .= ".$type";	//can load xxx.default xxx.nnn. ...
	if (!file_exists($fn)){
		//return array();
		throw new Exception ("config file $fn not exists!");
	}
	$sec = $f[1]?$f[1]:$mod;
	$cfg = $this->read_config($fn, $sec);
	$r = PHARSER::pharse_type($cfg, MOD_base::$syscfgpconfig);
	return $r;
}


function savechanges_in_file($usingfile, $new, $mod, $action, $type='current'){
	$header = "#last modified: ".date('Y-m-d H:i:s', time())." by $mod.$action\n";
	$f = explode(".", $usingfile);
	$fn = "/etc/sysconfig/syscfg_$f[0]";
	if ($type != 'current') $fn .= ".$type";	//can load xxx.default xxx.nnn. ...
	$sec = $f[1]?$f[1]:$mod;
	$data = "{[$sec]\n$header";
	foreach($new as $i=>$record){
		$data .= "record: $i\n";
		foreach ($record as $k=>$v) $data .= "\t$k: $v\n";
		$data .= "\n";
	}
	$data .= "}\n";
	if (!file_exists($fn)){ 
		$this->write_config($fn, $data);
	}else{
		$c = explode("\n", file_get_contents($fn));
		$insection = false;
		$inpara = false;
		$found = false;
		$o = '';
		foreach($c as $line){
			if (preg_match("/^{/", $line)) $inpara = true;
			if (preg_match("/^}/", $line)) $inpara = false;
			if (preg_match("/^\{\[([^\]]*)\] *$/", $line, $m)){//section start
				if ($m[1] == $sec){
					$insection = true;
				}
			}
			if ($insection && preg_match("/^} *$/", $line)){
				$insection = false;
				$o .= "$data"; //add null-line 
				$found = true;
				//don't include this '}'
				continue;
			}
			if ($insection) continue;
			if (!$inpara && preg_match("/^ *$/", $line)) continue;
			$o .= $line."\n";
		}
		if (!$found) $o .= "\n$data";
		$this->write_config($fn, $o);
	}
	return $new;
}

function load_sysconfig($type='current', $filterby=array()){
	$mod = str_replace("MOD_", "", get_class($this));
	if (!$this->savechangeconfig) throw new Exception("mod has not sysconfig setting!");
	$usingfile = $this->savechangeconfig[usingfile];
	if ($usingfile){
		$this->loginfo(TRACE, 'base', "loading sysconfig[$type] from file $usingfile.");
		$r = $this->loadcfg_in_file($usingfile, $mod, $type);
	}else{
		$this->loginfo(TRACE, 'base', "loading sysconfig[$type] sysconfig db.");
		$table = $this->savechangeconfig[talbename];
		if (!$table) $table = 'sysconfig';
		$r = array_shift($this->dbquery("SELECT $type FROM $table WHERE mod='$mod'"));
		if ($r){
			$r = unserialize($r[$type]);
		}
	}
	if (!$filterby) return $r;
	$r = $this->filter_result_by_records($r, $filterby);
	return $r;
}

function save_sysconfig($type='current'){
	//store current mod's config to sysconfig
	$this->savechanges('read', $type, null, null);
}

function savechanges($action, $params, $changed, $oldif=null){
	$this->trace_in(DBG, __FUNCTION__, $action, $params, $changed, $oldif);
//we just read current config and save!
//sub class can override this.
	//not save to sysconfig, should be done by subclass's savechanges or before/do/after_$action
	if (!$this->savechangeconfig) return;
	$usingfile = $this->savechangeconfig[usingfile];
	if ($usingfile){
		$this->loginfo(TRACE, 'base', "saving changed by $action into $usingfile.");
	}else{
		$this->loginfo(TRACE, 'base', "saving changed sysconfig by $action.");
	}
	$r = $this->read(array(), array());//get all and store it!
	$new = $r[data];
	$this->trace_in(DBG, __FUNCTION__." readout old", $new);
	//merge with changed
	//do we need this? or just read from updated above?
	//we not save change yet, so, ... these is need
	if ($action == 'update'){
		foreach($new as $k=>$record){
			foreach ($changed as $c){
				$newid = $this->get_record_id($c);
				$oldid = $this->get_record_id($record);
				if ($oldid == $newid){
					$new[$k] = $c; 
					break;
				}
			}
		}
	}else if ($action == 'destroy'){
		foreach($new as $k=>$record){
			foreach ($changed as $c){
				if ($this->get_record_id($c) == $this->get_record_id($record)){
					unset($new[$k]);
					break;
				}
			}
		}
	}else if ($action == 'create'){
		//don't just merge, maybe duplicated record!
		//$new = array_merge($new, $changed);
		foreach($new as $k=>$record){
			foreach ($changed as $j=>$c){
				if ($this->get_record_id($c) == $this->get_record_id($record)){
					unset($changed[$j]);
					break;
				}
			}
		}
		$new = array_merge($new, $changed);
	}

	$this->strip_unsaving($new);
	$mod = $this->mid;
	if ($usingfile) return $this->savechanges_in_file($usingfile, $new, $mod, $action);
	$table = $this->savechangeconfig[talbename];
	if (!$table) $table = 'sysconfig';
	$old = array_shift($this->dbquery("SELECT rowid,* FROM $table WHERE mod='$mod'"));
	if (!$old){
		if (!$this->savechangeconfig[autocreate]) throw new Exception("sysconfig.$mod not found, autocreate was hibited neither.");
		$r = array(
			mod=>$mod,
			'current'=>serialize($new),
			'currenttime'=>date('Y-m-d H:i:s', time()),
			byaction=>'create auto',
		);
		parent::create(array(_writetable=>$table), array($r));
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
	parent::update(array(_writetable=>$table), array($r));
	// subclass can using parent::savechanges to get all readed data
	return $new;	
}
	

function check_need_vars($arr, $needles, $title='read params'){
	$k = explode(",", $needles);
	foreach($k as $key) if (!isset($arr[$key])) throw new Exception(get_class($this)." $title need $needles, but $key not set.");
}

function getmod($modname, $loadonly=false, $newinstance=false){
	if ($modname == 'self') return $this;
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
	$modfile = mid2modfile($modname, $class);
	if (!file_exists($modfile)){
		throw new Exception(__FUNCTION__.": mod $modname not found");
	}
	include_once($modfile);
	$name = "$class";
	if ($loadonly) return $name;
	$mod = new $name($modname, null, $this->modconfig);
	$mod->caller = $this;
	return $mod;
}


function get_pconfig($class, $cmd)
{
	if (is_object($class)) $modname = get_class($class); else $modname = $class;
	$pconfigs = $modname::$pconfigs;
	$pconfig = $pconfigs[$cmd];
	$refcmd = $pconfig['refcmd']; //ref&call(ed) class must already included by caller
	$callcmd = $pconfig['callcmd'];
	if (!$refcmd && !$callcmd) return $pconfig;
	if ($callcmd) $refcmd = $callcmd;
	$r = explode('::', $refcmd);
	$rconfig = null;
	if (count($r)==2){
		if ($r[0] == 'self') $r[0] = $modname; else if (!preg_match('/^MOD_/', $r[0])) $r[0] = "MOD_".$r[0];
		$rconfig = $this->get_pconfig($r[0], $r[1]); //can get from other class, rescurively
	}else{
		$rconfig = $pconfigs[$refcmd];
	}	
	if (!$rconfig) throw new Exception("$cmd's reference cmd $refcmd's pharser config not exists!");
	if ($callcmd) return $rconfig;	//totally override
	$pconfig = array_merge($rconfig, $pconfig);	//using my setting addon
	return $pconfig;
}

function log($level, $log){
	echo "LOG@$level $log";
}

function get_executor(&$cmd, &$pconfig, &$args){
	$this->trace_in(DBG, __FUNCTION__, $cmd, $pconfig[executor], $args);
	//only ssh supported now!
	if ($this->exectype != 'ssh' && !$pconfig[executor]) return null;
	//strange, erhn?
	if ($pconfig[executor] && $pconfig[executor][type] != 'ssh') return null;
	if (!is_numeric($args[hostid])){
		$this->trace_in(DBG, __FUNCTION__, $cmd, $args);
		throw new Exception ("exec $cmd by ssh executor need numeric hostid as initial config.");
	}
	//find global cache first!
	global $__executorcaches;
	$hostid = $args[hostid];
	if (is_object($__executorcaches[$hostid])) return $__executorcaches[$hostid];
	//get hostinfo;
	$modconfig = $this->modconfig;
	if ($pconfig[executor]){
		$__executorcaches[$hostid] = new EXEC_ssh(array_merge($modconfig, $pconfig[executor]));
	}else{
		$host = array_shift($this->dbquery("SELECT rowid,* FROM host WHERE rowid='$hostid'"));
		if (!$host) throw new Exception("exec $cmd fail, can't find specified host($hostid).");
		$__executorcaches[$hostid] = new EXEC_ssh(array_merge($modconfig, array(ip=>$host[hostip], port=>$host[hostport], username=>$host[username], password=>$host[password])));
	}
	return $__executorcaches[$hostid];
	
}

function callcmd($cmd, &$cmderror, &$params=null, &$records=null, &$extra=null){
	$this->trace_in(TRACEALL, __FUNCTION__, $cmd, $params, $records, $extra);
//call internal cmd in pconfigs
//extra args using array('prefix'=>array_data or 'key'=>value);
//$cmd canbe "MOD::cmd"
	$cmderr = '';
	$cx = explode('::', $cmd);
	$mod = $this;
	if (count($cx)==2){
		$mod = $this->getmod($cx[0]);
		$c = $cx[1];
	}else{
		$mod = $this;
		$c = $cmd;
	}
	if ($c == 'faulty'){
		throw new Exception(str_replace("MOD_", "", get_class($mod))." operation not support.");
	}
	$pconfig = $this->get_pconfig($mod, $c);
	if (!$pconfig) throw new Exception(get_class($this)." callcmd $cmd fail: cmd not configurated.");
	if ($this->test_debug(PHDBG)) $pconfig[debug] = true;
	if ($this->test_debug(PHDBGALL)) $pconfig[debugall] = true;
	if ($this->test_debug(CMDDBG)) $pconfig[debugcmd] = true;
	$p = $params?$params:array();
	if (is_array($records)) $p = array_merge($p, $records);
	if ($extra && is_array($extra)) foreach($extra as $k=>$v){
		if (is_array($v)) foreach($v as $name=>$value) $p[$k."_".$name] = $value;
		else $p[$name] = $value;
	}

	//recheck args, if array, flat-it
	foreach($p as $k=>$v){
		if (!is_array($v)) continue;
		//usually, only posted files will use this feature.
		foreach($v as $vk=>$vv){
			$p[$k."__".$vk] = $vv;
		}
		$p[$k] = "Array(".implode(",", array_keys($p[$k])).")";
	}

	$logs = array();
	$executor = $mod->get_executor($c, $pconfig, $p);
	if ($executor && is_a($mod, 'MOD_servable')){ $mod->sendCmdStart($c, $p); }
	$r = PHARSER::pharse_cmd($c, $pconfig, $p, $cmderr, $mod, $logs, $executor);
	if ($executor && is_a($mod, 'MOD_servable')){ $mod->sendCmdEnd($c, $p, $cmderr); }
	$cmderror = $cmderr;
	if ($logs){
		foreach($logs as $log){
			$level = str_replace("LOG@", '', $log[record_id]);
			$this->loginfo($level, str_replace("MOD_", "", get_class($mod))."::$c", $log['log']);
		}
	}
	if ($this->test_debug(CMDRET)){
		if ($cmderror) $this->tracemsg(CMDRET, "exec $c return error: $cmderror.");
		else $this->trace_in(CMDRET, "$c return=", $r);
	}
	return $r;
}

//todo: call mod on other server!
function get_proxy($server, $debug){
	global $__proxycaches;
	$proxy = $__proxycaches["$server[host]:$server[port]"];
	if ($proxy) return $proxy;
	$proxy = new PROXY($server[host], $server[port], $server[user], $server[pass], $ssl=false, $debug);
	$proxy->autologin = $server[autologin];
	$proxy->callback = array($this, 'rmcallback');
	echo "REMOTE: login on $server[host]:$server[port]\n";
	$proxy->login($server[user], $server[pass]);
	$__proxycaches["$server[host]:$server[port]"] = $proxy;
	return $proxy;
}

//These functions should be override by MOD_servable
function sendPending($msg){ echo ">>>$msg"; }
function sendModStart($modname, &$mod, &$params, &$records, $remote=false){}
function sendModEnd($modname, &$mod, &$params, &$records, $remote=false){}
function sendCmdStart($name, &$args){}
function sendCmdEnd($name, &$args, &$result){}

function rmcallback($mod, $action, $r){
	$pending = $r[pending];
	$during = $r[during];
	$elapsed = $r[elapsed];
	if ($this->__debugon && $r[output]){
		//We need a CR here, or something strange occuring ...
		//Maybe ob_flush will not flush un-CR'ed strings?
		echo "REMOTE: ".str_replace("\n", "\nREMOTE: ", trim($r[output],"\n"))."\n";
	}
	if (!$elapsed){
		$msg = "\tremote: $pending[text]";
	}else{
		$msg = "\tremote: $pending[text] [".number_format($pending[number]*100,0)."%, $during's/$elapsed's EST]";
	}
	$this->sendPending($msg);
}

function callmod_remote($serverconfig, $modname, $action, $params, $records, $simpleresult=true){
	$this->trace_in(TRACE, __FUNCTION__, $serverconfig, $modname, $action, $params, $records);
	//should I use $this as check condition?
	$proxy = $this->get_proxy($serverconfig, $this->modconfig[debugsetting]);
	if (is_a($mod, 'MOD_servable')){ $mod->sendModStart("$modname.$action", $mod, $params, $records, $remote=true); }
	$r = $proxy->request_mod($modname, $action, $params, $records);
	$this->trace_in(DBG, __FUNCTION__." got", $r);
	if (is_a($mod, 'MOD_servable')){ $mod->sendModEnd("$modname.$action", $mod, $params, $records, $r, $remote=true); }
	if (!$simpleresult) return $r;
	if (!$r[success]){
		if ($throw) throw new Exception("callmod $modname::$action@$serverconfig[host] unsuccessful, $r[msg]");
		return false;
	}
	if ($action == 'read') $ret = $r[data];
	else if ($action == 'create') $ret = $r[created];
	else if ($action == 'update') $ret = $r[updated];
	else if ($action == 'destroy') $ret = $r[destroied];
	$this->trace_in(MODRET, "$action $modname, return=", $ret);
	return $ret;
}

function callmod($modname, $action, $params, $records, $simpleresult=true, $throw=false){
	$this->trace_in(TRACEALL, __FUNCTION__, $modname, $action, $params, $records);
	$mod = $this->getmod($modname);
	//should I use $this as check condition?
	if (is_a($mod, 'MOD_servable')){ $mod->sendModStart("$modname.$action", $mod, $params, $records); }
	$r = $mod->$action($params, $records);
	$this->trace_in(DBG, __FUNCTION__." got", $r);
	if (is_a($mod, 'MOD_servable')){ $mod->sendModEnd("$modname.$action", $mod, $params, $records, $r); }
	if (!$simpleresult) return $r;
	if (!$r[success]){
		if ($throw) throw new Exception("callmod $modname::$action unsuccessful, $r[msg]");
		return false;
	}
	if ($action == 'read') $ret = $r[data];
	else if ($action == 'create') $ret = $r[created];
	else if ($action == 'update') $ret = $r[updated];
	else if ($action == 'destroy') $ret = $r[destroied];
	$this->trace_in(MODRET, "$action $modname, return=", $ret);
	return $ret;
}

/*
function getid(&$record){
//return value by $this->readold configed keys
	$v = '';
	foreach($this->keyids as $k) $v .= $record[$k];
	return $v;
}
*/
//subclass advice:
//If has db, use before_read to change or add params. use after_read to fix result.
//IF has not db, use do_read/cmd to get info. usually, no before/after_read needed, all in do_read.
//don't overwrite this method generally.
function read($params, $records=null){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$cmd = $this->defaultcmds[read];
	$msg = null;
	$next = 'continue';
	$r = array();
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
				if ($cmderror) throw new Exception('read fail:'.$r[msg]);
			}else{// get read result by do_read of sub_classes
				$r = $this->do_read($params, $records);
				if (!$r[success]) throw new Exception('read fail:'.$r[msg]);
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
	if ($params[_readold]) $r = $this->filter_result_by_records($r, $records);
	if ($params[_condition]){
		$r = $this->filter_result_by_condition($r, $params[_condition]);
	}
/*
	if ($records && $this->readold){
		$old = array();
		foreach($records as $i=>$record){
			$v = $this->getid($record);
			foreach($r as $got) if ($v == $this->getid($got)){ $old[$i] = $got; break; }
		}
		$r = $old;
	}
*/
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
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$cmd = $this->defaultcmds[update];
	$msg = null;
	$old_records = array(); //read before whole updates
	$changes = 0;
	$updated = array();
	$retold = array(); //for return
	$okmsg = '';
	$mustreadold = false;
	try{
		if (!$this->batchsupport['update'] && count($records)>1) 
			throw new Exception("batch update not support, but ".count($records)." are supplied.");
		if (!$records){//so, the destroy has to do read before destroy records.
			if (!$params[_condition]) throw new Exception("update, but neither records nor condition supplied.");
			$mustreadold = true;
		}
		if ($mustreadold || $this->readbeforeupdate){
			$p = $params;
			if (!$mustreadold) $p[_readold] = true;
			//incase write in some table, read in view!
			if ($this->tablewrite) $p[_writetable] = $this->tablewrite;
			if (!$cmd && !method_exists($this, 'do_update')){
				$r = parent::read($p, $records);
			}else{
				$r = $this->read($p, $records);
			}
			if ($r[success]) $old_records = $r[data];
			else throw new Exception("fail to read old data before update.");
			if ($mustreadold) $records = $old_records;
			//return now!
			if (!$records && !$params[_checknull]) return array(
				success=>true,
				old=>$records,
				updated=>array(),
				changes=>0,
				msg=>'update done. nothing changed',
			);
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
					$updated = array_merge($updated, $r[updated]);
					$retold[] = $old;
				}else{
					if ($cmd){//get update result by cmd
						$extra = array(old=>$old);
						$r = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($cmderror, $r[msg]).");
						}
						$okmsg .= $r[msg];
						$changes ++;
						$updated[] = $record;
						$retold[] = $old;
					}else{// get update result by do_update of sub_classes
						$r = $this->do_update($params, array($record), array($old));
						$changes += $r[changes];
						$updated = array_merge($updated, $r[updated]);
						$retold[] = $old;
						$okmsg .= $r[msg];
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
						$r = $this->callcmd($cmd, $cmderror, $params, $record, $extra);
						if ($cmderror){
							throw new Exception(get_class($this)." update fail: $cmd return fail($cmderror, $r[msg]).");
						}
						$okmsg .= $r[msg];
						$changes ++;
						$updated[] = $record;
						$retold[] = $old;
					}
				}else{// get update result by do_update of sub_classes
					$r = $this->do_update($params, $records, $old_records);
					$changes = $r[changes];
					$updated = $r[updated];
					$retold = $old_records;
					$okmsg .= $r[msg];
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
		msg=>$okmsg?$okmsg:"$this->mid update done.",
		updated=>$updated,
		changes=>$changes,
		old=>$retold,
	);
}

function destroy($params, $records){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$cmd = $this->defaultcmds[destroy];
	$msg = null;
	$okmsg = '';
	$next = 'continue';
	$old_records = $records;
	$destroied = array();
	$mustreadold = false;
	$changes = 0;
	try{
		if (!$this->batchsupport['destroy'] && count($records)>1) 
			throw new Exception("batch destroy not support, but ".count($records)." are supplied.");
		if (!$records){//so, the destroy has to do read before destroy records.
			if (!$params[_condition]) throw new Exception("destroy, but null records supplied.");
			$mustreadold = true;
		}
		if ($mustreadold || $this->readbeforedestroy){
			$p = $params;
			if (!$mustreadold) $p[_readold] = true;
			//incase write in some table, read in view!
			if ($this->tablewrite) $p[_writetable] = $this->tablewrite;
			if (!$cmd && !method_exists($this, 'do_destroy')){
				$r = parent::read($p, $records);
			}else{
				$r = $this->read($p, $records);
			}
			if ($r[success]) $old_records = $r[data];
			else throw new Exception("fail to read old data before destroy.");
			if ($mustreadold) $records = $old_records;
			//return now!
			if (!$records && !$params[_checknull]) return array(
				success=>true,
				old=>$records,
				updated=>array(),
				changes=>0,
				msg=>'destroy done. nothing changed',
			);
		}
		if (method_exists($this, 'before_destroy')){
			$next = $this->before_destroy($params, $old_records);	
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
						$r = $this->callcmd($cmd, $cmderror, $params, $record);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($cmderror, $r[msg]).");
						}
					}else{// get destroy result by do_destroy of sub_classes
						$r = $this->do_destroy($params, array($record));
					}
				}
				$destroied[] = $old;
				$changes ++;
				$okmsg .= $r[msg];
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
					foreach($old_records as $record){
						$r = $this->callcmd($cmd, $cmderror, $params, $record);
						if ($cmderror){
							throw new Exception(get_class($this)." destroy fail: $cmd return fail($cmderror, $r[msg]).");
						}
						$destroied[] = $record;
						$changes ++;
						$okmsg .= $r[msg];
					}
				}else{// get destroy result by do_destroy of sub_classes
					$r = $this->do_destroy($params, $old_records);
					$destroied = $r[destroied];
					$changes = $r[changes];
					$okmsg .= $r[msg];
				}
			}
		}
		if (method_exists($this, 'after_destroy')){
			$this->after_destroy($params, $destroied);	
		}
		if ($cmd || method_exists($this, 'do_destroy')){
			$this->savechanges('destroy', $params, $destroied, array());
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
		msg=>$okmsg?$okmsg:"$this->mid destroy done.",
		destroied=>$destroied,
		changes=>$changes,
	);
}

function create($params, $records){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$cmd = $this->defaultcmds[create];
	$msg = null;
	$okmsg = '';
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
							throw new Exception(get_class($this)." create fail: $cmd return fail($cmderror, $r[msg]).");
						}
						//maybe multi-records were added!
						$changes +=count($r);
						$created = array_merge($created, $r);
						$okmsg .= $r[msg];
					
					}else{// get create result by do_destroy of sub_classes
						$r = $this->do_create($params, array($record), $created);
						$changes += $r[changes];
						$created = array_merge($created, $r[created]);
						$okmsg .= $r[msg];
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
							throw new Exception(get_class($this)." create fail: $cmd return fail($cmderror, $r[msg]).");
						}
						//maybe multi-records were added!
						$changes += count($r);;
						$created = array_merge($created, $r);
						$okmsg .= $r[msg];
					}
				}else{// get create result by do_create of sub_classes
					$r = $this->do_create($params, $records);
					$created = $r[created];
					$changes = $r[changes];
					$okmsg .= $r[msg];
				}
			}
		}
		if (method_exists($this, 'after_create')){
			$this->after_create($params, $created);	
		}
		if ($cmd || method_exists($this, 'do_create')){
			$this->savechanges('create', $params, $created, array());
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
		msg=>$okmsg?$okmsg:"$this->mid create done.",
		created=>$created,
		changes=>$changes,
	);
}

//end of class
}
?>
