<?php
include_once('../models/mod.base.php');
class MOD_hostsetting extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	echo "hostname: `hostname`"
	echo "date: `date -I`"
	echo "time: `date -Iseconds|sed \'s/^.*T//g\'|sed \'s/+.*//g\'`"
	timezone=`cat /etc/sysconfig/timezone`
	[ -z "$timezone" ] && echo "timezone: Asia/Shanghai" || echo "timezone: $timezone"
)',
		//pharse config:
		type=>'keyvalues_span_lines',
		matcher=>'/([^:]+): (.*)/',
		arrayret=>true,
	),
	'update'=>array(
		cmd=>'(
	date=`echo %date%|sed \'s/T.*//g\'`
	if [ "$date" != "%old_date%"  -o "%time%" != "%old_time%" ];then
		echo "#@LOG@INFO change datetime  from %old_date% %old_time% to $date %time%\n"
		date -s "$date %time%"
		hwclock --systohc
	fi

	if [ "%hostname%" != "%old_hostname%" ];then
		echo "#@LOG@INFO change hostname from %old_hostname% to %hostname%\n"
		hostname %hostname%
		[ $? -ne 0 ] && echo %hostname% >/etc/sysconfig/hostname
	fi

	if [ "%timezone%" != "%old_timezone%" ];then
		echo "%timezone%" >/etc/sysconfig/timezone
	fi
)',
	),
	
);

//save config to file
var $savechangeconfig = array(usingfile=>'hostsetting');

var $defaultcmds = array(read=>'get', update=>'update', create=>'faulty', destroy=>'faulty');

}
?>
