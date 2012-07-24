<?php
$modmap = array(
	'net_utils'=>'network',
	'storage' => 'storage',
);
function mid2modfile($mid, &$modname){
	$m = preg_split("/\.|\.model\./", $mid);
	if (count($m)<2){
		$modname = "MOD_$mid";
		return "../../models/misc/mod.$mid.php";
	}
	$modname = "MOD_$m[1]";
	return "../../models/$m[0]/mod.$m[1].php";
}
