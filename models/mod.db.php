<?php
class MOD_db{

var $mid;
var $useservice=array(read=>false, create=>false, update=>false, destroy=>false);

function run_as_service($params, $records){
	$act = $params[_act];
	return $this->useservice[$act];
}

function __construct($mid){
	$this->mid = $mid;
}

private function exopen($mode, $dbpath=null){
	if ($dbpath == null) return $this->open($mode);
	return $this->open($mode, $dbpath, $isEx=true);
}

function log($level, $tag, $info){
	echo "LOG@$level $tag $info";
}

private function removeunused(&$array){
	for ($i=0, $len=count($array); $i<$len; $i++){
		unset($array[$i]);
	}
	return $array;
}

function opendb($write=false, $usepreg=false, $dbpath='/etc/sysdb'){
	if (!$write) $flags = SQLITE3_OPEN_READONLY;
	else $flags = SQLITE3_OPEN_READWRITE;
	$db = new SQLite3($dbpath, $flags);
	if (!$db){
		throw new Exception("open db $dbpath fail!");
	}
	if ($usepreg && ($db->createFunction("preg_match", "preg_match", 2) === FALSE)){
		throw new Exception("can't create preg_match function: ". $db->lastErrorMsg());
	}
	return $db;
}

function dbquery($state, $dbpath='/etc/sysdb', $maxretry=3){
	$db = $this->opendb(false, preg_match('/preg_match(/', $state));
	$r = $this->_dbquery($db, $state, $maxretry);
	$db->close();
	return $r;
}

private function _dbquery($db, $state, $maxretry=3){
	$errmsg = "";
	$retry = 0;
	$ret = false;
	do{
		$result = $db->query($state);
		if (!$result){
			$errmsg = $db->lastErrorMsg();
			$retry++;
			$this->log(INFO, 'dbop', "'$state' fail($errmsg), retryleft:".($maxretry-$retry));
			$ret = false;
		}else{ 
			$rows = array();
			while($row = $result->fetchArray()){
				$rows[] = $this->removeunused($row);
			};
			$ret = $rows;
			break;
		}
	} while(preg_match("/database is locked/", $errmsg) && $retry <= $maxretry && !sleep(1));
	if ($ret === false) throw new Exception("'$state' fail($retry retries): $errmsg.");
	return $ret;
}


function dbexec($state, &$newrecord=null, $dbpath='/etc/sysdb', $maxretry=3){
	$db = $this->opendb($write=true);
	$r = $this->_dbexec($db, $state, $newrecord, $maxretry);
	$db->close();
	return $r;
}

private function _dbexec($state, &$newrecord, $maxretry=3){
	$errmsg = "";
	$retry = 0;
	$ret = false;
	do{
		$ret = $db->exec($state);
		if ($ret){
			$newid = $db->lastInsertRowID();
			$changes = $db->changes();
			if($newrecord !== null){
				$newrecord = $this->_dbquery("SELECT rowid, * FROM $newrecord WHERE rowid='$newid'");
			}
			return $changes;
		}else{
			$errmsg = $db->lastErrorMsg();
			$retry++;
			$this->log(INFO, 'dbop', "'$state' fail($errmsg), retryleft:".($maxretry-$retry));
			$ret = false;
		}
	} while(preg_match("/database is locked/", $errmsg) && $retry <= $maxretry && !sleep(2));
	if ($ret === false) throw new Exception("'$state' fail($retry retries): $errmsg.");
	return $ret;
}

private function get_update_sets($db, $records){
	$sets =null;
	foreach($records as $k=>$v){
		if ($k == 'id') continue;
		if ($k == 'rowid') continue;
		if ($sets){
			$sets .= ", $k = '".$db->escapeString($v)."'";
		}else{
			$sets .= "$k = '".$db->escapeString($v)."'";
		}
	}
	return $sets;
}
private function get_create_values($db, $records, &$cols){
	$cols = null;
	$values =null;
	foreach($records as $k=>$v){
		//skip id: primary key
		if ($k == 'id') continue;
		if ($k == 'rowid') continue;
		if ($cols){
			$cols .= ", $k";
			$values .= ", '".$db->escapeString($v)."'";
		}else{
			$cols = "$k";
			$values .= "'".$db->escapeString($v)."'";
		}
	}
	return $values;
}

function read($params, $records){
	$db = $this->opendb();
	$cond = $params[_condition];
	$table = $this->mid;
	if ($cond) $sql = "SELECT rowid, * FROM $table WHERE $cond";
	else $sql = "SELECT rowid, * FROM $table";
	try{
		$r = $this->_dbquery($db, $sql);
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
	$db->close();
	return array(
		success=>true,
		data=>$r,
	);
}

private function get_cond_from_records($records, $params){
	$cond = '';
	foreach($records as $record){
		if (isset($record[rowid])){
			$cond .= $cond?"rowid='$record[rowid]'":" OR rowid='$record[rowid]'";
		}else{
			if ($this->primarykey){
				$pk = $this->primarykey;
				$pv = $record[$pk];
				$cond .= $cond?"$pk='$pv'":" OR $pk='$pv'";
			}
		}
	}
	return $cond;
}

function update($params, $records){
	$db = $this->opendb(true);
	$sets = $this->get_update_sets($records);
	$table = $this->mid;
	$cond = $params[_condition];
	if (!$cond) $cond = $this->get_cond_from_records($records, $params);
	if ($cond)
		$sql = "UPDATE ".$tablename." SET $sets WHERE $cond";
	else
		$sql = "UPDATE ".$tablename." SET $sets";
	try{
		$r = $this->_dbexec($db, $sql);
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
	$db->close();
	return array(
		success=>true,
		data=>$r,
	);
}

function destroy($params, $records){
	$db = $this->opendb(true);
	$cond = $params[_condition];
	$table = $this->mid;
	if ($cond) $sql = "DELETE FROM $table WHERE $cond";
	else{
		if (!$params[_confirm]) throw new Exception("destroy all data in $table, need confirm!");
		$sql = "DELETE * FROM $table";
	}
	try{
		$r = $this->_dbexec($db, $sql);
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
	$db->close();
	return array(
		success=>true,
		changed=>$r,
	);
}

function create($params, $records){
	$db = $this->opendb(true);
	$table = $this->mid;
	$values = $this->get_create_values($records, $cols);
	$sql ="INSERT INTO ".$tablename." ($cols) VALUES ($values)";
	$newrecord = $table;
	try{
		$c = $this->_dbexec($db, $sql, $newrecord);
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
		);
	}
	$db->close();
	return array(
		success=>true,
		data=>$newrecord,
	);
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
