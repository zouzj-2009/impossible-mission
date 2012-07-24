<?php
include_once('../../models/core/mod.base.php');
class MOD_iscsiconn extends MOD_base{
static $pconfigs = array(
	'disconnect'=>array(
		cmd=>'(
	cat /proc/scsi_target/iscsi_target/0|grep "tx pid = %txpid%"
	if [ $? -eq 0 ];then
		kill -1 %txpid%
		echo "#@ERROR@INFO kick off connect %initiator% @%clientip% from target %targetid%\n"
		exit 0
	else
		echo "#@ERROR@INFO connect info out of date(%initiator% @%clientip% on target %targetid%)\n"
		exit 1
	fi
)',
		getmsg=>true,
	),
	'get'=>array(
		cmd=>'(
	cat /proc/scsi_target/iscsi_target/0
)', 
/*
VERSION = 110518a.nodbmp.queue.fio
ODYSYS iSCSI Target in Generic Mode (SCST)
Total of 1 targets
TARGETNAME_HEAD = iqn.2005-05.cn.com.odysys.iscsi.odyiscsi:
Total of 1 sessions
                                                                                            
        session for target 0(0,0,0,0) =>
        session has 1 connections
                CID 1: 192.168.101.204:2135 -> 192.168.101.101:3260, active = 1,
                        rx pid = 12788, tx pid = 12787,
                        iscsi cmds = 1,
                TSIH = 2
                queue_depth = 4
                maxsn = 274
                exp_cmd_sn = 270
                cmd_sn = 1
                recv 54(0) bytes, send 1387(1285) bytes,
                errors = 0,
                access level = rw
    session-wide parameters
                MaxConnections  1
                    TargetName  iqn.2005-05.cn.com.odysys.iscsi.odyiscsi:0
                 InitiatorName  iqn.1991-05.com.microsoft:security
                    InitialR2T  No
                 ImmediateData  Yes
                MaxBurstLength  32768
              FirstBurstLength  32768
              DefaultTime2Wait  2
            DefaultTime2Retain  20
             MaxOutstandingR2T  1
                DataPDUInOrder  Yes
           DataSequenceInOrder  Yes
            ErrorRecoveryLevel  0
                   SessionType  Normal
          TargetPortalGroupTag  1

*/
		type=>'records_span_lines',
		recordstart=>'/session for target/',
		recordid=>'/session for target ([0-9]*)\(/',
		recordend=>'/^ *$/',
		fieldstype=>'mixed',
		fieldsmode=>array(
			'connection-wide'=>array(
				gmatcher=>'/session has/',
				type=>'keyvalues_span_lines',
				matcher=>'/(CID |recv |rx pid =|iscsi cmds =|[^A-Z][^= ]* *=) *(.*)/',
				parentend=>'/session-wide/',
				trimkey=>"\t =",
				trimvalue=>"\t,",
				mergeup=>true,
			),
/*
			'send_recv'=>array(
				gmatcher=>'/^ *recv.*bytes/',
				type=>'record_in_one_line',
				filedsep=>'/ *|\(|\)/',
				fieldnames=>'_,_,_,recv,_,_,_,send',
			),
*/
			'session-wide'=>array(
				gmatcher=>'/session-wide/',
				type=>'keyvalues_span_lines',
				matcher=>'/([^ ]+|session-wide)  (.*)/',
				mergeup=>true,
			),
				
		),
		debug=>true,
		debugall=>true,
/*
            [record_id] => 0
            [CID] => 1: 192.168.101.204:2135 -> 192.168.101.101:3260, active = 1
            [rx pid] => 12788, tx pid = 12787
            [iscsi cmds] => 0
            [TSIH] => 2
            [queue_depth] => 4
            [maxsn] => 1876
            [exp_cmd_sn] => 1872
            [cmd_sn] => 1
            [errors] => 0
            [level] => rw
            [MaxConnections] => 1
            [TargetName] => iqn.2005-05.cn.com.odysys.iscsi.odyiscsi:0
            [InitiatorName] => iqn.1991-05.com.microsoft:security
            [InitialR2T] => No
            [ImmediateData] => Yes
            [MaxBurstLength] => 32768
            [FirstBurstLength] => 32768
            [DefaultTime2Wait] => 2
            [DefaultTime2Retain] => 20
            [MaxOutstandingR2T] => 1
            [DataPDUInOrder] => Yes
            [DataSequenceInOrder] => Yes
            [ErrorRecoveryLevel] => 0
            [SessionType] => Normal
            [TargetPortalGroupTag] => 1

*/
		newkeys=>array(
			'record_id'=>'targetid', 'iscsi cmds'=>'iscsicmds', 'level'=>'access', 'TargetName'=>'targetname',
			'InitiatorName'=>'initiator',		
		),
		newvalues=>array(
			'CID'=>array(
				type=>'record_in_one_line',
				fieldsep=>'/[:,=]|->/',
				fieldnames=>'_,clientip,_,targetip',
				mergeup=>true,
			),
			'rx pid'=>array(
				type=>'record_in_one_line',
				fieldsep=>'/[,=]/',
				fieldnames=>'rxpid,_,txpid',
				mergeup=>true,
			),
			'recv'=>array(//or ought to using get_xxx function?
				type=>'record_in_one_line',
				fieldsep=>'/[\(\) ]/',
				fieldnames=>'_,writespeed,_,_,_,_,readspeed',
				newvalues=>array(
					writespeed=>'filesize',
					readspeed=>'filesize',
				),
				mergeup=>true,
			),
		),
		skiprecord=>array(
		),
	),
);
var $savechangeconfig = null;
var $defaultcmds = array(read=>'get', destroy=>'disconnect', update=>'faulty', create=>'faulty');

}
?>
