<?php
/**
 * Plugin Name: FluentTools
 * Plugin URI:  https://fluentsmtp.com
 * Description: Unified plugin that bundles FluentAuth security features with FluentSMTP email delivery.
 * Version:     1.0.0
 * Author:      WPManageNinja Team
 * Author URI:  https://wpmanageninja.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fluent-tools
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FLUENT_TOOLS_PLUGIN_FILE', __FILE__);

define('FLUENT_TOOLS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FLUENT_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));

define('FLUENT_TOOLS_VERSION', '1.0.0');

require_once __DIR__ . '/modules/fluent-security/fluent-security.php';
require_once __DIR__ . '/modules/fluent-smtp/fluent-smtp.php';

$pluginBasename = plugin_basename(__FILE__);

\FluentTools\Modules\Security\Module::setup(
    FLUENT_TOOLS_PLUGIN_PATH . 'modules/fluent-security/',
    plugins_url('modules/fluent-security/', __FILE__),
    $pluginBasename
);

\FluentTools\Modules\Smtp\Module::setup(
    FLUENT_TOOLS_PLUGIN_PATH . 'modules/fluent-smtp/',
    plugins_url('modules/fluent-smtp/', __FILE__),
    $pluginBasename
);

\FluentTools\Modules\Security\Module::init();
\FluentTools\Modules\Smtp\Module::init();

register_activation_hook(__FILE__, static function ($networkWide) {
    \FluentTools\Modules\Security\Module::activate($networkWide);
    \FluentTools\Modules\Smtp\Module::activate($networkWide);
});

register_deactivation_hook(__FILE__, static function () {
    \FluentTools\Modules\Security\Module::deactivate();
    \FluentTools\Modules\Smtp\Module::deactivate();
});
