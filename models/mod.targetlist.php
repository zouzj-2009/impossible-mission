<?php
include_once('../models/mod.base.php');
class MOD_targetlist extends MOD_base{
static $pconfigs = array(
	'read'=>array(
		cmd=>'(
	cat /proc/scsi_target/scsi_target|sed "s/ MB$/MB/g"
)', 
/*
Target: 0 Host,Channel,Id,Lun: (scsi0,0,0,0), Capacity: 2048 MB
Vendor,Model,Rev: VMware,  VMware Virtual S 1.0 
Type:   Direct-Access                    ANSI SCSI revision: 02
*/
		type=>'records_span_lines',
		recordstart=>'/^Target:/',
		recordid=>'/^Target: *([0-9]*) /',
		fieldstype=>'mixed',
		//debug=>true,
		//debugall=>true,
		fieldsmode=>array(
			'line1_3'=>array(
				gmatcher=>'/^Target:|^ *Type:/',
				type=>'keyvalues_in_one_line',
				matcher=>'/(Host,Channel,Id,Lun:|Capacity:|Type:|ANSI SCSI revision:) *([^ ]*)/',
				mergeup=>true,
				newkeys=>array(
					'Host,Channel,Id,Lun'=>'sid', 'Type'=>'type', 'ANSI SCSI revision'=>'sbcversion',
					'Capacity'=>array('size','bytesize'),
				),
				newvalues=>array(
					sid=>array(
						type=>'record_in_one_line',
						fieldsep=>'/\(scsi|,|\)/',
						fieldnames=>'_,host,channel,scsiid,lun',
						mergeup=>true,
					),
					bytesize=>'bytesize',
					size=>'filesize',
				),
			),
			'line2'=>array(
				gmatcher=>'/^Vendor,/',
				type=>'keyvalues_in_one_line',
				matcher=>'/(Vendor,Model,Rev:) *(.*)/',
				mergeup=>true,
				newkeys=>array('Vendor,Model,Rev'=>'product'),
				newvalues=>array(
					product=>'MOD_targetlist::get_product',
				),
			),
		),
		newkeys=>array('record_id'=>array('targetid', 'targetname', 'shortname')),
		newvalues=>array(
			targetname=>'MOD_targetlist::get_targetname',
			shortname=>'MOD_targetlist::get_shortname',
		),

	),
);
static function get_product($v, $record, &$mergeup){
	$vendor = substr($v, 0, 8);
	$model = substr($v,9, 16);
	$rev = substr($v, 26);
	$mergeup = true;
	return array(vendor=>$vendor, model=>$model, rev=>$rev);
}

static function get_targetname($v, $record, &$mergeup){
	return "iqn.2005-05.cn.com.odysys.iscsi.".gethostname().":".$record[targetid];
}

static function get_shortname($v, $record, &$mergeup){
	return "iqn.20...".gethostname().":".$record[targetid];
}
var $defaultcmds = array(read=>'read', destroy=>'faulty', update=>'faulty', create=>'faulty');

}
?>
