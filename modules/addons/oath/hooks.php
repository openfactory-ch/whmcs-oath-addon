<?php
if(!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}

function oath_hook_client_login($vars) {
	if($_SESSION['adminid']) {
		return;
	}
	
    $userid = $vars['userid'];
	if(!get_query_val('mod_oath_client', 'secret', "userid = '{$vars['userid']}'")) {
		return;
	}
	
	if(!get_query_val('tbladdonmodules', 'value', "module = 'oath' AND setting = 'enable_clients'")) {
		return;
	}
	
	$_SESSION['twofactorverify'] = $userid;
	$_SESSION['twofactorverifypw'] = $_SESSION['upw'];
	unset($_SESSION['uid']);
	unset($_SESSION['upw']);
	
	header('Location: index.php?m=oath');
	exit(0);
}

add_hook("ClientLogin", 0, "oath_hook_client_login");