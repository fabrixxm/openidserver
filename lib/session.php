<?php

require_once "config.php";
require_once "lib/render.php";
require_once "Auth/OpenID/Server.php";

/**
 * Get the style markup
 */
function getStyle()
{
    $parent = rtrim(dirname(getServerURL()), '/');
    $url = htmlspecialchars($parent . '/openid-server.css', ENT_QUOTES);
    return sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $url);
}

/**
 * Get the URL of the current script
 */
function getServerURL()
{
	$a = get_app();
	return $a->get_baseurl()."/openidserver";
}

/**
 * Build a URL to a server action
 */
function buildURL($action=null, $escaped=true)
{
    $url = getServerURL();
    if ($action) {
        $url .= '/' . $action;
    }
    return $escaped ? htmlspecialchars($url, ENT_QUOTES) : $url;
}

/**
 * Extract the current action from the request
 */
function getAction()
{
    $a = get_app();
    $action = $a->argv[1];
    $function_name = 'action_' . $action;
	return $function_name;
}

/**
 * Write the response to the request
 */
function writeResponse($resp)
{
    if (is_array($resp)){
        list ($headers, $body) = $resp;
        logger("headers: $headers, body: $body", LOGGER_DEBUG);
        array_walk($headers, 'header');
    } else {
        $body = $resp;
    }
    header(header_connection_close);
    return $body;
}

/**
 * Instantiate a new OpenID server object
 */
function getServer()
{
    static $server = null;
    if (!isset($server)) {
        $server = new Auth_OpenID_Server(getOpenIDStore(),
                                         buildURL());
    }
    return $server;
}

/**
 * Return a hashed form of the user's password
 */
function hashPassword($password)
{
    return bin2hex(Auth_OpenID_SHA1($password));
}

/**
 * Get the openid_url out of the cookie
 *
 * @return mixed $openid_url The URL that was stored in the cookie or
 * false if there is none present or if the cookie is bad.
 */
function getLoggedInUser()
{
    $a = get_app();
    return local_user()
        ? $a->get_baseurl()."/profile/".$a->user['nickname']
        : false;
}

/**
 * Set the openid_url in the cookie
 *
 * @param mixed $identity_url The URL to set. If set to null, the
 * value will be unset.
 */
function setLoggedInUser($identity_url=null)
{
    if (!isset($identity_url)) {
        unset($_SESSION['openid_url']);
    } else {
        $_SESSION['openid_url'] = $identity_url;
    }
}

function getRequestInfo()
{
    return isset($_SESSION['request'])
        ? unserialize($_SESSION['request'])
        : false;
}

function setRequestInfo($info=null)
{
    if (!isset($info)) {
        unset($_SESSION['request']);
    } else {
        $_SESSION['request'] = serialize($info);
    }
}


function getSreg($identity)
{
    // from config.php
    global $openid_sreg;

    if (!is_array($openid_sreg)) {
        return null;
    }

    return $openid_sreg[$identity];

}

function idURL($identity)
{
    logger("identity: $identity", LOGGER_DEBUG);
	// $identity seems to be === to URL.. dunno why..
	return $identity;
}

function idFromURL($url)
{

	$a = get_app();
	$url = str_replace($a->get_baseurl()."/", "",$url);
	$argv = explode('/',$url);
	$argc = count($this->argv);
	
	if ($argc==2) return $argv[1];
	return null;
}

?>