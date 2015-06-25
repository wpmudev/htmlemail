<?php
/*
Plugin Name: HTML Email Templates
Plugin URI: https://premium.wpmudev.org/project/html-email-templates/
Description: Allows you to add HTML templates for all of the standard Wordpress emails. In Multisite templates can be set network wide or can be allowed to set site wise template, if template override for the site is enabled and template is not specified for a site, network template will be used.
Author: WPMU DEV
Version: 2.0.4
Author URI: http://premium.wpmudev.org/
Network: true
WDP ID: 142
*/

/*
Copyright 2010-2015 Incsub
Author - Aaron Edwards
Contributors - Barry Getty, Umesh Kumar

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
	 * @var string $textdomain Domain used for localization
	 */
	var $textdomain = "html_email";

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

	/**
	 * @var path Template Directory
	 */
	var $template_directory = '';

	/**
	 * @var path Template URL
	 */
	var $template_url = '';

	/**
	 * @var path Template Directory
	 */
	var $theme_path = '';

	/**
	 * @var path Template URL
	 */
	var $theme_url = '';

	/**
	 * @var path to assets
	 */
	var $assets_path = '';

	/**
	 * @var path to assets
	 */
	var $settings = array();

	/**
	 * Content type of email
	 * @var bool
	 */
	var $is_html = '';

	/**
	 * Plain Text Message
	 *
	 * @var string
	 */
	var $plain_text_message = '';

	//Class Functions
	/**
	 * PHP 5 Constructor
	 */
	function __construct() {

		//setup proper directories
		if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( plugin_dir_path( __FILE__ ) . basename( __FILE__ ) ) ) {
			$this->location   = 'plugins';
			$this->plugin_dir = plugin_dir_path( __FILE__ );
			$this->plugin_url = plugins_url( '', __FILE__ ) . '/';
		} else if ( defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
			$this->location   = 'mu-plugins';
			$this->plugin_dir = WPMU_PLUGIN_DIR . '/';
			$this->plugin_url = WPMU_PLUGIN_URL . '/';
		} else {
			wp_die( __( 'There was an issue determining where HTML Email is installed. Please reinstall.', $this->textdomain ) );
		}
		//Template Directory
		$this->template_directory = $this->plugin_dir . 'lib/templates/';
		$this->template_url       = $this->plugin_url . 'lib/templates/';

		//Assets Directory
		$this->assets_path = $this->plugin_url . 'assets/';

		//localize
		add_action( 'plugins_loaded', array( &$this, 'localization' ) );

		//Actions
		add_action( 'admin_menu', array( &$this, 'admin_menu_link' ) );
		add_action( 'network_admin_menu', array( &$this, 'admin_menu_link' ) );
		add_action( 'phpmailer_init', array( &$this, 'convert_plain_text' ) );

		//Filters
		add_filter( 'wp_mail', array( &$this, 'wp_mail' ) );
		add_filter( 'retrieve_password_message', array( &$this, 'fix_pass_msg' ) );

		//Templates
		add_filter( 'htmlemail_templates', array( $this, 'htmlemail_templates_list' ) );

		//Enqueue files
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		//Return template data
		add_action( 'wp_ajax_htmlemail_get_template_data', array( $this, 'htmlemail_template_data' ) );
		add_action( 'wp_ajax_get_preview_data', array( $this, 'get_preview_data' ) );

		//Handle preview email ajax request
		add_action( 'wp_ajax_preview_email', array( $this, 'preview_email' ) );

		// Set Content type HTML
		add_filter( 'wp_mail_content_type', array( $this, 'set_content_type' ), 11 );
		add_filter( 'woocommerce_email_headers', array( $this, 'set_woocommerce_content_type' ) );

	}

	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's "languages" folder and name it "html_email-[value in wp-config].mo"
		if ( $this->location == 'plugins' ) {
			load_plugin_textdomain( $this->textdomain, false, '/htmlemail/includes/languages/' );
		} else if ( $this->location == 'mu-plugins' ) {
			load_muplugin_textdomain( $this->textdomain, '/htmlemail/includes/languages/' );
		}
	}

	/**
	 * Filter the Content, add the template to actual email and then send it
	 *
	 * @param $args
	 *
	 * @return array
	 */
	function wp_mail( $args ) {
		extract( $args );

		if ( ! is_multisite() || is_network_admin() ) {
			$modify_html_email = get_site_option( 'modify_html_email' );
		} else {
			$modify_html_email = get_option( 'modify_html_email' );
		}
		$modify_html_email = isset( $modify_html_email ) ? $modify_html_email : 1;
		/**
		 * Check if the current mail is a html mail and template adding is allowed or not
		 */
		if ( ! empty( $this->is_html ) ) {
			if ( $this->is_html && ! $modify_html_email ) {
				return $args;
			}
		} elseif ( ! empty( $headers ) ) {
			//check headers
			if ( is_array( $headers ) ) {
				if ( in_array( 'text/html', $headers ) && ! $modify_html_email ) {
					return $args;
				}
			} elseif ( strpos( $headers, 'text/html' ) !== false && ! $modify_html_email ) {
				return $args;
			}
		}

		$html_template = $htmlemail_settings = '';

		$this->plain_text_message = $message;

		//Force WP to add <p> tags to the message content
		if ( $message == strip_tags( $message ) ) {
			// No HTML, do wpautop
			$message = wpautop( $message );
		}

		//Fetch HTML email settings
		$htmlemail_settings = get_site_option( 'htmlemail_settings' );

		//Check if site is allowed to use its own template
		$site_override = isset( $htmlemail_settings['site_override'] ) ? $htmlemail_settings['site_override'] : '';
		//As the network has site id 1, it loads the template for blog with id 1 but for preview we need to show network template
		if ( isset( $_POST['preview_html_email'] ) && 'Send' == $_POST['preview_html_email'] && is_network_admin() ) {
			$html_template = get_site_option( 'html_template' );
		}

		if ( empty( $html_template ) && $site_override && is_multisite() ) {
			$html_template = get_option( 'html_template' );
		}

		if ( empty( $html_template ) ) {
			$html_template = get_site_option( 'html_template' );
		}

		if ( ! empty ( $html_template ) ) {
			if ( strpos( $html_template, '{MESSAGE}' ) !== false ) {
				//Replace {MESSAGE} in template with actual email content
				$key = '{MESSAGE}';
			} else {
				//Compatibilty with previous version of the plugin, as it used MESSAGE instead of {MESSAGE}
				$key = 'MESSAGE';
			}
			$message = str_replace( $key, $message, $html_template );

			//Replace User name
			$user = get_user_by( 'email', $to );
			if ( $user ) {
				$message = preg_replace( '~\{USER_NAME}~', $user->data->display_name, $message );
			} else {
				$message = preg_replace( '~\{USER_NAME}~', '', $message );
			}

			$message = $this->replace_placeholders( $message, false );
		}

		//Compact & return all the vars
		return compact( 'to', 'subject', 'message', 'headers', 'attachments' );
	}

	//removes the <> symbols from the reset password link so it's not hidden in html mode emails
	function fix_pass_msg( $msg ) {
		$msg = str_replace( '<', '', $msg );
		$msg = str_replace( '>', '', $msg );

		return $msg;
	}

	function convert_plain_text( $phpmailer ) {
		// Create plain text version of email if it doesn't exist
		if ( $phpmailer->AltBody == '' ) {
			$phpmailer->AltBody = $this->plain_text_message;
		}
	}

	/**
	 * @desc Adds the options subpanel
	 */
	function admin_menu_link() {
		global $html_template, $html_template_network, $html_template_site;
		//If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
		//reflect the page filename (ie - options-general.php) of the page your plugin is under!
		if ( is_multisite() ) {
			$html_template_network = add_submenu_page( 'settings.php', __( 'HTML Email Template', $this->textdomain ), __( 'HTML Email Template', $this->textdomain ), 'manage_network_options', 'html-template', array(
				&$this,
				'admin_options_page'
			) );

			$htmlemail_settings = get_site_option( 'htmlemail_settings' );

			$site_override = isset( $htmlemail_settings['site_override'] ) ? $htmlemail_settings ['site_override'] : '';

			if ( $site_override ) {
				$html_template_site = add_options_page( __( 'HTML Email Template', $this->textdomain ), __( 'HTML Email Template', $this->textdomain ), 'manage_options', 'html-template', array(
					$this,
					'admin_options_page'
				) );
			}
			//register scripts for site
			add_action( "load-{$html_template_site}", array( &$this, 'register_scripts' ) );
		} else if ( ! is_multisite() ) {
			$html_template = add_submenu_page( 'options-general.php', __( 'HTML Email Template', $this->textdomain ), __( 'HTML Email Template', $this->textdomain ), 'manage_options', 'html-template', array(
				&$this,
				'admin_options_page'
			) );
		}
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
			&$this,
			'filter_plugin_actions'
		), 10, 2 );
		add_action( "load-{$html_template}", array( &$this, 'register_scripts' ) );
		add_action( "load-{$html_template_network}", array( &$this, 'register_scripts' ) );
	}

	/**
	 * @desc Adds the Settings link to the plugin activate/deactivate page
	 */
	function filter_plugin_actions( $links, $file ) {
		if ( is_multisite() ) {
			$settings_link = '<a href="' . network_admin_url( 'settings.php?page=html-template' ) . '">' . __( 'Settings', $this->textdomain ) . '</a>';
		} else {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=html-template' ) . '">' . __( 'Settings', $this->textdomain ) . '</a>';
		}

		array_unshift( $links, $settings_link ); // before other links

		return $links;
	}

	/**
	 * Adds settings/options page
	 */
	function admin_options_page() {
		global $current_user;

		//Process $_POST
		$this->save_settings();

		//Fetch HTML email settings
		if ( ! is_multisite() || is_network_admin() ) {

			$htmlemail_settings = get_site_option( 'htmlemail_settings' );
			$html_template      = get_site_option( 'html_template' );
			$modify_html_email  = get_site_option( 'modify_html_email' );

		} else {
			$htmlemail_settings = get_option( 'htmlemail_settings' );
			$html_template      = get_option( 'html_template' );
			$modify_html_email  = get_option( 'modify_html_email' );

		}
		$modify_html_email = isset( $modify_html_email ) ? $modify_html_email : 1;

		//Whether to allow subsites to specify their own html template
		$site_override = isset( $htmlemail_settings['site_override'] ) ? $htmlemail_settings ['site_override'] : '';
		?>
		<div class="wrap">
			<form method="post">
				<?php wp_nonce_field( 'html_email-update-options' ); ?>
				<h2><?php esc_html_e( 'HTML Email Template', $this->textdomain ); ?></h2>

				<p class="description"><?php _e( 'This plugin will wrap every WordPress email sent within an HTML template.', $this->textdomain ); ?></p>

				<div class='config-guide'>
					<h3><?php _e( 'Four easy steps to send better emails:', $this->textdomain ); ?></h3>
					<?php
					$configuration_steps = array(
						__( 'Either select a pre-designed template <a href="#template-wrapper" class="template-toggle" title="Select template">below</a>  by clicking over a template and then click over the load template button or type/paste your own HTML into the textarea.', $this->textdomain ),
						__( 'Click "Preview" to quickly see what your emails will look like in a popup.', $this->textdomain ),
						__( 'Send a "Test Email" to preview your template in actual email clients. You can specify an email address for this to be sent to.', $this->textdomain ),
						__( 'Select "Save" and the HTML you have below will be used as your HTML Email Template for all transactional emails from your site.', $this->textdomain )
					); ?>
					<ul class='config-steps'><?php
						$count = 1;
						foreach ( $configuration_steps as $step ) {
							?>
							<li class='config-step'>
							<span class="step-count"><?php echo sprintf( __( 'Step %d', $this->textdomain ), $count ) . "<br />"; ?></span><?php
							echo $step; ?>
							</li><?php
							$count ++;
						} ?>
					</ul>
				</div>
				<!-- Overwrite HTML Emails -->
				<label><input type="checkbox" name="modify_html_email" <?php checked( $modify_html_email, 1 ); ?> value="1"/><strong><?php echo esc_html__( "Modify HTML Emails", $this->textdomain ) ?></strong></label>
				<h5>
					<a href="#template-wrapper" class="template-toggle" title="<?php esc_attr_e( 'Click to toggle templates', $this->textdomain ); ?>"><?php _e( 'Choose from sample Templates', $this->textdomain ) ?>
						[<span class="toggle-indicator">+</span>]</a>
				</h5>

				<div class="template-wrapper" id="template-wrapper"><?php
					$templates = array();
					$templates = apply_filters( 'htmlemail_templates', $templates );
					if ( ! empty( $templates ) && is_array( $templates ) ) {
						?>
						<div class="email-templates-wrapper">
						<div class="email-templates"><?php

							foreach ( $templates as $template ) {
								$template_name = preg_replace( '/\s+/', '', $template['name'] );
								?>
								<div class="template-holder">
								<!--Template preview-->
								<a class="template-selector" href="#<?php echo $template['name']; ?>" title="<?php esc_attr_e( 'Click over the template to select it' ); ?>"><?php echo $template['name']; ?>
									<br/><img class="theme-preview" src="<?php echo $template['screenshot']; ?>" alt="<?php echo esc_attr( $template['name'] ); ?>"/></a>

								<a id="load_template_<?php echo $template_name; ?>" class="load_template button-primary disabled" href="#" title="<?php esc_attr_e( 'Load template html', $this->textdomain ); ?>"><?php echo __( 'Load Template ', $this->textdomain ) . $template['name']; ?></a>
								</div><?php
							} ?>

						</div>
						</div><?php
					} ?>
				</div><?php
				echo $this->list_placeholders( '', true ); ?>
				<div class="action-wrapper submit">
					<input type="submit" name="save_html_email_options" class="button-primary"
						value="<?php _e( 'Save', $this->textdomain ); ?>"/>

					<?php if ( current_user_can( 'unfiltered_html' ) ) { //it's only safe to allow live previews for unfiltered_html cap to prevent XSS ?>
						<a name="preview_template" id="preview_template" class="button button-secondary"
							href="<?php echo plugins_url( 'preview.html?TB_iframe=true&height=500&width=700', __FILE__ ); ?>"
							title="<?php esc_attr_e( 'Live Preview', $this->textdomain ); ?>"><?php _e( 'Preview', $this->textdomain ); ?></a>
					<?php } ?>

					<input type="button" name="specify_email" class="button-secondary specify_email"
						value="<?php _e( 'Test Email', $this->textdomain ); ?>"/>
					<span class="spinner"></span><br/>

					<div class="preview-email">
						<input type="text" name="preview_html_email_address" value="<?php echo $current_user->user_email; ?>" placeholder="Email address"/>
						<input type="submit" name="preview_html_email" class="button-primary" value="<?php _e( 'Send', $this->textdomain ); ?>"/>
						<?php wp_nonce_field( 'preview_email', 'preview_email' ); ?>
					</div>
				</div>
				<div class="template-content-holder">
					<span class="description"><?php _e( 'Edit the HTML of your email template here. You need to place {MESSAGE} somewhere in the template, preferably a main content section. That will be replaced with the email message.', $this->textdomain ) ?></span>
					<textarea name="template" id="template-content" rows="25" style="width: 100%"><?php echo esc_textarea( $html_template ); ?></textarea><br/>
				</div>

				<?php
				if ( is_network_admin() ) {
					?>
					<label>
					<input type="checkbox" name="htmlemail_settings[site_override]" <?php echo checked( $site_override, 1 ); ?> value="1"/><?php _e( 'Allow subsites to override this template with their own.', $this->textdomain ); ?>
					</label><?php
				}
				?>
			</form>
		</div>
	<?php
	}

	function register_scripts() {
		wp_register_style( 'slick_style', $this->assets_path . 'slick/slick.css' );
		wp_register_script( 'slick_js', $this->assets_path . 'slick/slick.min.js', array( 'jquery' ), '', true );
		wp_register_style( 'htmlemail_css', $this->assets_path . 'css/htmlemail.css' );
		wp_register_script( 'htmlemail_js', $this->assets_path . 'js/htmlemail.js', array(
			'jquery',
			'slick_js',
			'thickbox'
		), '', true );
		//Lolcalize string to js, to make them translatable
		$template_load_warning = __( "Your custom template changes will be lost, are you sure you want to continue?", $this->textdomain );
		$message_missing       = __( "You need to place {MESSAGE} somewhere in the template, preferably a main content section.", $this->textdomain );
		$htmlemail_help_text   = array(
			'load_template'   => $template_load_warning,
			'message_missing' => $message_missing
		);
		wp_localize_script( 'htmlemail_js', 'htmlemail_text', $htmlemail_help_text );
	}

	function enqueue_scripts() {
		if ( ! $this->is_htmlemail_setting_page() ) {
			return;
		}
		wp_enqueue_style( 'slick_style' );
		wp_enqueue_script( 'slick_js' );
		wp_enqueue_style( 'htmlemail_css' );
		wp_enqueue_script( 'htmlemail_js' );
		wp_enqueue_style( 'thickbox' );
	}

	/**
	 * Returns template list for Emails
	 * @return type
	 */
	function htmlemail_templates_list() {
		$templates = array();
		//get all template folders inside template directory
		foreach ( glob( $this->template_directory . '*', GLOB_ONLYDIR ) as $template_path ) {
			$template_url = $this->template_url . basename( $template_path );
			if ( $template_path ) {
				$template_html       = $template_path . '/template.html';
				$template_screenshot = glob( $template_path . '/screenshot.*' );
				//Check if it contains template.html and a screenshot
				if ( ! file_exists( $template_html ) || ! file_exists( $template_screenshot[0] ) ) {
					continue;
				}
				$theme_name  = get_file_data( $template_path . '/style.css', array( 'Name' => 'Theme Name' ) );
				$templates[] = array(
					'name'       => $theme_name['Name'],
					'screenshot' => $template_url . '/' . basename( $template_screenshot[0] )
				);
			}
		}

		return $templates;
	}

	/**
	 * Returns HTML for a selected template
	 */
	function htmlemail_template_data() {
		if ( empty( $_GET['theme'] ) ) {
			wp_send_json_error( 'no theme specified' );
		}
		$content = $this->get_contents_elements( $_GET['theme'] );
		wp_send_json_success( $content );
	}

	/**
	 * Fetches content from template files and returns content with inline styling
	 *
	 * @param type $theme_name
	 *
	 * @return boolean
	 */
	function get_contents_elements( $theme_name = '' ) {
		if ( ! $theme_name ) {
			return false;
		}
		$contents         = array();
		$theme_name       = explode( ' ', $theme_name );
		$theme_name       = implode( '', $theme_name );
		$theme_name       = ucfirst( strtolower( $theme_name ) );
		$this->theme_path = $this->template_directory . $theme_name;
		$this->theme_url  = $this->template_url . $theme_name;

		//Get Default Variables
		require_once( $this->theme_path . '/index.php' );

		//Template Files
		$build_htmls['header'][]  = $this->theme_path . "/header.html";
		$build_htmls['content'][] = $this->theme_path . "/template.html";
		$build_htmls['footer'][]  = $this->theme_path . "/footer.html";

		if ( defined( 'BUILDER_SETTING_USE_DEFAULT_HEADER_FOOTER' ) ) {
			$build_htmls['header'][] = $this->template_directory . "default_header.html";
			$build_htmls['footer'][] = $this->template_directory . "default_footer.html";
		}

		$build_styles['style'][]        = $this->theme_path . "/style.css";
		$build_styles['style_header'][] = $this->theme_path . "/style_header.css";

		if ( defined( 'BUILDER_SETTING_USE_DEFAULT_STYLES' ) ) {
			$build_styles['default_style'][] = $this->template_directory . "default_style.css";
		}

		$build_theme = array_merge( $build_htmls, $build_styles );
		foreach ( $build_theme as $type => $possible_files ) {
			foreach ( $possible_files as $possible_file ) {
				if ( isset( $contents_parts[ $type ] ) && ! empty( $contents_parts[ $type ] ) ) {
					continue;
				}
				if ( file_exists( $possible_file ) ) {
					$handle                  = fopen( $possible_file, "r" );
					$contents_parts[ $type ] = fread( $handle, filesize( $possible_file ) );
					fclose( $handle );

					if ( strpos( $type, 'style' ) !== false ) {
						$contents_parts[ $type ] = preg_replace( "/^\s*\/\*[^(\*\/)]*\*\//m", "", $contents_parts[ $type ] );
					}
				}
				if ( ! isset( $contents_parts[ $type ] ) ) {
					$contents_parts[ $type ] = '';
				}
			}
		}
		//if head missing - fix it!
		if ( strpos( $contents_parts['header'] . $contents_parts['content'], '<html' ) === false && strpos( $contents_parts['content'] . $contents_parts['footer'], '</html>' ) === false ) {
			if ( strpos( $contents_parts['header'] . $contents_parts['content'], '<body' ) === false && strpos( $contents_parts['content'] . $contents_parts['footer'], '</body>' ) === false ) {
				$body_header = '<body>';
				$body_footer = '</body>';
			} else {
				$body_header = $body_footer = '';
			}

			$contents_parts['header'] = '
                        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                        <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                            <title>{EMAIL_TITLE}</title>
                            <style type="text/css">
                                {DEFAULT_STYLE_HEADER}
                                {STYLE_HEADER}
                            </style>

                            {HEADER}
                        </head>' . $body_header . $contents_parts['header'];

			$contents_parts['footer'] = $contents_parts['footer'] . $body_footer . '
                        </html>';
		}
		//Merge header, content and footer
		$content  = $contents_parts['header'] . $contents_parts['content'] . $contents_parts['footer'];
		$blog_url = get_option( 'siteurl' );

		//Replace BLOG_URL with actual URL as DOM compatibility escapes img src
		$content = preg_replace( "/{BLOG_URL}/", $blog_url . '/', $content );
		$style   = isset( $contents_parts['default_style'] ) ? $contents_parts['default_style'] . $contents_parts['style'] . $contents_parts['style_header'] : $contents_parts['style'] . $contents_parts['style_header'];
		//Do the inline styling
		$content = $this->do_inline_styles( $content, $style );

		//Check for DOM compatibilty from E-Newsletter
		$content = $this->dom_compatibility( $content );

		//Replace CSS Variabls
		$possible_settings = array(
			'BG_COLOR',
			'BG_IMAGE',
			'LINK_COLOR',
			'BODY_COLOR',
			'ALTERNATIVE_COLOR',
			'TITLE_COLOR',
			'EMAIL_TITLE'
		);
		foreach ( $possible_settings as $possible_setting ) {
			if ( defined( 'BUILDER_DEFAULT_' . $possible_setting ) ) {
				$this->settings[] = $possible_setting;
			}
		}

		foreach ( $this->settings as $setting ) {
			if ( defined( 'BUILDER_DEFAULT_' . $setting ) ) {
				if ( $setting == 'BG_IMAGE' ) {
					//full path for image
					$value = defined( constant( 'BUILDER_DEFAULT_' . $setting ) ) ? $this->theme_url . constant( 'BUILDER_DEFAULT_' . $setting ) : '';

				} else {
					$value = constant( 'BUILDER_DEFAULT_' . $setting );
				}
			}
			if ( stripos( $setting, 'color' ) ) {
				$value = preg_replace( '/[^A-Za-z0-9\-]/', '', $value );
			}
			if ( $setting != 'EMAIL_TITLE' ) {
				$content = preg_replace( "/\b($setting)\b/i", $value, $content );
			}
		}

		return $content;
	}

	/**
	 * Prepare inline styles
	 **/
	function do_inline_styles( $contents = '', $styles ) {
		if ( $contents && $styles ) {
			if ( ! class_exists( 'CssToInlineStyles' ) ) {
				require_once( $this->plugin_dir . 'lib/builder/css-inline.php' );
			}

			$css_inline = new CssToInlineStyles( $contents, $styles );
			$contents   = $css_inline->convert();
		}

		return $contents;
	}

	/**
	 * DOM Walker to ensure compatibility
	 *
	 * @param type $contents
	 *
	 * @return type
	 */
	function dom_compatibility( $contents ) {

		if ( ! class_exists( 'DOMDocument' ) ) {
			return $contents;
		}

		$dom = new DOMDocument();

		libxml_use_internal_errors( true );
		@$dom->loadHTML( $contents );
		libxml_clear_errors();

		$imgs = $dom->getElementsByTagName( 'img' );
		$ps   = $dom->getElementsByTagName( 'p' );
		foreach ( $ps as $p ) {
			$p_style = $p->getAttribute( 'style' );
			if ( ! empty( $p_style ) ) {
				break;
			}
		}
		foreach ( $imgs as $img ) {
			$classes_to_aligns = array( 'left', 'right' );
			foreach ( $classes_to_aligns as $class_to_align ) {
				if ( $img->hasAttribute( 'class' ) && strstr( $img->getAttribute( 'class' ), 'align' . $class_to_align ) ) {
					$img->setAttribute( 'align', $class_to_align );
				}
			}

			if ( $img->hasAttribute( 'class' ) && strstr( $img->getAttribute( 'class' ), 'aligncenter' ) ) {
				$img_style = $img->getAttribute( 'style' );
				$img_style = preg_replace( '#display:(.*?);#', '', $img_style );
				$img->setAttribute( 'style', $img_style );

				$parent = $img->parentNode;
				if ( $parent->nodeName == 'a' ) {
					$parent = $parent->parentNode;
				}

				if ( $parent->nodeName != 'div' ) {
					$parent->setAttribute( 'style', 'text-align:center;' . $parent->getAttribute( 'style' ) );
				} else {
					$element = $dom->createElement( 'p' );
					$element->setAttribute( 'style', 'text-align:center;' . $p_style );

					$img->parentNode->replaceChild( $element, $img );
					$element->appendChild( $img );
				}
			}

			$style = $img->getAttribute( 'style' );
			preg_match( '#margin:(.*?);#', $style, $matches );
			if ( $matches ) {
				$space_px      = explode( 'px', $matches[1] );
				$space_procent = explode( '%', $matches[1] );
				$space         = ( $space_procent > $space_px ) ? $space_procent : $space_px;
				$space_unit    = ( $space_procent > $space_px ) ? '%' : '';
				if ( $space ) {
					$hspace = trim( $space[0] );
					$vspace = ( isset( $space[1] ) ) ? $hspace : trim( $space[0] );

					$img->setAttribute( 'hspace', $hspace . $space_unit );
					$img->setAttribute( 'vspace', $vspace . $space_unit );
				}
				$style = preg_replace( '#margin:(.*?);#', '', $style );
				if ( $style ) {
					$img->setAttribute( 'style', $style );
				} else {
					$img->removeAttribute( 'style' );
				}
			}
		}
		$contents = $dom->saveHTML();

		return $contents;
	}

	/**
	 * Returns the list of placeholders in template content
	 */
	function list_placeholders( $content, $desc = false ) {
		if ( $desc ) {
			//Return Placeholder desc table
			$placeholder_desc = array(
				'{MESSAGE}'          => __( 'Email content (required)', $this->textdomain ),
				'{SIDEBAR_TITLE}'    => __( "Title for the sidebar in email e.g. What's trending", $this->textdomain ),
				'{FROM_NAME}'        => __( "Sender's name if sender's email is associated with a user account", $this->textdomain ),
				'{FROM_EMAIL}'       => __( "Sender's email, email specified in site settings", $this->textdomain ),
				'{BLOG_URL}'         => __( 'Blog / Site URL', $this->textdomain ),
				'{BLOG_NAME}'        => __( 'Blog / Site name', $this->textdomain ),
				'{ADMIN_EMAIL}'      => __( 'Email address of the support or contact person. Same as {FROM_EMAIL}', $this->textdomain ),
				'{BLOG_DESCRIPTION}' => __( 'Blog Description', $this->textdomain ),
				'{DATE}'             => __( 'Current date', $this->textdomain ),
				'{TIME}'             => __( 'Current time', $this->textdomain ),
			);

			$output = '<h4><a href="#placeholder-list-wrapper" class="template-toggle" title="' . esc_attr__( 'Variable list', $this->textdomain ) . '">' . __( 'List of variables that can be used in template', $this->textdomain ) .
			          '[<span class="toggle-indicator">+</span>]</a></h4>'
			          . '<div class="placeholders-list-wrapper" id="placeholder-list-wrapper">'
			          . '<table class="template-placeholders-list">';
			$output .= '<th>Variable name</th>';
			$output .= '<th>Default value</th>';

			//Get list of common variables
			foreach ( $placeholder_desc as $p_name => $p_desc ) {
				$output .= '<tr>';
				$output .= '<td>' . $p_name . '</td>';
				$output .= '<td>' . $p_desc . '</td>';
				$output .= '</tr>';
			}
			$output .= '</table>'
			           . '</div>';

			return $output;
		}
		$placeholders = $links = '';
		preg_match_all( "/\{.+\}/U", $content, $placeholders );
		//Jugaad, need to find a fix for this
		preg_match_all( "/\%7B.+\%7D/U", $content, $links );

		$placeholders = ! empty( $placeholders ) ? $placeholders[0] : '';
		$links        = ! empty( $links ) ? $links[0] : '';
		$placeholders = array_merge( $placeholders, $links );

		return $placeholders;
	}

	/**
	 * Replaces placeholder text in email templates
	 */
	function replace_placeholders( $content, $demo_message = true ) {

		$placeholders     = $this->list_placeholders( $content );
		$current_blog_id  = get_current_blog_id();
		$blog_url         = get_option( 'siteurl' );
		$admin_email      = get_option( 'admin_email' );
		$blog_name        = get_option( 'blogname' );
		$blog_description = get_option( 'blogdescription' );
		$date             = date_i18n( get_option( 'date_format' ) );
		$time             = date_i18n( get_option( 'time_format' ) );

		$message = "This is a test message I want to try out to see if it works. This will be replaced with wordpress email content.
             Is it working well?";

		$from_email = get_option( 'admin_email' );
		$user_info  = get_userdata( $from_email );
		if ( $user_info ) {
			$display_name = $user_info->display_name;
		} else {
			$display_name = '';
		}

		$bg_image     = defined( 'BUILDER_DEFAULT_BG_IMAGE' ) ? $this->theme_url . '/' . constant( 'BUILDER_DEFAULT_BG_IMAGE' ) : '';
		$header_image = defined( 'BUILDER_DEFAULT_HEADER_IMAGE' ) ? '<img src="' . $this->theme_url . '/' . constant( 'BUILDER_DEFAULT_HEADER_IMAGE' ) . '" />' : '';

		//Sidebar
		$posts_list = $this->htmlemail_recent_posts();
		/**
		 * Filter the post list displayed in email sidebar
		 *
		 * @since 2.0
		 *
		 * @param array $posts_list , An array of posts, containing ID and post_title for each post
		 */
		$posts_list = apply_filters( 'htmlemail_sidebar_posts', $posts_list );
		/**
		 * Filter the sidebar title in email template
		 *
		 * @since 2.0
		 *
		 * @param string $title , Title to be displayed in email
		 */
		$sidebar_title = apply_filters( 'htmlemail_sidebar_title', $title = "What's new" );

		//Placeholder for posts
		$count             = 1;
		$placeholder_posts = array();
		foreach ( $posts_list as $post ) {
			if ( $count > 4 ) {
				break;
			}
			$placeholder_posts["{POST_$count}"] = $this->short_str( $post['post_title'], '...', 10 );
			//Jugaad, to keep the template styling and links
			$placeholder_posts[ "%7BPOST_" . $count . "_LINK%7D" ] = esc_url( get_permalink( $post['ID'] ) );
			$count ++;
		}
		//Show for preview only
		if ( $demo_message ) {
			//Removed as it conflicts
//			$content = preg_replace( "/({MESSAGE})/", $message, $content );
//			$content = preg_replace( "/(MESSAGE)/", $message, $content );

			if ( strpos( $content, '{MESSAGE}' ) !== false ) {
				//Replace {MESSAGE} in template with actual email content
				$key = '{MESSAGE}';
			} else {
				//Compatibility with previous version of the plugin, as it used MESSAGE instead of {MESSAGE}
				$key = 'MESSAGE';
			}
			$content = str_replace( $key, $message, $content );

			$content = preg_replace( "/({USER_NAME})/", 'Jon', $content );
		}
		$placeholders_list = array(
			'{}'                 => '',
			'{SIDEBAR_TITLE}'    => $sidebar_title,
			'{CONTENT_HEADER}'   => '',
			'{CONTENT_FOOTER}'   => '',
			'{FOOTER}'           => '',
			'{FROM_NAME}'        => $display_name,
			'{FROM_EMAIL}'       => $from_email,
			'{BLOG_URL}'         => $blog_url,
			'{BLOG_NAME}'        => $blog_name,
			'{EMAIL_TITLE}'      => $blog_name,
			'{ADMIN_EMAIL}'      => $admin_email,
			'{BG_IMAGE}'         => $bg_image,
			'{HEADER_IMAGE}'     => $header_image,
			'{BLOG_DESCRIPTION}' => $blog_description,
			'{DATE}'             => $date,
			'{TIME}'             => $time
		);
		$placeholders_list = $placeholders_list + $placeholder_posts;
		foreach ( $placeholders as $placeholder ) {
			if ( ! isset( $placeholders_list [ $placeholder ] ) ) {
				continue;
			}
			$content = preg_replace( "/($placeholder)/", $placeholders_list[ $placeholder ], $content );
		}
		//Replace admin email, left out due to escaped html
		$content = preg_replace( "/(%7BADMIN_EMAIL%7D)/", $admin_email, $content );

		return $content;
	}

	/**
	 * Checks if on setting page for HTML Email Template
	 * @global type $html_template
	 * @global type $hook_suffix
	 * @return boolean
	 */
	function is_htmlemail_setting_page() {
		global $html_template, $html_template_network, $html_template_site, $hook_suffix;
		if ( $GLOBALS['hook_suffix'] == $GLOBALS['html_template']
		     || $GLOBALS['hook_suffix'] == $GLOBALS['html_template_site']
		     || $GLOBALS['hook_suffix'] == $GLOBALS['html_template_network']
		) {
			return true;
		}

		return false;
	}

	/**
	 * Returns data for preview
	 */
	function get_preview_data() {
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			wp_send_json_error( __( "Whoops, you don't have permissions to preview html.", $this->textdomain ) );
		}
		if ( empty( $_POST ) ) {
			wp_send_json_error( __( 'Whoops, you need to enter some html to preview it!', $this->textdomain ) );
		}
		$content = trim( $_POST['content'] );
		$content = stripslashes( $content );
		$content = $this->replace_placeholders( $content );

		if ( empty( $content ) ) {
			wp_send_json_error( __( 'Whoops, you need to enter some html to preview it!', $this->textdomain ) );
		}

		wp_send_json_success( $content );
	}

	/**
	 * Shortens string
	 *
	 * @param type $after
	 * @param type $length
	 *
	 * @return type
	 */
	function short_str( $str, $after = '', $length ) {
		if ( empty( $str ) ) {
			$str = explode( ' ', get_the_title(), $length );
		} else {
			$str = explode( ' ', $str, $length );
		}

		if ( count( $str ) >= $length ) {
			array_pop( $str );
			$str = implode( " ", $str ) . $after;
		} else {
			$str = implode( " ", $str );
		}

		return $str;
	}

	/**
	 * Returns an array for recent posts
	 * @return boolean
	 */
	function htmlemail_recent_posts() {
		//Recent Posts with their links
		$args         = array(
			'numberposts' => '4',
			'post_type'   => 'post',
			'post_status' => 'publish'
		);
		$recent_posts = wp_get_recent_posts( $args );

		return $recent_posts;
	}

	/**
	 * Send a preview email
	 */
	function preview_email() {
		global $current_user;

		//Check for empty email and nonce
		if ( empty( $_POST['preview_html_email_address'] ) || empty( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( __( 'Missing Parameters', $this->textdomain ) );
		}

		//Verify nonce
		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'preview_email' ) ) {
			wp_send_json_error( __( 'Nonce verification failed', $this->textdomain ) );
		}

		$email = ( isset( $_POST['preview_html_email_address'] ) && is_email( $_POST['preview_html_email_address'] ) ) ? $_POST['preview_html_email_address'] : $current_user->user_email;
		$sent  = wp_mail( $email, 'Test HTML Email Subject', "This is a test message I want to try out to see if it works\n\nIs it working well?" );

		//Success
		if ( $sent ) {
			wp_send_json_success( sprintf( __( 'Preview email was mailed to %s!', $this->textdomain ), $email ) );
		}
		//Unable to send email
		wp_send_json_error( __( 'Unable to send test email', $this->textdomain ) );
	}

	/**
	 * Return Content type as HTML for plain text email
	 *
	 * @param $content_type
	 *
	 * @return string, Content type
	 */

	function set_content_type( $content_type ) {
		if ( $content_type == 'text/plain' ) {
			$this->is_html = false;

			return 'text/html';
		}
		$this->is_html = true;

		return $content_type;
	}

	/**
	 * Set Content type for Woocommerce emails
	 */
	function set_woocommerce_content_type( $content_type ) {
		return "Content-Type: " . 'text/html' . "\r\n";
	}

	/**
	 * Save settings for Network or Subsite
	 */
	function save_settings() {
		//Save template content and other settings
		if ( isset( $_POST['save_html_email_options'] ) ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'html_email-update-options' ) ) {
				die( __( 'Whoops! There was a problem with the data you posted. Please go back and try again.', $this->textdomain ) );
			}

			$template          = stripslashes( $_POST['template'] );
			$modify_html_email = ! empty( $_POST['modify_html_email'] ) ? $_POST['modify_html_email'] : 0;

			//Update settings for network or blog
			if ( is_network_admin() || ! is_multisite() ) {

				$htmlemail_settings = isset( $_POST['htmlemail_settings'] ) ? $_POST['htmlemail_settings'] : '';

				update_site_option( 'htmlemail_settings', $htmlemail_settings );
				update_site_option( 'html_template', $template );
				update_site_option( 'modify_html_email', $modify_html_email );
			} else {
				update_option( 'html_template', $template );
				update_option( 'modify_html_email', $modify_html_email );
			}

			echo '<div class="updated"><p>' . esc_html__( 'Success! Your changes were sucessfully saved!', $this->textdomain ) . '</p></div>';
		}
	}
} //End Class

//instantiate the class
$html_email_var = new HTML_emailer();

//Dash Notification Class
include_once( dirname( __FILE__ ) . '/includes/dash-notice/wpmudev-dash-notification.php' );
//Load WPMU DEV Dashboard Notices
global $wpmudev_notices;

$wpmudev_notices[] = array(
	'id'      => 142,
	'name'    => 'HTML Email Templates',
	'screens' => array( 'settings_page_html-template', 'settings_page_html-template-network' )
);