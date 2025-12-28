<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Reschedule Meeting', 'parent-meetings'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Reset and Scandinavian Minimalistic Design - Author: Tobalt — https://tobalt.lt */

        * {
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }

        html, body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif !important;
            background: #f7f9fa !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.6 !important;
            color: #2c3e50 !important;
        }

        body {
            padding: 40px 20px !important;
        }

        .pm-reschedule-container {
            max-width: 680px !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }

        .pm-reschedule-container > * {
            margin-bottom: 24px !important;
        }

        h1 {
            font-size: 28px !important;
            font-weight: 600 !important;
            color: #1a1a1a !important;
            letter-spacing: -0.02em !important;
            line-height: 1.3 !important;
            margin-bottom: 32px !important;
            padding: 0 !important;
        }

        h3 {
            font-size: 18px !important;
            font-weight: 600 !important;
            color: #1a1a1a !important;
            margin: 0 0 16px 0 !important;
            padding: 0 !important;
        }

        p {
            margin: 12px 0 !important;
            padding: 0 !important;
            font-size: 15px !important;
            color: #4a5568 !important;
            line-height: 1.6 !important;
        }

        strong {
            font-weight: 600 !important;
            color: #1a1a1a !important;
        }

        /* Current Booking Card */
        .pm-current-booking {
            background: #ffffff !important;
            border-left: 3px solid #607d8b !important;
            padding: 24px 28px !important;
            border-radius: 2px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06) !important;
            border: 1px solid #e8ecef !important;
            border-left: 3px solid #607d8b !important;
        }

        .pm-current-booking h3 {
            margin-top: 0 !important;
        }

        .pm-current-booking p {
            margin: 10px 0 !important;
        }

        /* Available Slots Section */
        .pm-available-slots {
            margin-top: 32px !important;
        }

        .pm-available-slots > h3 {
            margin-bottom: 12px !important;
        }

        .pm-available-slots > p {
            margin-bottom: 24px !important;
        }

        /* Day Groups */
        .pm-day-group {
            background: #ffffff !important;
            padding: 0 !important;
            margin-bottom: 16px !important;
            border-radius: 2px !important;
            border: 1px solid #e8ecef !important;
            overflow: hidden !important;
        }

        .pm-day-header {
            font-size: 15px !important;
            font-weight: 600 !important;
            padding: 16px 20px !important;
            background: #607d8b !important;
            color: #ffffff !important;
            border-bottom: none !important;
            margin: 0 !important;
        }

        /* Slot Items */
        .pm-slot-item {
            padding: 16px 20px !important;
            margin: 0 !important;
            background: #f7f9fa !important;
            border: none !important;
            border-bottom: 1px solid #e8ecef !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            display: flex !important;
            align-items: center !important;
        }

        .pm-slot-item:last-child {
            border-bottom: none !important;
        }

        .pm-slot-item:hover {
            background: #ffffff !important;
            transform: translateX(4px) !important;
        }

        .pm-slot-item.selected {
            background: #607d8b !important;
            color: #ffffff !important;
        }

        .pm-slot-item input[type="radio"] {
            margin-right: 12px !important;
            width: 18px !important;
            height: 18px !important;
            cursor: pointer !important;
        }

        /* Buttons */
        .pm-button {
            display: inline-block !important;
            padding: 14px 32px !important;
            background: #607d8b !important;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 2px !important;
            border: none !important;
            cursor: pointer !important;
            font-size: 15px !important;
            font-weight: 500 !important;
            transition: all 0.15s ease !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif !important;
        }

        .pm-button:hover {
            background: #546e7a !important;
            box-shadow: 0 2px 8px rgba(96, 125, 139, 0.25) !important;
            transform: translateY(-1px) !important;
            color: #ffffff !important;
        }

        .pm-button:disabled {
            background: #cbd5e0 !important;
            cursor: not-allowed !important;
            transform: none !important;
        }

        .pm-button[style*="background: #dc3232"],
        .pm-button[style*="background: #666"] {
            opacity: 1 !important;
        }

        .pm-button[style*="background: #dc3232"]:hover {
            background: #c92a2a !important;
        }

        .pm-button[style*="background: #666"]:hover {
            background: #555 !important;
        }

        /* Notices */
        .pm-notice {
            padding: 16px 20px !important;
            margin: 20px 0 !important;
            border-radius: 2px !important;
            font-size: 14px !important;
        }

        .pm-notice-success {
            background: #d4edda !important;
            border-left: 3px solid #28a745 !important;
            color: #155724 !important;
        }

        .pm-notice-error {
            background: #f8d7da !important;
            border-left: 3px solid #dc3545 !important;
            color: #721c24 !important;
        }

        #pm-reschedule-message {
            margin: 20px 0 !important;
        }

        /* Form */
        #pm-reschedule-form {
            margin: 0 !important;
            padding: 0 !important;
        }

        #pm-reschedule-form > p {
            margin-top: 32px !important;
            padding-top: 24px !important;
            border-top: 1px solid #e8ecef !important;
        }

        /* Mobile Responsive */
        @media (max-width: 640px) {
            body {
                padding: 20px 16px !important;
            }

            h1 {
                font-size: 24px !important;
                margin-bottom: 24px !important;
            }

            .pm-current-booking {
                padding: 20px !important;
            }

            .pm-slot-item {
                padding: 14px 16px !important;
                font-size: 14px !important;
            }

            .pm-button {
                display: block !important;
                width: 100% !important;
                margin: 8px 0 !important;
                text-align: center !important;
            }

            #pm-reschedule-form > p .pm-button {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="pm-reschedule-container">
        <h1><?php _e('Reschedule Meeting', 'parent-meetings'); ?></h1>

        <div class="pm-current-booking">
            <h3><?php _e('Current Booking', 'parent-meetings'); ?></h3>
            <p><strong><?php _e('Teacher:', 'parent-meetings'); ?></strong> <?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?></p>
            <p><strong><?php _e('Current Time:', 'parent-meetings'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->start_time))); ?></p>
            <p><strong><?php _e('Meeting Type:', 'parent-meetings'); ?></strong> <?php echo esc_html($booking->meeting_type); ?></p>
        </div>

        <div id="pm-reschedule-message"></div>

        <?php if (empty($available_slots)): ?>
            <div class="pm-notice pm-notice-error">
                <p><?php _e('Sorry, there are no available time slots for this teacher at the moment. Please try again later or cancel your current booking.', 'parent-meetings'); ?></p>
            </div>
            <p>
                <a href="<?php echo esc_url(home_url('/?pm_action=cancel&token=' . $token)); ?>" class="pm-button" style="background: #dc3232 !important;">
                    <?php _e('Cancel Current Booking', 'parent-meetings'); ?>
                </a>
            </p>
        <?php else: ?>
            <div class="pm-available-slots">
                <h3><?php _e('Select New Time Slot', 'parent-meetings'); ?></h3>
                <p><?php _e('Choose a new time slot from the available options below:', 'parent-meetings'); ?></p>

                <form id="pm-reschedule-form">
                    <?php
                    // Group slots by date
                    $grouped_slots = [];
                    foreach ($available_slots as $slot) {
                        $date = date('Y-m-d', strtotime($slot->start_time));
                        if (!isset($grouped_slots[$date])) {
                            $grouped_slots[$date] = [];
                        }
                        $grouped_slots[$date][] = $slot;
                    }

                    foreach ($grouped_slots as $date => $slots): ?>
                        <div class="pm-day-group">
                            <div class="pm-day-header">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($date))); ?>
                            </div>
                            <?php foreach ($slots as $slot): ?>
                                <label class="pm-slot-item" data-slot-id="<?php echo esc_attr($slot->id); ?>">
                                    <input type="radio" name="new_slot_id" value="<?php echo esc_attr($slot->id); ?>">
                                    <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($slot->start_time))); ?>
                                    -
                                    <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($slot->end_time))); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <p style="margin-top: 30px !important;">
                        <button type="submit" class="pm-button" id="pm-reschedule-submit" disabled>
                            <?php _e('Confirm Reschedule', 'parent-meetings'); ?>
                        </button>
                        <a href="<?php echo esc_url(home_url('/?pm_action=cancel&token=' . $token)); ?>" class="pm-button" style="background: #666 !important; margin-left: 10px !important;">
                            <?php _e('Or Cancel Booking', 'parent-meetings'); ?>
                        </a>
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
