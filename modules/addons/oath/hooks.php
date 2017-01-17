<?php

use WHMCS\View\Menu\Item as MenuItem;
use WHMCS\User\Admin;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('ClientAreaPrimarySidebar', 1, function (MenuItem $menu) {
    if (!is_null($menu->getChild('My Account'))) {
        $menu->getChild('My Account')->addChild('OATH', array(
            'label' => Lang::trans('twofactorauth'),
            'uri' => 'index.php?m=oath',
            'order' => '51',
        ));
    }
});

add_hook('ClientAreaSecondaryNavbar', 1, function (MenuItem $menu) {
    if (!is_null($menu->getChild('Account')) && !is_null(Menu::context('client'))) {
        $menu->getChild('Account')->addChild('OATH', array(
            'label' => Lang::trans('twofactorauth'),
            'uri' => 'index.php?m=oath',
            'order' => '51',
        ));
    }
});

function oath_hook_client_login($vars) {
    if ($_SESSION['adminid']) {
        return;
    }

    $userid = $vars['userid'];

    // Check wether the secret is available or not
    $sqlquery = Capsule::table('mod_oath_client')
                    ->where('userid', $vars['userid'])->get();
    foreach ($sqlquery as $data) {
        $secret = $data->secret;
        unset($data);
        break;
    }

    if (!$secret) {
        if (isset($_SESSION['twofactorverify'])) {
            unset($_SESSION['twofactorverify']);
        }

        return;
    }

    // Check wether the secret is available or not
    $sqlquery = Capsule::table('tbladdonmodules')
                    ->where('module', 'oath')
                    ->where('setting', 'enable_clients')->get();
    foreach ($sqlquery as $data) {
        $valueenableclients = $data->value;
        unset($data);
        break;
    }

    if (!$valueenableclients) {
        if (isset($_SESSION['twofactorverify'])) {
            unset($_SESSION['twofactorverify']);
        }

        return;
    }

    $_SESSION['twofactorverify'] = $userid;
    $_SESSION['twofactorverifypw'] = $_SESSION['upw'];
    unset($_SESSION['uid']);
    unset($_SESSION['upw']);

    # fix security vulnerabilty (issue #9): The automatic login will continue to login the user even when it got removed by our client login hook.
    # we need to ensure there will be no cookies passed to browser with autologin information and thus the cookie WHMCSUser will be overwritten
    # befor it gets send to the browser.
    $headers_cookie_safe = array();
    $all_headers = headers_list();
    foreach ($all_headers as $header) {
        if (preg_match('/^Set-Cookie: /', $header)) {
            if (!preg_match('/^Set-Cookie: WHMCSUser=/', $header)) {
                $headers_cookie_safe[] = $header;
            }
        }
    }
    header_remove('Set-Cookie');
    //for all safe headers print them out as they were
    //actually multiple values here would overwrite each other the way header works... http://stackoverflow.com/q/34664208
    foreach ($headers_cookie_safe as $header) {
        header($header);
    }
    //invoke deletion of any existing autologin cookies
    setcookie('WHMCSUser', 'deleted', time() - 3600, '/', "", false, true);

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
    if (($script[count($script) - 1] == 'addonmodules.php' && $_GET['module'] == 'oath') || $_SESSION['twofactoradmin'] == $_SESSION['adminid']) {
        return;
    }

    // Get Admin Secret
    try {
        $sqlquery = Capsule::table('mod_oath_admin')
                        ->where('adminid', $_SESSION['adminid'])->get();
        foreach ($sqlquery as $data) {
            $secret = $data->secret;
            unset($data);
            break;
        }
    } catch (\Exception $e) {
        $secret = false;
    }


    // Get Enable Admins
    try {
        $sqlquery = Capsule::table('tbladdonmodules')
                        ->where('module', 'oath')
                        ->where('setting', 'enable_admins')->get();
        foreach ($sqlquery as $data) {
            $enable_admins = $data->value;
            unset($data);
            break;
        }
    } catch (\Exception $e) {
        $enable_admins = 'None';
    }

    // Get Access Value
    try {
        $sqlquery = Capsule::table('tbladdonmodules')
                        ->where('module', 'oath')
                        ->where('setting', 'access')->get();
        foreach ($sqlquery as $data) {
            $accessvalue = $data->value;
            unset($data);
            break;
        }

        $access = explode(',', $accessvalue);
    } catch (\Exception $e) {
        $access = 'None';
    }

    // Get Role
    try {
        $sqlquery = Admin::where('id', $_SESSION['adminid'])->get();
        foreach ($sqlquery as $data) {
            $role = $data->roleid;
            unset($data);
            break;
        }
    } catch (\Exception $e) {
        $role = '';
    }

    if ((!$secret && $enable_admins != 'Required') || $enable_admins == 'No' || !in_array($role, $access)) {
        return;
    }

    $_SESSION['original_request_uri'] = $_SERVER['REQUEST_URI'];

    header('Location: addonmodules.php?module=oath');
    session_write_close();
    exit(0);
}

add_hook("AdminAreaPage", 0, "oath_admin_page");

function oath_hook_admin_client_profile_tab_fields($vars) {

    // Get Client Secret
    $sqlquery = Capsule::table('mod_oath_client')
                    ->where('userid', $vars['userid'])->get();
    foreach ($sqlquery as $data) {
        $secret = $data->secret;
        unset($data);
        break;
    }

    if ($secret) {
        return array('OATH Addon' => '<label><input type="checkbox" name="disable_twofactor" value="1" /> Tick and save to disable two-factor authentication for this client</label>');
    } else {
        return array();
    }
}

add_hook("AdminClientProfileTabFields", 0, "oath_hook_admin_client_profile_tab_fields");

function oath_hook_admin_client_profile_tab_fields_save($vars) {
    if (isset($vars['disable_twofactor'])) {
        Capsule::table('mod_oath_client')->where('userid', $vars['userid'])->delete();
    }
}

add_hook("AdminClientProfileTabFieldsSave", 0, "oath_hook_admin_client_profile_tab_fields_save");
