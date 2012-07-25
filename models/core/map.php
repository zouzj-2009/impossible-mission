<?php
$modmap = array(
	'net_utils'=>'network',
	'storage' => 'storage',
	'ui_common' => 'misc',
	'sys_misc.pciinfo'=>'hardware.pciinfo',
	'target_iscsi' => 'iscsi',
	'storage_scsi' => 'storage',
	'sys_misc' => 'misc',
);

function mid2modfile(&$mid, &$class){
//so, the class name must be MOD_$dirname_$modname!
	global $modmap;
	$mmid = $modmap[$mid];
	if ($mmid) $mid = $mmid;
	$m = preg_split("/\.|\.model\./", $mid);
	if (count($m)<2){
		$class = "MOD_misc_$mid";
		$mid = "misc.$mid";
		return "../../models/misc/mod.$m[0].php";
	}
	$mod = array_pop($m);
	$mp = implode(".", $m);
	$mc = implode("_", $m);
	$class = "MOD_$mc"."_$mod";
	$mdir = $modmap[$mp];
	if (!$mdir) $mdir=implode("/", $m);
	$mid = str_replace("/", ".", "$mdir.$mod");
	return "../../models/$mdir/mod.$mod.php";
}
