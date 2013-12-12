<?php
/**
 * Name: OpenID Server
 * Description: Allow users to use their profile url as OpenID.
 * Author: Fabio Comuni <http://kirgroup.com/profile/fabrixxm>
 * Version: 0.1
 * License: Apache License Version 2.0
 *
 * @package OpenIDServer
 * @author Fabio Comuni <fabrixxm@kirgroup.com>
 * @copyright 2013 Fabio Comuni
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache
 */

set_include_path( dirname(__FILE__) . PATH_SEPARATOR . get_include_path());

require_once 'config.php';


function openidserver_install(){
	register_hook('page_header', 'addon/openidserver/openidserver.php', 'openidserver_hook');
	$s = getOpenIDStore();
	$s->createTables();
}
function openidserver_uninstall() {
	unregister_hook('page_header', 'addon/openidserver/openidserver.php', 'openidserver_hook');	
}

function openidserver_module() {
}

function openidserver_hook(&$a, $b){
	global $server_url;
	$server_url = $a->get_baseurl()."/openidserver";
	
	if ( ($a->argc==2 && ($a->argv[0]=="profile" || $a->argv[0]=="u")) ||
		$a->argc==1 && $a->argv[0][0]=="~"){
		$a->page['htmlhead'] .= sprintf('<link rel="openid.server" href="%s" />'."\n", $server_url);
		$a->page['htmlhead'] .= sprintf('<link rel="openid2.provider" href="%s" />'."\n", $server_url);
	}
}

function openidserver_content(&$a) {
	global $server_url;
	$server_url = $a->get_baseurl()."/openidserver";
	
	
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');

	if (!local_user()){
		$hiddens = array();
		foreach ($_REQUEST as $key=>$value) {
			if (substr($key,0,7)=="openid_") $hiddens[$key]=$value;
		}
		return login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true, $hiddens);

	}
	
	#echo "<pre>"; var_dump($_REQUEST); killme();

	require_once 'lib/session.php';
	require_once 'lib/actions.php';

#	if ($_REQUEST['openid_mode']=="checkid_setup" &&
#		$_REQUEST['openid_claimed_id']!=idURL($a->user['nickname'])) {
#			echo "<pre>";
#			var_dump("mode",$_REQUEST['openid_mode']);
#			var_dump("claimed_id",$_REQUEST['openid_claimed_id']);
#			var_dump("nickname",$a->user['nickname']);
#	}
	


	$action = getAction();
	if (!function_exists($action)) {
		$action = 'action_default';
	}

	$resp = $action();

	$a->page['htmlhead'] .= '<meta http-equiv="cache-control" content="no-cache"/>';
	$a->page['htmlhead'] .= '<meta http-equiv="pragma" content="no-cache"/>"';
	echo writeResponse($resp);
	killme();
}

function openidserver_get_data($server, $req_url) {
	return array(
		'fullname' => 'Example User',
		'nickname' => 'example',
		'dob' => '1970-01-01',
		'email' => 'invalid@example.com',
		'gender' => 'F',
		'postcode' => '12345',
		'country' => 'ES',
		'language' => 'eu',
		'timezone' => 'America/New_York'
	);
}

