<?php
class PHARSER{

static $default_error_pharser = array(
	type=>'records_span_lines',
	recordstart=>'/^#@ERROR@[A-Z0-9]+ /',
	recordid=>'/^#@(ERROR@[A-Z0-9]+) /',
	recordend=>'/^ *$|^#ERROREND/',
	fieldstype=>'simple',
	fieldsmode=>array(
		type=>'just_output',
		name=>'msg',
		strip=>'/ @@@@@@@@ /',
		//endoutx=>'/^#### error msg end ####/',
	),
);

static $default_log_pharser = array(
	type=>'records_span_lines',
	recordstart=>'/^#@LOG@[A-Z0-9]+ /',
	recordid=>'/^#@(LOG@[A-Z0-9]+) /',
	recordend=>'/^ *$|^#@LOGEND/',
	fieldstype=>'simple',
	fieldsmode=>array(
		type=>'just_output',
		name=>'log',
		strip=>'/ @@@@@@@@ /',
	),
	//islogpconfig=>true,
);

function debug($tag, $info){
	echo "$tag: ";
	if (is_array($info)) print_r($info);
	else echo "$info\n";
}

function pharse_type(&$in, $pconfig){
	$type = $pconfig[type];
	if (!method_exists('PHARSER', "p_$type")){
		PHARSER::debug("PHARSER::debug", "wrong pharse type $type!");
		throw new Exception(__FUNCTION__." type $type not supported!");
	}
	if ($pconfig[debug]) PHARSER::debug("pharsing type", $type);
	return call_user_func("PHARSER::p_$type", &$in, $pconfig);
}

function pharse_cmd($name, $pconfig, $args, &$cmdresult, &$caller, &$log=null, $executor=null){
	$cmd = $pconfig[cmd];
	if ($pconfig[commargs]) $args = array_merge($pconfig[commonargs], $args);
	if (is_array($args)){
		foreach($args as $k=>$v){ $cmd = str_replace("%$k%", $v, $cmd); }
		//seach unsupplied keys
		$cmd = preg_replace("/%[^ ]+%/", '', $cmd);
	}
	//todo: using executer to do $pcmd[cmd] with $args
	if ($executor){
		$executor->exec($cmd, $out, $retvar);
	}else{
		exec($cmd, $out, $retvar);
	}

	echo "$name: $cmd return $retvar\n";
	$cmdresult = $retvar;
	if ($raw!==null) $raw = $out;
	if (!$pconfig['errpharse'] &&!$pconfig['skiperror']){
		//default error pharse
		$pconfig['errpharser'] = PHARSER::$default_error_pharser;
	}
	if (!$pconfig['logpharser'] && !$pconfig['skiplog']){
		$pconfig['logpharser'] = PHARSER::$default_log_pharser;
	}
	if ($pconfig[debug]) PHARSER::debug("PHARSER::debug", '------------- config ------------------');
	if ($pconfig[debug]) PHARSER::debug("$name.pconfig", $pconfig);
	if ($pconfig['logpharser']){
		if ($log!==null){
			$o = $out;
			if ($pconfig[debug]) PHARSER::debug("PHARSER::debug", '------------- cmd logx -----------------');
			$log = PHARSER::pharse_type($o, $pconfig['logpharser']);
			if ($pconfig[debug]) PHARSER::debug("PHARSER::logx", $log);
		}
	}
//	non 0 is error
	if ($pconfig[debug]) PHARSER::debug("PHARSER::debug", '------------- cmd output---------------');
	if ($pconfig[debug]) PHARSER::debug("$name.output", $out);
	if ($retvar && $pconfig['errpharser']){
		if ($pconfig[debug]) PHARSER::debug("PHARSER::debug", '------------- cmd fail -----------------');
		$r = PHARSER::pharse_type($out, $pconfig['errpharser']);
		$r = array_shift($r);
		if ($pconfig[debug]) PHARSER::debug("PHARSER::failmsg", $r);
		return $r;
	}
	if (!$pconfig[type]){
	if ($pconfig[debug]) PHARSER::debug("PHARSER::debug", '------------- no type configed, direct out -----');
		return $out;	//directly out;
	}
	if ($pconfig[debug]) PHARSER::debug("PHARSER::debug", '------------- pharser trace -----------');
	return PHARSER::pharse_type($out, $pconfig);
}

function check_skip_record($array, $skipconfig, $debug=false){
//check for record's key through skipconfig, if match ,{} return.
	foreach($array as $k=>$v){
		if (array_key_exists($k, $skipconfig)){
			$skip = false;
			foreach($skipconfig[$k] as $skipv){
				if ($skipv == $v){
					if ($debug) PHARSER::debug(__FUNCTION__." skip record[".implode(",", $skipconfig)."]", $array);
					return false;
				}
			}
			if ($skip) continue;
		}
		$r[$k] = $v;
	}
	return true;
}

function format_keys_and_values($array, $keys, $values=null){
//change $array's key to new names if specified in $keys
//change $array's value to new by pconfig specified in $values.
//common pconfig key:
//newkeys:	array of (<old key>=><new key>), format keys.
//newvalues:	array of (<old key|new key>=>array(pconfig)), to pharse the value again.
//mergeup:	merge new values array to parent after get newvalues.
	$r = array();
	if (!$keys) $keys = array();	//value only
	foreach($array as $k=>$v){
		$newkey = $keys[$k]?$keys[$k]:$k;
		$r[$newkey] = $v;
		if (!$values){
			continue;
		}//get new values;
		$pconfig = $values[$k]?$values[$k]:$values[$newkey];
		if (!$pconfig) continue;
		if (!is_array($pconfig)){
			$newvalue = $v;
			switch($pconfig){
			case 'boolean':
				$newvalue = (!$v||$v=='false'||$v=='0'||$v=='no'||$v=='NO'||$v=='FALSE'||$v=='null'||$v=='NULL')?false:true;
				break;
			case 'password':
				$newvalue = '********';
				break;
			}
			$r[$newkey] = $newvalue;
			continue;
		}
		$lines = array($v);
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." format value[$newkey=$lines], using pconfig", $pconfig);
		$newvalue = PHARSER::pharse_type($lines, $pconfig);
		$r[$newkey]  = $newvalue;
		//can overwrite the $newkey by $newvalues
		if ($pconfig[mergeup]){
			$r[$newkey] = $v;
			$r = array_merge($r, $newvalue);
		}
	}
	return $r;
}

function get_field_names($row, $pconfig){
//valid config key:
//some special name:	_value_:	use field value as field name,
//			_ignore_:	ignore this field in result array, null or '_' have same effect.
//			_xxx_ptype_yyy_:name as xxx, and parse as type yyy again, and this type must be configurated in pconfig	
	$fieldnames = $pconfig[fieldnames];
	$r = array();
	$lastkey = '';
	foreach($fieldnames as $i=>$name){
		$v = $row[$i];
		if ($name == '' || $name == '_' || $name == '_ignore_') continue;
		if ($name[0] != '_'){//usual name
			$r[$name] = $v;
			continue;
		}
		if ($name == '_value_'){
			$r[$v] = $v;
			continue;
		}
		if ($name == '_key_'){//means key of next value!
			$lastkey =$v;
			continue;
		}
		if ($name == '_auto_'){
			if ($lastkey) $r[$lastkey] = $v;
			//this field maybe skipped
			continue;
		}
		if (preg_match('/^_(.*)_ptype_(.*)_$/', $name, $m)){
			$fname = $m[0][0];
			$ftype = $m[0][1];
			$pconfig[$name][type] = $ftype;
			$r[$fname] = PHARSER::pharse_type($v, $pconfig[$name]);
			continue;
		}
		//not internal field name
		$r[$name] = $v;
	}
	return $r;
}

function p_just_output(&$in, $pconfig){
//valid config key:
//name: 	return keyname of output,
//ignore: 	regexp, skiped lines
//endout:	end of the msg, this line included
//endoutx:	endo of the msg, this line not included
//strip:	stips this pattern
	$o = '';
	while ($in){
		$line = array_shift($in);
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." check line", $line);
		if ($pconfig[endoutx] && preg_match($pconfig[endoutx], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [endoutx]", $pconfig[endoutx]);
			break;
		}
		if ($pconfig[endout] && preg_match($pconfig[endout], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [endout]", $pconfig[endout]);
			$o .= $line."\n";
			break;
		}
		if (($pconfig[parentstart] && preg_match($pconfig[parentstart], $line))
			||($pconfig[parentend] && preg_match($pconfig[parentend], $line))){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [parentstart|parentend]", $pconfig[parentstart]."|".$pconfig[parentend]);
			array_unshift($in, $line);
			break;
		}
		if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [ignore]", $pconfig[ignore]);
			continue;
		}
		$o .= $line."\n";
	}
	if ($pconfig[strip]) $o = preg_replace($pconfig[strip], '', $o);
	if (!$pconfig[name]) return $o;
	return array($pconfig[name]=>$o);
}

function p_record_in_one_line(&$in, $pconfig){
//valid config key:
//ignore: 	regexp, skiped lines
//fieldsep:	regexp, for fields seprate in line
//fieldnames:	string, 'name1,name2,name3...', define value name, see@get_field_names;
	$line = false;
	if (is_array($in)){
		while($in){
			$line = array_shift($in);
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." check line", $line);
 
			if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)){
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [ignore]", $pconfig[ignore]);
				continue;
			}
			break;
		}
		if (!$line) return array();
	}else $line = $in;
	$fieldsep = $pconfig[fieldsep];
	$fieldnames = $pconfig[fieldnames] = is_array($pconfig[fieldnames])?$pconfig[fieldnames]:explode(",", $pconfig[fieldnames]);
	if (!$fieldsep){
		throw new Exception(__FUNCTION__.' missing [fieldsep] config.');
	}
	$r = preg_split($fieldsep, $line);
	if (!$fieldnames) return $r;
	$r = PHARSER::get_field_names($r, $pconfig);
	if ($pconfig[newkeys]||$pconfig[newvalues]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues]);
	return $r;
}

function p_one_record_per_line(&$in, $pconfig){
//valid config key:
//ignore:	regexp, lines to be skiped.
//fieldsep:	regexp, for fields seprate in line
//fieldnames:	string, 'name1,name2,name3...', define value name, see@get_field_names;
	$r = array();
	$ignore = $pconfig[ignore];
	$fieldsep = $pconfig[fieldsep];
	$pconfig[fieldnames] = is_array($pconfig[fieldnames])?$pconfig[fieldnames]:explode(",", $pconfig[fieldnames]);
	if (!$fieldsep){
		throw new Exception(__FUNCTION__.' missing [fieldsep] config.');
		return $r;
	}
	while($in){
		$line = array_shift($in);
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." check line", $line);
		if (($pconfig[parentstart] && preg_match($pconfig[parentstart], $line))
			||($pconfig[parentend] && preg_match($pconfig[parentend], $line))){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [parentstart|parentend]", $pconfig[parentstart]."|".$pconfig[parentend]);
			array_unshift($in, $line);
			break;
		}
		if ($ignore && preg_match($ignore, $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [ignore]", $pconfig[ignore]);
			continue;
		}
		$r[] = PHARSER::p_record_in_one_line($line, $pconfig);
	}
	return $r;
}

function p_keyvalues_in_one_line(&$in, $pconfig){
//valid config key:
//matcher:	regexp, (key).*(value)
	$line = false;
	if (is_array($in)){
		while($in){
			$line = array_shift($in);
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." check line", $line);
			if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)){
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [ignore]", $pconfig[ignore]);
				continue;
			}
			break;
		}
		if (!$line) return array();
	}else $line = $in;
	if (!$pconfig[matcher]) $pconfig[matcher] = '/([^=]*) *= *([^ ]*)/';
	$r = array();
	if (preg_match_all($pconfig[matcher], $line, $m)){
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [matcher=".$pconfig[matcher]."]", $m);
		foreach($m[1] as $i=>$key){
			$r[trim($key, " :")] = trim($m[2][$i]);
		}	
	}
	if ($pconfig[newkeys]||$pconfig[newvalues]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues]);
	if ($pconfig[arrayret]) return array($r);
	return $r;
}

function p_keyvalues_span_lines(&$in, $pconfig){
//valid config key:
//matcher:	regexp, (key).*(value)
	$r = array();
	$ignore = $pconfig[ignore];
	$arrayret = $pconfig[arrayret];
	$pconfig[arrayret] = false;
	while($in){
		$line = array_shift($in);
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." check line", $line);
		if (($pconfig[parentstart] && preg_match($pconfig[parentstart], $line))
			||($pconfig[parentend] && preg_match($pconfig[parentend], $line))){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [parentstart|parentend]", $pconfig[parentstart]."|".$pconfig[parentend]);
			array_unshift($in, $line);
			break;
		}
		if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [ignore]", $pconfig[ignore]);
			continue;
		}
		$kv = PHARSER::p_keyvalues_in_one_line($line, $pconfig);
		$r = array_merge($r, $kv);
	}
	if ($arrayret) return array($r);
	return $r;
}

function p_records_span_lines(&$in, $pconfig){
//valid config key:
//ignore:	regexp, matched line will not be pharsed.
//recordignore: regexp, matched line will be ignored only when record alread start.
//recordstart:	regexp, flag of record data start
//recordend:	regexp, optional, for record end!
//recordid:	regexp, get record id from mathed lines.
//idindexed:	true|false, if true, use recordid as return array index.
//fieldstype:	[simple|mixed], all fields has same model or mixed models. 
//fieldsmode:	pconfig array, for mixed model, is a multi-pconfig, indexed by fieldgroup name, each pconfig must has these key:
//	gmatcher:	regexp: lines match this group.
//	gmergeup:	[true|false]: merge value to fater, other than store in a group array.
//
	$r = array();
	if (!$pconfig[recordstart]){
		throw new Exception(__FUNCTION__.' missing [recordstart] config');
	}
	$started = false;
	$cur_record = false;
	$cur_id	= false;
	while($in){
		$line = array_shift($in);
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." check line", $line);
		if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [ignore]", $pconfig[ignore]);
			continue;
		}
		if (($pconfig[parentstart] && preg_match($pconfig[parentstart], $line))
			||($pconfig[parentend] && preg_match($pconfig[parentend], $line))){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [parentstart|parentend]", $pconfig[parentstart]."|".$pconfig[parentend]);
			array_unshift($in, $line);
			break;
		}
		$changerecord = false;
		if ($pconfig[recordend] && preg_match($pconfig[recordend], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [recordend]", $pconfig[recordend]);
			$started = false;	//ended, but this line maybe also need pharse
			$changerecord = true;
		}
		if (preg_match($pconfig[recordstart], $line, $m)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [recordstart]", $pconfig[recordstart]);
			array_unshift($in, preg_replace($pconfig[recordstart], ' @@@@@@@@ ', $line));
			$started = true;
			$changerecord = true;
		}
		if ($changerecord){
			if ($cur_record){
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got new record(unformated)", $cur_record);
				if ($pconfig[newkeys]||$pconfig[newvalues]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $pconfig[newvalues]);
				if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
				else $r[] = $cur_record;	//add full record
			}
			$cur_record = false;
			$cur_id = false;
		}
		$cur_record = array();
		if (!$started) continue;
		if ($pconfig[recordignore] && preg_match($pconfig[recordignore], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [recordignore]", $pconfig[recordignore]);
			continue;
		}
		//only first line contain the recordid, should in sub lines?
		if ($pconfig[recordid] && preg_match_all($pconfig[recordid], $line, $m)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [recordid=".$pconfig[recordid]."]", $m);
			$cur_id = trim($m[1][0], " :");	//firstmatch and firstpattern	
			$cur_record[record_id] = $cur_id;
		}
		//this line is a valid record line
		if ($pconfig[fieldstype] == 'simple'){//just single line
		//	$pconfig[fieldsmode][startline] = $n; //type maybe cross lines!
			$pconfig[fieldsmode][parentend] = $pconfig[recordend];
			$pconfig[fieldsmode][parentstart] = $pconfig[recordstart];
			//$pconfig[fieldsmode][debug] = $pconfig[debug];
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." simple fields [pconfig]", $pconfig[fieldsmode]);
			$fields = PHARSER::pharse_type($in, $pconfig[fieldsmode]);
			$cur_record = array_merge($cur_record, $fields);
		}else{ //for mixed models, try each group
			foreach($pconfig[fieldsmode] as $group=>$gpconfig){
				if (!preg_match($gpconfig[gmatcher], $line)){
					if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." !gmatch [gmatcher]", $pconfig[recordignore]);
					continue;
				}
				//try this group;
		//		$gpconfig[fieldsmode][startline] = $n; //type maybe cross lines!
				$gpconfig[fieldsmode][parentend] = $pconfig[recordend];
				$gpconfig[fieldsmode][parentstart] = $pconfig[recordstart];
				//$gpconfig[fieldsmode][debug] = $pconfig[debug];
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." $group fields [pconfig]", $gpconfig[fieldsmode]);
				$fields = PHARSER::pharse_type($in, $gpconfig[fieldsmode]);
				if ($gpconfig[fieldsmode][gmergeup])
					$cur_record = array_merge($cur_record, $fields);
				else
					$cur_record[$group] = $fields;
			}
		}
	}
	if ($cur_record){
		if ($pconfig[newkeys]||$pconfig[newvalues]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $pconfig[newvalues]);
		if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
		else $r[] = $cur_record;	//add full record
	}
	if ($pconfig[skiprecord]){
		$t = array();
		foreach($r as $record){
			if (!PHARSER::check_skip_record($record, $pconfig[skiprecord], $pconfig[debug])) continue;
			$t[] = $record;
		}
		return $t;
	}
	return $r;
}
function p_one_record_span_lines(&$in, $pconfig){
//valid config key:
//same as records_span_lines
	if ($pconfig[debug]) PHARSER::debug("pharser using type", "records_span_lines");
	$pconfig[recordstart] = '/^##@!!!1234567890/';
	$firstline = '##@!!!1234567890'; //fake recordstart
	array_unshift($in,  $firstline); 
	$r = PHARSER::p_records_span_lines($in, $pconfig);
	return $r;
}

//end of class
}
?>
