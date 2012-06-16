<?php
$act = $_REQUEST['_act'];
$mid = strtolower($_REQUEST['mid']);
if (!$mid) $mid = 'host';
$data = array();
$records = $_REQUEST['records'];
if ($act == 'read'){
	exec("sqlite3 --header sqlitedb 'SELECT rowid,* FROM $mid'", $out, $status);
	if ($status){
		$output = array(success=>false);
	}else{
		$rows = explode('|', $out[0]);
		array_shift($out);
		$output = array(success=>true, data=>array());
		foreach($out as $line){
			$row = array();
			$raw = explode('|', $line);
			foreach($rows as $i=>$col){
				$row[$col] = $raw[$i];		
			}
			$output[data][] = $row;
		}
		$data = $output[data];
	}
	if (0){
		if ($_REQUEST['seqid']) sleep(2);
		if ($_REQUEST['seqid'] >= 2)
			$output=array(success=>true, data=>$data, msg=>'server job done.');
		else
			$output=array(success=>false, pending=>array(
				seq=>$_REQUEST['seqid'],
				msg=>'big job pending...'.$_REQUEST['seqid'],
				text=>'server doing '.$_REQUEST['_act'].' '.($_REQUEST['seqid']/2*100).'%',
				title=>'Server Doing Title',
				number=>$_REQUEST['seqid']/2));
	}
}else{
	if ($_REQUEST['seqid']) sleep(2);
	$count = 2;
	if (0 || $_REQUEST['seqid'] >= $count)
		$output=array(success=>false, msg=>'server job fail.');
	else
		$output=array(success=>false, pending=>array(
			seq=>$_REQUEST['seqid'],
			msg=>'big job pending...'.$_REQUEST['seqid'],
			text=>'server doing '.$_REQUEST['_act'].' '.($_REQUEST['seqid']/$count*100).'%',
			title=>'Server Doing Title',
			number=>$_REQUEST['seqid']/$count
		));
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
