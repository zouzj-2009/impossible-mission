<?php
include_once('../../models/core/mod.servable.php');
class MOD_aix_osinfo extends MOD_servable{
var $exectype = 'ssh';
var $useservice=array(read=>true);
static $pconfigs = array(
	'osinfo'=>array(
		cmd=>'(
        echo "os=`uname -s`"
        echo "release=`uname -v`"."`uname -r`"
        echo "hostname=`uname -n`"
        echo "arch=`uname -p`"
        echo "hostid=`uname -m`"

)',
		type=>'keyvalues_span_lines',
		arrayret=>true,
	),
);
var $savechangeconfig = null;
var $defaultcmds = array(read=>'osinfo', destroy=>'faulty', update=>'faulty', create=>'faulty');

}
?>
