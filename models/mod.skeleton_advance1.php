<?php
include_once('../models/mod.base.php');
/*

This is skeleton mod for advanced mode: 

   **	User defined do_$actions, but no direct support db table, just using sysconfig to store data: **

	Things to do:
		* define some usable cmd and pharser
		* define do_read/create/update/destroy function, note! these functions recieve array of record, not just a record!
		* can use callcmd(MOD::cmd) or callmod(MOD, ACTION, Ps, Rs) to finish your works
		* can use modlib->$methods to finish your works
		* return array(success=>true/false, data, changes, updated, created, destroied, msg ...) indeed;

	Things to note:
		* when batchmode is set as 'one by one', special params was sent in:
			do_update($params, array($record), array($old)), just one record, one old in their array.
			do_create($params, array($record), $created), just one record in array, but all created in this batch.
			do_destroy($param, array($record)), just one record.
		* when using just batch:
			do_update($params, $records, $olds),	all records and olds were sent.
			do_create($params, $records),	no $created.
			do_destroy($params, $records),	all records in one time.
			

Current setting after changing, will be saved in 'sysconfig' table automaticly.

Just copy and edit for your need, don't extend from this class!

*/
class MOD_skeleton_advance1 extends MOD_base{
/*
//these are default configurations
var $keyids = array('id'); 	//using this keys as record identify
var $defaultcmds = array( 
	read=>null,
);
var $batchsupport = array(// false|true|'one_by_one'
	update=>true, create=>'one_by_one', destroy=>true,
);

var $savechangeconfig = array(
	tablename=>'sysconfig', //table for store changing config data.
	autocreate=>true,	//auto create records for not-existed update.
);

var $saving_fields = null;
var $readbeforeupdate = true;	//weather read old data before update
var $readbeforedestroy = false;	//weather read old data before destroy
*/

//we don't need these default cmd all defined if related action was defined
var $defaultcmds = array(
	update=>'update',
);

var $batchsupport = array(
	update=>'one_by_one', create=>'one_by_one', destroy=>'one_by_one',
);

//we define this for get_sysconfig_$things to working
var $keyids = array('dev'); 	

//we set it, for get destroied data by condition maybe
var $readbeforedestroy = true;

//config fields recorded in sysconfig table, note! no space between fields!
//if dont' want to saving, just redefine $savechangeconfig as null
//we using netconfig's command ant output , so take this also
var $saving_fields = 'dev,ipaddress,broadcast,netmask,mtu,is_up,ipv6address,ipv6prefix';


//define some  commands, see mod.skeleton_shell.php 
static $pconfigs = array(
	'mycommand'=>array( /* valid cmd pharser config, see mod.skeleton_shell.php */),
	//....

	//we reference other mod's command here
	'update'=>array(
		callcmd=>'netconfig::updconfig', //use that whole config, including cmd itself.
	),
);

function do_read($params, $records){
	$cmderror = '';
	//$r = $this->callcmd('netconfig::ifconfig', $cmderror, $params, $records, $extra=array());
	$r = $this->callcmd('netconfig::ifconfig');
	return array(
		success=>true,
		data=>$r,
		msg=>'This is a sample output from mod skeleton_advanced1',
	);
}

function do_create($params, $records, $created){
	//we use one_by_one batch mode, so, onley one record sent in actually,
	$record = array_shift($records);
	//the changes of netconfig will also be recorded in sysconfig!
	//need recordS not recorD send in
	$r = $this->callmod('netconfig', 'create', $params, array(array(
			dev=>'eth0:test',
			ipaddress=>'192.168.0.111',
		)), $simpleresult=false, $throwerror=true);
	return $r;
}

//function do_update($params, $records, $olds)

function do_destroy($params, $records){
	$record = array_shift($records);
	//this change will not be recorded in sysconfig!
	$this->callcmd('netconfig::delconfig', $params, $record);
	return array(
		success=>true,
		destroied=>$records,
		changes=>1,
	);
}


//end of class
}
?>
