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
		strip=>'/ @@@@@@@@ |\\\n$/',
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
		strip=>'/ @@@@@@@@ |\\\n$/',
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
		PHARSER::debug("PHARSER::debug", "wrong pharse type '$type'!");
		throw new Exception(__FUNCTION__." type $type not supported!");
	}
	if ($pconfig[debug]) PHARSER::debug("pharsing type", $type);
	$r = call_user_func("PHARSER::p_$type", &$in, $pconfig);
	if ($pconfig[skiprecord]){
		$t = array();
		foreach($r as $record){
			if (!PHARSER::check_skip_record($record, $pconfig[skiprecord], $pconfig[debug])) continue;
			$t[] = $record;
		}
		return $t;
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got skipped", $r);
	}
	return $r;
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
	//try to get error output
	$xcmd = '(
'.$cmd.' 2>/tmp/.cmderr.$$
ret=$?
if [ $ret -ne 0 ];then
	echo -n "#@ERROR@TRACE "
	cat /tmp/.cmderr.$$
	echo 
fi
rm /tmp/.cmderr.$$
exit $ret
)';
	if ($executor){
		$executor->exec($cmd, $out, $retvar);
	}else{
		exec($pconfig[errorignore]?$cmd:$xcmd, $out, $retvar);
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
	if (($retvar||$pconfig[getmsg]) && $pconfig['errpharser']){
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
					if ($debug) PHARSER::debug(__FUNCTION__." skip record by [".implode(",", $skipconfig)."]", $v);
					return false;
				}
			}
			if ($skip) continue;
		}
		$r[$k] = $v;
	}
	return true;
}

function get_file_size($bytes, $decimals=2){
	$bytes = strtoupper($bytes);
	if (preg_match("/(MB|GB|KB|TB|K|M|G|T)$/", $bytes)){
		//calc bytes first
		$b = preg_replace("/(MB|GB|KB|TB|K|M|G|T)$/", "", $bytes);
		$u = str_replace($b, "", $bytes);
		$b = trim($b);
		switch($u){
		case 'K':
		case 'KB': $bytes = $b*1024;break;
		case 'M':
		case 'MB': $bytes = $b*1024*1024;break;
		case 'G':
		case 'GB': $bytes = $b*1024*1024*1024;break;
		case 'T':
		case 'TB': return $bytes;
		}
	}
	if ($bytes === "") $bytes = 0;
	$kb = $bytes/1024;
	if ($kb<1.0) return number_format($bytes,$decimals, '.', ',')."Bytes";
	$mb = $bytes/1024/1024;
	if ($mb<1.0) return number_format($kb, $decimals, '.', ',')."KB";
	$gb = $bytes/1024/1024/1024;
	if ($gb<1.0) return number_format($mb, $decimals, '.', ',')."MB";
	$tb = $bytes/1024/1024/1024/1024;
	if ($tb<1.0) return number_format($gb, $decimals, '.', ',')."GB";
	return number_format($tb, $decimals, '.', ',')."TB";
}

function get_wwwn($string){
	return strtolower(preg_replace('/^0x/i', '', $string));
}

function get_byte_size($cap, $multiple1024=true){
	$cap = strtoupper($cap);
	if (preg_match("/(MB|GB|KB|TB|K|M|G|T)$/", $cap)){
		//calc bytes first
		$b = preg_replace("/(MB|GB|KB|TB|K|M|G|T)$/", "", $cap);
		$u = str_replace($b, "", $cap);
		$b = trim($b);
		$k = $multiple1024?1024:1000;
		switch($u){
		case 'K':
		case 'KB': $bytes = $b*$k;break;
		case 'M':
		case 'MB': $bytes = $b*$k*$k;break;
		case 'G':
		case 'GB': $bytes = $b*$k*$k*$k;break;
		case 'T':
		case 'TB': $bytes = $b*$k*$k*$k*$k;break;
		}
	}
	return $bytes;
}

function format_keys_and_values($array, $keys, $valueconfig=null, $ppconfig){
//change $array's key to new names if specified in $keys
//change $array's value to new by pconfig specified in $values.
//common pconfig key:
//newkeys:	array of (<old key>=><new key>), format keys.
//newvalues:	array of (<old key|new key>=>array(pconfig)), to pharse the value again.
//mergeup:	merge new values array to parent after get newvalues.
	$r = array();
	if (!$keys) $keys = array();	//value only
	foreach($array as $k=>$v){
		$newkeys = $keys[$k]?$keys[$k]:$k;
		if (!is_array($newkeys)) $newkeys = array($newkeys);
		foreach($newkeys as $newkey){
			$r[$newkey] = $v;
			if (!$valueconfig){ continue; }//get new values;
			$pconfig = $valueconfig[$newkey];
			if (!$pconfig) continue;
			if (!is_array($pconfig)){
				$newvalue = $v;
				switch($pconfig){
				case 'tolower':
					$newvalue = strtolower($v);
					break;
				case 'touppser':
					$newvalue = strtoupper($v);
					break;
				case 'tohex':
					$newvalue = dechex($v);
					break;
				case 'todec':
					$newvalue = hexdec($v);
					break;
				case 'boolean':
					$newvalue = (!$v||$v=='false'||$v=='0'||$v=='no'||$v=='NO'||$v=='FALSE'||$v=='null'||$v=='NULL')?false:true;
					break;
				case '!boolean':
					$newvalue = (!$v||$v=='false'||$v=='0'||$v=='no'||$v=='NO'||$v=='FALSE'||$v=='null'||$v=='NULL')?true:false;
					break;
				case 'password':
					$newvalue = '********';
					break;
				case 'wwwn':
					$newvalue = PHARSER::get_wwwn($v);	
					break;
				case 'bytesize':
					$newvalue = PHARSER::get_byte_size($v);
					break;
				case 'filesize':
					$newvalue = PHARSER::get_file_size($v);
					break;
				default:
					if (preg_match('/([^:]*)::(.*)/', $pconfig, $m)){
						$mod = $m[1];
						$func = $m[2];
						$mergeup = false;
						$newvalue = $mod::$func($v, $r, $mergeup);	//must be static function!
						if ($mergeup){
							$r = array_merge($r, $newvalue);
							continue;
						}
				
					}
				}
				$r[$newkey] = $newvalue;
				continue;
			}
			$lines = array($v);
			if ($ppconfig[debug]) PHARSER::debug(__FUNCTION__." format value[$newkey=$v], using type", $pconfig[type]);
			$pconfig[debug] = $ppconfig[debugall];
			$pconfig[debugall] = $ppconfig[debugall];
			$newvalue = PHARSER::pharse_type($lines, $pconfig);
			$r[$newkey]  = $newvalue;
			//can overwrite the $newkey by $newvalues
			if ($pconfig[mergeup]){
				$r[$newkey] = $v;
				$r = array_merge($r, $newvalue);
			}
		}
	}
	if ($ppconfig[debug]) PHARSER::debug(__FUNCTION__." got new values", $r);
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
	$trimvalue = $pconfig[trimvalue];
	if (!$trimvalue) $trimvalue = " ";
	foreach($fieldnames as $i=>$name){
		$v = trim($row[$i], $trimvalue);
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
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got fieldnames", $r);
	return $r;
}

function p_csv(&$in, $pconfig){
	$r = array();
	$pconfig[fieldnames] = is_array($pconfig[fieldnames])?$pconfig[fieldnames]:explode(",", $pconfig[fieldnames]);
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
		$row = str_getcsv($line, $pconfig[delimiter]?$pconfig[delimiter]:',', $pconfig[enclosure]?$pconfig[enclosure]:'"', $pconfig[escape]?$pconfig[escape]:'\\');
		//$row = str_getcsv($line);
		$row = PHARSER::get_field_names($row, $pconfig);
		if ($pconfig[newkeys]||$pconfig[newvalues]) $row = PHARSER::format_keys_and_values($row, $pconfig[newkeys], $pconfig[newvalues], $pconfig);
		$r[] = $row;
	}
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $row);
	return $r;
}

function p_just_output(&$in, $pconfig){
//valid config key:
//name: 	return keyname of output,
//ignore: 	regexp, skiped lines
//endout:	end of the msg, this line included
//endoutx:	endo of the msg, this line not included
//strip:	stips this pattern
//nextnlines:	just output nextnlines after igore and strip:
	$o = '';
	$ocount =0;
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
		$ocount ++;
		if ($pconfig[nextnlines] && $pconfig[nextnlines] == $ocount) break;
	}
	if ($pconfig[strip]) $o = preg_replace($pconfig[strip], '', $o);
	if (!$pconfig[name]) return $o;
	$r = array($pconfig[name]=>$o);
	if ($pconfig[arrayret]) $r = array($r);
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
	return $r;
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
		if (!$line){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", 'nothing');
			return array();
		}
	}else $line = $in;
	$fieldsep = $pconfig[fieldsep];
	$fieldnames = $pconfig[fieldnames] = is_array($pconfig[fieldnames])?$pconfig[fieldnames]:explode(",", $pconfig[fieldnames]);
	if (!$fieldsep){
		throw new Exception(__FUNCTION__.' missing [fieldsep] config.');
	}
	$r = preg_split($fieldsep, $line);
	if (!$fieldnames) return $r;
	$r = PHARSER::get_field_names($r, $pconfig);
	if ($pconfig[newkeys]||$pconfig[newvalues]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues], $pconfig);
	if ($pconfig[arrayret]) $r = array($r);
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
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
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." pharsing", $line);
		$r[] = PHARSER::p_record_in_one_line($line, $pconfig);
	}
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
	return $r;
}

function p_keyvalues_in_one_line(&$in, $pconfig, $format=true){
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
		if (!$line){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", 'nothing');
			return array();
		}
	}else $line = $in;
	if (!$pconfig[matcher]) $pconfig[matcher] = '/([^=]*) *= *([^ ]*)/';
	$r = array();
	if (preg_match_all($pconfig[matcher], $line, $m)){
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [matcher=".$pconfig[matcher]."]", $m);
		$trimkey = $pconfig[trimkey];
		$trimvalue = $pconfig[trimvalue];
		if (!$trimkey) $trimkey = " :";
		if (!$trimvalue) $trimvalue = " ";
		foreach($m[1] as $i=>$key){
			$r[trim($key, $trimkey)] = trim($m[2][$i], $trimvalue);
		}	
	}
	if ($format && ($pconfig[newkeys]||$pconfig[newvalues])) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues], $pconfig);
	if ($pconfig[arrayret]) $r = array($r);
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
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
		$kv = PHARSER::p_keyvalues_in_one_line($line, $pconfig, $format=false);
		$r = array_merge($r, $kv);
	}
	if ($pconfig[newkeys]||$pconfig[newvalues]) $r = PHARSER::format_keys_and_values($r, $pconfig[newkeys], $pconfig[newvalues], $pconfig);
	if ($arrayret) $r = array($r);
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
	return $r;
}

function p_records_in_one_line(&$in, $pconfig){
//valid config key:
//recordmatcher:	regexp, pattern for match a record
//recordspliter:	regexp, pattern for split line into record
//strip:		regexp, things was stripped before record was checken
//recordtype:		pharser config for in record pharse.
	$r = array();
	if (!$pconfig[recordmatcher] && !$pconfig[recordspliter]){
		throw new Exception(__FUNCTION__.' missing [recordmatcher|recordspliter] config.');
	}
	if (!is_array($pconfig[recordtype])){
		throw new Exception(__FUNCTION__.' missing [recordtype] config(array need).');
	}
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
		if (!$line){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", 'nothing');
			return array();
		}
	}else $line = $in;
	if ($pconfig[strip]) $line = preg_replace($pconfig[strip], '', $line);
	$m = array();
	if ($pconfig[recordspliter]) $m = preg_split($pconfig[recordspliter], $line);
	else if (preg_match_all($pconfig[recordmatcher], $line, $m)){
		$m = $m[1];
	}else $m = array();
	if (!$m){
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." !match [recordmatcher]", $pconfig[recordmatcher]);
		return array();
	}
	$records = array();
	foreach($m as $rline){
		$rconfig = $pconfig[recordtype];
		$rconfig[debug] = $pconfig[debugall];
		$rconfig[debugall] = $pconfig[debugall];
		if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." record, using", $rconfig[type]);
		$rin = array($rline);
		$record = PHARSER::pharse_type($rin, $rconfig);
		if ($pconfig[debug] && !$pconfig[recordtype][debug]) PHARSER::debug(__FUNCTION__." got record", $record);
		$records[] = $record;
	}
	return $records;
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
	$cur_record = array();
	$cur_needexpand = array();
	$cur_id	= false;
	while($in){
		$line = array_shift($in);
		$borrowed = false;
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
			$borrowed = preg_replace($pconfig[recordend], '', $line);
		}
		if (preg_match($pconfig[recordstart], $line, $m)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [recordstart]", $pconfig[recordstart]);
//			array_unshift($in, preg_replace($pconfig[recordstart], ' @@@@@@@@ ', $line));
			$started = true;
			$changerecord = true;
			$borrowed = preg_replace($pconfig[recordstart], '', $line);
		}
		if ($changerecord){
			if ($cur_record || $cur_needexpand){
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got new record(unformated)", $cur_record);
				if ($pconfig[newkeys]||$pconfig[newvalues]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $pconfig[newvalues], $pconfig);
				if ($cur_needexpand){
					foreach ($cur_needexpand as $e_records){
						foreach($e_records as $e_record){
							$rec = $cur_record;
							$rec = array_merge($rec, $e_record);
							$r[] = $rec;
						}
					}
				}else{
					if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
					else $r[] = $cur_record;	//add full record
				}
			}
			$cur_record = array();
			$cur_needexpand = array();
			$cur_id = false;
		}
		if (!$started) continue;
		if ($pconfig[recordignore] && preg_match($pconfig[recordignore], $line)){
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." match [recordignore]", $pconfig[recordignore]);
			continue;
		}
		//only first line contain the recordid, should in sub lines?
		if ($pconfig[recordid] && preg_match($pconfig[recordid], $line, $m)){
			$cur_id = trim($m[1], " :");	//firstmatch and firstpattern	
			$cur_record[record_id] = $cur_id;
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." recordid match [".$pconfig[recordid]."], got '$cur_id' from", $m);
		}
		//this line is a valid record line
		if ($borrowed) array_unshift($in, $borrowed);
		if ($pconfig[fieldstype] == 'simple'){//just single line
		//	$pconfig[fieldsmode][startline] = $n; //type maybe cross lines!
			if (!$pconfig[fieldsmode][parentend]) $pconfig[fieldsmode][parentend] = $pconfig[recordend];
			$pconfig[fieldsmode][parentstart] = $pconfig[recordstart];
			$pconfig[fieldsmode][debug] = $pconfig[debugall];
			$pconfig[fieldsmode][debugall] = $pconfig[debugall];
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." simple fields, using", $pconfig[fieldsmode][type]);
			$fields = PHARSER::pharse_type($in, $pconfig[fieldsmode]);
			if ($pconfig[debug] && !$pconfig[fileldsmode][debug]) PHARSER::debug(__FUNCTION__." got simple fields", $fields);
			if (preg_match('/^records/', $pconfig[fieldsmode][type]) && $pconfig[fieldsmode][mergeup]){
				//expand record to records with cur_record!
				$cur_needexpand[] = $fields;
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." cur_needexpand", $cur_needexpand);
			}else{
				$cur_record = array_merge($cur_record, $fields);
				if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." cur_record", $cur_record);
			}
		}else{ //for mixed models, try each group
			foreach($pconfig[fieldsmode] as $group=>$gpconfig){
				if (preg_match($gpconfig[gmatcher], $line)){
					if (!$borrowed) array_unshift($in, $line);
print_r($in);
					if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." gmatch [$group]", $pconfig[recordignore]);
					//try this group;
					if (!$gpconfig[parentend]) $gpconfig[parentend] = $pconfig[recordend];
					$gpconfig[parentstart] = $pconfig[recordstart];
					$gpconfig[debug] = $pconfig[debugall];
					$gpconfig[debugall] = $pconfig[debugall];
					if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." pharse field group [$group] using", $gpconfig[type]);
					$fields = PHARSER::pharse_type($in, $gpconfig);
					if ($pconfig[debug] && !$gpconfig[debug]) PHARSER::debug(__FUNCTION__." got group[$group] fields", $fields);
					if (preg_match('/^records/', $gpconfig[type]) && $gpconfig[mergeup]){
						//expand record to records with cur_record!
						$cur_needexpand[] = $fields;
						if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." cur_needexpand", $cur_needexpand);
					}else{
						if ($gpconfig[mergeup])
							$cur_record = array_merge($cur_record, $fields);
						else
							$cur_record[$group] = $fields;
					}
					break;	//only match one time!
				}
			}
			if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." cur_record", $cur_record);
		}
	}
	if ($cur_record || $cur_needexpand){
		if ($pconfig[newkeys]||$pconfig[newvalues]) $cur_record = PHARSER::format_keys_and_values($cur_record, $pconfig[newkeys], $pconfig[newvalues], $pconfig);
		if ($cur_needexpand){
			foreach ($cur_needexpand as $e_records){
				foreach($e_records as $e_record){
					$rec = $cur_record;
					$rec = array_merge($rec, $e_record);
					$r[] = $rec;
				}
			}
		}else{
			if ($cur_id && $pconfig[idindexed]) $r[$cur_id] = $cur_record;
			else $r[] = $cur_record;	//add full record
		}
	}
/*
	if ($pconfig[skiprecord]){
		$t = array();
		foreach($r as $record){
			if (!PHARSER::check_skip_record($record, $pconfig[skiprecord], $pconfig[debug])) continue;
			$t[] = $record;
		}
		return $t;
	}
*/
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
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
	if ($pconfig[debug]) PHARSER::debug(__FUNCTION__." got", $r);
	return $r;
}

//end of class
}
?>
