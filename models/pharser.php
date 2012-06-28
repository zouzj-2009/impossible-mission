<?php
class PHARSER{

static $default_error_pharser = array(
	type=>'one_record_span_lines',
	recordstart=>'/^#### error msg begin ####/',
	endoutx=>'/^#### error msg end ####/',
	fieldtype=>'simple',
	fieldmode=>array(
		type=>'just_output',
		name=>'msg',
		ignore=>'/^#### error msg begin ####/',
	),
);

function pharse_type(&$in, $pconfig){
	$type = $pconfig[type];
	if (!method_exists('PHARSER', "p_$type")){
		throw new Exception(__FUNCTION__." type $type not supported!");
	}
	return call_user_func("PHARSER::p_$type", &$in, $pconfig);
}

function pharse_cmd($name, $pconfig, $args, &$cmdresult, &$caller, &$raw=null){
	$cmd = $pconfig[cmd];
	if ($pconfig[commargs]) $args = array_merge($pconfig[commonargs], $args);
	if (is_array($args)){
		foreach($args as $k=>$v){ $cmd = str_replace("%$k%", $v, $cmd); }
	}
	//todo: using executer to do $pcmd[cmd] with $args
	exec($cmd, $out, $retvar);

	echo "$name: $cmd return $retvar\n";
	$cmdresult = $retvar;
	if ($raw!==null) $raw = $out;
	if (!$pconfig['errpharse'] &&!$pconfig['skiperror']){
		//default error pharse
		$pconfig['errpharse'] = PHARSER::$default_error_pharser;
	}
//	non 0 is error
	if ($retvar && $pconfig['errpharse']){
		return PHARSER::pharse_type($out, $pconfig['errpharse']);
	}
	if (!$pconfig[type]) return $out;	//directly out;
	return PHARSER::pharse_type($out, $pconfig);
}

function check_skip_record($array, $skipconfig){
//check for record's key through skipconfig, if match ,{} return.
	foreach($array as $k=>$v){
		if (array_key_exists($k, $skipconfig)){
			$skip = false;
			foreach($skipconfig[$k] as $skipv){
				if ($skipv == $v) return false;
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
	foreach($array as $k=>$v){
		$newkey = $keys[$k]?$keys[$k]:$k;
		$r[$newkey] = $v;
		if (!$values){
			continue;
		}//get new values;
		$pconfig = $values[$k]?$values[$k]:$values[$newkey];
		if (!$pconfig) continue;
		$lines = array($v);
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
	$o = '';
	while ($in){
		$line = array_shift($in);
		if ($pconfig[endoutx] && preg_match($pconfig[endoutx], $line)) break;
		if ($pconfig[endout] && preg_match($pconfig[endout], $line)){
			$o .= $line."\n";
			break;
		}
		if ($pconfig[parentend] && preg_match($pconfig[parentend], $line)){
			array_unshift($in, $line);
			break;
		}
		if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)) continue;
		$o .= $line."\n";
	}
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
			if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)) continue;
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
	if ($pconfig[newkeys]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues]);
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
		if ($pconfig[parentend] && preg_match($pconfig[parentend], $line)){
			array_unshift($in, $line);
			break;
		}
		if ($ignore && preg_match($ignore, $line)){
			if (getenv("MODETEST")){echo "skip $line by $ignore\n";}
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
			if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)) continue;
			break;
		}
		if (!$line) return array();
	}else $line = $in;
	if (!$pconfig[matcher]) $pconfig[matcher] = '/(.*)/=/(.*)/';
	$r = array();
	if (preg_match_all($pconfig[matcher], $line, $m)){
		foreach($m[1] as $i=>$key){
			$r[trim($key, " :")] = trim($m[2][$i]);
		}	
	}
	if ($pconfig[newkeys]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues]);
	return $r;
}

function p_keyvalues_span_lines(&$in, $pconfig){
//valid config key:
//matcher:	regexp, (key).*(value)
	$r = array();
	$ignore = $pconfig[ignore];
	while($in){
		$line = array_shift($in);
		if ($pconfig[parentend] && preg_match($pconfig[parentend], $line)){
			array_unshift($in, $line);
			break;
		}
		if ($ignore && preg_match($ignore, $line)){
			if (getenv("MODETEST")){echo "skip $line by $ignore\n";}
			continue;
		}
		$kv = PHARSER::p_keyvalues_in_one_line($line, $pconfig);
		$r = array_merge($r, $kv);
	}
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
//	matcher:	regexp: lines match this group.
//	mergeup:	[true|false]: merge value to fater, other than store in a group array.
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
		if ($pconfig[ignore] && preg_match($pconfig[ignore], $line)) continue;
		if ($pconfig[parentend] && preg_match($pconfig[parentend], $line)){
			//pharse end by parent ending token.
			array_unshift($in, $line);
			break;
		}
		if (preg_match($pconfig[recordstart], $line, $m)){
			array_unshift($in, preg_replace($pconfig[recordstart], ' @@@@@@@@ ', $line));
			$started = true;
			if ($cur_record){
				if ($pconfig[newkeys]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $pconfig[newvalues]);
				if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
				else $r[] = $cur_record;	//add full record
			}
			$cur_record = false;
			$cur_id = false;
		}
		$cur_record = array();
		if (!$started) continue;
		if ($pconfig[recordignore] && preg_match($pconfig[recordignore], $line)) continue;
		//only first line contain the recordid, should in sub lines?
		if ($pconfig[recordid] && preg_match_all($pconfig[recordid], $line, $m)){
			$cur_id = trim($m[1][0], " :");	//firstmatch and firstpattern	
			$cur_record[record_id] = $cur_id;
		}
		if ($pconfig[recordend] && preg_match($pconfig[recordend], $line)){
			$started = false;	//ended, but this line maybe also need pharse
		}
		//this line is a valid record line
		if ($pconfig[fieldstype] == 'simple'){//just single line
		//	$pconfig[fieldsmode][startline] = $n; //type maybe cross lines!
			$pconfig[fieldsmode][parentend] = $pconfig[recordstart];
			$fields = PHARSER::pharse_type($in, $pconfig[fieldsmode]);
			$cur_record = array_merge($cur_record, $fields);
		}else{ //for mixed models, try each group
			foreach($pconfig[fieldsmode] as $group=>$gpconfig){
				if (!preg_match($gpconfig[matcher], $line)) continue;
				//try this group;
		//		$gpconfig[fieldsmode][startline] = $n; //type maybe cross lines!
				$gpconfig[fieldsmode][parentend] = $pconfig[recordstart];
				$fields = PHARSER::pharse_type($in, $gpconfig[fieldsmode]);
				if ($gpconfig[fieldsmode][mergeup])
					$cur_record = array_merge($cur_record, $fields);
				else
					$cur_record[$group] = $fields;
			}
		}
	}
	if ($cur_record){
		if ($pconfig[newkeys]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $pconfig[newvalues]);
		if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
		else $r[] = $cur_record;	//add full record
	}
	if ($pconfig[skiprecord]){
		$t = array();
		foreach($r as $record){
			if (!PHARSER::check_skip_record($record, $pconfig[skiprecord])) continue;
			$t[] = $record;
		}
		return $t;
	}
	return $r;
}
function p_one_record_span_lines($in, $pconfig){
//valid config key:
//same as records_span_lines
	$r = PHARSER::p_records_span_lines($in, $pconfig);
	return array_shift($r);
}

//end of class
}
?>
