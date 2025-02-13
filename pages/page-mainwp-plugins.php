<?php
/**
 * MainWP Plugins Page.
 *
 * @package     MainWP/Dashboard
 */

namespace MainWP\Dashboard;

/**
 * Class MainWP_Plugins
 *
 * @package MainWP\Dashboard\
 *
 * @uses \MainWP\Dashboard\MainWP_Install_Bulk
 */
class MainWP_Plugins {
	// phpcs:disable Generic.Metrics.CyclomaticComplexity -- This is the only way to achieve desired results, pull request solutions appreciated.

	/**
	 * Get Class Name.
	 *
	 * @return string __CLASS__
	 */
	public static function get_class_name() {
		return __CLASS__;
	}

	/**
	 * MainWP Plugins sub-pages.
	 *
	 * @var array $subPages MainWP Plugins Sub Pages.
	 */
	public static $subPages;

	/**
	 * Plugins table.
	 *
	 * @var mixed $pluginsTable Plugins table.
	 */
	public static $pluginsTable;

	/** Instantiate Hooks. */
	public static function init() {
		/**
		 * This hook allows you to render the Plugins page header via the 'mainwp-pageheader-plugins' action.
		 *
		 * @link http://codex.mainwp.com/#mainwp-pageheader-plugins
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-plugins'
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-plugins
		 *
		 * @see \MainWP_Plugins::render_header
		 */
		add_action( 'mainwp-pageheader-plugins', array( self::get_class_name(), 'render_header' ) );

		/**
		 * This hook allows you to render the Plugins page footer via the 'mainwp-pagefooter-plugins' action.
		 *
		 * @link http://codex.mainwp.com/#mainwp-pagefooter-plugins
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-plugins'
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-plugins
		 *
		 * @see \MainWP_Plugins::render_footer
		 */
		add_action( 'mainwp-pagefooter-plugins', array( self::get_class_name(), 'render_footer' ) );

		add_action( 'mainwp_help_sidebar_content', array( self::get_class_name(), 'mainwp_help_content' ) );
	}

	/**
	 * Instantiate Main Plugins Menu.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 */
	public static function init_menu() {
		$_page = add_submenu_page(
			'mainwp_tab',
			__( 'Plugins', 'mainwp' ),
			'<span id="mainwp-Plugins">' . __( 'Plugins', 'mainwp' ) . '</span>',
			'read',
			'PluginsManage',
			array(
				self::get_class_name(),
				'render',
			)
		);
		if ( mainwp_current_user_have_right( 'dashboard', 'install_plugins' ) ) {
			$page = add_submenu_page(
				'mainwp_tab',
				__( 'Plugins', 'mainwp' ),
				'<div class="mainwp-hidden">' . __( 'Install ', 'mainwp' ) . '</div>',
				'read',
				'PluginsInstall',
				array(
					self::get_class_name(),
					'render_install',
				)
			);

			add_action( 'load-' . $page, array( self::get_class_name(), 'load_page' ) );
		}
		add_submenu_page(
			'mainwp_tab',
			__( 'Plugins', 'mainwp' ),
			'<div class="mainwp-hidden">' . __( 'Advanced Auto Updates', 'mainwp' ) . '</div>',
			'read',
			'PluginsAutoUpdate',
			array(
				self::get_class_name(),
				'render_auto_update',
			)
		);
		add_submenu_page(
			'mainwp_tab',
			__( 'Plugins', 'mainwp' ),
			'<div class="mainwp-hidden">' . __( 'Ignored Updates', 'mainwp' ) . '</div>',
			'read',
			'PluginsIgnore',
			array(
				self::get_class_name(),
				'render_ignore',
			)
		);
		add_submenu_page(
			'mainwp_tab',
			__( 'Plugins', 'mainwp' ),
			'<div class="mainwp-hidden">' . __( 'Ignored Abandoned', 'mainwp' ) . '</div>',
			'read',
			'PluginsIgnoredAbandoned',
			array(
				self::get_class_name(),
				'render_ignored_abandoned',
			)
		);

		/**
		 * Plugins Subpages
		 *
		 * Filters subpages for the Plugins page.
		 *
		 * @since Unknown
		 */
		$sub_pages      = apply_filters_deprecated( 'mainwp-getsubpages-plugins', array( array() ), '4.0.7.2', 'mainwp_getsubpages_plugins' );  // @deprecated Use 'mainwp_getsubpages_plugins' instead.
		self::$subPages = apply_filters( 'mainwp_getsubpages_plugins', $sub_pages );

		if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
			foreach ( self::$subPages as $subPage ) {
				if ( MainWP_Menu::is_disable_menu_item( 3, 'Plugins' . $subPage['slug'] ) ) {
					continue;
				}
				add_submenu_page( 'mainwp_tab', $subPage['title'], '<div class="mainwp-hidden">' . $subPage['title'] . '</div>', 'read', 'Plugins' . $subPage['slug'], $subPage['callback'] );
			}
		}
		self::init_left_menu( self::$subPages );
	}

	/**
	 * Load the Plugins Page.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Plugins_Install_List_Table
	 */
	public static function load_page() {
		self::$pluginsTable = new MainWP_Plugins_Install_List_Table();
		$pagenum            = self::$pluginsTable->get_pagenum();

		self::$pluginsTable->prepare_items();

		$total_pages = self::$pluginsTable->get_pagination_arg( 'total_pages' );

		if ( $pagenum > $total_pages && 0 < $total_pages ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'paged', $total_pages ) ) );
			exit;
		}
	}

	/**
	 * Instantiate Subpage "tabs".
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 */
	public static function init_subpages_menu() {
		?>
		<div id="menu-mainwp-Plugins" class="mainwp-submenu-wrapper" xmlns="http://www.w3.org/1999/html">
			<div class="wp-submenu sub-open" >
				<div class="mainwp_boxout">
					<div class="mainwp_boxoutin"></div>
					<a href="<?php echo admin_url( 'admin.php?page=PluginsManage' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Manage Plugins', 'mainwp' ); ?></a>
					<?php if ( mainwp_current_user_have_right( 'dashboard', 'install_plugins' ) ) : ?>
						<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsInstall' ) ) : ?>
							<a href="<?php echo admin_url( 'admin.php?page=PluginsInstall' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Install Plugins', 'mainwp' ); ?></a>
							<?php endif; ?>
							<?php endif; ?>
							<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsAutoUpdate' ) ) : ?>
							<a href="<?php echo admin_url( 'admin.php?page=PluginsAutoUpdate' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Advanced Auto Updates', 'mainwp' ); ?></a>
							<?php endif; ?>
							<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsIgnore' ) ) : ?>
								<a href="<?php echo admin_url( 'admin.php?page=PluginsIgnore' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Ignored Updates', 'mainwp' ); ?></a>
							<?php endif; ?>
							<?php if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsIgnoredAbandoned' ) ) : ?>
								<a href="<?php echo admin_url( 'admin.php?page=PluginsIgnoredAbandoned' ); ?>" class="mainwp-submenu"><?php esc_html_e( 'Ignored Abandoned', 'mainwp' ); ?></a>
							<?php endif; ?>
							<?php
							if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
								foreach ( self::$subPages as $subPage ) {
									if ( MainWP_Menu::is_disable_menu_item( 3, 'Plugins' . $subPage['slug'] ) ) {
										continue;
									}
									?>
									<a href="<?php echo admin_url( 'admin.php?page=Plugins' . $subPage['slug'] ); ?>" class="mainwp-submenu"><?php echo esc_html( $subPage['title'] ); ?></a>
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
	 * Instantiate MainWP main menu Subpages menu.
	 *
	 * @param array $subPages Subpages array.
	 *
	 * @uses MainWP_Menu::add_left_menu()
	 * @uses MainWP_Menu::init_subpages_left_menu()
	 * @uses MainWP_Menu::is_disable_menu_item()
	 * @uses \MainWP\Dashboard\MainWP_Menu::add_left_menu()
	 */
	public static function init_left_menu( $subPages = array() ) {
		MainWP_Menu::add_left_menu(
			array(
				'title'      => __( 'Plugins', 'mainwp' ),
				'parent_key' => 'mainwp_tab',
				'slug'       => 'PluginsManage',
				'href'       => 'admin.php?page=PluginsManage',
				'icon'       => '<i class="plug icon"></i>',
			),
			1
		);

		$init_sub_subleftmenu = array(
			array(
				'title'      => __( 'Manage Plugins', 'mainwp' ),
				'parent_key' => 'PluginsManage',
				'href'       => 'admin.php?page=PluginsManage',
				'slug'       => 'PluginsManage',
				'right'      => '',
			),
			array(
				'title'      => __( 'Install Plugins', 'mainwp' ),
				'parent_key' => 'PluginsManage',
				'href'       => 'admin.php?page=PluginsInstall',
				'slug'       => 'PluginsInstall',
				'right'      => 'install_plugins',
			),
			array(
				'title'      => __( 'Advanced Auto Updates', 'mainwp' ),
				'parent_key' => 'PluginsManage',
				'href'       => 'admin.php?page=PluginsAutoUpdate',
				'slug'       => 'PluginsAutoUpdate',
				'right'      => '',
			),
			array(
				'title'      => __( 'Ignored Updates', 'mainwp' ),
				'parent_key' => 'PluginsManage',
				'href'       => 'admin.php?page=PluginsIgnore',
				'slug'       => 'PluginsIgnore',
				'right'      => '',
			),
			array(
				'title'      => __( 'Ignored Abandoned', 'mainwp' ),
				'parent_key' => 'PluginsManage',
				'href'       => 'admin.php?page=PluginsIgnoredAbandoned',
				'slug'       => 'PluginsIgnoredAbandoned',
				'right'      => '',
			),
		);

		MainWP_Menu::init_subpages_left_menu( $subPages, $init_sub_subleftmenu, 'PluginsManage', 'Plugins' );

		foreach ( $init_sub_subleftmenu as $item ) {
			if ( MainWP_Menu::is_disable_menu_item( 3, $item['slug'] ) ) {
				continue;
			}
			MainWP_Menu::add_left_menu( $item, 2 );
		}
	}

	/**
	 * Render MainWP Plugins Page Header.
	 *
	 * @param string $shownPage The page slug shown at this moment.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Menu::is_disable_menu_item()
	 * @uses \MainWP\Dashboard\MainWP_UI::render_top_header()
	 * @uses \MainWP\Dashboard\MainWP_UI::render_page_navigation()
	 */
	public static function render_header( $shownPage = '' ) {

		$params = array(
			'title' => __( 'Plugins', 'mainwp' ),
		);

		MainWP_UI::render_top_header( $params );

		$renderItems   = array();
		$renderItems[] = array(
			'title'  => __( 'Manage Plugins', 'mainwp' ),
			'href'   => 'admin.php?page=PluginsManage',
			'active' => ( 'Manage' === $shownPage ) ? true : false,
		);

		if ( mainwp_current_user_have_right( 'dashboard', 'install_plugins' ) ) {
			if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsInstall' ) ) {
				$renderItems[] = array(
					'title'  => __( 'Install', 'mainwp' ),
					'href'   => 'admin.php?page=PluginsInstall',
					'active' => ( 'Install' === $shownPage ) ? true : false,
				);
			}
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsAutoUpdate' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Advanced Auto Updates', 'mainwp' ),
				'href'   => 'admin.php?page=PluginsAutoUpdate',
				'active' => ( 'AutoUpdate' === $shownPage ) ? true : false,
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsIgnore' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Ignored Updates', 'mainwp' ),
				'href'   => 'admin.php?page=PluginsIgnore',
				'active' => ( 'Ignore' === $shownPage ) ? true : false,
			);
		}

		if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PluginsIgnoredAbandoned' ) ) {
			$renderItems[] = array(
				'title'  => __( 'Ignored Abandoned', 'mainwp' ),
				'href'   => 'admin.php?page=PluginsIgnoredAbandoned',
				'active' => ( 'IgnoreAbandoned' === $shownPage ) ? true : false,
			);
		}

		if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
			foreach ( self::$subPages as $subPage ) {
				if ( MainWP_Menu::is_disable_menu_item( 3, 'Plugins' . $subPage['slug'] ) ) {
					continue;
				}

				$item           = array();
				$item['title']  = $subPage['title'];
				$item['href']   = 'admin.php?page=Plugins' . $subPage['slug'];
				$item['active'] = ( $subPage['slug'] == $shownPage ) ? true : false;
				$renderItems[]  = $item;
			}
		}

		MainWP_UI::render_page_navigation( $renderItems );
	}

	/**
	 * Method render_footer()
	 *
	 * Render MainWP Plugins Page Footer.
	 *
	 * @param string $shownPage The page slug shown at this moment.
	 */
	public static function render_footer( $shownPage ) {
		echo '</div>';
	}

	/**
	 * Render MainWP Plugins Page.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Cache::get_cached_context()
	 * @uses \MainWP\Dashboard\MainWP_Cache::get_cached_result()
	 * @uses \MainWP\Dashboard\MainWP_UI::render_empty_bulk_actions()
	 * @uses \MainWP\Dashboard\MainWP_UI::select_sites_box()
	 */
	public static function render() {
		$cachedSearch    = MainWP_Cache::get_cached_context( 'Plugins' );
		$selected_sites  = array();
		$selected_groups = array();
		if ( null != $cachedSearch ) {
			if ( is_array( $cachedSearch['sites'] ) ) {
				$selected_sites = $cachedSearch['sites'];
			} elseif ( is_array( $cachedSearch['groups'] ) ) {
				$selected_groups = $cachedSearch['groups'];
			}
		}
		$cachedResult = MainWP_Cache::get_cached_result( 'Plugins' );
		self::render_header( 'Manage' );
		?>

		<div id="mainwp-manage-plugins" class="ui alt segment">
			<div class="mainwp-main-content">
				<div class="ui mini form mainwp-actions-bar">
					<div class="ui grid">
						<div class="ui two column row">
							<div class="column" >
								<a href="#"  onclick="jQuery( '.mainwp-selected-plugin' ).prop( 'checked', true ).change();jQuery( '.mainwp-selected-plugin' ).attr('checked-state', 'checked'); jQuery( '.mainwp_plugins_site_check_all' ).prop( 'checked', true ).change(); return false;" class="ui mini button"><?php esc_html_e( 'Select All', 'mainwp' ); ?></a>
								<a href="#" onclick="jQuery( '.mainwp-selected-plugin' ).prop( 'checked', false ).change();jQuery( '.mainwp-selected-plugin' ).attr('checked-state', 'un-checked'); jQuery( '.mainwp_plugins_site_check_all' ).prop( 'checked', false ).change(); return false;"   class="ui mini button"><?php esc_html_e( 'Select None', 'mainwp' ); ?></a>
								<button id="mainwp-install-to-selected-sites" class="ui mini green basic button" style="display: none"><?php esc_html_e( 'Install to Selected Site(s)', 'mainwp' ); ?></button>
								<?php
								/**
								 * Action: mainwp_plugins_actions_bar_left
								 *
								 * Fires at the left side of the actions bar on the Plugins screen, after the Bulk Actions menu.
								 *
								 * @since 4.0
								 */
								do_action( 'mainwp_plugins_actions_bar_left' );
								?>
							</div>
							<div class="right aligned column">
								<div id="mainwp-plugins-bulk-actions-wapper">
									<?php
									if ( is_array( $cachedResult ) && isset( $cachedResult['bulk_actions'] ) ) {
										echo $cachedResult['bulk_actions'];
									} else {
										MainWP_UI::render_empty_bulk_actions();
									}
									?>
								</div>
								<?php
								/**
								 * Action: mainwp_plugins_actions_bar_right
								 *
								 * Fires at the right side of the actions bar on the Plugins screen.
								 *
								 * @since 4.0
								 */
								do_action( 'mainwp_plugins_actions_bar_right' );
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="ui segment" id="mainwp-plugins-table-wrapper">
					<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-manage-plugins-info-message' ) ) : ?>
						<div class="ui info message">
							<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-manage-plugins-info-message"></i>
							<div><?php echo __( 'Manage installed plugins on your child sites. Here you can activate, deactive, and delete installed plugins.', 'mainwp' ); ?></div>
							<p><?php echo __( 'To Activate or Delete a specific plugin, you must search only for Inactive plugin on your child sites. If you search for Active or both Active and Inactive, the Activate and Delete actions will be disabled.', 'mainwp' ); ?></p>
							<p><?php echo __( 'To Deactivate a specific plugin, you must search only for Active plugins on your child sites. If you search for Inactive or both Active and Inactive, the Deactivate action will be disabled.', 'mainwp' ); ?></p>
							<p><?php echo sprintf( __( 'For additional help, please check this %1$shelp documentation%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/managing-plugins-with-mainwp/" target="_blank">', '</a>' ); ?></p>
						</div>
					<?php endif; ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<div id="mainwp-loading-plugins-row" class="ui active inverted dimmer" style="display:none">
						<div class="ui large text loader"><?php esc_html_e( 'Loading Plugins...', 'mainwp' ); ?></div>
					</div>
					<div id="mainwp-plugins-main-content" <?php echo ( null != $cachedSearch ) ? 'style="display: block;"' : ''; ?> >
						<div id="mainwp-plugins-content">
							<?php if ( is_array( $cachedResult ) && isset( $cachedResult['result'] ) ) : ?>
								<?php echo $cachedResult['result']; ?>
							<?php else : ?>
								<table id="mainwp-manage-plugins-table-placeholder" class="ui table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Sites / Plugins', 'mainwp' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td><?php esc_html_e( 'Please use the search options to find wanted plugins.', 'mainwp' ); ?></td>
										</tr>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="mainwp-side-content mainwp-no-padding">
				<?php
				/**
				 * Action: mainwp_manage_plugins_sidebar_top
				 *
				 * Fires at the top of the sidebar on Manage themes.
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_manage_plugins_sidebar_top' );
				?>

				<div class="mainwp-select-sites ui accordion mainwp-sidebar-accordion">
					<?php
					/**
					 * Action: mainwp_manage_plugins_before_select_sites
					 *
					 * Fires before the Select Sites elemnt on Manage plugins.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_before_select_sites' );
					?>
					<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Select Sites', 'mainwp' ); ?></div>
					<div class="content active"><?php MainWP_UI::select_sites_box( 'checkbox', true, true, 'mainwp_select_sites_box_left', '', $selected_sites, $selected_groups ); ?></div>
					<?php
					/**
					 * Action: mainwp_manage_plugins_after_select_sites
					 *
					 * Fires after the Select Sites elemnt on Manage plugins.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_after_select_sites' );
					?>
					</div>
					<div class="ui fitted divider"></div>
				<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
					<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Select Status', 'mainwp' ); ?></div>
					<div class="content active">
					<?php
					/**
					 * Action: mainwp_manage_plugins_before_search_options
					 *
					 * Fires before the Search Options elemnt on Manage plugins.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_before_search_options' );
					?>
					<div class="ui info message">
						<i class="close icon mainwp-notice-dismiss" notice-id="plugins-manage-info"></i>
						<?php esc_html_e( 'A plugin needs to be Inactive in order for it to be Activated or Deleted.', 'mainwp' ); ?>
					</div>
					<div class="ui mini form">
						<div class="field">
							<select multiple="" class="ui fluid dropdown" id="mainwp_plugins_search_by_status">
								<option value=""><?php esc_html_e( 'Select status', 'mainwp' ); ?></option>
								<option value="active" selected><?php esc_html_e( 'Active', 'mainwp' ); ?></option>
								<option value="inactive"><?php esc_html_e( 'Inactive', 'mainwp' ); ?></option>
							</select>
						</div>
					</div>
					<?php
					/**
					 * Action: mainwp_manage_plugins_after_search_options
					 *
					 * Fires after the Search Options elemnt on Manage plugins.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_after_search_options' );
					?>
				</div>
					</div>
				<div class="ui fitted divider"></div>
				<?php self::render_search_options(); ?>
				<div class="ui fitted divider"></div>
					<div class="mainwp-search-submit">
					<?php
					/**
					 * Action: mainwp_manage_plugins_before_submit_button
					 *
					 * Fires before the Submit Button elemnt on Manage plugins.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_before_submit_button' );
					?>
					<input type="button" name="mainwp-show-plugins" id="mainwp-show-plugins" class="ui green big fluid button" value="<?php esc_attr_e( 'Show Plugins', 'mainwp' ); ?>"/>
					<?php
					/**
					 * Action: mainwp_manage_plugins_after_submit_button
					 *
					 * Fires after the Submit Button elemnt on Manage plugins.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_after_submit_button' );
					?>
				</div>
				<?php
				/**
				 * Action: mainwp_manage_plugins_sidebar_bottom
				 *
				 * Fires at the bottom of the sidebar on Manage themes.
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_manage_plugins_sidebar_bottom' );
				?>
			</div>
			<div style="clear:both"></div>
		</div>
		<?php
		self::render_footer( 'Manage' );
	}

	/**
	 * Render MainWP plugins page search options.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Cache::get_cached_context()
	 */
	public static function render_search_options() {
		$cachedSearch = MainWP_Cache::get_cached_context( 'Plugins' );
		$statuses     = isset( $cachedSearch['status'] ) ? $cachedSearch['status'] : array();
		if ( $cachedSearch && isset( $cachedSearch['keyword'] ) ) {
			$cachedSearch['keyword'] = trim( $cachedSearch['keyword'] );
		}
		$disabledNegative = ( null != $cachedSearch ) && ! empty( $cachedSearch['keyword'] ) ? false : true;
		$checkedNegative  = ! $disabledNegative && ( null != $cachedSearch ) && ! empty( $cachedSearch['not_criteria'] ) ? true : false;
		?>
		<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
			<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Search Options', 'mainwp' ); ?></div>
			<div class="content active">
				<div class="ui mini form">
					<div class="field">
						<div class="ui input fluid">
							<input type="text" placeholder="<?php esc_attr_e( 'Plugin name', 'mainwp' ); ?>" id="mainwp_plugin_search_by_keyword" class="text" value="<?php echo ( null != $cachedSearch ) ? esc_attr( $cachedSearch['keyword'] ) : ''; ?>" />
						</div>
					</div>
					<div class="ui hidden fitted divider"></div>
					<div class="field">
						<div class="ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Display sites not meeting the above search criteria.', 'mainwp' ); ?>" data-position="left center" data-inverted="">
								<input type="checkbox" <?php echo $disabledNegative ? 'disabled' : ''; ?> <?php echo ( $checkedNegative ? 'checked="true"' : '' ); ?> value="1" id="display_sites_not_meeting_criteria" />
							<label for="display_sites_not_meeting_criteria"><?php esc_html_e( 'Negative search', 'mainwp' ); ?></label>
							</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		if ( is_array( $statuses ) && 0 < count( $statuses ) ) {
			$status = '';
			foreach ( $statuses as $st ) {
				$status .= "'" . esc_html( $st ) . "',";
			}
			$status = rtrim( $status, ',' );
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function () {
					jQuery( '#mainwp_plugins_search_by_status' ).dropdown( 'set selected', [<?php echo $status; // phpcs:ignore -- safe output, to fix incorrect characters. ?>] );
				} );
			</script>
			<?php
		}
		?>
		<script type="text/javascript">
			jQuery( document ).on( 'keyup', '#mainwp_plugin_search_by_keyword', function () {
				if( jQuery(this).val() != '' ){
					jQuery( '#display_sites_not_meeting_criteria' ).removeAttr('disabled');
				} else {
					jQuery( '#display_sites_not_meeting_criteria' ).closest('.checkbox').checkbox('set unchecked');
					jQuery( '#display_sites_not_meeting_criteria' ).attr('disabled', 'true');					
				}
			});
		</script>
		<?php
	}

	/**
	 * Render Plugins Table.
	 *
	 * @param mixed $keyword Search Terms.
	 * @param mixed $status active|inactive Whether the plugin is active or inactive.
	 * @param mixed $groups Selected Child Site Groups.
	 * @param mixed $sites Selected individual Child Sites.
	 * @param mixed $not_criteria Show not criteria result.
	 *
	 * @return string Plugin Table.
	 *
	 * @uses MainWP_Cache::init_cache()
	 * @uses MainWP_Utility::ctype_digit()
	 * @uses MainWP_DB::instance()
	 * @uses MainWP_DB::free_result()
	 * @uses MainWP_DB::fetch_object()
	 * @uses MainWP_Utility::map_site()
	 * @uses MainWP_Utility::fetch_urls_authed()
	 * @uses MainWP_Utility::get_nice_url()
	 * @uses MainWP_Cache::add_context()
	 * @uses MainWP_Cache::add_result()
	 * @uses \MainWP\Dashboard\MainWP_Cache::init_cache()
	 * @uses \MainWP\Dashboard\MainWP_Cache::add_context()
	 * @uses \MainWP\Dashboard\MainWP_Cache::add_result()
	 * @uses \MainWP\Dashboard\MainWP_Connect::fetch_url_authed()
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_by_id()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites_by_group_id()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 * @uses \MainWP\Dashboard\MainWP_Plugins_Handler::get_class_name()
	 * @uses \MainWP\Dashboard\MainWP_Utility::ctype_digit()
	 * @uses \MainWP\Dashboard\MainWP_Utility::map_site()
	 */
	public static function render_table( $keyword, $status, $groups, $sites, $not_criteria ) { // phpcs:ignore -- Current complexity required to achieve desired results. Pull request solutions appreciated.
		$keyword = trim( $keyword );
		MainWP_Cache::init_cache( 'Plugins' );

			$output                       = new \stdClass();
			$output->errors               = array();
			$output->plugins              = array();
			$output->not_criteria_plugins = array();

		if ( 1 == get_option( 'mainwp_optimize' ) ) {
			if ( '' != $sites ) {
				foreach ( $sites as $k => $v ) {
					if ( MainWP_Utility::ctype_digit( $v ) ) {
						$website    = MainWP_DB::instance()->get_website_by_id( $v );
						$allPlugins = json_decode( $website->plugins, true );
						$_count     = count( $allPlugins );
						$not_found  = true;
						for ( $i = 0; $i < $_count; $i ++ ) {
							$plugin = $allPlugins[ $i ];

							if ( ( 'active' === $status ) || ( 'inactive' === $status ) ) {
								if ( ( ( 'active' === $status ) ? 1 : 0 ) != $plugin['active'] ) {
										continue;
								}
							}

							if ( '' != $keyword && ! stristr( $plugin['name'], $keyword ) ) {
								continue;
							}

							$plugin['websiteid']  = $website->id;
							$plugin['websiteurl'] = $website->url;
							$output->plugins[]    = $plugin;
							$not_found            = false;
						}

						if ( $not_found && $not_criteria ) {
							for ( $i = 0; $i < $_count; $i ++ ) {
								$plugin                         = $allPlugins[ $i ];
								$plugin['websiteid']            = $website->id;
								$plugin['websiteurl']           = $website->url;
								$output->not_criteria_plugins[] = $plugin;
							}
						}
					}
				}
			}

			if ( '' !== $groups ) {
				foreach ( $groups as $k => $v ) {
					if ( MainWP_Utility::ctype_digit( $v ) ) {
						$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_by_group_id( $v ) );
						while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
							if ( '' !== $website->sync_errors ) {
								continue;
							}
							$allPlugins = json_decode( $website->plugins, true );
							$_count     = count( $allPlugins );
							$not_found  = true;
							for ( $i = 0; $i < $_count; $i ++ ) {
								$plugin = $allPlugins[ $i ];

								if ( ( 'active' === $status ) || ( 'inactive' === $status ) ) {
									if ( ( ( 'active' === $status ) ? 1 : 0 ) != $plugin['active'] ) {
										continue;
									}
								}
								if ( '' != $keyword && ! stristr( $plugin['name'], $keyword ) ) {
									continue;
								}

								$plugin['websiteid']  = $website->id;
								$plugin['websiteurl'] = $website->url;
								$output->plugins[]    = $plugin;
								$not_found            = false;
							}
							if ( $not_found && $not_criteria ) {
								for ( $i = 0; $i < $_count; $i ++ ) {
									$plugin                         = $allPlugins[ $i ];
									$plugin['websiteid']            = $website->id;
									$plugin['websiteurl']           = $website->url;
									$output->not_criteria_plugins[] = $plugin;
								}
							}
						}
						MainWP_DB::free_result( $websites );
					}
				}
			}
		} else {
			$dbwebsites = array();

			if ( '' !== $sites ) {
				foreach ( $sites as $k => $v ) {
					if ( MainWP_Utility::ctype_digit( $v ) ) {
						$website                    = MainWP_DB::instance()->get_website_by_id( $v );
						$dbwebsites[ $website->id ] = MainWP_Utility::map_site(
							$website,
							array(
								'id',
								'url',
								'name',
								'adminname',
								'nossl',
								'privkey',
								'nosslkey',
								'http_user',
								'http_pass',
								'ssl_version',
							)
						);
					}
				}
			}

			if ( '' !== $groups ) {
				foreach ( $groups as $k => $v ) {
					if ( MainWP_Utility::ctype_digit( $v ) ) {
						$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_by_group_id( $v ) );
						while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
							if ( '' !== $website->sync_errors ) {
								continue;
							}
							$dbwebsites[ $website->id ] = MainWP_Utility::map_site(
								$website,
								array(
									'id',
									'url',
									'name',
									'adminname',
									'nossl',
									'privkey',
									'nosslkey',
									'http_user',
									'http_pass',
									'ssl_version',
								)
							);
						}
						MainWP_DB::free_result( $websites );
					}
				}
			}

			$post_data = array(
				'keyword' => $keyword,
			);

			if ( 'active' === $status || 'inactive' === $status ) {
				$post_data['status'] = $status;
				$post_data['filter'] = true;
			} else {
				$post_data['status'] = '';
				$post_data['filter'] = false;
			}

			$post_data['not_criteria'] = $not_criteria ? true : false;

			MainWP_Connect::fetch_urls_authed( $dbwebsites, 'get_all_plugins', $post_data, array( MainWP_Plugins_Handler::get_class_name(), 'plugins_search_handler' ), $output );

			if ( 0 < count( $output->errors ) ) {
				foreach ( $output->errors as $siteid => $error ) {
					echo MainWP_Utility::get_nice_url( $dbwebsites[ $siteid ]->url ) . ': ' . $error . ' <br/>';
				}
				echo '<div class="ui hidden divider"></div>';
			}

			if ( count( $output->errors ) == count( $dbwebsites ) ) {
				return;
			}
		}

		MainWP_Cache::add_context(
			'Plugins',
			array(
				'keyword'      => $keyword,
				'status'       => $status,
				'sites'        => ( '' !== $sites ) ? $sites : '',
				'groups'       => ( '' !== $groups ) ? $groups : '',
				'not_criteria' => $not_criteria ? true : false,
			)
		);

		$bulkActions = self::render_bulk_actions( $status );

		ob_start();

		if ( 0 == count( $output->plugins ) && ! $not_criteria ) {
			?>
			<div class="ui message yellow"><?php esc_html_e( 'No plugins found.', 'mainwp' ); ?></div>
			<?php
		} else {
				$sites              = array();
				$sitePlugins        = array();
				$plugins            = array();
				$muPlugins          = array();
				$pluginsVersion     = array();
				$pluginsName        = array();
				$pluginsMainWP      = array();
				$pluginsRealVersion = array();

				$plugins_list = array();

			if ( $not_criteria ) {
				if ( property_exists( $output, 'not_criteria_plugins' ) && ! empty( $output->not_criteria_plugins ) ) {
					$plugins_list = $output->not_criteria_plugins;
				}
			} else {
				$plugins_list = $output->plugins;
			}

			foreach ( $plugins_list as $plugin ) {
				$pn                            = esc_html( $plugin['name'] . '_' . $plugin['version'] );
				$sites[ $plugin['websiteid'] ] = esc_html( $plugin['websiteurl'] );
				$plugins[ $pn ]                = isset( $plugin['slug'] ) ? rawurlencode( $plugin['slug'] ) : '';
				$muPlugins[ $pn ]              = isset( $plugin['mu'] ) ? esc_html( $plugin['mu'] ) : 0;
				$pluginsName[ $pn ]            = esc_html( $plugin['name'] );
				$pluginsVersion[ $pn ]         = array(
					'name' => $plugin['name'],
					'ver'  => $plugin['version'],
				);
				$pluginsMainWP[ $pn ]          = isset( $plugin['mainwp'] ) ? esc_html( $plugin['mainwp'] ) : 'F';
				$pluginsRealVersion[ $pn ]     = rawurlencode( $plugin['version'] );

				if ( ! isset( $sitePlugins[ $plugin['websiteid'] ] ) || ! is_array( $sitePlugins[ $plugin['websiteid'] ] ) ) {
					$sitePlugins[ $plugin['websiteid'] ] = array();
				}

				$sitePlugins[ $plugin['websiteid'] ][ $pn ] = $plugin;
			}

				uasort(
					$pluginsVersion,
					function( $a, $b ) {
						$ret = strcasecmp( $a['name'], $b['name'] );
						if ( 0 != $ret ) {
							return $ret;
						}
						return version_compare( $a['ver'], $b['ver'] );
					}
				);

				self::render_manage_table( $sites, $plugins, $sitePlugins, $pluginsMainWP, $muPlugins, $pluginsName, $pluginsVersion, $pluginsRealVersion );

		}

		$newOutput = ob_get_clean();
		$result    = array(
			'result'       => $newOutput,
			'bulk_actions' => $bulkActions,
		);

		MainWP_Cache::add_result( 'Plugins', $result );
		return $result;
	}

	/**
	 * Render Bulk Actions.
	 *
	 * @param mixed $status active|inactive|all.
	 *
	 * @return string Plugin Bulk Actions Menu.
	 */
	public static function render_bulk_actions( $status ) {
		ob_start();
		?>


		<select class="ui dropdown" id="mainwp-bulk-actions">
			<option value="none"><?php esc_html_e( 'Bulk Actions', 'mainwp' ); ?></option>
			<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
				<option value="ignore_updates" data-value="ignore_updates"><?php esc_html_e( 'Ignore updates', 'mainwp' ); ?></option>
			<?php endif; ?>
		<?php if ( mainwp_current_user_have_right( 'dashboard', 'activate_deactivate_plugins' ) ) : ?>
				<?php if ( 'active' === $status ) : ?>
				<option value="deactivate" data-value="deactivate"><?php esc_html_e( 'Deactivate', 'mainwp' ); ?></option>
				<?php else : ?>
					<option value="deactivate" disabled data-value="deactivate"><?php esc_html_e( 'Deactivate', 'mainwp' ); ?></option>
			<?php endif; ?>
		<?php endif; ?>
			<?php if ( 'inactive' === $status ) : ?>
				<?php if ( mainwp_current_user_have_right( 'dashboard', 'activate_deactivate_plugins' ) ) : ?>
				<option value="activate" data-value="activate"><?php esc_html_e( 'Activate', 'mainwp' ); ?></option>
			<?php endif; ?>
				<?php if ( mainwp_current_user_have_right( 'dashboard', 'delete_plugins' ) ) : ?>
				<option value="delete" data-value="delete"><?php esc_html_e( 'Delete', 'mainwp' ); ?></option>
			<?php endif; ?>
			<?php else : ?>
				<?php if ( mainwp_current_user_have_right( 'dashboard', 'activate_deactivate_plugins' ) ) : ?>
					<option value="activate" disabled data-value="activate"><?php esc_html_e( 'Activate', 'mainwp' ); ?></option>
				<?php endif; ?>
				<?php if ( mainwp_current_user_have_right( 'dashboard', 'delete_plugins' ) ) : ?>
					<option value="delete" disabled data-value="delete"><?php esc_html_e( 'Delete', 'mainwp' ); ?></option>
		<?php endif; ?>
		<?php endif; ?>
		<?php
		/**
		 * Action: mainwp_plugins_bulk_action
		 *
		 * Adds a new action to the Manage Plugins bulk actions menu.
		 *
		 * @param string $status Status search parameter.
		 *
		 * @since 4.1
		 */
		do_action( 'mainwp_plugins_bulk_action' );
		?>
		</select>
	<button class="ui mini basic button" href="javascript:void(0)" id="mainwp-do-plugins-bulk-actions"><?php esc_html_e( 'Apply', 'mainwp' ); ?></button>
	<span id="mainwp_bulk_action_loading"><i class="ui active inline loader tiny"></i></span>
		<?php
		$bulkActions = ob_get_clean();
		return $bulkActions;
	}

	/**
	 * Method render_manage_table()
	 *
	 * Render Manage Plugins Table.
	 *
	 * @param array $sites Child Sites array.
	 * @param array $plugins Plugins array.
	 * @param array $sitePlugins Site plugins array.
	 * @param array $pluginsMainWP MainWP plugins array.
	 * @param array $muPlugins Must use plugins array.
	 * @param array $pluginsName Plugin names array.
	 * @param array $pluginsVersion Installed plugins versions.
	 * @param array $pluginsRealVersion Latest plugin release version.
	 */
	public static function render_manage_table( $sites, $plugins, $sitePlugins, $pluginsMainWP, $muPlugins, $pluginsName, $pluginsVersion, $pluginsRealVersion ) {
		/**
		 * Action: mainwp_before_plugins_table
		 *
		 * Fires before the Plugins table.
		 *
		 * @since 4.1
		 */
		do_action( 'mainwp_before_plugins_table' );
		?>
		<table id="mainwp-manage-plugins-table" checked-select-all="0" style="min-width:100%" class="ui celled single line selectable compact unstackable table">
			<thead>
				<tr>
					<th class="mainwp-first-th no-sort"></th>
					<?php
					/**
					 * Action: mainwp_manage_plugins_table_header
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_table_header' );
					?>
					<?php foreach ( $pluginsVersion as $plugin_name => $plugin_info ) : ?>
						<?php
						$plugin_title = $plugin_info['name'] . ' ' . $plugin_info['ver'];
						$th_id        = strtolower( $plugin_name );
						$th_id        = preg_replace( '/[[:space:]]+/', '_', $th_id );
						?>
						<th id="<?php echo esc_html( $th_id ); ?>" class="center aligned mainwp-manage-plugins-plugin-name">
							<?php echo esc_html( $plugin_title ); ?>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sites as $site_id => $site_url ) : ?>
					<?php $website = MainWP_DB::instance()->get_website_by_id( $site_id ); ?>
				<tr>
					<td style="padding-left:40px;padding-right:40px;">
						<input class="websiteId" type="hidden" name="id" value="<?php echo intval( $site_id ); ?>"/>
						<div class="ui slider checkbox">
							<input type="checkbox" value="" id="<?php echo esc_url( $site_url ); ?>" class="mainwp_plugins_site_check_all"/><label></label>
						</div>
						<a href="<?php echo 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $site_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank" data-tooltip="<?php esc_html_e( 'Go to the site WP Admin', 'mainwp' ); ?>" data-inverted=""><i class="sign in alternate icon"></i></a>
						<a href="<?php echo esc_attr( $site_url ); ?>"><?php echo esc_html( $website->name ); ?></a>
					</td>
					<?php
					/**
					 * Action: mainwp_manage_plugins_table_column
					 *
					 * @param int $site_id Site ID.
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_table_column', $site_id );
					?>
					<?php foreach ( $pluginsVersion as $plugin_name => $plugin_info ) : ?>
						<?php
						$active_status_class = '';

						if ( isset( $sitePlugins[ $site_id ][ $plugin_name ]['active'] ) && 1 == $sitePlugins[ $site_id ][ $plugin_name ]['active'] ) {
							$active_status_class = 'positive';
						} elseif ( isset( $sitePlugins[ $site_id ][ $plugin_name ]['active'] ) && 0 == $sitePlugins[ $site_id ][ $plugin_name ]['active'] ) {
							$active_status_class = 'negative';
						} else {
							$active_status_class = '';
						}

						if ( isset( $sitePlugins[ $site_id ][ $plugin_name ] ) && ( 1 == $muPlugins[ $plugin_name ] ) ) {
							$active_status_class = 'warning';
						}

						?>
						<td class="center aligned <?php echo $active_status_class; ?>">
						<?php if ( isset( $sitePlugins[ $site_id ] ) && isset( $sitePlugins[ $site_id ][ $plugin_name ] ) && ( 0 == $muPlugins[ $plugin_name ] ) ) : ?>
							<?php if ( ! isset( $pluginsMainWP[ $plugin_name ] ) || 'F' === $pluginsMainWP[ $plugin_name ] ) : ?>
						<div class="ui checkbox">
							<input type="checkbox" value="<?php echo wp_strip_all_tags( $plugins[ $plugin_name ] ); ?>" name="<?php echo wp_strip_all_tags( $pluginsName[ $plugin_name ] ); ?>" class="mainwp-selected-plugin" version="<?php echo wp_strip_all_tags( $pluginsRealVersion[ $plugin_name ] ); ?>" />
								<label></label>
						</div>
					<?php elseif ( isset( $pluginsMainWP[ $plugin_name ] ) && 'T' === $pluginsMainWP[ $plugin_name ] ) : ?>
						<div class="ui disabled checkbox"><input type="checkbox" disabled="disabled"><label></label></div>
						<?php endif; ?>
					<?php endif; ?>
					</td>
					<?php endforeach; ?>
				</tr>
					<?php
				endforeach;
				?>
				</tbody>
			<tfoot>
				<tr>
					<th class="mainwp-first-th"></th>
					<?php
					/**
					 * Action: mainwp_manage_plugins_table_header
					 *
					 * @since 4.1
					 */
					do_action( 'mainwp_manage_plugins_table_header' );
					?>
					<?php foreach ( $pluginsVersion as $plugin_name => $plugin_info ) : ?>
						<?php
						$plugin_title = $plugin_info['name'] . ' ' . $plugin_info['ver'];
						$th_id        = strtolower( $plugin_name );
						$th_id        = preg_replace( '/[[:space:]]+/', '_', $th_id );
						?>
						<th id="<?php echo esc_html( $th_id ); ?>" class="center aligned">
							<div class="ui slider checkbox mainwp-768-hide">
								<input type="checkbox" value="<?php echo wp_strip_all_tags( $plugins[ $plugin_name ] ); ?>" id="<?php echo wp_strip_all_tags( $plugins[ $plugin_name ] . '-' . $pluginsRealVersion[ $plugin_name ] ); ?>" version="<?php echo wp_strip_all_tags( $pluginsRealVersion[ $plugin_name ] ); ?>" class="mainwp_plugin_check_all" />
								<label></label>
							</div>
						</th>
					<?php endforeach; ?>
				</tr>
			</tfoot>
			</table>
		<div class="ui horizontal list" id="mainwp-manage-plugins-table-legend">
			<div class="item"><a class="ui empty circular label" style="background:#f7ffe6;border:1px solid #7fb100;"></a> <?php echo esc_html__( 'Installed/Active', 'mainwp' ); ?></div>
			<div class="item"><a class="ui empty circular label" style="background:#fff4e4;border:1px solid #ffd598;"></a> <?php echo esc_html__( 'Installed/Active/Must Use', 'mainwp' ); ?></div>
			<div class="item"><a class="ui empty circular label" style="background:#ffe7e7;border:1px solid #910000;"></a> <?php echo esc_html__( 'Installed/Inactive', 'mainwp' ); ?></div>
			<div class="item"><a class="ui empty circular label" style="background:#fafafa;border:1px solid #f4f4f4;"></a> <?php echo esc_html__( 'Not installed', 'mainwp' ); ?></div>
		</div>
			<?php
			/**
			 * Action: mainwp_after_plugins_table
			 *
			 * Fires after the Plugins table.
			 *
			 * @since 4.1
			 */
			do_action( 'mainwp_after_plugins_table' );

			$table_features = array(
				'searching'      => 'true',
				'paging'         => 'true', // to fix.
				'info'           => 'false',
				'colReorder'     => 'false',
				'stateSave'      => 'true',
				'ordering'       => 'true',
				'scrollCollapse' => 'true',
				'scrollY'        => '750',
				'scrollX'        => 'true',
				'scroller'       => 'true',
				'responsive'     => 'true',
			);

			/**
			 * Filter: mainwp_plugins_table_features
			 *
			 * Filter the Plugins table features.
			 *
			 * @since 4.1
			 */
			$table_features = apply_filters( 'mainwp_plugins_table_features', $table_features );
			?>
			<style type="text/css">

			thead th.mainwp-first-th {
				position: sticky !important;
				left: 0  !important;
				top: 0  !important;
					z-index: 9 !important;
			}

			#mainwp-manage-plugins-table tbody tr td:first-child {
				position: sticky !important;
				left: 0;
				top: 0;
					background: #f9fafb;
					z-index: 9 !important;
			}
			</style>
			<script type="text/javascript">
			var responsive = <?php echo $table_features['responsive']; ?>;
			if( jQuery( window ).width() > 1140 ) {
				responsive = false;
			}
			jQuery( document ).ready( function( $ ) {

				try {
					jQuery( '#mainwp-manage-plugins-table' ).DataTable( {
						"paging" : <?php echo $table_features['paging']; ?>,
						"colReorder" : <?php echo $table_features['colReorder']; ?>,
						"stateSave" :  <?php echo $table_features['stateSave']; ?>,
						"ordering" : <?php echo $table_features['ordering']; ?>,
						"searching" : <?php echo $table_features['searching']; ?>,
						"info" : <?php echo $table_features['info']; ?>,
						"scrollCollapse" : <?php echo $table_features['scrollCollapse']; ?>,
						"scrollY" : <?php echo $table_features['scrollY']; ?>,
						"scrollX" : <?php echo $table_features['scrollX']; ?>,
						"scroller" : <?php echo $table_features['scroller']; ?>,
						"columnDefs": [ { "orderable": false, "targets": [ 0 ] } ],
						"responsive": responsive,
					} );
				} catch( err ) {
					// to fix js issues.
				}
				jQuery( '.mainwp-ui-page .ui.checkbox:not(.not-auto-init)' ).checkbox();
			} );
			</script>
		<?php
	}

	/** Render Install Subpage. */
	public static function render_install() {
		self::render_header( 'Install' );
		self::render_plugins_table();
		self::render_footer( 'Install' );
	}

	/**
	 * Render Install plugins Table.
	 *
	 * @uses \MainWP\Dashboard\MainWP_UI::render_modal_install_plugin_theme()
	 * @uses \MainWP\Dashboard\MainWP_UI::select_sites_box()
	 * @uses \MainWP\Dashboard\MainWP_Install_Bulk::render_upload()
	 */
	public static function render_plugins_table() {

		/**
		 * Tab array.
		 *
		 * @global object
		 */
		global $tab;

		if ( ! mainwp_current_user_have_right( 'dashboard', 'install_plugins' ) ) {
			mainwp_do_not_have_permissions( __( 'install plugins', 'mainwp' ) );
			return;
		}
		?>

	<div class="ui alt segment" id="mainwp-install-plugins">
		<div class="mainwp-main-content">
			<div class="mainwp-actions-bar">
				<div class="ui grid">
					<div class="ui two column row">
						<div class="column">
							<div id="mainwp-search-plugins-form" class="ui fluid search focus">
								<div class="ui icon fluid input">
									<input id="mainwp-search-plugins-form-field" class="fluid prompt" type="text" placeholder="<?php esc_attr_e( 'Search plugins...', 'mainwp' ); ?>" value="<?php echo isset( $_GET['s'] ) ? esc_html( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; ?>">
									<i class="search icon"></i>
								</div>
								<div class="results"></div>
							</div>
							<script type="text/javascript">
								jQuery( document ).ready(function () {
									jQuery( '#mainwp-search-plugins-form-field' ).on( 'keypress', function(e) {
										var search = jQuery( '#mainwp-search-plugins-form-field' ).val();
										var sel_ids = jQuery( '#plugin_install_selected_sites' ).val();										
										if ( '' != sel_ids )
											sel_ids = '&selected_sites=' + sel_ids;
										var origin   = '<?php echo get_admin_url(); ?>';
										if ( 13 === e.which ) {
											location.href = origin + 'admin.php?page=PluginsInstall&tab=search&s=' + encodeURIComponent(search) + sel_ids;
										}
									} );
								} );
							</script>
							<?php
							/**
							 * Install Plugins actions bar (left)
							 *
							 * Fires at the left side of the actions bar on the Install Plugins screen, after the Search bar.
							 *
							 * @since 4.0
							 */
							do_action( 'mainwp_install_plugins_actions_bar_left' );
							?>
						</div>
					<div class="right aligned column">
						<div class="ui buttons">
							<a href="#" id="MainWPInstallBulkNavSearch" class="ui button" ><?php esc_html_e( 'Install from WordPress.org', 'mainwp' ); ?></a>
							<div class="or"></div>
							<a href="#" id="MainWPInstallBulkNavUpload" class="ui button" ><?php esc_html_e( 'Upload .zip file', 'mainwp' ); ?></a>
						</div>
					<?php
					/**
					 * Install Plugins actions bar (right)
					 *
					 * Fires at the left side of the actions bar on the Install Plugins screen, after the Nav buttons.
					 *
					 * @since 4.0
					 */
					do_action( 'mainwp_install_plugins_actions_bar_right' );
					?>
					</div>
				</div>
			</div>
		</div>
		<div class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-install-plugins-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-install-plugins-info-message"></i>
					<?php echo sprintf( __( 'Install plugins on your child sites.  You can install plugins from the WordPress.org repository or by uploading a ZIP file.  For additional help, please check this %1$shelp documentation%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/install-plugins/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
			<div id="mainwp-message-zone" class="ui message" style="display:none;"></div>
			<div class="mainwp-upload-plugin" style="display:none;">
				<?php MainWP_Install_Bulk::render_upload( 'plugin' ); ?>
			</div>
			<div class="mainwp-browse-plugins">
				<form id="plugin-filter" method="post">
					<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
					<?php self::$pluginsTable->display(); ?>
				</form>
			</div>
			<?php
			MainWP_UI::render_modal_install_plugin_theme();
			MainWP_Updates::render_plugin_details_modal();
			?>
		</div>
	</div>
		<?php
		$selected_sites  = array();
		$selected_groups = array();

		if ( isset( $_GET['selected_sites'] ) && ! empty( $_GET['selected_sites'] ) ) {
			$selected_sites = explode( '-', sanitize_text_field( wp_unslash( $_GET['selected_sites'] ) ) ); // sanitize ok.
			$selected_sites = array_map( 'intval', $selected_sites );
			$selected_sites = array_filter( $selected_sites );
		}
		?>
	<div class="mainwp-side-content mainwp-no-padding">
		<?php do_action( 'mainwp_manage_plugins_sidebar_top' ); ?>
		<div class="mainwp-select-sites ui accordion mainwp-sidebar-accordion">
			<?php do_action( 'mainwp_manage_plugins_before_select_sites' ); ?>
			<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Select Sites', 'mainwp' ); ?></div>
			<div class="content active"><?php	MainWP_UI::select_sites_box( 'checkbox', true, true, 'mainwp_select_sites_box_left', '', $selected_sites, $selected_groups ); ?></div>
			<?php do_action( 'mainwp_manage_plugins_after_select_sites' ); ?>
		</div>
		<input type="hidden" id="plugin_install_selected_sites" name="plugin_install_selected_sites" value="<?php echo esc_html( implode( '-', $selected_sites ) ); ?>" />
		<div class="ui fitted divider"></div>
		<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
			<?php do_action( 'mainwp_manage_plugins_before_search_options' ); ?>
			<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Installation Options', 'mainwp' ); ?></div>
			<div class="content active">
			<div class="ui form">
				<div class="field">
					<div class="ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled, the plugin will be automatically activated after the installation.', 'mainwp' ); ?>" data-position="left center" data-inverted="">
						<input type="checkbox" value="1" checked="checked" id="chk_activate_plugin" />
						<label for="chk_activate_plugin"><?php esc_html_e( 'Activate after installation', 'mainwp' ); ?></label>
					</div>
				</div>
				<div class="field">
					<div class="ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled and the plugin already installed on the sites, the already installed version will be overwritten.', 'mainwp' ); ?>" data-position="left center" data-inverted="">
						<input type="checkbox" value="2" checked="checked" id="chk_overwrite" />
						<label for="chk_overwrite"><?php esc_html_e( 'Overwrite existing version', 'mainwp' ); ?></label>
					</div>
				</div>
			</div>
			</div>
			<?php do_action( 'mainwp_manage_plugins_after_search_options' ); ?>
		</div>
		<div class="ui fitted divider"></div>
		<div class="mainwp-search-submit">
			<?php do_action( 'mainwp_manage_plugins_before_submit_button' ); ?>
		<?php
		/**
		 * Disables plugin installation
		 *
		 * Filters whether file modifications are allowed on the Dashboard site. If not, installation process will be disabled too.
		 *
		 * @since 4.1
		 */
		$allow_install = apply_filters( 'file_mod_allowed', true, 'mainwp_install_plugin' );
		if ( $allow_install ) {
			?>
			<input type="button" value="<?php esc_attr_e( 'Complete Installation', 'mainwp' ); ?>" class="ui green big fluid button" id="mainwp_plugin_bulk_install_btn" bulk-action="install" name="bulk-install">
			<?php
		}
		?>
			<?php do_action( 'mainwp_manage_plugins_before_submit_button' ); ?>
		</div>
		<?php do_action( 'mainwp_manage_plugins_sidebar_bottom' ); ?>
	</div>
	<div class="ui clearing hidden divider"></div>
	</div>
		<?php
	}

	/**
	 * Render Autoupdate SubPage.
	 *
	 * @uses \MainWP\Dashboard\MainWP_UI::render_modal_edit_notes()
	 */
	public static function render_auto_update() {  // phpcs:ignore -- Current complexity is the only way to achieve desired results, pull request solutions appreciated.
		$cachedAUSearch = null;

		if ( isset( $_SESSION['MainWP_PluginsActiveStatus'] ) ) {
			$cachedAUSearch = $_SESSION['MainWP_PluginsActiveStatus'];
		}

		self::render_header( 'AutoUpdate' );

		if ( ! mainwp_current_user_have_right( 'dashboard', 'trust_untrust_updates' ) ) {
			mainwp_do_not_have_permissions( __( 'trust/untrust updates', 'mainwp' ) );
		} else {
			$snPluginAutomaticDailyUpdate = get_option( 'mainwp_pluginAutomaticDailyUpdate' );

			if ( false === $snPluginAutomaticDailyUpdate ) {
				$snPluginAutomaticDailyUpdate = get_option( 'mainwp_automaticDailyUpdate' );
				update_option( 'mainwp_pluginAutomaticDailyUpdate', $snPluginAutomaticDailyUpdate );
			}

			?>
			<div class="ui alt segment" id="mainwp-plugin-auto-updates">
				<div class="mainwp-main-content">
					<div class="mainwp-actions-bar">
						<div class="ui mini form stackable grid">
							<div class="ui two column row">
								<div class="left aligned column">
									<select id="mainwp-bulk-actions" name="bulk_action" class="ui mini selection dropdown">
										<option class="item" value=""><?php esc_html_e( 'Bulk Actions', 'mainwp' ); ?></option>
										<option class="item" value="trust"><?php esc_html_e( 'Trust', 'mainwp' ); ?></option>
										<option class="item" value="untrust"><?php esc_html_e( 'Untrust', 'mainwp' ); ?></option>
												<?php
												/**
												 * Action: mainwp_plugins_auto_updates_bulk_action
												 *
												 * Adds new action to the bulk actions menu on Plugins Auto Updates.
												 *
												 * @since 4.1
												 */
												do_action( 'mainwp_plugins_auto_updates_bulk_action' );
												?>
									</select>
										<input type="button" name="" id="mainwp-bulk-trust-plugins-action-apply" class="ui mini basic button" value="<?php esc_attr_e( 'Apply', 'mainwp' ); ?>"/>
									</div>
								<div class="right aligned column"></div>
							</div>
						</div>
					</div>

					<?php if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) : ?>
						<div class="ui message green"><?php esc_html_e( 'Settings have been saved.', 'mainwp' ); ?></div>
					<?php endif; ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<div id="mainwp-auto-updates-plugins-content" class="ui segment">
						<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-disable-auto-updates-info-message' ) ) : ?>
						<div class="ui info message">
							<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-disable-auto-updates-info-message"></i>
							<div><?php echo sprintf( __( 'Check out %1$show to disable the WordPress built in auto-updates feature%2$s.', 'mainwp' ), '<a href="https://mainwp.com/how-to-disable-automatic-plugin-and-theme-updates-on-your-child-sites/" target="_blank">', '</a>' ); ?></div>
						</div>
						<?php endif; ?>
						<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-plugins-auto-updates-info-message' ) ) : ?>
						<div class="ui info message">
							<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-plugins-auto-updates-info-message"></i>
							<div><?php esc_html_e( 'The MainWP Advanced Auto Updates feature is a tool for your Dashboard to automatically update plugins that you trust to be updated without breaking your Child sites.', 'mainwp' ); ?></div>
							<div><?php esc_html_e( 'Only mark plugins as trusted if you are absolutely sure they can be automatically updated by your MainWP Dashboard without causing issues on the Child sites!', 'mainwp' ); ?></div>
							<div><strong><?php esc_html_e( 'Advanced Auto Updates a delayed approximately 24 hours from the update release.  Ignored plugins can not be automatically updated.', 'mainwp' ); ?></strong></div>
						</div>
						<?php endif; ?>
						<div class="ui inverted dimmer">
							<div class="ui text loader"><?php esc_html_e( 'Loading plugins', 'mainwp' ); ?></div>
						</div>
						<div id="mainwp-auto-updates-plugins-table-wrapper">
						<?php
						if ( isset( $_SESSION['MainWP_PluginsActive'] ) ) {
							self::render_all_active_table( $_SESSION['MainWP_PluginsActive'] );
						}
						?>
					</div>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<?php do_action( 'mainwp_manage_plugins_sidebar_top' ); ?>
				<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
					<?php do_action( 'mainwp_manage_plugins_before_search_options' ); ?>
					<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Plugin Status to Search', 'mainwp' ); ?></div>
					<div class="content active">
					<div class="ui mini form">
						<div class="field">
							<select class="ui fluid dropdown" id="mainwp_au_plugin_status">
								<option value="all" <?php echo ( null != $cachedAUSearch && 'all' === $cachedAUSearch['plugin_status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Active and Inactive', 'mainwp' ); ?></option>
								<option value="active" <?php echo ( null != $cachedAUSearch && 'active' === $cachedAUSearch['plugin_status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Active', 'mainwp' ); ?></option>
								<option value="inactive" <?php echo ( null != $cachedAUSearch && 'inactive' === $cachedAUSearch['plugin_status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Inactive', 'mainwp' ); ?></option>
							</select>
						</div>
					</div>
					</div>
					<?php do_action( 'mainwp_manage_plugins_after_search_options' ); ?>
				</div>
				<div class="ui fitted divider"></div>
				<div class="mainwp-search-options ui accordion mainwp-sidebar-accordion">
					<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Search Options', 'mainwp' ); ?></div>
					<div class="content active">
					<div class="ui mini form">
						<div class="field">
							<select class="ui fluid dropdown" id="mainwp_au_plugin_trust_status">
								<option value="all" <?php echo ( null != $cachedAUSearch && 'all' === $cachedAUSearch['status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Trusted, Not trusted and Ignored', 'mainwp' ); ?></option>
								<option value="trust" <?php echo ( null != $cachedAUSearch && 'trust' === $cachedAUSearch['status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Trusted', 'mainwp' ); ?></option>
								<option value="untrust" <?php echo ( null != $cachedAUSearch && 'untrust' === $cachedAUSearch['status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Not trusted', 'mainwp' ); ?></option>
								<option value="ignored" <?php echo ( null != $cachedAUSearch && 'ignored' === $cachedAUSearch['status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Ignored', 'mainwp' ); ?></option>
							</select>
						</div>
						<div class="field">
							<div class="ui input fluid">
								<input type="text" placeholder="<?php esc_attr_e( 'Plugin name', 'mainwp' ); ?>" id="mainwp_au_plugin_keyword" class="text" value="<?php echo ( null !== $cachedAUSearch ) ? $cachedAUSearch['keyword'] : ''; ?>">
							</div>
						</div>
					</div>
				</div>
				</div>
				<div class="ui fitted divider"></div>
				<div class="mainwp-search-submit">
					<?php do_action( 'mainwp_manage_plugins_before_submit_button' ); ?>
					<a href="#" class="ui green big fluid button" id="mainwp_show_all_active_plugins"><?php esc_html_e( 'Show Plugins', 'mainwp' ); ?></a>
					<?php do_action( 'mainwp_manage_plugins_after_submit_button' ); ?>
				</div>
				<?php do_action( 'mainwp_manage_plugins_sidebar_bottom' ); ?>
			</div>
		</div>
			<?php
			MainWP_UI::render_modal_edit_notes( 'plugin' );
		}
		self::render_footer( 'AutoUpdate' );
	}

	/**
	 * Method render_all_active_table()
	 *
	 * Render all active Plugins table.
	 *
	 * @param null $output function output.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Connect::fetch_url_authed()
	 * @uses \MainWP\Dashboard\MainWP_DB_Common::get_user_extension()
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites_for_current_user()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 * @uses \MainWP\Dashboard\MainWP_Plugins_Handler::get_class_name()
	 * @uses \MainWP\Dashboard\MainWP_Utility::map_site()
	 * @uses \MainWP\Dashboard\MainWP_Utility::get_nice_url()
	 */
	public static function render_all_active_table( $output = null ) { // phpcs:ignore -- not quite complex function.
		$keyword       = null;
		$search_status = 'all';

		if ( null == $output ) {
			$keyword              = isset( $_POST['keyword'] ) && ! empty( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : null;
			$search_status        = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'all';
			$search_plugin_status = isset( $_POST['plugin_status'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_status'] ) ) : 'all';

			$output          = new \stdClass();
			$output->errors  = array();
			$output->plugins = array();

			if ( 1 == get_option( 'mainwp_optimize' ) ) {
				$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_for_current_user() );
				while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
					$allPlugins = json_decode( $website->plugins, true );
					$_count     = count( $allPlugins );
					for ( $i = 0; $i < $_count; $i ++ ) {
						$plugin = $allPlugins[ $i ];
						if ( 'all' !== $search_plugin_status ) {
							if ( 1 == $plugin['active'] && 'active' !== $search_plugin_status ) {
								continue;
							} elseif ( 1 != $plugin['active'] && 'inactive' !== $search_plugin_status ) {
								continue;
							}
						}
						if ( '' != $keyword && false === stristr( $plugin['name'], $keyword ) ) {
							continue;
						}
						$plugin['websiteid']  = $website->id;
						$plugin['websiteurl'] = $website->url;
						$output->plugins[]    = $plugin;
					}
				}
				MainWP_DB::free_result( $websites );
			} else {
				$dbwebsites = array();
				$websites   = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_for_current_user() );
				while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
					$dbwebsites[ $website->id ] = MainWP_Utility::map_site(
						$website,
						array(
							'id',
							'url',
							'name',
							'adminname',
							'nossl',
							'privkey',
							'nosslkey',
							'http_user',
							'http_pass',
							'ssl_version',
						)
					);
				}
				MainWP_DB::free_result( $websites );

				$post_data = array( 'keyword' => $keyword );

				if ( 'active' === $search_plugin_status || 'inactive' === $search_plugin_status ) {
					$post_data['status'] = $search_plugin_status;
					$post_data['filter'] = true;
				} else {
					$post_data['status'] = '';
					$post_data['filter'] = false;
				}

				MainWP_Connect::fetch_urls_authed( $dbwebsites, 'get_all_plugins', $post_data, array( MainWP_Plugins_Handler::get_class_name(), 'plugins_search_handler' ), $output );

				if ( 0 < count( $output->errors ) ) {
					foreach ( $output->errors as $siteid => $error ) {
						echo MainWP_Utility::get_nice_url( $dbwebsites[ $siteid ]->url ) . ' - ' . $error . ' <br/>';

					}
					echo '<div class="ui hidden divider"></div>';

					if ( count( $output->errors ) == count( $dbwebsites ) ) {
						$_SESSION['MainWP_PluginsActive']       = $output;
						$_SESSION['MainWP_PluginsActiveStatus'] = array(
							'keyword'       => $keyword,
							'status'        => $search_status,
							'plugin_status' => $search_plugin_status,
						);
						return;
					}
				}
			}

			$_SESSION['MainWP_PluginsActive']       = $output;
			$_SESSION['MainWP_PluginsActiveStatus'] = array(
				'keyword'       => $keyword,
				'status'        => $search_status,
				'plugin_status' => $search_plugin_status,
			);
		} else {
			if ( isset( $_SESSION['MainWP_PluginsActiveStatus'] ) ) {
				$keyword              = $_SESSION['MainWP_PluginsActiveStatus']['keyword'];
				$search_status        = $_SESSION['MainWP_PluginsActiveStatus']['status'];
				$search_plugin_status = $_SESSION['MainWP_PluginsActiveStatus']['plugin_status'];
			}
		}

		if ( 'inactive' !== $search_plugin_status ) {
			if ( empty( $keyword ) || ( ! empty( $keyword ) && false !== stristr( 'MainWP Child', $keyword ) ) ) {
				$output->plugins[] = array(
					'slug'   => 'mainwp-child/mainwp-child.php',
					'name'   => 'MainWP Child',
					'active' => 1,
				);
			}
		}

		if ( 0 == count( $output->plugins ) ) {
			?>
			<div class="ui message yellow"><?php esc_html_e( 'No plugins found.', 'mainwp' ); ?></div>
			<?php
			return;
		}

		$plugins = array();
		foreach ( $output->plugins as $plugin ) {
			$plugins[ $plugin['slug'] ] = $plugin;
		}
		asort( $plugins );

		$userExtension         = MainWP_DB_Common::instance()->get_user_extension();
		$decodedIgnoredPlugins = json_decode( $userExtension->ignored_plugins, true );
		$trustedPlugins        = json_decode( $userExtension->trusted_plugins, true );

		if ( ! is_array( $trustedPlugins ) ) {
			$trustedPlugins = array();
		}
		$trustedPluginsNotes = json_decode( $userExtension->trusted_plugins_notes, true );
		if ( ! is_array( $trustedPluginsNotes ) ) {
			$trustedPluginsNotes = array();
		}
		self::render_all_active_html( $plugins, $trustedPlugins, $search_status, $decodedIgnoredPlugins, $trustedPluginsNotes );
	}


	/**
	 * Method render_all_active_html()
	 *
	 * Render all active plugins html.
	 *
	 * @param array $plugins Plugins array.
	 * @param array $trustedPlugins Trusted plugins array.
	 * @param mixed $search_status trust|untrust|ignored.
	 * @param array $decodedIgnoredPlugins Decoded ignored plugins array.
	 * @param array $trustedPluginsNotes Trusted plugins notes.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Utility::esc_content()
	 */
	public static function render_all_active_html( $plugins, $trustedPlugins, $search_status, $decodedIgnoredPlugins, $trustedPluginsNotes ) {

		/**
		 * Action: mainwp_plugins_before_auto_updates_table
		 *
		 * Fires before the Auto Update Plugins table.
		 *
		 * @since 4.1
		 */
		do_action( 'mainwp_plugins_before_auto_updates_table' );
		?>
		<table class="ui unstackable table" id="mainwp-all-active-plugins-table">
			<thead>
				<tr>
					<th class="no-sort check-column collapsing"><span class="ui checkbox"><input id="cb-select-all-top" type="checkbox" /></span></th>
					<th data-priority="1"><?php esc_html_e( 'Plugin', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Status', 'mainwp' ); ?></th>
					<th class="collapsing" data-priority="2"><?php esc_html_e( 'Trust Status', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Ignored Status', 'mainwp' ); ?></th>
					<th class="collapsing"></th>
					<th class="collapsing"><?php esc_html_e( 'Notes', 'mainwp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $plugins as $slug => $plugin ) : ?>
					<?php
					$name = $plugin['name'];
					if ( ! empty( $search_status ) && 'all' !== $search_status ) {
						if ( 'trust' === $search_status && ! in_array( $slug, $trustedPlugins ) ) {
							continue;
						} elseif ( 'untrust' === $search_status && in_array( $slug, $trustedPlugins ) ) {
							continue;
						} elseif ( 'ignored' === $search_status && ! isset( $decodedIgnoredPlugins[ $slug ] ) ) {
							continue;
						}
					}
					$esc_note   = '';
					$strip_note = '';
					if ( isset( $trustedPluginsNotes[ $slug ] ) ) {
						$esc_note   = MainWP_Utility::esc_content( $trustedPluginsNotes[ $slug ] );
						$strip_note = wp_strip_all_tags( $esc_note );
					}
					?>
					<tr plugin-slug="<?php echo rawurlencode( $slug ); ?>" plugin-name="<?php echo wp_strip_all_tags( $name ); ?>">
						<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="plugin[]" value="<?php echo rawurlencode( $slug ); ?>"></span></td>
						<td><a href="<?php echo admin_url() . 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( dirname( $slug ) ) . '&TB_iframe=true&width=640&height=477'; ?>" target="_blank"><?php echo esc_html( $name ); ?></a></td>
						<td><?php echo ( 1 == $plugin['active'] ) ? esc_html__( 'Active', 'mainwp' ) : esc_html__( 'Inactive', 'mainwp' ); ?></td>
						<td><?php echo ( in_array( $slug, $trustedPlugins ) ) ? '<span class="ui mini green fluid center aligned label">' . esc_html__( 'Trusted', 'mainwp' ) . '</span>' : '<span class="ui mini red fluid center aligned label">' . esc_html__( 'Not Trusted', 'mainwp' ) . '</span>'; ?></td>
						<td><?php echo ( isset( $decodedIgnoredPlugins[ $slug ] ) ) ? '<span class="ui mini label">' . esc_html__( 'Ignored', 'mainwp' ) . '</span>' : ''; ?></td>
						<td><?php echo ( isset( $decodedIgnoredPlugins[ $slug ] ) ) ? '<span data-tooltip="Ignored plugins will not be automatically updated." data-inverted=""><i class="info red circle icon" ></i></span>' : ''; ?></td>
						<td class="collapsing center aligned">
						<?php if ( '' === $esc_note ) : ?>
							<a href="javascript:void(0)" class="mainwp-edit-plugin-note" ><i class="sticky note outline icon"></i></a>
						<?php else : ?>
							<a href="javascript:void(0)" class="mainwp-edit-plugin-note" data-tooltip="<?php echo substr( $strip_note, 0, 100 ); ?>" data-position="left center" data-inverted=""><i class="sticky green note icon"></i></a>
						<?php endif; ?>
							<span style="display: none" class="esc-content-note"><?php echo $esc_note; ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort check-column"><span class="ui checkbox"><input id="cb-select-all-bottom" type="checkbox" /></span></th>
					<th></th>
					<th><?php esc_html_e( 'Plugin', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Status', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Trust Status', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Ignored Status', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Notes', 'mainwp' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php
		/**
		 * Action: mainwp_plugins_after_auto_updates_table
		 *
		 * Fires after the Auto Update Plugins table.
		 *
		 * @since 4.1
		 */
		do_action( 'mainwp_plugins_after_auto_updates_table' );

		$table_features = array(
			'searching'  => 'true',
			'stateSave'  => 'true',
			'colReorder' => 'true',
			'info'       => 'true',
			'paging'     => 'false',
			'ordering'   => 'true',
			'order'      => '[ [ 2, "asc" ] ]',
			'responsive' => 'true',
		);

		/**
		 * Filter: mainwp_plugin_auto_updates_table_fatures
		 *
		 * Filters the Plugin Auto Updates table features.
		 *
		 * @since 4.1
		 */
		$table_features = apply_filters( 'mainwp_plugin_auto_updates_table_fatures', $table_features );
		?>
		<script type="text/javascript">
		var responsive = <?php echo $table_features['responsive']; ?>;
			if( jQuery( window ).width() > 1140 ) {
				responsive = false;
			}
		jQuery( document ).ready( function() {
			jQuery( '#mainwp-all-active-plugins-table' ).DataTable( {
				"searching" : <?php echo $table_features['searching']; ?>,
				"stateSave" : <?php echo $table_features['stateSave']; ?>,
				"colReorder" : <?php echo $table_features['colReorder']; ?>,
				"info" : <?php echo $table_features['info']; ?>,
				"paging" : <?php echo $table_features['paging']; ?>,
				"ordering" : <?php echo $table_features['ordering']; ?>,
				"order" : <?php echo $table_features['order']; ?>,
				"columnDefs": [ { "orderable": false, "targets": [ 0, 1, 6 ] } ],
				"responsive": responsive,
			} );
		} );
		</script>

		<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery( '.mainwp-ui-page .ui.checkbox:not(.not-auto-init)' ).checkbox();
			} );
		</script>
		<?php
	}

	/**
	 * Render Ignore Subpage.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB_Common::get_user_extension()
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites_for_current_user()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 */
	public static function render_ignore() {
		$websites              = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_for_current_user() );
		$userExtension         = MainWP_DB_Common::instance()->get_user_extension();
		$decodedIgnoredPlugins = json_decode( $userExtension->ignored_plugins, true );
		$ignoredPlugins        = ( is_array( $decodedIgnoredPlugins ) && ( 0 < count( $decodedIgnoredPlugins ) ) );

		$cnt = 0;

		while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
			if ( $website->is_ignorePluginUpdates ) {
				continue;
			}

			$tmpDecodedIgnoredPlugins = json_decode( $website->ignored_plugins, true );

			if ( ! is_array( $tmpDecodedIgnoredPlugins ) || 0 == count( $tmpDecodedIgnoredPlugins ) ) {
				continue;
			}

				$cnt ++;
		}

		self::render_header( 'Ignore' );
		?>
		<div id="mainwp-ignored-plugins" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-ignored-plugins-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-ignored-plugins-info-message"></i>
					<?php echo sprintf( __( 'Manage plugins you have told your MainWP Dashboard to ignore updates on global or per site level.  For additional help, please check this %1$shelp documentation%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/ignore-plugin-updates/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
			<?php
			/**
			 * Action: mainwp_plugins_before_ignored_updates
			 *
			 * Fires on the top of the Ignored Plugins Updates page.
			 *
			 * @since 4.1
			 */
			do_action( 'mainwp_plugins_before_ignored_updates', $ignoredPlugins, $websites );
			?>
			<h3 class="ui header">
				<?php esc_html_e( 'Globally Ignored Plugins', 'mainwp' ); ?>
				<div class="sub header"><?php esc_html_e( 'These are plugins you have told your MainWP Dashboard to ignore updates on global level and not notify you about pending updates.', 'mainwp' ); ?></div>
			</h3>
			<?php self::render_global_ignored( $ignoredPlugins, $decodedIgnoredPlugins ); ?>
			<div class="ui hidden divider"></div>
			<h3 class="ui header">
				<?php esc_html_e( 'Per Site Ignored Plugins' ); ?>
				<div class="sub header"><?php esc_html_e( 'These are plugins you have told your MainWP Dashboard to ignore updates per site level and not notify you about pending updates.', 'mainwp' ); ?></div>
			</h3>
			<?php self::render_sites_ignored( $cnt, $websites ); ?>
			<?php
			/**
			 * Action: mainwp_plugins_after_ignored_updates
			 *
			 * Fires on the bottom of the Ignored Plugins Updates page.
			 *
			 * @since 4.1
			 */
			do_action( 'mainwp_plugins_after_ignored_updates', $ignoredPlugins, $websites );
			?>
		</div>
		<?php
		self::render_footer( 'Ignore' );
	}

	/**
	 * Method render_global_ignored()
	 *
	 * Render Global Ignored plugins list.
	 *
	 * @param array $ignoredPlugins Ignored plugins array.
	 * @param array $decodedIgnoredPlugins Decoded ignored plugins array.
	 */
	public static function render_global_ignored( $ignoredPlugins, $decodedIgnoredPlugins ) {
		?>
		<table id="mainwp-globally-ignored-plugins" class="ui compact selectable table unstackable">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plugin', 'mainwp' ); ?></th>
						<th><?php esc_html_e( 'Plugin slug', 'mainwp' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="globally-ignored-plugins-list">
					<?php if ( $ignoredPlugins ) : ?>
						<?php foreach ( $decodedIgnoredPlugins as $ignoredPlugin => $ignoredPluginName ) : ?>
							<tr plugin-slug="<?php echo rawurlencode( $ignoredPlugin ); ?>">
								<td><a href="<?php echo admin_url() . 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( dirname( $ignoredPlugin ) ) . '&TB_iframe=true&width=640&height=477'; ?>" target="_blank"><?php echo esc_html( $ignoredPluginName ); ?></a></td>
								<td><?php echo esc_html( $ignoredPlugin ); ?></td>
								<td class="right aligned">
									<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
										<a href="#" class="ui mini button" onClick="return updatesoverview_plugins_unignore_globally( '<?php echo rawurlencode( $ignoredPlugin ); ?>' )"><?php esc_html_e( 'Unignore', 'mainwp' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="3"><?php esc_html_e( 'No ignored plugins', 'mainwp' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
				<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
					<?php if ( $ignoredPlugins ) : ?>
						<tfoot class="full-width">
							<tr>
								<th colspan="3">
									<a class="ui right floated small green labeled icon button" onClick="return updatesoverview_plugins_unignore_globally_all();" id="mainwp-unignore-globally-all">
										<i class="check icon"></i> <?php esc_html_e( 'Unignore All', 'mainwp' ); ?>
									</a>
								</th>
							</tr>
						</tfoot>
					<?php endif; ?>
				<?php endif; ?>
			</table>
			<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery( '#mainwp-globally-ignored-plugins' ).DataTable( {
					searching: false,
					paging: false,
					info: false,
					responsive: true,
				} );
			} );
			</script>
		<?php
	}

	/**
	 * Render Per Site Ignored table.
	 *
	 * @param mixed $cnt Plugin count.
	 * @param mixed $websites Child Sites.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::data_seek()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 */
	public static function render_sites_ignored( $cnt, $websites ) {
		?>
		<table id="mainwp-per-site-ignored-plugins" class="ui unstackable compact selectable table ">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Site', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Plugin', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Plugin slug', 'mainwp' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="ignored-plugins-list">
				<?php if ( 0 < $cnt ) : ?>
					<?php
					MainWP_DB::data_seek( $websites, 0 );

					while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
						if ( $website->is_ignorePluginUpdates ) {
							continue;
						}

						$decodedIgnoredPlugins = json_decode( $website->ignored_plugins, true );
						if ( ! is_array( $decodedIgnoredPlugins ) || 0 == count( $decodedIgnoredPlugins ) ) {
							continue;
						}
						$first = true;

						foreach ( $decodedIgnoredPlugins as $ignoredPlugin => $ignoredPluginName ) {
							?>
							<tr site-id="<?php echo intval( $website->id ); ?>" plugin-slug="<?php echo rawurlencode( $ignoredPlugin ); ?>">
							<?php if ( $first ) : ?>
								<td><div><a href="<?php echo admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ); ?>"><?php echo stripslashes( $website->name ); ?></a></div></td>
								<?php $first = false; ?>
							<?php else : ?>
								<td><div style="display:none;"><a href="<?php echo admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ); ?>"><?php echo stripslashes( $website->name ); ?></a></div></td>
							<?php endif; ?>
							<td><a href="<?php echo admin_url() . 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( dirname( $ignoredPlugin ) ) . '&TB_iframe=true&width=640&height=477'; ?>" target="_blank"><?php echo esc_html( $ignoredPluginName ); ?></a></td>
							<td><?php echo esc_html( $ignoredPlugin ); ?></td>
							<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
								<td class="right aligned"><a href="#" class="ui mini button" onClick="return updatesoverview_plugins_unignore_detail( '<?php echo rawurlencode( $ignoredPlugin ); ?>', <?php echo esc_attr( $website->id ); ?> )"> <?php esc_html_e( 'Unignore', 'mainwp' ); ?></a></td>
							<?php endif; ?>
						</tr>
							<?php
						}
					}

					MainWP_DB::free_result( $websites );
					?>
				<?php else : ?>
					<tr><td colspan="999"><?php esc_html_e( 'No ignored plugins', 'mainwp' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
			<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
				<?php if ( 0 < $cnt ) : ?>
					<tfoot class="full-width">
						<tr>
							<th colspan="4">
								<a class="ui right floated small green labeled icon button" onClick="return updatesoverview_plugins_unignore_detail_all();" id="mainwp-unignore-detail-all">
									<i class="check icon"></i> <?php esc_html_e( 'Unignore All', 'mainwp' ); ?>
								</a>
							</th>
						</tr>
					</tfoot>
				<?php endif; ?>
			<?php endif; ?>
		</table>
		<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( '#mainwp-per-site-ignored-plugins' ).DataTable( {
				"responsive": true,
				"searching": false,
				"paging": false,
				"info": false,
			} );
		} );
		</script>
		<?php
	}

	/**
	 * Render Ignored Abandoned Page.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB_Common::get_user_extension()
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites_for_current_user()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_option()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 */
	public static function render_ignored_abandoned() {
		$websites              = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_for_current_user() );
		$userExtension         = MainWP_DB_Common::instance()->get_user_extension();
		$decodedIgnoredPlugins = json_decode( $userExtension->dismissed_plugins, true );
		$ignoredPlugins        = ( is_array( $decodedIgnoredPlugins ) && ( 0 < count( $decodedIgnoredPlugins ) ) );
		$cnt                   = 0;
		while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
			$tmpDecodedDismissedPlugins = json_decode( MainWP_DB::instance()->get_website_option( $website, 'plugins_outdate_dismissed' ), true );
			if ( ! is_array( $tmpDecodedDismissedPlugins ) || 0 == count( $tmpDecodedDismissedPlugins ) ) {
				continue;
			}
			$cnt ++;
		}

		self::render_header( 'IgnoreAbandoned' );
		?>
		<div id="mainwp-ignored-abandoned-plugins" class="ui segment">
			<?php if ( MainWP_Utility::show_mainwp_message( 'notice', 'mainwp-ignored-abandoned-plugins-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-ignored-abandoned-plugins-info-message"></i>
					<?php echo sprintf( __( 'Manage plugins you have told your MainWP Dashboard to ignore updates on global or per site level.  For additional help, please check this %1$shelp documentation%2$s.', 'mainwp' ), '<a href="https://kb.mainwp.com/docs/abandoned-plugins/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
			<?php
			/**
			 * Action: mainwp_plugins_before_ignored_abandoned
			 *
			 * Fires on the top of the Ignored Plugins Abandoned page.
			 *
			 * @since 4.1
			 */
			do_action( 'mainwp_plugins_before_ignored_abandoned', $ignoredPlugins, $websites );
			?>
			<h3 class="ui header">
				<?php esc_html_e( 'Globally Ignored Abandoned Plugins' ); ?>
				<div class="sub header"><?php esc_html_e( 'These are plugins you have told your MainWP Dashboard to ignore on global level even though they have passed your Abandoned Plugin Tolerance date', 'mainwp' ); ?></div>
			</h3>
			<?php self::render_global_ignored_abandoned( $ignoredPlugins, $decodedIgnoredPlugins ); ?>
			<div class="ui hidden divider"></div>
			<h3 class="ui header">
				<?php esc_html_e( 'Per Site Ignored Abandoned Plugins' ); ?>
				<div class="sub header"><?php esc_html_e( 'These are plugins you have told your MainWP Dashboard to ignore per site level even though they have passed your Abandoned Plugin Tolerance date', 'mainwp' ); ?></div>
			</h3>
			<?php self::render_sites_ignored_abandoned( $cnt, $websites ); ?>
			<?php
			/**
			 * Action: mainwp_plugins_after_ignored_abandoned
			 *
			 * Fires on the bottom of the Ignored Plugins Abandoned page.
			 *
			 * @since 4.1
			 */
			do_action( 'mainwp_plugins_after_ignored_abandoned', $ignoredPlugins, $websites );
			?>
		</div>
		<?php
		self::render_footer( 'IgnoreAbandoned' );
	}

	/**
	 * Method render_global_ignored_abandoned()
	 *
	 * Render Global Ignored Abandoned table.
	 *
	 * @param array $ignoredPlugins Ignored plugins array.
	 * @param array $decodedIgnoredPlugins Decoded dgnored plugins array.
	 */
	public static function render_global_ignored_abandoned( $ignoredPlugins, $decodedIgnoredPlugins ) {
		?>
		<table id="mainwp-globally-ignored-abandoned-plugins" class="ui compact selectable table unstackable">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Plugin', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Plugin slug', 'mainwp' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="ignored-abandoned-plugins-list">
				<?php if ( $ignoredPlugins ) : ?>
					<?php foreach ( $decodedIgnoredPlugins as $ignoredPlugin => $ignoredPluginName ) : ?>
						<tr plugin-slug="<?php echo rawurlencode( $ignoredPlugin ); ?>">
							<td><a href="<?php echo admin_url() . 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( dirname( $ignoredPlugin ) ) . '&TB_iframe=true&width=640&height=477'; ?>" target="_blank"><?php echo esc_html( $ignoredPluginName ); ?></a></td>
							<td><?php echo esc_html( $ignoredPlugin ); ?></td>
							<td class="right aligned">
								<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
									<a href="#" class="ui mini button" onClick="return updatesoverview_plugins_abandoned_unignore_globally( '<?php echo rawurlencode( $ignoredPlugin ); ?>' )"><?php esc_html_e( 'Unignore', 'mainwp' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="999"><?php esc_html_e( 'No ignored abandoned plugins.', 'mainwp' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
			<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
				<?php if ( $ignoredPlugins ) : ?>
					<tfoot class="full-width">
						<tr>
							<th colspan="3">
								<a class="ui right floated small green labeled icon button" onClick="return updatesoverview_plugins_abandoned_unignore_globally_all();" id="mainwp-unignore-globally-all">
									<i class="check icon"></i> <?php esc_html_e( 'Unignore All', 'mainwp' ); ?>
								</a>
							</th>
						</tr>
					</tfoot>
				<?php endif; ?>
			<?php endif; ?>
		</table>
		<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( '#mainwp-globally-ignored-abandoned-plugins' ).DataTable( {
				"responsive": true,
				"searching": false,
				"paging": false,
				"info": false,
			} );
		} );
		</script>
		<?php
	}

	/**
	 * Method render_sites_ignored_abandoned()
	 *
	 * Render Per Site Ignored Abandoned Table.
	 *
	 * @param int    $cnt Plugins count.
	 * @param object $websites The websites object.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_option()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 */
	public static function render_sites_ignored_abandoned( $cnt, $websites ) {
		?>
		<table id="mainwp-per-site-ignored-abandoned-plugins" class="ui compact selectable table unstackable">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Site', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Plugin', 'mainwp' ); ?></th>
					<th><?php esc_html_e( 'Plugin slug', 'mainwp' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="ignored-abandoned-plugins-list">
				<?php if ( 0 < $cnt ) : ?>
					<?php
					MainWP_DB::data_seek( $websites, 0 );

					while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
						$decodedIgnoredPlugins = json_decode( MainWP_DB::instance()->get_website_option( $website, 'plugins_outdate_dismissed' ), true );
						if ( ! is_array( $decodedIgnoredPlugins ) || 0 == count( $decodedIgnoredPlugins ) ) {
							continue;
						}
						$first = true;
						foreach ( $decodedIgnoredPlugins as $ignoredPlugin => $ignoredPluginName ) {
							?>
					<tr site-id="<?php echo esc_attr( $website->id ); ?>" plugin-slug="<?php echo rawurlencode( $ignoredPlugin ); ?>">
							<?php if ( $first ) : ?>
							<td>
								<a href="<?php echo admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ); ?>"><?php echo stripslashes( $website->name ); ?></a>
							</td>
								<?php $first = false; ?>
						<?php else : ?>
							<td><div style="display:none;"><a href="<?php echo admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ); ?>"><?php echo stripslashes( $website->name ); ?></a></div></td>
						<?php endif; ?>
						<td><a href="<?php echo admin_url() . 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( dirname( $ignoredPlugin ) ) . '&TB_iframe=true&width=640&height=477'; ?>" target="_blank"><?php echo esc_html( $ignoredPluginName ); ?></a></td>
						<td><?php echo esc_html( $ignoredPlugin ); ?></td>
						<td class="right aligned"><a href="#" class="ui mini button" onClick="return updatesoverview_plugins_unignore_abandoned_detail( '<?php echo rawurlencode( $ignoredPlugin ); ?>', <?php echo esc_attr( $website->id ); ?> )"> <?php esc_html_e( 'Unignore', 'mainwp' ); ?></a></td>
					</tr>
							<?php
						}
					}

					MainWP_DB::free_result( $websites );

		else :
			?>
			<tr>
				<td colspan="4"><?php esc_html_e( 'No ignored abandoned plugins', 'mainwp' ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
		<?php if ( mainwp_current_user_have_right( 'dashboard', 'ignore_unignore_updates' ) ) : ?>
			<?php if ( 0 < $cnt ) : ?>
				<tfoot class="full-width">
					<tr>
						<th colspan="4">
							<a class="ui right floated small green labeled icon button" onClick="return updatesoverview_plugins_unignore_abandoned_detail_all();" id="mainwp-unignore-detail-all">
								<i class="check icon"></i> <?php esc_html_e( 'Unignore All', 'mainwp' ); ?>
							</a>
						</th>
					</tr>
				</tfoot>
			<?php endif; ?>
		<?php endif; ?>
		</table>
		<script type="text/javascript">
		jQuery( document ).ready( function() {
			jQuery( '#mainwp-per-site-ignored-abandoned-plugins' ).DataTable( {
				"responsive": true,
				"searching": false,
				"paging": false,
				"info": false,
			} );
		} );
		</script>
		<?php
	}

	/**
	 * Hooks the section help content to the Help Sidebar element.
	 */
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( 'PluginsManage' === $_GET['page'] || 'PluginsInstall' === $_GET['page'] || 'PluginsAutoUpdate' === $_GET['page'] || 'PluginsIgnore' === $_GET['page'] || 'PluginsIgnoredAbandoned' === $_GET['page'] ) ) {
			?>
			<p><?php esc_html_e( 'If you need help with managing plugins, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://kb.mainwp.com/docs/managing-plugins-with-mainwp/" target="_blank">Managing Plugins with MainWP</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/install-plugins/" target="_blank">Install Plugins</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/activate-plugins/" target="_blank">Activate Plugins</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/delete-plugins/" target="_blank">Delete Plugins</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/abandoned-plugins/" target="_blank">Abandoned Plugins</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/update-plugins/" target="_blank">Update Plugins</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/plugins-auto-updates/" target="_blank">Plugins Auto Updates</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/ignore-plugin-updates/" target="_blank">Ignore Plugin Updates</a></div>
				<?php
				/**
				 * Action: mainwp_plugins_help_item
				 *
				 * Fires at the bottom of the help articles list in the Help sidebar on the Plugins page.
				 *
				 * Suggested HTML markup:
				 *
				 * <div class="item"><a href="Your custom URL">Your custom text</a></div>
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_plugins_help_item' );
				?>
			</div>
			<?php
		}
	}

}
