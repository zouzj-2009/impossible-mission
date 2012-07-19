<?php
include_once('../models/mod.servable.php');
class MOD_jobtest extends MOD_servable{

var $useservice=array(read=>true);
function do_read($params, $records){
	$count = 0;
	$max = 5; //1000;
	while (++$count < $max){
		echo "output test runing 5x$count\n";
		$this->sendPending("Running 5 x $count seconds ...", $count/$max);
		for($i =0; $i<100; $i++) $this->callmod('netconfig', 'read', $params, $records);
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
