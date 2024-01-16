<?php

/*
 * This file is part of the Dplugins\PlainClasses package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dplugins\PlainClasses\Core;

use League\Plates\Engine;
use Dplugins\PlainClasses\Plugin;

/**
 * The main Core class
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Plain
{
    /**
     * The template system for the plugin.
     *
     * @var Engine
     */
    private $templates;

    public const OPTION_NAMESPACE = Plugin::WP_OPTION_NAMESPACE;

    public function __construct()
    {
        $templates = Plugin::get_instance()->templates;

        $this->templates = $templates;
        $this->templates->addFolder('core', dirname(__FILE__) . '/templates');

        // set oxygen builder to debug mode
        if (!defined('debugger')) {
            define('debugger', true);
        }

        add_action('plugins_loaded', [$this, 'plugins_loaded']);
    }

    /**
     * Load the plain classes feature.
     */
    public function plugins_loaded()
    {
        add_action('init', [$this, 'init']);
    }

    /**
     * Add filters and action hooks. The hook will manipulate the Oxygen Builder.
     */
    public function init()
    {
        add_filter('f!Wakaloka\\PlainClasses\\Core\\Plain::tribute_autocomplete', [$this, 'builtin_integration'], 10);
        add_filter('f!Wakaloka\\PlainClasses\\Core\\Plain::tribute_autocomplete', fn ($classes) => array_unique($classes), 100001);

        add_action('oxygen_enqueue_ui_scripts', [$this, 'oxygen_enqueue_ui_scripts']);
        add_action('oxygen_enqueue_iframe_scripts', [$this, 'oxygen_enqueue_iframe_scripts']);

        remove_action('wp_ajax_oxygen_vsb_signing_process', 'oxygen_vsb_signing_process');
        add_action('wp_ajax_oxygen_vsb_signing_process', [$this, 'oxygen_vsb_signing_process']);
    }

    public function oxygen_enqueue_ui_scripts()
    {
        $autocomplete = [];

        $autocomplete = apply_filters('f!Wakaloka\\PlainClasses\\Core\\Plain::tribute_autocomplete', $autocomplete);

        $autocomplete = array_map(function ($item) {
            return [
                'key' => $item,
                'value' => $item,
            ];
        }, $autocomplete);

        wp_enqueue_style(self::OPTION_NAMESPACE . '-editor', plugin_dir_url(DPlUGINS_PLAIN_CLASSES_FILE) . 'dist/editor.css', [], Plugin::VERSION);
        wp_enqueue_script(self::OPTION_NAMESPACE . '-editor', plugin_dir_url(DPlUGINS_PLAIN_CLASSES_FILE) . 'dist/editor.js', [], Plugin::VERSION, true);
        wp_localize_script(self::OPTION_NAMESPACE . '-editor', 'wakoloka_plain_classes_tribute', [
            'autocomplete' => $autocomplete,
        ]);

        add_action('wp_footer', function () {
            echo $this->templates->render('core::oxygen-editor');

            if (!defined('ALPINEJS_LOADED')) {
                define('ALPINEJS_LOADED', true);
                echo $this->templates->render('core::alpinejs');
            }
        }, 1000000);
    }

    public function oxygen_enqueue_iframe_scripts()
    {
        wp_enqueue_script(self::OPTION_NAMESPACE . '-iframe', plugin_dir_url(DPlUGINS_PLAIN_CLASSES_FILE) . 'dist/iframe.js', [], Plugin::VERSION, true);
    }

    public function is_enable_autocomplete_oxygen_selector()
    {
        return get_option(self::OPTION_NAMESPACE . '_enable_autocomplete_oxygen_selector');
    }

    public function is_enable_autocomplete_oxymade()
    {
        return get_option(self::OPTION_NAMESPACE . '_enable_autocomplete_oxymade');
    }

    public function is_enable_autocomplete_oxyninja()
    {
        return get_option(self::OPTION_NAMESPACE . '_enable_autocomplete_oxyninja');
    }

    public function is_enable_autocomplete_automaticcss()
    {
        return get_option(self::OPTION_NAMESPACE . '_enable_autocomplete_automaticcss');
    }

    public function builtin_integration(array $classes)
    {
        // Oxygen Selector
        if ($this->is_enable_autocomplete_oxygen_selector()) {
            $component_classes = get_option('ct_components_classes', []);
            $component_classes = array_keys($component_classes);
            $classes = array_merge($classes, $component_classes);
        }

        // OxygenMade
        if ($this->is_enable_autocomplete_oxymade()) {
            if (defined('OXYMADE_PLUGIN_FILE')) {
                $component_classes = get_option('ct_components_classes', []);
                $component_classes = array_filter($component_classes, fn ($v) => in_array($v['parent'], ['OxyMadeFramework', 'OxyMadeHoverStyles']));
                $component_classes = array_keys($component_classes);
                $classes = array_merge($classes, $component_classes);
            }
        }

        // OxyNinja
        if ($this->is_enable_autocomplete_oxyninja()) {
            if (defined('OXYNINJA_PLUGIN_FILE')) {
                $component_classes = get_option('ct_components_classes', []);
                $component_classes = array_filter($component_classes, fn ($v) => in_array($v['parent'], ['core']));
                $component_classes = array_keys($component_classes);
                $classes = array_merge($classes, $component_classes);
            }
        }

        // Automatic.css
        if ($this->is_enable_autocomplete_automaticcss()) {
            if (defined('ACSS_PLUGIN_FILE')) {
                $file_name = dirname(constant('ACSS_PLUGIN_FILE')) . '/config/classes.json';

                if (file_exists($file_name)) {
                    $file_content = file_get_contents($file_name);

                    if (false !== $file_content) {
                        $classes = array_merge($classes, json_decode($file_content, true)['classes']);
                    }
                }
            }
        }

        return $classes;
    }

    public function oxygen_vsb_signing_process()
    {
        $response = [
            'children' => [],
            'step' => 9999,
            'messages' => [
                'Plain Classes plugin is activated.',
                '<b>The signing process will be skipped</b>.',
                'Your Oxygen Builder version now use JSON instead of shortcode to save the datas.',
                '<b>Signing the shortcodes is not necessary</b>.',
            ],
        ];

        header('Content-Type: application/json');

        echo json_encode($response);

        die();
    }
}
