<?php
/**
 * Plugin Name: Parent Meetings - Quantity Mode
 * Description: Tėvų susitikimų rezervavimo sistema mokykloms (kiekio režimas - pagal eilės numerį, ne pagal laiką)
 * Version: 1.0.0
 * Author: Tobalt
 * Author URI: https://tobalt.lt
 * Text Domain: parent-meetings-quantity
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple loading
if (defined('PMQ_PLUGIN_LOADED')) {
    return;
}
define('PMQ_PLUGIN_LOADED', true);

// Constants
if (!defined('PMQ_VERSION')) {
    define('PMQ_VERSION', '1.0.0');
}
if (!defined('PMQ_PLUGIN_DIR')) {
    define('PMQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('PMQ_PLUGIN_URL')) {
    define('PMQ_PLUGIN_URL', plugin_dir_url(__FILE__));
}
// Aliases for compatibility with shared classes
if (!defined('PM_VERSION')) {
    define('PM_VERSION', PMQ_VERSION);
}
if (!defined('PM_PLUGIN_DIR')) {
    define('PM_PLUGIN_DIR', PMQ_PLUGIN_DIR);
}
if (!defined('PM_PLUGIN_URL')) {
    define('PM_PLUGIN_URL', PMQ_PLUGIN_URL);
}

// API Keys - Get from settings (backward compatibility with constants)
if (!defined('PM_RECAPTCHA_SITE_KEY')) {
    $pmq_settings = get_option('pmq_settings', []);
    define('PM_RECAPTCHA_SITE_KEY', $pmq_settings['recaptcha_site_key'] ?? '');
}
if (!defined('PM_RECAPTCHA_SECRET_KEY')) {
    $pmq_settings = get_option('pmq_settings', []);
    define('PM_RECAPTCHA_SECRET_KEY', $pmq_settings['recaptcha_secret_key'] ?? '');
}

// Autoload classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'PM_') === 0) {
        $file = PM_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Activation/Deactivation
register_activation_hook(__FILE__, function() {
    PM_Database::create_tables();

    // Create demo data if requested
    if (get_option('pm_create_demo_data')) {
        PM_Database::create_demo_data();
        delete_option('pm_create_demo_data');
    }

    // Schedule cron events
    PM_Cron::schedule_events();

    // Flush rewrite rules
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    PM_Database::cleanup();
    PM_Cron::unschedule_events();
});

// Load text domain for translations
add_action('init', function() {
    load_plugin_textdomain('parent-meetings-quantity', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Initialize
add_action('plugins_loaded', function() {
    // Check and update database if needed
    $current_db_version = get_option('pmq_db_version', '0');
    if (version_compare($current_db_version, PMQ_VERSION, '<')) {
        PM_Database::create_tables();
        update_option('pmq_db_version', PMQ_VERSION);
    }

    new PM_Admin();       // Initialize admin first to create parent menu
    new PM_Settings();    // Then settings to add submenu
    new PM_Frontend();
    new PM_Magic_Links();
    new PM_Emails();
    new PM_Cron();
    new PM_Analytics();   // Analytics dashboard
});

// AJAX handlers
add_action('wp_ajax_pm_get_classes', ['PM_Ajax', 'get_classes']);
add_action('wp_ajax_nopriv_pm_get_classes', ['PM_Ajax', 'get_classes']);
add_action('wp_ajax_pm_get_teachers', ['PM_Ajax', 'get_teachers']);
add_action('wp_ajax_nopriv_pm_get_teachers', ['PM_Ajax', 'get_teachers']);
add_action('wp_ajax_pm_get_time_slots', ['PM_Ajax', 'get_time_slots']);
add_action('wp_ajax_nopriv_pm_get_time_slots', ['PM_Ajax', 'get_time_slots']);
add_action('wp_ajax_pm_book_meeting', ['PM_Ajax', 'book_meeting']);
add_action('wp_ajax_nopriv_pm_book_meeting', ['PM_Ajax', 'book_meeting']);
add_action('wp_ajax_pm_cancel_booking', ['PM_Ajax', 'cancel_booking']);
add_action('wp_ajax_nopriv_pm_cancel_booking', ['PM_Ajax', 'cancel_booking']);
add_action('wp_ajax_pm_reschedule_booking', ['PM_Ajax', 'reschedule_booking']);
add_action('wp_ajax_nopriv_pm_reschedule_booking', ['PM_Ajax', 'reschedule_booking']);
add_action('wp_ajax_pm_toggle_attendance', ['PM_Ajax', 'toggle_attendance']);
add_action('wp_ajax_nopriv_pm_toggle_attendance', ['PM_Ajax', 'toggle_attendance']);
add_action('wp_ajax_pm_set_attendance', ['PM_Ajax', 'set_attendance']);
add_action('wp_ajax_nopriv_pm_set_attendance', ['PM_Ajax', 'set_attendance']);
add_action('wp_ajax_pm_toggle_slot_visibility', ['PM_Ajax', 'toggle_slot_visibility']);
add_action('wp_ajax_nopriv_pm_toggle_slot_visibility', ['PM_Ajax', 'toggle_slot_visibility']);
add_action('wp_ajax_pm_teacher_cancel_booking', ['PM_Ajax', 'teacher_cancel_booking']);
add_action('wp_ajax_nopriv_pm_teacher_cancel_booking', ['PM_Ajax', 'teacher_cancel_booking']);
add_action('wp_ajax_pm_get_quantity_slots', ['PM_Ajax', 'get_quantity_slots']);
add_action('wp_ajax_nopriv_pm_get_quantity_slots', ['PM_Ajax', 'get_quantity_slots']);
