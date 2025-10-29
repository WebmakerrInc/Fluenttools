<?php

namespace FluentTools\Modules\Smtp;

if (!defined('ABSPATH')) {
    exit;
}

class Module
{
    /**
     * @var string
     */
    protected static $modulePath;

    /**
     * @var string
     */
    protected static $pluginBasename;

    /**
     * @var bool
     */
    protected static $bootstrapped = false;

    /**
     * Prepare the module with resolved paths.
     *
     * @param string $modulePath
     * @param string $moduleUrl
     * @param string $pluginBasename
     * @return void
     */
    public static function setup($modulePath, $moduleUrl, $pluginBasename)
    {
        self::$modulePath = trailingslashit($modulePath);
        self::$pluginBasename = $pluginBasename;
    }

    /**
     * Boot the Fluent SMTP module.
     *
     * @return void
     */
    public static function init()
    {
        if (defined('FLUENTMAIL')) {
            return;
        }

        define('FLUENTMAIL_PLUGIN_FILE', self::$modulePath . 'fluent-smtp.php');
        define('FLUENTMAIL_PLUGIN_BASENAME', self::$pluginBasename);

        self::loadDependencies();
        self::loadTextDomain();
        self::maybeOverrideWpMail();

        add_action('plugins_loaded', [static::class, 'bootApplication']);
    }

    /**
     * Handle activation routines.
     *
     * @param bool $networkWide
     * @return void
     */
    public static function activate($networkWide = false)
    {
        self::loadDependencies();
        \FluentMail\Includes\Activator::handle($networkWide);
    }

    /**
     * Handle deactivation routines.
     *
     * @return void
     */
    public static function deactivate()
    {
        self::loadDependencies();
        \FluentMail\Includes\Deactivator::handle();
    }

    /**
     * Load the module dependencies and register autoloaders.
     *
     * @return void
     */
    protected static function loadDependencies()
    {
        if (self::$bootstrapped) {
            return;
        }

        require_once self::$modulePath . 'boot.php';
        self::$bootstrapped = true;
    }

    /**
     * Register the Fluent SMTP application with WordPress.
     *
     * @return void
     */
    public static function bootApplication()
    {
        $application = new \FluentMail\Includes\Core\Application();
        do_action('fluentMail_loaded', $application);
    }

    /**
     * Load the text domain for localization.
     *
     * @return void
     */
    protected static function loadTextDomain()
    {
        load_plugin_textdomain(
            'fluent-smtp',
            false,
            dirname(self::$pluginBasename) . '/modules/fluent-smtp/language'
        );
    }

    /**
     * Override the default wp_mail function if necessary.
     *
     * @return void
     */
    protected static function maybeOverrideWpMail()
    {
        if (!function_exists('wp_mail')) {
            function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
                return fluentMailSend($to, $subject, $message, $headers, $attachments);
            }
        } elseif (!(defined('DOING_AJAX') && DOING_AJAX)) {
            add_action('init', 'fluentMailFuncCouldNotBeLoadedRecheckPluginsLoad');
        }
    }
}
