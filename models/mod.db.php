<?php
class MOD_db{

var $mid;
function __construct($mid){
	$this->mid = $mid;
}
function read($params, $records){
	$output = array();
	$data = array();
	exec("sqlite3 --header sqlitedb 'SELECT rowid,* FROM $this->mid'", $out, $status);
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
	return $output;
}

function update($params, $records){
	return MOD_db::pending_test($params, $records);
}
function destroy($params, $records){
	return MOD_db::pending_test($params, $records);
}
function create($params, $records){
	return MOD_db::pending_test($params, $records);
}

function pending_test($params, $records){
	global $_REQUEST;
	$count = 2;
	if ($_REQUEST['seqid']) sleep(2);
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
	return $output;
}

}
?>
