<?php
include_once('../../models/core/mod.base.php');
class MOD_iscsi_virtportal extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	FILE=/proc/scsi_target/iscsi_target/groups 
	cat $FILE
)',
/*
enable ipcheckmode:0 free:1 master:0.0.0.0 grpmask:255.255.0.0 srcmask:255.255.255.0 maxweight:64
tgn: default
 ix: 192.168.1.0(0) 192.168.1.1(0) 
 ex: 192.168.1.100 192.168.1.101
*/
		//pharse config:
		type=>'records_span_lines',
		ignore=>'/^ *$|^ena|^dis/',
		recordstart=>'/^tgn:/',
		recordid=>'/^tgn: (.*)/',
		fieldstype=>'mixed',
		fieldsmode=>array(
			'ix'=>array(
				gmatcher=>'/^ ix:/',
				type=>'records_in_one_line',
				recordmatcher=>'/([0-9.]+\([0-9]+\))/',
				recordtype=>array(
					type=>'record_in_one_line',
					fieldsep=>'/\(|\)/',
					fieldnames=>'includeip,count',
				),
				mergeup=>true,
			),
			'ex'=>array(
				gmatcher=>'/^ ex:/',
				type=>'records_in_one_line',
				recordmatcher=>'/([0-9.]+)/',
				recordtype=>array(
					type=>'just_output',
					name=>'excludesource',
				),
				mergeup=>true,
			),
				
		),
		newkeys=>array(
			'record_id'=>'targetname',
		),
		newvalues=>array(
			ix=>'records',
			ex=>'records',
		),
	),	
	'create'=>array(
		cmd=>'(
	if [ ! -z "%includeip%" ];then
		echo "#@LOG@INFO add includeip %includeip% for target:%targetname%\n"
		shell group add %targetname% include %includeip% >/dev/null
		echo "tgn: %targetname%"
		echo " ix: %includeip%(0)"
	fi
	if [ ! -z "%excludesource%" ];then
		echo "#@LOG@INFO add excludesource %excludesource% for target:%targetname%\n"
		shell group add %targetname% exclude %excludesource% >/dev/null
		echo "tgn: %targetname%"
		echo " ex: %excludesource%"
	fi
)',
		refcmd=>'MOD_iscsi_virtportal::get',
	),	

	'delete'=>array(
		cmd=>'(
	if [ ! -z "%includeip%" ];then
		echo "#@LOG@INFO del includeip %includeip% from target:%targetname%\n"
		shell group del %targetname% include %includeip% >/dev/null
	fi
	if [ ! -z "%excludesource%" ];then
		echo "#@LOG@INFO del excludesource %excludesource% from target:%targetname%\n"
		shell group del %targetname% exclude %excludesource% >/dev/null
	fi
)',
	),	
);

var $defaultcmds=array(
	read=>'get',
	create=>'create',
	destroy=>'delete',
	update=>'faulty',
);

var $saving_fields = 'targetname,includeip,excludesource';

var $keyids = array('targetname','includeip','excludesource');


}
?>
