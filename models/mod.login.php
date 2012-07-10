<?php
include_once('../models/mod.base.php');
class MOD_login extends MOD_base{

var $logfiles = array(
//	raidlog=>'/var/log/hptlog',
//	pcilog=>'/var/log/env_lspci',
);

var $savechangeconfig = array(usingfile=>'adminusers');
function read($params, $records=array()){
	if (!$params){//get all
		//todo: check login, only check in user, and have rights can do this!
		if (!$_SESSION[loginuser]) throw new Exception ('user not login!', -1);
		$r = $this->load_sysconfig();
		if (!$r) $r = array(array(
			username=>'admin',
			password=>md5('admin'),
		));
		return array(success=>true, data=>$r);
	}else if ($records){//check login!
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
		return array(success=>false, authfail=>true, msg=>'login fail.');
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

}
?>
