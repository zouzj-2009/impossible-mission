<?php
class PHARSER{
function pharse_type(&$in, $pconfig, &$perror){
	$type = $pconfig[type];
	if (!method_exists('PHARSER', "p_$type")){
		$perror = "type $type not supported!";
		return array();
	}
	return call_user_func("PHARSER::p_$type", &$in, $pconfig, $perror);
}

function pharse_cmd($cmd, $pconfig, &$perror, &$cmdresult=null, &$raw=null, &$trace=null){
	exec($cmd, $out, $retvar);
	if ($cmdresult) $cmdresult = $retvar;
	if ($raw) $raw = $out;
	$perror = null;
	if (!$retvar && $pconfig['errpharse']){
		return PHARSER::pharse_type($out, $pconfig['errpharse'], $perror);
	}
	if (!$pconfig) return $out;	//directly out;
	return PHARSER::pharse_type($out, $pconfig, $perror);
}

function format_keys_and_values($array, $keys, &$perror, $values=null){
//change $array's key to new names if specified in $keys
//change $array's value to new by pconfig specified in $values.
//common pconfig key:
//newkeys:	array of (<old key>=><new key>), format keys.
//newvalues:	array of (<old key|new key>=>array(pconfig)), to pharse the value again.
//mergeup:	merge new values array to parent after get newvalues.
	$r = array();
	foreach($array as $k=>$v){
		$newkey = $keys[$k]?$keys[$k]:$k;
		if (!$values){
			$r[$newkey] = $v;
			continue;
		}//get new values;
		$pconfig = $values[$k]?$values[$k]:$values[$newkey];
		if (!$pconfig) continue;
		$lines = array($v);
		$newvalue = PHARSER::pharse_type($lines, $pconfig, $perror);
		$r[$newkey]  = $newvalue;
		//can overwrite the $newkey by $newvalues
		if ($pconfig[mergeup]) $r = array_merge($r, $newvalue);
	}
	return $r;
}

function get_field_names($row, $pconfig, &$perror){
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
			$r[$fname] = PHARSER::pharse_type($v, $pconfig[$name], $perror);
			continue;
		}
		//not internal field name
		$r[$name] = $v;
	}
	return $r;
}

function p_record_in_one_line($line, $pconfig, &$perror){
//valid config key:
//fieldsep:	regexp, for fields seprate in line
//fieldnames:	string, 'name1,name2,name3...', define value name, see@get_field_names;
	$fieldsep = $pconfig[fieldsep];
	$fieldnames = $pconfig[fieldnames] = is_array($pconfig[fieldnames])?$pconfig[fieldnames]:explode(",", $pconfig[fieldnames]);
	if (!$fieldsep){
		$perror = __FUNCTION__.' missing [fieldsep] config.';
		return $r;
	}
	$r = preg_split($fieldsep, $line);
	if (!$fieldnames) return $r;
	$r = PHARSER::get_field_names($r, $pconfig, $perror);
	if ($pconfig[newkeys]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $perror, $pconfig[newvalues]);
	return $r;
}

function p_one_record_per_line(&$in, $pconfig, &$perror){
//valid config key:
//ignore:	regexp, lines to be skiped.
//fieldsep:	regexp, for fields seprate in line
//fieldnames:	string, 'name1,name2,name3...', define value name, see@get_field_names;
	$r = array();
	$ignore = $pconfig[ignore];
	$fieldsep = $pconfig[fieldsep];
	$pconfig[fieldnames] = is_array($pconfig[fieldnames])?$pconfig[fieldnames]:explode(",", $pconfig[fieldnames]);
	if (!$fieldsep){
		$perror = __FUNCTION__.' missing [fieldsep] config.';
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
		$r[] = PHARSER::p_record_in_one_line($line, $pconfig, $perror);
	}
	return $r;
}

function p_keyvalues_in_one_line($line, $pconfig, &$perror){
//valid config key:
//matcher:	regexp, (key).*(value)
	if (!$pconfig[matcher]) $pconfig[matcher] = '/(.*)/=/(.*)/';
	$r = array();
	if (preg_match_all($pconfig[matcher], $line, $m)){
		foreach($m[1] as $i=>$key){
			$r[trim($key, " :")] = trim($m[2][$i]);
		}	
	}
	if ($pconfig[newkeys]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $perror, $pconfig[newvalues]);
	return $r;
}

function p_keyvalues_span_lines(&$in, $pconfig, &$perror){
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
		$kv = PHARSER::p_keyvalues_in_one_line($line, $pconfig, $perror);
		$r = array_merge($r, $kv);
	}
	return $r;
}

function p_records_span_lines(&$in, $pconfig, &$perror){
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
		$perror = __FUNCTION__.' missing [recordstart] config';
		return $r;
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
				if ($pconfig[newkeys]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $perror, $pconfig[newvalues]);
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
			$fields = PHARSER::pharse_type($in, $pconfig[fieldsmode], $perror);
			$cur_record = array_merge($cur_record, $fields);
		}else{ //for mixed models, try each group
			foreach($pconfig[fieldsmode] as $group=>$gpconfig){
				if (!preg_match($gpconfig[matcher], $line)) continue;
				//try this group;
		//		$gpconfig[fieldsmode][startline] = $n; //type maybe cross lines!
				$gpconfig[fieldsmode][parentend] = $pconfig[recordstart];
				$fields = PHARSER::pharse_type($in, $gpconfig[fieldsmode], $perror);
				if ($gpconfig[fieldsmode][mergeup])
					$cur_record = array_merge($cur_record, $fields);
				else
					$cur_record[$group] = $fields;
			}
		}
	}
	if ($cur_record){
		if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
		else $r[] = $cur_record;	//add full record
	}
	return $r;
}
function p_one_record_span_lines($in, $pconfig, &$perror){
//valid config key:
//same as records_span_lines
	$r = PHARSER::p_records_span_lines($in, $pconfig, &$perror);
	return array_shift($r);
}

//end of class
}
?>
