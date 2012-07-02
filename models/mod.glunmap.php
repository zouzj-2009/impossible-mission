<?php
include_once('../models/mod.base.php');
class MOD_glunmap extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	if [ `cat /proc/scsi_target/iscsi_target/lunmapping|grep "^enable"` != "" ];then
		echo enabled=true
	else
		echo enabled=
	fi
	if [ ! -f /etc/sysconfig/usechap ];then
		echo chapenabled=
		echo targetuser=
		echo targetpass=
	else
		. /etc/sysconfig/usechap
		echo chapenabled=true
		echo targetuser=$chaplocaluser
		echo targetpass=$chaplocalpass
	fi
)',
		//pharse config:
		type=>'one_record_span_lines',
		fieldstype=>'simple',
		fieldsmode=>array(
			type=>'keyvalues_span_lines',
			ignore=>'/ @@@@@@@@ /',
			newvalues=>array(
				enabled=>'boolean',
				chapenabled=>'boolean',
			//todo: mask the value, will get masked value when update records received!, so ...
			//	targetpass=>'password',
			),
		),
/*
		//or using this simple mode
		type=>'keyvalues_span_lines',
		matcher=>'/([^ ]+) (.*)/',
		arrayret=>true,
*/
	),	
	'update'=>array(
		cmd=>'(
	DRIVERPATH=/home/manager/drivers/`uname -r`                                          
	HOST=`basename /proc/scsi_target/iscsi_target/* 2>/dev/null`    
	if [ "%enabled%" != "%old_enabled%" ];then
		if [ "%enabled%" == "1" ];then
			echo "#@LOG@INFO lunmap enabled\n"
			echo "enable" >/proc/scsi_target/iscsi_target/lunmapping
		else
			echo "#@LOG@INFO lunmap disable\n"
			echo "disable" >/proc/scsi_target/iscsi_target/lunmapping
		fi
		cat /proc/scsi_target/iscsi_target/lunmapping >/etc/sysconfig/lunmapsaving
	fi
	if [ "%chapenabled%" != "%old_chapenabled%" -o "%targetuser%" != "%old_targetuser%" -o "%targetpass%" != "%old_targetpass%" ];then
		if [ "%chapenabled%" == "1" ];then
			echo "#@LOG@INFO chap enabled as user:%targetuser%\n"
			echo "chaplocaluser=%targetuser%" >/etc/sysconfig/usechap
			echo "chaplocalpass=%targetpass%" >>/etc/sysconfig/usechap
			. /etc/sysconfig/usechap
			$DRIVERPATH/cmd/iscsi_manage target set AuthMethod=CHAP host=${HOST} 
			$DRIVERPATH/cmd/iscsi_manage target force b t cl=256 lx="$CHAPLOCALSECRET" ln="$CHAPLOCALNAME" host=${HOST}
		else
			echo "#@LOG@INFO chap disabled\n"
			rm /etc/sysconfig/usechap 2>/dev/null
			$DRIVERPATH/cmd/iscsi_manage target set AuthMethod=None host=${HOST} 
		fi
	fi
)',
	),
	
);

var $defaultcmds = array(read=>'get', update=>'update', destroy=>'faulty', create=>'faulty');

}
?>
