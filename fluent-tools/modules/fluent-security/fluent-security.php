<?php

namespace FluentTools\Modules\Security;

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
    protected static $moduleUrl;

    /**
     * @var string
     */
    protected static $pluginBasename;

    /**
     * @var bool
     */
    protected static $dependenciesLoaded = false;

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
        self::$moduleUrl = trailingslashit($moduleUrl);
        self::$pluginBasename = $pluginBasename;
    }

    /**
     * Boot the Fluent Security module.
     *
     * @return void
     */
    public static function init()
    {
        if (defined('FLUENT_AUTH_VERSION')) {
            return;
        }

        define('FLUENT_AUTH_PLUGIN_PATH', self::$modulePath);
        define('FLUENT_AUTH_PLUGIN_URL', self::$moduleUrl);
        define('FLUENT_AUTH_VERSION', '2.0.3');

        self::loadDependencies();

        load_plugin_textdomain(
            'fluent-security',
            false,
            dirname(self::$pluginBasename) . '/modules/fluent-security/language'
        );

        add_filter(
            'plugin_action_links_' . self::$pluginBasename,
            [static::class, 'addContextLinks'],
            10,
            1
        );
    }

    /**
     * Handle network activation tasks.
     *
     * @param bool $networkWide
     * @return void
     */
    public static function activate($networkWide = false)
    {
        self::loadDependencies();
        \FluentAuth\App\Helpers\Activator::activate($networkWide);
    }

    /**
     * Clean up scheduled tasks on deactivation.
     *
     * @return void
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('fluent_auth_daily_tasks');
        wp_clear_scheduled_hook('fluent_auth_hourly_tasks');
    }

    /**
     * Register the autoloader and include required files.
     *
     * @return void
     */
    protected static function loadDependencies()
    {
        if (self::$dependenciesLoaded) {
            return;
        }

        spl_autoload_register(function ($class) {
            $match = 'FluentAuth';

            if (!preg_match("/\\b{$match}\\b/", $class)) {
                return;
            }

            $file = str_replace(
                ['FluentAuth', '\\', '/App/'],
                ['', DIRECTORY_SEPARATOR, 'app/'],
                $class
            );

            require self::$modulePath . trim($file, '/') . '.php';
        });

        require_once self::$modulePath . 'app/Services/DB/wpfluent.php';

        $modulePath = self::$modulePath;
        add_action('rest_api_init', static function () use ($modulePath) {
            require_once $modulePath . 'app/Http/routes.php';
        });

        require_once self::$modulePath . 'app/Hooks/hooks.php';

        self::$dependenciesLoaded = true;
    }

    /**
     * Add quick action links to the plugin row.
     *
     * @param array $actions
     * @return array
     */
    public static function addContextLinks($actions)
    {
        $actions['settings'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=fluent-auth#/settings')),
            esc_html__('Settings', 'fluent-security')
        );

        $actions['dashboard_page'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=fluent-auth#/')),
            esc_html__('Dashboard', 'fluent-security')
        );

        return $actions;
    }
}
