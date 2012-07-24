<?php
include_once('../../models/core/mod.base.php');
class MOD_dnsgw extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	cat /etc/resolv.conf|grep -v "^#"|grep nameserver|awk \'{print "dns"NR" "$2}\' 
	got=${PIPESTATUS[2]};
	if [ $got -ne 0 ];then
		echo "dns1 127.0.0.1"	#for create a record
	fi
	route -n|grep "^0.0.0.0"|awk \'{print "defgw"NR" "$2}\' 
)',
		//pharse config:
		type=>'one_record_span_lines',
		fieldstype=>'simple',
		fieldsmode=>array(
			type=>'keyvalues_span_lines',
			matcher=>'/([^ ]+) (.*)/',
			ignore=>'/ @@@@@@@@ /',
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
	changegw(){
		echo "#@LOG@INFO change defgw from $1 to $2\n"
		if [ ! -z "$1" ];then
			route del -net 0.0.0.0 gw $1 2>&1
		fi
		route add -net 0.0.0.0 gw $2 2>/tmp/.changegw.$$
		if [ $? -ne 0 ];then
			echo "#@ERROR@INFO add default gateway fail: `cat /tmp/.changegw.$$`"
			rm /tmp/.changegw.$$
			echo 
			exit 1
		fi
		rm /tmp/.changegw.$$
	}
	if [ "%dns1%" != "%old_dns1%"  -o "%dns2" != "%old_dns2" ];then
		mv /etc/resolv.conf /etc/resolv.conf.bak
		touch /etc/resolv.conf
		if [ "%dns1%" != "%old_dns1%" ];then
			echo "#@LOG@INFO changedns from %old_dns1% to %dns1%\n"
			echo "#old nameserver %old_dns1%" >>/etc/resolv.conf
		fi
		[ ! -z "%dns1%" ] && echo "nameserver %dns1%" >>/etc/resolv.conf
		if [ "%dns2%" != "%old_dns2%" ];then
			echo "#@LOG@INFO changedns from %old_dns2% to %dns2%\n"
			echo "#old nameserver %old_dns2%" >>/etc/resolv.conf
		fi
		[ ! -z "%dns2%" ] && echo "nameserver %dns2%" >>/etc/resolv.conf
	fi
	if [ "%defgw1%" != "%old_defgw1%" ];then
		changegw "%old_defgw1%" "%defgw1%"
	fi
	if [ "%defgw2%" != "%old_defgw2%" ];then
		changegw "%old_defgw2%" "%gw2%"
	fi
		
)',
		debug=>true,
	),
	
);

//save config to file
var $savechangeconfig = array(usingfile=>'network.dnsgw');

var $defaultcmds = array(read=>'get', update=>'update', create=>'faulty', destroy=>'faulty');

}
?>
