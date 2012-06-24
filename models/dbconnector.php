<?php
class DBConnector{
var $dbus;
var $proxy;
var $if;
var $obj;
var $path;
var $failmsg;

var $msgsignal = null;
var $unread = array(); //unreadmsgs;
function __construct($type, $modname, $jid){
	$ifname = "mod.$modname.j$jid";
	$this->if = $ifname;
	$this->obj = $ifname;
	$this->path = "/".str_replace('.', '/', $ifname);
	if ($type == 'CLIENT'){
		$this->dbus = new Dbus( Dbus::BUS_SESSION );
		$this->proxy = $this->dbus->createProxy( 
			$this->if,
			$this->path,
			$this->if
		);
		return;
	}else{
		$this->dbus = new Dbus( Dbus::BUS_SESSION, true );
		$this->dbus->requestName($this->if);
		$this->msgsignal = new DbusSignal(
			$this->dbus,
			$this->path,
			$this->if,	
			'msg'
		);
		$this->dbus->registerObject($this->obj, $this->if, 'DBConnector');
	}
}

/*
//registered interface
static function _getMsgCount(){
	return count($this->unread);
}

static function _readMsg($index, $remove=true){
	$msg = $this->unread($index);
	if ($remove){
		unset($this->unread[$index]);
		//todo: store into history, and flush to trace file?
	}
	return $msg;
}
*/

//server functions
function sendMsg($data){
//	$this->unread[] = serialize($data);
	//$this->msgsignal->send(count($this->unreal.length)-1); //just send cound
//	$this->msgsignal->send(); //just send cound
	$data[timestamp] = microtime(true);
	$this->msgsignal->send(serialize($data));
	if(getenv("MODTEST")) print_r($data);
}
 
/*
//client functions
function getMsgCount(){
	$r = $this->proxy->_getMsgcount();
	return $r;
}

function readMsg($index, $remove=true){
	$r = $this->proxy->_readMsg($index, $remove);
	return $r;
}
*/

function watch($signals, $maxtimeout=0, $if=null){
	$signals = explode(",", $signals);
	$if = $if?$if:$this->if;
	try{
		$this->dbus->addWatch($if);
		$timeout = 0;
		while (true) {
			$s = $this->dbus->waitLoop(1000);
			if (!$s){
				$timeout += 5;
				if ($maxtimeout && $timeout >$maxtimeout){
					throw new Exception("timeout($timeout).");
				}
				continue;
			}
			foreach($signals as $signal){
				if ($s->matches($if, $signal)) {
					$o = $s->getData();
					if ($o){
						$s = unserialize($o);
						if ($s === false) throw new Exception("unformatted data.");
						if ($s[timestamp]) $s[ipctime] = microtime(true)-$s[timestamp];
						return $s;
					}
					return $o;
				}
			}
		}
	}catch(Exception $e){
		$this->failmsg = $e->getMessage();
	}
	return false;
}
//end of class
}
?>
