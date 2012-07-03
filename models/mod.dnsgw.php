<?php
include_once('../models/mod.base.php');
class MOD_dnsgw extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	cat /etc/resolv.conf|grep -v "^#"|grep nameserver|awk \'{print "dns"NR" "$2}\' 
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

var $defaultcmds=array(read=>'get', update=>'update', create=>'faulty', destroy='faulty');

}
?>
