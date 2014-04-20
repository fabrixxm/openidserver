<?php

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
    $body = sprintf(t('Please wait; you are being redirected to <%s>'), $redir_url);
    
    
    return array($headers, $body);
}



/**
 * Render an HTML page
 */
function page_render($body, $user, $title, $h1=null, $login=false)
{
    $h1 = $h1 ? $h1 : $title;

    $text = sprintf("<h1>%s</h1>%s", $h1, $body);
    // No special headers here
    $headers = array();
    return array($headers, $text);
}

?>