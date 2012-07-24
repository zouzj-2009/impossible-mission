<?php
include_once('../../models/core/mod.base.php');
class MOD_modlist extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

function do_read($params){
	exec(" ls ../models/mod.*|grep -v skeleton ", $out);
	$r = array();
	foreach ($out as $modfn){
		$modfn = basename($modfn);
		include_once("../models/$modfn");
		$modname = "MOD_".preg_replace("/^mod\.|\.php$/", '', $modfn);
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
