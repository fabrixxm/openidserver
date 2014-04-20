<?php

//define("Auth_OpenID_RAND_SOURCE", null);
define("OpenIDServer_plugin_root", dirname(__file__));



$server_url = "";


function getOpenIDStore(){
	require_once 'FriendicaStore.php';
	$s = new FriendicaStore();
	return $s;
}
