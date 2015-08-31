<?php

use WHMCS\View\Menu\Item as MenuItem;

if(!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}
 
add_hook('ClientAreaPrimarySidebar', 1, function (MenuItem $menu)
{
    if (!is_null($menu->getChild('My Account'))) {
		$menu->getChild('My Account')->addChild('OATH', array(
			'label' => Lang::trans('twofactorauth'),
			'uri' => 'index.php?m=oath',
			'order' => '51',
		));
    }
});
 
add_hook('ClientAreaSecondaryNavbar', 1, function (MenuItem $menu)
{
    if (!is_null($menu->getChild('Account')) && !is_null(Menu::context('client'))) {
		$menu->getChild('Account')->addChild('OATH', array(
			'label' => Lang::trans('twofactorauth'),
			'uri' => 'index.php?m=oath',
			'order' => '51',
		));
    }
});

function oath_hook_client_login($vars) {
	if($_SESSION['adminid']) {
		return;
	}
	
    $userid = $vars['userid'];
	if(!get_query_val('mod_oath_client', 'secret', "userid = '{$vars['userid']}'")) {
		if(isset($_SESSION['twofactorverify'])) {
			unset($_SESSION['twofactorverify']);
		}
		
		return;
	}
	
	if(!get_query_val('tbladdonmodules', 'value', "module = 'oath' AND setting = 'enable_clients'")) {
		if(isset($_SESSION['twofactorverify'])) {
			unset($_SESSION['twofactorverify']);
		}
		
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
    session_start();
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
	
	$_SESSION['original_request_uri'] = $_SERVER['REQUEST_URI'];
	
	header('Location: addonmodules.php?module=oath');
	session_write_close();
	exit(0);
}

add_hook("AdminAreaPage", 0, "oath_admin_page");

function oath_hook_admin_client_profile_tab_fields($vars) {
	$secret = get_query_val('mod_oath_client', 'secret', "userid = '{$vars['userid']}'");
	if($secret) {
		return array('OATH Addon' => '<label><input type="checkbox" name="disable_twofactor" value="1" /> Tick and save to disable two-factor authentication for this client</label>');
	} else {
		return array();
	}
}

add_hook("AdminClientProfileTabFields", 0, "oath_hook_admin_client_profile_tab_fields");

function oath_hook_admin_client_profile_tab_fields_save($vars) {
	if(isset($vars['disable_twofactor'])) {
		full_query("DELETE FROM `mod_oath_client` WHERE userid = '{$vars['userid']}'");
	}
}

add_hook("AdminClientProfileTabFieldsSave", 0, "oath_hook_admin_client_profile_tab_fields_save");
