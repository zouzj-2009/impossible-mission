<?php
include_once('../models/mod.base.php');
class MOD_netconfig extends MOD_base{
var $pconfigs = array(
	'ifconfig'=>array(
		cmd=>"(busybox ifconfig -a|sed 's/UP\|RUNNING\|MULTICAST/\\0:true/g')", 
		//pharse config:
/*
eth0      Link encap:Ethernet  HWaddr 00:21:86:5A:12:15  
          inet addr:198.88.88.209  Bcast:198.88.88.255  Mask:255.255.255.0
          inet6 addr: fe80::221:86ff:fe5a:1215/64 Scope:Link
          UP BROADCAST RUNNING MULTICAST  MTU:1500  Metric:1
          RX packets:207940 errors:0 dropped:0 overruns:0 frame:0
          TX packets:149475 errors:0 dropped:0 overruns:0 carrier:0
          collisions:0 txqueuelen:1000 
          RX bytes:63570475 (60.6 MiB)  TX bytes:13737814 (13.1 MiB)
          Interrupt:20 Memory:fe000000-fe020000 
*/
		type=>'records_span_lines',
		recordstart=>'/^eth|^lo|^vm|^wlan/',
		recordid=>'/^(eth[^ ]*|lo|vm[^ ]*|wlan[^ ]*) /',
		recordend=>'/^ *$/',
		fieldstype=>'simple',
		fieldsmode=>array(
			type=>'keyvalues_span_lines',
			matcher=>'/(Link encap:|HWaddr |inet addr:|Bcast:|Mask:|inet6 addr: |MTU:|RX bytes:|TX bytes:|UP:|RUNNING:|MULTICAST:)([^ ]*)/'
		),
		newkeys=>array(
			'record_id'=>'dev', 'Link encap'=>'link', 'HWaddr'=>'mac', 'inet addr'=>'ipaddress', 'Bcast'=>'broadcast', 
			'inet6 addr'=>'ipv6address', 'Mask'=>'netmask', 'MTU'=>'mtu', 'UP'=>'is_up', 'MULTICAST'=>'multicast', 
			'RUNNING'=>'running', 'RX bytes'=>'rxbytes', 'TX bytes'=>'txbytes'
		),
		newvalues=>array(
			'dev'=>array(
				type=>'record_in_one_line',
				fieldsep=>'/:/',
				fieldnames=>'physicdevice,alias',
				mergeup=>true,
			),
			'ipv6address'=>array(
				type=>'record_in_one_line',
				fieldsep=>'/\//',
				fieldnames=>'ipv6address,ipv6prefix',
				mergeup=>true,
			),
		),
	),
);

var $defaultcmds=array(read=>'ifconfig');

}
?>
