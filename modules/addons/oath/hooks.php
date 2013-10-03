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

function oath_hook_admin_logout($vars) {
	unset($_SESSION['twofactoradmin']);
}

add_hook("AdminLogout", 0, "oath_hook_admin_logout");

function oath_admin_page($vars) {
	$script = explode('/', $_SERVER['SCRIPT_NAME']);
	if(($script[count($script) - 1] == 'addonmodules.php' && $_GET['module'] == 'oath') || $_SESSION['twofactoradmin'] == $_SESSION['adminid']) {
		return;
	}
	
	$secret = get_query_val('mod_oath_admin', 'secret', "adminid = '{$_SESSION['adminid']}'");
	$enable_admins = get_query_val('tbladdonmodules', 'value', "module = 'oath' AND setting = 'enable_admins'");
	$access = explode(',', get_query_val('tbladdonmodules', 'value', "module = 'oath' AND setting = 'access'"));
	$role = get_query_val('tbladmins', 'roleid', "id = '{$_SESSION['adminid']}'");
	
	if((!$secret && $enable_admins != 'Required') || $enable_admins == 'No' || !in_array($role, $access)) {
		return;
	}
	
	header('Location: addonmodules.php?module=oath');
	exit(0);
}

add_hook("AdminAreaPage", 0, "oath_admin_page");