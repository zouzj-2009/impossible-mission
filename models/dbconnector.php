<?php

class DBConnector{
static $msgdone;
static $msgsignal;
static $dbus;
static $proxy;
static $if;
static $obj;
static $path;
static $failmsg;
static $modname;
static $jid;

function __construct($type, $modname, $jid){
	self::$modname = $modname;
	self::$jid = $jid;
	$ifname = "mod.$modname.j$jid";
	self::$if = $ifname;
	self::$obj = $ifname;
	self::$path = "/".str_replace('.', '/', $ifname);
	if ($type == 'CLIENT'){
		self::$dbus = new Dbus( Dbus::BUS_SESSION, true );
		self::$proxy = self::$dbus->createProxy( 
			self::$if,
			self::$path,
			self::$if
		);
	}else{
		self::$dbus = new Dbus( Dbus::BUS_SESSION, true );
		self::$dbus->requestName(self::$if);
		
		self::$msgsignal = new DbusSignal(
			self::$dbus,
			self::$path,
			self::$if,	
			"msg"
		);
		self::$dbus->registerObject(self::$obj, self::$if, 'DBConnector');
	}
	self::$msgdone = new DbusSignal(
		self::$dbus,
		self::$path,
		self::$if,	
		"done"
	);
}

/*
//registered interface
static function _getMsgCount(){
	return count(self::$unread);
}

static function _readMsg($index, $remove=true){
	$msg = self::$unread($index);
	if ($remove){
		unset(self::$unread[$index]);
		//todo: store into history, and flush to trace file?
	}
	return $msg;
}
*/

//server functions
static function sendMsg($data){
	if (!$data) throw new Exception("NULL DATA!!!!!");
	$data[timestamp] = microtime(true);
	self::$msgsignal->send(serialize($data));
	//self::$msgsignal->send($data);
}
 
/*
//client functions
function getMsgCount(){
	$r = self::$proxy->_getMsgcount();
	return $r;
}

function readMsg($index, $remove=true){
	$r = self::$proxy->_readMsg($index, $remove);
	return $r;
}
*/

function resendDone($data){
	self::$msgdone->send(serialize($data));
}
function waitDone($data, $timeount=null){
	if ($timeout === null) $timeout = 5000;
	if (self::watch('done', $timeout, self::$if, 'resendDone', $data)) exit(0);
}

function ackDone($data){
	self::$msgdone->send(serialize(self::$modname.".".self::$jid));
}


function watch($signal=null, $maxtimeout=0, $if=null, $tm_callback=null, $tmdata=array()){
	if (!$signal){
		$signal = 'msg';
	}
	$if = $if?$if:self::$if;
	try{
		self::$dbus->addWatch($if);
		$timeout = 0;
		while (true) {
			$s = self::$dbus->waitLoop(1000);
			if (!$s){
				$timeout += 1000;
				if ($tm_callback){	
					echo "recall $tm_callback\n";
					$this->$tm_callback($tmdata);
				}
				if ($maxtimeout && $timeout >$maxtimeout){
					throw new Exception("timeout($timeout).");
				}
				continue;
			}
			if ($s->matches($if, $signal)) {
				return(unserialize($s->getData()));
			}
		}
	}catch(Exception $e){
		self::$failmsg = $e->getMessage();
	}
	return false;
}
//end of class
}
?>
