<?php
include_once('../models/mod.servable.php');
class MOD_jobtest extends MOD_servable{

var $useservice=array(read=>true);
function read($params, $records){
	$count = 0;
	while (++$count < 20){
		$this->sendPending("Running 5 x $count seconds ...", null, $count/20);
		sleep(5);
	}
	$r = array(
		success=>true,
		data=>array(
			array(abcd=>1234),
		),
		msg=>"ok, done now",
	);
	return $r;
}

}
?>
