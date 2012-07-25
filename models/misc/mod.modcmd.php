<?php
include_once('../../models/core/mod.base.php');
class MOD_misc_modcmd extends MOD_base{

function do_read($params, $records){
//valid params:
//	_mod:	mod name
//	_cmd:	cmd defined in mod's pconfig
//	_retvalue:	true for return origin cmd return value instead of printed string.

	$this->check_need_vars($params, '_cmd,_mod');
	try{
		$mod = $this->getmod($params[_mod]);
		$data = array();
		$r = $mod->callcmd($params[_cmd], $cmderror, $params, $data);
		return array(
			success=>true,
			data=>array(
				array(
					cmd=>$params[_cmd],
					cmderror=>$cmderror,
					//return value or printed value
					data=>$params[_retvalue]?$r:print_r($r, true),
				),
			)
		);
	}catch(Exception $e){
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
}

}
?>
