<?php

class PM_Cron {

    public function __construct() {
        // Register cron schedules
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);

        // Schedule events on activation
        add_action('pm_plugin_activated', [$this, 'schedule_events']);

        // Cron event handlers
        add_action('pm_send_reminders', [$this, 'send_meeting_reminders']);
        add_action('pm_cleanup_old_data', [$this, 'cleanup_old_data']);
        add_action('pm_cleanup_expired_tokens', [$this, 'cleanup_expired_tokens']);
    }

    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        // Every hour
        $schedules['pm_hourly'] = [
            'interval' => 3600,
            'display' => __('Every Hour', 'parent-meetings')
        ];

        return $schedules;
    }

    /**
     * Schedule all cron events
     */
    public static function schedule_events() {
        // Send reminders every hour
        if (!wp_next_scheduled('pm_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'pm_send_reminders');
        }

        // Cleanup old data daily
        if (!wp_next_scheduled('pm_cleanup_old_data')) {
            wp_schedule_event(time(), 'daily', 'pm_cleanup_old_data');
        }

        // Cleanup expired tokens daily
        if (!wp_next_scheduled('pm_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'daily', 'pm_cleanup_expired_tokens');
        }
    }

    /**
     * Unschedule all cron events
     */
    public static function unschedule_events() {
        wp_clear_scheduled_hook('pm_send_reminders');
        wp_clear_scheduled_hook('pm_cleanup_old_data');
        wp_clear_scheduled_hook('pm_cleanup_expired_tokens');
    }

    /**
     * Send 24-hour reminder emails
     */
    public function send_meeting_reminders() {
        // Check if reminders are enabled
        if (!PM_Settings::get_option('send_reminders', 1)) {
            return;
        }

        global $wpdb;

        // Get bookings that start in approximately 24 hours
        // We check for meetings between 23-25 hours from now to account for cron timing
        $start_time = date('Y-m-d H:i:s', strtotime('+23 hours'));
        $end_time = date('Y-m-d H:i:s', strtotime('+25 hours'));

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, s.start_time, s.end_time, t.first_name, t.last_name, t.email as teacher_email
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             WHERE b.status = 'confirmed'
             AND s.start_time BETWEEN %s AND %s
             AND b.reminder_sent = 0",
            $start_time,
            $end_time
        ));

        foreach ($bookings as $booking) {
            $sent = PM_Emails::send_reminder($booking);

            if ($sent) {
                // Mark reminder as sent
                $wpdb->update(
                    $wpdb->prefix . 'pm_bookings',
                    ['reminder_sent' => 1],
                    ['id' => $booking->id]
                );
            }
        }

        // Log the number of reminders sent
        if (count($bookings) > 0) {
            error_log(sprintf('PM: Sent %d meeting reminders', count($bookings)));
        }
    }

    /**
     * Cleanup old bookings and slots
     */
    public function cleanup_old_data() {
        $cleanup_days = PM_Settings::get_option('cleanup_old_data_days', 0);

        // Skip if cleanup is disabled
        if ($cleanup_days === 0) {
            return;
        }

        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$cleanup_days} days"));

        // Delete old bookings
        $deleted_bookings = $wpdb->query($wpdb->prepare(
            "DELETE b FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE s.start_time < %s",
            $cutoff_date
        ));

        // Delete old slots
        $deleted_slots = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}pm_slots
             WHERE start_time < %s",
            $cutoff_date
        ));

        // Delete old availability periods
        $deleted_availability = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}pm_availability
             WHERE date < %s",
            date('Y-m-d', strtotime("-{$cleanup_days} days"))
        ));

        if ($deleted_bookings > 0 || $deleted_slots > 0 || $deleted_availability > 0) {
            error_log(sprintf(
                'PM: Cleaned up old data - Bookings: %d, Slots: %d, Availability: %d',
                $deleted_bookings,
                $deleted_slots,
                $deleted_availability
            ));
        }
    }

    /**
     * Cleanup expired magic link tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}pm_tokens WHERE expires_at < NOW()"
        );

        if ($deleted > 0) {
            error_log(sprintf('PM: Cleaned up %d expired tokens', $deleted));
        }
    }
}
