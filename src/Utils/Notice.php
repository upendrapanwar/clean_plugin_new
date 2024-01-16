<?php

/*
 * This file is part of the Dplugins\PlainClasses package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dplugins\PlainClasses\Utils;

use Dplugins\PlainClasses\Plugin;

/**
 * Manage the plugin's notices for the wp-admin page.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Notice
{
    public const ERROR = 'error';
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const INFO = 'info';

    public const OPTION_NAMESPACE = Plugin::WP_OPTION_NAMESPACE;

    /**
     * Get lists of notices.
     *
     * @param bool $clear Clear all notices after fetching them.
     */
    public static function get_lists($clear = true)
    {
        $notices = get_option(self::OPTION_NAMESPACE . '_notices', []);

        if ($clear) {
            update_option(self::OPTION_NAMESPACE . '_notices', []);
        }

        return $notices;
    }

    public static function add($status, $message, $key = false, $unique = false)
    {
        $notices = get_option(self::OPTION_NAMESPACE . '_notices', []);

        $payload = [
            'status' => $status,
            'message' => $message,
        ];

        if ($unique) {
            if ($key && isset($notices[$key])) {
                return;
            }

            if (in_array([
                'status' => $status,
                'message' => $message,
            ], $notices)) {
                return;
            }
        }

        if ($key) {
            $notices[$key] = $payload;
        } else {
            $notices[] = $payload;
        }

        update_option(self::OPTION_NAMESPACE . '_notices', $notices);
    }

    public static function adds($status, $messages)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            if (!is_array($message)) {
                self::add($status, $message);
            } else {
                self::add($status, $message[0], $message[1], $message[2], $message[3]);
            }
        }
    }

    public static function success($message, $key = false, $unique = false)
    {
        self::add(self::SUCCESS, $message, $key, $unique);
    }

    public static function warning($message, $key = false, $unique = false)
    {
        self::add(self::WARNING, $message, $key, $unique);
    }

    public static function info($message, $key = false, $unique = false)
    {
        self::add(self::INFO, $message, $key, $unique);
    }

    public static function error($message, $key = false, $unique = false)
    {
        self::add(self::ERROR, $message, $key, $unique);
    }
}
