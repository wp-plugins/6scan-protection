=== 6Scan Security ===
Contributors: 6Scan
Version: 1.0.4
Tags: security,secure,6scan,protection,anti-hack,hack,attack,scan,sql injection,xss,file inclusion,exploit,automatic,bodyguard,patrol
Requires at least: 3.0.0
Tested up to: 3.3
Stable tag: trunk

6Scan Security protects your website against hackers destroying, stealing or manipulating your data using constantly updated attack signatures.

== Description ==

6Scan Security is the world's first plugin to scan your Wordpress site for known and unknown security vulnerabilities and *automatically* fix them, before hackers use them to damage your site and online reputation.

* Patrol: 6Scan's Patrol scanner, written by a team of security experts, imitates the actions of a hacker trying to hack into your website.  Each page, form and script on your site is scoured for weak points that could potentially become security holes.  Patrol finds and protects against:
 * SQL Injection
 * Cross-Site Scripting (XSS)
 * Directory traversals
 * Remote file inclusion
 * And many more, including all of the OWASP Top Ten security vulnerabilities.
* Bodyguard: 6Scan's Bodyguard acts on your server to rapidly fix all security vulnerabilities found by Patrol.
* Constantly updated: Our team of security experts constantly finds new vulnerabilities and attack strategies, and integrates them into Patrol so you are immediately protected.
* Install and forget: once 6Scan Security is installed, no further action is required to keep your site protected.
* Invisibility: 6Scan Security is specifically engineered not to affect your site's performance or interfere with your site's legitimate users.

If you have any questions, please feel free to contact us using our [support area](http://6scan.com/support).

== Installation ==

1. Upload the ZIP file containing the plugin to your Wordpress site, using the "Add New"->"Upload" option on the Plugins screen.
1. When the plugin has been installed, click to activate the plugin.
1. Once activated, 6Scan Security will display a message informing you how to activate protection.

If you encounter any problems during installation, please visit our [support area](http://6scan.com/support) or email us at support@6scan.com.

== Frequently Asked Questions ==

= Does 6Scan Security work with other security plugins? =

Yes, 6Scan Security has been tested with many other security plugins and does not conflict with them.

= Will 6Scan Security work with my hosting package? =

We work with all standard hosting packages that support Wordpress.  We have specifically tested 6Scan Security with many popular hosting companies, including GoDaddy, Hostgator, Dreamhost, Site5, 1&1 and others.

= I get the error "Can't create signature file" or "Can't update .htaccess file" when installing the plugin =

6Scan requires write permissions to your web root directory and .htaccess file in order to install the automatic fix signatures.  For more information on how to enable write access, please see http://codex.wordpress.org/Changing_File_Permissions .

= I am seeing an error that is similar to "Could not open handle for fopen..." =

Please read an extensive explanation on this matter [here](http://6scan.freshdesk.com/solution/articles/2681-i-am-seeing-an-error-that-is-similar-to-could-not-open-handle-for-fopen-)

= What webservers does 6Scan support? =

6Scan Security currently works with any server that has .htaccess and mod_rewrite support such as Apache and IIS, but we plan to support Nginx in our later releases.

== Screenshots ==

1. Your dashboard shows the vulnerabilities you are being protected against.
2. An initial dashboard while the software is being installed.

== Changelog ==

= 1.0.1 =
* Initial alpha release.

= 1.0.2 =
* Error reporting form added.
* If install fails, user now sees better error description.
* Fixed a bug that could occur when installing the plugin on servers with an empty or outdated root CA list.

= 1.0.3 =
* Bugfix, regarding access to 6Scan's SSL server.

= 1.0.4 =
* Gate script now works correctly with servers, that have DOCUMENT_ROOT different from the real document root (like 000webhost).
* More sanity checks before installing (checking for openssl_* functions, required php.ini flags, and more).
* Added helpful links to errors that might occur while installing.
* Now verification file resides on server as long as 6Scan Security is installed.

== Upgrade Notice ==

* Error reporting improved.
* Does not fail on SSL errors anymore.
