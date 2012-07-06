<?php
include_once('../models/mod.base.php');
class MOD_license extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	cat /etc/sysconfig/license.dat
)',
		//pharse config:
		type=>'just_output',
		name=>'licensedata',
		arrayret=>true,
	),
	'update'=>array(
		cmd=>'(
	if [ "%old_licensedata%" != "%licensedata%" ];then
		cp /etc/sysconfig/license.dat /tmp/license.dat.$$
		echo "%licensedata%" >/etc/sysconfig/license.dat
		rmmod xfs
		shell license test|grep "ok!"
		if [ $? -ne 0 ];then
			echo "#@ERROR@INFO new license test fail!\n"
			mv /tmp/license.dat.$$ /etc/sysconfig/license.dat
			exit 1
		else
			echo "#@ERROR@INFO new license test ok!\n"
			rm /tmp/license.dat.$$
			exit 0
		fi
	else
		shell license test|grep "ok!"
		if [ $? -ne 0 ];then
			echo "#@ERROR@INFO current license test fail!\n"
			exit 1
		else
			echo "#@ERROR@INFO current license test ok!\n"
			exit 0
		fi
	fi
)',
		getmsg=>true,	//for license test ok!
	),
	
);

//save config to file
var $savechangeconfig = array(usingfile=>'hostsetting');

var $defaultcmds = array(read=>'get', update=>'update', create=>'faulty', destroy=>'faulty');

}
?>
