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
	'upload'=>array(
		cmd=>'(
	if [ "%licensefile__size%" -gt 2048 ];then
		echo "#@ERROR@INFO new license file %licensefile__name% size too big: %licensefile__size%(2k max).\n"
		exit 1
	fi
	cp /etc/sysconfig/license.dat /tmp/license.dat.$$
	echo "%licensefile__type%"|grep "gzip"
	if [ $? -eq 0 ];then
		cat %licensefile__tmp_name%|gunzip|dd of=/etc/sysconfig/license.dat bs=2k count=1
	else
		cp %licensefile__tmp_name% /etc/sysconfig/license.dat
	fi
	rmmod xfs
	maybefail=$?
	shell license test|grep "ok!"
	if [ $? -ne 0 ];then
		echo "#@ERROR@INFO new license %licensefile__name% test fail, use original one!\n"
		mv /etc/sysconfig/license.dat /var/log/license.dat.fail
		mv /tmp/license.dat.$$ /etc/sysconfig/license.dat
		exit 1
	else
		if [ "$maybefail" -ne 0 ];then
			echo "#@ERROR@INFO new license %licensefile__name% test maybe ok(need reboot to test for asure)!\n"
		else
			echo "#@ERROR@INFO new license %licensefile__name% test ok!\n"
			cp /etc/sysconfig/license.dat /etc/sysconfig/license.dat.ok
		fi
		rm /tmp/license.dat.$$
		exit 0
	fi
)',
		getmsg=>true,	//for license test ok!
	),
	'update'=>array(
		cmd=>'(
	if [ "%old_licensedata%" != "%licensedata%" ];then
		cp /etc/sysconfig/license.dat /tmp/license.dat.$$
		echo "%licensedata%" >/etc/sysconfig/license.dat
		rmmod xfs
		maybefail=$?
		shell license test|grep "ok!"
		if [ $? -ne 0 ];then
			echo "#@ERROR@INFO new license test fail!\n"
			mv /tmp/license.dat.$$ /etc/sysconfig/license.dat
			exit 1
		else
			if [ "$maybefail" -ne 0 ];then
				echo "#@ERROR@INFO new license test maybe ok(need reboot to test for asure)!\n"
			else
				echo "#@ERROR@INFO new license test ok!\n"
				cp /etc/sysconfig/license.dat /etc/sysconfig/license.dat.ok
			fi
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

var $defaultcmds = array(read=>'get', update=>'update', create=>'upload', destroy=>'faulty');

function before_create($params, $records){
print_r($params);
print_r($records);
}

}
?>
