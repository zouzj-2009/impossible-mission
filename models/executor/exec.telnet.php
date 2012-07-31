<?php
include_once('../../models/core/debugee.php');

class EXEC_telnet extends DEBUGEE{
protected $config; //array of: usernmae, password, ip, port, scpdir, mode=session|shell
protected $logged;
protected $ssh_conn;
protected $stdio;
protected $stderr;
protected $defcfg = array(
	mode=>'session',
	port=>23,
	usebash=>true,
);

protected $sock = NULL;
protected $header1, $header2;

function telnet($host,$port) {
	$this->sock = fsockopen($host,$port);
	socket_set_timeout($this->sock,2,0);
	return $this->sock;
}

function close() {
	if ($this->sock)  fclose($this->sock);
	$this->sock = NULL;
}

function write($buffer) {
	$buffer = str_replace(chr(255),chr(255).chr(255),$buffer);
	fwrite($this->sock,$buffer);
}

function getc() {
	return fgetc($this->sock); 
}

protected $h1sent, $h2sent;
function read_till($what, &$istmout, $timeout=2) {
	socket_set_timeout($this->sock,$timeout,0);
	$buf = '';
	$IAC = chr(255);
	$DONT = chr(254);
	$DO = chr(253);
	$WONT = chr(252);
	$WILL = chr(251);
	$theNULL = chr(0);
	$LR = chr(10);
	$istmout = false;
	while (1) {
		$c = $this->getc();
		if ($c === false){
			$istmout = true;
			return $buf;
		}
		if ($c == $theNULL) { continue; }
		if ($c == "1") { continue; }
		if ($c == $LR && $no_lr) continue;
		if ($c != $IAC) {
			$buf .= $c;
			if ($what == (substr($buf,strlen($buf)-strlen($what)))) {
				return $buf;
			} else {
				continue;
			}
		}
		$c = $this->getc();
		if ($c == $IAC) {
			$buf .= $c;
		} else if (($c == $DO) || ($c == $DONT)) {
			$opt = $this->getc();
			// echo "we wont ".ord($opt)."\n";
/*
			if (!$this->h1sent){
				$this->tracemsg(EXECUTOR, "send header1");
				fwrite($this->sock, $this->header1);
				$this->h1sent = true;
			}else if (!$this->h2sent){
				$this->tracemsg(EXECUTOR, "send header2");
				fwrite($this->sock, $this->header2);
				$this->h2sent = true;
			}
*/
			$this->tracemsg(EXECUTOR, sprintf("telnet opt: WE WONT %d.", $opt));
			fwrite($this->sock,$IAC.$WONT.$opt);
		} elseif (($c == $WILL) || ($c == $WONT)) {
			$opt = $this->getc();
			// echo "we dont ".ord($opt)."\n";
			$this->tracemsg(EXECUTOR, sprintf("telnet opt: WE DONT %d.", $opt));
			fwrite($this->sock,$IAC.$DONT.$opt);
		} else {
			//echo "where are we? c=".ord($c)."\n";
		}
	}
}

function __construct ($config){
	$c = $this->defcfg;
	$config = array_merge($c, $config);
	$this->config = $config;
//	print_r($this->conn1);
//	print_r($this->conn2);
// TELNET commands. From tools.ietf.org/html/rfc854  and also (IANA website)/assignments/telnet-options 
	$this->header1 = chr(0xFF).chr(0xFB).chr(0x1F).  // 0xFF 0xFB 0x1F - WILL command - NEGOTIATE-WINDOW-SIZE 
		chr(0xFF).chr(0xFB).chr(0x20).    // 0xFF 0xFB 0x20 - WILL command - TERMINAL-SPEED 
		chr(0xFF).chr(0xFB).chr(0x18).    // 0xFF 0xFB 0x18 - WILL command - TERMINAL-TYPE 
		chr(0xFF).chr(0xFB).chr(0x27).    // 0xFF 0xFB 0x27 - WILL command - NEW-ENVIRON 
		chr(0xFF).chr(0xFD).chr(0x01).    // 0xFF 0xFD 0x01 - DO command - ECHO 
		chr(0xFF).chr(0xFB).chr(0x03).    // 0xFF 0xFB 0x03 - WILL command - SUPPRESS-GO-AHEAD 
		chr(0xFF).chr(0xFD).chr(0x03).    // 0xFF 0xFD 0x03 - DO command - SUPPRESS-GO-AHEAD 
		chr(0xFF).chr(0xFC).chr(0x23).    // 0xFF 0xFC 0x23 - WON'T command - X-DISPLAY-LOCATION 
		chr(0xFF).chr(0xFC).chr(0x24).    // 0xFF 0xFC 0x24 - WON'T command - ENVIRONMENT 
		chr(0xFF).chr(0xFA).              // 0xFF 0xFA ... - SB command 
		chr(0x1F).chr(0x00).chr(0x50).chr(0x00).chr(0x18).      // NEGOTIATE-WINDOW-SIZE <Width1>=0 <Width0>=80 <Height1>=0 <Height0>=24 
		chr(0xFF).chr(0xF0).              // 0xFF 0xF0 - SE command 
/*
		chr(0xFF).chr(0xFA).              // 0xFF 0xFA ... - SB command 
		chr(0x20).chr(0x00).chr(0x33).chr(0x38).chr(0x34).chr(0x30).chr(0x30).chr(0x2C).chr(0x33).chr(0x38).chr(0x34).chr(0x30).chr(0x30).    // TERMINAL-SPEED - 38400,38400 
		chr(0xFF).chr(0xF0).              // 0xFF 0xF0 - SE command 
*/
		chr(0xFF).chr(0xFA).              // 0xFF 0xFA ... - SB command 
		chr(0x27).chr(0x00).    	  // NEW-ENVIRON <IS> <empty> 
		chr(0xFF).chr(0xF0).              // 0xFF 0xF0 - SE command 
		chr(0xFF).chr(0xFA).              // 0xFF 0xFA ... - SB command 
		chr(0x18).chr(0x00).chr(0x76).chr(0x74).chr(0x31).chr(0x30).chr(0x30).    // TERMINAL-TYPE: <IS> vt100 
		chr(0xFF).chr(0xF0);              // 0xFF 0xF0 - SE command 
	$this->header2 = chr(0xFF).chr(0xFC).chr(0x01).  // 0xFF 0xFC 0x01 - WON'T command - ECHO 
		chr(0xFF).chr(0xFC).chr(0x22).    // 0xFF 0xFC 0x22 - WON'T command - LINEMODE 
		chr(0xFF).chr(0xFE).chr(0x05).    // 0xFF 0xFE 0x05 - DON'T command - STATUS 
		chr(0xFF).chr(0xFC).chr(0x21);    // 0xFF 0xFC 0x21 - WON'T command - TOGGLE-FLOW-CONTROL  
	parent::__construct($config[debugon], $config[debugsetting]);
}

function login($username=null, $password=null, $mode=null){
	if ($this->logged) return true;
	$this->trace_in('TASKLOG,EXECUTOR', __FUNCTION__, $this->config, $username, $password, $mode);
	if ($username) $this->config[username] = $username;
	if ($password) $this->config[password] = $password;
	$r = $this->telnet($this->config[ip], 23);
	if (!$r) throw new Exception("telnet: connection fail.");
	$tmout = false;

/*
	$this->tracemsg(EXECUTOR, "send header1");
	$this->write($this->header1);
	$p = $this->read_till("#$@#@", $tmout);
	$this->trace_in(EXECUTOR, __FUNCTION__.".header1", $p);

	$this->tracemsg(EXECUTOR, "send header2");
	$this->write($this->header2);
	$p = $this->read_till("#$@#@", $tmout);
	$this->trace_in(EXECUTOR, __FUNCTION__.".header2", $p);
*/

	$p = $this->read_till("login: ", $tmout);
	if ($tmout) throw new Exception("telnet get login prompt timeout, wait 'login: ', but got($p)");
	$this->trace_in(EXECUTOR, __FUNCTION__.".prompt1", $p);
	$this->write($this->config[username]."\r\n");
	$p  = $this->read_till("Password: ", $tmout);
	if ($tmout) throw new Exception("telnet get password prompt timeout, wait 'Password: ', but got($p)");
	$this->trace_in(EXECUTOR, __FUNCTION__.".prompt2", $p);
	$this->write($this->config[password]."\r\n");
	$p  = $this->read_till("# ", $tmout);
	if ($tmout) throw new Exception("telnet get shell prompt timeout, wait '# ', but got($p)");
	$this->trace_in(EXECUTOR, __FUNCTION__.".prompt3", $p);
	if ($this->config[usebash]){
		$this->write("bash\r\n");
		$p  = $this->read_till("# ", $tmout);
		$this->trace_in(EXECUTOR, __FUNCTION__.".bashprompt", $p);
		if ($tmout) throw new Exception("telnet get bash prompt timeout, wait '# ', but got($p)");
	}
	if ($mode) $this->config[mode] = $mode;
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

function _exec_telnet($taskname, $cmd, &$out, &$ret, &$err){
	$mytail = "; echo \"x---got-cmd-return-as----(\$?)\""."\r\n";
	$my = $cmd.$mytail;
	$this->write($my);
	$tmout = false;
	$maxtmout = 60;//todo, read from host info?
	$outlns = $this->read_till("# ", $tmout, $maxtmout);
	if (!$outlns) throw new Exception("exec $taskname/telnet network error.");
	if ($tmout) throw new Exception("exec $taskname/telnet time out($maxtmout), got($outlns)");
	$outlns = str_replace("\r", '', $outlns);
	if (preg_match("/x---got-cmd-return-as----\((\d+)\)/", $outlns, $match)){
		$ret = $match[1][0];
		$outlns = preg_replace("/x---got-cmd-return-as----\($ret\)/", "", $outlns);
	}else $ret = -2;
	$out = explode("\n", $outlns);
	//remove echo chars, in case server default 'echo off'
	$out[0] = preg_replace("/^(> )*/", "", $out[0]); 
	$outn = explode("\n", $outlns);
	$out = array();
	$found = false;
	foreach($outn as $ln){
		$this->tracemsg(EXECUTOR, "check line ##$ln@@");
		if (preg_match("/x---got-cmd-return-as----\([^\)]*\)/", $ln)){
			$found = true;
			continue;
		}
		if (!$found) continue;
		$out[] = $ln;
	}
	if (!$found) $out = $outn;
	array_pop($out);	//last cmd prompt
	return $ret;
}

function exec_shell_mode($taskname, $cmd, &$out, &$ret, &$err){
	if (!$this->login()){
		throw new Exception("$taskname exec fail: telnet-login fail.");
	}
	return $this->_exec_telnet($taskname, $cmd, $out, $ret, $err);
}


function exec_session_mode($taskname, $cmd, &$out, &$ret, &$err=null){
	if (!$this->login()){
		throw new Exception("$taskname exec fail: telnet-login fail.");
	}
	$ret = $this->_exec_telnet($taskname, $cmd, $out, $ret, $err);
	return $ret;
}

function copy_to_remote($local, $remote, $create_mode=0644){
	throw new Exception("copy to remote/telnet not supported.");
}

function copy_from_remote($remote, $local){
	throw new Exception("copy from remote/telnet not supported.");
}
function shutdown(){
	$this->close();
}
//end of EXEC_ssh class 
}
?>
