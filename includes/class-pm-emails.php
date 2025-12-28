<?php

class PM_Emails {

    /**
     * Get secondary email for a project if enabled
     */
    private static function get_project_secondary_email($project_id) {
        if (!$project_id) {
            return null;
        }

        global $wpdb;
        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT secondary_email, secondary_email_enabled FROM {$wpdb->prefix}pm_projects WHERE id = %d",
            $project_id
        ));

        if ($project && $project->secondary_email_enabled && !empty($project->secondary_email)) {
            return $project->secondary_email;
        }

        return null;
    }

    /**
     * Send email to primary recipient and optionally to secondary email
     */
    private static function send_with_secondary($to, $subject, $message, $project_id = null) {
        $success = self::send_email($to, $subject, $message);

        // Send to secondary email if enabled for this project
        $secondary_email = self::get_project_secondary_email($project_id);
        if ($secondary_email && $secondary_email !== $to) {
            self::send_email($secondary_email, $subject, $message);
        }

        return $success;
    }

    public static function send_teacher_invitation($teacher_id) {
        global $wpdb;

        $teacher = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_teachers WHERE id = %d",
            $teacher_id
        ));

        if (!$teacher) return false;

        $urls = PM_Magic_Links::get_urls($teacher_id);

        $subject = 'Kvietimas į Tėvų Susitikimų Sistemą';

        $message = self::get_email_template(
            sprintf('Sveiki, %s %s!', esc_html($teacher->first_name), esc_html($teacher->last_name)),
            '<p>Jūs buvote pridėtas į tėvų susitikimų sistemą.</p>' .
            '<p style="margin: 30px 0;">' .
            '<a href="' . esc_url($urls['manage']) . '" style="display: inline-block; padding: 12px 24px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px; margin-right: 10px;">Tvarkyti prieinamumo laikus</a>' .
            '<a href="' . esc_url($urls['print']) . '" style="display: inline-block; padding: 12px 24px; background-color: #00a32a; color: #ffffff; text-decoration: none; border-radius: 4px;">Peržiūrėti susitikimus</a>' .
            '</p>' .
            '<p style="color: #666; font-size: 14px;"><em>Šios nuorodos galioja 90 dienų.</em></p>' .
            '<p>Pagarbiai,<br>Mokyklos administracija</p>'
        );

        return self::send_email($teacher->email, $subject, $message);
    }
    
    public static function send_booking_confirmation($booking_id) {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, t.first_name, t.last_name, s.start_time, s.end_time, c.name as class_name
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             LEFT JOIN {$wpdb->prefix}pm_classes c ON b.class_id = c.id
             WHERE b.id = %d",
            $booking_id
        ));

        if (!$booking) return false;

        $cancel_url = home_url('/?pm_action=cancel&token=' . $booking->cancel_token);
        $reschedule_url = home_url('/?pm_action=reschedule&token=' . $booking->cancel_token);

        $subject = 'Susitikimo Patvirtinimas';

        $details = '<div style="background-color: #f5f5f5; padding: 20px; border-radius: 4px; margin: 20px 0;">' .
            '<p style="margin: 5px 0;"><strong>Mokytojas:</strong> ' . esc_html($booking->first_name . ' ' . $booking->last_name) . '</p>' .
            '<p style="margin: 5px 0;"><strong>Data:</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($booking->start_time))) . '</p>' .
            '<p style="margin: 5px 0;"><strong>Laikas:</strong> ' . esc_html(date_i18n(get_option('time_format'), strtotime($booking->start_time))) . ' - ' . esc_html(date_i18n(get_option('time_format'), strtotime($booking->end_time))) . '</p>' .
            '<p style="margin: 5px 0;"><strong>Tipas:</strong> ' . esc_html($booking->meeting_type) . '</p>' .
            '<p style="margin: 5px 0;"><strong>Klasė:</strong> ' . esc_html($booking->class_name ?: '-') . '</p>' .
            '</div>';

        $message = self::get_email_template(
            sprintf('Sveiki, %s!', esc_html($booking->parent_name)),
            '<p><strong>Jūsų susitikimas patvirtintas!</strong></p>' .
            $details .
            '<p style="margin: 30px 0;">' .
            '<a href="' . esc_url($reschedule_url) . '" style="display: inline-block; padding: 12px 24px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px; margin-right: 10px;">Perkelti susitikimą</a>' .
            '<a href="' . esc_url($cancel_url) . '" style="display: inline-block; padding: 12px 24px; background-color: #dc3232; color: #ffffff; text-decoration: none; border-radius: 4px;">Atšaukti susitikimą</a>' .
            '</p>' .
            '<p>Pagarbiai,<br>Mokyklos administracija</p>'
        );

        return self::send_email($booking->parent_email, $subject, $message);
    }
    
    public static function send_cancellation_notice($booking_id) {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, t.email as teacher_email, t.first_name, t.last_name, s.start_time
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.id = %d",
            $booking_id
        ));

        if (!$booking) return false;

        $subject = 'Susitikimas Atšauktas';

        $message = self::get_email_template(
            sprintf('Sveiki, %s %s!', esc_html($booking->first_name), esc_html($booking->last_name)),
            '<p>Tėvas <strong>' . esc_html($booking->parent_name) . '</strong> atšaukė susitikimą:</p>' .
            '<div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">' .
            '<p style="margin: 0;"><strong>Data/Laikas:</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->start_time))) . '</p>' .
            '</div>' .
            '<p>Pagarbiai,<br>Sistema</p>'
        );

        return self::send_with_secondary($booking->teacher_email, $subject, $message, $booking->project_id);
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Send cancellation notice to parent when teacher cancels
     */
    public static function send_teacher_cancellation_notice($booking_id) {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, t.first_name, t.last_name, s.start_time
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.id = %d",
            $booking_id
        ));

        if (!$booking) return false;

        $subject = 'Susitikimas Atšauktas Mokytojo';

        $message = self::get_email_template(
            sprintf('Sveiki, %s!', esc_html($booking->parent_name)),
            '<p>Mokytojas <strong>' . esc_html($booking->first_name . ' ' . $booking->last_name) . '</strong> atšaukė jūsų susitikimą:</p>' .
            '<div style="background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;">' .
            '<p style="margin: 0;"><strong>Data/Laikas:</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->start_time))) . '</p>' .
            '</div>' .
            '<p>Jei norite užsiregistruoti iš naujo, prašome apsilankyti registracijos puslapyje.</p>' .
            '<p>Pagarbiai,<br>Mokyklos administracija</p>'
        );

        return self::send_email($booking->parent_email, $subject, $message);
    }

    public static function send_reminder($booking) {
        $cancel_url = home_url('/?pm_action=cancel&token=' . $booking->cancel_token);

        $subject = __('Reminder: Meeting Tomorrow', 'parent-meetings');

        $details = '<div style="background-color: #e7f3ff; padding: 20px; border-radius: 4px; border-left: 4px solid #0073aa; margin: 20px 0;">' .
            '<p style="margin: 5px 0;"><strong>' . __('Teacher:', 'parent-meetings') . '</strong> ' . esc_html($booking->first_name . ' ' . $booking->last_name) . '</p>' .
            '<p style="margin: 5px 0;"><strong>' . __('Date:', 'parent-meetings') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($booking->start_time))) . '</p>' .
            '<p style="margin: 5px 0;"><strong>' . __('Time:', 'parent-meetings') . '</strong> ' . esc_html(date_i18n(get_option('time_format'), strtotime($booking->start_time))) . ' - ' . esc_html(date_i18n(get_option('time_format'), strtotime($booking->end_time))) . '</p>' .
            '<p style="margin: 5px 0;"><strong>' . __('Type:', 'parent-meetings') . '</strong> ' . esc_html($booking->meeting_type) . '</p>' .
            '</div>';

        $message = self::get_email_template(
            sprintf(__('Hello %s!', 'parent-meetings'), esc_html($booking->parent_name)),
            '<p><strong>🔔 ' . __('Reminder: Your meeting is tomorrow!', 'parent-meetings') . '</strong></p>' .
            $details .
            '<p style="margin: 30px 0;">' .
            '<a href="' . esc_url($cancel_url) . '" style="display: inline-block; padding: 10px 20px; background-color: #666; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 14px;">' . __('Cancel Meeting', 'parent-meetings') . '</a>' .
            '</p>' .
            '<p>' . __('Best regards,', 'parent-meetings') . '<br>' . __('School Administration', 'parent-meetings') . '</p>'
        );

        return self::send_email($booking->parent_email, $subject, $message);
    }

    public static function send_reschedule_notification($booking_id, $old_slot_id, $new_slot_id) {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, t.first_name, t.last_name, t.email as teacher_email, s.start_time, s.end_time
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.id = %d",
            $booking_id
        ));

        if (!$booking) return false;

        // Get old slot info
        $old_slot = $wpdb->get_row($wpdb->prepare(
            "SELECT start_time, end_time FROM {$wpdb->prefix}pm_slots WHERE id = %d",
            $old_slot_id
        ));

        // Email to parent
        $subject = __('Meeting Rescheduled', 'parent-meetings');

        $comparison = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">' .
            '<tr>' .
            '<th style="background-color: #f5f5f5; padding: 12px; text-align: left; border: 1px solid #ddd;">' . __('Old Time', 'parent-meetings') . '</th>' .
            '<th style="background-color: #f5f5f5; padding: 12px; text-align: left; border: 1px solid #ddd;">' . __('New Time', 'parent-meetings') . '</th>' .
            '</tr>' .
            '<tr>' .
            '<td style="padding: 12px; border: 1px solid #ddd; text-decoration: line-through; color: #999;">' .
            esc_html(date_i18n(get_option('date_format'), strtotime($old_slot->start_time))) . '<br>' .
            esc_html(date_i18n(get_option('time_format'), strtotime($old_slot->start_time))) .
            '</td>' .
            '<td style="padding: 12px; border: 1px solid #ddd; background-color: #d4edda; font-weight: bold;">' .
            esc_html(date_i18n(get_option('date_format'), strtotime($booking->start_time))) . '<br>' .
            esc_html(date_i18n(get_option('time_format'), strtotime($booking->start_time))) .
            '</td>' .
            '</tr>' .
            '</table>';

        $message = self::get_email_template(
            sprintf(__('Hello %s!', 'parent-meetings'), esc_html($booking->parent_name)),
            '<p><strong>✏️ ' . __('Your meeting has been rescheduled', 'parent-meetings') . '</strong></p>' .
            '<p><strong>' . __('Teacher:', 'parent-meetings') . '</strong> ' . esc_html($booking->first_name . ' ' . $booking->last_name) . '</p>' .
            $comparison .
            '<p>' . __('Best regards,', 'parent-meetings') . '<br>' . __('School Administration', 'parent-meetings') . '</p>'
        );

        self::send_email($booking->parent_email, $subject, $message);

        // Email to teacher
        $teacher_subject = __('Meeting Rescheduled', 'parent-meetings');

        $teacher_message = self::get_email_template(
            sprintf(__('Hello %s %s!', 'parent-meetings'), esc_html($booking->first_name), esc_html($booking->last_name)),
            '<p>' . sprintf(__('Parent <strong>%s</strong> has rescheduled their meeting:', 'parent-meetings'), esc_html($booking->parent_name)) . '</p>' .
            $comparison .
            '<p>' . __('Best regards,', 'parent-meetings') . '<br>' . __('System', 'parent-meetings') . '</p>'
        );

        return self::send_with_secondary($booking->teacher_email, $teacher_subject, $teacher_message, $booking->project_id);
    }

    /**
     * Generate iCal attachment for a booking
     */
    public static function generate_ical($booking) {
        $start = date('Ymd\THis', strtotime($booking->start_time));
        $end = date('Ymd\THis', strtotime($booking->end_time));
        $now = date('Ymd\THis');

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Parent Meetings//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:REQUEST\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . md5($booking->id . $booking->created_at) . "@" . parse_url(home_url(), PHP_URL_HOST) . "\r\n";
        $ical .= "DTSTAMP:" . $now . "\r\n";
        $ical .= "DTSTART:" . $start . "\r\n";
        $ical .= "DTEND:" . $end . "\r\n";
        $ical .= "SUMMARY:" . sprintf(__('Meeting with %s %s', 'parent-meetings'), $booking->first_name, $booking->last_name) . "\r\n";
        $ical .= "DESCRIPTION:" . sprintf(__('Parent-teacher meeting - Type: %s', 'parent-meetings'), $booking->meeting_type) . "\r\n";
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "SEQUENCE:0\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Get HTML email template wrapper
     */
    private static function get_email_template($greeting, $content) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        $template = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($site_name) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0073aa 0%, #005177 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">' . esc_html($site_name) . '</h1>
                            <p style="margin: 5px 0 0 0; color: #cce7f5; font-size: 14px;">Tėvų susitikimų sistema</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333; font-size: 20px;">' . $greeting . '</h2>
                            <div style="color: #555; font-size: 15px; line-height: 1.6;">
                                ' . $content . '
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; color: #999; font-size: 12px;">
                                &copy; ' . date('Y') . ' <a href="' . esc_url($site_url) . '" style="color: #0073aa; text-decoration: none;">' . esc_html($site_name) . '</a>
                            </p>
                            <p style="margin: 10px 0 0 0; color: #999; font-size: 11px;">
                                Šis laiškas buvo išsiųstas automatiškai. Prašome neatsakinėti į šį laišką.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        return $template;
    }

    private static function send_email($to, $subject, $message, $attachments = []) {
        $from_name = PM_Settings::get_option('from_name', get_bloginfo('name'));
        $from_email = PM_Settings::get_option('from_email', get_option('admin_email'));

        $headers = [
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Content-Type: text/html; charset=UTF-8'
        ];

        return wp_mail($to, $subject, $message, $headers, $attachments);
    }
}
