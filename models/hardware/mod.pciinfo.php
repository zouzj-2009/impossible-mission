<?php
include_once('../../models/core/mod.base.php');
class MOD_hardware_pciinfo extends MOD_base{
static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	lspci -m
)',
/*
02:00.0 USB Controller: VMware: Unknown device 0774 (prog-if 00 [UHCI])
        Subsystem: VMware: Unknown device 1976
        Flags: bus master, medium devsel, latency 64, IRQ 59
        I/O ports at 2080 [size=32]

02:01.0 Ethernet controller: Intel Corporation 82545EM Gigabit Ethernet Controller (Copper) (rev 01)
        Subsystem: VMware PRO/1000 MT Single Port Adapter
        Flags: bus master, 66Mhz, medium devsel, latency 0, IRQ 67
        Memory at c9020000 (64-bit, non-prefetchable) [size=128K]
        Memory at c9000000 (64-bit, non-prefetchable) [size=64K]
        I/O ports at 2000 [size=64]
        Expansion ROM at d8400000 [disabled] [size=64K]
        Capabilities: [dc] Power Management version 2
        Capabilities: [e4] PCI-X non-bridge device.
*/
		type=>'csv',
		delimiter=>' ',
		enclosure=>'"',
		escape=>'\\',
		fieldnames=>'busid,type,vendor,model,version,oem,description,longname',
		newkeys=>array(
		),
		newvalues=>array(
			longname=>'MOD_hardware_pciinfo::get_longname',
		),
		skiprecord=>array(
			type=>array('PCI bridge'),
		),
	),
);

static function get_longname($v, $record){
	return "$record[model]($record[description] $record[version])";
}
var $saving_fields = 'dev,ipaddress,broadcast,netmask,mtu,is_up,ipv6address,ipv6prefix';
var $savechangeconfig = array(usingfile=>'pciinfo');
var $defaultcmds = array(read=>'get', destroy=>'faulty', update=>'faulty', create=>'faulty');

}
?>
