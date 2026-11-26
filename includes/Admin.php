<?php
/** Native Settings API page and explicit action previews. @package CoreExtras */

namespace CoreExtras;

final class Admin {
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_init', array( RedirectStore::class, 'sweep' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( CORE_EXTRAS_FILE ), array( $this, 'links' ) );
	}

	private function labels() {
		return array(
			'comments' => __( 'Disable comments', 'core-extras' ),
			'svg' => __( 'Safe SVG support', 'core-extras' ),
			'duplicate' => __( 'Duplicate posts and pages', 'core-extras' ),
			'media' => __( 'Replace media', 'core-extras' ),
			'revisions' => __( 'Revision limits', 'core-extras' ),
			'transfer' => __( 'Settings import/export', 'core-extras' ),
			'image_sizes' => __( 'Generated image sizes', 'core-extras' ),
			'redirects' => __( 'Simple redirects', 'core-extras' ),
			'maintenance' => __( 'Maintenance mode', 'core-extras' ),
			'admin_cleanup' => __( 'Dashboard & toolbar', 'core-extras' ),
			'columns' => __( 'Content list columns', 'core-extras' ),
			'uninstall' => __( 'Data removal', 'core-extras' ),
		);
	}

	public function menu() {
		add_options_page( __( 'CoreExtras', 'core-extras' ), __( 'CoreExtras', 'core-extras' ), 'manage_options', 'core-extras', array( $this, 'render' ) );
	}

	public function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=core-extras' ) ) . '">' . esc_html__( 'Settings', 'core-extras' ) . '</a>' );
		return $links;
	}

	/** Load the small enhancement only on this plugin's settings screen. */
	public function assets( $hook ) {
		if ( 'settings_page_core-extras' !== $hook ) {
			return;
		}
		$url = plugin_dir_url( CORE_EXTRAS_FILE ) . 'assets/';
		wp_enqueue_style( 'core-extras-admin', $url . 'admin.css', array(), (string) filemtime( dirname( CORE_EXTRAS_FILE ) . '/assets/admin.css' ) );
		wp_enqueue_script( 'core-extras-redirects', $url . 'redirects.js', array(), (string) filemtime( dirname( CORE_EXTRAS_FILE ) . '/assets/redirects.js' ), true );
		wp_localize_script( 'core-extras-redirects', 'coreExtrasRedirects', array(
			'working' => __( 'Loading or saving redirects…', 'core-extras' ),
			'failed' => __( 'The request could not be confirmed. Your entered paths have been kept. Reload rules before retrying a save.', 'core-extras' ),
			'saved' => __( 'Redirect saved.', 'core-extras' ),
			'deleted' => __( 'Redirect deleted.', 'core-extras' ),
			/* translators: %s: source path of the redirect to edit. */
			'edit' => __( 'Edit redirect from %s', 'core-extras' ),
			'empty' => __( 'No redirects yet. Add your first rule below.', 'core-extras' ),
			'limit' => __( 'The 10,000 redirect limit has been reached. You can still edit or delete existing rules.', 'core-extras' ),
			'noMatches' => __( 'No matching redirects. Try another search.', 'core-extras' ),
			'discard' => __( 'Discard unsaved edits to existing redirects and load another page?', 'core-extras' ),
			/* translators: %s: source path of the rule being deleted. */
			'remove' => __( 'Delete the redirect from %s?', 'core-extras' ),
			/* translators: 1: matching redirects, 2: total redirects. */
			'summary' => __( '%1$d matching redirects of %2$d total.', 'core-extras' ),
			/* translators: 1: current page, 2: total pages. */
			'page' => __( 'Page %1$d of %2$d', 'core-extras' ),
		) );
		wp_enqueue_script( 'core-extras-admin', $url . 'admin.js', array( 'core-extras-redirects' ), (string) filemtime( dirname( CORE_EXTRAS_FILE ) . '/assets/admin.js' ), true );
	}

	private function groups() {
		return array(
			'content' => array( 'label' => __( 'Content', 'core-extras' ), 'features' => array( 'duplicate', 'comments', 'revisions' ) ),
			'media' => array( 'label' => __( 'Media', 'core-extras' ), 'features' => array( 'svg', 'media', 'image_sizes' ) ),
			'site' => array( 'label' => __( 'Site behavior', 'core-extras' ), 'features' => array( 'redirects', 'maintenance' ) ),
			'admin' => array( 'label' => __( 'Admin workspace', 'core-extras' ), 'features' => array( 'admin_cleanup', 'columns' ) ),
			'plugin' => array( 'label' => __( 'Plugin settings', 'core-extras' ), 'features' => array( 'transfer', 'uninstall' ) ),
		);
	}

	public function settings() {
		register_setting( 'core_extras', Settings::OPTION, array( 'type' => 'object', 'sanitize_callback' => array( Settings::class, 'sanitize_storage' ), 'default' => Settings::defaults(), 'show_in_rest' => false ) );
	}

	private function descriptions() {
		return array(
			'media' => __( 'Replace an image while keeping its attachment ID and URL.', 'core-extras' ),
			'transfer' => __( 'Move your CoreExtras configuration between sites.', 'core-extras' ),
			'uninstall' => __( 'Choose what happens to plugin settings when CoreExtras is deleted.', 'core-extras' ),
			'comments' => __( 'Turn off comments across the site or for selected content types.', 'core-extras' ),
			'svg' => __( 'Allow sanitized SVG uploads for trusted users.', 'core-extras' ),
			'duplicate' => __( 'Create an editable draft from an existing post or page.', 'core-extras' ),
			'revisions' => __( 'Limit saved revisions and preview cleanup of older revisions.', 'core-extras' ),
			'image_sizes' => __( 'Choose which image sizes WordPress generates for future uploads.', 'core-extras' ),
			'redirects' => __( 'Send visitors from an old local path to a new one.', 'core-extras' ),
			'maintenance' => __( 'Show a temporary maintenance message to visitors.', 'core-extras' ),
			'admin_cleanup' => __( 'Choose which dashboard widgets and toolbar items each role sees.', 'core-extras' ),
			'columns' => __( 'Add useful information to post and page lists.', 'core-extras' ),
		);
	}

	/** Decorative line artwork from the supplied dashboard template. */
	private function icon( $key ) {
		$icons = array(
			'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
			'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5"/>',
			'content' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
			'media' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m3 17 6-5 4 4 3-3 5 4"/>',
			'behavior' => '<path d="m13 3-8 10h6l-1 8 9-11h-6l1-7Z"/>',
			'workspace' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>',
			'settings' => '<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3" fill="currentColor" stroke="none"/><circle cx="15" cy="17" r="3" fill="currentColor" stroke="none"/>',
			'copy' => '<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M15 8V4a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h4"/>',
			'comments' => '<path d="M20 11V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2v4l5-4h3M3 3l18 18"/>',
			'history' => '<path d="M3 10a9 9 0 1 1 2 8M3 4v6h6M12 7v5l3 2"/>',
			'shield' => '<path d="m12 3 8 3v6c0 4-4 7-8 9-4-2-8-5-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>',
		);
		$aliases = array( 'duplicate' => 'copy', 'revisions' => 'history', 'svg' => 'shield', 'image_sizes' => 'media', 'redirects' => 'behavior', 'maintenance' => 'shield', 'admin_cleanup' => 'workspace', 'columns' => 'content', 'transfer' => 'settings', 'uninstall' => 'settings', 'site' => 'behavior', 'admin' => 'workspace', 'plugin' => 'settings' );
		$key = $aliases[ $key ] ?? $key;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup comes exclusively from the hardcoded map above.
		echo '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ( $icons[ $key ] ?? $icons['settings'] ) . '</svg>';
	}

	private function group_descriptions() {
		return array(
			'content' => __( 'A smoother workflow for your posts and pages.', 'core-extras' ),
			'media' => __( 'Practical controls for uploads, image sizes and file replacement.', 'core-extras' ),
			'site' => __( 'Manage where visitors go and what they see.', 'core-extras' ),
			'admin' => __( 'Make your WordPress workspace work for you.', 'core-extras' ),
			'plugin' => __( 'Transfer your configuration and manage plugin data.', 'core-extras' ),
		);
	}

	private function feature_header( $key, $enabled ) {
		echo '<header class="core-extras-cardhead"><span class="core-extras-feature-icon">';
		$this->icon( $key );
		echo '</span><div class="core-extras-feature-text"><div class="core-extras-titleline"><h3 id="core-extras-label-' . esc_attr( $key ) . '">' . esc_html( $this->labels()[ $key ] ) . '</h3>';
		if ( 'uninstall' !== $key ) {
			echo '<span class="core-extras-badge" data-feature-state data-on="' . esc_attr__( 'Enabled', 'core-extras' ) . '" data-off="' . esc_attr__( 'Disabled', 'core-extras' ) . '" data-enabled="' . ( $enabled ? 'true' : 'false' ) . '">' . esc_html( $enabled ? __( 'Enabled', 'core-extras' ) : __( 'Disabled', 'core-extras' ) ) . '</span>';
		}
		echo '</div><p class="core-extras-feature-description">' . esc_html( $this->descriptions()[ $key ] );
		if ( 'redirects' === $key ) {
			echo ' <span class="core-extras-redirect-info"><button type="button" data-redirect-info aria-label="' . esc_attr__( 'About simple redirects', 'core-extras' ) . '" aria-describedby="core-extras-redirect-help" aria-expanded="false">';
			$this->icon( 'info' );
			echo '</button><span id="core-extras-redirect-help" class="core-extras-info-bubble" role="tooltip" hidden><span>' . esc_html__( 'Up to 10,000 exact local redirects. Redirect changes save separately and take effect immediately when this feature is enabled. Use Save changes below to enable or disable the feature.', 'core-extras' ) . '</span><span>' . esc_html__( 'Enter local paths such as /old-page/ and /new-page/. Start with 302 (temporary) while testing; browsers can cache 301 (permanent). No wildcards, query strings, external destinations, or protected WordPress routes. Include the site prefix on subdirectory sites. Only GET/HEAD visitors redirect; incoming query strings are discarded. Local chains resolve in one hop.', 'core-extras' ) . '</span></span></span>';
		}
		echo '</p></div>';
		if ( 'uninstall' !== $key ) {
			echo '<label class="core-extras-switch-control"><span class="screen-reader-text">' . esc_html( $this->labels()[ $key ] ) . '</span><input class="core-extras-switch" type="checkbox" role="switch" form="core-extras-settings" id="core-extras-' . esc_attr( $key ) . '" name="' . esc_attr( Settings::OPTION . '[' . $key . '][enabled]' ) . '" value="1" ' . checked( $enabled, true, false ) . '></label>';
		}
		echo '</header>';
	}

	private function checkbox( $name, $checked, $label, $id = '' ) {
		echo '<label' . ( $id ? ' for="' . esc_attr( $id ) . '"' : '' ) . '><input type="checkbox" form="core-extras-settings" name="' . esc_attr( Settings::OPTION . $name ) . '" value="1" ' . checked( $checked, true, false ) . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '> ' . esc_html( $label ) . '</label>';
	}

	private function types( $key, $values ) {
		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Selected post types', 'core-extras' ) . '</legend>';
		foreach ( Settings::post_types() as $type ) {
			echo '<label><input type="checkbox" form="core-extras-settings" name="' . esc_attr( Settings::OPTION . '[' . $key . '][post_types][]' ) . '" value="' . esc_attr( $type->name ) . '" ' . checked( in_array( $type->name, $values, true ), true, false ) . '> ' . esc_html( $type->labels->name ) . '</label><br>';
		}
		echo '</fieldset>';
	}

	private function list_checkbox( $module, $list, $item, $selected, $label ) {
		echo '<label><input type="checkbox" form="core-extras-settings" name="' . esc_attr( Settings::OPTION . '[' . $module . '][' . $list . '][]' ) . '" value="' . esc_attr( $item ) . '" ' . checked( in_array( $item, $selected, true ), true, false ) . '> ' . esc_html( $label ) . '</label><br>';
	}

	private function roles( $module, $selected, $legend ) {
		echo '<fieldset><legend>' . esc_html( $legend ) . '</legend>';
		foreach ( wp_roles()->roles as $role => $info ) {
			$this->list_checkbox( $module, 'roles', $role, $selected, translate_user_role( $info['name'] ) );
		}
		echo '</fieldset><br>';
	}

	public function field( $args ) {
		$key = $args['key'];
		$settings = Settings::get( false );
		$this->feature_header( $key, 'uninstall' === $key ? false : $settings[ $key ]['enabled'] );
		if ( 'uninstall' === $key ) {
			echo '<div class="core-extras-cardbody">';
			$this->checkbox( '[uninstall]', $settings['uninstall'], __( 'Remove CoreExtras configuration when the plugin is deleted', 'core-extras' ), 'core-extras-uninstall' );
			echo '<p class="description">' . esc_html__( 'Deactivation always preserves settings. Uninstall never deletes posts, comments, revisions, or media.', 'core-extras' ) . '</p></div>';
			return;
		}
		$value = $settings[ $key ];
		if ( 'redirects' === $key ) {
			// Keep this outside the disabled fieldset so non-JavaScript saves retain all rules.
			echo '<input type="hidden" form="core-extras-settings" name="' . esc_attr( Settings::OPTION . '[redirects][managed]' ) . '" value="1">';
		}
		$configurable = ! in_array( $key, array( 'media', 'transfer' ), true );
		if ( $configurable ) {
			echo '<fieldset class="core-extras-options" data-feature-options' . ( 'redirects' === $key && ! $value['enabled'] ? ' disabled' : '' ) . '><legend class="screen-reader-text">';
			/* translators: %s: feature name. */
			echo esc_html( sprintf( __( 'Options for %s', 'core-extras' ), $this->labels()[ $key ] ) ) . '</legend><div class="core-extras-options-content">';
		}
		if ( ! $configurable ) {
			echo '<div class="core-extras-cardbody">';
		}
		switch ( $key ) {
			case 'image_sizes':
				$sizes = Settings::image_sizes();
				echo '<fieldset><legend>' . esc_html__( 'Do not generate these sizes', 'core-extras' ) . '</legend>';
				foreach ( $sizes as $name => $size ) {
					/* translators: 1: image size name, 2: width, 3: height, 4: resize mode. */
					$label = sprintf( __( '%1$s — %2$d × %3$d (%4$s)', 'core-extras' ), $name, $size['width'], $size['height'], empty( $size['crop'] ) ? __( 'proportional', 'core-extras' ) : __( 'cropped', 'core-extras' ) );
					$this->list_checkbox( 'image_sizes', 'disabled_sizes', $name, $value['disabled_sizes'], $label );
				}
				foreach ( array_diff( $value['disabled_sizes'], array_keys( $sizes ) ) as $name ) {
					/* translators: %s: a previously registered image size. */
					$this->list_checkbox( 'image_sizes', 'disabled_sizes', $name, $value['disabled_sizes'], sprintf( __( '%s (not currently registered)', 'core-extras' ), $name ) );
				}
				echo '</fieldset><p class="description">' . esc_html__( 'Applies to future uploads, regeneration, and file replacement. Existing files remain. Fewer sizes can reduce responsive image choices and make visitors download larger images. Themes may fall back to the full image. 0 means an unconstrained dimension; full-size originals and WordPress big-image scaling are preserved.', 'core-extras' ) . '</p>';
				break;
			case 'redirects':
				echo '<div id="core-extras-redirect-editor" data-url="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'core_extras_redirects' ) ) . '">';
				echo '<p><button type="button" class="button button-primary" data-redirect-open aria-expanded="false" aria-controls="core-extras-redirect-new"><span aria-hidden="true">+</span> ' . esc_html__( 'Add redirect', 'core-extras' ) . '</button></p><div id="core-extras-redirect-new" data-redirect-new hidden>';
				$this->redirect_row( array( 'from' => '', 'to' => '', 'status' => 302 ) );
				echo '<p><button type="button" class="button" data-redirect-close>' . esc_html__( 'Close', 'core-extras' ) . '</button></p></div>';
				echo '<div class="core-extras-redirect-toolbar"><label for="core-extras-redirect-search">' . esc_html__( 'Search source or destination', 'core-extras' ) . '</label><div class="core-extras-redirect-search-box"><input id="core-extras-redirect-search" type="search" maxlength="1024" placeholder="' . esc_attr__( 'Search source or destination', 'core-extras' ) . '"><button type="button" class="button" data-redirect-search aria-label="' . esc_attr__( 'Search', 'core-extras' ) . '" title="' . esc_attr__( 'Search', 'core-extras' ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button></div><button type="button" class="button-link" data-redirect-clear hidden>' . esc_html__( 'Clear search', 'core-extras' ) . '</button><button type="button" class="button" data-redirect-reload>' . esc_html__( 'Reload rules', 'core-extras' ) . '</button></div>';
				echo '<p data-redirect-message role="status" aria-live="polite">' . esc_html__( 'Loading redirects…', 'core-extras' ) . '</p>';
				$this->redirect_pagination();
				echo '<div class="core-extras-redirect-list-heading" data-redirect-heading aria-hidden="true"><span>' . esc_html__( 'Source path', 'core-extras' ) . '</span><span>' . esc_html__( 'Destination path', 'core-extras' ) . '</span><span>' . esc_html__( 'Redirect type', 'core-extras' ) . '</span></div><ul class="core-extras-redirect-list" data-redirect-rows role="list"></ul>';
				$this->redirect_pagination();
				echo '<template data-redirect-template>';
				$this->redirect_row( array( 'from' => '', 'to' => '', 'status' => 302 ), true );
				echo '</template><noscript><p>' . esc_html__( 'Enable JavaScript to search and manage redirect rules. Existing redirects continue to work.', 'core-extras' ) . '</p></noscript></div>';
				break;
			case 'maintenance':
				$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
				$selected_page = Settings::maintenance_page( $value['page_id'] );
				echo '<p><label for="core-extras-maintenance-page">' . esc_html__( 'Maintenance page', 'core-extras' ) . '</label><br><select form="core-extras-settings" id="core-extras-maintenance-page" name="' . esc_attr( Settings::OPTION . '[maintenance][page_id]' ) . '" aria-describedby="core-extras-maintenance-page-help"><option value="0">' . esc_html__( 'Use default maintenance message', 'core-extras' ) . '</option>';
				foreach ( $pages as $page ) {
					if ( ! Settings::maintenance_page( $page->ID ) ) {
						continue;
					}
					/* translators: %d: page ID when the page has no title. */
					$title = $page->post_title ?: sprintf( __( 'Untitled page (#%d)', 'core-extras' ), $page->ID );
					echo '<option value="' . esc_attr( $page->ID ) . '" ' . selected( $selected_page ? $selected_page->ID : 0, $page->ID, false ) . '>' . esc_html( $title ) . '</option>';
				}
				echo '</select> <button type="button" class="button-link" id="core-extras-maintenance-page-clear" hidden>' . esc_html__( 'Remove selection', 'core-extras' ) . '</button></p><p id="core-extras-maintenance-page-help" class="description">' . esc_html__( 'Create and publish a page under Pages, then select it here. Only published pages without a password are listed. It displays the page content without the theme menu, header or footer, at the visitor’s current URL with HTTP 503. Choose the default option or Remove selection, then Save changes to return to the default message without deleting the page. The title and message below are also the fallback if the page becomes unavailable.', 'core-extras' ) . '</p>';
				if ( $value['page_id'] && ! $selected_page ) {
					echo '<p class="description">' . esc_html__( 'The previously selected page is no longer available. Visitors receive the default message. Choose another page or save to use the default message.', 'core-extras' ) . '</p>';
				}
				echo '<p><label for="core-extras-maintenance-title">' . esc_html__( 'Visitor page title (maximum 120 characters)', 'core-extras' ) . '</label><br><input form="core-extras-settings" class="regular-text" type="text" maxlength="120" id="core-extras-maintenance-title" name="' . esc_attr( Settings::OPTION . '[maintenance][title]' ) . '" value="' . esc_attr( $value['title'] ) . '"></p>';
				echo '<p><label for="core-extras-maintenance-message">' . esc_html__( 'Visitor message (plain text, maximum 2000 characters)', 'core-extras' ) . '</label><br><textarea form="core-extras-settings" class="large-text" rows="3" maxlength="2000" id="core-extras-maintenance-message" name="' . esc_attr( Settings::OPTION . '[maintenance][message]' ) . '">' . esc_textarea( $value['message'] ) . '</textarea></p>';
				$this->roles( 'maintenance', $value['roles'], __( 'Additional roles that can view the site', 'core-extras' ) );
				echo '<p class="description">' . esc_html__( 'Visitors receive HTTP 503 and a one-hour Retry-After header, including on REST requests. Administrators retain access. Login, admin screens, AJAX, cron, XML-RPC, and static files remain available. This does not make private content secure. Cached pages may bypass WordPress; purge or disable page/CDN caches before enabling. Blank text uses the translated default message.', 'core-extras' ) . '</p>';
				break;
			case 'admin_cleanup':
				$this->roles( 'admin_cleanup', $value['roles'], __( 'Apply cleanup to these roles', 'core-extras' ) );
				foreach ( array( 'widgets' => Settings::widget_labels(), 'toolbar' => Settings::toolbar_labels() ) as $list => $items ) {
					echo '<fieldset><legend>' . esc_html( 'widgets' === $list ? __( 'Hide dashboard widgets', 'core-extras' ) : __( 'Hide toolbar items', 'core-extras' ) ) . '</legend>';
					foreach ( $items as $item => $label ) {
						$this->list_checkbox( 'admin_cleanup', $list, $item, $value[ $list ], $label );
					}
					echo '</fieldset><br>';
				}
				echo '<p class="description">' . esc_html__( 'Presentation only: capabilities and direct access remain unchanged. A user with any selected role is affected. Toolbar choices also apply on the front end. The account menu and maintenance indicator remain available.', 'core-extras' ) . '</p>';
				break;
			case 'columns':
				$this->types( $key, $value['post_types'] );
				foreach ( array( 'show_id' => __( 'Content ID', 'core-extras' ), 'show_thumbnail' => __( 'Featured-image thumbnail', 'core-extras' ), 'show_modified' => __( 'Last modified date and time', 'core-extras' ) ) as $flag => $label ) {
					$this->checkbox( '[columns][' . $flag . ']', $value[ $flag ], $label );
					echo '<br>';
				}
				echo '<p class="description">' . esc_html__( 'Adds the selected information to supported content lists. Columns do not change editing permissions or create new image sizes.', 'core-extras' ) . '</p>';
				break;
			case 'comments':
				echo '<fieldset><legend>' . esc_html__( 'Comment scope', 'core-extras' ) . '</legend>';
				foreach ( array( 'all' => __( 'Entire site', 'core-extras' ), 'selected' => __( 'Selected post types', 'core-extras' ) ) as $scope => $label ) {
					echo '<label><input type="radio" form="core-extras-settings" name="' . esc_attr( Settings::OPTION . '[comments][scope]' ) . '" value="' . esc_attr( $scope ) . '" ' . checked( $value['scope'], $scope, false ) . '> ' . esc_html( $label ) . '</label><br>';
				}
				echo '</fieldset><br>';
				$this->types( $key, $value['post_types'] );
				echo '<p class="description">' . esc_html__( 'Blocks comments, pingbacks, and trackbacks, including on existing content. Existing comments stay in the database and return when disabled. Type selections apply only to “Selected post types”.', 'core-extras' ) . '</p>';
				break;
			case 'svg':
				echo '<fieldset><legend>' . esc_html__( 'Additional roles allowed to upload SVGs', 'core-extras' ) . '</legend>';
				foreach ( wp_roles()->roles as $role => $info ) {
					if ( 'administrator' === $role ) {
						continue;
					}
					echo '<label><input type="checkbox" form="core-extras-settings" name="' . esc_attr( Settings::OPTION . '[svg][roles][]' ) . '" value="' . esc_attr( $role ) . '" ' . checked( in_array( $role, $value['roles'], true ), true, false ) . '> ' . esc_html( translate_user_role( $info['name'] ) ) . '</label><br>';
				}
				echo '</fieldset><p class="description">' . esc_html__( 'Administrators with upload permission are allowed by default. Selected roles still need upload_files. SVGs are sanitized, limited to 2 MB, and stripped of CSS and external references. PHP DOM and Fileinfo are required. Multisite upload restrictions still apply.', 'core-extras' ) . '</p>';
				if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'finfo' ) ) {
					echo '<p><strong>' . esc_html__( 'SVG uploads are unavailable: enable the PHP DOM and Fileinfo extensions.', 'core-extras' ) . '</strong></p>';
				}
				break;
			case 'duplicate':
				$this->types( $key, $value['post_types'] );
				echo '<p class="description">' . esc_html__( 'Adds “Duplicate as draft” to the selected content lists. Copies content, terms, featured image and safe metadata. Protected integration data and likely unique identifiers are excluded.', 'core-extras' ) . '</p>';
				break;
			case 'media':
				echo '<p class="description">' . esc_html__( 'Adds “Replace file” to attachment edit screens. Supports local JPEG, PNG, GIF, WebP and AVIF files with the same type and extension. Keeps the ID and URL. Dimensions may change and cached copies may take time to refresh.', 'core-extras' ) . '</p>';
				break;
			case 'revisions':
				echo '<label for="core-extras-revision-limit">' . esc_html__( 'Maximum revisions per post', 'core-extras' ) . '</label> <input form="core-extras-settings" class="small-text" id="core-extras-revision-limit" name="' . esc_attr( Settings::OPTION . '[revisions][limit]' ) . '" type="number" min="0" max="1000" value="' . esc_attr( $value['limit'] ) . '">';
				echo '<p class="description">' . esc_html__( '0 disables future normal revisions. Autosaves are preserved. Saving settings does not clean up existing revisions; WordPress trims them when a future revision is saved. Use the separate preview below for an explicit cleanup.', 'core-extras' ) . '</p>';
				break;
			case 'transfer':
				echo '<p class="description">' . esc_html__( 'Save this toggle to reveal export and import tools. Imports require a preview and confirmation and change configuration only.', 'core-extras' ) . '</p>';
				break;
		}
		if ( $configurable ) {
			echo '</div></fieldset>';
		} else {
			echo '</div>';
		}
		if ( $value['enabled'] && in_array( $key, array( 'revisions', 'transfer' ), true ) ) {
			echo '<div class="core-extras-tools">';
			if ( 'revisions' === $key ) {
				$this->revision_tools();
			} else {
				$this->transfer_tools();
			}
			echo '</div>';
		}
	}


	private function redirect_pagination() {
		echo '<nav class="core-extras-redirect-pagination" hidden aria-label="' . esc_attr__( 'Redirect pages', 'core-extras' ) . '"><button type="button" class="button" data-redirect-prev aria-label="' . esc_attr__( 'Previous page', 'core-extras' ) . '"><svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m12 5-5 5 5 5"/></svg></button><span class="core-extras-redirect-pages" data-redirect-pages></span><button type="button" class="button" data-redirect-next aria-label="' . esc_attr__( 'Next page', 'core-extras' ) . '"><svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m8 5 5 5-5 5"/></svg></button></nav>';
	}

	private function redirect_row( $rule, $existing = false ) {
		if ( $existing ) {
			echo '<li class="core-extras-redirect-item" data-redirect-row><div class="core-extras-redirect-summary" data-redirect-read>';
			foreach ( array( 'from' => __( 'Source path', 'core-extras' ), 'to' => __( 'Destination path', 'core-extras' ), 'status' => __( 'Redirect type', 'core-extras' ) ) as $field => $label ) {
				echo '<div><span class="core-extras-redirect-list-label">' . esc_html( $label ) . '</span><span data-redirect-value="' . esc_attr( $field ) . '"></span>';
				if ( 'from' === $field ) {
					echo '<div class="core-extras-redirect-row-links"><button type="button" class="button-link" data-redirect-edit aria-expanded="false">' . esc_html__( 'Edit', 'core-extras' ) . '</button></div>';
				}
				echo '</div>';
			}
			echo '</div><div class="core-extras-redirect-row" data-redirect-edit-fields hidden>';
		} else {
			echo '<div class="core-extras-redirect-row" data-redirect-row>';
		}
		foreach ( array( 'from' => __( 'Source path', 'core-extras' ), 'to' => __( 'Destination path', 'core-extras' ) ) as $field => $label ) {
			echo '<label>' . esc_html( $label ) . '<input type="text" data-redirect-field="' . esc_attr( $field ) . '" value="' . esc_attr( $rule[ $field ] ) . '" placeholder="' . ( 'from' === $field ? '/old-page/' : '/new-page/' ) . '" maxlength="1024" required aria-describedby="core-extras-redirect-help" spellcheck="false" autocomplete="off"></label>';
		}
		echo '<label>' . esc_html__( 'Redirect type', 'core-extras' ) . '<select data-redirect-field="status"><option value="302">' . esc_html__( '302 — Temporary', 'core-extras' ) . '</option><option value="301">' . esc_html__( '301 — Permanent', 'core-extras' ) . '</option></select></label>';
		echo '<div class="core-extras-redirect-actions"><button type="button" class="button-link" data-redirect-save>' . esc_html__( 'Save redirect', 'core-extras' ) . '</button><span aria-hidden="true">|</span><button type="button" class="button-link" data-redirect-cancel>' . esc_html( $existing ? __( 'Cancel', 'core-extras' ) : __( 'Discard edit', 'core-extras' ) ) . '</button><span aria-hidden="true" data-redirect-delete-separator>|</span><button type="button" class="button-link" data-redirect-remove>' . esc_html__( 'Delete', 'core-extras' ) . '</button></div></div>';
		if ( $existing ) {
			echo '</li>';
		}
	}

	private function action_form( $action, $label, $upload = false, $extra = null ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $upload ? ' enctype="multipart/form-data"' : '' ) . '>';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
		Guard::nonce( $action );
		if ( $upload ) {
			echo '<p><label for="core-extras-import">' . esc_html__( 'Settings JSON file (maximum 32 MB)', 'core-extras' ) . '</label><br><input id="core-extras-import" type="file" name="settings_file" accept=".json,application/json" required></p>';
		}
		if ( $extra ) {
			$extra();
		}
		submit_button( $label, 'secondary', 'submit', true, array( 'id' => $action . '-submit' ) );
		echo '</form>';
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = Settings::get( false );
		echo '<div class="wrap core-extras-admin"><header class="core-extras-brandrow"><div class="core-extras-brand"><h1 class="core-extras-brand-title">' . esc_html__( 'CoreExtras', 'core-extras' ) . '</h1><span class="core-extras-version">' . esc_html( CORE_EXTRAS_VERSION ) . '</span></div></header>';
		if ( is_multisite() ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Settings apply to this site only. Network defaults and site overrides are not available. SVG uploads remain subject to network file-type policy.', 'core-extras' ) . '</p></div>';
		}
		$notices = array( 'imported' => __( 'Configuration imported. No cleanup or content action was performed.', 'core-extras' ), 'revision_batch' => __( 'Revision cleanup batch finished. Review the remaining count below.', 'core-extras' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Allowlisted display-only notice; no state change.
		$code = isset( $_GET['core_extras_notice'] ) && is_string( $_GET['core_extras_notice'] ) ? sanitize_key( $_GET['core_extras_notice'] ) : '';
		if ( isset( $notices[ $code ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notices[ $code ] ) . '</p></div>';
		}
		settings_errors();
		echo '<div class="core-extras-tabs-layout">';
		echo '<nav class="core-extras-navigation" aria-label="' . esc_attr__( 'Feature groups', 'core-extras' ) . '"><p class="core-extras-nav-label">' . esc_html__( 'Workspace', 'core-extras' ) . '</p><ul>';
		foreach ( $this->groups() as $key => $group ) {
			echo '<li><a id="core-extras-tab-' . esc_attr( $key ) . '" href="#core-extras-group-' . esc_attr( $key ) . '">';
			$this->icon( $key );
			echo '<span>' . esc_html( $group['label'] ) . '</span><span class="core-extras-nav-arrow" aria-hidden="true">›</span></a></li>';
		}
		echo '</ul><p class="core-extras-nav-note"><strong>' . esc_html__( 'Lightweight by design.', 'core-extras' ) . '</strong>' . esc_html__( 'Every feature starts disabled. You decide what belongs on your site.', 'core-extras' ) . '</p></nav><div class="core-extras-panels">';
		// Explicit form ownership keeps configuration together without nesting action forms.
		echo '<form id="core-extras-settings" method="post" action="options.php">';
		settings_fields( 'core_extras' );
		echo '</form>';
		foreach ( $this->groups() as $key => $group ) {
			$enabled = 0;
			$total = 0;
			foreach ( $group['features'] as $feature ) {
				if ( 'uninstall' !== $feature ) {
					++$total;
					$enabled += $settings[ $feature ]['enabled'] ? 1 : 0;
				}
			}
			echo '<section id="core-extras-group-' . esc_attr( $key ) . '" class="core-extras-group" aria-labelledby="core-extras-heading-' . esc_attr( $key ) . '"><header class="core-extras-sectionhead"><div><h2 id="core-extras-heading-' . esc_attr( $key ) . '" tabindex="-1">' . esc_html( $group['label'] ) . '</h2><p>' . esc_html( $this->group_descriptions()[ $key ] ) . '</p></div>';
			/* translators: 1: enabled features, 2: available features in this group. */
			echo '<span class="core-extras-count" data-enabled-count data-template="' . esc_attr__( '%1$d of %2$d enabled', 'core-extras' ) . '">' . esc_html( sprintf( __( '%1$d of %2$d enabled', 'core-extras' ), $enabled, $total ) ) . '</span></header>';
			foreach ( $group['features'] as $feature ) {
				echo '<article class="core-extras-card" aria-labelledby="core-extras-label-' . esc_attr( $feature ) . '">';
				$this->field( array( 'key' => $feature ) );
				echo '</article>';
			}
			echo '</section>';
		}
		echo '<p class="core-extras-bottomnote">';
		$this->icon( 'shield' );
		echo esc_html__( 'Changes apply after saving. Cleanup always requires a separate action.', 'core-extras' ) . '</p>';
		echo '<footer class="core-extras-savebar"><span id="core-extras-save-status" role="status" data-clean="' . esc_attr__( 'No unsaved changes', 'core-extras' ) . '" data-dirty="' . esc_attr__( 'You have unsaved changes', 'core-extras' ) . '">' . esc_html__( 'Changes apply after saving.', 'core-extras' ) . '</span><button type="reset" class="button" id="core-extras-discard" form="core-extras-settings" hidden>' . esc_html__( 'Discard', 'core-extras' ) . '</button>';
		submit_button( __( 'Save changes', 'core-extras' ), 'primary', 'submit', false, array( 'form' => 'core-extras-settings', 'id' => 'core-extras-save' ) );
		echo '</footer>';
		echo '</div></div></div>';
	}

	private function confirmation( $state, $label ) {
		echo '<input type="hidden" name="preview_token" value="' . esc_attr( $state['token'] ) . '"><p><label><input type="checkbox" name="confirm" value="1" required> ' . esc_html( $label ) . '</label></p>';
		echo '<p><a href="' . esc_url( admin_url( 'options-general.php?page=core-extras' ) ) . '">' . esc_html__( 'Back to settings', 'core-extras' ) . '</a></p>';
	}

	private function revision_tools() {
		echo '<h3 id="core-extras-revision-tools" tabindex="-1">' . esc_html__( 'Revision cleanup', 'core-extras' ) . '</h3><p>' . esc_html__( 'Preview old normal revisions before removing any. Autosaves are excluded. Cleanup runs in batches of up to 100; continue each batch explicitly. Back up your database before cleanup.', 'core-extras' ) . '</p>';
		$this->action_form( 'core_extras_revision_preview', __( 'Preview cleanup', 'core-extras' ) );
		$state = get_transient( Modules\Revisions::transient_key() );
		if ( $state ) {
			echo '<h4>' . esc_html__( 'Cleanup preview', 'core-extras' ) . '</h4><p>';
			/* translators: 1: revisions at preview, 2: already removed, 3: current remaining candidates. */
			echo esc_html( sprintf( __( 'Preview: %1$d revisions to remove. Removed in this run: %2$d. Remaining: %3$d.', 'core-extras' ), $state['count'], $state['deleted'], $state['remaining'] ?? $state['count'] ) );
			echo '</p>';
			if ( ( $state['remaining'] ?? $state['count'] ) > 0 ) {
				$this->action_form( 'core_extras_revision_cleanup', __( 'Remove next batch', 'core-extras' ), false, function () use ( $state ) {
					$this->confirmation( $state, __( 'I confirm permanent deletion of these old revisions, up to 100 in this batch.', 'core-extras' ) );
				} );
			}
		}
	}

	private function transfer_tools() {
		echo '<h3 id="core-extras-transfer-tools" tabindex="-1">' . esc_html__( 'Settings import/export tools', 'core-extras' ) . '</h3><p>' . esc_html__( 'Transfer versioned configuration only. Imports never run revision cleanup, replace files, or duplicate content. Roles and post types must exist on this site.', 'core-extras' ) . '</p>';
		$this->action_form( 'core_extras_export', __( 'Export settings', 'core-extras' ) );
		$this->action_form( 'core_extras_import_preview', __( 'Preview import', 'core-extras' ), true );
		$state = get_transient( Modules\Transfer::transient_key() );
		if ( ! $state ) {
			return;
		}
		echo '<h4>' . esc_html__( 'Import preview', 'core-extras' ) . '</h4><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Setting', 'core-extras' ) . '</th><th scope="col">' . esc_html__( 'Current', 'core-extras' ) . '</th><th scope="col">' . esc_html__( 'Imported', 'core-extras' ) . '</th></tr></thead><tbody>';
		$current = Settings::get();
		foreach ( $state['settings'] as $key => $value ) {
			if ( $value === $current[ $key ] ) {
				continue;
			}
			$before = is_array( $current[ $key ] ) ? $current[ $key ] : array( 'uninstall' => $current[ $key ] );
			$after = is_array( $value ) ? $value : array( 'uninstall' => $value );
			$fields = array( 'enabled' => __( 'Enabled', 'core-extras' ), 'scope' => __( 'Scope', 'core-extras' ), 'roles' => __( 'Additional roles', 'core-extras' ), 'post_types' => __( 'Post types', 'core-extras' ), 'limit' => __( 'Maximum revisions', 'core-extras' ), 'uninstall' => __( 'Remove configuration on uninstall', 'core-extras' ), 'disabled_sizes' => __( 'Sizes not generated', 'core-extras' ), 'rules' => __( 'Redirect rules', 'core-extras' ), 'widgets' => __( 'Hidden dashboard widgets', 'core-extras' ), 'toolbar' => __( 'Hidden toolbar items', 'core-extras' ), 'title' => __( 'Visitor title', 'core-extras' ), 'message' => __( 'Visitor message', 'core-extras' ), 'page_id' => __( 'Maintenance page', 'core-extras' ), 'show_id' => __( 'Content ID', 'core-extras' ), 'show_thumbnail' => __( 'Featured image', 'core-extras' ), 'show_modified' => __( 'Last modified', 'core-extras' ) );
			foreach ( $after as $field => $field_value ) {
				if ( $before[ $field ] !== $field_value ) {
					echo '<tr><th scope="row">' . esc_html( $this->labels()[ $key ] . ': ' . $fields[ $field ] ) . '</th><td>' . esc_html( $this->value_label( $before[ $field ], $field ) ) . '</td><td>' . esc_html( $this->value_label( $field_value, $field ) ) . '</td></tr>';
				}
			}
		}
		if ( $state['settings'] === $current ) {
			echo '<tr><td colspan="3">' . esc_html__( 'No configuration changes.', 'core-extras' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		$this->action_form( 'core_extras_import_apply', __( 'Apply imported configuration', 'core-extras' ), false, function () use ( $state ) {
			$this->confirmation( $state, __( 'I confirm the configuration changes shown above, including feature and uninstall settings.', 'core-extras' ) );
		} );
	}
	/** Human-readable preview values use the same vocabulary as the settings. */
	private function value_label( $value, $field ) {
		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'core-extras' ) : __( 'No', 'core-extras' );
		}
		if ( 'scope' === $field ) {
			return 'all' === $value ? __( 'Entire site', 'core-extras' ) : __( 'Selected post types', 'core-extras' );
		}
		if ( 'page_id' === $field ) {
			$page = Settings::maintenance_page( $value );
			if ( ! $page ) {
				return __( 'Use default maintenance message', 'core-extras' );
			}
			return $page->post_title . ' (#' . $page->ID . ')';
		}
		if ( 'rules' === $field ) {
			$lines = array();
			foreach ( array_slice( $value, 0, 10 ) as $rule ) {
				$lines[] = $rule['status'] . ' ' . $rule['from'] . ' → ' . $rule['to'];
			}
			if ( count( $value ) > 10 ) {
				/* translators: %d: total number of redirect rules. */
				$lines[] = sprintf( __( 'Showing the first 10 of %d rules. The import replaces the complete rule set.', 'core-extras' ), count( $value ) );
			}
			return $lines ? implode( '; ', $lines ) : __( 'None', 'core-extras' );
		}
		if ( is_array( $value ) ) {
			$labels = array();
			foreach ( $value as $item ) {
				if ( 'roles' === $field ) {
					$labels[] = translate_user_role( wp_roles()->roles[ $item ]['name'] ?? $item );
				} elseif ( 'post_types' === $field ) {
					$labels[] = get_post_type_object( $item )->labels->name ?? $item;
				} elseif ( 'widgets' === $field || 'toolbar' === $field ) {
					$items = 'widgets' === $field ? Settings::widget_labels() : Settings::toolbar_labels();
					$labels[] = $items[ $item ] ?? $item;
				} else {
					$labels[] = $item;
				}
			}
			return $labels ? implode( ', ', $labels ) : __( 'None', 'core-extras' );
		}
		return (string) $value;
	}

}
