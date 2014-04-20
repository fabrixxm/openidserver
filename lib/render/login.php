<?php

require_once "lib/session.php";
require_once "lib/render.php";


function login_render($errors=null, $input=null, $needed=null)
{
    $hiddens = array();
    foreach ($_REQUEST as $key=>$value) {
        if (substr($key,0,7)=="openid_") $hiddens[$key]=$value;
    }
    return login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true, $hiddens);
}


?>