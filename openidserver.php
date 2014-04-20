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
define("OPENIDSERVER_PLUGIN_PATH",dirname(__FILE__));
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
    
	require_once 'lib/session.php';
	require_once 'lib/actions.php';
	
	$action = getAction();
	if (!function_exists($action)) {
		$action = 'action_default';
    }
	$resp = $action();
	$a->page['htmlhead'] .= '<meta http-equiv="cache-control" content="no-cache"/>';
	$a->page['htmlhead'] .= '<meta http-equiv="pragma" content="no-cache"/>"';
	$a->page['content'] = writeResponse($resp);
    return;
}

function openidserver_get_data($server, $req_url) {
    $a = get_app();
	return array(
		'fullname' => $a->user['username'],
		'nickname' => $a->user['nickname'],
		'dob' => $a->contact['db'],
		'email' => $a->user['email'],
		#'gender' => 'F',
		#'postcode' => '12345',
		#'country' => 'ES',
		'language' => $a->user['language'],
		'timezone' => $a->user['timezone']
	);
}

