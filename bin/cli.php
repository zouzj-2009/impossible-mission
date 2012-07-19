#!/usr/bin/php
<?php
$dir = dirname($argv[0]);
chdir($dir);
include_once('../models/proxy.php');
include_once('../models/debugee.php');

$shortopts = 'a:m:ds:p:r:o:h:';
$longopts = array(
	'param:',
	'port:',
	'fieldsep:',
	'record:',
);
function help(){
	echo "
Usage: 
cli.php -a read    -m MOD [-d] [-o json|xml|short|long] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]
cli.php -a update  -m MOD [-d] [-o json|xml|short|long] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]
cli.php -a create  -m MOD [-d] [-o json|xml|short|long] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]
cli.php -a destroy -m MOD [-d] [-o json|xml|short|long] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]

* using [-h HOSTIP] [--port PORT] to specify other host:port than localhost:80.
* multiple -p|--param|-r|--record will be accepted.
* PARAM|RECORD can be KEY=VALUE or KEY=VALUE,KEY=VALUE...
* default filed seperator is ','.
* default read output format is 'short', other is 'long'.

";
	die(1);
}

function check_args($s, $l, $retarray=false, $kvsep=','){
	if (is_array($s) && is_array($l)) $o = array_merge($s, $l);
	else if (is_array($s)) $o = array_merge($s, array($l));
	else if (is_array($l)) $o = array_merge($l, array($s));
	else if ($s && $l) $o = array($s, $l);
	else if ($s) $o = array($s);
	else if ($l) $o = array($l);
	else $o = array();
	$ret = array();
	foreach($o as $i=>$kv){
		$got = array();
		if (preg_match_all('/([^\\'.$kvsep.'=]*)=([^\\'.$kvsep.']*)/', $kv, $m)){
			foreach($m[1] as $i=>$k){
				$got[$k] = $m[2][$i];
			}
			if ($retarray) $ret[] = $got;
			else $ret = array_merge($ret, $got);
		}
	}
	return $ret;
}

$o = getopt($shortopts, $longopts);
if (!$o) help();
$act = $o[a];
$mod = $o[m];
$p = $o[p];
$param = $o[param];
$r = $o[r];
$record = $o[record];
$debug = isset($o[d]);
$format = $o[o];
$host = $o[h]?$o[h]:'localhost';
$port = $o[port]?$o[port]:'80';
if (!preg_match('/^(json|xml|short|long)$/', $format)) $format = $act=='read'?'short':'long';

if (!$act || !$mod) help();
if ($act=='create' && !$r && !$record) help();
if ($act=='update' && !$r && !$record) help();
if ($act=='destroy' && (!$r && !$record && !$p && !$param)) help();

$params = check_args($p, $param, false, $o[s]?$o[s]:',');
$records = check_args($r, $record, true, $o[s]?$o[s]:',');


class COMMANDER extends DEBUGEE{
var $debug = false;
var $format = 'short';
var $proxy;

function __construct($host, $debug=false, $port=80, $outformat='short'){
	$this->debug = $debug;
	$this->format = $outformat;
	$this->proxy = new PROXY($host, $port);
	$this->proxy->autologin = true;
	$this->proxy->debug = $debug;
}
	
function format_xml($data){
	echo "not implimented yet.\n";
}
function format_json($data){
	echo json_encode($data)."\n";
}
function format_short($data){
	if (!$data){
		echo "Nothing!\n";
		return;
	}
	$h = implode("|", array_keys($data[0]));
	echo $h."\n";
	foreach($data as $d) echo implode("|", array_values($d))."\n";
}

function format_long($data){
	if (!$data){
		echo "Nothing!\n";
		return;
	}
	echo count($data). " records total.\n";
	foreach($data as $i=>$d){
		echo "record: $i\n";
		foreach($d as $k=>$v) echo sprintf("\t%-20s: %s", $k, $v)."\n";
		echo "\n";
	}
	echo count($data). " records total.\n";
}

function do_cmd($mod, $act, $params, $records){
	$this->trace_in(DBG, __FUNCTION__, $mod, $act, $params, $records);
	$ret = $this->proxy->request_mod($mod, $act, $params, $records);
	if ($this->debug) print_r($ret);
	if (!$ret[success]){
		echo "$act $mod fail(". $this->args_to_string(array($params, $records)). ")\n";
		return false;
	}
	$format = $this->format;
	if ($act == 'read'){
		echo "read $mod ok. data:\n";
		$of = "format_$format";
		$this->$of($ret[data]);
	}else if ($act == 'create'){
		echo "create $mod ok. created:\n";
		$of = "format_$format";
		$this->$of($ret[created]);
	}else if ($act == 'update'){
		echo "update $mod ok. updated:\n";
		$of = "format_$format";
		$this->$of($ret[updated]);
	}else if ($act == 'destroy'){
		echo "destroy $mod ok. destroied:";
		$of = "format_$format";
		$this->$of($ret[destroied]);
	}
	return true;
}

//end of class
}

$cmd = new COMMANDER($host, $debug, $port, $format);
$cmd->do_cmd($mod, $act, $params, $records);
?>
