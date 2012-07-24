<?php
include_once('../../models/core/mod.base.php');
class MOD_testunit extends MOD_base{
static $pconfigs = array(
	'list'=>array(
		cmd=>'(
find ../../|grep controller|grep js$|grep -v /lib/core|grep -v ../iwm/app|grep -v ../app/unittest|grep -v ../ui/common/|sed "s/app\/controller/controller/"|sed "s/^..\/..\//text: /"|sed "s/\//_/"|sed "s/\//./g"|sed "s/\.js$//"
)', 
		type=>'records_span_lines',
		recordstart=>'/^/',
		fieldstype=>'simple',
		fieldsmode=>array(
			type=>'keyvalues_in_one_line',
			matcher=>'/([^:]*): (.*)/',
		),
	),
);

//don't read 
var $readbeforeupdate = false;	
//don't save
var $savechangeconfig = null;	

var $defaultcmds = array(read=>'list', destroy=>'faulty', update=>'faulty', create=>'faulty');

}
?>
