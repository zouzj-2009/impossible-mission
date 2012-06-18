<?php
/* base service class */
#set_time_limit(60);
date_default_timezone_set ('Asia/Shanghai');
if ($_GET['sid']) session_id($_GET['sid']);
//session started now
$_GET['vid'] = $_GET['mid'];
if ($_GET['cid']) 
	$request_ctrl = $_GET['cid'];
else 
	$request_ctrl = $_GET['vid'];
if (isset($_SESSION['lang'])) $lang = $_SESSION['lang'];
if ($_GET['did']) 
	$viewid = $_GET['did'];
else 
	$viewid = $_GET['vid'];
include_once('../lang/lang.php');

//todo, store in session!
if (!$request_ctrl || !file_exists("../controller/ctrl.$request_ctrl.php")){
	$request_ctrl = 'base';
}
include_once ("../controller/ctrl.$request_ctrl.php");
@ob_start();
ob_implicit_flush(0);
session_start();
//todo: error handle?
$ctrl = call_user_func("CTRL_$request_ctrl::__createclass", $viewid);
$action = $_REQUEST['_act'];
$records = $_REQUEST['records'];
$params = $action == 'read'?$_REQUEST:array();
unset($params['records']);
$exparams = $action == 'read'?array():$_REQUEST;
unset($exparams['records']);
$cpending = false;
if ($_REQUEST['pending']){
	$cpending = json_decode(stripslashes($_REQUEST['pending']), true);
}
$args = array(
	arg=>$params,
	extra=>$exparams,
	pending=>$cpending
);

print_r($_REQUEST['pending']);
print_r($args);
switch ($_REQUEST['_act']){
case 'read':
	$result=$ctrl->read($args, $records);
	break;
default:
	$result=$ctrl->$_REQUEST['_act']($args, $records);
	break;
}
$output = ob_get_clean();
if ($result['result']){
	if ($result['result']['opresult']){
		$ret = array(
			'success'=>true,
			'count'=>$result['result']['rownum'],
			'cols'=>$result['result']['cols'],
			'data'=>$result['result']['rows'],
			'msg'=>$result['result']['msg']['error']//?
		);
	}else{
		$ret = array(
			'success'=>false,
			'msg'=>$result['result']['msg']['error']
		);
	}
}else{//pending?
	$pending = false;
	if ($result['pending']){
		$pending = $result['pending'];
		$pending['title'] = $pending['jobtitle']?$pending['jobtitle']:$action.' '.$viewid;
		$pending['msg'] = $pending['jobname'];
		$pending['number'] = $pending['cpercent']/100.0;
		$pending['text'] = $pending['title'].' '.$pending['detail'];
	}
	if ($pending){
		$ret = array(
			'success'=>false,
			'pending'=>$pending,
			'msg'=>$result['result']['msg']['error']
		);
	}else{
		$ret = array(
			'success'=>false,
			'msg'=>'no result'
		);
	}
}
$ret['output'] = $output;
$ret['raw'] = $result;

//start output
$callback = $_REQUEST['callback'];
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($ret) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($ret);
}
?>

