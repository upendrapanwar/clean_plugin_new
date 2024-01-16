<?php

/*
 * This file is part of the Dplugins\PlainClasses package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dplugins\PlainClasses\Admin\Plates\Extensions;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Dplugins\PlainClasses\Plugin;

/**
 * SettingsPage extension for Plates template engine.
 *
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 */
class Settings implements ExtensionInterface
{
    public const OPTION_NAMESPACE = Plugin::WP_OPTION_NAMESPACE;

    public function __construct()
    {
    }

    public function register(Engine $engine)
    {
    }
}
