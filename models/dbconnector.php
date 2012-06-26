<?php

class DBConnector{
static $dbus;
var $proxy;
var $if;
var $obj;
var $path;
var $failmsg;
var $modname;
var $jid;

var $msgsignal = array();
var $msgpoolcount = 1;
var $msgindex = 0;
var $unread = array(); //unreadmsgs;
var $msgdone;
var $datadone;
static $lastsend;
function __construct($type, $modname, $jid){
	$this->modname = $modname;
	$this->jid = $jid;
	$ifname = "mod.$modname.j$jid";
	$this->if = $ifname;
	$this->obj = $ifname;
	$this->path = "/".str_replace('.', '/', $ifname);
	if ($type == 'CLIENT'){
		$this->dbus = new Dbus( Dbus::BUS_SYSTEM );
		$this->proxy = $this->dbus->createProxy( 
			$this->if,
			$this->path,
			$this->if
		);
	}else{
		$this->dbus = new Dbus( Dbus::BUS_SYSTEM );
//		$this->dbus->requestName($this->if);
		
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
	$this->msgdone = new DbusSignal(
		$this->dbus,
		$this->path,
		$this->if,	
		"done"
	);
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
	if (!$data) throw new Exception("NULL DATA!!!!!");
	$this->lastsend = serialize($data);
//	$this->unread[] = serialize($data);
	//$this->msgsignal->send(count($this->unreal.length)-1); //just send cound
//	$this->msgsignal->send(); //just send cound
	$data[timestamp] = microtime(true);
//	echo "send msg via $this->msgindex\n";
	$this->msgsignal[$this->msgindex++]->send($this->lastsend);
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

function resendDone($data){
	echo "resend done.\n";
	$this->lastsend = serialize($data);
	$this->msgdone->send($this->lastsend);
}
function waitDone($data, $timeount=null){
	if ($timeout === null) $timeout = 5000;
	if ($this->watch('done', $timeout, $this->if, 'resendDone', $data)){
		echo "Done acked.\n";
		exit(0);
	}else{
		echo "Done withou ack.\n";
		exit(1);
	}
}

function ackDone($data){
	$this->lastsend = serialize($this->modname.".".$this->jid);
	$this->msgdone->send($this->lastsend);
}


function watchx($signals=null, $maxtimeout=0, $if=null, $tm_callback=null, $tmdata=array()){
	if (!$signals){
		$signals = 'done,';
		for($i=0;$i<$this->msgpoolcount;$i++) $signals .= ",msg$i";
	}
echo "watch singals:$signals\n";
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
				if ($tm_callback){
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

function watch($signals=null, $maxtimeout=0, $if=null, $tm_callback=null, $tmdata=array()){
	if (!$signals){
		$signals = 'done,msg0';
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
				if ($tm_callback) $this->$tm_callback($tmdata);
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
