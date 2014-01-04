<?php
if(!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}

define("FORCESSL", true);

function oath_config() {
	$configarray = array(
	"name" => "OATH Two Factor Authentication",
	"description" => "Provides OATH token-based two factor authentication for clients and admins",
	"version" => "1.1.0",
	"author" => "Dr. McKay",
	"fields" => array(
		"enable_clients" => array("FriendlyName" => "Enable for Clients", "Type" => "yesno", "Description" => "Tick to enable OATH two-factor authentication support for clients"),
		"enable_admins" => array("FriendlyName" => "Enable for Admins", "Type" => "dropdown", "Options" => "No,Yes,Required", "Description" => "Enables OATH two-factor authentication support for admins", "Default" => "No"),
		"allow_secret_review" => array("FriendlyName" => "Allow Secret Review", "Type" => "yesno", "Description" => "Tick to allow users to re-view their secret and emergency code at any time"),
		"discrepancy" => array("FriendlyName" => "Discrepancy", "Type" => "text", "Size" => "4", "Description" => "Allowed code discrepancy, in 30-second intervals (recommended at least 1)", "Default" => "2")
	));
	return $configarray;
}

function oath_activate() {
	full_query("CREATE TABLE `mod_oath_client` (`userid` int(11) NOT NULL, `secret` varchar(64) NOT NULL, `emergencycode` varchar(64) NOT NULL, PRIMARY KEY (`userid`))");
	full_query("CREATE TABLE `mod_oath_admin` (`adminid` int(11) NOT NULL, `secret` varchar(64) NOT NULL, PRIMARY KEY (`adminid`))");
	
	return array('status' => 'success', 'description' => 'OATH Two-Factor Authentication has been enabled.');
}

function oath_deactivate() {
	full_query("DROP TABLE `mod_oath_client`");
	full_query("DROP TABLE `mod_oath_admin`");
	
	return array('status' => 'success', 'description' => 'OATH Two-Factor Authentication has been disabled and all tokens have been deleted.');
}

function oath_upgrade($vars) {
	full_query("CREATE TABLE `mod_oath_admin` (`adminid` int(11) NOT NULL, `secret` varchar(64) NOT NULL, PRIMARY KEY (`adminid`))");
}

function oath_clientarea($vars) {
    define("FORCESSL", true);
    
	if($_GET['qr']) {
		require_once('./modules/addons/oath/phpqrcode/qrlib.php');
		$company = get_query_val('tblconfiguration', 'value', "setting = 'CompanyName'");
		QRcode::png('otpauth://totp/' . urlencode(str_replace(' ', '', $company)) . '?secret=' . $_GET['secret']);
		exit(0);
	}
	
	require_once('./modules/addons/oath/GoogleAuthenticator.php');
	
	$gauth = new PHPGangsta_GoogleAuthenticator();
	
	$userid = $_SESSION['uid'];
	if($_SESSION['twofactorverify']) {
		$userid = $_SESSION['twofactorverify'];
	}
	
	$secret = get_query_val('mod_oath_client', 'secret', "userid = '$userid'");
	$emergencycode = get_query_val('mod_oath_client', 'emergencycode', "userid = '$userid'");
	
	if($_SESSION['twofactorverify'] && !$_POST['action'] == 'login') {
		$ret['pagetitle'] = 'Two-Factor Login Verification';
		$ret['breadcrumb'] = array('index.php?m=oath', 'Two-Factor Login Verification');
		$ret['templatefile'] = 'clientareaoath';
		$ret['requirelogin'] = false;
		$ret['vars']['secret'] = $secret;
		$ret['vars']['modulelink'] = $vars['modulelink'];
		$ret['vars']['login'] = 1;
		return $ret;
	} elseif($_SESSION['twofactorverify'] && $_POST['action'] == 'login') {
		if($_POST['emergencycode']) {
			$realcode = str_replace(' ', '', strtolower($emergencycode));
			$theircode = str_replace(' ', '', strtolower($_POST['emergencycode']));
			if($theircode == $realcode) {
				full_query("DELETE FROM `mod_oath_client` WHERE userid = '$userid'");
				$_SESSION['uid'] = $_SESSION['twofactorverify'];
				$_SESSION['upw'] = $_SESSION['twofactorverifypw'];
				unset($_SESSION['twofactorverify']);
				unset($_SESSION['twofactorverifypw']);
				header('Location: clientarea.php');
				exit(0);
			} else {
				$ret['pagetitle'] = 'Two-Factor Login Verification';
				$ret['breadcrumb'] = array('index.php?m=oath', 'Two-Factor Login Verification');
				$ret['templatefile'] = 'clientareaoath';
				$ret['requirelogin'] = false;
				$ret['vars']['secret'] = $secret;
				$ret['vars']['modulelink'] = $vars['modulelink'];
				$ret['vars']['login'] = 1;
				$ret['vars']['incorrect'] = 1;
				return $ret;
			}
		}
		
		$discrepancy = get_query_val('tbladdonmodules', 'value', "module = 'oath' AND setting = 'discrepancy'");
		if(!$gauth->verifyCode($secret, $_POST['code'], $discrepancy)) {
			$ret['pagetitle'] = 'Two-Factor Login Verification';
			$ret['breadcrumb'] = array('index.php?m=oath', 'Two-Factor Login Verification');
			$ret['templatefile'] = 'clientareaoath';
			$ret['requirelogin'] = false;
			$ret['vars']['secret'] = $secret;
			$ret['vars']['modulelink'] = $vars['modulelink'];
			$ret['vars']['login'] = 1;
			$ret['vars']['incorrect'] = 1;
			return $ret;
		} else {
			$_SESSION['uid'] = $_SESSION['twofactorverify'];
			$_SESSION['upw'] = $_SESSION['twofactorverifypw'];
			unset($_SESSION['twofactorverify']);
			unset($_SESSION['twofactorverifypw']);
			header('Location: clientarea.php');
			exit(0);
		}
	}
	
	if($secret) {
		$ret['vars']['active'] = 1;
	}
	
	if($_POST['action'] == 'enable') {
		$secret = $gauth->createSecret();
		$ret['vars']['verify'] = 1;
	} elseif($_POST['action'] == 'verify') {
		$secret = $_POST['secret'];
		$code = $_POST['code'];
		if(!$gauth->verifyCode($secret, $code, $vars['discrepancy'])) {
			$ret['vars']['incorrect'] = 1;
			$ret['vars']['verify'] = 1;
		} else {
			$characters = 'abcdefghijklmnopqrstuvwxyz1234567890';
			$emergencycode = '';
			for($i = 0; $i < 16; $i++) {
				if($i % 4 == 0 && $i != 0 && $i != 16) {
					$emergencycode .= ' ';
				}
				$emergencycode .= substr($characters, rand(0, strlen($characters) - 1), 1);
			}
			insert_query('mod_oath_client', array('userid' => $_SESSION['uid'], 'secret' => $secret, 'emergencycode' => $emergencycode));
			$ret['vars']['active'] = 1;
			$ret['vars']['firstactivation'] = 1;
		}
	} elseif($_POST['action'] == 'disable') {
		full_query("DELETE FROM `mod_oath_client` WHERE userid = '{$_SESSION['uid']}'");
		unset($ret['vars']['active']);
		unset($secret);
		unset($emergencycode);
	}
	
	$ret['pagetitle'] = 'Two-Factor Login Configuration';
	$ret['breadcrumb'] = array('index.php?m=oath' => 'Two-Factor Login Configuration');
	$ret['templatefile'] = 'clientareaoath';
	$ret['requirelogin'] = true;
	$ret['vars']['secret'] = $secret;
	$ret['vars']['emergencycode'] = $emergencycode;
	$ret['vars']['enable_clients'] = $vars['enable_clients'];
	$ret['vars']['allow_secret_review'] = $vars['allow_secret_review'];
	$ret['vars']['modulelink'] = $vars['modulelink'];
	
	return $ret;	
}

function oath_output($vars) {
    
	if($_GET['qr']) {
		require_once('./../modules/addons/oath/phpqrcode/qrlib.php');
		$company = get_query_val('tblconfiguration', 'value', "setting = 'CompanyName'");
		QRcode::png('otpauth://totp/' . urlencode(str_replace(' ', '', $company)) . 'Admin?secret=' . $_GET['secret']);
		exit(0);
	}
	
	echo '<div style="text-align: center;">';
	
	$secret = get_query_val('mod_oath_admin', 'secret', "adminid = '{$_SESSION['adminid']}'");
	
	require_once('./../modules/addons/oath/GoogleAuthenticator.php');
	$gauth = new PHPGangsta_GoogleAuthenticator();
	
	if($vars['enable_admins'] == 'No') {
		echo 'Two-factor authentication is currently disabled for administrators.';
	} elseif(!$secret && $_POST['enable']) {
		if($_POST['secret']) {
			if($gauth->verifyCode($_POST['secret'], $_POST['code'], $vars['discrepancy'])) {
				insert_query('mod_oath_admin', array('adminid' => $_SESSION['adminid'], 'secret' => $_POST['secret']));
				$_SESSION['twofactoradmin'] = $_SESSION['adminid'];
				header('Location: ' . $vars['modulelink']);
				exit(0);
			} else {
				echo '<p><b>Your code was incorrect.</b></p>';
				$secret = $_POST['secret'];
			}
		} else {
			$secret = $gauth->createSecret();
		}
		echo '<p>Please scan this QR code with your mobile authenticator app.</p>';
		echo '<img src="' . $vars['modulelink'] . '&qr=1&secret=' . $secret . '" />';
		echo '<p>If you are unable to scan, use this secret:<br />' . $secret . '</p>';
		echo '<form method="post" action="' . $vars['modulelink'] . '">';
		echo '<input type="hidden" name="secret" value="' . $secret . '" />';
		echo '<input type="text" name="code" placeholder="Enter your code" autocomplete="off" /><br /><br />';
		echo '<input type="submit" name="enable" value="Verify Code" class="btn btn-primary" />';
		echo '</form>';
	} elseif(!$secret && $vars['enable_admins'] == 'Required') {
		echo '<b>You must enable two-factor authentication to proceed.</b><br /><br />';
		echo '<form method="post" action="' . $vars['modulelink'] . '"><input type="submit" name="enable" value="Enable Two-Factor Authentication" class="btn btn-primary" /></form>';
	} elseif($secret && $_SESSION['twofactoradmin'] != $_SESSION['adminid']) {
		if($_POST['code']) {
			if($gauth->verifyCode($secret, $_POST['code'], $vars['discrepancy'])) {
				$_SESSION['twofactoradmin'] = $_SESSION['adminid'];
				$redirectURI = (!empty($_SESSION['original_request_uri'])) ? $_SESSION['original_request_uri'] : 'index.php';
				
				header('Location: '. $redirectURI);
				unset($_SESSION['original_request_uri']);
				exit(0);
			} else {
				echo '<p><b>Your code was incorrect.</b></p>';
			}
		}
		echo '<p>Please enter the code generated by your mobile authenticator app.</p>';
		echo '<form method="post" action="' . $vars['modulelink'] . '">';
		echo '<input type="text" name="code" placeholder="Enter your code" autocomplete="off" /><br /><br />';
		echo '<input type="submit" name="enable" value="Validate Login" class="btn btn-primary" />';
		echo '</form>';
	} elseif($secret && $_POST['disable']) {
		full_query("DELETE FROM `mod_oath_admin` WHERE adminid = '{$_SESSION['adminid']}'");
		unset($_SESSION['twofactoradmin']);
		header('Location: ' . $vars['modulelink']);
		exit(0);
	} elseif($secret) {
		echo '<p>You have two-factor authentication enabled.</p>';
		echo '<form method="post" action="' . $vars['modulelink'] . '"><input type="submit" name="disable" value="Disable Two-Factor Authentication" class="btn btn-danger" /></form>';
	} else {
		echo '<p>You do not have two-factor authentication enabled.</p>';
		echo '<form method="post" action="' . $vars['modulelink'] . '"><input type="submit" name="enable" value="Enable Two-Factor Authentication" class="btn btn-primary" /></form>';
	}
	
	echo '</div>';
}