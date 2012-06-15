<?php
$mid = $_REQUEST['mid'];
if (!$mid) $mid = 'host';
exec("sqlite3 --header sqlitedb 'SELECT rowid,* FROM $mid'", $out, $status);
if ($status){
	$output = array(result=>false);
}else{
	$rows = explode('|', $out[0]);
	array_shift($out);
	$output = array(result=>true, data=>array());
	foreach($out as $line){
		$row = array();
		$raw = explode('|', $line);
		foreach($rows as $i=>$col){
			$row[$col] = $raw[$i];		
		}
		$output[data][] = $row;
	}
}

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
