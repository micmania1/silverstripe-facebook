Silverstripe Facebook
============================

This module is to allow integration between Facebook and Silverstripe. Its based (and ported from) the [sstwitter module](http://www.github.com/micmania1/sstwitter).

Features
--------
* CMS interface to integrate Silverstripe with a Facebook application & connect an account to the website.
* Connect/Disconnect Member's to Facebook accounts.
* Facebook signup.
* Enable/Disable Facebook login & signup through the CMS.
* Developer Access to Facebook API through the official [Facebook PHP SDK](https://github.com/facebook/facebook-php-sdk).

Usage
--------

**$FacebookConnectURL** (FacebookApp::connect_url())
This displays a link where a logged in user will be taken through the Facebook authentication process to connect their Facebook account.

**$FacebookDisconnectURL** (FacebookApp::disconnect_url())
This will disassociate the Facebook account from the Member.

**$FacebookLoginURL** (FacebookApp::login_url())
This will return a url whereby the user can login to their Silverstripe account through Facebook. It will sign the user up if a Facebook account connect be found and Signup is enabled.

    <a href="$FacebookConnectURL">Connect</a><br />
    <a href="$FacebookDisconnectURL">Disconnect</a><br />
    <% if FacebookLoginURL %>
        <a href="$FacebookLoginURL">Login</a>
    <% else %>
        Facebook Login is disabled.
    <% end_if %>


Extending
---------
Silverstripe Facebook allows you to use the full power of Facebook's PHP SDK. You can access this by referencing FacebookApp->getFacebook().
