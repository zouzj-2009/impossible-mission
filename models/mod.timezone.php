<?php
include_once('../models/mod.base.php');
class MOD_timezone extends MOD_base{

function do_read($params){
	$tzs = DateTimeZone::listIdentifiers();
	$r = array();
	foreach($tzs as $tz){
		$r[] = array(zone=>$tz, shortname=>$tz);
	}
	return array(success=>true, data=>$r);
}

}
?>
