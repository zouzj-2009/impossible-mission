<?php
include_once('../models/mod.servable.php');
class MOD_jobtest extends MOD_servable{

var $useservice=array(read=>true);
function read($params, $records){
	$count = 0;
	while (++$count < 20){
		echo "runing 5x$count\n";
		$this->sendPending("Running 5 x $count seconds ...", $count/20);
		for($i =0; $i<10; $i++) $this->callmod('netconfig', 'read', $params, $records);
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
