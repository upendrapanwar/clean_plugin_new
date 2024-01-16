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

use Oxygen_Revisions;
use WP_Query;

/**
 * Migrate classes from Plain Classes to Oxygen Selector System or vice versa.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Migration
{
    static $oxy_selectors = null;

    /**
     * Get the Posts that have Oxygen shortcodes
     * 
     * @return array 
     */
    public static function get_post_ids()
    {
        $posts = [];

        $ignoredPostTypes = [
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
        ];

        $postTypes = get_post_types();

        if (is_array($ignoredPostTypes) && is_array($postTypes)) {
            $postTypes = array_diff($postTypes, $ignoredPostTypes);
        }

        $postTypes = array_filter($postTypes, fn ($val) => get_option('oxygen_vsb_ignore_post_type_' . $val, false) !== 'true');

        /**
         * @see oxygen_css_cache_generation_script()
         */
        $query = new WP_Query([
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_type' => $postTypes,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => 'ct_builder_shortcodes',
                    'value'   => '',
                    'compare' => '!=',
                ],
                [
                    'key'     => 'ct_builder_json',
                    'value'   => '',
                    'compare' => '!=',
                ],
            ],
        ]);

        foreach ($query->posts as $post_id) {
            $posts[] = $post_id;
        }

        return $posts;
    }

    /**
     * Convert classes from Oxygen Selector System to Plain Classes
     * 
     * @param array $shortcode The shortcode of Oxygen
     * @return array 
     */
    public static function to_plain_classes($shortcode)
    {
        if (array_key_exists('children', $shortcode)) {
            $shortcode['children'] = array_map(fn ($child) => self::to_plain_classes($child), $shortcode['children']);
        }

        if (array_key_exists('options', $shortcode)) {
            $filtered_plain_classes = [];
            $filtered_oxy_classes = [];

            if (array_key_exists('plain_classes', $shortcode['options'])) {
                $plain_classes = $shortcode['options']['plain_classes'];

                // replace whitespaces into a whitespace
                $plain_classes = preg_replace('/\s+/', ' ', $plain_classes);

                // trim whitespace from begining and end
                $plain_classes = trim($plain_classes);

                // split into array
                $plain_classes = explode(' ', $plain_classes);

                // remove empty values
                $filtered_plain_classes = array_filter($plain_classes, fn ($item) => !empty(trim($item)));
            }

            if (array_key_exists('classes', $shortcode['options'])) {
                $oxy_classes = $shortcode['options']['classes'];

                // only keep the classes that are not exist in the plain classes
                $filtered_oxy_classes = array_filter($oxy_classes, function ($item) use ($filtered_plain_classes) {
                    // if the item is already in plain classes, then don't add it
                    if (in_array($item, $filtered_plain_classes)) {
                        return false;
                    }

                    // if the item is not in the cached selectors, then add it
                    if (!array_key_exists($item, self::$oxy_selectors)) {
                        return true;
                    }

                    $current_selector = self::$oxy_selectors[$item];

                    unset($current_selector['key']);
                    unset($current_selector['parent']);

                    $selector_states = array_keys($current_selector);

                    if (count($selector_states) === 0) {
                        unset(self::$oxy_selectors[$item]);
                        return true;
                    }

                    foreach ($selector_states as $state) {
                        if (!empty($current_selector[$state])) {
                            return false;
                        }
                    }

                    unset(self::$oxy_selectors[$item]);
                    return true;
                });

                $diff_oxy_classes = array_diff($oxy_classes, $filtered_oxy_classes);

                // remove classes key from the options

                if (!empty($diff_oxy_classes)) {
                    $shortcode['options']['classes'] = $diff_oxy_classes;
                } else {
                    unset($shortcode['options']['classes']);
                }
            }

            if (!empty($filtered_oxy_classes)) {
                $joined_oxy_classes = implode(' ', $filtered_oxy_classes);

                if (array_key_exists('plain_classes', $shortcode['options'])) {
                    // trim whitespace from begining and end
                    $shortcode['options']['plain_classes'] = trim($shortcode['options']['plain_classes']);

                    if (!empty($shortcode['options']['plain_classes'])) {
                        $shortcode['options']['plain_classes'] .= "\n\n";
                    }

                    $shortcode['options']['plain_classes'] .= $joined_oxy_classes;
                } else {
                    $shortcode['options']['plain_classes'] = $joined_oxy_classes;
                }
            }

            if (array_key_exists('activeselector', $shortcode['options'])) {
                $shortcode['options']['activeselector'] = false;
            }
        }

        return $shortcode;
    }

    /**
     * Convert classes from Plain Classes to Oxygen Selector System
     * 
     * @param array $shortcode The shortcode of Oxygen
     * @return array 
     */
    public static function from_plain_classes($shortcode)
    {
        if (array_key_exists('children', $shortcode)) {
            $shortcode['children'] = array_map(fn ($child) => self::from_plain_classes($child), $shortcode['children']);
        }

        if (array_key_exists('options', $shortcode)) {
            $filtered_plain_classes = [];
            $filtered_oxy_classes = [];

            if (array_key_exists('classes', $shortcode['options'])) {
                $filtered_oxy_classes = $shortcode['options']['classes'];
            } else {
                $shortcode['options']['classes'] = [];
            }

            if (array_key_exists('plain_classes', $shortcode['options'])) {
                $plain_classes = $shortcode['options']['plain_classes'];

                // replace whitespaces into a whitespace
                $plain_classes = preg_replace('/\s+/', ' ', $plain_classes);

                // trim whitespace from begining and end
                $plain_classes = trim($plain_classes);

                // split into array
                $plain_classes = explode(' ', $plain_classes);

                // remove empty values
                $filtered_plain_classes = array_filter($plain_classes, fn ($item) => !empty(trim($item)));

                // unique the values
                $filtered_plain_classes = array_unique($filtered_plain_classes);

                // only keep the classes that are not in the filtered_oxy_classes
                $filtered_plain_classes = array_filter($filtered_plain_classes, fn ($item) => !in_array($item, $filtered_oxy_classes));

                $diff_plain_classes = array_diff($plain_classes, $filtered_plain_classes);
                $shortcode['options']['plain_classes'] = implode(' ', $diff_plain_classes);
            }

            if (!empty($filtered_plain_classes)) {
                $shortcode['options']['classes'] = array_merge($shortcode['options']['classes'], $filtered_plain_classes);

                foreach ($filtered_plain_classes as $class) {
                    if (!array_key_exists($class, self::$oxy_selectors)) {
                        self::$oxy_selectors[$class] = [
                            'key' => sanitize_html_class($class),
                        ];
                    }
                }
            }
        }

        return $shortcode;
    }

    /**
     * Migrate the classes
     * 
     * @param string $mode 'to' or 'from'. 'to' means migrate to plain classes, 'from' means migrate from plain classes.
     * @return void 
     */
    public static function convert($mode = 'to')
    {
        $post_ids = self::get_post_ids();

        self::$oxy_selectors = get_option('ct_components_classes', []);

        foreach ($post_ids as $post_id) {
            $shortcode_tree = get_post_meta($post_id, 'ct_builder_json', true);
            $shortcode_tree = json_decode($shortcode_tree, true);

            if (!$shortcode_tree) {
                $shortcode_tree = get_post_meta($post_id, 'ct_builder_shortcodes', true);
            }

            if (!is_array($shortcode_tree)) {
                $shortcode_tree = json_decode(oxygen_safe_convert_old_shortcodes_to_json($shortcode_tree), true);

                // skip if the shortcode is not an array
                if (!is_array($shortcode_tree)) {
                    continue;
                }
            }

            if ($mode === 'to') {
                $shortcode_tree = self::to_plain_classes($shortcode_tree);
            } else {
                $shortcode_tree = self::from_plain_classes($shortcode_tree);
            }

            /**
             * @see ct_save_components_tree_as_post()
             */
            $shortcodes = '';
            $components_tree_json = '';

            // code tree back to JSON to pass into old function
            $components_tree_json = json_encode($shortcode_tree);

            // base64 encode js,css code and (wrapping_shortcode in case of nestable_shortcode component) in the IDs
            if (isset($shortcode_tree['children'])) {
                $shortcode_tree['children'] = ct_base64_encode_decode_tree($shortcode_tree['children']);
            }
            $components_tree_json_for_shortcodes = json_encode($shortcode_tree);

            // transform JSON to shortcodes
            $shortcodes = components_json_to_shortcodes($components_tree_json_for_shortcodes);

            Oxygen_Revisions::create_revision($post_id);

            // Save as post Meta (NEW WAY)
            update_post_meta($post_id, 'ct_builder_shortcodes', $shortcodes);

            $components_tree_json = preg_replace_callback('/\[oxygen ([^\]]*)\]/i', 'ct_sign_oxy_dynamic_shortcode', $components_tree_json);
            update_post_meta($post_id, 'ct_builder_json', addslashes($components_tree_json));
        }

        update_option('ct_components_classes', self::$oxy_selectors);
    }
}
