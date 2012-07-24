<?php
include_once('../../models/core/mod.base.php');
class MOD_scsihost extends MOD_base{
static $pconfigs = array(
	'read'=>array(
		cmd=>'(
	for dev in `ls /sys/class/scsi_host/`
	do
		devneed=%devname%
		[ "$devneed" != "" -a "$devneed" != $dev ]  && continue;
		echo "dev: $dev"
		if [ -d /sys/class/fc_host/$dev ];then
			echo "is_fchost: true"
			is_fc=1
		else
			echo "is_fchost: false"
			is_fc=0
		fi
		cd /sys/class/scsi_host/$dev/
		#for key in `ls cmd_per_lun host_busy state sg_tablesize proc_name`
		for key in `ls cmd_per_lun host_busy state sg_tablesize proc_name driver_version target_mode_enabled model_name model_desc pci_info serial_num 2>/dev/null`
		do
			[ -d $key ] && continue
			echo "$key: `cat $key`"
		done
		#check devicelist
		targetlist=""
		hostno=`echo $dev|sed "s/^host//g"`
		echo "hostno: $hostno"
		for target in `ls /sys/class/scsi_disk/|grep "^$hostno:"`
		do
			if [ "$targetlist" = "" ];then
				targetlist=$target
			else
				targetlist=$targetlist" "$target
			fi
			
		done
		[ "$targetlist" != "" ]  && echo "targets: $targetlist"
		#check pci
		busid=
		pcipath=`find /sys/devices/pci*|grep "scsi_host:$dev$"`
		echo "pcipath: $pcipath"
		if [ ! -z $pcipath ];then
			busid=`dirname $pcipath`
			busid=`dirname $busid`
			busid=`basename $busid|sed "s/^0000://g"`
		fi
		echo "busid: $busid"
		[ ! -z "$busid" ] && lspci -mvs $busid|awk \'{print gensub("Device:\t([0-9a-f]{2}):", "devicdid: \\\\1:", 1)}\'
		[ ! -z "$busid" ] && lspci -s $busid|sed "s/^[^ ]* //g"
		cd -
		echo "shortname: unknown"
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
			'SCSI storage controller'=>'model', 'proc_name'=>'driver',
		),
		newvalues=>array(
			'shortname'=>'MOD_scsihost::get_shortname',
			'mac'=>'tolower',
		),
	),
	'update'=>array(
		cmd=>'(
	echo "#@LOG@INFO rescan %pcipath%\n"
	echo \'- - -\' >%pcipath%/scan
)',
	),
);

//don't read 
var $readbeforeupdate = false;	
//don't save
var $savechangeconfig = null;	

static function get_shortname($v, $record){
	return "$record[dev] ($record[driver], ".substr($record[model], 0, 10)." ...)";
}
var $defaultcmds = array(read=>'read', destroy=>'faulty', update=>'update', create=>'faulty');

}
?>
