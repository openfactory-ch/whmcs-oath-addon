<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

define("FORCESSL", true);

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\User\Client;

function oath_config() {
    $configarray = array(
        "name" => "OATH Two Factor Authentication",
        "description" => "Provides OATH token-based two factor authentication for clients and admins",
        "version" => "2.1",
        "author" => "openfactory-ch",
        "fields" => array(
            "enable_clients" => array("FriendlyName" => "Enable for Clients", "Type" => "yesno", "Description" => "Tick to enable OATH two-factor authentication support for clients"),
            "enable_admins" => array("FriendlyName" => "Enable for Admins", "Type" => "dropdown", "Options" => "No,Yes,Required", "Description" => "Enables OATH two-factor authentication support for admins", "Default" => "No"),
            "allow_secret_review" => array("FriendlyName" => "Allow Secret Review", "Type" => "yesno", "Description" => "Tick to allow users to re-view their secret and emergency code at any time"),
            "discrepancy" => array("FriendlyName" => "Discrepancy", "Type" => "text", "Size" => "4", "Description" => "Allowed code discrepancy, in 30-second intervals (recommended at least 1)", "Default" => "2")
    ));
    return $configarray;
}

function oath_activate() {

    // Delete table mod_oath_client if exist
    Capsule::schema()->dropIfExists('mod_oath_client');
    try {
        Capsule::schema()->create(
                'mod_oath_client', function ($table) {
            $table->integer('userid')->primary();
            $table->string('secret');
            $table->string('emergencycode');
        }
        );
    } catch (\Exception $e) {
        echo "Unable to create mod_oath_client: {$e->getMessage()}";
    }

    // Delete table mod_oath_admin if exist
    Capsule::schema()->dropIfExists('mod_oath_admin');
    try {
        Capsule::schema()->create(
                'mod_oath_admin', function ($table) {
            $table->integer('adminid')->primary();
            $table->string('secret');
        }
        );
    } catch (\Exception $e) {
        echo "Unable to create mod_oath_admin: {$e->getMessage()}";
    }

    return array('status' => 'success', 'description' => 'OATH Two-Factor Authentication has been enabled.');
}

function oath_deactivate() {
    Capsule::schema()->dropIfExists('mod_oath_client');
    Capsule::schema()->dropIfExists('mod_oath_admin');

    return array('status' => 'success', 'description' => 'OATH Two-Factor Authentication has been disabled and all tokens have been deleted.');
}

function oath_upgrade($vars) {
    try {
        Capsule::schema()->create(
                'mod_oath_admin', function ($table) {
            $table->integer('adminid')->primary();
            $table->string('secret');
        }
        );
    } catch (\Exception $e) {
        echo "Unable to create mod_oath_admin: {$e->getMessage()}";
    }
}

function oath_clientarea($vars) {
    define("FORCESSL", true);

    if ($_GET['qr']) {
        require_once(__DIR__ . '/phpqrcode/qrlib.php');

        // Get client email
        $sqlquery = Client::where('id', $_SESSION['uid'])->get();
        foreach ($sqlquery as $data) {
            $user = $data->email;
            unset($data);
            break;
        }

        global $CONFIG;
        $company2 = $CONFIG['CompanyName'];
        QRcode::png('otpauth://totp/' . $user . '?issuer=' . urlencode($company) . '&secret=' . $_GET['secret']);
        exit(0);
    }

    require_once(__DIR__ . '/GoogleAuthenticator.php');

    $gauth = new PHPGangsta_GoogleAuthenticator();

    $userid = $_SESSION['uid'];
    if ($_SESSION['twofactorverify']) {
        $userid = $_SESSION['twofactorverify'];
    }

    // Get Client Secret & Emergency Code
    $sqlquery = Capsule::table('mod_oath_client')
                    ->where('userid', $userid)->get();
    foreach ($sqlquery as $data) {
        $secret = $data->secret;
        $emergencycode = $data->emergencycode;
        unset($data);
        break;
    }

    $ret['vars']['OATH'] = $vars['_lang'];

    if ($_SESSION['twofactorverify'] && !$_POST['action'] == 'login') {
        $ret['pagetitle'] = Lang::trans('twofactorauth');
        $ret['breadcrumb'] = array('index.php?m=oath' => Lang::trans('twofactorauth'));
        $ret['templatefile'] = 'clientareaoath';
        $ret['requirelogin'] = false;
        $ret['vars']['secret'] = $secret;
        $ret['vars']['modulelink'] = $vars['modulelink'];
        $ret['vars']['login'] = 1;
        return $ret;
    } elseif ($_SESSION['twofactorverify'] && $_POST['action'] == 'login') {
        if ($_POST['emergencycode']) {
            $realcode = str_replace(' ', '', strtolower($emergencycode));
            $theircode = str_replace(' ', '', strtolower($_POST['emergencycode']));

            // If the user enter correct emergency code

            if ($theircode == $realcode) {

                // Don't disable the two factor. 
                // Give them new code!
                // Capsule::table('mod_oath_client')->where('userid', $userid)->delete();

                $emergencycode = SecretCodeOATH::emergencyCode();

                Capsule::table('mod_oath_client')
                        ->where('userid', $userid)
                        ->update(['emergencycode' => $emergencycode]);

                $_SESSION['uid'] = $_SESSION['twofactorverify'];
                $_SESSION['upw'] = $_SESSION['twofactorverifypw'];
                unset($_SESSION['twofactorverify']);
                unset($_SESSION['twofactorverifypw']);

                global $CONFIG;
                $ret['vars']['active'] = 1;
                $ret['pagetitle'] = Lang::trans('twofactorauth');
                $ret['breadcrumb'] = array('index.php?m=oath' => Lang::trans('twofactorauth'));
                $ret['templatefile'] = 'clientareaoath';
                $ret['requirelogin'] = false;
                $ret['vars']['secret'] = $secret;
                $ret['vars']['modulelink'] = $CONFIG['SystemURL'] . '/clientarea.php';
                $ret['vars']['enable_clients'] = $vars['enable_clients'];
                $ret['vars']['backupCode'] = 1;
                $ret['vars']['newBackupCode'] = $emergencycode;

                return $ret;
            } else {
                $ret['pagetitle'] = Lang::trans('twofactorauth');
                $ret['breadcrumb'] = array('index.php?m=oath' => Lang::trans('twofactorauth'));
                $ret['templatefile'] = 'clientareaoath';
                $ret['requirelogin'] = false;
                $ret['vars']['secret'] = $secret;
                $ret['vars']['modulelink'] = $vars['modulelink'];
                $ret['vars']['login'] = 1;
                $ret['vars']['incorrect'] = 1;
                return $ret;
            }
        }

        // Get discrepancy value
        $sqlquery = Capsule::table('tbladdonmodules')
                ->where('module', 'oath')
                ->where('setting', 'discrepancy')
                ->get();
        foreach ($sqlquery as $data) {
            $discrepancy = $data->value;
            unset($data);
            break;
        }

        if (!$gauth->verifyCode($secret, $_POST['code'], $discrepancy)) {
            $ret['pagetitle'] = Lang::trans('twofactorauth');
            $ret['breadcrumb'] = array('index.php?m=oath' => Lang::trans('twofactorauth'));
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

    if ($secret) {
        $ret['vars']['active'] = 1;
    }

    if ($_POST['action'] == 'enable') {
        $secret = $gauth->createSecret();
        $ret['vars']['verify'] = 1;
    } elseif ($_POST['action'] == 'verify') {
        $secret = $_POST['secret'];
        $code = $_POST['code'];
        if (!$gauth->verifyCode($secret, $code, $vars['discrepancy'])) {
            $ret['vars']['incorrect'] = 1;
            $ret['vars']['verify'] = 1;
        } else {

            $emergencycode = SecretCodeOATH::emergencyCode();

            // Save generated secret and emergency code to mod_oath_client table
            Capsule::table('mod_oath_client')->insert([
                ['userid' => $_SESSION['uid'], 'secret' => $secret, 'emergencycode' => $emergencycode]
            ]);
            $ret['vars']['active'] = 1;
            $ret['vars']['firstactivation'] = 1;
        }
    } elseif ($_POST['action'] == 'disable') {
        // Delete data for client
        Capsule::table('mod_oath_client')->where('userid', $_SESSION['uid'])->delete();
        unset($ret['vars']['active']);
        unset($secret);
        unset($emergencycode);
    }

    $ret['pagetitle'] = Lang::trans('twofactorauth');
    $ret['breadcrumb'] = array('index.php?m=oath' => Lang::trans('twofactorauth'));
    $ret['templatefile'] = './clientareaoath';
    $ret['requirelogin'] = true;
    $ret['vars']['secret'] = $secret;
    $ret['vars']['emergencycode'] = $emergencycode;
    $ret['vars']['enable_clients'] = $vars['enable_clients'];
    $ret['vars']['allow_secret_review'] = $vars['allow_secret_review'];
    $ret['vars']['modulelink'] = $vars['modulelink'];

    return $ret;
}

function oath_output($vars) {

    if ($_GET['qr']) {

        require_once(__DIR__ . '/phpqrcode/qrlib.php');

        // Get client email
        $sqlquery = Client::where('id', $_SESSION['uid'])->get();
        foreach ($sqlquery as $data) {
            $user = $data->email;
            unset($data);
            break;
        }

        global $CONFIG;
        $company2 = $CONFIG['CompanyName'];
        QRcode::png('otpauth://totp/' . $user . '?issuer=' . urlencode($company) . '&secret=' . $_GET['secret']);
        exit(0);
    }

    echo '<div style="text-align: center;">';

    // Get Admin Secret
    $sqlquery = Capsule::table('mod_oath_admin')
                    ->where('adminid', $_SESSION['adminid'])->get();
    foreach ($sqlquery as $data) {
        $secret = $data->secret;
        unset($data);
        break;
    }

    require_once(__DIR__ . '/GoogleAuthenticator.php');
    $gauth = new PHPGangsta_GoogleAuthenticator();

    if ($vars['enable_admins'] == 'No') {
        echo 'Two-factor authentication is currently disabled for administrators.';
    } elseif (!$secret && $_POST['enable']) {
        if ($_POST['secret']) {
            if ($gauth->verifyCode($_POST['secret'], $_POST['code'], $vars['discrepancy'])) {
                // Save generated secret and emergency code to mod_oath_client table
                Capsule::table('mod_oath_admin')->insert([
                    ['adminid' => $_SESSION['adminid'], 'secret' => $_POST['secret']]
                ]);

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
    } elseif (!$secret && $vars['enable_admins'] == 'Required') {
        echo '<b>You must enable two-factor authentication to proceed.</b><br /><br />';
        echo '<form method="post" action="' . $vars['modulelink'] . '"><input type="submit" name="enable" value="Enable Two-Factor Authentication" class="btn btn-primary" /></form>';
    } elseif ($secret && $_SESSION['twofactoradmin'] != $_SESSION['adminid']) {
        if ($_POST['code']) {
            if ($gauth->verifyCode($secret, $_POST['code'], $vars['discrepancy'])) {
                $_SESSION['twofactoradmin'] = $_SESSION['adminid'];
                $redirectURI = (!empty($_SESSION['original_request_uri'])) ?
                        htmlspecialchars_decode($_SESSION['original_request_uri']) : 'index.php';

                header('Location: ' . $redirectURI);
                unset($_SESSION['original_request_uri']);
                exit(0);
            } else {
                echo '<p style="color: red;"><b>Your code was incorrect.</b></p>';
            }
        }
        echo '<p>Please enter the code generated by your mobile authenticator app.</p>';
        echo '<form method="post" action="' . $vars['modulelink'] . '">';
        echo '<input type="text" name="code" placeholder="Enter your code" autocomplete="off" /><br /><br />';
        echo '<input type="submit" name="enable" value="Validate Login" class="btn btn-primary" />';
        echo '</form>';
    } elseif ($secret && $_POST['disable']) {

        Capsule::table('mod_oath_admin')->where('adminid', $_SESSION['adminid'])->delete();

        unset($_SESSION['twofactoradmin']);
        header('Location: ' . $vars['modulelink']);
        exit(0);
    } elseif ($secret) {
        echo '<p>You have two-factor authentication enabled.</p>';
        echo '<form method="post" action="' . $vars['modulelink'] . '"><input type="submit" name="disable" value="Disable Two-Factor Authentication" class="btn btn-danger" /></form>';
    } else {
        echo '<p>You do not have two-factor authentication enabled.</p>';
        echo '<form method="post" action="' . $vars['modulelink'] . '"><input type="submit" name="enable" value="Enable Two-Factor Authentication" class="btn btn-primary" /></form>';
    }

    echo '</div>';
}

if (!class_exists('SecretCodeOATH')) {

    class SecretCodeOATH {

        public static function emergencyCode() { // return type declaration not available on php v5.6, whmcs officially supports 5.6+ until now
            $characters = 'abcdefghijklmnopqrstuvwxyz1234567890';
            $emergencycode = '';
            for ($i = 0; $i < 16; $i++) {
                if ($i % 4 == 0 && $i != 0 && $i != 16) {
                    $emergencycode .= ' ';
                }
                $emergencycode .= substr($characters, rand(0, strlen($characters) - 1), 1);
            }
            return $emergencycode;
        }

    }

}
