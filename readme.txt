=== Private WP suite ===
Contributors: fpoller
Tags: private, protect, feed, uploads, content
Requires at least: 2.9
Tested up to: 3.1
Stable tag: 0.4.1

Adds option in the admin panel for making your blog (including rss feeds and uploaded files) private.

== Description ==

Gives the following options for making the Wordpress installation more private:

* Protect content from being viewed to users who hasn't logged in
* Disable all feeds
* Only serve uploaded files to logged in users
* IP address based exceptions for the above options

== Installation ==

1. Upload private-wp-suite.php to the /wp-content/plugins/ directory
1. Activate the plugin through the Plugins menu in Wordpress
1. Configure the plugin through the admin page under Settings

== Screenshots ==

1. Screenshot of the admin page

== Changelog ==

= 0.4.1 =
* Changed bloginfo(url) to bloginfo(wpurl), for correct handling of sites installed in subdir

= 0.4 =
* Tested with 3.1
* Fixed embarrassing 404 header (http://wordpress.org/support/topic/plugin-private-wp-suite-pdf-files-dont-work-solution-included)

= 0.3 =
* Removed debug functions
* Tested with 3.0.1

= 0.2 =
* Added deactivation function

= 0.1 =
* Initial release
