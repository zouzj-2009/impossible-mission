<?php
include_once('../models/mod.base.php');
class MOD_sysconfig extends MOD_base{

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
