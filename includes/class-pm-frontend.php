<?php

class PM_Frontend {
    
    public function __construct() {
        add_shortcode('parent_meetings', [$this, 'render_booking_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('template_redirect', [$this, 'handle_magic_urls']);
    }
    
    public function enqueue_scripts() {
        // Always enqueue on frontend - let shortcode rendering handle display
        // The has_shortcode check doesn't work reliably with page builders
        global $post;

        // Skip if we're in admin
        if (is_admin()) {
            return;
        }

        // Check if shortcode exists in post content
        $has_shortcode = false;
        if (is_a($post, 'WP_Post')) {
            $has_shortcode = has_shortcode($post->post_content, 'parent_meetings');
        }

        // Enqueue if shortcode found OR if we're on a page (to support page builders)
        if (!$has_shortcode && !is_page()) {
            return;
        }

        $this->force_enqueue_scripts();
    }

    private function force_enqueue_scripts() {
        // Prevent double-enqueuing
        static $already_enqueued = false;
        if ($already_enqueued) {
            return;
        }
        $already_enqueued = true;

        wp_enqueue_style('pm-frontend', PM_PLUGIN_URL . 'assets/css/frontend.css', [], PM_VERSION);
        wp_enqueue_script('pm-frontend', PM_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], PM_VERSION, true);

        $site_key = PM_Settings::get_option('recaptcha_site_key', '');
        wp_localize_script('pm-frontend', 'pmFrontend', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pm_frontend'),
            'recaptcha_key' => $site_key,
            'project_id' => 0 // Will be overridden by template
        ]);

        // Load reCAPTCHA only if configured
        $recaptcha_enabled = PM_Settings::get_option('recaptcha_enabled', 0);
        if ($recaptcha_enabled && !empty($site_key)) {
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, [], null, true);
        }
    }
    
    public function render_booking_form($atts = []) {
        global $wpdb;

        // Force enqueue scripts when shortcode is rendered
        $this->force_enqueue_scripts();

        // Parse shortcode attributes
        $atts = shortcode_atts([
            'id' => 0 // project_id
        ], $atts, 'parent_meetings');

        $project_id = intval($atts['id']);

        // If project_id is provided, validate it exists
        if ($project_id > 0) {
            $project = PM_Projects::get($project_id);
            if (!$project) {
                return '<div class="pm-notice pm-error">' . esc_html__('Invalid project ID.', 'parent-meetings') . '</div>';
            }
        }

        // Check if any teacher has availability for this project
        if ($project_id > 0) {
            $has_availability = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pm_slots
                 WHERE status = 'available' AND start_time > NOW() AND project_id = %d",
                $project_id
            ));
        } else {
            $has_availability = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pm_slots
                 WHERE status = 'available' AND start_time > NOW()"
            );
        }

        if (!$has_availability) {
            return '<div class="pm-notice">' . esc_html__('There are no available meeting times at the moment.', 'parent-meetings') . '</div>';
        }

        // Pass project_id to the template
        $booking_project_id = $project_id;

        ob_start();
        include PM_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }
    
    public function handle_magic_urls() {
        if (!isset($_GET['pm_action']) || !isset($_GET['token'])) {
            return;
        }

        $action = sanitize_text_field($_GET['pm_action']);
        $token = sanitize_text_field($_GET['token']);

        if ($action === 'manage') {
            $this->show_manage_availability($token);
        } elseif ($action === 'print') {
            $this->show_print_list($token);
        } elseif ($action === 'cancel') {
            $this->handle_cancellation($token);
        } elseif ($action === 'reschedule') {
            $this->show_reschedule_form($token);
        }
    }
    
    private function show_manage_availability($token) {
        $teacher_id = PM_Magic_Links::validate_token($token, 'manage');
        
        if (!$teacher_id) {
            wp_die(esc_html__('Link is invalid or expired.', 'parent-meetings'));
        }
        
        global $wpdb;
        $teacher = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_teachers WHERE id = %d",
            $teacher_id
        ));

        // Check if editing availability
        if (isset($_GET['edit_availability'])) {
            $this->show_edit_availability($token, $teacher_id, $teacher);
            exit;
        }

        // Check if managing individual slots
        if (isset($_GET['manage_slots'])) {
            $availability_id = intval($_GET['manage_slots']);
            
            // Get availability and slots
            $availability = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pm_availability WHERE id = %d AND teacher_id = %d",
                $availability_id,
                $teacher_id
            ));
            
            if (!$availability) {
                wp_die('Prieinamumo laikotarpis nerastas.');
            }
            
            $message = $availability->message;
            
            $slots = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pm_slots 
                 WHERE availability_id = %d 
                 ORDER BY start_time",
                $availability_id
            ));
            
            include PM_PLUGIN_DIR . 'templates/manage-slots.php';
            exit;
        }
        
        // Handle form submission
        if (isset($_POST['pm_add_availability']) && check_admin_referer('pm_availability_' . $teacher_id)) {
            $this->process_availability_form($teacher_id);
        }

        // Handle delete availability
        if (isset($_POST['pm_delete_availability']) && check_admin_referer('pm_delete_availability_' . $teacher_id)) {
            $this->process_delete_availability($teacher_id);
        }

        // Handle slot toggle
        if (isset($_POST['pm_toggle_slot']) && check_admin_referer('pm_slot_toggle_' . $teacher_id)) {
            $slot_id = intval($_POST['slot_id']);
            $current_hidden = intval($_POST['current_hidden']);
            
            $wpdb->update(
                $wpdb->prefix . 'pm_slots',
                ['is_hidden' => $current_hidden ? 0 : 1],
                [
                    'id' => $slot_id,
                    'teacher_id' => $teacher_id,
                    'status' => 'available'
                ]
            );
            
            echo '<div class="pm-notice pm-notice-success">' . ($current_hidden ? 'Laikas atidarytas tėvams.' : 'Laikas paslėptas nuo tėvų.') . '</div>';
        }
        
        // Get teacher's projects
        $teacher_projects = PM_Projects::get_teacher_projects($teacher_id);

        $availabilities = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.name as project_name
             FROM {$wpdb->prefix}pm_availability a
             LEFT JOIN {$wpdb->prefix}pm_projects p ON a.project_id = p.id
             WHERE a.teacher_id = %d AND a.date >= CURDATE()
             ORDER BY a.date, a.start_time",
            $teacher_id
        ));

        // Get upcoming slots for management
        $upcoming_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, a.message 
             FROM {$wpdb->prefix}pm_slots s
             JOIN {$wpdb->prefix}pm_availability a ON s.availability_id = a.id
             WHERE s.teacher_id = %d 
             AND s.start_time >= NOW()
             AND s.status = 'available'
             ORDER BY s.start_time
             LIMIT 50",
            $teacher_id
        ));
        
        include PM_PLUGIN_DIR . 'templates/manage-availability.php';
        exit;
    }
    
    private function process_availability_form($teacher_id) {
        global $wpdb;

        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $booking_mode = isset($_POST['booking_mode']) ? sanitize_text_field($_POST['booking_mode']) : 'time';
        $message = wp_kses_post($_POST['availability_message']);

        // Validate project
        if ($project_id <= 0) {
            echo '<div class="pm-notice pm-notice-error">Prašome pasirinkti projektą!</div>';
            return;
        }

        // Validate date range
        if (strtotime($end_date) < strtotime($start_date)) {
            echo '<div class="pm-notice pm-notice-error">Pabaigos data negali būti ankstesnė už pradžios datą!</div>';
            return;
        }

        // Handle based on booking mode
        if ($booking_mode === 'quantity') {
            $this->process_quantity_availability($teacher_id, $project_id, $start_date, $end_date, $message);
        } else {
            $this->process_time_availability($teacher_id, $project_id, $start_date, $end_date, $message);
        }
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Process time-based availability (original logic)
     */
    private function process_time_availability($teacher_id, $project_id, $start_date, $end_date, $message) {
        global $wpdb;

        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $duration = intval($_POST['duration']);
        $buffer_time = intval($_POST['buffer_time']);
        $meeting_type = sanitize_text_field($_POST['meeting_type']);

        // Loop through each date in range
        $current_date = strtotime($start_date);
        $final_date = strtotime($end_date);
        $days_added = 0;

        while ($current_date <= $final_date) {
            $date = date('Y-m-d', $current_date);

            // Insert availability period
            $wpdb->insert($wpdb->prefix . 'pm_availability', [
                'teacher_id' => $teacher_id,
                'project_id' => $project_id,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration' => $duration,
                'buffer_time' => $buffer_time,
                'meeting_type' => $meeting_type,
                'message' => $message
            ]);

            $availability_id = $wpdb->insert_id;

            // Generate time slots for this date
            $this->generate_time_slots($availability_id, $teacher_id, $project_id, $date, $start_time, $end_time, $duration, $buffer_time, $meeting_type);

            $days_added++;
            $current_date = strtotime('+1 day', $current_date);
        }

        echo '<div class="pm-notice pm-notice-success">Prieinamumo laikotarpis pridėtas sėkmingai! Sukurta ' . $days_added . ' dienų.</div>';
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Process quantity-based availability
     */
    private function process_quantity_availability($teacher_id, $project_id, $start_date, $end_date, $message) {
        global $wpdb;

        $capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 15;
        $capacity = max(1, min(100, $capacity));

        // Loop through each date in range
        $current_date = strtotime($start_date);
        $final_date = strtotime($end_date);
        $days_added = 0;

        while ($current_date <= $final_date) {
            $date = date('Y-m-d', $current_date);

            // Insert availability period for quantity mode
            $wpdb->insert($wpdb->prefix . 'pm_availability', [
                'teacher_id' => $teacher_id,
                'project_id' => $project_id,
                'date' => $date,
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'duration' => 0,
                'buffer_time' => 0,
                'meeting_type' => 'quantity',
                'message' => $message
            ]);

            $availability_id = $wpdb->insert_id;

            // Create a single quantity slot for this date
            $wpdb->insert($wpdb->prefix . 'pm_slots', [
                'availability_id' => $availability_id,
                'teacher_id' => $teacher_id,
                'project_id' => $project_id,
                'start_time' => $date . ' 00:00:00',
                'end_time' => $date . ' 23:59:59',
                'slot_date' => $date,
                'capacity' => $capacity,
                'status' => 'available',
                'meeting_type' => 'quantity'
            ]);

            $days_added++;
            $current_date = strtotime('+1 day', $current_date);
        }

        echo '<div class="pm-notice pm-notice-success">Prieinamumas pridėtas sėkmingai! Sukurta ' . $days_added . ' dienų po ' . $capacity . ' vietų.</div>';
    }
    
    private function generate_time_slots($availability_id, $teacher_id, $project_id, $date, $start_time, $end_time, $duration, $buffer_time, $meeting_type) {
        global $wpdb;

        $current = strtotime($date . ' ' . $start_time);
        $end = strtotime($date . ' ' . $end_time);
        $slot_duration = ($duration + $buffer_time) * 60;

        while ($current < $end) {
            $slot_start = date('Y-m-d H:i:s', $current);
            $slot_end = date('Y-m-d H:i:s', $current + ($duration * 60));

            $wpdb->insert($wpdb->prefix . 'pm_slots', [
                'availability_id' => $availability_id,
                'teacher_id' => $teacher_id,
                'project_id' => $project_id,
                'start_time' => $slot_start,
                'end_time' => $slot_end,
                'status' => 'available',
                'meeting_type' => $meeting_type
            ]);

            $current += $slot_duration;
        }
    }
    
    private function show_print_list($token) {
        $teacher_id = PM_Magic_Links::validate_token($token, 'print');
        
        if (!$teacher_id) {
            wp_die(esc_html__('Link is invalid or expired.', 'parent-meetings'));
        }
        
        global $wpdb;
        
        $teacher = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_teachers WHERE id = %d",
            $teacher_id
        ));
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, s.start_time, s.end_time, c.name as class_name, p.name as project_name
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             LEFT JOIN {$wpdb->prefix}pm_classes c ON b.class_id = c.id
             LEFT JOIN {$wpdb->prefix}pm_projects p ON b.project_id = p.id
             WHERE b.teacher_id = %d AND s.start_time >= CURDATE()
             ORDER BY s.start_time",
            $teacher_id
        ));
        
        include PM_PLUGIN_DIR . 'templates/print-list.php';
        exit;
    }
    
    private function handle_cancellation($token) {
        global $wpdb;

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_bookings WHERE cancel_token = %s",
            $token
        ));

        if (!$booking) {
            wp_die(__('Cancellation link is invalid.', 'parent-meetings'));
        }

        // Check if meeting hasn't started yet
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT start_time FROM {$wpdb->prefix}pm_slots WHERE id = %d",
            $booking->slot_id
        ));

        if (strtotime($slot->start_time) <= time()) {
            wp_die(__('Meeting has already started, cannot cancel.', 'parent-meetings'));
        }

        // Update booking status
        $wpdb->update(
            $wpdb->prefix . 'pm_bookings',
            ['status' => 'cancelled'],
            ['id' => $booking->id]
        );

        // Free the slot
        $wpdb->update(
            $wpdb->prefix . 'pm_slots',
            ['status' => 'available'],
            ['id' => $booking->slot_id]
        );

        // Send notification to teacher
        PM_Emails::send_cancellation_notice($booking->id);

        echo '<html><body><div style="text-align:center; padding:50px;">
              <h2>' . esc_html__('Meeting Successfully Cancelled', 'parent-meetings') . '</h2>
              <p>' . esc_html__('The teacher has been notified about the cancellation.', 'parent-meetings') . '</p>
              </div></body></html>';
        exit;
    }

    private function show_reschedule_form($token) {
        global $wpdb;

        // Get booking by cancel token
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, t.first_name, t.last_name, s.start_time, s.end_time
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             WHERE b.cancel_token = %s",
            $token
        ));

        if (!$booking) {
            wp_die(__('Reschedule link is invalid.', 'parent-meetings'));
        }

        // Check if booking is still active
        if ($booking->status !== 'confirmed') {
            wp_die(__('This booking has already been cancelled.', 'parent-meetings'));
        }

        // Check if meeting hasn't started yet
        if (strtotime($booking->start_time) <= time()) {
            wp_die(__('Meeting has already started, cannot reschedule.', 'parent-meetings'));
        }

        // Get available slots for the same teacher (next 14 days)
        $available_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.start_time, s.end_time
             FROM {$wpdb->prefix}pm_slots s
             WHERE s.teacher_id = %d
             AND s.status = 'available'
             AND s.is_hidden = 0
             AND s.id != %d
             AND (s.meeting_type = %s OR s.meeting_type = 'both')
             AND s.start_time > NOW()
             AND s.start_time <= DATE_ADD(NOW(), INTERVAL 14 DAY)
             ORDER BY s.start_time",
            $booking->teacher_id,
            $booking->slot_id,
            $booking->meeting_type
        ));

        // Enqueue scripts for reschedule
        wp_enqueue_style('pm-frontend', PM_PLUGIN_URL . 'assets/css/frontend.css', [], PM_VERSION);
        wp_enqueue_script('pm-reschedule', PM_PLUGIN_URL . 'assets/js/reschedule.js', ['jquery'], PM_VERSION, true);
        wp_localize_script('pm-reschedule', 'pmReschedule', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pm_frontend'),
            'booking_id' => $booking->id,
            'cancel_token' => $token
        ]);

        include PM_PLUGIN_DIR . 'templates/reschedule-form.php';
        exit;
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Show edit availability page
     */
    private function show_edit_availability($token, $teacher_id, $teacher) {
        global $wpdb;

        $availability_id = intval($_GET['edit_availability']);

        // Get availability record
        $availability = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_availability WHERE id = %d AND teacher_id = %d",
            $availability_id,
            $teacher_id
        ));

        if (!$availability) {
            wp_die('Prieinamumo laikotarpis nerastas.');
        }

        // Get project name
        $project_name = $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}pm_projects WHERE id = %d",
            $availability->project_id
        ));

        // Count booked slots
        $booked_slots_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_slots
             WHERE availability_id = %d AND status = 'booked'",
            $availability_id
        ));

        // Handle update form submission
        if (isset($_POST['pm_update_availability']) && check_admin_referer('pm_edit_availability_' . $teacher_id)) {
            $this->process_edit_availability_form($teacher_id, $availability_id);
            // Reload availability after update
            $availability = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pm_availability WHERE id = %d AND teacher_id = %d",
                $availability_id,
                $teacher_id
            ));
        }

        include PM_PLUGIN_DIR . 'templates/edit-availability.php';
        exit;
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Process edit availability form
     */
    private function process_edit_availability_form($teacher_id, $availability_id) {
        global $wpdb;

        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $duration = intval($_POST['duration']);
        $buffer_time = intval($_POST['buffer_time']);
        $meeting_type = sanitize_text_field($_POST['meeting_type']);
        $message = wp_kses_post($_POST['message']);

        // Validate time range
        if (strtotime($end_time) <= strtotime($start_time)) {
            echo '<div class="pm-notice pm-notice-error">Pabaigos laikas turi būti vėlesnis už pradžios laiką!</div>';
            return;
        }

        // Get availability record to preserve date and project
        $availability = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_availability WHERE id = %d AND teacher_id = %d",
            $availability_id,
            $teacher_id
        ));

        if (!$availability) {
            echo '<div class="pm-notice pm-notice-error">Prieinamumo laikotarpis nerastas!</div>';
            return;
        }

        // Update availability record
        $wpdb->update(
            $wpdb->prefix . 'pm_availability',
            [
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration' => $duration,
                'buffer_time' => $buffer_time,
                'meeting_type' => $meeting_type,
                'message' => $message
            ],
            [
                'id' => $availability_id,
                'teacher_id' => $teacher_id
            ]
        );

        // Delete only unbooked slots
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}pm_slots
             WHERE availability_id = %d AND status = 'available'",
            $availability_id
        ));

        // Regenerate time slots with new settings (preserving booked slots)
        $this->generate_time_slots(
            $availability_id,
            $teacher_id,
            $availability->project_id,
            $availability->date,
            $start_time,
            $end_time,
            $duration,
            $buffer_time,
            $meeting_type
        );

        echo '<div class="pm-notice pm-notice-success">Prieinamumas sėkmingai atnaujintas! Rezervuoti laikai liko nepakeisti.</div>';
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Process delete availability
     */
    private function process_delete_availability($teacher_id) {
        global $wpdb;

        $availability_id = intval($_POST['availability_id']);

        // Verify ownership
        $availability = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_availability WHERE id = %d AND teacher_id = %d",
            $availability_id,
            $teacher_id
        ));

        if (!$availability) {
            echo '<div class="pm-notice pm-notice-error">Prieinamumo laikotarpis nerastas!</div>';
            return;
        }

        // Check for bookings
        $booked_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_slots
             WHERE availability_id = %d AND status = 'booked'",
            $availability_id
        ));

        if ($booked_count > 0) {
            echo '<div class="pm-notice pm-notice-error">Negalima ištrinti prieinamumo laikotarpio, kuriame yra ' . absint($booked_count) . ' rezervacijų!</div>';
            return;
        }

        // Delete all slots
        $wpdb->delete(
            $wpdb->prefix . 'pm_slots',
            ['availability_id' => $availability_id]
        );

        // Delete availability record
        $wpdb->delete(
            $wpdb->prefix . 'pm_availability',
            ['id' => $availability_id, 'teacher_id' => $teacher_id]
        );

        echo '<div class="pm-notice pm-notice-success">Prieinamumo laikotarpis sėkmingai ištrintas!</div>';
    }
}
