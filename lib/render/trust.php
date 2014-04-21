<?php

require_once "lib/session.php";
require_once "lib/render.php";


define('id_select_pat',
       '<p>You entered the server URL at the RP.
Please choose the name you wish to use.  If you enter nothing, the request will be cancelled.<br/>
<input type="text" name="idSelect" /></p>
');

define('no_id_pat',
'
You did not send an identifier with the request,
and it was not an identifier selection request.
Please return to the relying party and try again.
');

function trust_render($info)
{
    $current_user = getLoggedInUser();
    $lnk = idURL($current_user);
    $trust_root = htmlspecialchars($info->trust_root);
    $trust_url = buildURL('trust', true);

    if ($info->idSelect()) {
        $prompt = id_select_pat;
    } else {
        $prompt = sprintf(t('Do you wish to confirm your identity (<tt>%s</tt>) with <tt>%s</tt>'), $lnk, $trust_root);
    }
	
	/* get optional sreg fields */
	$sreg_fields = $info->message->getArgs('http://openid.net/extensions/sreg/1.1');
	$optfields=explode(",",$sreg_fields['optional']);
	
    $tpl = get_markup_template("trust.html",OPENIDSERVER_PLUGIN_PATH);
    $o = replace_macros($tpl, array(
        "lnk" => $lnk,
        "trust_root" => $trust_root,
        "trust_url" => $trust_url,
        "prompt" => $prompt,
        "confirm" => t("Confirm"),
        "noconfirm" => t("Do not confirm"),
		"extra" => openidserver_get_data($optfields),
    ));
                          
    return page_render($o, $current_user, t('Trust This Site'));
}

function noIdentifier_render()
{
    return page_render(no_id_pat, null, t('No Identifier Sent'));
}

?>