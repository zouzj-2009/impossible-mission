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
	change_enabled(){
		echo "#@LOG@0 change portal enabled from $1 to $2\n"
		if [ "$2" = 1 ];then
			shell group enable
		else
			shell group disable
		fi
	}

	change_portalip(){
		echo "#@LOG@0 change portalip from $1 to $2\n"
		shell group master $2
	}

	change_portalmask(){
		echo "#@LOG@0 change portalmask from $1 to $2\n"
		shell group grpmask $2
	}

	change_maxcount(){
		echo "#@LOG@0 change portal maxcount from $1 to $2\n"
		shell group maxweight $2
	}

	if [ "%enabled%" != "%old_enabled%" ];then
		change_enabled "%old_enabled%" "%enabled%"
	fi
	if [ "%portalip%" != "%old_portalip%" ];then
		change_portalip "%old_portalip%" "%portalip%"
	fi
	if [ "%portalmask%" != "%old_portalmask%" ];then
		change_portalmask "%old_portalmask%" "%portalmask%"
	fi
	if [ "%maxcount%" != "%old_maxcount%" ];then
		change_maxcount "%old_maxcount%" "%maxcount%"
	fi
		
)',
	),
	
);

static function get_enabled($v, $record, &$merge_up){
	return $v=='enable';
}

var $defaultcmds=array(read=>'get', update=>'update', destroy=>'faulty', create=>'faulty');

}
?>
