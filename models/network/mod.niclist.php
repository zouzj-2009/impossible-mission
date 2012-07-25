<?php
include_once('../../models/core/mod.base.php');
class MOD_network_niclist extends MOD_base{
static $pconfigs = array(
	'read'=>array(
		cmd=>'(
	for nic in `ls /sys/class/net|grep -v ^lo`
	do
		echo device: $nic
		ethtool -i $nic
		sid=`ethtool -i $nic|grep bus-info|awk \'{print $2}\'|sed "s/^0000://g"`
		lspci -s $sid|sed "s/^[^ ]* //g"
		ethtool $nic|grep "Link\|Speed\|Dup"
		echo "shortname: unknown"
		ifconfig $nic|grep HWaddr|sed "s/.*HWaddr/mac:/g"
	done
)', 
/*
Target: 0 Host,Channel,Id,Lun: (scsi0,0,0,0), Capacity: 2048 MB
Vendor,Model,Rev: VMware,  VMware Virtual S 1.0 
Type:   Direct-Access                    ANSI SCSI revision: 02
*/
		type=>'keyvalues_span_lines',
		matcher=>'/([^:]+): (.*)/',
		arrayret=>true,
		trimkey=>" \t",
		newkeys=>array(
			'device'=>'physicdevice', 'firmware-version'=>'firmware', 'Ethernet controller'=>'model',
			'Speed'=>'speed', 'Duplex'=>'duplex', 'Link detected'=>'linkup',
		),
		newvalues=>array(
			'shortname'=>'MOD_network_niclist::get_shortname',
			'mac'=>'tolower',
		),
/*
                    [device] => eth0
                    [driver] => e1000
                    [version] => 8.0.6-NAPI
                    [firmware-version] => N/A
                    [bus-info] => 0000:02:01.0
                    [Ethernet controller] => Intel Corporation 82545EM Gigabit Ethernet Controller (Copper) (rev 01)
                    [Speed] => 1000Mb/s
                    [Duplex] => Full
                    [Link detected] => yes

*/
	),
);

static function get_shortname($v, $record){
	return "$record[physicdevice] ($record[driver],$record[speed])";
}
var $defaultcmds = array(read=>'read', destroy=>'faulty', update=>'faulty', create=>'faulty');

}
?>
