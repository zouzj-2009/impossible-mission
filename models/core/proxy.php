<?
include_once('../../models/core/debugee.php');
class PROXY extends DEBUGEE{
var $host;
var $port;
var $user;
var $pass;
var $ssl;
var $httprequest;
var $baseurl;
var $cookies;
var $debug = false;
var $autologin = false;
var $suspendpendingmsg = false;
var $tracepending = false;
var $callback = false;
var $loginbanner = "
Authorication fail, need Login\n";

function __construct($host, $port=80, $user=null, $pass=null, $ssl=false, $debug=false){
	$this->host = $host;
	$this->port = $port;
	$this->user = $user;
	$this->pass = $pass;
	$this->ssl = $ssl;
	$this->baseurl = ($ssl?'https://':'http://').($user?"$user:$pass@":'')."$host:$port/models/core/get.php?";
	$this->httprequest = new HttpRequest($this->baseurl, HttpRequest::METH_POST);
	if (getenv(PHPSESSID)){
		$this->cookies = array(PHPSESSID=>getenv(PHPSESSID));
	}
	parent::__construct($debug?true:false, $debug);
}

function prompt_silent($prompt = "Enter Password: ") {
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
    file_put_contents(
      $vbscript, 'wscript.echo(InputBox("'
      . addslashes($prompt)
      . '", "", "password here"))');
    $command = "cscript //nologo " . escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  } else {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      trigger_error("Can't invoke bash");
      return;
    }
    $command = "/usr/bin/env bash -c 'read -s -p \""
      . addslashes($prompt)
      . "\" mypassword && echo \$mypassword'";
    $password = rtrim(shell_exec($command));
    echo "\n";
    return $password;
  }
}

function trylogin(){
	echo $this->loginbanner;
	$username = readline("Enter Username: ");
	$password = $this->prompt_silent("Enter Password: ");
	return $this->login($username, $password);
}

function logout(){
}
function login($username, $password){
	$r = $this->request_mod('login', 'read', array(_login=>true), array(username=>$username, password=>md5($password)), $post=true);
	if (!$r[success]) return false;
	return true;
}

function showpending($mod, $action, $r){
	if ($this->callback){
		return call_user_func($this->callback, $mod, $action, $r);
	}
	$pending = $r[pending];
	$during = $r[during];
	$elapsed = $r[elapsed];
	if ($this->__debugon && $r[output]) echo "\t".trim(str_replace("\n", "\n\t", $r[output]), "\t");
	if (!$elapsed){
		$msg = ">>> $pending[text]";
	}else{
		$msg = ">>> $pending[text] (".number_format($pending[number]*100,0)."%, $during's/$elapsed's EST)";
	}
	echo "$msg\n";
}
function request_mod($mod, $action, $params=array(), $records=array(), $post=false, $isretry=false){
	//note! post will only accept one_record! this is a limit of get.php
	$this->trace_in(DBG, __FUNCTION__, $mod, $action, $params, $records);
	if ($this->cookies) $this->httprequest->setCookies($this->cookies);
	$this->httprequest->setMethod($post?HttpRequest::METH_POST:HttpRequest::METH_GET);
	$this->httprequest->setUrl($this->baseurl."&mid=$mod&_act=$action");
	$params[_debugsetting] = $this->__debugsetting;
	$params[_debugon] = $this->__debugon;
	$this->httprequest->setQueryData($params);
	if ($post) $this->httprequest->setPostFields($records); else {
		if (!$isretry){
			$params[records] = addslashes(json_encode($records));
			$this->httprequest->setQueryData($params);
		}
	}
	try {
		$this->httprequest->send();
		if ($this->httprequest->getResponseCode() == 200) {
			$resp = $this->httprequest->getResponseBody();
			if ($this->test_debug(NETWORK)){
				fwrite(STDERR, "<<<<< response start:<<<<<\n");
				fwrite(STDERR, $resp);
				fwrite(STDERR, "\n<<<<< response end.<<<<<\n");
			}
			$o = $this->httprequest->getResponseHeader();
			$c = explode(";", $o['Set-Cookie']);
			if ($c){
				$this->cookies = array();
				foreach ($c as $k=>$v){
					if (preg_match('/^([^=]*)=(.*)/', trim($v), $m)){
						$this->cookies[$m[1]]= $m[2];
					}
				}
			}
			$r = json_decode($resp, true);
			if (!$isretry && $r[authfail] && $this->autologin){
				while(!$this->trylogin()){};
				return $this->request_mod($mod, $action, $params, $records, $post, true);
			}
			$seq = 1;
			$taskid = $r[pending][taskid];
			$params[taskid] = $taskid;
			while ($taskid && !$r[success] && is_array($r[pending])){
				if (!$this->suspendpendingmsg){
					$this->showpending($mod, $action, $r);
				}
				$params[_seq] = $seq++;
				$this->httprequest->setQueryData($params);
				if ($this->tracepending)
				$this->trace_in(DBG, __FUNCTION__.".pending", $mod, $action, $params, $records, $r);
				$this->httprequest->send();
				if ($this->httprequest->getResponseCode() == 200){
					if ($this->test_debug(NETWORK)){
						fwrite(STDERR, "<<<<< response start:<<<<<\n");
						fwrite(STDERR, $resp);
						fwrite(STDERR, "\n<<<<< response end.<<<<<\n");
					}
					$resp = $this->httprequest->getResponseBody();
					$r = json_decode($resp, true);
					if ($r === NULL) return array(
						success=>false,
						msg=>"response can't be decode.",
						output=>$resp,
					);
				}else{ 
					if ($taskid) $this->tracemsg('DBG,BGTRACE,TRACE,INFO', "$mod $action task $taskid fail.");
					return array(
						success=>false,
						msg=>"request fail: ". $this->httprequest->getResponseCode(),
						output=>$this->httprequest->getResponseBody(),
					);
				}
			}
			//debugee
			if ($taskid) $this->tracemsg('DBG,BGTRACE,TRACE,INFO', "$mod $action task $taskid done.");
			return $r;
		}
		$r = json_decode($this->httprequest->getResponseBody(), true);
		if ($r){
			$r[msg] = "response fail: ".$this->httprequest->getResponseCode()."\n".$r[msg];
			if ($r[trace]) $r[msg].="($r[trace])";
			return $r;
		}
		return array(
			success=>false,
			msg=>"response fail: ". $this->httprequest->getResponseCode(),
			output=>$this->httprequest->getResponseBody(),
		);
	} catch (HttpException $ex) {
		return array(
			success=>false,
			msg=>"proxy error: ".$ex,
		);
	} catch (Exception $e){
		return array(
			success=>false,
			msg=>"unknown error: ".$e->getMessage(),
		);
	}
}

//end of class
}
?>
