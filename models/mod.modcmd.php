<?php
include_once('../models/mod.base.php');
class MOD_modcmd extends MOD_base{

function read($params, $records){

//valid params:
//	_mod:	mod name
//	_cmd:	cmd defined in mod's pconfig
//	_retvalue:	true for return origin cmd return value instead of printed string.

	try{
		$mod = $this->getmod($params[_mod]);
		$data = array();
		$r = $mod->callcmd($params[_cmd], $params, $data);
		return array(
			success=>true,
			data=>array(
				array(
					cmd=>$params[_cmd],
					cmdresult=>$r,
					//return value or printed value
					data=>$param[_retvalue]?$data:print_r($data, true),
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
