<?php
include_once('../../models/core/mod.base.php');
class MOD_login extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

var $savechangeconfig = array(usingfile=>'adminusers');
var $keyids = array('username');
function read($params, $records=array()){
	if (!$params && !$records){//get all
		//todo: check login, only check in user, and have rights can do this!
		if (!$_SESSION[loginuser]) throw new Exception ('user not login!', -1);
		$r = $this->load_sysconfig();
		if (!$r) $r = array(array(
			username=>'admin',
			password=>md5('admin'),
		));
		return array(success=>true, data=>$r);
	}else if ($records){//check login! or read old
		if ($params[_readold]){
			if ($_SESSION[loginuser]){
				return array(
					success=>true,
					data=>$this->load_sysconfig('current', $records),
				);
			}
		}
		//if readold but not login, login here, so do_update will login first!
		$users = $this->load_sysconfig();
		if (!$users) $users = array(array(
			username=>'admin',
			password=>md5('admin'),
		));
		$login = array_shift($records);
		foreach($users as $user){
			if ($user[username] == $login[username] && $user[password] == $login[password]){
				$_SESSION['loginuser'] = $login;
				return array(success=>true, data=>array($login), msg=>'login ok.');
			}
		}
		return array(success=>false, authfail=>true, msg=>'login fail.', login=>$login);
	}else if ($params[_logout]){
		unset($_SESSION['loginuser']);
	}
	$r = array(
		username=>'input username ...',
		password=>'',
		logingon=>getenv('SERVER_ADDR')."(".gethostname().")", 
		language=>'zh_cn',
	);
	return array(success=>true, data=>array($r), msg=>'get loging information done.');
}

function do_update($params, $records, $olds){
	$r = array_shift($records);
	if (!$_SESSION[loginuser]) throw new Exception ('user not login!', -1);
	if (!$r['newpassword']){
		if (!$olds) throw new Exception('new password not set!');
		//really login!
		return array(success=>true, updated=>$olds, changes=>1, msg=>'login ok.');
	}
	$o = array_shift($olds);
	if (!$o) throw new Exception("old user $r[username] not found!");
	if ($o[password] != $r[password]) throw new Exception("old password mismatch!");
	$updated = array(username=>$o[username], password=>$r[newpassword]);
	return array(success=>true, updated=>array($updated), changes=>1);
	
}

}
?>
