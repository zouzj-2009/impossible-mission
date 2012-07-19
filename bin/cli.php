#!/usr/bin/php
<?php
$dir = dirname($argv[0]);
chdir($dir);
include_once('../models/proxy.php');
$proxy = new PROXY('localhost');
$proxy->autologin = true;

$shortopts = 'a:m:ds:p:r:';
$longopts = array(
	'param:',
	'fieldsep:',
	'record:',
);
function help(){
	echo "
Usage: 
cli.php -a read    -m MOD [-d] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]
cli.php -a update  -m MOD [-d] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]
cli.php -a create  -m MOD [-d] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]
cli.php -a destroy -m MOD [-d] [-s|--fieldsep SEPCHAR] [-p|--param PARAM] [-r|--record RECORD]

* multiple -p|--param|-r|--record will be accepted.
* PARAM|RECORD can be KEY=VALUE or KEY=VALUE,KEY=VALUE...
* default filed seperator is ','.

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

if (!$act || !$mod) help();
if ($act=='create' && !$r && !$record) help();
if ($act=='update' && !$r && !$record) help();
if ($act=='destroy' && ((!$r && !$record)||(!$p && !$param))) help();

$params = check_args($p, $param, false, $o[s]?$o[s]:',');
$records = check_args($r, $record, true, $o[s]?$o[s]:',');

$ret = $proxy->request_mod($mod, $act, $params, $records);

print_r($ret);

