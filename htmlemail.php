<?php
/*
Plugin Name: HTML Email Templates
Plugin URI: http://premium.wpmudev.org/project/html-email-templates/
Description: Allows you to add HTML templates for all of the standard Wordpress emails. In Multisite templates are set network wide.
Author: WPMU DEV
Version: 1.1
Author URI: http://premium.wpmudev.org/
Network: true
WDP ID: 142
*/

/*
Copyright 2010-2014 Incsub
Author - Aaron Edwards
Contributors - Barry Getty

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Portions of this code are from or inspired by Mohammad Jangda's "HTML Emails" plugin: http://wordpress.org/extend/plugins/html-emails/
*/

class HTML_emailer {
	//This is where the class variables go, don't forget to use @var to tell what they're for
	/**
	* @var string The options string name for this plugin
	*/
	var $optionsName = 'html_email_options';

	/**
	* @var string $localizationDomain Domain used for localization
	*/
	var $localizationDomain = "html_email";

	/**
	* @var string $pluginurl The path to this plugin
	*/
	var $thispluginurl = '';
	/**
	* @var string $pluginurlpath The path to this plugin
	*/
	var $thispluginpath = '';

	/**
	* @var array $options Stores the options for this plugin
	*/
	var $options = array();

	//Class Functions
	/**
	* PHP 5 Constructor
	*/
	function __construct(){

		//setup proper directories
		if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/htmlemail/' . basename(__FILE__))) {
			$this->location = 'plugins';
			$this->plugin_dir = WP_PLUGIN_DIR . '/htmlemail/';
			$this->plugin_url = WP_PLUGIN_URL . '/htmlemail/';
		} else if (defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__))) {
			$this->location = 'mu-plugins';
			$this->plugin_dir = WPMU_PLUGIN_DIR . '/';
			$this->plugin_url = WPMU_PLUGIN_URL . '/';
		} else {
			wp_die(__('There was an issue determining where HTML Email is installed. Please reinstall.', $this->localizationDomain));
		}

		//localize
		add_action( 'plugins_loaded', array(&$this, 'localization') );

		//Actions
		add_action('admin_menu', array(&$this,'admin_menu_link'));
		add_action('network_admin_menu', array(&$this,'admin_menu_link'));
		add_action('phpmailer_init', array(&$this,'convert_plain_text'));

		//Filters
		add_filter('wp_mail', array(&$this,'wp_mail'));
		add_filter('retrieve_password_message', array(&$this,'fix_pass_msg'));
	}

	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's "languages" folder and name it "html_email-[value in wp-config].mo"
		if ($this->location == 'plugins')
			load_plugin_textdomain( $this->localizationDomain, false, '/htmlemail/includes/languages/' );
		else if ($this->location == 'mu-plugins')
			load_muplugin_textdomain( $this->localizationDomain, '/htmlemail/includes/languages/' );
	}

	function wp_mail($args) {
		extract($args);

		$reply_to = '';

		//Set the content type header, charset, and reply to headers
		if ( is_array($headers) ) {
			$headers[] = 'Content-Type: text/html; charset="' . get_option('blog_charset') . '"';
		} else {
			$headers .= 'Content-Type: text/html; charset="' . get_option('blog_charset') . "\"\n";
		}

		$this->plain_text_message = $message;

		//Force WP to add <p> tags to the message content
		$message = wpautop($message);
		$message = str_replace('MESSAGE', $message, get_site_option('html_template'));

		//Compact & return all the vars
		return compact( 'to', 'subject', 'message', 'headers', 'attachments' );
	}

	//removes the <> symbols from the reset password link so it's not hidden in html mode emails
	function fix_pass_msg($msg) {
		$msg = str_replace('<', '', $msg);
		$msg = str_replace('>', '', $msg);
		return $msg;
	}

	function convert_plain_text( $phpmailer ) {
		// Create plain text version of email if it doesn't exist
		if ( $phpmailer->ContentType == 'text/html' && $phpmailer->AltBody == '') {
			$phpmailer->AltBody = $this->plain_text_message;
		}
	}

	/**
	* @desc Adds the options subpanel
	*/
	function admin_menu_link() {
		//If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
		//reflect the page filename (ie - options-general.php) of the page your plugin is under!
		if (is_multisite())
			$page = add_submenu_page( 'settings.php', __('HTML Email Template', $this->localizationDomain), __('HTML Email Template', $this->localizationDomain), 'manage_network_options', 'html-template', array(&$this, 'admin_options_page') );
		else if (!is_multisite())
			$page = add_submenu_page( 'options-general.php', __('HTML Email Template', $this->localizationDomain), __('HTML Email Template', $this->localizationDomain), 'manage_options', 'html-template', array(&$this, 'admin_options_page') );

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
	}

	/**
	* @desc Adds the Settings link to the plugin activate/deactivate page
	*/
	function filter_plugin_actions($links, $file) {
		if (is_multisite())
			$settings_link = '<a href="'.network_admin_url('settings.php?page=html-template').'">' . __('Settings', $this->localizationDomain) . '</a>';
		else
			$settings_link = '<a href="'.admin_url('options-general.php?page=html-template').'">' . __('Settings', $this->localizationDomain) . '</a>';

		array_unshift( $links, $settings_link ); // before other links

		return $links;
	}

	/**
	* Adds settings/options page
	*/
	function admin_options_page() {
		global $current_user;

		if (isset($_POST['save_html_email_options'])) {
			if (! wp_verify_nonce($_POST['_wpnonce'], 'html_email-update-options') ) die(__('Whoops! There was a problem with the data you posted. Please go back and try again.', $this->localizationDomain));

			$template = stripslashes($_POST['template']);
			update_site_option('html_template', $template);
			echo '<div class="updated"><p>' . __('Success! Your changes were sucessfully saved!', $this->localizationDomain) . '</p></div>';
		}

		if (isset($_POST['preview_html_email'])) {
			if (! wp_verify_nonce($_POST['_wpnonce'], 'html_email-update-options') ) wp_die(__('Whoops! There was a problem with the data you posted. Please go back and try again.', $this->localizationDomain));

			wp_mail($current_user->user_email, 'Test HTML Email Subject', "This is a test message I want to try out to see if it works\n\nIs it working well?");
			echo '<div class="updated"><p>' . sprintf(__('Preview email was mailed to %s!', $this->localizationDomain), $current_user->user_email). '</p></div>';
		}
		?>
		<div class="wrap">
		<form method="post">
		<?php wp_nonce_field('html_email-update-options'); ?>
		<h2><?php _e('HTML Email Template', $this->localizationDomain); ?></h2>
		<p><?php _e('This plugin will wrap every WordPress email sent within an HTML template.', $this->localizationDomain); ?></p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('HTML Template', $this->localizationDomain) ?></th>
				<td>
					<textarea name="template" rows="25" style="width: 95%"><?php echo esc_attr(get_site_option('html_template')); ?></textarea><br />
					<span class="description"><?php _e('Please enter the HTML of your email template here. You need to place MESSAGE somewhere in the template, preferably a main content section. That will be replaced with the email message.', $this->localizationDomain) ?></span>
				</td>
			</tr>
		</table>
		<p><div class="submit"><input type="submit" name="save_html_email_options" class="button-primary" value="<?php _e('Save', $this->localizationDomain); ?>" />&nbsp;&nbsp;&nbsp;<input type="submit" name="preview_html_email" class="button-secondary" value="<?php _e('Preview &raquo;', $this->localizationDomain); ?>" /></div>			</p>
		</form>
		</div>
		<?php
	}

} //End Class
//instantiate the class
$html_email_var = new HTML_emailer();

global $wpmudev_notices;
$wpmudev_notices[] = array( 'id'=> 142,'name'=> 'HTML Email Templates', 'screens' => array( 'settings_page_html-template', 'settings_page_html-template-network' ) );
include_once( dirname( __FILE__ ) . '/includes/dash-notice/wpmudev-dash-notification.php' );