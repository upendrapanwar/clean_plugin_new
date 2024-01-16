<?php

/*
 * This file is part of the Dplugins\PlainClasses package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dplugins\PlainClasses;

use Exception;
use League\Plates\Engine;
use Wakaloka\Lib\EDD\PluginUpdater;
use Dplugins\PlainClasses\Admin\SettingsPage;
use Dplugins\PlainClasses\Core\Plain;
use Dplugins\PlainClasses\Utils\Notice;

/**
 * The Plugin is the heart of the Wakaloka plugin.
 *
 * It manage the plugin lifecycle and provides a single point of entry for
 * the plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 * 
 * @property Engine $templates
 * @property PluginUpdater $plugin_updater
 * @property Plain $plain
 */
final class Plugin
{
    /**
     * Stores the Plugin's instance, implementing a Singleton pattern.
     *
     * @var \Dplugins\PlainClasses\Plugin
     */
    private static $instance;

    /**
     * Easy Digital Downloads Software Licensing integration wrapper.
     *
     * @var PluginUpdater
     */
    private $plugin_updater;

    /**
     * PHP template system for the plugin.
     *
     * @var Engine
     */
    private $templates;

    /**
     * The Plugin's setting page.
     *
     * @var SettingsPage
     */
    private $settings_page;

    /**
     * The instance of the Plain class.
     *
     * @var Plain
     */
    private $plain;

    public const VERSION = '1.0.14';
    public const VERSION_ID = 10014;
    public const MAJOR_VERSION = 1;
    public const MINOR_VERSION = 0;
    public const RELEASE_VERSION = 14;
    public const EXTRA_VERSION = '';

    public const WP_OPTION_NAMESPACE = 'wakaloka_plain_classes';

    /**
     * The Singleton's constructor should always be private to prevent direct
     * construction calls with the `new` operator.
     */
    protected function __construct()
    {
        $this->templates = new Engine(dirname(WAKALOKA_PLAIN_CLASSES_FILE) . '/templates');
    }

    /**
     * Singletons should not be cloneable.
     */
    protected function __clone()
    {
    }

    /**
     * Singletons should not be restorable from strings.
     *
     * @throws Exception Cannot unserialize a singleton.
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton.');
    }

    /**
     * This is the static method that controls the access to the singleton
     * instance. On the first run, it creates a singleton object and places it
     * into the static property. On subsequent runs, it returns the client existing
     * object stored in the static property.
     */
    public static function get_instance()
    {
        $cls = static::class;
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Method for getting the instances of exposeable object for the plugin.
     *
     * @param string $key Key.
     * @throws Exception If provided key is not allowed or not set.
     */
    public function __get($key)
    {
        $allowed_keys = ['plain', 'templates', 'plugin_updater'];
        if (in_array($key, $allowed_keys) && isset($this->$key)) {
            return $this->$key;
        } else {
            throw new Exception("Trying to get a not allowed or not set key {$key} on the Plugin instance");
        }
    }

    /**
     * Entry to the Plugin.
     */
    public function boot()
    {
        // (de)activation hooks.
        register_activation_hook(DPlUGINS_PLAIN_CLASSES_FILE, [$this, 'activate_plugin']);
        register_deactivation_hook(DPlUGINS_PLAIN_CLASSES_FILE, [$this, 'deactivate_plugin']);

        $this->plain = new Plain();

        // admin hooks.
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(DPlUGINS_PLAIN_CLASSES_FILE), [$this, 'plugin_action_links']);

            add_action('plugins_loaded', [$this, 'plugins_loaded'], 100);

            $this->maybe_update_plugin();

            if (defined('CT_PLUGIN_MAIN_FILE')) {
                $this->settings_page = new SettingsPage();
            }
        }

        // expose the plugin to the world.
        add_filter('f!Wakaloka\\PlainClasses::get_instance', fn ($_) => \Dplugins\PlainClasses\Plugin::get_instance());
    }

    /**
     * Handle the plugin's activation
     */
    public function activate_plugin()
    {
        do_action('a!Wakaloka\\PlainClasses\\Plugins::activate_plugin_start');
        // TODO: Add activation logic here.
        do_action('a!Wakaloka\\PlainClasses\\Plugins::activate_plugin_end');
    }

    /**
     * Handle plugin's deactivation by (maybe) cleaning up after ourselves.
     */
    public function deactivate_plugin()
    {
        do_action('a!Wakaloka\\PlainClasses\\Plugins::deactivate_plugin_start');
        // TODO: Add deactivation logic here.
        do_action('a!Wakaloka\\PlainClasses\\Plugins::deactivate_plugin_end');
    }

    /**
     * Warm up the plugin by registering core hooks.
     */
    public function plugins_loaded()
    {
        add_action('admin_notices', function () {
            $messages = Notice::get_lists();

            if ($messages && is_array($messages)) {
                foreach ($messages as $message) {
                    echo sprintf(
                        '<div class="notice notice-%s is-dismissible"><p><b>Plain Classes:</b> %s</p></div>',
                        $message['status'],
                        $message['message']
                    );
                }
            }
        }, 100);

        if (!defined('CT_PLUGIN_MAIN_FILE')) {
            Notice::error('The Oxygen Builder plugin is not installed or activated. Please install and activate it.', 'require_oxygen_builder', true);
        }
    }

    /**
     * Initialize the plugin updater.
     */
    public function maybe_update_plugin()
    {
        $license_key = get_option(self::WP_OPTION_NAMESPACE . '_license_key');
        $opt_in_beta = get_option(self::WP_OPTION_NAMESPACE . '_opt_in_beta');
        $author = $this->plugin_data('Author');

        $this->plugin_updater = new PluginUpdater(
            self::WP_OPTION_NAMESPACE,
            [
                'version' => self::VERSION,
                'license' => $license_key ? trim($license_key) : false,
                'beta' => $opt_in_beta,
                'plugin_file' => DPlUGINS_PLAIN_CLASSES_FILE,
                'item_id' => DPlUGINS_PLAIN_CLASSES_EDD_STORE['item_id'],
                'store_url' => DPlUGINS_PLAIN_CLASSES_EDD_STORE['url'],
                'author' => DPlUGINS_PLAIN_CLASSES_EDD_STORE['author'],
            ]
        );
    }

    /**
     * Get the plugin's data.
     *
     * @param string $key The key to retrieve.
     * @return mixed
     */
    public function plugin_data($key = null)
    {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $plugin_data = wp_cache_get('plugin_data', self::WP_OPTION_NAMESPACE);

        if (!$plugin_data) {
            $plugin_data = get_plugin_data(WAKALOKA_PLAIN_CLASSES_FILE);
            wp_cache_set('plugin_data', $plugin_data, self::WP_OPTION_NAMESPACE);
        }

        return $key ? $plugin_data[$key] : $plugin_data;
    }

    /**
     * Add action link to the plugin management page.
     *
     * @param array $links The current action links.
     * @return array The modified action links.
     */
    public function plugin_action_links($links)
    {
        $plugin_shortcuts = [
            sprintf('<a href="%s">%s</a>', add_query_arg([
                'page' => self::WP_OPTION_NAMESPACE,
            ], admin_url('admin.php')), __('Settings', 'wakaloka-plain-classes')),
        ];

        return array_merge($links, $plugin_shortcuts);
    }
}
