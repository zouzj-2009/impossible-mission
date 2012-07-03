<?php
include_once('../models/mod.base.php');
class MOD_gvirtportal extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	FILE=/proc/scsi_target/iscsi_target/groups 
	cat $FILE|grep "^enable\|^disable"|sed "s/.*/enabled:\0/g"
)',
/*
enable ipcheckmode:0 free:1 master:0.0.0.0 grpmask:255.255.0.0 srcmask:255.255.255.0 maxweight:64
tgn: default
 ix: 192.168.1.0(0) 192.168.1.1(0) 
 ex: 192.168.1.100 
*/
		//pharse config:
		type=>'keyvalues_in_one_line',
		matcher=>'/([^ ]+):([^ ]+)/',
		arrayret=>true,
		newkeys=>array(
			'master'=>'portalip',
			'grpmask'=>'portalmask',
			'maxweight'=>'maxcount',
		),
		newvalues=>array(
			'enabled'=>'MOD_gvirtportal::get_enabled',
		),
	),	
	'update'=>array(
		cmd=>'(
	changedns(){
		echo "#@LOG@0 change dns from $1 to $2\n"
	}
	changegw(){
		echo "#@LOG@0 change defgw from $1 to $2\n"
	}
	if [ "%dns1%" != "%old_dns1%" ];then
		changedns "%old_dns1%" "%dns1%"
	fi
	if [ "%defgw1%" != "%old_defgw1%" ];then
		changegw "%old_defgw1%" "%defgw1%"
	fi
	if [ "%dns2%" != "%old_dns2%" ];then
		changedns "%old_dns2%" "%dns2%"
	fi
	if [ "%defgw2%" != "%old_defgw2%" ];then
		changegw "%old_defgw2%" "%gw2%"
	fi
		
)',
	),
	
);

static function get_enabled($v, $record, &$merge_up){
	return $v=='enable';
}

var $defaultcmds=array(read=>'get', update=>'update');

}
?>
