<?php
echo 'records="'.addslashes(json_encode(array(
array(
	id=>1,
	abc=>2,
	dev=>'eth0:99',
	ipaddress=>'192.253.253.111',
	netmask=>'255.255.255.0',
),
))).'"'."\n";
?>
