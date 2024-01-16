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

/**
 * Common utility functions for the plugin.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Common
{
    /**
     * Check the type of the current request.
     *
     * @param string $type The type of the request. Available values: `admin`, `ajax`, `frontend`, `rest`, `cron`.
     * @return bool
     */
    public static function is_request($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'rest':
                return defined('REST_REQUEST');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
            default:
                return false;
                break;
        }
    }
}
