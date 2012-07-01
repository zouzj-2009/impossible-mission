<?php
include_once('../models/mod.base.php');
/*

This is skeleton mod for only shell cmd needs, it's simplest one. 

	** Four cmd,  and a pharser for read cmd was defined, config your saving fields if need, that's all! **

Current setting after changing, will be saved in 'sysconfig' table automaticly.

Just copy and edit for your need, don't extend from this class!

*/
class MOD_skeleton_shell extends MOD_base{
/*
//these are default configurations
var $keyids = array('id'); 	//using this keys as record identify
var $defaultcmds = array( 
	read=>null,
);
var $batchsupport = array(// false|true|'one_by_one'
	update=>true, create=>'one_by_one', destroy=>true,
);

var $savechangeconfig = array(
	tablename=>'sysconfig', //table for store changing config data.
	autocreate=>true,	//auto create records for not-existed update.
);

var $saving_fields = null;
var $readbeforeupdate = true;	//weather read old data before update
var $readbeforedestroy = false;	//weather read old data before destroy
*/

//we config defaultcmds and batch model for our operation
var $defaultcmds = array(
	read=>'get_info',
	create=>'create_one',
	update=>'update_one',
	destroy=>'delete_it',
);

var $batchsupport = array(
	update=>true, create=>false, destroy=>true
);

//we define this for get_sysconfig_$things to working
var $keyids = array('record_id'); 	

//we set it, for get destroied data by condition maybe
var $readbeforedestroy = true;

//config fields recorded in sysconfig table, note! no space between fields!
//if dont' want to saving, just redefine $savechangeconfig as null
var $saving_fields = 'record_id,modname,cmd';

//now we define cmd and pharser
// all valid type and configurator 
// This define just for description, should be removed in your real mod!
var $valid_pharser = array(
	'records_span_lines_simple'=>array(
		cmd=>'shell cmd to run',
		executor=>executor_mode,	//optional, default is shell
		type=>'records_span_lines',
		fieldstype=>'simple',
		recordstart=>'/record start pattern/',
		recordend=>'/record end pattern/', //optional
		recordid=>'/record (id patterh) start/',
		ignore=>'/ignore pattern/',	//optional, ignored lines
		fieldsmode=>array(
			/* any valid_pharser config */
		),
		newkeys=>array(			//optional, after pharse, keys will be transfered.
			oldkey=>'newkey', 	//...
		),
		newvalues=>array(		//optional, after pharse, recalc the result by new pharser
			'key'=>array( /* any valid pharser config */), // ...
			mergeup=>true,		//optional, default is false, if true, merge result to parent, otherwise, value indexed by key
		),
		debug=>false,			//true fo debug this pharser, sub modes sucha as fieldsmode need set debug flag indivisually.
		idindexed=>false,		//true|false, if true, use record_id as return array index.
	),
	'records_span_lines_mixed'=>array(
		cmd=>'shell cmd to run',
		executor=>executor_mode,	//optional, default is shell.
		type=>'records_span_lines',
		fieldstype=>'mixed',
		recordstart=>'/record start pattern/',
		recordend=>'/record end pattern/', //optional
		recordid=>'/record (id patterh) start/',
		ignore=>'/ignore pattern/',	//optional, ignored lines
		fieldsmode=>array(
			groupid=>array(
				gmatcher=>'/pattern for this group begin/',
				gmergeup=>false,//true for merge this group result as parent's elements, indexed by fields indexes,
						//as default, this fields will be a array of parent, indexed as 'groupid'
				/* any valid_pharser config */
			),
			//... more groups ...
		),
		newkeys=>array(			//optional, after pharse, keys will be transfered.
			oldkey=>'newkey', 	//...
		),
		newvalues=>array(		//optional, after pharse, recalc the result by new pharser
			'key'=>array( /* any valid pharser config */), // ...
			mergeup=>true,		//optional, default is false, if true, merge result to parent, otherwise, value indexed by key
		),
		debug=>false,			//true fo debug this pharser, sub modes sucha as fieldsmode need set debug flag indivisually.
		idindexed=>false,		//true|false, if true, use record_id as return array index.
	),
	'one_record_span_lines'=>array(
		/* config same as records_span_lines, but only first record will be returned, see above. */
	),
	'keyvalues_span_lines'=>array(
		cmd=>'shell cmd to run',
		executor=>executor_mode,	//optional, default is shell.
		type=>'records_span_lines',
		ignore=>'/ignore pattern/',	//optional, ignored lines
		matcher=>'/(key pattern)split pattern(value pattern)/',
						//optional, default is /([^=]*) *= *([^ ].*)/
		newkeys=>array(			//optional, after pharse, keys will be transfered.
			oldkey=>'newkey', 	//...
		),
		newvalues=>array(		//optional, after pharse, recalc the result by new pharser
			'key'=>array( /* any valid pharser config */), // ...
			mergeup=>true,		//optional, default is false, if true, merge result to parent, otherwise, value indexed by key
		),
		debug=>false,			//true fo debug this pharser, sub modes sucha as fieldsmode need set debug flag indivisually.
	),
	'keyvalues_in_one_line'=>array(
		/* same as keyvalues_span_lines, see above, but only one line was pharsed, new_values config use it often */
	),
	'record_in_one_line'=>array(
		cmd=>'shell cmd to run',
		executor=>executor_mode,	//optional, default is shell.
		type=>'records_span_lines',
		fieldsep=>'/field split pattern/', 
		fieldnames=>'name1,name2,...',	//name for splitted fields, one by one, some reserved name:
						//	_value_:	use field value as field name,
						//	_key_:		use field value as further _auto_ field's name,
						//	_auto_:		use previous _key_ field's value as this field name,
						//	_ignore_:	ignore this field in result array, null or '_' have same effect.
						//	_xxx_ptype_yyy_:name as xxx, and parse as type yyy again, 
						//		and this type must be configurated in [_xxx_ptype_yyy_]=>valid pharser config
						//		same result as newvalues with mergup
		ignore=>'/ignore pattern/',	//optional, ignored lines
		newkeys=>array(			//optional, after pharse, keys will be transfered.
			oldkey=>'newkey', 	//...
		),
		newvalues=>array(		//optional, after pharse, recalc the result by new pharser
			'key'=>array( /* any valid pharser config */), // ...
			mergeup=>true,		//optional, default is false, if true, merge result to parent, otherwise, value indexed by key
		),
		debug=>false,			//true fo debug this pharser, sub modes sucha as fieldsmode need set debug flag indivisually.
	),
	'just_output'=>array(
		cmd=>'shell cmd to run',
		executor=>executor_mode,	//optional, default is shell.
		type=>'records_span_lines',
		name=>'name_of_return_key',
		endout=>'/pattern for end output, this line is included/',
		endoutx=>'/pattern for end output, this line is excluded/',
		strip=>'/pattern was stripped/',
		ignore=>'/ignore pattern/',	//optional, ignored lines
		debug=>false,			//true fo debug this pharser, sub modes sucha as fieldsmode need set debug flag indivisually.
	),
);


//my command define
static $pconfigs = array(
	//default cmd for reading info, return array of record: [ record ].
	'get_info'=>array(
//command output start with #@LOG@LEVEL will be logged at log level LEVEL!
//multiline log supported, null line(just \n) means log end.
		cmd=>'(
	echo "#@LOG@TRACE skeleton read running ...\n"
	echo "recordid=abc"
	echo "modname=skeleon"
	echo "cmd=test"
	echo "runtime=0.02"
	echo 
	echo "recordid=def"
	echo "modname=another one"
	echo "cmd=asdfasdf asdfasd asdfasdf"
	echo "runtime=0.05"
)',
		//output type!
		type=>'records_span_lines',
		//pharser configuration
		recordstart=>'/^recordid=/',
		recordid=>'/^recordid=(.*)/',
		fieldstype=>'simple',
		fieldsmode=>array(
			type=>'keyvalues_span_lines',
			matcher=>'/([^=]*)=(.*)/',
		),
		debug=>false,	//true for pconfig debug purpose
	),	
		
	//default cmd for create record, run one by one, after ending, get created record in an array [ created ].
	'create_one' =>array(
		//if in-batchmode, last created record was supplied by last_$fieldname
		cmd=>'(
	echo "#@LOG@INFO create new one for %modname%(last created:%last_modname%) ...\n"
	echo "do something ..."
	echo "recordid=new"
	echo "modname=%modname%"
	echo "cmd=aaa"
	echo runtime=0.07
)',
		//use get_info's pharser config. create then read created.
		refcmd=>'get_info',
	),

	//default cmd for update record, in default, old record will be read out and transfer in for reference.
	//$this->readold is array of key fields for identify the records, set it to null will cause old data not be read before update.
	'update_one'=>array(
		//old record was supplied by old_$fieldname
		cmd=>'(
	echo "#@LOG@INFO update %record_id% modname from %old_modname% to %modname%\n"
	echo "do update...."
)',
	),

	//default cmd for destroy record, in default, old record will be read out and transfer in for reference.
	//$this->readold is array of key fields for identify the records, set it to null will cause old data not be read before update.
	'destroy_it'=>array(
		cmd=>'(
	echo "#@LOG@INFO destroy %record_id%\n"
	echo "do destroy...."
)',
	),
);



//end of class
}
?>
