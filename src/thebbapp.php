<?php

/**
 * Plugin Name: TheBbApp
 * Description: BbApp is a native mobile application with push alerts, instant loading and offline mode for WordPress. Also works with BBPress.
 * Requires at least: 6.5
 * Requires PHP: 7.2.24
 * Plugin URI: https://wordpress.org/plugins/thebbapp/
 * Author: Bb App LLC
 * Author URI: https://thebbapp.com/
 * Text Domain: thebbapp
 * Version: 0.1.0
 * License: AGPLv3
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
**/

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

require_once ABSPATH . '/wp-includes/pluggable.php';

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/include/functions.php';
require_once __DIR__ . '/include/admin/settings.php';

register_activation_hook(__FILE__, 'bb_app_register_activation_hook');
register_deactivation_hook(__FILE__, 'bb_app_register_deactivation_hook');
register_uninstall_hook(__FILE__, 'bb_app_register_uninstall_hook');

bb_app_init();
