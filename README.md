# WHMCS OATH Addon (WHMCS v6 & v7)

This WHMCS addon module provides OATH-based two-factor authentication. You'd normally use this with a mobile app, like Google Authenticator (Free, [iOS](https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8) / [Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en)).

If you are logged in as an admin, client area two-factor authentication will be bypassed.

If user input the Emergency Code, the client will get new Emergency Code with same secret. _Two Factor Authentication will not disabled because logging in using Emergency Code_

## Installation

To install, simply download the latest [release](https://github.com/openfactory-ch/whmcs-oath-addon/releases), unzip it, and upload the `modules` folder to your WHMCS root directory. The Links are automatically created with the new WHMCS v6/v7 Client Area Menu.

Once uploaded, go to Setup > Addon Modules in your admin area and click Activate for the "OATH Two Factor Authentication" entry. Once activated, click Configure to customize your settings.

When enabling two-factor authentication for administrators, the "Yes" option will make it optional, while "Required" will require all admins to enable two-factor authentication on their next login. You must tick off the admin role permission boxes next to the roles that you want to have access to two-factor authentication.

## Notes for Upgrade from WHMCS v5 to WHMCS v6+

The former developer(s) (see Credits section) haven't updated their source for about a year. Since WHMCS v6 release their module is not compatible anymore.

Since the codebase and database is exactly the same and only minimal stuff have changed, it's safe to just repeat the Installation section and overwrite the files.

## Issues

Latest tested Release: WHMCS v7.1.1.

If you discover any issues or bugs, please report them on the [issue tracker](https://github.com/openfactory-ch/whmcs-oath-addon/issues).

## Credits

This addon was made possible by:

* Current maintainer: [Openfactory GmbH](http://www.openfactory.ch)
* Former developer(s): [Doctor_McKay/whmcs-oath-addon](https://bitbucket.org/Doctor_McKay/whmcs-oath-addon/) / [donate](https://www.doctormckay.com/donate.php)
* Library: [PHPGangsta's GoogleAuthenticator class](https://github.com/PHPGangsta/GoogleAuthenticator)
* Library: [PHP QR Code](http://phpqrcode.sourceforge.net/)

## License

This module is licensed under GPLv3. See [GPLv3.txt](GPLv3.txt) for complete license terms.
