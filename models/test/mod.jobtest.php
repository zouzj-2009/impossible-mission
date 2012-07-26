<?php
include_once('../../models/core/mod.servable.php');
class MOD_test_jobtest extends MOD_servable{

var $useservice=array(read=>true);
function do_read($params, $records){
	$count = 0;
	$max = 5; //1000;
	if ($params[callremote]){
		for($i=0; $i<$max; $i++)
		$this->callmod_remote(array(host=>'127.0.0.1', user=>'admin', pass=>'admin'),'test.jobtest', 'read', array(called=>1), $records);
	}
	while (++$count < $max){
		echo "output test runing 5x$count\n";
		$this->sendPending("Running 5 x $count seconds ...", $count/$max);
		for($i =0; $i<1; $i++) $this->callmod('network.netconfig', 'read', $params, $records);
		sleep(1);
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
