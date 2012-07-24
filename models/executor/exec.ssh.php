<?php
include_once('../../models/core/debugee.php');
class EXEC_ssh extends DEBUGEE{
protected $config; //array of: usernmae, password, ip, port, scpdir, mode=session|shell
protected $logged;
protected $ssh_conn;
protected $stdio;
protected $stderr;
protected $defcfg = array(
	mode => 'session',
	scpdir => '/tmp/',
);

function __construct ($config){
	$c = $this->defcfg;
	$config = array_merge($c, $config);
	$this->config = $config;
	parent::__construct($config[_debugon], $config[_debugsetting]);
	$this->trace_in(DBG, "exec.ssh", $config);
}

function login($username=null, $password=null, $mode=null){
	if ($this->logged) return true;
	$this->ssh_conn = ssh2_connect($this->config[ip], $this->config[port]);
	if ($username) $this->config[username] = $username;
	if ($password) $this->config[password] = $password;
	if ($mode) $this->config[mode] = $mode;
	if (!$this->ssh_conn) return false;
	if (!ssh2_auth_password($this->ssh_conn, $this->config[username], $this->config[password])){
		return false;
	}
	
	if ($this->config[mode] == "shell"){
		$this->stdio = ssh2_shell($this->ssh_conn, "linux");
		$this->stderr = ssh2_fetch_stream($this->stdio, SSH2_STREAM_STDERR);
	}
	$this->logged = true;
	return true;
}


function exec($taskname, $cmd, &$out, &$ret, &$err){
	if ($this->config[mode] == "shell"){
		throw new Exception("shell mode ssh-exec not supported yet.");
		return $this->exec_shell_mode($taskname, $cmd, $out, $ret, $err);
	}else{
		return $this->exec_session_mode($taskname, $cmd, $out, $ret, $err);
	}
}

function exec_shell_mode($taskname, $cmd, &$out, &$ret, &$err){
	if (!$this->login() || !$this->stdio){
		throw new Exception("$taskname exec fail: ssh-login fail.");
	}
	stream_set_blocking($this->stdio, true);

	$startline = $cmd ."; echo \"___GOT_CMD_RETURN_AS____(\$?)\"";
	fwrite($this->stdio, $startline."\n");
	fflush($this->stdio);
	$outlns = "";
	$err = array();
	$skipped = "";
	while($outbuf = fgets($this->stdio, 4096)){
		$outlns .= $outbuf;
		$pos = strpos($outlns, $startline);
		while (!($pos === false)){
			$skipped .= substr($out, 0, $pos);
			$outlns = substr($outlns, $pos+strlen($startline));
			$pos = strpos($outlns, $startline);
		}
		if (preg_match("/___GOT_CMD_RETURN_AS____\((\d+)\)/", $outbuf, $match)){
			$ret = $match[1][0];
			$outlns = str_replace("___GOT_CMD_RETURN_AS____($ret)", "", $outlns);
			break;
		}
	}
	
	$out = explode("\n", $outlns);
	if (!is_numeric($ret)){
		$ret = -1;
	}
	return $outlns;
}


function exec_session_mode($taskname, $cmd, &$out, &$ret, &$err=null){
	if (!$this->login()){
		throw new Exception("$taskname exec fail: ssh-login fail.");
	}
	if (!($shellstdio = ssh2_exec($this->ssh_conn, $cmd."; echo \"___GOT_CMD_RETURN_AS____(\$?)\""))){
		//auto relogin?
		throw new Exception("$taskname exec fail: session-exec fail.");
	}
	stream_set_blocking($shellstdio, true);
	$outlns = "";
	$err = array();
	while($outbuf = fgets($shellstdio, 4096)){
		$outlns .= $outbuf;
		if (preg_match("/___GOT_CMD_RETURN_AS____\((\d+)\)/", $outbuf, $match)){
			$ret = $match[1][0];
			$outlns = str_replace("___GOT_CMD_RETURN_AS____($ret)", "", $outlns);
		}
	}
	fclose($shellstdio);
	$out = explode("\n", $outlns);
	if (!is_numeric($ret)){
		$ret = -1;
	}
	return $outlns;
}

function copy_to_remote($local, $remote, $create_mode=0644){
	if (!$this->login()){
		throw new Exception("scp $local to remote fail: ssh login fail.");
	}
	return ssh2_scp_send($this->ssh_conn, $this->config[scpdir]."/".$local, $remote, $create_mode);
}

function copy_from_remote($remote, $local){
	if (!$this->login()){
		throw new Exception("scp $local to remote fail: ssh login fail.");
	}
	return ssh2_scp_recv($this->ssh_conn, $remote, $this->config[scpdir]."/".$local);
}

//end of EXEC_ssh class 
}
?>
