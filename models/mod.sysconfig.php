<?php
include_once('../models/mod.base.php');
class MOD_sysconfig extends MOD_base{

function before_read($params, &$records){
	if (!$params[_mod]) return;
	//read using mod
	$m = explode(".", $params[_mod]);
	$mod = $this->getmod($m[0]);
	$type = $m[1];
	$records = $mod->load_sysconfig($type = $type?$type:'current');
	return 'return';
}

function after_read($params, &$records){
	foreach($records as $i=>$record){
		if ($record[bootup]) $records[$i][bootup] = unserialize($record[bootup]);
		if ($record[shutdown]) $records[$i][shutdown] = unserialize($record[shutdown]);
		if ($record[last]) $records[$i][last] = unserialize($record[last]);
		if ($record['current']) $records[$i]['current'] = unserialize($record['current']);
	}
}

}
?>
