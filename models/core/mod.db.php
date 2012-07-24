<?php
include_once("../../models/core/debugee.php");
class MOD_db extends DEBUGEE{

var $mid;
var $useservice=array(read=>false, create=>false, update=>false, destroy=>false);

function run_as_service($params, $records){
	$act = $params[_act];
	return $this->useservice[$act];
}

function __construct($mid, $taskid=null, $modconfig=array()){
	$this->mid = $mid;
	parent::__construct($modconfig[debugon], $modconfig[debugsetting]);
	$this->trace_in(DBG, 'modconfig', $modconfig);
}

private function exopen($mode, $dbpath=null){
	if ($dbpath == null) return $this->open($mode);
	return $this->open($mode, $dbpath, $isEx=true);
}

function loginfo($level, $tag, $info){
	echo "LOG@$level $tag $info\n";
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
	$db = $this->opendb(false, preg_match('/preg_match\(/', $state));
	$r = $this->_dbquery($db, $state, $maxretry);
	$db->close();
	return $r;
}

private function _dbquery($db, $state, $maxretry=3){
	$errmsg = "";
	$retry = 0;
	$ret = false;
	$this->loginfo(TRACE, 'dbop', $state);
	do{
		$result = $db->query($state);
		if (!$result){
			$errmsg = $db->lastErrorMsg();
			if (!preg_match("/database is locked/", $errmsg)){
				$this->loginfo(ERROR, 'dbop', "$state fail: $errmsg");
				throw new Exception("dbquery $state fail: $errmsg");
			}
			$retry++;
			$this->loginfo(INFO, 'dbop', "'$state' fail($errmsg), retryleft:".($maxretry-$retry));
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

private function _dbexec($db,$state, &$newrecord=null, $maxretry=3){
	$errmsg = "";
	$retry = 0;
	$ret = false;
	$this->loginfo(TRACE, 'dbop', $state);
	do{
		$ret = $db->exec($state);
		if ($ret){
			$newid = $db->lastInsertRowID();
			$changes = $db->changes();
			if($newrecord !== null){
				$newrecord = $this->_dbquery($db, "SELECT rowid, * FROM $newrecord WHERE rowid='$newid'");
			}
			return $changes;
		}else{
			$errmsg = $db->lastErrorMsg();
			if (!preg_match("/database is locked/", $errmsg)){
				$this->loginfo(ERROR, 'dbop', "$state fail: $errmsg");
				throw new Exception("dbexec $state fail: $errmsg");
			}
			$retry++;
			$this->loginfo(INFO, 'dbop', "'$state' fail($errmsg), retryleft:".($maxretry-$retry));
			$ret = false;
		}
	} while(preg_match("/database is locked/", $errmsg) && $retry <= $maxretry && !sleep(2));
	if ($ret === false) throw new Exception("'$state' fail($retry retries): $errmsg.");
	return $ret;
}

private function get_update_sets($db, $record){
	$sets =null;
	foreach($record as $k=>$v){
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
private function get_create_values($db, $record, &$cols){
	$cols = null;
	$values =null;
	foreach($record as $k=>$v){
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

function read($params, $records=null){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
//if records supplied, an no condition, will use these records as condition

	$db = $this->opendb();
	$cond = $params[_condition];
	$table = str_replace(".", "_", $this->mid);
	try{
		if (!$cond){
			if ($records){
				$cond = $this->get_cond_from_records($db, $records, $params);
				if (!$cond) $this->loginfo(WARN, 'dbop', "records supplied for db.read, but no condition made.") ;
				else $this->loginfo(TRACE, 'dbop', "read specified records: $cond");
			}
		}
		if ($cond){
			 $sql = "SELECT rowid, * FROM $table WHERE $cond";
		}else{
			$sql = "SELECT rowid, * FROM $table";
		}
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

protected function get_primarykey(){
	if ($this->primarykey) return $this->primarykey;
	return null;
}

private function get_cond_from_records($db, $records, $params){
	$cond = '';
	$pk = $this->get_primarykey();
	foreach($records as $record){
		if (isset($record[rowid])){
			$cond .= $cond?" OR rowid='$record[rowid]'":"rowid='$record[rowid]'";
		}else{
			if ($pk){
				$pv = $db->escapeString($record[$pk]);
				$cond .= $cond?" OR $pk='$pv'":"$pk='$pv'";
			}
		}
	}
	return $cond;
}

function update($params, $records){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$db = $this->opendb(true);
	$table = str_replace(".", "_", $this->mid);
	if ($params[_writetable]) $table = $params[_writetable];
	$gcond = $params[_condition];
	$updated = array();
	$old = array();
	$changes = 0;
	try{
		if ($gcond && count($records) != 1){
			throw new Exception ("can't update multiple records or no records while condition '$gcond ' subclourse supplied.");
		}
		foreach($records as $record){
			$sets = $this->get_update_sets($db, $record);
			if ($gcond){//execed only one time
				$sql = "UPDATE $table SET $sets WHERE $gcond";
				if ($params[_readold]) $old = $this->_dbquery($db, "SELECT FROM $table WHERE $gcond");
			}else{
				$cond = $this->get_cond_from_records($db, array($record), $params);
				if (!$cond) throw new Exception("can't found key fields in record, update record fail.");
				if ($params[_readold]){
					$oldone = array_shift($this->_dbquery($db, "SELECT FROM $table WHERE $cond"));
					if (!$oldone) throw new Exception("update non-existed record($cond)!");
					$old[] = $oldone;
				}
				$sql = "UPDATE ".$table." SET $sets WHERE $cond";
			}
			$changes += $this->_dbexec($db, $sql);
			$updated[] = $record;
		}
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
			changes=>$changes,
			updated=>$updated,
			old=>$old,
		);
	}
	$db->close();
	return array(
		success=>true,
		updated=>$updated,
		changes=>$changes,
		old=>$old,
	);
}

function destroy($params, $records){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$db = $this->opendb(true);
	$cond = $params[_condition];
	$table = str_replace(".", "_", $this->mid);
	if ($params[_writetable]) $table = $params[_writetable];
	$changes = 0;
	$old = array();
	try{
		if ($cond){
			if ($params[_readold]) $old = $this->_dbquery("SELECT rowid, * FROM $table WHERE $cond");
			$changes = $this->_dbexec($db, "DELETE FROM $table WHERE $cond");
		}else{
			if (!$records) throw new Exception("destroy need condition or records supplied.");
			if (!$params[_destroy_one_by_one]){
				$cond = $this->get_cond_from_records($db, $records, $params);
				if (!$cond) throw new Exception("can't found key fields in records, destroy records fail.");
				$changes = $this->_dbexec($db, "DELETE FROM $table WHERE $cond");
				$old = $records;
			}else{
				foreach($records as $record){
					$cond = $this->get_cond_from_records($db, array($record), $params);
					if (!$cond) throw new Exception("can't found key fields in record, destroy single record fail.");
					$changes += $this->_dbexec($db, "DELETE FROM $table WHERE $cond");
					$old[] = $record;
				}
			}
		}
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
			destroied=>$old,
			changes=>$changes,
		);
	}
	$db->close();
	return array(
		success=>true,
		changed=>$changes,
		destroied=>$old,
	);
}

function create($params, $records){
	$this->trace_in(TRACE, __FUNCTION__, $params, $records);
	$db = $this->opendb(true);
	$table = str_replace(".", "_", $this->mid);
	if ($params[_writetable]) $table = $params[_writetable];
	$changes = 0;
	$created = array();
	try{
		foreach($records as $record){
			$values = $this->get_create_values($db, $record, $cols);
			$sql ="INSERT INTO ".$table." ($cols) VALUES ($values)";
			$newrecord = $table;
			$changes += $this->_dbexec($db, $sql, $newrecord);
			$created[] = $newrecord;
		}
	}catch(Exception $e){
		$db->close();
		return array(
			success=>false,
			msg=>$e->getMessage(),
			changes=>$changes,
			created=>$created,
		);
	}
	$db->close();
	return array(
		success=>true,
		changes=>$changes,
		created=>$created,
	);
}

function pending_test($params, $records){
	global $_REQUEST;
	$count = 2;
	if ($_REQUEST['seqid']) sleep(2);
	if (0 || $_REQUEST['seqid'] >= $count)
		$output=array(success=>false, msg=>'server task fail.');
	else
		$output=array(success=>false, pending=>array(
			seq=>$_REQUEST['seqid'],
			msg=>'big task pending...'.$_REQUEST['seqid'],
			text=>'server doing '.$_REQUEST['_act'].' '.($_REQUEST['seqid']/$count*100).'%',
			title=>'Server Doing Title',
			number=>$_REQUEST['seqid']/$count
			));
	return $output;
}

}
?>
