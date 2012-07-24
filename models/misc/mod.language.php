<?php
include_once('../../models/core/mod.base.php');
class MOD_language extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

function do_read($params){
	return array(
		success=>true,
		data=>array(
			array(language=>'中文(Simplified Chinese)', lang=>'zh_cn'),
			array(language=>'English', lang=>'en'),
		),
	);
}

}
?>
