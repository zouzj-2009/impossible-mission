<?php
include_once('../../models/core/mod.base.php');
class MOD_scsidisk extends MOD_base{
static $pconfigs = array(
	'list'=>array(
		cmd=>'(
	for dev in `ls /sys/block/|grep ^sd`
	do
		devneed=%dev%
		[ "$devneed" != "" -a "$devneed" != $dev ]  && continue;
		echo "blockdev: $dev"
		cd /sys/block/$dev
		for key in `ls|grep -v ^stat`
		do
			[ -d "$key" ] && continue
			if [ "$key" = "size" ];then
				echo "size: $((`cat size`*512))"
			else
				echo "$key: "`cat "$key"`
			fi
		done
		cd "/sys/block/$dev/device/"
		echo "scsi_device: "`ls scsi_device:*|sed "s/scsi_device://g"`
		echo "sg_device: "`ls scsi_generic:*|sed "s/scsi_generic://g"`
		for key in `ls |grep -v "delete\|rescan\|power"`
		do
			[ -d "$key" ] && continue
			echo "$key: "`cat "$key"`
		done
		echo "targetid: "`gettid.sh $dev|awk \'{print $7}\'`
		echo "shortname: unknown"
		cd -
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
			'scsi_device'=>array('scsi_device', 'host'),
			'size'=>array('bytesize', 'capacity'),
			'model'=>'product',
			'rev'=>'revision',
			'iorequest_cnt'=>'IORequested',
			'ioerr_cnt'=>'IOError',
			'iodone_cnt'=>'IODone',
			'removable'=>'fixed',
		),
		newvalues=>array(
			'shortname'=>'MOD_scsidisk::get_shortname',
			'host'=>'MOD_scsidisk::get_host',
			'capacity'=>'filesize',
			'IORequested'=>'todec',
			'IODone'=>'todec',
			'IOError'=>'todec',
			'fixed'=>'!boolean',
			
		),
	),
	'remove'=>array(
		cmd=>'(
	echo "#@LOG@INFO remove %blockdev% %scsi_device%\n"
	echo scsi remove-single-device `echo "%scsi_device%"|sed "s/:/ /g"`  >/proc/scsi/scsi
)',
	),
);

//don't read 
var $readbeforeupdate = false;	
//don't save
var $savechangeconfig = null;	

static function get_host($v, $record){
	$s = explode(':', $v);
	return "host$s[0]";
}
static function get_shortname($v, $record){
	return "$record[blockdev] ($record[capacity]/$record[vendor])";
}
var $defaultcmds = array(read=>'list', destroy=>'remove', update=>'faulty', create=>'faulty');

}
?>
