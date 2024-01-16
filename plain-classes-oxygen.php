<?php

/**
 * Plain Classes - Simplified Classes for Oxygen Builder
 *
 * @wordpress-plugin
 * Plugin Name:         Plain Classes 2.0
 * Plugin URI:          https://dplugins.com/products/plain-classes
 * Description:         Simplified Classes for Oxygen Builder.
 * Version:             1.0.14
 * Requires at least:   5.6
 * Requires PHP:        7.4
 * Author:              DPlugins
 * Author URI:          https://dplugins.com
 * Text Domain:         wakaloka-plain-classes
 * Domain Path:         /languages
 */

defined('ABSPATH') || exit;

//define('WAKALOKA_PLAIN_CLASSES_FILE', __FILE__);
define('DPlUGINS_PLAIN_CLASSES_FILE', __FILE__);

//define('WAKALOKA_PLAIN_CLASSES_EDD_STORE', [
define('DPlUGINS_PLAIN_CLASSES_EDD_STORE', [    
    'url' => 'https://dplugins.com',
    'item_id' => 55491,
    'author' => 'wakaloka',
]);

require_once __DIR__ . '/vendor/autoload.php';

\Dplugins\PlainClasses\Plugin::get_instance()->boot();
