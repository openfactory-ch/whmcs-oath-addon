# WHMCS OATH Addon

This WHMCS addon module provides OATH-based two-factor authentication. You'd normally use this with a mobile app, like Google Authenticator (Free, [iOS](https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8) / [Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en)).

At the present moment, only client area two-factor authentication is supported, but admin area two-factor authentication is planned to be finished soon.

If you are logged in as an admin, client area two-factor authentication will be bypassed.

## Installation

To install, simply download the latest [release](https://bitbucket.org/Doctor_McKay/whmcs-oath-addon/downloads), unzip it, and upload the `modules` and `templates` folders to your WHMCS root directory. If you use a WHMCS template besides "default", you'll need to add a link to "index.php?m=oath" somewhere within your WHMCS templates to allow clients to configure their two-factor authentication settings.

Once uploaded, go to Setup > Addon Modules in your admin area and click Activate for the "OATH Two Factor Authentication" entry. Once activated, click Configure to customize your settings.

## Issues

If you discover any issues or bugs, please report them on the [issue tracker](https://bitbucket.org/Doctor_McKay/whmcs-oath-addon/issues?status=new&status=open).

## Support

Donations are gladly welcomed [here](https://www.doctormckay.com/donate.php).

## Credits

This addon was made possible by:

* [PHPGangsta's GoogleAuthenticator class](https://github.com/PHPGangsta/GoogleAuthenticator)
* [PHP QR Code](http://phpqrcode.sourceforge.net/)

## License

This module is licensed under GPLv3. See GPLv3.txt for complete license terms.