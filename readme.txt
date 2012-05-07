=== 6Scan Security ===
Contributors: 6Scan
Version: 2.1.1
Tags: security,secure,6scan,protection,anti-hack,hack,attack,scan,sql injection,xss,file inclusion,exploit,automatic,bodyguard,patrol
Requires at least: 3.0.0
Tested up to: 3.3.2
Stable tag: trunk

6Scan Security goes beyond the rule-based protection of other Wordpress security plugins to provide the most comprehensive protection against hackers.

== Description ==

6Scan Security is the most comprehensive *automatic* protection your Wordpress site can get against hackers.  Our security scanner goes beyond the rule-based protection of other Wordpress security plugins, employing active penetration testing algorithms to find security vulnerabilities.  These are then  automatically fixed before hackers can exploit them. Our team of website security experts ensures your protection is always up-to-date and airtight.

Our automatic security scanner finds and protects against:
* SQL Injection
* Cross-Site Scripting (XSS)
* CSRF
* Directory traversal
* Remote file inclusion
* Several DoS conditions
* And many more, including all of the OWASP Top Ten security vulnerabilities.

6Scan Security includes an agent that runs on your server to rapidly fix all security vulnerabilities found by the scanner.  Our team of security experts constantly finds new vulnerabilities and attack strategies, and integrates them into the scanner so you are immediately protected.

6Scan Security also includes a Web Application Firewall (WAF) that uses pattern matching to block out even more security threats.  Our WAF is completely configurable so you can choose the level of security you desire for your site.

Once 6Scan Security is installed, no further action is required to keep your site protected.  6Scan Security is also specifically engineered not to affect your site's performance or interfere with your site's legitimate users.  Our dashboard is specifically designed to convey your security status in a clear and simple manner, so that even non-experts can understand the situation.

It is very important to take note of the difference between various Wordpress security plugins.  Most of these are based on a ruleset which recognizes and blocks certain attack signatures. This approach is effective for protecting against some common SQL injection attacks, but fails to detect or prevent hackers from exploiting flawed logic.  For example, it could not protect against an authorization bug in a file upload plugin, potentially allowing unauthorized users to upload malware and viruses to your site.  6Scan's security response team constantly updates your blog's protection to deal with the latest threats found on all major exploit databases on the Internet.

Let 6Scan handle the security of your Wordpress site, so you can worry about what really matters to you - your content.  If you have any questions, please feel free to contact us using our [support area](http://6scan.com/support).

== Installation ==

In order to install 6Scan Security, please follow these steps:

1. Upload the ZIP file containing the plugin to your Wordpress site, using the "Add New"->"Upload" option on the Plugins screen (or automatically find us by searching for "6Scan").
1. When the plugin has been installed, click to activate the plugin.
1. Once activated, 6Scan Security will display a message informing you how to activate protection.

Please note that 6Scan Security requires read/write access to your .htaccess file, which allows us to intercept and analyze/block suspicious requests before they reach any vulnerable script.  If you do not have this access, installation of the plugin may fail.

To allow 6Scan Security to constantly update its signatures and keep you protected, we require the fopen or curl library to be installed and enabled.  If you have a file-monitoring extension, you may see frequent changes in the 6Scan signature files; this is normal and is an indication that your protection is working properly and up to date.

Once you register with 6Scan, we will automatically send you an email notification when our scanner detects a threat or another problem with your website. To change the notification options (or completely unsubscribe from these messages) please visit the 6Scan Settings panel.  You may also elect to be notified of new threats or problems by SMS to any destination worldwide.

Once installed, 6Scan Security will add three items to your Wordpress menu: Dashboard, Settings and Support.

The dashboard shows you the list of security vulnerabilities detected by our scanner. Every security issue can be clicked for more information. There is a textual description of each vulnerability and a link to a public advisory (when available) on a Bugtraq site.

The settings page allows you to configure the following WAF security options:
* SQL Injection - Detects and blocks database hacking attempts
* Cross Site Scripting (XSS) - Detects and blocks identity theft attacks, which are based on stolen cookies
* Disable Non-standard request types - Disables any non-GET/POST requests to your site
* Remote File Inclusion protection - Disables requests that include a path to an external PHP file, which could be a major security risk
* CSRF protection for POST requests - Detects and blocks POST requests originating from foreign domains

The support area allows you to ask questions, report bugs or consult on security-related matters.  If you need to add confidential details to your support request (such as passwords), you may email us at support@6scan.com instead of posting on our public support forum.  All tickets received at support@6scan.com are handled with discretion and your information will only be shared with employees on a need-to-know basis to help solve your problem.

In order to uninstall the 6Scan Security plugin:

1. Open the Plugins menu item on your Wordpress admin area
1. Next to the 6Scan Security plugin, click Deactivate, then Delete
1. When the plugin is deleted, any active 6Scan subscription will be automatically deactivated, along with any email or SMS notifications you have configured.

Please note that if you uninstall 6Scan Security, any fixed security vulnerabilities will lose their fixes.  If 6Scan Security has fixed vulnerabilities on your site, you must keep the plugin installed to keep these fixes active.

If you encounter any problems during installation, please visit our [support area](http://6scan.com/support) or email us at support@6scan.com.

== Frequently Asked Questions ==

= Does 6Scan Security work with other security plugins? =

Yes, 6Scan Security has been tested with many other security plugins and does not conflict with them. If you suspect any compatibility problem, please contact us via our [support area](http://6scan.com/support) or email support@6scan.com.

= Will 6Scan Security work with my hosting package? =

We work with all standard hosting packages that support Wordpress.  We have specifically tested 6Scan Security with many popular hosting companies, including GoDaddy, Hostgator, Dreamhost, Site5, 1&1 and others.  Of course, more advanced configurations such as VPS/VDS are also supported, as long as your file permissions are configured correctly (see the Installation section for more details).

= I get the error "Can't create signature file" or "Can't update .htaccess file" when installing the plugin =

6Scan requires write permissions to your web root directory and .htaccess file in order to install the automatic fix signatures.  For more information on how to enable write access, please see http://codex.wordpress.org/Changing_File_Permissions .

= What web servers does 6Scan support? =

6Scan Security currently works with any server that has .htaccess and mod_rewrite support, such as Apache and IIS.  This is required, so that 6Scan could intercept and analyze requests before they reach server and potentially vulnerable scripts.  Support for Nginx is planned in the future.

= Does 6Scan affect my site's performance? =

We pay specific attention to our plugin's performance because it should work seamlessly, even under heavy load.  Because our initial flagging rules are optimized to be lightning fast, and only suspicious requests undergo additional checks, your site's legigimate users will not be affected.

= Does 6Scan protect against TimThumb vulnerability? =
TimThumb is an RFI vulnerability, which is based on including a malicious PHP script as a path to your TimbThumb gallery.  It is easily filtered out by 6Scan Security's WAF feature.  One of the advantages of the WAF rules, is that they are complete generic, and will block out TimThumb wherever it is on your site, as well as automatically blocking similar vulnerabilities in the future.

= What is the 6Scan WAF feature? =

WAF is an acronym for Web Application Firewall.  It is a set of rules which are designed to flag suspicious requests and then act accordingly (for example, by blocking the request before it reaches its target).  Our WAF is written to match a set of widespread attacks patterns, while minimizing its impact on user experience.

= How often does 6Scan Security scan my site for newest security threats? =

On average, your site will be scanned once every few hours, making sure your site is scanned several times every day for the latest security issues.  However, when a new vulnerability is discovered and published, 6Scan Security will scan affected sites with a higher priority to make sure the vulnerability is fixed right away.

= How quickly does 6Scan find and protect against new exploits? =

We monitor all the large exploit databases 24/7, which allows us to respond immediately to any publicly published exploit.  Our security research team also analyzes Wordpress and plugin code to find vulnerabilities even before they are known to the general public.  Finally, we use honeypots - special traps designed to lure hackers in - to gather information about new techniques hackers try, and those techniques are immediately found and fixed on your site.

When you have 6Scan installed, you do not need to worry about a newly found exploit for Wordpress or any of your installed plugins - we follow security newsfeeds for you and release a fix before hackers find out about and exploit new vulnerabilities.

= Why should I choose 6Scan Security and not any other available security plugin? =

First, because other plugins do not protect against all the security vulnerabilities we can.  Most other plugins are based on a ruleset which recognizes and blocks certain attack signatures. This approach is effective for protecting against some common SQL injection attacks, but fails to detect or prevent hackers from exploiting flawed logic.  For example, it could not protect against an authorization bug in a file upload plugin, potentially allowing unauthorized users to upload malware and viruses to your site.  6Scan's security response team constantly updates your blog's protection to deal with the latest threats found on all major exploit databases on the Internet.

Second, because 6Scan Security is easy-to-use, so that anyone - even without a technical background - can understand and use our plugin to fix security problems.  Our plugin is easy to activate, very user-friendly but still extremely efficient.

= 6Scan scanned my site and no vulnerabilities were found. What does this mean? =

Good news!  This means that there are no immediate security problems with your site.  However, you should still keep 6Scan Security installed so it can continue to monitor your site.  It is quite possible that one of your site’s components has a security vulnerability which hasn’t yet been discovered.  Once it is discovered (either by our security research team or by another party), 6Scan Security will notify you and allow you to patch it before hackers use it to compromise your site.

= I have a feature request! =

We are always open to feature requests, especially for security-related features. Please contact us with a detailed description of your request at our [support area](http://6scan.com/support), and we will consider including it in our plugin.


== Screenshots ==

1. Your dashboard shows the security vulnerabilities you are being protected against.
2. Security settings of 6Scan WAF together with SMS notification configured.

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

= 1.0.5 =
* 6Scan Security Plugin has an easier to use activation feature
* Support submenu added
* Htaccess rules have been changed to tighten the security even more
* Fixed few bugs, which could occur under Windows server environment

= 1.0.6 =
* Now supports curl transport, if fopen() fails
* Improved communication with 6Scan server

= 1.0.7 =
* Installation process improved.
* Added settings menu
* Added support for more Patrol servers

= 1.0.8 =
* Security tightened even more
* Small bugfixes

= 1.0.9 =
* Adjusted signature update protocol for new API 

= 1.0.10 =
* Site verification process improved

= 2.0.1 =
* Smoother install process
* Displays vulnerability count
* Added patch to work with very slow servers

= 2.1.1 =
* Added WAF security settings
* Added manual fixes instructions
* New dashboard design

== Upgrade Notice ==

* Support menu, if user encounters a problem
* Security tightened up even more
* Easier to install