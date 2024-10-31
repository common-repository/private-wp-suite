<?php
/*	Copyright 2010 Fredrik Poller. All rights reserved.

	Redistribution and use in source and binary forms, with or without modification, are
	permitted provided that the following conditions are met:

	  1. Redistributions of source code must retain the above copyright notice, this list of
	  conditions and the following disclaimer.

	  2. Redistributions in binary form must reproduce the above copyright notice, this list
	  of conditions and the following disclaimer in the documentation and/or other materials
	  provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY FREDRIK POLLER ``AS IS'' AND ANY EXPRESS OR IMPLIED
	WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
	FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> OR
	CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
	CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
	SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
	ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those of the
	authors and should not be interpreted as representing official policies, either expressed
	or implied, of Fredrik Poller.
*/

/*
	Plugin Name: Private WP suite 
	Plugin URI: http://poller.se/code/wordpress-plugins/
	Description: Adds option in the admin panel for making your blog (including rss feeds and uploaded files) private.
	Version: 0.4.1
	Author: Fredrik Poller
	Author URI: http://poller.se/
	License: Simplified BSD License
*/

	// This function adds a menu item
	function plrpws_admin_menu() {
		add_options_page('Private WP suite', 'Private WP suite', 'manage_options', 'plr-private-wp-suite', 'plrpws_admin_page');
	}

	// Tell wordpress to use our menu item function
	add_action('admin_menu', 'plrpws_admin_menu');

	// This functions is the admin page itself
	function plrpws_admin_page() {
		// Start wrap div
		echo '<div class="wrap">' . "\n"; 

		// Fancy title
		echo '<div id="icon-themes" class="icon32"><br /></div>' . "\n";
		echo '<h2>Private WP suite</h2>' . "\n"; 

		// Get current options
		$plrpws_protect_blog    = get_option('plrpws_protect_blog');
		$plrpws_protect_feeds   = get_option('plrpws_protect_feeds');
		$plrpws_protect_uploads = get_option('plrpws_protect_uploads');
		$plrpws_exceptions      = get_option('plrpws_exceptions');
		$plrpws_wp_path         = parse_url(get_bloginfo('wpurl'), PHP_URL_PATH);
		$plrpws_upload_path     = get_option('upload_path');
		if(!$plrpws_upload_path)
			$plrpws_upload_path = 'wp-content/uploads';

		$plrpws_htaccess_file   = $plrpws_upload_path . '/.htaccess';

		// Stolen from wp-includes/vars.php, used to give correct notices to the user below
		$is_apache = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false);

		// If upload protection is enabled, check that .htaccess file is in place. If it isn't, try to create it. If it can't be created, warn the user.
		if($plrpws_protect_uploads) {
			$plrpws_htaccess_content = "# Start Private WP suite rewrite rules\nRewriteEngine On\nRewriteBase $plrpws_wp_path/$plrpws_upload_path/\nRewriteRule . $plrpws_wp_path/nonexsistent_file_to_trigger_404_error\nOptions -Indexes\n# End Private WP suite rewrite rules\n";
			if(file_exists(ABSPATH . $plrpws_htaccess_file)) {
				// File exists, check for correct content (our Private WP suite comment)
				if(is_readable(ABSPATH . $plrpws_htaccess_file)) {
					if(!preg_match('/Private\ WP\ suite/', file_get_contents(ABSPATH . $plrpws_htaccess_file)))
						echo '<div id="notice" class="error"><p><i>' . $plrpws_htaccess_file . '</i> exists, but doesn\'t contain the correct rules. The following content should be placed in the file <i>' . $plrpws_htaccess_file . '</i>:</p><p><pre>' . $plrpws_htaccess_content . '</pre></p><p>You can also delete the file, and I\'ll try to recreate it for you. If you\'re doing rewrites another way this error can be ignored, see notice below on how to test your rewrite rule.</p></div>' . "\n";
				} else {
					echo '<div id="notice" class="error"><p><i>' . $plrpws_htaccess_file . '</i> exists, but I can\'t read it. Make sure it\'s readable by the web server and that the content of the file is the following:</p><p><pre>' . $plrpws_htaccess_content . '</pre></p><p>If you\'re doing rewrites another way this error can be ignored, see notice below on how to test your rewrite rule.</p></div>' . "\n";
				}
			} else {
				// No file found, check if upload path is writable
				if(is_writable(ABSPATH . $plrpws_upload_path)) {
					$plrpws_fp = fopen(ABSPATH . $plrpws_htaccess_file, 'w');
					fwrite($plrpws_fp, $plrpws_htaccess_content);
					fclose($plrpws_fp);
				} else {
					// Not writable, warn user
					echo '<div id="notice" class="error"><p><i>' . $plrpws_upload_path . '</i> is not writable, so i can\'t create the rewrite rules for you, you\'ll have to do it yourself. The following content should be placed in the file <i>' . $plrpws_htaccess_file . '</i>:</p><p><pre>' . $plrpws_htaccess_content . '</pre></p><p>If you\'re doing rewrites another way this error can be ignored, see notice below on how to test your rewrite rule.</p></div>' . "\n";
				}
			}
		} else {
			// Upload protection disabled, we should remove the .htaccess file if it exists
			if(file_exists(ABSPATH . $plrpws_htaccess_file))
				@unlink(ABSPATH . $plrpws_htaccess_file);
		}

		// Add notifications if needed
		if(!get_option('permalink_structure') && ($plrpws_protect_feeds || $plrpws_protect_uploads))
			echo '<div id="notice" class="error"><p>You\'ve enabled options that requires permalinks to be enabled. Please <a href="' . get_bloginfo('wpurl') . '/wp-admin/options-permalink.php">enable it here</a>. Feed and upload protection doesn\'t work with permalinks disabled.</p></div>' . "\n";

		if(!$is_apache && $plrpws_protect_uploads)
			echo '<div id="notice" class="updated"><p>You\'re not using the Apache (or LiteSpeed) webserver, this usually means that mod_rewrite rules that this plugin installs for you won\'t work.</p><p>Please check the <i>' . $plrpws_upload_path . '/.htaccess</i> file and translate the rewrite rules according to your web server requirements.</p></div>' . "\n";

		if($plrpws_protect_uploads)
			echo '<div id="notice" class="updated"><p>To test your rewrite rules, copy the complete URL for one of your uploaded images, log out from wordpress and try to visit the image. You should get a 404 error or the login page. If you see your image, rewrite rules doesn\'t work.</p></div>' . "\n";

		// Description
		echo 'Altough these settings are separate, you probable want to enable all of them.<br /><br />' . "\n";

		// Start form
		echo '<form method="post" action="options.php">' . "\n";

		// Magic wordpress function, adds hidden inputs to help redirect the user back to the right page after submit
		wp_nonce_field('update-options');

		// Start table
		echo '<table class="form-table">' . "\n";

		echo '<tr valign="top">' . "\n";
		echo '<th scope="row">Protect blog</th>' . "\n";
		if($plrpws_protect_blog) {
			echo '<td><input type="radio" name="plrpws_protect_blog" value="1" checked /> Enable <input type="radio" name="plrpws_protect_blog" value="0" /> Disable</td>' . "\n";
		} else {
			echo '<td><input type="radio" name="plrpws_protect_blog" value="1" /> Enable <input type="radio" name="plrpws_protect_blog" value="0" checked /> Disable</td>' . "\n";
		}
		echo '<td><span class="description">Require visitors to login before viewing any content (except feeds) on your site.</span></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr valign="top">' . "\n";
		echo '<th scope="row">Disable feeds</th>' . "\n";
		if($plrpws_protect_feeds) {
			echo '<td><input type="radio" name="plrpws_protect_feeds" value="1" checked /> Enable <input type="radio" name="plrpws_protect_feeds" value="0" /> Disable</td>' . "\n";
		} else {
			echo '<td><input type="radio" name="plrpws_protect_feeds" value="1" /> Enable <input type="radio" name="plrpws_protect_feeds" value="0" checked /> Disable</td>' . "\n";
		}
		echo '<td><span class="description">Disable all feeds completely, since RSS readers can\'t handle the login form anyways.</span></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr valign="top">' . "\n";
		echo '<th scope="row">Protect uploads</th>' . "\n";
		if($plrpws_protect_uploads) {
			echo '<td><input type="radio" name="plrpws_protect_uploads" value="1" checked /> Enable <input type="radio" name="plrpws_protect_uploads" value="0" /> Disable</td>' . "\n";
		} else {
			echo '<td><input type="radio" name="plrpws_protect_uploads" value="1" /> Enable <input type="radio" name="plrpws_protect_uploads" value="0" checked /> Disable</td>' . "\n";
		}
		echo '<td><span class="description">Add rewrite rules to make this plugin handle requests to uploaded files (and only serve them to logged in users).</span></td>' . "\n";
		echo '</tr>' . "\n";

		echo '<tr valign="top">' . "\n";
		echo '<th scope="row">Exceptions</th>' . "\n";
		echo '<td><textarea name="plrpws_exceptions" cols="30" rows="5">' . $plrpws_exceptions . '</textarea></td>' . "\n";
		echo '<td><span class="description">IP addresses (in <a href="http://en.wikipedia.org/wiki/CIDR_notation">CIDR notation</a> format) to be excempt from the above restrictions. One per line.</span></td>' . "\n";
		echo '</tr>' . "\n";

		// End table
		echo '</table><br />' . "\n";

		// Magic hidden inputs to make wordpress update our options
		echo '<input type="hidden" name="action" value="update" />' . "\n";
		echo '<input type="hidden" name="page_options" value="plrpws_protect_blog,plrpws_protect_feeds,plrpws_protect_uploads,plrpws_exceptions" />' . "\n";

		// Submit button
		echo '<input type="submit" name="plrpws_submit" class="button-primary" value="Save Changes" />' . "\n";

		// End form
		echo '</form>' . "\n";

		// End wrap div
		echo '</div>' . "\n";
	}

	// This is the function that requires the user to login if the option is enabled and the IP address is not excempt
	function plrpws_protect_blog() {
		if(!is_user_logged_in() && get_option('plrpws_protect_blog') && !plrpws_is_excempt())
			auth_redirect();
	}

	// Register the above function
	add_action('get_header', 'plrpws_protect_blog', 1);

	// This is the function that disables the feeds if the visitor is not excempt
	function plrpws_protect_feeds() {
		if(get_option('plrpws_protect_feeds') && !plrpws_is_excempt())
			die('You are not authorized to view this feed.');
	}

	// Register the above functiona
	add_action('do_feed', 'plrpws_protect_feeds', 1);
	add_action('do_feed_rdf', 'plrpws_protect_feeds', 1);
	add_action('do_feed_rss', 'plrpws_protect_feeds', 1);
	add_action('do_feed_rss2', 'plrpws_protect_feeds', 1);
	add_action('do_feed_atom', 'plrpws_protect_feeds', 1);

	// This is the function that protects the uploaded files
	function plrpws_protect_uploads() {
		if(is_user_logged_in() || plrpws_is_excempt() || !get_option('plrpws_protect_uploads')) {
			// User is logged in (or excempt) and protection is enabled, check if the requested file exists
			$plrpws_wp_path        = parse_url(get_bloginfo('wpurl'), PHP_URL_PATH);
			$plrpws_requested_file = substr($_SERVER['REQUEST_URI'], strlen($plrpws_wp_path));

			if(file_exists(ABSPATH . $plrpws_requested_file)) {
				// File exists, serve it
				$plrpws_file_size = filesize(ABSPATH . $plrpws_requested_file);
				$plrpws_mime_type = wp_check_filetype(ABSPATH . $plrpws_requested_file);
				header('HTTP/1.1 200 OK', true, 200);
				header('Content-Length: ' . $plrpws_file_size);
				header('Content-type: ' . $plrpws_mime_type['type']);

				ob_clean();
				flush();

				readfile(ABSPATH . $plrpws_requested_file);
				exit;
			}
		}
	}

	// Hook into the 404 page
	add_filter('404_template', 'plrpws_protect_uploads');

	// Function to run when deactivating plugin
	function plrpws_deactivate() {
		// Remove .htaccess if it exists
		$plrpws_upload_path     = get_option('upload_path');
		if(!$plrpws_upload_path)
			$plrpws_upload_path = 'wp-content/uploads';

		$plrpws_htaccess_file   = $plrpws_upload_path . '/.htaccess';

		if(file_exists(ABSPATH . $plrpws_htaccess_file))
			@unlink(ABSPATH . $plrpws_htaccess_file);

		// Delete old options
		delete_option('plrpws_protect_blog');
		delete_option('plrpws_protect_feeds');
		delete_option('plrpws_protect_uploads');
		delete_option('plrpws_exceptions');
	}

	// Register deactivation function
	register_deactivation_hook(__FILE__, 'plrpws_deactivate');

	// Function to check if visitor is excempt, used in other functions
	function plrpws_is_excempt() { 
		$exceptions = explode("\n", get_option('plrpws_exceptions'));
		foreach($exceptions as $exception) {
			$exception = trim($exception);
			// If match is found, return true
			if($exception && (plrpws_net_match($exception, $_SERVER['REMOTE_ADDR']) || $exception == $_SERVER['REMOTE_ADDR']))
				return true;
		}
	} 

	// Function to match CIDR notation and IP address, used in other functions
	function plrpws_net_match($cidr, $ip) {
		list($net, $mask) = explode('/', $cidr); 
		return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($net); 
	} 
?>
