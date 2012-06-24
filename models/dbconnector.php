<?php
class DBConnector{
var $dbus;
var $proxy;
var $if;
var $obj;
var $path;
var $failmsg;
var $modname;
var $jid;

var $msgsignal = array();
var $msgpoolcount = 3;
var $msgindex = 0;
var $unread = array(); //unreadmsgs;
var $msgdone;
var $datadone;
function __construct($type, $modname, $jid){
	$this->modname = $modname;
	$this->jid = $jid;
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
		$this->msgdone = new DbusSignal(
			$this->dbus,
			$this->path,
			$this->if,	
			"done"
		);
		return;
	}else{
		$this->dbus = new Dbus( Dbus::BUS_SESSION, true );
		$this->dbus->requestName($this->if);
		
		for($i=0;$i<$this->msgpoolcount;$i++){
			$this->msgsignal[$i] = new DbusSignal(
				$this->dbus,
				$this->path,
				$this->if,	
				"msg$i"
			);
		}
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
//	echo "send msg via $this->msgindex\n";
	$this->msgsignal[$this->msgindex++]->send(serialize($data));
	$this->msgindex %= $this->msgpoolcount;
//	if(getenv("MODTEST")) print_r($data);
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

function waitDone($data, $timeount=null){
	$this->datadone = $data;
	if ($timeout === null) $timeout = 5000;
	return $this->watchx('done', $timeout, $this->if, null, $this->datadone);
}

function ackDone($data){
	$this->msgdone->send($this->modname.".".$this->jid);
}


function watchx($signals=null, $maxtimeout=0, $if=null, $tm_callbck=null, $tmdata=null){
	if (!$signals){
		$signals = 'msg0';
		for($i=1;$i<$this->msgpoolcount;$i++) $signals .= ",msg$i";
	}
	$signals = explode(",", $signals);
	$if = $if?$if:$this->if;
	try{
		$this->dbus->addWatch($if);
		$timeout = 0;
		while (true) {
			$sa = $this->dbus->waitLoopx(1000);
/*
			if (!$sa){
				$timeout += 1000;
				if ($maxtimeout && $timeout >$maxtimeout){
					throw new Exception("timeout($timeout).");
				}
				continue;
			}
*/
			$output = '';
			$out = array();
			if ($sa)
			foreach($sa as $s){
				foreach($signals as $signal){
					if ($s->matches($if, $signal)) {
						$o = $s->getData();
						if ($o){
							$r = unserialize($o);
							if ($r === false) throw new Exception("unformatted data.");
							if ($r[timestamp]) $r[ipctime] = microtime(true)-$r[timestamp];
							$r[msgid] = $signal;
							$out[] = $r;
							$output .= $r[output];
							//if (!($r[pending])) return $r;
						}
						//return $o;
					}
				}
			}
			if ($out){
				if (count($out)>1){
					print_r($out);
				}
				$out[count($out)-1][output] = $output;
				return $out[count($out)-1];
			}else{
				$timeout += 1000;
				if ($tm_callbck){
					$this->$tm_callback($tmdata);
				}
				if ($maxtimeout && $timeout >$maxtimeout){
					throw new Exception("timeout($timeout).");
				}
				continue;
			}
		}
	}catch(Exception $e){
		$this->failmsg = $e->getMessage();
	}
	return false;
}

function watch($signals=null, $maxtimeout=0, $if=null){
	if (!$signals){
		$signals = 'msg0';
		for($i=1;$i<$this->msgpoolcount;$i++) $signals .= ",msg$i";
	}
	$signals = explode(",", $signals);
	$if = $if?$if:$this->if;
	try{
		$this->dbus->addWatch($if);
		$timeout = 0;
		while (true) {
			$s = $this->dbus->waitLoop(1000);
			if (!$s){
				$timeout += 1000;
				if ($maxtimeout && $timeout >$maxtimeout){
					throw new Exception("timeout($timeout).");
				}
				continue;
			}
			foreach($signals as $signal){
				if ($s->matches($if, $signal)) {
					$o = $s->getData();
					if ($o){
						$r = unserialize($o);
						if ($r === false) throw new Exception("unformatted data.");
						if ($r[timestamp]) $r[ipctime] = microtime(true)-$r[timestamp];
						$r[msgid] = $signal;
						return $r;
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
