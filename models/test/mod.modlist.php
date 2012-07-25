<?php
include_once('../../models/core/mod.base.php');
class MOD_test_modlist extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

function do_read($params){
	exec(" find ../../models/|grep -v skeleton|grep -v core|grep 'mod\..*\.php$' ", $out);
	$r = array();
	foreach ($out as $modfn){
		$modn = basename($modfn);
		$moddir = basename(dirname($modfn));
		if (!$moddir) continue;
		$modname = "MOD_$moddir"."_".preg_replace("/^mod\.|\.php$/", '', $modn);
		include_once ($modfn);
		$mod = new $modname();
		$r[] = array(
			name=>$modname,
			taskable=>is_a($mod, 'MOD_servable'),
			keyids=>implode(",", $mod->keyids),
			savedfileds=>$mod->saving_fields,
		);	
	}
	return array( success=>true, data=>$r);
}

//end of class
}
?>
