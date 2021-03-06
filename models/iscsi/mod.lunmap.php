<?php
include_once('../../models/core/mod.base.php');
class MOD_iscsi_lunmap extends MOD_base{

static $pconfigs = array(
	'get'=>array(
		cmd=>'(
	cat /proc/scsi_target/iscsi_target/lunmapping 
)',
		//pharse config:
		type=>'one_record_per_line',
		ignore=>'/^ *$|^ena|^dis/',
		fieldsep=>'/  */',
		fieldnames=>'_ignore_,sourceip,_,netmask,,targetid,access,_key_,_auto_,_key_,_auto_,_key_,_auto_,_key_,_auto_,_key_,_auto_',
		newkeys=>array(
			agusr=>'initiatoruser',agpwd=>'initiatorpass',svusr=>'targetuser',svpass=>'targetpass',dest=>'destinationip',
		),
	),	
	'create'=>array(
		cmd=>'(
	[ ! -z "%destinationip%" ] && dest="dest %destinationip%" || dest=""
	[ ! -z "%targetid%" ] && target="target %targetid%" || target=""
	[ ! -z "%targetuser%" ] && chap=" svusr %targetuser% svpwd %targetpass%" || chap=""
	[ ! -z "%initiatoruser%" ] && mutalchap=" agusr %initiatoruser% agpwd %initiatorpass%" || mutalchap=""
	echo "append src %sourceip% mask %netmask% $dest$target %access%$chap$mutalchap" >/proc/scsi_target/iscsi_target/lunmapping
	ret=$?
	cat /proc/scsi_target/iscsi_target/lunmapping|grep "src %sourceip% mask %netmask% $dest$target %access%$chap$mutalchap"
	cat /proc/scsi_target/iscsi_target/lunmapping >/etc/sysconfig/lunmapsaving
	exit $ret
)',
		refcmd=>'MOD_iscsi_lunmap::get',
	),	

	'delete'=>array(
		cmd=>'(
	[ ! -z "%destinationip%" ] && dest="dest %destinationip%" || dest=""
	[ ! -z "%targetid%" ] && target="target %targetid%" || target=""
	[ ! -z "%targetuser%" ] && chap=" svusr %targetuser% svpwd %targetpass%" || chap=""
	[ ! -z "%initiatoruser%" ] && mutalchap=" agusr %initiatoruser% agpwd %initiatorpass%" || mutalchap=""
	echo "delete src %sourceip% mask %netmask% $dest$target %access%$chap$mutalchap" >/proc/scsi_target/iscsi_target/lunmapping
	ret=$?
	cat /proc/scsi_target/iscsi_target/lunmapping >/etc/sysconfig/lunmapsaving
	exit $ret;
)',
	),	
	'update'=>array(
		cmd=>'(
	echo "#@ERROR@TRACE lunamp::update operation not supported!\n"
	exit 1
)',
	),
);

var $defaultcmds=array(
	read=>'get',
	create=>'create',
	destroy=>'delete',
	update=>'update',
);

var $saving_fields = 'sourceip,netmask, taragetid,access,destinationip,targetuser,targetpass,initiatoruser,initiatorpass';
var $savechangeconfig = array(usingfile=>'lunmap.rules');

var $keyids = array('sourceip','netmask','targetid','destinationip','access');


}
?>
