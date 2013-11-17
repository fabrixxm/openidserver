<?php


define('logged_in_pat', 'You are logged in as %s (URL: %s)');

/**
 * HTTP response line contstants
 */
define('http_bad_request', 'HTTP/1.1 400 Bad Request');
define('http_found', 'HTTP/1.1 302 Found');
define('http_ok', 'HTTP/1.1 200 OK');
define('http_internal_error', 'HTTP/1.1 500 Internal Error');

/**
 * HTTP header constants
 */
define('header_connection_close', 'Connection: close');
define('header_content_text', 'Content-Type: text/plain; charset=us-ascii');

define('redirect_message',
       'Please wait; you are being redirected to <%s>');


/**
 * Return a string containing an anchor tag containing the given URL
 *
 * The URL does not need to be quoted, but if text is passed in, then
 * it does.
 */
function link_render($url, $text=null) {
    $esc_url = htmlspecialchars($url, ENT_QUOTES);
    $text = ($text === null) ? $esc_url : $text;
    return sprintf('<a href="%s">%s</a>', $esc_url, $text);
}

/**
 * Return an HTTP redirect response
 */
function redirect_render($redir_url)
{
    $headers = array(http_found,
                     header_content_text,
                     header_connection_close,
                     'Location: ' . $redir_url,
                     );
    $body = sprintf(redirect_message, $redir_url);
    return array($headers, $body);
}



/**
 * Render an HTML page
 */
function page_render($body, $user=null, $title=null, $h1=null, $login=false)
{
    // No special headers here
    $headers = array();
    return array($headers, $body);
}

?>
