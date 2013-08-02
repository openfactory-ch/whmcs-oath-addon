<?php
if(!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}

function oath_config() {
	$configarray = array(
	"name" => "OATH Two Factor Authentication",
	"description" => "Provides OATH token-based two factor authentication for clients and admins",
	"version" => "1.0.0",
	"author" => "Dr. McKay",
	"fields" => array(
		"enable_clients" => array("FriendlyName" => "Enable for Clients", "Type" => "yesno", "Description" => "Tick to enable OATH two-factor authentication support for clients"),
		//"enable_admins" => array("FriendlyName" => "Enable for Admins", "Type" => "yesno", "Description" => "Tick to enable OATH two-factor authentication support for admins"),
		"allow_secret_review" => array("FriendlyName" => "Allow Secret Review", "Type" => "yesno", "Description" => "Tick to allow users to re-view their secret and emergency code at any time"),
		"discrepancy" => array("FriendlyName" => "Discrepancy", "Type" => "text", "Size" => "4", "Description" => "Allowed code discrepancy, in 30-second intervals (recommended at least 1)")
	));
	return $configarray;
}

function oath_activate() {
	mysql_query("CREATE TABLE `mod_oath_client` (`userid` int(11) NOT NULL, `secret` varchar(64) NOT NULL, `emergencycode` varchar(64) NOT NULL, PRIMARY KEY (`userid`))");
	//mysql_query("CREATE TABLE `mod_oath_admin` (`adminid` int(11) NOT NULL, `secret` varchar(64) NOT NULL, PRIMARY KEY (`userid`))");
	
	return array('status' => 'success', 'description' => 'OATH Two-Factor Authentication has been enabled.');
}

function oath_deactivate() {
	mysql_query("DROP TABLE `mod_oath_client`");
	mysql_query("DROP TABLE `mod_oath_admin`");
	
	return array('status' => 'success', 'description' => 'OATH Two-Factor Authentication has been disabled and all tokens have been deleted.');
}

function oath_clientarea($vars) {
	require_once('./modules/addons/oath/GoogleAuthenticator.php');
	
	$gauth = new PHPGangsta_GoogleAuthenticator();
	
	$userid = $_SESSION['uid'];
	if($_SESSION['twofactorverify']) {
		$userid = $_SESSION['twofactorverify'];
	}
	
	$q = mysql_fetch_array(mysql_query("SELECT secret, emergencycode FROM `mod_oath_client` WHERE userid = '$userid'"));
	
	if($_SESSION['twofactorverify'] && !$_POST['action'] == 'login') {
		$ret['pagetitle'] = 'Two-Factor Login Verification';
		$ret['breadcrumb'] = array('index.php?m=oath', 'Two-Factor Login Verification');
		$ret['templatefile'] = 'clientareaoath';
		$ret['requirelogin'] = false;
		$ret['vars']['secret'] = $q['secret'];
		$ret['vars']['modulelink'] = $vars['modulelink'];
		$ret['vars']['login'] = 1;
		return $ret;
	} elseif($_SESSION['twofactorverify'] && $_POST['action'] == 'login') {
		if($_POST['emergencycode']) {
			if($_POST['emergencycode'] == $q['emergencycode']) {
				mysql_query("DELETE FROM `mod_oath_client` WHERE userid = '$userid'");
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
				$ret['vars']['secret'] = $q['secret'];
				$ret['vars']['modulelink'] = $vars['modulelink'];
				$ret['vars']['login'] = 1;
				$ret['vars']['incorrect'] = 1;
				return $ret;
			}
		}
		
		$discrepancy = mysql_fetch_array(mysql_query("SELECT value FROM `tbladdonmodules` WHERE module = 'oath' AND setting = 'discrepancy'"));
		if(!$gauth->verifyCode($q['secret'], $_POST['code'], $discrepancy['value'])) {
			$ret['pagetitle'] = 'Two-Factor Login Verification';
			$ret['breadcrumb'] = array('index.php?m=oath', 'Two-Factor Login Verification');
			$ret['templatefile'] = 'clientareaoath';
			$ret['requirelogin'] = false;
			$ret['vars']['secret'] = $q['secret'];
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
	
	if($_GET['qr']) {
		require_once('./modules/addons/oath/phpqrcode/qrlib.php');
		$company = mysql_fetch_array(mysql_query("SELECT value FROM `tblconfiguration` WHERE setting = 'CompanyName'"));
		QRcode::png('otpauth://totp/' . urlencode(str_replace(' ', '', $company['value'])) . '?secret=' . $_GET['secret']);
		return;
	}
	
	if($q['secret']) {
		$ret['vars']['active'] = 1;
	}
	
	if($_POST['action'] == 'enable') {
		$q['secret'] = $gauth->createSecret();
		$ret['vars']['verify'] = 1;
	} elseif($_POST['action'] == 'verify') {
		$secret = $_POST['secret'];
		$code = $_POST['code'];
		$q['secret'] = $secret;
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
			mysql_query("INSERT INTO `mod_oath_client` SET userid = '{$_SESSION['uid']}', secret = '$secret', emergencycode = '$emergencycode'");
			$ret['vars']['active'] = 1;
			$ret['vars']['firstactivation'] = 1;
			$q['emergencycode'] = $emergencycode;
		}
	} elseif($_POST['action'] == 'disable') {
		mysql_query("DELETE FROM `mod_oath_client` WHERE userid = '{$_SESSION['uid']}'");
		unset($ret['vars']['active']);
		unset($q);
	}
	
	$ret['pagetitle'] = 'Two-Factor Login Configuration';
	$ret['breadcrumb'] = array('index.php?m=oath' => 'Two-Factor Login Configuration');
	$ret['templatefile'] = 'clientareaoath';
	$ret['requirelogin'] = true;
	$ret['vars']['secret'] = $q['secret'];
	$ret['vars']['emergencycode'] = $q['emergencycode'];
	$ret['vars']['enable_clients'] = $vars['enable_clients'];
	$ret['vars']['allow_secret_review'] = $vars['allow_secret_review'];
	$ret['vars']['modulelink'] = $vars['modulelink'];
	
	return $ret;	
}