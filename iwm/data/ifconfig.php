<?php
exec("cat /proc/net/dev|grep '^[ a-z0-9]*:'", $out);
$ret = array();
foreach($out as $line){
	$info = preg_split('/ +/', trim($line));
	$a = array('devname'=>str_replace(':', '', $info[0]),
	'recv'=>$info[1],
	'send'=>$info[9],
	'total'=>$info[1]+$info[9]);
	$ret [] = $a;
}
$output = array('data'=>$ret);
$callback = $_REQUEST['callback'];

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($output) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($output);
}
?>
