<?php

require_once "lib/session.php";
require_once "lib/render.php";


function trust_render($info)
{
	$current_user = getLoggedInUser();
	$lnk = link_render(idURL($current_user));
	$trust_root = htmlspecialchars($info->trust_root);
	$trust_url = buildURL('trust', true);

	$prompt = sprintf(
		t('Do you wish to confirm your identity (<tt>%s</tt>) with <tt>%s</tt>?'), 
		$lnk, $trust_root
	);

	$form = sprintf(trust_form_pat, $trust_url, $prompt);


	$tpl = get_markup_template("trust.html", OpenIDServer_plugin_root);
	$body =replace_macros($tpl, array(
		'$prompt' => $prompt,
		'$trust_url' => $trust_url,
		'$confirm' => t("Confirm"),
		'$noconfirm' => t("Do not confirm"),
	));
	return page_render($body);
}

function noIdentifier_render()
{
	return page_render(t('No Identifier Sent'));
}

?>
