<?php
/**
 * Plugin Name: CoreExtras
 * Description: Practical, opt-in utilities for content, media, redirects, maintenance, and WordPress administration.
 * Version: 1.3.2
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Marko Moguljak
 * License: GPL-2.0-or-later
 * Text Domain: core-extras
 * Domain Path: /languages
 *
 * @package CoreExtras
 */

namespace CoreExtras;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CORE_EXTRAS_FILE', __FILE__ );
define( 'CORE_EXTRAS_VERSION', '1.3.2' );
require_once __DIR__ . '/includes/autoload.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );
add_action( 'plugins_loaded', array( Plugin::class, 'boot' ) );
