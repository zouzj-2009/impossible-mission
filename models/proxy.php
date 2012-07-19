<?
include_once('../models/debugee.php');
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
var $loginbanner = "
Authorication fail, need Login\n";

function __construct($host, $port=80, $user=null, $pass=null, $ssl=false){
	$this->host = $host;
	$this->port = $port;
	$this->user = $user;
	$this->pass = $pass;
	$this->ssl = $ssl;
	$this->baseurl = ($ssl?'https://':'http://').($user?"$user:$pass@":'')."$host:$port/models/get.php?";
	$this->httprequest = new HttpRequest($this->baseurl, HttpRequest::METH_POST);
	if (getenv(PHPSESSID)){
		$this->cookies = array(PHPSESSID=>getenv(PHPSESSID));
	}
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

function showpending($mod, $action, $pending, $during, $elapsed){
	if (!$elapsed)
		$msg = "$mod.$action/$pending[title]: $pending[text]";
	else
		$msg = "$mod.$action/$pending[title]: $pending[text] (".number_format($pending[number]*100,0)."%, $during's/$elapsed's EST)";
	echo "$msg\n";
}
function request_mod($mod, $action, $params=array(), $records=array(), $post=false, $isretry=false){
	//note! post will only accept one_record! this is a limit of get.php
	$this->trace_in(DBG, __FUNCTION__, $mod, $action, $params, $records);
	if ($this->cookies) $this->httprequest->setCookies($this->cookies);
	$this->httprequest->setMethod($post?HttpRequest::METH_POST:HttpRequest::METH_GET);
	$this->httprequest->setUrl($this->baseurl."&mid=$mod&_act=$action&_debug=");
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
			$jid = $r[pending][jid];
			$params[jid] = $jid;
			while ($jid && !$r[success] && is_array($r[pending])){
				if (!$this->suspendpendingmsg){
					$this->showpending($mod, $action, $r[pending], $r[during], $r[elapsed]);
				}
				$params[_seq] = $seq++;
				$this->httprequest->setQueryData($params);
				if ($this->tracepending)
				$this->trace_in(DBG, __FUNCTION__.".pending", $mod, $action, $params, $records, $r);
				$this->httprequest->send();
				if ($this->httprequest->getResponseCode() == 200){
					$resp = $this->httprequest->getResponseBody();
					$r = json_decode($resp, true);
				}else return array(
					success=>false,
					msg=>"request fail: ". $this->httprequest->getResponseCode(),
					output=>$this->httprequest->getResponseBody(),
				);
			}
			//debugee
			return $r;
		}
		return array(
			success=>false,
			msg=>"request fail: ". $this->httprequest->getResponseCode(),
			output=>$this->httprequest->getResponseBody(),
		);
	} catch (Exception $e) {
		throw $e;
	}
}

//end of class
}
?>
