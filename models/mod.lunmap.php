<?php
include_once('../models/mod.base.php');
class MOD_lunmap extends MOD_base{

static $pconfigs = array(
	'getlunmap'=>array(
		//cmd=>"cat /proc/scsi_target/iscsi_target/lunmapping", 
		cmd=>'(
	cat /tmp/lunmap
)',
		//pharse config:
		type=>'one_record_per_line',
		ignore=>'/^ *$|^ena|^dis/',
		fieldsep=>'/  */',
		fieldnames=>'_ignore_,sourceip,_,netmask,,targetid,access'
	),	
);

var $defaultcmds=array(read=>'getlunmap');

}
?>
