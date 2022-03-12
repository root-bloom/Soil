<?php

/**
 * Plugin Name:       Soil
 * Plugin URI:        https://rootandbloom.studio
 * Description:       Our rich and clean soil.
 * Version:           1.0
 * Requires PHP:      7.4
 * Author:            Root + Bloom Studio
 * Author URI:        https://rootandbloom.studio
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       root-bloom
 */

require_once WPMU_PLUGIN_DIR . '/root-bloom-soil/vendor/autoload.php';
new RootBloom\Soil();