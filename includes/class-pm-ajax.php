<?php

class PM_Ajax {
    
    public static function get_classes() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $cache_key = 'pm_classes_' . $project_id;
        $classes = get_transient($cache_key);

        if (false === $classes) {
            if ($project_id > 0) {
                // Get classes for specific project
                $classes = $wpdb->get_results($wpdb->prepare(
                    "SELECT c.id, c.name
                     FROM {$wpdb->prefix}pm_classes c
                     INNER JOIN {$wpdb->prefix}pm_class_projects cp ON c.id = cp.class_id
                     WHERE cp.project_id = %d
                     ORDER BY c.name",
                    $project_id
                ));
            } else {
                // Get all classes
                $classes = $wpdb->get_results(
                    "SELECT id, name FROM {$wpdb->prefix}pm_classes ORDER BY name"
                );
            }
            // Cache for 1 hour (invalidated on admin changes)
            set_transient($cache_key, $classes, HOUR_IN_SECONDS);
        }

        wp_send_json_success($classes);
    }
    
    public static function get_teachers() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $class_id = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
        $project_id = isset($_POST['project_id']) ? absint($_POST['project_id']) : 0;

        // Build the query based on parameters
        if ($project_id > 0) {
            // Filter by project
            if ($class_id > 0) {
                // Sanitize class_id for JSON_CONTAINS - ensure it's a clean integer string
                $class_id_json = wp_json_encode( $class_id );
                if ( false === $class_id_json || ! is_numeric( json_decode( $class_id_json ) ) ) {
                    wp_send_json_error( __( 'Invalid class ID.', 'parent-meetings' ) );
                }
                // Filter by both project and class
                $teachers = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT t.id, t.first_name, t.last_name, t.meeting_types
                     FROM {$wpdb->prefix}pm_teachers t
                     INNER JOIN {$wpdb->prefix}pm_teacher_projects tp ON t.id = tp.teacher_id
                     WHERE tp.project_id = %d
                     AND (
                         JSON_CONTAINS(t.class_ids, %s)
                         OR t.class_ids IS NULL
                         OR t.class_ids = '[]'
                         OR t.class_ids = ''
                     )
                     AND EXISTS (
                         SELECT 1 FROM {$wpdb->prefix}pm_slots s
                         WHERE s.teacher_id = t.id
                         AND s.project_id = %d
                         AND s.status = 'available'
                         AND s.start_time > NOW()
                     )
                     ORDER BY t.last_name, t.first_name",
                    $project_id,
                    $class_id_json,
                    $project_id
                ));
            } else {
                // Filter by project only (no class selection)
                $teachers = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT t.id, t.first_name, t.last_name, t.meeting_types
                     FROM {$wpdb->prefix}pm_teachers t
                     INNER JOIN {$wpdb->prefix}pm_teacher_projects tp ON t.id = tp.teacher_id
                     WHERE tp.project_id = %d
                     AND EXISTS (
                         SELECT 1 FROM {$wpdb->prefix}pm_slots s
                         WHERE s.teacher_id = t.id
                         AND s.project_id = %d
                         AND s.status = 'available'
                         AND s.start_time > NOW()
                     )
                     ORDER BY t.last_name, t.first_name",
                    $project_id,
                    $project_id
                ));
            }
        } else {
            // Legacy: no project filter
            if (!$class_id || $class_id < 1) {
                wp_send_json_error(__('Class not specified.', 'parent-meetings'));
            }

            // Sanitize class_id for JSON_CONTAINS - ensure it's a clean integer string
            $class_id_json = wp_json_encode( $class_id );
            if ( false === $class_id_json || ! is_numeric( json_decode( $class_id_json ) ) ) {
                wp_send_json_error( __( 'Invalid class ID.', 'parent-meetings' ) );
            }

            $teachers = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT t.id, t.first_name, t.last_name, t.meeting_types
                 FROM {$wpdb->prefix}pm_teachers t
                 WHERE JSON_CONTAINS(t.class_ids, %s)
                 AND EXISTS (
                     SELECT 1 FROM {$wpdb->prefix}pm_slots s
                     WHERE s.teacher_id = t.id
                     AND s.status = 'available'
                     AND s.start_time > NOW()
                 )
                 ORDER BY t.last_name, t.first_name",
                $class_id_json
            ));
        }

        wp_send_json_success($teachers);
    }
    
    public static function get_time_slots() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
        $meeting_type = isset($_POST['meeting_type']) ? sanitize_text_field($_POST['meeting_type']) : '';
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

        // Validate inputs
        if (!$teacher_id || $teacher_id < 1) {
            wp_send_json_error(__('Teacher not specified.', 'parent-meetings'));
        }

        // Validate meeting type against whitelist
        $valid_types = ['vietoje', 'nuotoliu', 'both'];
        if (!in_array($meeting_type, $valid_types, true)) {
            wp_send_json_error(__('Invalid meeting type.', 'parent-meetings'));
        }

        // Get teacher message
        if ($project_id > 0) {
            $message = $wpdb->get_var($wpdb->prepare(
                "SELECT message FROM {$wpdb->prefix}pm_availability
                 WHERE teacher_id = %d
                 AND project_id = %d
                 AND date >= CURDATE()
                 AND message IS NOT NULL
                 AND message != ''
                 ORDER BY date ASC
                 LIMIT 1",
                $teacher_id,
                $project_id
            ));
        } else {
            $message = $wpdb->get_var($wpdb->prepare(
                "SELECT message FROM {$wpdb->prefix}pm_availability
                 WHERE teacher_id = %d
                 AND date >= CURDATE()
                 AND message IS NOT NULL
                 AND message != ''
                 ORDER BY date ASC
                 LIMIT 1",
                $teacher_id
            ));
        }

        // Get slots for next 14 days (excluding hidden slots)
        if ($project_id > 0) {
            $slots = $wpdb->get_results($wpdb->prepare(
                "SELECT s.id, s.start_time, s.end_time, a.message
                 FROM {$wpdb->prefix}pm_slots s
                 LEFT JOIN {$wpdb->prefix}pm_availability a ON s.availability_id = a.id
                 WHERE s.teacher_id = %d
                 AND s.project_id = %d
                 AND s.status = 'available'
                 AND s.is_hidden = 0
                 AND (s.meeting_type = %s OR s.meeting_type = 'both')
                 AND s.start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 14 DAY)
                 ORDER BY s.start_time",
                $teacher_id,
                $project_id,
                $meeting_type
            ));
        } else {
            $slots = $wpdb->get_results($wpdb->prepare(
                "SELECT s.id, s.start_time, s.end_time, a.message
                 FROM {$wpdb->prefix}pm_slots s
                 LEFT JOIN {$wpdb->prefix}pm_availability a ON s.availability_id = a.id
                 WHERE s.teacher_id = %d
                 AND s.status = 'available'
                 AND s.is_hidden = 0
                 AND (s.meeting_type = %s OR s.meeting_type = 'both')
                 AND s.start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 14 DAY)
                 ORDER BY s.start_time",
                $teacher_id,
                $meeting_type
            ));
        }
        
        // Group by date
        $grouped = [];
        $message = '';
        foreach ($slots as $slot) {
            if (empty($message) && !empty($slot->message)) {
                $message = $slot->message;
            }
            $date = date('Y-m-d', strtotime($slot->start_time));
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = [
                'id' => $slot->id,
                'start' => date_i18n(get_option('time_format'), strtotime($slot->start_time)),
                'end' => date_i18n(get_option('time_format'), strtotime($slot->end_time)),
                'timestamp' => strtotime($slot->start_time)
            ];
        }
        
        wp_send_json_success([
            'slots' => $grouped,
            'message' => $message ? wp_kses_post($message) : null
        ]);
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Get quantity-based availability slots for a teacher
     */
    public static function get_quantity_slots() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $teacher_id = intval($_POST['teacher_id']);
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

        if (!$teacher_id) {
            wp_send_json_error('Invalid teacher');
        }

        // Get quantity slots with booking counts
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.slot_date, s.capacity, a.message,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings b
                     WHERE b.slot_id = s.id AND b.status = 'confirmed') as booked_count
             FROM {$wpdb->prefix}pm_slots s
             JOIN {$wpdb->prefix}pm_availability a ON s.availability_id = a.id
             WHERE s.teacher_id = %d
             AND s.project_id = %d
             AND s.meeting_type = 'quantity'
             AND s.slot_date >= CURDATE()
             AND s.is_hidden = 0
             ORDER BY s.slot_date ASC",
            $teacher_id,
            $project_id
        ));

        $result = [];
        $message = null;

        foreach ($slots as $slot) {
            $available = $slot->capacity - $slot->booked_count;
            if ($available > 0) {
                $result[] = [
                    'id' => $slot->id,
                    'date' => $slot->slot_date,
                    'date_formatted' => date_i18n(get_option('date_format'), strtotime($slot->slot_date)),
                    'capacity' => $slot->capacity,
                    'booked' => $slot->booked_count,
                    'available' => $available
                ];
                if (!$message && $slot->message) {
                    $message = $slot->message;
                }
            }
        }

        wp_send_json_success([
            'slots' => $result,
            'message' => $message ? wp_kses_post($message) : null
        ]);
    }

    public static function book_meeting() {
        check_ajax_referer('pm_frontend', 'nonce');

        // Rate limiting: 5 booking attempts per minute per IP
        PM_Rate_Limiter::check_rate_limit('book_meeting', 5, 60);

        // Verify reCAPTCHA if enabled
        $recaptcha_enabled = PM_Settings::get_option('recaptcha_enabled', 0);
        if ($recaptcha_enabled) {
            $recaptcha_token = isset($_POST['recaptcha_token']) ? sanitize_text_field($_POST['recaptcha_token']) : '';
            if (!self::verify_recaptcha($recaptcha_token)) {
                wp_send_json_error(__('reCAPTCHA verification failed. Please try again.', 'parent-meetings'));
            }
        }

        global $wpdb;

        // Sanitize and validate inputs with length limits
        $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
        $parent_name = isset($_POST['parent_name']) ? mb_substr(sanitize_text_field(trim($_POST['parent_name'])), 0, 100) : '';
        $parent_email = isset($_POST['parent_email']) ? mb_substr(sanitize_email(trim($_POST['parent_email'])), 0, 254) : '';
        $parent_phone = isset($_POST['parent_phone']) ? mb_substr(sanitize_text_field(trim($_POST['parent_phone'])), 0, 20) : '';
        $student_name = isset($_POST['student_name']) ? mb_substr(sanitize_text_field(trim($_POST['student_name'])), 0, 100) : '';
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        $meeting_type = isset($_POST['meeting_type']) ? sanitize_text_field($_POST['meeting_type']) : '';
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $notes = isset($_POST['notes']) ? mb_substr(sanitize_textarea_field(trim($_POST['notes'])), 0, 1000) : '';
        $booking_mode = isset($_POST['booking_mode']) ? sanitize_text_field($_POST['booking_mode']) : 'time';

        // Validate required fields
        if (!$slot_id || $slot_id < 1) {
            wp_send_json_error(__('Please select a time slot.', 'parent-meetings'));
        }

        if (empty($parent_name) || strlen($parent_name) < 2) {
            wp_send_json_error(__('Please enter your name.', 'parent-meetings'));
        }

        if (empty($parent_email) || !is_email($parent_email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'parent-meetings'));
        }

        // Validate meeting type (skip for quantity mode)
        if ($booking_mode !== 'quantity') {
            $valid_types = ['vietoje', 'nuotoliu'];
            if (!in_array($meeting_type, $valid_types, true)) {
                wp_send_json_error(__('Invalid meeting type.', 'parent-meetings'));
            }
        } else {
            $meeting_type = 'quantity';
        }

        // Handle based on booking mode
        if ($booking_mode === 'quantity') {
            self::book_quantity_meeting($slot_id, $parent_name, $parent_email, $parent_phone, $student_name, $class_id, $project_id, $notes);
        } else {
            self::book_time_meeting($slot_id, $parent_name, $parent_email, $parent_phone, $student_name, $class_id, $meeting_type, $project_id, $notes);
        }
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Book a time-based meeting (original logic)
     */
    private static function book_time_meeting($slot_id, $parent_name, $parent_email, $parent_phone, $student_name, $class_id, $meeting_type, $project_id, $notes) {
        global $wpdb;

        // START TRANSACTION FIRST to prevent race conditions
        $wpdb->query('START TRANSACTION');

        // Lock the slot immediately with FOR UPDATE
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_slots WHERE id = %d FOR UPDATE",
            $slot_id
        ));

        if (!$slot || $slot->status !== 'available') {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('This time slot was just booked. Please select another time.', 'parent-meetings'));
        }

        // Check minimum advance booking time (now inside transaction with lock)
        $advance_hours = PM_Settings::get_option('booking_advance_hours', 1);
        $min_booking_time = date('Y-m-d H:i:s', strtotime("+{$advance_hours} hours"));

        if ($slot->start_time < $min_booking_time) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(
                sprintf(
                    __('Bookings must be made at least %d hour(s) in advance.', 'parent-meetings'),
                    $advance_hours
                )
            );
        }

        // Check for overlapping bookings for this parent (with FOR UPDATE lock)
        $overlap = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.parent_email = %s
             AND b.status = 'confirmed'
             AND s.start_time <= %s
             AND s.end_time >= %s
             FOR UPDATE",
            $parent_email,
            $slot->end_time,
            $slot->start_time
        ));

        if ($overlap > 0) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('You already have a booking at the same time with another teacher.', 'parent-meetings'));
        }

        // Create booking
        $cancel_token = bin2hex(random_bytes(32));

        $wpdb->insert($wpdb->prefix . 'pm_bookings', [
            'slot_id' => $slot_id,
            'teacher_id' => $slot->teacher_id,
            'project_id' => $project_id > 0 ? $project_id : $slot->project_id,
            'parent_name' => $parent_name,
            'parent_email' => $parent_email,
            'parent_phone' => $parent_phone,
            'student_name' => $student_name,
            'class_id' => $class_id,
            'meeting_type' => $meeting_type,
            'notes' => $notes,
            'status' => 'confirmed',
            'cancel_token' => $cancel_token
        ]);

        $booking_id = $wpdb->insert_id;

        // Mark slot as booked
        $wpdb->update(
            $wpdb->prefix . 'pm_slots',
            ['status' => 'booked'],
            ['id' => $slot_id]
        );

        $wpdb->query('COMMIT');

        // Send confirmation email
        PM_Emails::send_booking_confirmation($booking_id);

        wp_send_json_success([
            'message' => __('Meeting booked successfully! Check your email for confirmation.', 'parent-meetings'),
            'booking_id' => $booking_id
        ]);
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Book a quantity-based meeting
     */
    private static function book_quantity_meeting($slot_id, $parent_name, $parent_email, $parent_phone, $student_name, $class_id, $project_id, $notes) {
        global $wpdb;

        // Check slot availability and capacity (with lock)
        $wpdb->query('START TRANSACTION');

        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_slots WHERE id = %d FOR UPDATE",
            $slot_id
        ));

        if (!$slot) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('Invalid slot selected.', 'parent-meetings'));
        }

        // Count current bookings for this slot (with FOR UPDATE lock to prevent race conditions)
        $booked_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings
             WHERE slot_id = %d AND status = 'confirmed'
             FOR UPDATE",
            $slot_id
        ));

        if ($booked_count >= $slot->capacity) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('No more spots available for this date. Please select another date.', 'parent-meetings'));
        }

        // Check if parent already has booking for this slot/date (with FOR UPDATE lock)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings
             WHERE slot_id = %d AND parent_email = %s AND status = 'confirmed'
             FOR UPDATE",
            $slot_id,
            $parent_email
        ));

        if ($existing > 0) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('You already have a registration for this date.', 'parent-meetings'));
        }

        // Assign position number atomically using MAX (safer than COUNT for race conditions)
        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(position_number), 0) FROM {$wpdb->prefix}pm_bookings
             WHERE slot_id = %d AND status = 'confirmed'",
            $slot_id
        ));
        $position_number = $max_position + 1;

        // Create booking
        $cancel_token = bin2hex(random_bytes(32));

        $wpdb->insert($wpdb->prefix . 'pm_bookings', [
            'slot_id' => $slot_id,
            'teacher_id' => $slot->teacher_id,
            'project_id' => $project_id > 0 ? $project_id : $slot->project_id,
            'position_number' => $position_number,
            'parent_name' => $parent_name,
            'parent_email' => $parent_email,
            'parent_phone' => $parent_phone,
            'student_name' => $student_name,
            'class_id' => $class_id,
            'meeting_type' => 'quantity',
            'notes' => $notes,
            'status' => 'confirmed',
            'cancel_token' => $cancel_token
        ]);

        $booking_id = $wpdb->insert_id;

        $wpdb->query('COMMIT');

        // Send confirmation email
        PM_Emails::send_booking_confirmation($booking_id);

        wp_send_json_success([
            'message' => __('Registration successful! Check your email for confirmation.', 'parent-meetings'),
            'booking_id' => $booking_id,
            'position' => $position_number
        ]);
    }
    
    public static function cancel_booking() {
        // Handled in frontend class
    }

    public static function reschedule_booking() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $new_slot_id = isset($_POST['new_slot_id']) ? intval($_POST['new_slot_id']) : 0;
        $cancel_token = isset($_POST['cancel_token']) ? sanitize_text_field($_POST['cancel_token']) : '';

        // Validate inputs
        if (!$booking_id || !$new_slot_id || empty($cancel_token)) {
            wp_send_json_error(__('Invalid reschedule request.', 'parent-meetings'));
        }

        // Verify booking and token
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_bookings WHERE id = %d AND cancel_token = %s",
            $booking_id,
            $cancel_token
        ));

        if (!$booking) {
            wp_send_json_error(__('Booking not found or invalid token.', 'parent-meetings'));
        }

        // Check if booking is still active
        if ($booking->status !== 'confirmed') {
            wp_send_json_error(__('This booking has already been cancelled.', 'parent-meetings'));
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Check new slot availability
        $new_slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_slots WHERE id = %d FOR UPDATE",
            $new_slot_id
        ));

        if (!$new_slot || $new_slot->status !== 'available') {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('The selected time slot is no longer available.', 'parent-meetings'));
        }

        // Check if new slot is for the same teacher
        if ($new_slot->teacher_id != $booking->teacher_id) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('You can only reschedule with the same teacher.', 'parent-meetings'));
        }

        // Check for time conflicts
        $overlap = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.parent_email = %s
             AND b.id != %d
             AND b.status = 'confirmed'
             AND s.start_time <= %s
             AND s.end_time >= %s",
            $booking->parent_email,
            $booking_id,
            $new_slot->end_time,
            $new_slot->start_time
        ));

        if ($overlap > 0) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(__('You have another booking at this time.', 'parent-meetings'));
        }

        // Free the old slot
        $wpdb->update(
            $wpdb->prefix . 'pm_slots',
            ['status' => 'available'],
            ['id' => $booking->slot_id]
        );

        // Book the new slot
        $wpdb->update(
            $wpdb->prefix . 'pm_slots',
            ['status' => 'booked'],
            ['id' => $new_slot_id]
        );

        // Update booking
        $wpdb->update(
            $wpdb->prefix . 'pm_bookings',
            ['slot_id' => $new_slot_id],
            ['id' => $booking_id]
        );

        $wpdb->query('COMMIT');

        // Send reschedule notification
        PM_Emails::send_reschedule_notification($booking_id, $booking->slot_id, $new_slot_id);

        // Notify waiting list for old slot
        do_action('pm_slot_available', $booking->slot_id);

        wp_send_json_success([
            'message' => __('Meeting rescheduled successfully!', 'parent-meetings')
        ]);
    }
    
    public static function toggle_attendance() {
        check_ajax_referer('pm_frontend', 'nonce');
        
        global $wpdb;
        
        $booking_id = intval($_POST['booking_id']);
        $token = sanitize_text_field($_POST['token']);
        
        // Verify teacher token
        $teacher_id = PM_Magic_Links::validate_token($token, 'print');
        
        if (!$teacher_id) {
            wp_send_json_error('Negalioja nuoroda');
        }
        
        // Get current status
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT attendance FROM {$wpdb->prefix}pm_bookings 
             WHERE id = %d AND teacher_id = %d",
            $booking_id,
            $teacher_id
        ));
        
        if ($current === null) {
            wp_send_json_error('Rezervacija nerasta');
        }
        
        // Toggle status
        switch($current) {
            case 'pending':
                $new_status = 'attended';
                break;
            case 'attended':
                $new_status = 'missed';
                break;
            case 'missed':
                $new_status = 'attended';
                break;
            default:
                $new_status = 'attended';
                break;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'pm_bookings',
            ['attendance' => $new_status],
            ['id' => $booking_id]
        );
        
        wp_send_json_success(['status' => $new_status]);
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Set attendance status directly (not toggle)
     */
    public static function set_attendance() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $booking_id = intval($_POST['booking_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $token = sanitize_text_field($_POST['token']);

        // Validate status
        if (!in_array($new_status, ['pending', 'attended', 'missed'])) {
            wp_send_json_error('Neteisinga būsena');
        }

        // Verify teacher token
        $teacher_id = PM_Magic_Links::validate_token($token, 'print');

        if (!$teacher_id) {
            wp_send_json_error('Negalioja nuoroda');
        }

        // Verify booking belongs to teacher
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pm_bookings WHERE id = %d AND teacher_id = %d",
            $booking_id,
            $teacher_id
        ));

        if (!$booking) {
            wp_send_json_error('Rezervacija nerasta');
        }

        $wpdb->update(
            $wpdb->prefix . 'pm_bookings',
            ['attendance' => $new_status],
            ['id' => $booking_id]
        );

        wp_send_json_success(['status' => $new_status]);
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Teacher cancels a booking from their print list
     */
    public static function teacher_cancel_booking() {
        check_ajax_referer('pm_frontend', 'nonce');

        global $wpdb;

        $booking_id = intval($_POST['booking_id']);
        $token = sanitize_text_field($_POST['token']);

        // Verify teacher token
        $teacher_id = PM_Magic_Links::validate_token($token, 'print');

        if (!$teacher_id) {
            wp_send_json_error('Negalioja nuoroda');
        }

        // Get booking with slot info
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.start_time
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.id = %d AND b.teacher_id = %d AND b.status = 'confirmed'",
            $booking_id,
            $teacher_id
        ));

        if (!$booking) {
            wp_send_json_error('Rezervacija nerasta arba jau atšaukta');
        }

        // Check if meeting hasn't started yet
        if (strtotime($booking->start_time) <= time()) {
            wp_send_json_error('Susitikimas jau prasidėjo, negalima atšaukti');
        }

        // Update booking status
        $wpdb->update(
            $wpdb->prefix . 'pm_bookings',
            ['status' => 'cancelled'],
            ['id' => $booking_id]
        );

        // Free the slot
        $wpdb->update(
            $wpdb->prefix . 'pm_slots',
            ['status' => 'available'],
            ['id' => $booking->slot_id]
        );

        // Send notification to parent
        PM_Emails::send_teacher_cancellation_notice($booking_id);

        wp_send_json_success(['message' => 'Rezervacija atšaukta']);
    }

    public static function toggle_slot_visibility() {
        check_ajax_referer('pm_frontend', 'nonce');
        
        global $wpdb;
        
        $slot_id = intval($_POST['slot_id']);
        $hidden = intval($_POST['hidden']);
        $token = sanitize_text_field($_POST['token']);
        
        // Verify teacher token
        $teacher_id = PM_Magic_Links::validate_token($token, 'manage');
        
        if (!$teacher_id) {
            wp_send_json_error('Negalioja nuoroda');
        }
        
        // Check if slot belongs to teacher and is available
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_slots 
             WHERE id = %d AND teacher_id = %d",
            $slot_id,
            $teacher_id
        ));
        
        if (!$slot) {
            wp_send_json_error('Laikas nerastas');
        }
        
        if ($slot->status !== 'available') {
            wp_send_json_error('Užimto laiko negalima paslėpti');
        }
        
        // Update visibility
        $wpdb->update(
            $wpdb->prefix . 'pm_slots',
            ['is_hidden' => $hidden],
            ['id' => $slot_id]
        );
        
        wp_send_json_success(['hidden' => $hidden]);
    }
    
    private static function verify_recaptcha($token) {
        if (empty(PM_RECAPTCHA_SECRET_KEY) || PM_RECAPTCHA_SECRET_KEY === 'YOUR_RECAPTCHA_SECRET_KEY_HERE') {
            return true; // Skip verification if not configured
        }
        
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => PM_RECAPTCHA_SECRET_KEY,
                'response' => $token
            ]
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return isset($body['success']) && $body['success'] && $body['score'] >= 0.5;
    }
}
