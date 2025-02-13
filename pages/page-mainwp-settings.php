<?php
/**
 * MainWP Settings page
 *
 * This Class handles building/Managing the
 * Settings MainWP DashboardPage & all SubPages.
 *
 * @package MainWP/Settings
 */

namespace MainWP\Dashboard;

/**
 * Class MainWP_Settings
 *
 * @package MainWP\Dashboard
 */
class MainWP_Settings {

	// phpcs:disable Generic.Metrics.CyclomaticComplexity -- complexity.

	/**
	 * Get Class Name
	 *
	 * @return __CLASS__
	 */
	public static function get_class_name() {
		return __CLASS__;
	}

	/**
	 * Public static varable to hold Subpages information.
	 *
	 * @var array $subPages
	 */
	public static $subPages;

	/** Instantiate Hooks for the Settings Page. */
	public static function init() {
		/**
		 * This hook allows you to render the Settings page header via the 'mainwp_pageheader_settings' action.
		 *
		 * This hook is normally used in the same context of 'mainwp_getsubpages_settings'
		 *
		 * @see \MainWP_Settings::render_header
		 */
		add_action( 'mainwp-pageheader-settings', array( self::get_class_name(), 'render_header' ) );

		/**
		 * This hook allows you to render the Settings page footer via the 'mainwp-pagefooter-settings' action.
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-settings'
		 *
		 * @see \MainWP_Settings::render_footer
		 */
		add_action( 'mainwp-pagefooter-settings', array( self::get_class_name(), 'render_footer' ) );

		add_action( 'admin_init', array( self::get_class_name(), 'admin_init' ) );

		add_action( 'mainwp_help_sidebar_content', array( self::get_class_name(), 'mainwp_help_content' ) );
	}

	/** Run the export_sites method that exports the Child Sites .csv file */
	public static function admin_init() {
		self::export_sites();
		if ( isset( $_GET['clearActivationData'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'clear_activation_data' ) ) {
			delete_option( 'mainwp_extensions_api_username' );
			delete_option( 'mainwp_extensions_api_password' );
			delete_option( 'mainwp_extensions_api_save_login' );
			delete_option( 'mainwp_extensions_plan_info' );
			update_option( 'mainwp_extensions_master_api_key', '' );

			$new_extensions = array();
			$extensions     = get_option( 'mainwp_extensions', array() );

			foreach ( $extensions as $ext ) {
				if ( isset( $ext['api'] ) && isset( $ext['apiManager'] ) && ! empty( $ext['apiManager'] ) ) {
					if ( isset( $ext['api_key'] ) ) {
						$ext['api_key'] = '';
					}
					if ( isset( $ext['activation_email'] ) ) {
						$ext['activation_email'] = '';
					}
					if ( isset( $ext['activated_key'] ) ) {
						$ext['activated_key'] = 'Deactivated';
					}

					$act_info = MainWP_Api_Manager::instance()->get_activation_info( $ext['api'] );
					if ( isset( $act_info['api_key'] ) ) {
						$act_info['api_key'] = '';
					}
					if ( isset( $act_info['activation_email'] ) ) {
						$act_info['activation_email'] = '';
					}
					if ( isset( $act_info['activated_key'] ) ) {
						$act_info['activated_key'] = 'Deactivated';
					}
					MainWP_Api_Manager::instance()->set_activation_info( $ext['api'], $act_info );
				}
				$new_extensions[] = $ext;
			}

			MainWP_Utility::update_option( 'mainwp_extensions', $new_extensions );
			update_option( 'mainwp_extensions_all_activation_cached', '' );
			wp_safe_redirect( admin_url( 'admin.php?page=MainWPTools' ) );
			die();
		}
	}

	/**
	 * Instantiate the Settings Menu.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 */
	public static function init_menu() {
		add_submenu_page(
			'mainwp_tab',
			__( 'Settings Global options', 'mainwp' ),
			' <span id="mainwp-Settings">' . __( 'Settings', 'mainwp' ) . '</span>',
			'read',
			'Settings',
			array(
				self::get_class_name(),
				'render',
			)
		);

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'MainWPTools' ) ) {
			add_submenu_page(
				'mainwp_tab',
				__( 'Tools', 'mainwp' ),
				' <div class="mainwp-hidden">' . __( 'Tools', 'mainwp' ) . '</div>',
				'read',
				'MainWPTools',
				array(
					self::get_class_name(),
					'render_mainwp_tools',
				)
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'RESTAPI' ) ) {
			add_submenu_page(
				'mainwp_tab',
				__( 'REST API', 'mainwp' ),
				' <div class="mainwp-hidden">' . __( 'REST API', 'mainwp' ) . '</div>',
				'read',
				'RESTAPI',
				array(
					self::get_class_name(),
					'render_rest_api',
				)
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsAdvanced' ) ) {
			add_submenu_page(
				'mainwp_tab',
				__( 'Advanced Options', 'mainwp' ),
				' <div class="mainwp-hidden">' . __( 'Advanced Options', 'mainwp' ) . '</div>',
				'read',
				'SettingsAdvanced',
				array(
					self::get_class_name(),
					'render_advanced',
				)
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsEmail' ) ) {
			add_submenu_page(
				'mainwp_tab',
				__( 'Email Settings', 'mainwp' ),
				' <div class="mainwp-hidden">' . __( 'Email Settings', 'mainwp' ) . '</div>',
				'read',
				'SettingsEmail',
				array(
					self::get_class_name(),
					'render_email_settings',
				)
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'cache-control' ) ) {
			add_submenu_page(
				'mainwp_tab',
				__( 'Cache Control', 'mainwp' ),
				' <div class="mainwp-hidden">' . __( 'Cache Control', 'mainwp' ) . '</div>',
				'read',
				'cache-control',
				array(
					self::get_class_name(),
					'render_cache_control',
				)
			);
		}

		if ( 1 == get_option( 'mainwp_enable_managed_cr_for_wc' ) ) {
			if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsClientReportsResponder' ) ) {
				add_submenu_page(
					'mainwp_tab',
					__( 'Managed Client Reports', 'mainwp' ),
					' <div class="mainwp-hidden">' . __( 'Managed Client Reports', 'mainwp' ) . '</div>',
					'read',
					'SettingsClientReportsResponder',
					array(
						self::get_class_name(),
						'render_report_responder',
					)
				);
			}
		}

		/**
		 * Settings Subpages
		 *
		 * Filters subpages for the Settings page.
		 *
		 * @since Unknown
		 */
		$sub_pages      = apply_filters_deprecated( 'mainwp-getsubpages-settings', array( array() ), '4.0.7.2', 'mainwp_getsubpages_settings' );  // @deprecated Use 'mainwp_getsubpages_settings' instead.
		self::$subPages = apply_filters( 'mainwp_getsubpages_settings', $sub_pages );

		if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
			foreach ( self::$subPages as $subPage ) {
				if ( MainWP_Menu::is_disable_menu_item( 3, 'Settings' . $subPage['slug'] ) ) {
					continue;
				}
				add_submenu_page( 'mainwp_tab', $subPage['title'], '<div class="mainwp-hidden">' . $subPage['title'] . '</div>', 'read', 'Settings' . $subPage['slug'], $subPage['callback'] );
			}
		}

		self::init_left_menu( self::$subPages );
	}

	/**
	 * Instantiate Settings SubPages Menu.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 */
	public static function init_subpages_menu() {
		?>
		<div id="menu-mainwp-Settings" class="mainwp-submenu-wrapper">
			<div class="wp-submenu sub-open" style="">
				<div class="mainwp_boxout">
					<div class="mainwp_boxoutin"></div>
					<a href="<?php echo admin_url( 'admin.php?page=Settings' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'General Settings', 'mainwp' ); ?></a>
					<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsAdvanced' ) ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=SettingsAdvanced' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Advanced Settings', 'mainwp' ); ?></a>
					<?php } ?>
					<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsEmail' ) ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=SettingsEmail' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Email Settings', 'mainwp' ); ?></a>
					<?php } ?>
					<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'MainWPTools' ) ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=MainWPTools' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Tools', 'mainwp' ); ?></a>
					<?php } ?>					
					<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'RESTAPI' ) ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=RESTAPI' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'REST API', 'mainwp' ); ?></a>
					<?php } ?>

					<?php
					if ( 1 == get_option( 'mainwp_enable_managed_cr_for_wc' ) ) {
						if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsClientReportsResponder' ) ) {
							?>
						<a href="<?php echo admin_url( 'admin.php?page=SettingsClientReportsResponder' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Managed Client Reports', 'mainwp' ); ?></a>
							<?php
						}
					}
					?>
					<?php
					if ( isset( self::$subPages ) && is_array( self::$subPages ) && ( count( self::$subPages ) > 0 ) ) {
						foreach ( self::$subPages as $subPage ) {
							if ( MainWP_Menu::is_disable_menu_item( 3, 'Settings' . $subPage['slug'] ) ) {
								continue;
							}
							?>
							<a href="<?php echo admin_url( 'admin.php?page=Settings' . $subPage['slug'] ); ?>" class="mainwp-submenu"><?php echo esc_html( $subPage['title'] ); ?></a>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Instantiate left menu
	 *
	 * Settings Page & SubPage link data.
	 *
	 * @param array $subPages SubPages Array.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::add_left_menu()
	 * @uses \MainWP\Dashboard\MainWP_Menu::init_subpages_left_menu()
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 */
	public static function init_left_menu( $subPages = array() ) {
		MainWP_Menu::add_left_menu(
			array(
				'title'      => __( 'Settings', 'mainwp' ),
				'parent_key' => 'mainwp_tab',
				'slug'       => 'Settings',
				'href'       => 'admin.php?page=Settings',
				'icon'       => '<i class="cogs icon"></i>',
			),
			1
		);

		$init_sub_subleftmenu = array(
			array(
				'title'      => __( 'General Settings', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=Settings',
				'slug'       => 'Settings',
				'right'      => '',
			),
			array(
				'title'      => __( 'Advanced Settings', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=SettingsAdvanced',
				'slug'       => 'SettingsAdvanced',
				'right'      => '',
			),
			array(
				'title'      => __( 'Email Settings', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=SettingsEmail',
				'slug'       => 'SettingsEmail',
				'right'      => '',
			),
			array(
				'title'      => __( 'Tools', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=MainWPTools',
				'slug'       => 'MainWPTools',
				'right'      => '',
			),
			array(
				'title'      => __( 'REST API', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=RESTAPI',
				'slug'       => 'RESTAPI',
				'right'      => '',
			),
			array(
				'title'      => __( 'Cache Control', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=cache-control',
				'slug'       => 'cache-control',
				'right'      => '',
			),
		);

		if ( 1 == get_option( 'mainwp_enable_managed_cr_for_wc' ) ) {
			$init_sub_subleftmenu[] = array(
				'title'      => __( 'Managed Client Reports', 'mainwp' ),
				'parent_key' => 'Settings',
				'href'       => 'admin.php?page=SettingsClientReportsResponder',
				'slug'       => 'SettingsClientReportsResponder',
				'right'      => '',
			);
		}

		MainWP_Menu::init_subpages_left_menu( $subPages, $init_sub_subleftmenu, 'Settings', 'Settings' );
		foreach ( $init_sub_subleftmenu as $item ) {
			if ( MainWP_Menu::is_disable_menu_item( 3, $item['slug'] ) ) {
				continue;
			}

			MainWP_Menu::add_left_menu( $item, 2 );
		}
	}

	/**
	 * Render Page Header.
	 *
	 * @param string $shownPage The page slug shown at this moment.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 * @uses \MainWP\Dashboard\MainWP_UI::render_top_header()
	 * @uses \MainWP\Dashboard\MainWP_UI::render_page_navigation()
	 */
	public static function render_header( $shownPage = '' ) {

		$params = array(
			'title' => __( 'MainWP Settings', 'mainwp' ),
		);

		MainWP_UI::render_top_header( $params );

		$renderItems = array();

		$renderItems[] = array(
			'title'  => __( 'General Settings', 'mainwp' ),
			'href'   => 'admin.php?page=Settings',
			'active' => ( '' == $shownPage ) ? true : false,
		);

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsAdvanced' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Advanced Settings', 'mainwp' ),
				'href'   => 'admin.php?page=SettingsAdvanced',
				'active' => ( 'Advanced' == $shownPage ) ? true : false,
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsEmail' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Email Settings', 'mainwp' ),
				'href'   => 'admin.php?page=SettingsEmail',
				'active' => ( 'Emails' == $shownPage ) ? true : false,
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'MainWPTools' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Tools', 'mainwp' ),
				'href'   => 'admin.php?page=MainWPTools',
				'active' => ( 'MainWPTools' == $shownPage ) ? true : false,
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'RESTAPI' ) ) {
			$renderItems[] = array(
				'title'  => __( 'REST API', 'mainwp' ),
				'href'   => 'admin.php?page=RESTAPI',
				'active' => ( 'RESTAPI' == $shownPage ) ? true : false,
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'cache-control' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Cache Control', 'mainwp' ),
				'href'   => 'admin.php?page=cache-control',
				'active' => ( 'cache-control' == $shownPage ) ? true : false,
			);
		}

		if ( 1 == get_option( 'mainwp_enable_managed_cr_for_wc' ) ) {
			if ( ! MainWP_Menu::is_disable_menu_item( 3, 'SettingsClientReportsResponder' ) ) {
				$renderItems[] = array(
					'title'  => __( 'Managed Client Reports', 'mainwp' ),
					'href'   => 'admin.php?page=SettingsClientReportsResponder',
					'active' => ( 'SettingsClientReportsResponder' == $shownPage ) ? true : false,
				);
			}
		}

		if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
			foreach ( self::$subPages as $subPage ) {
				if ( MainWP_Menu::is_disable_menu_item( 3, 'Settings' . $subPage['slug'] ) ) {
					continue;
				}

				$item           = array();
				$item['title']  = $subPage['title'];
				$item['href']   = 'admin.php?page=Settings' . $subPage['slug'];
				$item['active'] = ( $subPage['slug'] == $shownPage ) ? true : false;
			}
		}

		MainWP_UI::render_page_navigation( $renderItems );
	}

	/**
	 * Close the HTML container.
	 *
	 * @param string $shownPage The page slug shown at this moment.
	 */
	public static function render_footer( $shownPage ) {
		echo '</div>';
	}

	/**
	 * Method handle_settings_post().
	 *
	 * This class handles the $_POST of Settings Options.
	 *
	 * @uses MainWP_DB::instance()
	 * @uses MainWP_Utility::update_option()
	 *
	 * @return boolean True|False Posts On True.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB_Common::get_user_extension()
	 * @uses \MainWP\Dashboard\MainWP_DB_Common::update_user_extension()
	 * @uses \MainWP\Dashboard\MainWP_System_Utility::is_admin()
	 * @uses \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public static function handle_settings_post() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'Settings' ) ) {
			$userExtension            = MainWP_DB_Common::instance()->get_user_extension();
			$userExtension->pluginDir = '';

			MainWP_DB_Common::instance()->update_user_extension( $userExtension );
			if ( MainWP_System_Utility::is_admin() ) {

				/**
				* Action: mainwp_before_save_general_settings
				*
				* Fires before general settings save.
				*
				* @since 4.1
				*/
				do_action( 'mainwp_before_save_general_settings', $_POST );

				$val = ( ! isset( $_POST['mainwp_pluginAutomaticDailyUpdate'] ) ? 0 : intval( $_POST['mainwp_pluginAutomaticDailyUpdate'] ) );
				MainWP_Utility::update_option( 'mainwp_pluginAutomaticDailyUpdate', $val );
				$val = ( ! isset( $_POST['mainwp_themeAutomaticDailyUpdate'] ) ? 0 : intval( $_POST['mainwp_themeAutomaticDailyUpdate'] ) );
				MainWP_Utility::update_option( 'mainwp_themeAutomaticDailyUpdate', $val );
				$val = ( ! isset( $_POST['mainwp_automaticDailyUpdate'] ) ? 0 : intval( $_POST['mainwp_automaticDailyUpdate'] ) );
				MainWP_Utility::update_option( 'mainwp_automaticDailyUpdate', $val );
				$val = ( ! isset( $_POST['mainwp_show_language_updates'] ) ? 0 : 1 );
				MainWP_Utility::update_option( 'mainwp_show_language_updates', $val );
				$val = ( ! isset( $_POST['mainwp_disable_update_confirmations'] ) ? 0 : intval( $_POST['mainwp_disable_update_confirmations'] ) );
				MainWP_Utility::update_option( 'mainwp_disable_update_confirmations', $val );
				$val = ( ! isset( $_POST['mainwp_backup_before_upgrade'] ) ? 0 : 1 );
				MainWP_Utility::update_option( 'mainwp_backup_before_upgrade', $val );
				$val = ( ! isset( $_POST['mainwp_backup_before_upgrade_days'] ) ? 7 : intval( $_POST['mainwp_backup_before_upgrade_days'] ) );
				MainWP_Utility::update_option( 'mainwp_backup_before_upgrade_days', $val );

				if ( is_plugin_active( 'mainwp-comments-extension/mainwp-comments-extension.php' ) ) {
					MainWP_Utility::update_option( 'mainwp_maximumComments', isset( $_POST['mainwp_maximumComments'] ) ? intval( $_POST['mainwp_maximumComments'] ) : 50 );
				}
				MainWP_Utility::update_option( 'mainwp_timeDailyUpdate', isset( $_POST['mainwp_timeDailyUpdate'] ) ? sanitize_text_field( wp_unslash( $_POST['mainwp_timeDailyUpdate'] ) ) : '' );

				$new_freq = ( isset( $_POST['mainwp_frequencyDailyUpdate'] ) ? intval( $_POST['mainwp_frequencyDailyUpdate'] ) : 1 );
				MainWP_Utility::update_option( 'mainwp_frequencyDailyUpdate', $new_freq );

				$val  = ( isset( $_POST['mainwp_sidebarPosition'] ) ? intval( $_POST['mainwp_sidebarPosition'] ) : 1 );
				$user = wp_get_current_user();
				if ( $user ) {
					update_user_option( $user->ID, 'mainwp_sidebarPosition', $val, true );
				}

				MainWP_Utility::update_option( 'mainwp_numberdays_Outdate_Plugin_Theme', ! empty( $_POST['mainwp_numberdays_Outdate_Plugin_Theme'] ) ? intval( $_POST['mainwp_numberdays_Outdate_Plugin_Theme'] ) : 365 );
				$ignore_http = isset( $_POST['mainwp_ignore_http_response_status'] ) ? sanitize_text_field( wp_unslash( $_POST['mainwp_ignore_http_response_status'] ) ) : '';
				MainWP_Utility::update_option( 'mainwp_ignore_HTTP_response_status', $ignore_http );

				$check_http_response = ( isset( $_POST['mainwp_check_http_response'] ) ? 1 : 0 );
				MainWP_Utility::update_option( 'mainwp_check_http_response', $check_http_response );

				// Handle custom date/time formats.
				if ( ! empty( $_POST['date_format'] ) && isset( $_POST['date_format_custom'] )
					&& '\c\u\s\t\o\m' === wp_unslash( $_POST['date_format'] )
				) {
					$_POST['date_format'] = $_POST['date_format_custom'];
				}

				if ( ! empty( $_POST['time_format'] ) && isset( $_POST['time_format_custom'] )
					&& '\c\u\s\t\o\m' === wp_unslash( $_POST['time_format'] )
				) {
					$_POST['time_format'] = $_POST['time_format_custom'];
				}

				// Map UTC+- timezones to gmt_offsets and set timezone_string to empty.
				if ( ! empty( $_POST['timezone_string'] ) && preg_match( '/^UTC[+-]/', $_POST['timezone_string'] ) ) {
					$_POST['gmt_offset']      = $_POST['timezone_string'];
					$_POST['gmt_offset']      = preg_replace( '/UTC\+?/', '', $_POST['gmt_offset'] );
					$_POST['timezone_string'] = '';
				}

				$options = array(
					'gmt_offset',
					'date_format',
					'time_format',
					'timezone_string',
				);

				foreach ( $options as $option ) {
					$value = null;
					if ( isset( $_POST[ $option ] ) ) {
						$value = $_POST[ $option ];
						if ( ! is_array( $value ) ) {
							$value = trim( $value );
						}
						$value = wp_unslash( $value );
					}
					update_option( $option, $value );
				}

				MainWP_Utility::update_option( 'mainwp_use_favicon', ( ! isset( $_POST['mainwp_use_favicon'] ) ? 0 : 1 ) );

				/**
				* Action: mainwp_after_save_general_settings
				*
				* Fires after save general settings.
				*
				* @since 4.1
				*/
				do_action( 'mainwp_after_save_general_settings', $_POST );
			}

			return true;
		}

		return false;
	}

	/**
	 * Render the MainWP Settings Page.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Monitoring_View
	 * @uses \MainWP\Dashboard\MainWP_Manage_Backups::render_settings()
	 * @uses \MainWP\Dashboard\MainWP_Utility::get_http_codes()
	 */
	public static function render() {
		if ( ! mainwp_current_user_have_right( 'dashboard', 'manage_dashboard_settings' ) ) {
			mainwp_do_not_have_permissions( __( 'manage dashboard settings', 'mainwp' ) );
			return;
		}

		$updatescheck_today_count = get_option( 'mainwp_updatescheck_today_count' );

		self::render_header( '' );
		?>
		<div id="mainwp-general-settings" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-general-settings-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-general-settings-info-message"></i>
					<?php echo sprintf( __( 'Manage MainWP general settings.  For additional help, review this %1$shelp document%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/mainwp-dashboard-settings/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
				<?php if ( isset( $_GET['message'] ) && 'saved' == $_GET['message'] ) : ?>
					<div class="ui green message"><i class="close icon"></i><?php esc_html_e( 'Settings have been saved successfully!', 'mainwp' ); ?></div>
				<?php endif; ?>
				<div class="ui form">
					<form method="POST" action="admin.php?page=Settings" id="mainwp-settings-page-form">
						<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
						<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'Settings' ); ?>" />
						<?php
						/**
						 * Action: mainwp_settings_form_top
						 *
						 * Fires at the top of settings form.
						 *
						 * @since 4.1
						 */
						do_action( 'mainwp_settings_form_top' );
						?>
						<h3 class="ui dividing header"><?php esc_html_e( 'General Settings', 'mainwp' ); ?></h3>
						<?php
						$timeDailyUpdate      = get_option( 'mainwp_timeDailyUpdate' );
						$frequencyDailyUpdate = get_option( 'mainwp_frequencyDailyUpdate' );
						$run_timestamp        = MainWP_System_Cron_Jobs::get_timestamp_from_hh_mm( $timeDailyUpdate );

						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Automatic daily sync time', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set specific time for the automatic daily sync process.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<div class="time-selector">
									<div class="ui input left icon">
										<i class="clock icon"></i>
										<input type="text" current-utc-datetime="<?php echo date( 'Y-m-d H:i:s' ); ?>" sync-time-local-datetime="<?php echo date( 'Y-m-d H:i:s', $run_timestamp ); ?>" local-datetime="<?php echo date( 'Y-m-d H:i:s', MainWP_Utility::get_timestamp() ); // phpcs:ignore -- to get local time. ?>" name="mainwp_timeDailyUpdate" id="mainwp_timeDailyUpdate" value="<?php echo esc_attr( $timeDailyUpdate ); ?>" />
									</div>
								</div>
								<script type="text/javascript">
								jQuery( document ).ready( function() {
									jQuery( '.time-selector' ).calendar( {
										type: 'time',
										ampm: false
									} );
								} );
								</script>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Daily update frequency', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set how often you want your MainWP Dashboard to run the auto update process.', 'mainwp' ); ?>" data-inverted="" data-position="top left" >
								<select name="mainwp_frequencyDailyUpdate" id="mainwp_frequencyDailyUpdate" class="ui dropdown">
									<option value="1" <?php echo ( 1 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Once per day', 'mainwp' ); ?></option>
									<option value="2" <?php echo ( 2 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Twice per day', 'mainwp' ); ?></option>
									<option value="3" <?php echo ( 3 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Three times per day', 'mainwp' ); ?></option>
									<option value="4" <?php echo ( 4 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Four times per day', 'mainwp' ); ?></option>
									<option value="5" <?php echo ( 5 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Five times per day', 'mainwp' ); ?></option>
									<option value="6" <?php echo ( 6 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Six times per day', 'mainwp' ); ?></option>
									<option value="7" <?php echo ( 7 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Seven times per day', 'mainwp' ); ?></option>
									<option value="8" <?php echo ( 8 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Eight times per day', 'mainwp' ); ?></option>
									<option value="9" <?php echo ( 9 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Nine times per day', 'mainwp' ); ?></option>
									<option value="10" <?php echo ( 10 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Ten times per day', 'mainwp' ); ?></option>
									<option value="11" <?php echo ( 11 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Eleven times per day', 'mainwp' ); ?></option>
									<option value="12" <?php echo ( 12 == $frequencyDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Twelve times per day', 'mainwp' ); ?></option>
								</select>
							</div>
						</div>

						<?php
						self::render_timezone_settings();
						self::render_datetime_settings();

						$sidebarPosition = get_user_option( 'mainwp_sidebarPosition' );
						if ( false === $sidebarPosition ) {
							$sidebarPosition = 1;
						}

						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Sidebar position', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Select if you want to show sidebar with option on left or right.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<select name="mainwp_sidebarPosition" id="mainwp_sidebarPosition" class="ui dropdown">
									<option value="1" <?php echo ( 1 == $sidebarPosition ? 'selected' : '' ); ?>><?php esc_html_e( 'Right (default)', 'mainwp' ); ?></option>
									<option value="0" <?php echo ( 0 == $sidebarPosition ? 'selected' : '' ); ?>><?php esc_html_e( 'Left', 'mainwp' ); ?></option>
								</select>
							</div>
						</div>
						<?php echo MainWP_UI::render_screen_options(); ?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Show favicons', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled, your MainWP Dashboard will download and show child sites favicons.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input type="checkbox" name="mainwp_use_favicon" id="mainwp_use_favicon" <?php echo ( ( 1 == get_option( 'mainwp_use_favicon', 1 ) ) ? 'checked="true"' : '' ); ?> />
							</div>
						</div>
						<h3 class="ui dividing header"><?php esc_html_e( 'Updates Settings', 'mainwp' ); ?></h3>
						<?php
						$snAutomaticDailyUpdate            = get_option( 'mainwp_automaticDailyUpdate' );
						$snPluginAutomaticDailyUpdate      = get_option( 'mainwp_pluginAutomaticDailyUpdate' );
						$snThemeAutomaticDailyUpdate       = get_option( 'mainwp_themeAutomaticDailyUpdate' );
						$backup_before_upgrade             = get_option( 'mainwp_backup_before_upgrade' );
						$mainwp_backup_before_upgrade_days = get_option( 'mainwp_backup_before_upgrade_days' );
						if ( empty( $mainwp_backup_before_upgrade_days ) || ! ctype_digit( $mainwp_backup_before_upgrade_days ) ) {
							$mainwp_backup_before_upgrade_days = 7;
						}

						$mainwp_show_language_updates = get_option( 'mainwp_show_language_updates', 1 );

						$update_time         = self::get_websites_automatic_update_time();
						$lastAutomaticUpdate = $update_time['last'];
						$nextAutomaticUpdate = $update_time['next'];

						$enableLegacyBackupFeature  = get_option( 'mainwp_enableLegacyBackupFeature' );
						$primaryBackup              = get_option( 'mainwp_primaryBackup' );
						$disableUpdateConfirmations = get_option( 'mainwp_disable_update_confirmations', 0 );

						$http_error_codes = MainWP_Utility::get_http_codes();
						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Plugin advanced automatic updates', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enable or disable automatic plugins updates. If enabled, MainWP will update only plugins that you have marked as Trusted.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<select name="mainwp_pluginAutomaticDailyUpdate" id="mainwp_pluginAutomaticDailyUpdate" class="ui dropdown">
									<option value="1" <?php echo ( 1 == $snPluginAutomaticDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Install Trusted Updates', 'mainwp' ); ?></option>
									<option value="0" <?php echo ( ( false !== $snPluginAutomaticDailyUpdate && 0 == $snPluginAutomaticDailyUpdate ) || 2 == $snPluginAutomaticDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Disabled', 'mainwp' ); ?></option>
								</select>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Theme advanced automatic updates', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enable or disable automatic themes updates. If enabled, MainWP will update only themes that you have marked as Trusted.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<select name="mainwp_themeAutomaticDailyUpdate" id="mainwp_themeAutomaticDailyUpdate" class="ui dropdown">
									<option value="1" <?php echo ( 1 == $snThemeAutomaticDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Install Trusted Updates', 'mainwp' ); ?></option>
									<option value="0" <?php echo ( ( false !== $snThemeAutomaticDailyUpdate && 0 == $snThemeAutomaticDailyUpdate ) || 2 == $snThemeAutomaticDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Disabled', 'mainwp' ); ?></option>
								</select>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'WP Core advanced automatic updates.', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enable or disable automatic WordPress core updates.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<select name="mainwp_automaticDailyUpdate" id="mainwp_automaticDailyUpdate" class="ui dropdown">
									<option value="1" <?php echo ( 1 == $snAutomaticDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Install Trusted Updates', 'mainwp' ); ?></option>
									<option value="0" <?php echo ( ( false !== $snAutomaticDailyUpdate && 0 == $snAutomaticDailyUpdate ) || 2 == $snAutomaticDailyUpdate ? 'selected' : '' ); ?>><?php esc_html_e( 'Disabled', 'mainwp' ); ?></option>
								</select>
								<div class="ui hidden divider"></div>
								<div class="ui label"><?php esc_html_e( 'Last run: ', 'mainwp' ); ?><?php echo esc_html( $lastAutomaticUpdate ); ?></div>
								<div class="ui label" updatescheck-today-count="<?php echo intval( $updatescheck_today_count ); ?>"><?php esc_html_e( 'Next run: ', 'mainwp' ); ?><?php echo esc_html( $nextAutomaticUpdate ); ?></div>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Show WordPress language updates', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable if you want to manage Translation updates', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="checkbox" name="mainwp_show_language_updates" id="mainwp_show_language_updates" <?php echo ( 1 == $mainwp_show_language_updates ? 'checked="true"' : '' ); ?>/>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Update confirmations', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Choose if you want to disable the popup confirmations when performing updates.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<select name="mainwp_disable_update_confirmations" id="mainwp_disable_update_confirmations" class="ui dropdown">
									<option value="0" <?php echo ( 0 == $disableUpdateConfirmations ? 'selected' : '' ); ?>><?php esc_html_e( 'Enable', 'mainwp' ); ?></option>
									<option value="2" <?php echo ( 2 == $disableUpdateConfirmations ? 'selected' : '' ); ?>><?php esc_html_e( 'Disable', 'mainwp' ); ?></option>
									<option value="1" <?php echo ( 1 == $disableUpdateConfirmations ? 'selected' : '' ); ?>><?php esc_html_e( 'Disable for single updates', 'mainwp' ); ?></option>
								</select>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Check site HTTP response after update', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable if you want your MainWP Dashboard to check child site header response after updates.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input type="checkbox" name="mainwp_check_http_response" id="mainwp_check_http_response" <?php echo ( ( 1 == get_option( 'mainwp_check_http_response', 0 ) ) ? 'checked="true"' : '' ); ?>/>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Ignored HTTP response statuses', 'mainwp' ); ?></label>
							<div class="ten wide column"  data-tooltip="<?php esc_attr_e( 'Select response codes that you want your MainWP Dashboard to ignore.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<div class="ui multiple selection dropdown" init-value="<?php echo ( get_option( 'mainwp_ignore_HTTP_response_status', '' ) ); ?>">
									<input name="mainwp_ignore_http_response_status" type="hidden">
									<i class="dropdown icon"></i>
									<div class="default text"></div>
									<div class="menu">
										<?php
										foreach ( $http_error_codes as $error_code => $label ) {
											?>
											<div class="item" data-value="<?php echo $error_code; ?>"><?php echo $error_code . ' (' . $label . ')'; ?></div>
											<?php
										}
										?>
									</div>
								</div>
							</div>
						</div>
						<?php if ( ( ( $enableLegacyBackupFeature && empty( $primaryBackup ) ) || ( empty( $enableLegacyBackupFeature ) && ! empty( $primaryBackup ) ) ) ) { ?>
						<div class="ui grid field mainwp-parent-toggle">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Require a backup before an update', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled, your MainWP Dashboard will check if full backups exists before updating.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="checkbox" name="mainwp_backup_before_upgrade" id="mainwp_backup_before_upgrade" <?php echo ( 1 == $backup_before_upgrade ? 'checked="true"' : '' ); ?>/>
							</div>
						</div>
						<div class="ui grid field mainwp-child-field" <?php echo ( 1 == $backup_before_upgrade ? '' : 'style="display:none"' ); ?> >
							<label class="six wide column middle aligned"><?php esc_html_e( 'Days without of a full backup tolerance', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set the number of days without of backup tolerance.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_backup_before_upgrade_days" id="mainwp_backup_before_upgrade_days" value="<?php echo esc_attr( $mainwp_backup_before_upgrade_days ); ?>" />
							</div>
						</div>
						<?php } ?>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Abandoned plugins/themes tolerance', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set how many days without an update before plugin or theme will be considered as abandoned.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_numberdays_Outdate_Plugin_Theme" id="mainwp_numberdays_Outdate_Plugin_Theme" value="<?php echo ( ( false === get_option( 'mainwp_numberdays_Outdate_Plugin_Theme' ) ) ? 365 : get_option( 'mainwp_numberdays_Outdate_Plugin_Theme' ) ); ?>"/>
							</div>
						</div>
						<?php MainWP_Monitoring_View::render_settings(); ?>					
						<?php MainWP_Manage_Backups::render_settings(); ?>
						<?php
						/**
						 * Action: mainwp_settings_form_bottom
						 *
						 * Fires at the bottom of settings form.
						 *
						 * @since 4.1
						 */
						do_action( 'mainwp_settings_form_bottom' );
						?>
						<div class="ui divider"></div>
						<input type="submit" name="submit" id="submit" class="ui button green big" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>"/>
						<div style="clear:both"></div>
					</form>
				</div>
			</div>
		<?php
		self::render_footer( '' );
	}

	/**
	 * Render Timezone settings.
	 */
	public static function render_timezone_settings() {

		$current_offset  = get_option( 'gmt_offset' );
		$tzstring        = get_option( 'timezone_string' );
		$check_zone_info = true;

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
			$tzstring = '';
		}

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
			$check_zone_info = false;
			if ( 0 == $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		$timezone_format = _x( 'Y-m-d H:i:s', 'timezone date format' );

		?>
	<div class="ui grid field">
			<label class="six wide column middle aligned"><?php esc_html_e( 'Timezone', 'mainwp' ); ?></label>			
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Choose either a city in the same timezone as you or a %s (Coordinated Universal Time) time offset.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
				<select id="timezone_string" class="ui dropdown" name="timezone_string" aria-describedby="timezone-description">
				<?php echo wp_timezone_choice( $tzstring, get_user_locale() ); ?>
				</select>
				<div class="ui hidden fitted divider"></div>
				<div class="ui secondary segment">
					<?php printf( __( 'Universal time is %s.' ), '<code>' . date_i18n( $timezone_format, false, true ) . '</code>' ); ?>
					<div class="ui hidden fitted divider"></div>
				<?php if ( get_option( 'timezone_string' ) || ! empty( $current_offset ) ) : ?>
						<?php printf( __( 'Local time is %s.' ), '<code>' . date_i18n( $timezone_format ) . '</code>' ); ?>
						<div class="ui hidden fitted divider"></div>
				<?php endif; ?>
				<?php if ( $check_zone_info && $tzstring ) : ?>
						<?php
						$now = new \DateTime( 'now', new \DateTimeZone( $tzstring ) );
						$dst = (bool) $now->format( 'I' );

						if ( $dst ) {
							_e( 'This timezone is currently in daylight saving time.', 'mainwp' );
						} else {
							_e( 'This timezone is currently in standard time.', 'mainwp' );
						}
						?>
						<div class="ui hidden fitted divider"></div>
						<?php
						if ( in_array( $tzstring, timezone_identifiers_list(), true ) ) {
							$transitions = timezone_transitions_get( timezone_open( $tzstring ), time() );

							if ( ! empty( $transitions[1] ) ) {
								echo ' ';
								$message = $transitions[1]['isdst'] ? __( 'Daylight saving time begins on: %s.', 'mainwp' ) : __( 'Standard time begins on: %s.', 'mainwp' );
								printf( $message, '<code>' . wp_date( __( 'F j, Y' ) . ' ' . __( 'g:i a' ), $transitions[1]['ts'] ) . '</code>' );
							} else {
								_e( 'This timezone does not observe daylight saving time.', 'mainwp' );
							}
						}
						?>
			<?php endif; ?>
			</div>
		</div>
		</div>
		<?php
	}

	/**
	 * Render Date/Time settings.
	 */
	public static function render_datetime_settings() {
		?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php esc_html_e( 'Date Format', 'mainwp' ); ?></label>
			<div class="ten wide column fieldset-wrapper" data-tooltip="<?php esc_attr_e( 'Date Format.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
			<?php
				/**
				 * Filters the default date formats.
				 *
				 * @since 2.7.0
				 * @since 4.0.0 Added ISO date standard YYYY-MM-DD format.
				 *
				 * @param string[] $default_date_formats Array of default date formats.
				 */
				$date_formats = array_unique( apply_filters( 'date_formats', array( __( 'F j, Y' ), 'Y-m-d', 'm/d/Y', 'd/m/Y' ) ) );

				$custom = true;

			foreach ( $date_formats as $format ) {
				echo "\t<label><input type='radio' name='date_format' value='" . esc_attr( $format ) . "'";
				if ( get_option( 'date_format' ) === $format ) { // checked() uses "==" rather than "===".
					echo " checked='checked'";
					$custom = false;
				}
				echo ' /> <span class="date-time-text format-i18n">' . date_i18n( $format ) . '</span><code>' . esc_html( $format ) . "</code></label><br />\n";
			}

				echo '<label><input type="radio" name="date_format" id="date_format_custom_radio" value="\c\u\s\t\o\m"';
				checked( $custom );
				echo '/> <span class="date-time-text date-time-custom-text">' . __( 'Custom:' ) . '<span class="screen-reader-text"> ' . __( 'enter a custom date format in the following field' ) . '</span></span></label>' .
					'<label for="date_format_custom" class="screen-reader-text">' . __( 'Custom date format:' ) . '</label>' .
					'<input type="text" name="date_format_custom" id="date_format_custom" value="' . esc_attr( get_option( 'date_format' ) ) . '" class="small-text" />' .
					'<br />' .
					'<em><strong>' . __( 'Preview:' ) . '</strong> <span class="example">' . date_i18n( get_option( 'date_format' ) ) . '</span>' .
					"<span class='spinner'></span>\n" . '</em>';
			?>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned"><?php esc_html_e( 'Time format', 'mainwp' ); ?></label>
		<div class="ten wide column fieldset-wrapper" data-tooltip="<?php esc_attr_e( 'Select preferred time format.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
		<?php
				/**
				 * Filters the default time formats.
				 *
				 * @since 2.7.0
				 *
				 * @param string[] $default_time_formats Array of default time formats.
				 */
				$time_formats = array_unique( apply_filters( 'time_formats', array( __( 'g:i a' ), 'g:i A', 'H:i' ) ) );

				$custom = true;

		foreach ( $time_formats as $format ) {
			echo "\t<label><input type='radio' name='time_format' value='" . esc_attr( $format ) . "'";
			if ( get_option( 'time_format' ) === $format ) { // checked() uses "==" rather than "===".
				echo " checked='checked'";
				$custom = false;
			}
			echo ' /> <span class="date-time-text format-i18n">' . date_i18n( $format ) . '</span><code>' . esc_html( $format ) . "</code></label><br />\n";
		}
			echo '<label><input type="radio" name="time_format" id="time_format_custom_radio" value="\c\u\s\t\o\m"';
			checked( $custom );
			echo '/> <span class="date-time-text date-time-custom-text">' . __( 'Custom:' ) . '<span class="screen-reader-text"> ' . __( 'enter a custom time format in the following field' ) . '</span></span></label>' .
				'<label for="time_format_custom" class="screen-reader-text">' . __( 'Custom time format:' ) . '</label>' .
				'<input type="text" name="time_format_custom" id="time_format_custom" value="' . esc_attr( get_option( 'time_format' ) ) . '" class="small-text" />' .
				'<br />' .
			'<em><strong>' . __( 'Preview:' ) . '</strong> <span class="example">' . date_i18n( get_option( 'time_format' ) ) . '</span>' .
			"<span class='spinner'></span>\n" . '</em>';
		?>
		</div>
	</div>
	<script type="text/javascript">
			jQuery(document).ready( function($) {
				$( 'input[name="date_format"]' ).on( 'click', function() {
					if ( 'date_format_custom_radio' !== $( this ).attr( 'id' ) )
						$( 'input[name="date_format_custom"]' ).val( $( this ).val() ).closest( '.fieldset-wrapper' ).find( '.example' ).text( $( this ).parent( 'label' ).children( '.format-i18n' ).text() );
				} );

				$( 'input[name="date_format_custom"]' ).on( 'click input', function() {
					$( '#date_format_custom_radio' ).prop( 'checked', true );
				} );

				$( 'input[name="time_format"]' ).on( 'click', function() {
					if ( 'time_format_custom_radio' !== $(this).attr( 'id' ) )
						$( 'input[name="time_format_custom"]' ).val( $( this ).val() ).closest( '.fieldset-wrapper' ).find( '.example' ).text( $( this ).parent( 'label' ).children( '.format-i18n' ).text() );
				} );

				$( 'input[name="time_format_custom"]' ).on( 'click input', function() {
					$( '#time_format_custom_radio' ).prop( 'checked', true );
				} );

				$( 'input[name="date_format_custom"], input[name="time_format_custom"]' ).on( 'input', function() {
					var format = $( this ),
						fieldset = format.closest( '.fieldset-wrapper' ),
						example = fieldset.find( '.example' ),
						spinner = fieldset.find( '.spinner' );

					// Debounce the event callback while users are typing.
					clearTimeout( $.data( this, 'timer' ) );
					$( this ).data( 'timer', setTimeout( function() {
						// If custom date is not empty.
						if ( format.val() ) {
							spinner.addClass( 'is-active' );

							$.post( ajaxurl, {
								action: 'date_format_custom' === format.attr( 'name' ) ? 'date_format' : 'time_format',
								date 	: format.val()
							}, function( d ) { spinner.removeClass( 'is-active' ); example.text( d ); } );
						}
					}, 500 ) );
				} );
			});
		</script>
		<?php
	}

	/**
	 * Method get_websites_automatic_update_time()
	 *
	 * Get websites automatic update time.
	 *
	 * @return mixed array
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_last_automatic_sync()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_count_where_dts_automatic_sync_smaller_then_start()
	 * @uses \MainWP\Dashboard\MainWP_Utility::format_timestamp()
	 */
	public static function get_websites_automatic_update_time() {
		$lastAutomaticUpdate    = MainWP_DB::instance()->get_websites_last_automatic_sync();
		$lasttimeAutomatic      = get_option( 'mainwp_updatescheck_last_timestamp' );
		$lasttimeStartAutomatic = get_option( 'mainwp_updatescheck_start_last_timestamp' );
		$local_timestamp        = MainWP_Utility::get_timestamp();
		$running                = get_option( 'mainwp_cron_checksites_running' );

		if ( empty( $lasttimeStartAutomatic ) && ! empty( $lasttimeAutomatic ) ) {
			$lasttimeStartAutomatic = $lasttimeAutomatic;
		}

		if ( 0 == $lastAutomaticUpdate ) {
			$nextAutomaticUpdate = __( 'Any minute', 'mainwp' );
		} elseif ( 'yes' == $running && ( 0 < MainWP_DB::instance()->get_websites_count_where_dts_automatic_sync_smaller_then_start( $lasttimeStartAutomatic ) || 0 < MainWP_DB::instance()->get_websites_check_updates_count( $lasttimeStartAutomatic ) ) ) {
			$nextAutomaticUpdate = __( 'Processing your websites.', 'mainwp' );
		} else {
			$next_time = MainWP_System_Cron_Jobs::get_next_time_automatic_update_to_show();
			if ( $next_time < $local_timestamp + 5 * MINUTE_IN_SECONDS ) {
				$nextAutomaticUpdate = __( 'Any minute', 'mainwp' );
			} else {
				$nextAutomaticUpdate = MainWP_Utility::format_timestamp( $next_time );
			}
		}

		if ( 0 == $lastAutomaticUpdate ) {
			$lastAutomaticUpdate = __( 'Never', 'mainwp' );
		} else {
			$lastAutomaticUpdate = MainWP_Utility::format_timestamp( $lastAutomaticUpdate );
		}

		return array(
			'last' => $lastAutomaticUpdate,
			'next' => $nextAutomaticUpdate,
		);
	}

	/**
	 * Returns false or the location of the OpenSSL Lib File.
	 *
	 * @return mixed false|opensslLibLocation
	 *
	 * @uses \MainWP\Dashboard\MainWP_Server_Information_Handler::is_openssl_config_warning()
	 */
	public static function show_openssl_lib_config() {
		if ( MainWP_Server_Information_Handler::is_openssl_config_warning() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check MainWP Installation Hosting Type & System Type.
	 *
	 * To compatible.
	 *
	 * @return boolean true|false
	 */
	public static function is_local_window_config() {
		$setup_hosting_type = get_option( 'mwp_setup_installationHostingType' );
		$setup_system_type  = get_option( 'mwp_setup_installationSystemType' );
		if ( 2 == $setup_hosting_type && 3 == $setup_system_type ) {
			return true;
		}
		return false;
	}

	/**
	 * Render Advanced Options Subpage.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public static function render_advanced() {
		if ( ! mainwp_current_user_have_right( 'dashboard', 'manage_dashboard_settings' ) ) {
			mainwp_do_not_have_permissions( __( 'manage dashboard settings', 'mainwp' ) );
			return;
		}

		if ( isset( $_POST['submit'] ) && isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'SettingsAdvanced' ) ) {

			/**
			* Action: mainwp_before_save_advanced_settings
			*
			* Fires before save advanced settings.
			*
			* @since 4.1
			*/
			do_action( 'mainwp_before_save_advanced_settings', $_POST );

			MainWP_Utility::update_option( 'mainwp_maximumRequests', ! empty( $_POST['mainwp_maximumRequests'] ) ? intval( $_POST['mainwp_maximumRequests'] ) : 4 );
			MainWP_Utility::update_option( 'mainwp_minimumDelay', ! empty( $_POST['mainwp_minimumDelay'] ) ? intval( $_POST['mainwp_minimumDelay'] ) : 200 );
			MainWP_Utility::update_option( 'mainwp_maximumIPRequests', ! empty( $_POST['mainwp_maximumIPRequests'] ) ? intval( $_POST['mainwp_maximumIPRequests'] ) : 1 );
			MainWP_Utility::update_option( 'mainwp_minimumIPDelay', ! empty( $_POST['mainwp_minimumIPDelay'] ) ? intval( $_POST['mainwp_minimumIPDelay'] ) : 1000 );
			MainWP_Utility::update_option( 'mainwp_maximumSyncRequests', ! empty( $_POST['mainwp_maximumSyncRequests'] ) ? intval( $_POST['mainwp_maximumSyncRequests'] ) : 8 );
			MainWP_Utility::update_option( 'mainwp_maximumInstallUpdateRequests', ! empty( $_POST['mainwp_maximumInstallUpdateRequests'] ) ? intval( $_POST['mainwp_maximumInstallUpdateRequests'] ) : 3 );
			MainWP_Utility::update_option( 'mainwp_sslVerifyCertificate', isset( $_POST['mainwp_sslVerifyCertificate'] ) ? 1 : 0 );
			MainWP_Utility::update_option( 'mainwp_forceUseIPv4', isset( $_POST['mainwp_forceUseIPv4'] ) ? 1 : 0 );
			MainWP_Utility::update_option( 'mainwp_wp_cron', ( ! isset( $_POST['mainwp_options_wp_cron'] ) ? 0 : 1 ) );
			MainWP_Utility::update_option( 'mainwp_optimize', ( ! isset( $_POST['mainwp_optimize'] ) ? 0 : 1 ) );

			if ( isset( $_POST['mainwp_openssl_lib_location'] ) ) {
				$openssl_loc = ! empty( $_POST['mainwp_openssl_lib_location'] ) ? sanitize_text_field( wp_unslash( $_POST['mainwp_openssl_lib_location'] ) ) : '';
				MainWP_Utility::update_option( 'mainwp_opensslLibLocation', $openssl_loc );
				$setup_conf_loc = get_option( 'mwp_setup_opensslLibLocation' );
				if ( ! empty( $setup_conf_loc ) ) {
					delete_option( 'mwp_setup_opensslLibLocation' );  // delete old version settings.
					delete_option( 'mwp_setup_installationHostingType' );
					delete_option( 'mwp_setup_installationSystemType' );
				}
			}

			/**
			* Action: mainwp_after_save_advanced_settings
			*
			* Fires after advanced settings save.
			*
			* @since 4.1
			*/
			do_action( 'mainwp_after_save_advanced_settings', $_POST );
		}
		self::render_header( 'Advanced' );
		?>

		<div id="mainwp-advanced-settings" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-advanced-settings-info-notice' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-advanced-settings-info-notice"></i>
					<?php echo __( 'Set how many requests are performed at once and delay between requests in order to optimize your MainWP Dashboard performance.  Both Cross IP and IP Settings handle the majority of work connecting to your Child sites, while the sync, update, and installation request have specialized options under the Frontend Requests Settings section.', 'mainwp' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( isset( $_POST['submit'] ) && isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'SettingsAdvanced' ) ) : ?>
				<div class="ui green message"><i class="close icon"></i><?php esc_html_e( 'Settings have been saved successfully!', 'mainwp' ); ?></div>
				<?php endif; ?>
				<div class="ui form">
					<form method="POST" action="">
						<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
						<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'SettingsAdvanced' ); ?>" />
						<?php

						/**
						 * Action: mainwp_advanced_settings_form_top
						 *
						 * Fires at the top of advanced settings form.
						 *
						 * @since 4.1
						 */
						do_action( 'mainwp_advanced_settings_form_top' );

						if ( self::show_openssl_lib_config() ) {
							$openssl_loc = MainWP_System_Utility::get_openssl_conf();
							?>
							<h3 class="ui dividing header">
								<?php esc_html_e( 'OpenSSL Settings', 'mainwp' ); ?>
								<div class="sub header"><?php esc_html_e( 'Due to bug with PHP on some servers it is required to set the OpenSSL Library location so MainWP Dashboard can connect to your child sites.', 'mainwp' ); ?></div>
							</h3>
								<div class="ui grid field">
									<label class="six wide column middle aligned"><?php esc_html_e( 'OpenSSL.cnf location', 'mainwp' ); ?></label>
									<div class="ten wide column ui field">
										<input type="text" name="mainwp_openssl_lib_location" value="<?php echo esc_html( $openssl_loc ); ?>">
										<em><?php esc_html_e( 'If your openssl.cnf file is saved to a different path from what is entered please enter your exact path.', 'mainwp' ); ?> <?php echo sprintf( __( 'If you are not sure how to find the openssl.cnf location, please %1$scheck this help document%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/how-to-find-the-openssl-cnf-file/" target="_blank">', '</a>' ); ?></em>
										<em><?php esc_html_e( 'If you have confirmed the placement of your openssl.cnf and are still receiving an error banner, click the "Error Fixed" button to dismiss it.', 'mainwp' ); ?></em>
									</div>
								</div>
					<?php } ?>

						<h3 class="ui dividing header"><?php esc_html_e( 'Cross IP Settings', 'mainwp' ); ?></h3>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Maximum simultaneous requests', 'mainwp' ); ?></label>
							<div class="ten wide column ui right labeled input" data-tooltip="<?php esc_attr_e( 'If too many requests are sent out, they will begin to time out. This causes your sites to be shown as offline while they are up and running.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_maximumRequests" id="mainwp_maximumRequests" value="<?php echo ( ( false === get_option( 'mainwp_maximumRequests' ) ) ? 4 : get_option( 'mainwp_maximumRequests' ) ); ?>"/><div class="ui basic label"><?php esc_html_e( 'Default: 4', 'mainwp' ); ?></div>
							</div>
						</div>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Minimum delay between requests', 'mainwp' ); ?></label>
							<div class="ten wide column ui right labeled input" data-tooltip="<?php esc_attr_e( 'This option allows you to control minimum time delay between two requests.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_minimumDelay" id="mainwp_minimumDelay" value="<?php echo ( ( false === get_option( 'mainwp_minimumDelay' ) ) ? 200 : get_option( 'mainwp_minimumDelay' ) ); ?>"/><div class="ui basic label"><?php esc_html_e( 'Default: 200', 'mainwp' ); ?></div>
							</div>
						</div>

						<h3 class="ui dividing header"><?php esc_html_e( 'Per IP Settings', 'mainwp' ); ?></h3>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Maximum simultaneous requests per IP', 'mainwp' ); ?></label>
							<div class="ten wide column ui right labeled input"  data-tooltip="<?php esc_attr_e( 'If too many requests are sent out, they will begin to time out. This causes your sites to be shown as offline while they are up and running.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_maximumIPRequests" id="mainwp_maximumIPRequests" value="<?php echo ( ( false === get_option( 'mainwp_maximumIPRequests' ) ) ? 1 : get_option( 'mainwp_maximumIPRequests' ) ); ?>"/><div class="ui basic label"><?php esc_html_e( 'Default: 1', 'mainwp' ); ?></div>
							</div>
						</div>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Minimum delay between requests to the same IP', 'mainwp' ); ?></label>
							<div class="ten wide column ui right labeled input" data-tooltip="<?php esc_attr_e( 'This option allows you to control minimum time delay between two requests.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_minimumIPDelay" id="mainwp_minimumIPDelay" value="<?php echo ( ( false === get_option( 'mainwp_minimumIPDelay' ) ) ? 1000 : get_option( 'mainwp_minimumIPDelay' ) ); ?>"/><div class="ui basic label"><?php esc_html_e( 'Default: 1000', 'mainwp' ); ?></div>
							</div>
						</div>

						<h3 class="ui dividing header"><?php esc_html_e( 'Frontend Request Settings', 'mainwp' ); ?></h3>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Maximum simultaneous sync requests', 'mainwp' ); ?></label>
							<div class="ten wide column ui right labeled input" data-tooltip="<?php esc_attr_e( 'This option allows you to control how many sites your MainWP Dashboard should sync at once.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_maximumSyncRequests" id="mainwp_maximumSyncRequests" value="<?php echo ( ( false === get_option( 'mainwp_maximumSyncRequests' ) ) ? 8 : get_option( 'mainwp_maximumSyncRequests' ) ); ?>"/><div class="ui basic label"><?php esc_html_e( 'Default: 8', 'mainwp' ); ?></div>
							</div>
						</div>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Maximum simultaneous install and update requests', 'mainwp' ); ?></label>
							<div class="ten wide column ui right labeled input"  data-tooltip="<?php esc_attr_e( 'This option allows you to control how many update and install requests your MainWP Dashboard should process at once.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="mainwp_maximumInstallUpdateRequests" id="mainwp_maximumInstallUpdateRequests" value="<?php echo ( ( false === get_option( 'mainwp_maximumInstallUpdateRequests' ) ) ? 3 : get_option( 'mainwp_maximumInstallUpdateRequests' ) ); ?>"/><div class="ui basic label"><?php esc_html_e( 'Default: 3', 'mainwp' ); ?></div>
							</div>
						</div>

						<h3 class="ui dividing header"><?php esc_html_e( 'Miscellaneous Settings', 'mainwp' ); ?></h3>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Optimize for shared hosting or big networks', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox"  data-tooltip="<?php esc_attr_e( 'If enabled, your MainWP Dashboard will cache updates for faster loading.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input type="checkbox" name="mainwp_optimize" id="mainwp_optimize" <?php echo ( ( 1 == get_option( 'mainwp_optimize', 0 ) ) ? 'checked="true"' : '' ); ?> /><label><?php esc_html_e( 'Default: Off', 'mainwp' ); ?></label>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Use WP Cron', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Disabling this option will disable the WP Cron so all scheduled events will stop working.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="checkbox" name="mainwp_options_wp_cron" id="mainwp_options_wp_cron" <?php echo ( ( 1 == get_option( 'mainwp_wp_cron' ) ) || ( false === get_option( 'mainwp_wp_cron' ) ) ? 'checked="true"' : '' ); ?>/><label><?php esc_html_e( 'Default: On', 'mainwp' ); ?></label>
							</div>
						</div>
						<div class="ui grid field" >
							<label class="six wide column middle aligned"><?php esc_html_e( 'Verify SSL certificate', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled, your MainWP Dashboard will verify the SSL Certificate on your Child Site (if exists) while connecting the Child Site.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="checkbox" name="mainwp_sslVerifyCertificate" id="mainwp_sslVerifyCertificate" value="checked" <?php echo ( ( false === get_option( 'mainwp_sslVerifyCertificate' ) ) || ( 1 == get_option( 'mainwp_sslVerifyCertificate' ) ) ) ? 'checked="checked"' : ''; ?>/><label><?php esc_html_e( 'Default: On', 'mainwp' ); ?></label>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Force IPv4', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox"  data-tooltip="<?php esc_attr_e( 'Enable if you want to force your MainWP Dashboard to use IPv4 while tryig to connect child sites.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input type="checkbox" name="mainwp_forceUseIPv4" id="mainwp_forceUseIPv4" value="checked" <?php echo ( 1 == get_option( 'mainwp_forceUseIPv4' ) ) ? 'checked="checked"' : ''; ?>/><label><?php esc_html_e( 'Default: Off', 'mainwp' ); ?></label>
							</div>
						</div>
						<?php

						/**
						 * Action: mainwp_advanced_settings_form_bottom
						 *
						 * Fires at the bottom of advanced settings form.
						 *
						 * @since 4.1
						 */
						do_action( 'mainwp_advanced_settings_form_bottom' );

						?>
						<div class="ui divider"></div>
						<input type="submit" name="submit" id="submit" class="ui green big button" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>"/>
					</form>
				</div>
			</div>
		<?php
		self::render_footer( 'Advanced' );
	}

	/**
	 * Render MainWP Tools SubPage.
	 *
	 * @uses \MainWP\Dashboard\MainWP_UI::render_screen_options()
	 */
	public static function render_mainwp_tools() {
		if ( ! mainwp_current_user_have_right( 'dashboard', 'manage_dashboard_settings' ) ) {
			mainwp_do_not_have_permissions( __( 'manage dashboard settings', 'mainwp' ) );

			return;
		}

		self::render_header( 'MainWPTools' );

		?>
		<div id="mainwp-tools-settings" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-tools-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-tools-info-message"></i>
					<?php echo sprintf( __( 'Use MainWP tools to adjust your MainWP Dashboard to your needs and perform specific actions when needed.  For additional help, review this %1$shelp document%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/mainwp-dashboard-settings/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
				<?php if ( isset( $_POST['submit'] ) && isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'MainWPTools' ) ) : ?>
					<div class="ui green message"><i class="close icon"></i><?php esc_html_e( 'Settings have been saved successfully!', 'mainwp' ); ?></div>
				<?php endif; ?>
			<?php if ( isset( $_POST['mainwp_restore_info_messages'] ) && isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'MainWPTools' ) ) : ?>
				<div class="ui green message"><i class="close icon"></i><?php esc_html_e( 'Info messages have been restored successfully!', 'mainwp' ); ?></div>
			<?php endif; ?>
				<div class="ui form">
					<form method="POST" action="">
						<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
						<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'MainWPTools' ); ?>" />
						<h3 class="ui dividing header"><?php esc_html_e( 'MainWP Dashboard Tools', 'mainwp' ); ?></h3>
						<?php
						/**
						 * Action: mainwp_tools_form_top
						 *
						 * Fires at the top of MainWP tools form.
						 *
						 * @since 4.1
						 */
						do_action( 'mainwp_tools_form_top' );

						$show_qsw = apply_filters( 'mainwp_show_qsw', true );

						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Force your MainWP Dashboard to establish a new connection', 'mainwp' ); ?></label>
							<div class="ten wide column"  data-tooltip="<?php esc_attr_e( 'Force your MainWP Dashboard to reconnect with your child sites. Only needed if suggested by MainWP Support.', 'mainwp' ); ?>" data-inverted="" data-position="top left"><input type="button" name="" id="force-destroy-sessions-button" class="ui green basic button" value="<?php esc_attr_e( 'Re-establish Connections', 'mainwp' ); ?>" data-tooltip="<?php esc_attr_e( 'Forces your dashboard to reconnect with your child sites. This feature will log out any currently logged in users on the Child sites and require them to re-log in. Only needed if suggested by MainWP Support.', 'mainwp' ); ?>" data-inverted=""/></div>
						</div>
						<?php if ( $show_qsw ) { ?> 
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Start the MainWP Quick Setup Wizard', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Click this button to start the Quick Setup Wizard', 'mainwp' ); ?>" data-inverted="" data-position="top left"><a href="admin.php?page=mainwp-setup" class="ui green button basic" ><?php esc_html_e( 'Start Quick Setup Wizard', 'mainwp' ); ?></a></div>
						</div>
						<?php } ?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Export child sites to CSV file', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Click this button to export all connected sites to a CSV file.', 'mainwp' ); ?>" data-inverted="" data-position="top left"><a href="admin.php?page=MainWPTools&doExportSites=yes&_wpnonce=<?php echo wp_create_nonce( 'export_sites' ); ?>" class="ui button green basic"><?php esc_html_e( 'Export Child Sites', 'mainwp' ); ?></a></div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Import child sites', 'mainwp' ); ?></label>
							<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Click this button to import websites to your MainWP Dashboard.', 'mainwp' ); ?>" data-inverted="" data-position="top left"><a href="admin.php?page=managesites&do=bulknew" class="ui button green basic"><?php esc_html_e( 'Import Child Sites', 'mainwp' ); ?></a></div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Disconnect all child sites', 'mainwp' ); ?></label>
						<div class="ten wide column" id="mainwp-disconnect-sites-tool" data-content="<?php esc_attr_e( 'This will function will break the connection and leave the MainWP Child plugin active and which makes your sites vulnerable. Use only if you attend to reconnect site to the same or a different dashboard right away.', 'mainwp' ); ?>" data-variation="inverted" data-position="top left">
						<a href="admin.php?page=MainWPTools&disconnectSites=yes&_wpnonce=<?php echo wp_create_nonce( 'disconnect_sites' ); ?>" onclick="mainwp_tool_disconnect_sites(); return false;" class="ui button green basic"><?php esc_html_e( 'Disconnect Websites', 'mainwp' ); ?></a>
					</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Delete extensions API Activation data', 'mainwp' ); ?></label>
							<div class="ten wide column" id="mainwp-clear-activation-data" data-content="<?php esc_attr_e( 'Delete extensions API activation data. This will not affect extensions settings, it just removes API activation data.', 'mainwp' ); ?>" data-variation="inverted" data-position="top left">
								<a href="admin.php?page=MainWPTools&clearActivationData=yes&_wpnonce=<?php echo wp_create_nonce( 'clear_activation_data' ); ?>" onclick="mainwp_tool_clear_activation_data(this); return false;" class="ui button green basic"><?php esc_html_e( 'Delete Extensions API Activation Data', 'mainwp' ); ?></a>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Turn off brag button', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled, Twitter messages will be turn off.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input type="checkbox" name="mainwp_hide_twitters_message" id="mainwp_hide_twitters_message" <?php echo ( ( 1 == get_option( 'mainwp_hide_twitters_message', 0 ) ) ? 'checked="true"' : '' ); ?> />
							</div>
						</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php esc_html_e( 'Restore all info messages', 'mainwp' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Click this button to restore all info messages in your MainWP Dashboard and Extensions.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
							<input type="submit" name="mainwp_restore_info_messages" id="mainwp_restore_info_messages" class="ui button" value="<?php esc_attr_e( 'Restore Info Messages', 'mainwp' ); ?>"/>
						</div>
					</div>
						<?php
						$enabled_lcr = ( 1 == get_option( 'mainwp_enable_managed_cr_for_wc' ) ) ? true : false;
						?>
						<div class="ui grid field" <?php echo $enabled_lcr ? '' : 'style="display:none"'; ?>>
							<label class="six wide column middle aligned"><?php esc_html_e( 'Enable Managed Client Reports for WooCommerce', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable Managed Client Reports for WooCommerce', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="checkbox" name="enable_managed_cr_for_wc" <?php echo $enabled_lcr ? 'checked="true"' : ''; ?> />
							</div>
						</div>
						<?php
						/**
						 * Action: mainwp_tools_form_bottom
						 *
						 * Fires at the bottom of mainwp tools form.
						 *
						 * @since 4.1
						 */
						do_action( 'mainwp_tools_form_bottom' );
						?>
						<div class="ui divider"></div>
						<input type="submit" name="submit" id="submit" class="ui green big button" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>"/>
					</form>
				</div>
			</div>
			<script type="text/javascript">
				jQuery( '#mainwp-disconnect-sites-tool' ).popup();
				jQuery( '#mainwp-clear-activation-data' ).popup();
			</script>
		<?php
		self::render_footer( 'MainWPTools' );
	}


	/** Render REST API SubPage */
	public static function render_rest_api() {
		if ( ! mainwp_current_user_have_right( 'dashboard', 'manage_dashboard_settings' ) ) {
			mainwp_do_not_have_permissions( __( 'manage dashboard settings', 'mainwp' ) );

			return;
		}

		self::render_header( 'RESTAPI' );

		?>
		<div id="rest-api-settings" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-rest-api-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-rest-api-info-message"></i>
					<?php echo sprintf( __( 'Enable the MainWP REST API functionality and generate API credentials.  Check this %1$shelp document%2$s to see all available endpoints.', 'mainwp' ), '<a href="https://mainwp.dev/rest-api/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
				<?php if ( isset( $_POST['submit'] ) && isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'RESTAPI' ) ) : ?>
					<div class="ui green message"><i class="close icon"></i><?php esc_html_e( 'Settings have been saved successfully!', 'mainwp' ); ?></div>
				<?php endif; ?>
				<div id="api-credentials-created" style="display: none;" class="ui green message"><i class="close icon"></i><?php esc_html_e( 'API credentials have been successfully generated. Please copy the consumer key and secret now as after you leave this page the credentials will no longer be accessible. You can retrieve new credentials any time by clicking the "Generate new API credentials" button below.', 'mainwp' ); ?></div>
				<div class="ui form">
					<form method="POST" action="">
						<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
						<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'RESTAPI' ); ?>" />
						<?php
						/**
						 * Action: rest_api_form_top
						 *
						 * Fires at the top of REST API form.
						 *
						 * @since 4.1
						 */
						do_action( 'rest_api_form_top' );
						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Enable REST API', 'mainwp' ); ?></label>
							<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled, the REST API will be activated.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input type="checkbox" name="mainwp_enable_rest_api" id="mainwp_enable_rest_api" <?php echo ( ( 1 == get_option( 'mainwp_enable_rest_api', 0 ) ) ? 'checked="true"' : '' ); ?> />
							</div>
						</div>
						<div class="ui grid field">
							<div class="six wide column">
							</div>
							<div class="ten wide column">
								<a id="generate-new-api-credentials" href="#" data-tooltip="<?php esc_attr_e( 'Generate new API credentials.', 'mainwp' ); ?>" data-position="left center" data-inverted="" class="ui green button"><?php esc_html_e( 'Generate New API Credentials.', 'mainwp' ); ?></a>	
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Consumer Key', 'mainwp' ); ?></label>

							<div class="five wide column">
								<input type="password" name="mainwp_consumer_key" id="mainwp_consumer_key" value="<?php echo ( ( false === get_option( 'mainwp_rest_api_consumer_key' ) ) ? '' : get_option( 'mainwp_rest_api_consumer_key' ) ); ?>" readonly />
							</div>

							<div class="five wide column">
								<input id="mainwp_consumer_key_clipboard_button" style="display: none;" type="button" name="" class="ui green basic button copy-to-clipboard" value="<?php esc_attr_e( 'Copy to Clipboard', 'mainwp' ); ?>">
							</div>
						</div>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Consumer Secret', 'mainwp' ); ?></label>

							<div class="five wide column">
								<input type="password" name="mainwp_consumer_secret" id="mainwp_consumer_secret" value="<?php echo ( ( false === get_option( 'mainwp_rest_api_consumer_secret' ) ) ? '' : get_option( 'mainwp_rest_api_consumer_secret' ) ); ?>" readonly />
							</div>

							<div class="five wide column">
								<input id="mainwp_consumer_secret_clipboard_button" style="display: none;" type="button" name="" class="ui green basic button copy-to-clipboard" value="<?php esc_attr_e( 'Copy to Clipboard', 'mainwp' ); ?>">
							</div>
						</div>
						<?php
						/**
						 * Action: rest_api_form_bottom
						 *
						 * Fires at the bottom of REST API form.
						 *
						 * @since 4.1
						 */
						do_action( 'rest_api_form_bottom' );
						?>
						<div class="ui divider"></div>
						<input type="submit" name="submit" id="submit" class="ui green big button" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>"/>
						<div style="clear:both"></div>
					</form>
				</div>
			</div>
		<?php

		self::render_footer( 'RESTAPI' );
	}



	/**
	 * Export Child Sites and save as .csv file.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites_for_current_user()
	 * @uses \MainWP\Dashboard\MainWP_DB::data_seek()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_Utility::map_site()
	 */
	public static function export_sites() {
		if ( isset( $_GET['doExportSites'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'export_sites' ) ) {

			$sql      = MainWP_DB::instance()->get_sql_websites_for_current_user( true );
			$websites = MainWP_DB::instance()->query( $sql );

			if ( ! $websites ) {
				die( 'Not found sites' );
			}

			$keys           = array( 'name', 'url', 'adminname', 'wpgroups', 'uniqueId', 'http_user', 'http_pass', 'verify_certificate', 'ssl_version' );
			$allowedHeaders = array( 'site name', 'url', 'admin name', 'group', 'security id', 'http username', 'http password', 'verify certificate', 'ssl version' );

			$csv = implode( ',', $allowedHeaders ) . "\r";
			MainWP_DB::data_seek( $websites, 0 );
			while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
				if ( empty( $website ) ) {
					continue;
				}
				$row  = MainWP_Utility::map_site( $website, $keys, false );
				$csv .= '"' . implode( '","', $row ) . '"' . "\r";
			}

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=export-sites.csv' );
			echo $csv;
			exit();
		}
	}

	/**
	 * Render MainWP Email Settings SubPage.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_notification_types()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::render_edit_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::emails_general_settings_handle()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::render_all_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Template::handle_template_file_action()
	 */
	public static function render_email_settings() {
		$notification_emails = MainWP_Notification_Settings::get_notification_types();
		self::render_header( 'Emails' );
		$edit_email = isset( $_GET['edit-email'] ) ? sanitize_text_field( wp_unslash( $_GET['edit-email'] ) ) : '';
		if ( ! empty( $edit_email ) && isset( $notification_emails[ $edit_email ] ) ) {
			$updated_templ = MainWP_Notification_Template::instance()->handle_template_file_action();
			MainWP_Notification_Settings::instance()->render_edit_settings( $edit_email, $updated_templ );
		} else {
			$updated = MainWP_Notification_Settings::emails_general_settings_handle();
			MainWP_Notification_Settings::instance()->render_all_settings( $updated );
		}
		self::render_footer( 'Emails' );
	}

	/**
	 * Render Cache Control Settings SubPage.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Auto_Cache_Purge_View::handle_cache_control_post()
	 * @uses \MainWP\Dashboard\MainWP_Auto_Cache_Purge_View::render_global_settings()
	 */
	public static function render_cache_control() {

		self::render_header( 'cache-control' );
		$updated = MainWP_Auto_Cache_Purge_View::instance()->handle_cache_control_post();
		MainWP_Auto_Cache_Purge_View::instance()->render_global_settings( $updated );
		self::render_footer( 'cache-control' );
	}

	/**
	 * Method generate_random_string()
	 *
	 * Generate a random string.
	 *
	 * @param integer $length Lenght of final string.
	 *
	 * @return string $randomString Random String.
	 */
	public static function generate_random_string( $length = 8 ) {

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$charactersLength = strlen( $characters );

		$randomString = '';

		for ( $i = 0; $i < $length; $i++ ) {

			$randomString .= $characters[ wp_rand( 0, $charactersLength - 1 ) ];
		}

		return $randomString;
	}


	/**
	 * Render Client Reports Responder.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public static function render_report_responder() {
		if ( ! mainwp_current_user_have_right( 'dashboard', 'manage_dashboard_settings' ) ) {
			mainwp_do_not_have_permissions( __( 'manage dashboard settings', 'mainwp' ) );
			return;
		}

		self::render_header( 'SettingsClientReportsResponder' );
		?>
		<div id="mainwp-mcrwc-settings" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-live-reports-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-live-reports-info-message"></i>
					<?php echo __( 'This feature is deprecated.  It will be removed in one of the next updates.', 'mainwp' ); ?>
				</div>
			<?php endif; ?>
				<?php
				if ( isset( $_POST['save_changes'] ) || isset( $_POST['reset_connection'] ) ) {
					$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
					if ( ! wp_verify_nonce( $nonce, 'general_settings' ) ) {
						echo '<div class="ui red message"><i class="close icon"></i>' . __( 'Unable to save settings, please refresh and try again.', 'mainwp' ) . '</div>';
					} else {
						if ( isset( $_POST['reset_connection'] ) ) {
							MainWP_Utility::update_option( 'live-report-responder-pubkey', '' );
						} else {
							$siteurl = isset( $_POST['live_reponder_site_url'] ) ? sanitize_text_field( wp_unslash( $_POST['live_reponder_site_url'] ) ) : '';
							if ( ! empty( $siteurl ) && '/' != substr( $siteurl, - 1 ) ) {
								$siteurl = $siteurl . '/';
							}
							update_option( 'live-report-responder-siteurl', $siteurl );
							update_option( 'live-report-responder-provideaccess', ( isset( $_POST['live_reponder_provideaccess'] ) ) ? sanitize_text_field( wp_unslash( $_POST['live_reponder_provideaccess'] ) ) : '' );
							$security_token = self::generate_random_string();
							update_option( 'live-reports-responder-security-id', ( isset( $_POST['requireUniqueSecurityId'] ) ) ? sanitize_text_field( wp_unslash( $_POST['requireUniqueSecurityId'] ) ) : '' );
							update_option( 'live-reports-responder-security-code', stripslashes( $security_token ) );
							echo '<div class="ui green message"><i class="close icon"></i>' . __( 'Settings have been saved successfully!', 'mainwp' ) . '</div>';
						}
					}
				}
				?>
				<div class="ui form">
					<form method="POST">
					<?php
					wp_nonce_field( 'general_settings' );
					$pubkey = get_option( 'live-report-responder-pubkey' );
					?>
					<h3 class="ui dividing header"><?php esc_html_e( 'Managed Client Reports for WooCommerce Settings', 'mainwp' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php esc_html_e( 'Managed Client Reports site URL', 'mainwp' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter your WooCommerce reporting site URL here.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
							<input type="text"  name="live_reponder_site_url" placeholder="https://yourwoosite.com/" value="<?php echo esc_attr( get_option( 'live-report-responder-siteurl' ) ); ?>" autocomplete="off"
							<?php
							if ( ! empty( $pubkey ) ) {
								echo 'disabled'; }
							?>
							>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php esc_html_e( 'Allow connection', 'mainwp' ); ?></label>
						<div class="ten wide column">
							<div class="ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable to allow connection.', 'mainwp' ); ?>" data-inverted="" data-position="top left">
								<input type="checkbox" name="live_reponder_provideaccess" value="yes"
								<?php
								if ( 'yes' == get_option( 'live-report-responder-provideaccess' ) ) {
									echo 'checked';
								}

								if ( ! empty( $pubkey ) ) {
									echo 'disabled';
								}
								?>
								>
							</div>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php esc_html_e( 'Require unique security ID to secure the connection', 'mainwp' ); ?></label>
						<div class="ten wide column">
							<div class="ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable to generate unique security ID for additional security.', 'mainwp' ); ?>" data-inverted="" data-position="bottom left">
								<input name="requireUniqueSecurityId" type="checkbox" id="requireUniqueSecurityId"
								<?php
								if ( 'on' == get_option( 'live-reports-responder-security-id' ) ) {
									echo 'checked';
								}
								?>
								<?php
								if ( ! empty( $pubkey ) ) {
									echo 'disabled'; }
								?>
								/>
							</div>
						</div>
					</div>
					<?php if ( 'on' == get_option( 'live-reports-responder-security-id' ) ) { ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php esc_html_e( 'Your unique Security ID', 'mainwp' ); ?></label>
						<div class="ten wide column">
							<div class="ui label huge">
								<i class="key icon"></i>
								<?php echo get_option( 'live-reports-responder-security-code' ); ?>
							</div>
						</div>
					</div>
					<?php } ?>
					<div class="ui divider"></div>
					<input type="submit" name="save_changes" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>" class="ui button green big"
						<?php
						if ( ! empty( $pubkey ) ) {
							echo 'disabled'; }
						?>
					/>
					<?php if ( ! empty( $pubkey ) ) { ?>
						<input type="submit" name="reset_connection" value="<?php esc_attr_e( 'Reset Connection', 'mainwp' ); ?>" class="ui button green big basic">
					<?php } ?>
					<div style="clear:both"></div>
					</form>
				</div>
			</div>

		<?php
		self::render_footer( 'SettingsClientReportsResponder' );
	}

	/**
	 * Hook the section help content to the Help Sidebar element
	 */
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( 'Settings' == $_GET['page'] || 'SettingsAdvanced' == $_GET['page'] || 'MainWPTools' == $_GET['page'] || 'SettingsClientReportsResponder' == $_GET['page'] ) ) {
			?>
			<p><?php esc_html_e( 'If you need help with your MainWP Dashboard settings, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://kb.mainwp.com/docs/mainwp-dashboard-settings/" target="_blank">MainWP Dashboard Settings</a></div>
			</div>
			<?php
		}
	}

}
