<?php

/*
 * This file is part of the Dplugins\PlainClasses package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dplugins\PlainClasses\Admin;

use League\Plates\Engine;
use Plugin_Upgrader;
use stdClass;
use Dplugins\PlainClasses\Admin\Plates\Extensions\Settings;
use Dplugins\PlainClasses\Core\Migration;
use Dplugins\PlainClasses\Plugin;
use Dplugins\PlainClasses\Utils\Notice;

/**
 * Plugin Settings Page for managing the Plain Classes plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class SettingsPage
{
    public const OPTION_NAMESPACE = Plugin::WP_OPTION_NAMESPACE;

    /**
     * The template system for the plugin.
     *
     * @var Engine
     */
    private $templates;

    /**
     * The base URL for the plugin settings page
     *
     * @var string
     */
    private $settings_base_url;

    private $capability = 'manage_options';

    public function __construct()
    {
        $templates = Plugin::get_instance()->templates;

        $this->templates = $templates;
        $this->templates->addFolder('admin', dirname(__FILE__) . '/templates');
        $this->templates->loadExtension(new Settings());

        $this->settings_base_url = add_query_arg([
            'page' => self::OPTION_NAMESPACE,
        ], admin_url('admin.php'));

        add_action('admin_menu', [$this, 'admin_menu'], 100);

        add_action('admin_init', function () {
            $patch_file_path = plugin_dir_path(CT_PLUGIN_MAIN_FILE) . 'patched.php';

            if (file_exists($patch_file_path)) {
                require_once $patch_file_path;
            }

            if (false === apply_filters('ct_vsb_wakaloka_patch', false)) {
                Notice::error('The installed Oxygen plugin is not patched.', 'require_oxygen_builder', true);
            }
        }, 100);
    }

    public function admin_menu()
    {
        if (current_user_can($this->capability)) {
            $hook = add_submenu_page(
                'ct_dashboard_page',
                __('Plain Classes', 'wakaloka-plain-classes'),
                __('Plain Classes', 'wakaloka-plain-classes'),
                $this->capability,
                self::OPTION_NAMESPACE,
                [$this, 'plugin_page']
            );

            add_action('load-' . $hook, [$this, 'init_hooks']);

            // check if automatic css plugin installed acss purger plugin is not installed
            if (defined('ACSS_PLUGIN_FILE') && !defined('ACSS_PURGER_FILE')) {
                add_submenu_page(
                    'automatic-css',
                    'ACSS Purger',
                    'ACSS Purger',
                    'manage_options',
                    'acss-purger',
                    function () {
                        $location = 'https://rosua.org/downloads/acss-purger/?utm_medium=wakaloka-plain-classes-plugin&utm_source=automatic-css-plugin';
                        $safe = false;
                        if (!headers_sent()) {
                            if ($safe) {
                                wp_safe_redirect($location);
                            } else {
                                wp_redirect($location);
                            }
                        } else {
                            echo '<meta http-equiv="refresh" content="0;url=' . $location . '">';
                        }
                        exit;
                    }
                );
            }
        }
    }

    public function init_hooks()
    {
        $this->register_actions();

        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style(self::OPTION_NAMESPACE . "-admin", plugin_dir_url(DPlUGINS_PLAIN_CLASSES_FILE) . 'dist/admin.css', [], Plugin::VERSION);
    }

    public function plugin_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

        $this->templates->addData([
            'version' => Plugin::VERSION,
            'active_tab' => $active_tab,
            'tabs' => [
                [
                    'href' => add_query_arg([
                        'tab' => 'settings',
                    ], $this->settings_base_url),
                    'title' => __('Settings', 'wakaloka-plain-classes'),
                    'classes' => $active_tab === 'settings' ? 'nav-tab-active' : '',
                ],
                [
                    'href' => add_query_arg([
                        'tab' => 'license',
                    ], $this->settings_base_url),
                    'title' => __('License', 'wakaloka-plain-classes'),
                    'classes' => $active_tab === 'license' ? 'nav-tab-active' : '',
                ],
            ],
        ], 'admin::layout');

        switch ($active_tab) {
            case 'license':
                $this->license_tab();
                break;
            case 'settings':
            default:
                $this->setting_tab();
                break;
        }
    }

    public function license_tab()
    {
        if (isset($_POST['submit'])) {
            if (!wp_verify_nonce($_POST[self::OPTION_NAMESPACE . "_settings_form"], self::OPTION_NAMESPACE)) {
                Notice::error('Nonce verification failed');

                echo sprintf('<script>window.location.href = "%s";</script>', add_query_arg([
                    'tab' => 'license',
                ], $this->settings_base_url));
                exit;
            }

            $plugin_updater = Plugin::get_instance()->plugin_updater;

            $req_license_key = sanitize_text_field($_REQUEST['license_key']);

            if ($req_license_key !== get_option(self::OPTION_NAMESPACE . "_license_key")) {
                if (empty($req_license_key)) {
                    $plugin_updater->deactivate();
                    update_option(self::OPTION_NAMESPACE . "_license_key", null);

                    Notice::success('Plugin license key de-activated successfully');
                } else {
                    $response = $plugin_updater->activate($req_license_key);

                    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                        Notice::error(is_wp_error($response) ? $response->get_error_message() : 'An error occurred, please try again.');
                    } else {
                        $license_data = json_decode(wp_remote_retrieve_body($response));

                        if ($license_data->license != 'valid') {
                            Notice::error($plugin_updater->error_message($license_data->error));
                        } else {
                            update_option(self::OPTION_NAMESPACE . "_license_key", $req_license_key);
                            Notice::success('Plugin license key activated successfully');
                        }
                    }
                }
            }

            update_option(self::OPTION_NAMESPACE . "_beta", sanitize_text_field($_REQUEST['beta'] ?? false));

            Notice::success('License have been saved!');
            echo sprintf('<script>window.location.href = "%s";</script>', add_query_arg([
                'tab' => 'license',
            ], $this->settings_base_url));

            exit;
        }

        try {
            $is_license_activated = Plugin::get_instance()->plugin_updater->is_activated();
        } catch (\Throwable $th) {
            //throw $th;
            $is_license_activated = false;
        }

        echo $this->templates->render('admin::pages/license', [
            'license_key' => get_option(self::OPTION_NAMESPACE . "_license_key"),
            'opt_in_beta' => get_option(self::OPTION_NAMESPACE . "_opt_in_beta"),
            'is_license_activated' => $is_license_activated,
            'wp_nonce_field' => wp_nonce_field(self::OPTION_NAMESPACE, self::OPTION_NAMESPACE . "_settings_form", true, false),
        ]);
    }

    public function setting_tab()
    {
        $plain = Plugin::get_instance()->plain;

        if (isset($_POST['submit'])) {
            if (!wp_verify_nonce($_POST[self::OPTION_NAMESPACE . '_settings_form'], self::OPTION_NAMESPACE)) {
                Notice::error('Nonce verification failed');

                echo sprintf('<script>window.location.href = "%s";</script>', add_query_arg([], false));
                exit;
            }

            update_option(self::OPTION_NAMESPACE . '_enable_autocomplete_oxygen_selector', sanitize_text_field($_POST['enable_autocomplete_oxygen_selector'] ?? false));
            update_option(self::OPTION_NAMESPACE . '_enable_autocomplete_oxymade', sanitize_text_field($_POST['enable_autocomplete_oxymade'] ?? false));
            update_option(self::OPTION_NAMESPACE . '_enable_autocomplete_oxyninja', sanitize_text_field($_POST['enable_autocomplete_oxyninja'] ?? false));
            update_option(self::OPTION_NAMESPACE . '_enable_autocomplete_automaticcss', sanitize_text_field($_POST['enable_autocomplete_automaticcss'] ?? false));

            Notice::success('Settings have been saved!');
            echo ("<script>location.href = '" . add_query_arg([], false) . "'</script>");
            exit;
        }

        echo $this->templates->render('admin::pages/setting', [
            'wp_nonce_field' => wp_nonce_field(self::OPTION_NAMESPACE, self::OPTION_NAMESPACE . "_settings_form", true, false),
            'oxygen_builder_version' => $this->get_oxygen_builder_version(),
            'is_supported' => $this->is_supported(),
            'available_patch' => $this->get_available_patch(),
            'patch_version' => $this->get_patch_version(),
            'get_newer_patch_version' => $this->get_newer_patch_version(),
            'patch_action_url' => add_query_arg([
                'action'  => 'patch-oxygen-builder',
                '_wpnonce' => wp_create_nonce(self::OPTION_NAMESPACE),
            ]),
            'migrate_to_action_url' => add_query_arg([
                'action'  => 'migrate-to-plain-classes',
                '_wpnonce' => wp_create_nonce(self::OPTION_NAMESPACE),
            ]),
            'migrate_from_action_url' => add_query_arg([
                'action'  => 'migrate-from-plain-classes',
                '_wpnonce' => wp_create_nonce(self::OPTION_NAMESPACE),
            ]),

            'enable_autocomplete_oxygen_selector' => $plain->is_enable_autocomplete_oxygen_selector(),
            'enable_autocomplete_oxymade' => $plain->is_enable_autocomplete_oxymade(),
            'enable_autocomplete_oxyninja' => $plain->is_enable_autocomplete_oxyninja(),
            'enable_autocomplete_automaticcss' => $plain->is_enable_autocomplete_automaticcss(),
        ]);
    }

    public function get_oxygen_builder_version()
    {
        return get_plugin_data(CT_PLUGIN_MAIN_FILE)['Version'];
    }

    public function is_supported()
    {
        $oxygen_builder_version = $this->get_oxygen_builder_version();

        $available_patch = $this->get_available_patch();

        return array_key_exists($oxygen_builder_version, $available_patch);
    }

    /**
     * Get the installed patch version of Oxygen Builder.
     * 
     * @return false|string False if the patch is not installed. Otherwise, the version number.
     */
    public function get_patch_version()
    {
        $patch_file_path = plugin_dir_path(CT_PLUGIN_MAIN_FILE) . 'patched.php';

        if (!$this->is_supported() || !file_exists($patch_file_path)) {
            return false;
        }

        require_once $patch_file_path;

        return apply_filters('ct_vsb_wakaloka_patch', '0.0.0');
    }

    /**
     * Get the newer patch version of Oxygen Builder.
     * 
     * @return false|string False if the patch is not installed or no newer version available. Otherwise, the version number.
     */
    public function get_newer_patch_version()
    {
        $patch_version = $this->get_patch_version();

        $oxygen_builder_version = $this->get_oxygen_builder_version();

        if (false === $patch_version) {
            return false;
        }

        $available_patch = $this->get_available_patch();

        $latest_patch = $available_patch[$oxygen_builder_version]['latest'];

        return version_compare($patch_version, $latest_patch, '<') ? $latest_patch : false;
    }

    public function register_actions()
    {
        if (!isset($_REQUEST['action']) || !isset($_REQUEST['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_wpnonce'], self::OPTION_NAMESPACE)) {
            die('Invalid nonce');
        }

        switch ($_REQUEST['action']) {
            case 'patch-oxygen-builder':
                $this->patch_oxygen_builder();
                echo '<script>window.location.href = "' . remove_query_arg(['action', '_wpnonce']) . '";</script>';
                exit;
                break;
            case 'migrate-to-plain-classes':
                try {
                    Migration::convert('to');
                    Notice::success('Migration from Oxygen Selector System to Plain Classes completed successfully!');
                } catch (\Throwable $th) {
                    Notice::error($th->getMessage());
                }
                echo '<script>window.location.href = "' . remove_query_arg(['action', '_wpnonce']) . '";</script>';
                exit;
                break;
            case 'migrate-from-plain-classes':
                try {
                    Migration::convert('from');
                    Notice::success('Migration from Plain Classes to Oxygen Selector System completed successfully!');
                } catch (\Throwable $th) {
                    Notice::error($th->getMessage());
                }
                echo '<script>window.location.href = "' . remove_query_arg(['action', '_wpnonce']) . '";</script>';
                exit;
                break;
            default:
                break;
        }
    }

    /**
     * Patch the Oxygen Builder.
     */
    public function patch_oxygen_builder()
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'site_transient_update_plugins'], 1000);

        wp_clean_update_cache();

        wp_update_plugins();

        if (!class_exists('Plugin_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        $plugin_upgrader = new Plugin_Upgrader();

        $plugin_upgrader->strings = [];

        $try_upgrade = $plugin_upgrader->upgrade(plugin_basename(CT_PLUGIN_MAIN_FILE));

        if (is_wp_error($try_upgrade)) {
            Notice::error($try_upgrade->get_error_message());
        } elseif (false === $try_upgrade) {
            Notice::error('Failed to patch Oxygen Builder');
        } else {
            Notice::success('Oxygen Builder has been patched');
        }

        if (is_plugin_inactive(plugin_basename(CT_PLUGIN_MAIN_FILE))) {
            activate_plugin(plugin_basename(CT_PLUGIN_MAIN_FILE));
        }
    }

    /**
     * Add the patch version to the update_plugins transient.
     * @param mixed $transient 
     * @return mixed 
     */
    public function site_transient_update_plugins($transient)
    {
        $oxygen_builder_version = $this->get_oxygen_builder_version();

        $available_patch = $this->get_available_patch();

        $latest_patch = $available_patch[$oxygen_builder_version]['latest'];

        $res = new stdClass();
        $res->slug = basename(CT_PLUGIN_MAIN_FILE, '.php');
        $res->plugin = plugin_basename(CT_PLUGIN_MAIN_FILE);
        $res->new_version = '123.456.789';
        $res->package = $available_patch[$oxygen_builder_version]['patch'][$latest_patch]['download'];

        $transient->response[$res->plugin] = $res;

        return $transient;
    }

    /**
     * Get the available patch version from the remote server.
     * 
     * @return array|null
     */
    public function get_available_patch()
    {
        $available_patch = get_transient(self::OPTION_NAMESPACE . '_available_oxygen_patch');

        if (false === $available_patch) {
            $request_args = [
                'headers' => [
                    'accept' => 'application/json',
                    'cache-control' => 'no-cache',
                    'pragma' => 'no-cache',
                ],
            ];

            // randomize the request to avoid the cache
            $rand_seed = rand(0, 99999);

            $req_def = wp_remote_get('https://dplugins.com/wp-json/wp/v2/oxygenpatch/59701?rand_seed=' . $rand_seed, $request_args);
            $req_def = wp_remote_retrieve_body($req_def);
            $req_def = json_decode($req_def, true);
            $req_def = $req_def['acf']['oxygen_patches'];

            $req_media = wp_remote_get('https://dplugins.com/wp-json/wp/v2/media?parent=59701&per_page=100&rand_seed=' . $rand_seed, $request_args);
            $req_media = wp_remote_retrieve_body($req_media);
            $req_media = json_decode($req_media, true);

            $medias = [];
            foreach ($req_media as $media) {
                $medias[$media['id']] = $media['source_url'];
            }

            $defs = [];
            foreach ($req_def as $def) {
                $defs[$def['version']] = [
                    'latest' => $def['latest'],
                    'patch' => [],
                ];

                foreach ($def['patch'] as $patch) {
                    $defs[$def['version']]['patch'][$patch['version']] = [
                        'download' => $medias[$patch['download']],
                        'tag' => $patch['tag'],
                    ];
                }
            }

            $defs = json_encode($defs);

            $available_patch = $defs;
            set_transient(self::OPTION_NAMESPACE . '_available_oxygen_patch', $available_patch, MINUTE_IN_SECONDS);
        }

        return json_decode($available_patch, true);
    }
}
