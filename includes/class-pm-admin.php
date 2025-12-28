<?php

class PM_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_init', [$this, 'handle_actions'], 5); // Priority 5 to run early
    }
    
    public function handle_actions() {
        // Handle actions early before any output
        if (!isset($_GET['page'])) {
            return;
        }

        // Verify user has admin capabilities for all actions
        if (!current_user_can('manage_options')) {
            return;
        }

        $page = sanitize_text_field(wp_unslash($_GET['page']));
        
        // Projects page actions
        if ($page === 'pm-projects') {
            // Add project
            if (isset($_POST['pm_add_project']) && check_admin_referer('pm_project_action')) {
                $form_config = [];
                if (isset($_POST['form_fields'])) {
                    foreach ($_POST['form_fields'] as $field => $settings) {
                        $form_config[$field] = [
                            'enabled' => isset($settings['enabled']),
                            'required' => isset($settings['required']),
                            'label' => sanitize_text_field($settings['label'])
                        ];
                    }
                }

                $project_data = [
                    'name' => sanitize_text_field($_POST['name']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'form_fields_config' => json_encode($form_config),
                    'show_class_selection' => isset($_POST['show_class_selection']) ? 1 : 0,
                    'booking_mode' => isset($_POST['booking_mode']) && $_POST['booking_mode'] === 'quantity' ? 'quantity' : 'time',
                    'daily_capacity' => isset($_POST['daily_capacity']) ? intval($_POST['daily_capacity']) : 15,
                    'secondary_email' => isset($_POST['secondary_email']) ? sanitize_email($_POST['secondary_email']) : '',
                    'secondary_email_enabled' => isset($_POST['secondary_email_enabled']) ? 1 : 0
                ];

                $project_id = PM_Projects::create($project_data);

                if ($project_id) {
                    // Assign teachers
                    if (isset($_POST['teacher_ids'])) {
                        PM_Projects::assign_teachers($project_id, array_map('intval', $_POST['teacher_ids']));
                    }

                    // Assign classes
                    if (isset($_POST['class_ids'])) {
                        PM_Projects::assign_classes($project_id, array_map('intval', $_POST['class_ids']));
                    }

                    wp_redirect(admin_url('admin.php?page=pm-projects&added=1'));
                    exit;
                } else {
                    wp_redirect(admin_url('admin.php?page=pm-projects&error=create_failed'));
                    exit;
                }
            }

            // Edit project
            if (isset($_POST['pm_edit_project']) && check_admin_referer('pm_edit_project_' . $_POST['project_id'])) {
                $project_id = intval($_POST['project_id']);

                $form_config = [];
                if (isset($_POST['form_fields'])) {
                    foreach ($_POST['form_fields'] as $field => $settings) {
                        $form_config[$field] = [
                            'enabled' => isset($settings['enabled']),
                            'required' => isset($settings['required']),
                            'label' => sanitize_text_field($settings['label'])
                        ];
                    }
                }

                $project_data = [
                    'name' => sanitize_text_field($_POST['name']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'form_fields_config' => json_encode($form_config),
                    'show_class_selection' => isset($_POST['show_class_selection']) ? 1 : 0,
                    'booking_mode' => isset($_POST['booking_mode']) && $_POST['booking_mode'] === 'quantity' ? 'quantity' : 'time',
                    'daily_capacity' => isset($_POST['daily_capacity']) ? intval($_POST['daily_capacity']) : 15,
                    'secondary_email' => isset($_POST['secondary_email']) ? sanitize_email($_POST['secondary_email']) : '',
                    'secondary_email_enabled' => isset($_POST['secondary_email_enabled']) ? 1 : 0
                ];

                PM_Projects::update($project_id, $project_data);

                // Update teacher assignments
                if (isset($_POST['teacher_ids'])) {
                    PM_Projects::assign_teachers($project_id, array_map('intval', $_POST['teacher_ids']));
                } else {
                    PM_Projects::assign_teachers($project_id, []);
                }

                // Update class assignments
                if (isset($_POST['class_ids'])) {
                    PM_Projects::assign_classes($project_id, array_map('intval', $_POST['class_ids']));
                } else {
                    PM_Projects::assign_classes($project_id, []);
                }

                wp_redirect(admin_url('admin.php?page=pm-projects&updated=1'));
                exit;
            }

            // Delete project (POST only for CSRF protection)
            if (isset($_POST['pm_delete_project'])) {
                $project_id = intval($_POST['pm_delete_project']);
                check_admin_referer('pm_delete_project_' . $project_id);
                $result = PM_Projects::delete($project_id);
                if (is_wp_error($result)) {
                    wp_redirect(admin_url('admin.php?page=pm-projects&error=' . $result->get_error_code()));
                } else {
                    wp_redirect(admin_url('admin.php?page=pm-projects&deleted=1'));
                }
                exit;
            }
        }

        // Classes page actions
        if ($page === 'parent-meetings') {
            if (isset($_GET['create_demo']) && check_admin_referer('pm_create_demo')) {
                PM_Database::create_demo_data();
                wp_redirect(admin_url('admin.php?page=parent-meetings&demo_created=1'));
                exit;
            }
            
            if (isset($_POST['pm_add_class']) && check_admin_referer('pm_class_action')) {
                global $wpdb;
                $name = sanitize_text_field($_POST['class_name']);
                if (!empty($name)) {
                    $wpdb->insert($wpdb->prefix . 'pm_classes', ['name' => $name]);
                    wp_redirect(admin_url('admin.php?page=parent-meetings&class_added=1'));
                    exit;
                }
            }
            
            // Delete class (POST only for CSRF protection)
            if (isset($_POST['pm_delete_class'])) {
                global $wpdb;
                $class_id = intval($_POST['pm_delete_class']);
                check_admin_referer('pm_delete_class_' . $class_id);
                $wpdb->delete($wpdb->prefix . 'pm_classes', ['id' => $class_id]);
                wp_redirect(admin_url('admin.php?page=parent-meetings&class_deleted=1'));
                exit;
            }
        }
        
        // Teachers page actions
        if ($page === 'pm-teachers') {
            if (isset($_POST['pm_add_teacher']) && check_admin_referer('pm_teacher_action')) {
                global $wpdb;

                $email = sanitize_email($_POST['email']);

                // Check for duplicate email
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}pm_teachers WHERE email = %s",
                    $email
                ));

                if ($existing > 0) {
                    wp_redirect(admin_url('admin.php?page=pm-teachers&error=duplicate_email'));
                    exit;
                }

                // Validate email format
                if (empty($email) || !is_email($email)) {
                    wp_redirect(admin_url('admin.php?page=pm-teachers&error=invalid_email'));
                    exit;
                }

                $data = [
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'email' => $email,
                    'phone' => sanitize_text_field($_POST['phone']),
                    'class_ids' => isset($_POST['class_ids']) ? json_encode(array_map('intval', $_POST['class_ids'])) : '[]',
                    'meeting_types' => isset($_POST['meeting_types']) ? json_encode(array_map('sanitize_text_field', $_POST['meeting_types'])) : '[]',
                    'default_duration' => intval($_POST['default_duration']),
                    'buffer_time' => intval($_POST['buffer_time'])
                ];

                $result = $wpdb->insert($wpdb->prefix . 'pm_teachers', $data);

                if ($result === false) {
                    wp_redirect(admin_url('admin.php?page=pm-teachers&error=database'));
                    exit;
                }

                $teacher_id = $wpdb->insert_id;

                // Add project assignments if provided
                if (isset($_POST['project_ids']) && is_array($_POST['project_ids'])) {
                    $project_ids = array_map('intval', $_POST['project_ids']);

                    foreach ($project_ids as $project_id) {
                        if ($project_id > 0) {
                            $wpdb->insert(
                                $wpdb->prefix . 'pm_teacher_projects',
                                [
                                    'teacher_id' => $teacher_id,
                                    'project_id' => $project_id
                                ]
                            );
                        }
                    }
                }

                PM_Magic_Links::generate_tokens($teacher_id);
                PM_Emails::send_teacher_invitation($teacher_id);

                wp_redirect(admin_url('admin.php?page=pm-teachers&success=1'));
                exit;
            }

            // Edit teacher
            if (isset($_POST['pm_edit_teacher'])) {
                $teacher_id = intval($_POST['teacher_id']);
                check_admin_referer('pm_edit_teacher_' . $teacher_id);

                global $wpdb;

                $data = [
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'email' => sanitize_email($_POST['email']),
                    'phone' => sanitize_text_field($_POST['phone']),
                    'class_ids' => json_encode(array_map('intval', $_POST['class_ids'] ?? [])),
                    'meeting_types' => json_encode(array_map('sanitize_text_field', $_POST['meeting_types'] ?? [])),
                    'default_duration' => intval($_POST['default_duration']),
                    'buffer_time' => intval($_POST['buffer_time'])
                ];

                $result = $wpdb->update(
                    $wpdb->prefix . 'pm_teachers',
                    $data,
                    ['id' => $teacher_id]
                );

                // Update project assignments if provided
                if (isset($_POST['project_ids'])) {
                    $project_ids = array_map('intval', $_POST['project_ids']);

                    // Remove existing assignments
                    $wpdb->delete(
                        $wpdb->prefix . 'pm_teacher_projects',
                        ['teacher_id' => $teacher_id]
                    );

                    // Add new assignments
                    foreach ($project_ids as $project_id) {
                        $wpdb->insert(
                            $wpdb->prefix . 'pm_teacher_projects',
                            [
                                'teacher_id' => $teacher_id,
                                'project_id' => $project_id
                            ]
                        );
                    }
                } else {
                    // No projects selected - remove all assignments
                    $wpdb->delete(
                        $wpdb->prefix . 'pm_teacher_projects',
                        ['teacher_id' => $teacher_id]
                    );
                }

                if ($result === false) {
                    wp_redirect(admin_url('admin.php?page=pm-teachers&error=database'));
                    exit;
                }

                wp_redirect(admin_url('admin.php?page=pm-teachers&updated=1'));
                exit;
            }

            // Delete teacher (POST only for CSRF protection)
            if (isset($_POST['pm_delete_teacher'])) {
                global $wpdb;
                $teacher_id = intval($_POST['pm_delete_teacher']);
                check_admin_referer('pm_delete_teacher_' . $teacher_id);
                $wpdb->delete($wpdb->prefix . 'pm_teachers', ['id' => $teacher_id]);
                wp_redirect(admin_url('admin.php?page=pm-teachers&deleted=1'));
                exit;
            }
            
            if (isset($_GET['regenerate']) && check_admin_referer('pm_regenerate_' . $_GET['regenerate'])) {
                PM_Magic_Links::generate_tokens(intval($_GET['regenerate']));
                PM_Emails::send_teacher_invitation(intval($_GET['regenerate']));
                wp_redirect(admin_url('admin.php?page=pm-teachers&regenerated=1'));
                exit;
            }
        }

        // Bookings page actions
        if ($page === 'pm-bookings') {
            // Delete booking (POST only for CSRF protection)
            if (isset($_POST['pm_delete_booking'])) {
                global $wpdb;
                $booking_id = intval($_POST['pm_delete_booking']);
                check_admin_referer('pm_delete_booking_' . $booking_id);

                // Get booking and slot info
                $booking = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}pm_bookings WHERE id = %d",
                    $booking_id
                ));

                if ($booking) {
                    // Free up the slot
                    $wpdb->update(
                        $wpdb->prefix . 'pm_slots',
                        ['status' => 'available'],
                        ['id' => $booking->slot_id]
                    );

                    // Delete booking
                    $wpdb->delete(
                        $wpdb->prefix . 'pm_bookings',
                        ['id' => $booking_id]
                    );

                    wp_redirect(admin_url('admin.php?page=pm-bookings&booking_deleted=1'));
                    exit;
                }
            }

            // Cancel booking (POST only for CSRF protection)
            if (isset($_POST['pm_cancel_booking'])) {
                global $wpdb;
                $booking_id = intval($_POST['pm_cancel_booking']);
                check_admin_referer('pm_cancel_booking_' . $booking_id);

                $booking = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}pm_bookings WHERE id = %d",
                    $booking_id
                ));

                if ($booking) {
                    // Free up the slot
                    $wpdb->update(
                        $wpdb->prefix . 'pm_slots',
                        ['status' => 'available'],
                        ['id' => $booking->slot_id]
                    );

                    // Mark booking as cancelled
                    $wpdb->update(
                        $wpdb->prefix . 'pm_bookings',
                        ['status' => 'cancelled'],
                        ['id' => $booking_id]
                    );

                    wp_redirect(admin_url('admin.php?page=pm-bookings&booking_cancelled=1'));
                    exit;
                }
            }
        }
    }

    public function add_menu() {
        add_menu_page(
            'Tėvų susitikimai',
            'Susitikimai',
            'manage_options',
            'parent-meetings',
            [$this, 'classes_page'],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'parent-meetings',
            'Projektai',
            'Projektai',
            'manage_options',
            'pm-projects',
            [$this, 'projects_page']
        );

        add_submenu_page(
            'parent-meetings',
            'Klasės',
            'Klasės',
            'manage_options',
            'parent-meetings',
            [$this, 'classes_page']
        );

        add_submenu_page(
            'parent-meetings',
            'Mokytojai',
            'Mokytojai',
            'manage_options',
            'pm-teachers',
            [$this, 'teachers_page']
        );

        add_submenu_page(
            'parent-meetings',
            'Rezervacijos',
            'Rezervacijos',
            'manage_options',
            'pm-bookings',
            [$this, 'bookings_page']
        );

        add_submenu_page(
            'parent-meetings',
            'Analitika',
            'Analitika',
            'manage_options',
            'pm-analytics',
            [$this, 'analytics_page']
        );
    }
    
    public function enqueue_scripts($hook) {
        // Check if we're on any Parent Meetings page
        $is_pm_page = (strpos($hook, 'parent-meetings') !== false || strpos($hook, 'pm-') !== false);

        if (!$is_pm_page) {
            return;
        }

        // Force load dashicons
        wp_enqueue_style('dashicons');

        // Load admin styles
        wp_enqueue_style('pm-admin', PM_PLUGIN_URL . 'assets/css/admin.css', ['dashicons'], PM_VERSION);
        wp_enqueue_script('pm-admin', PM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], PM_VERSION, true);

        wp_localize_script('pm-admin', 'pmAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pm_admin'),
            'hook' => $hook // Debug: see what hook is
        ]);

        // Analytics page specific assets (check multiple hook formats)
        if ($hook === 'meetings_page_pm-analytics' || strpos($hook, 'pm-analytics') !== false) {
            // ApexCharts library
            wp_enqueue_script('apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js', [], '3.45.1', true);

            // Analytics specific CSS and JS
            wp_enqueue_style('pm-analytics', PM_PLUGIN_URL . 'assets/css/analytics.css', [], PM_VERSION);
            wp_enqueue_script('pm-analytics', PM_PLUGIN_URL . 'assets/js/analytics.js', ['jquery', 'apexcharts'], PM_VERSION, true);

            wp_localize_script('pm-analytics', 'pmAnalytics', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pm_analytics_nonce'),
                'exportUrl' => admin_url('admin-ajax.php?action=pm_export_analytics_csv&nonce=' . wp_create_nonce('pm_analytics_nonce'))
            ]);
        }
    }

    public function projects_page() {
        global $wpdb;

        // Show messages
        if (isset($_GET['added'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Project added successfully.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Project updated successfully.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Project deleted.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['error'])) {
            $error = sanitize_text_field($_GET['error']);
            if ($error === 'has_bookings') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Cannot delete project with existing bookings.', 'parent-meetings') . '</p></div>';
            }
        }

        $projects = PM_Projects::get_all();
        $all_teachers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_teachers ORDER BY last_name, first_name");
        $all_classes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_classes ORDER BY name");

        // If editing, get project data
        $edit_project = null;
        $edit_project_teachers = [];
        $edit_project_classes = [];
        if (isset($_GET['edit'])) {
            $edit_project = PM_Projects::get(intval($_GET['edit']));
            if ($edit_project) {
                $edit_project_teachers = PM_Projects::get_teachers($edit_project->id);
                $edit_project_classes = PM_Projects::get_classes($edit_project->id);
            }
        }

        include PM_PLUGIN_DIR . 'templates/admin-projects.php';
    }

    public function classes_page() {
        global $wpdb;

        // Show messages
        if (isset($_GET['demo_created'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Demo data created successfully! Check teachers and bookings.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['class_added'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Class added successfully.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['class_deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Class deleted.', 'parent-meetings') . '</p></div>';
        }

        $classes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_classes ORDER BY name");

        include PM_PLUGIN_DIR . 'templates/admin-classes.php';
    }
    
    public function teachers_page() {
        global $wpdb;

        // Show messages
        if (isset($_GET['success'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Teacher added and invitation sent.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Teacher deleted.', 'parent-meetings') . '</p></div>';
        }
        if (isset($_GET['regenerated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Links refreshed and sent.', 'parent-meetings') . '</p></div>';
        }

        // Show error messages
        if (isset($_GET['error'])) {
            $error = sanitize_text_field($_GET['error']);
            $error_messages = [
                'duplicate_email' => __('A teacher with this email already exists. Please use a different email address.', 'parent-meetings'),
                'invalid_email' => __('Invalid email address. Please enter a valid email.', 'parent-meetings'),
                'database' => __('Database error occurred. Please try again.', 'parent-meetings')
            ];

            if (isset($error_messages[$error])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_messages[$error]) . '</p></div>';
            }
        }

        $teachers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_teachers ORDER BY last_name, first_name");
        $classes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_classes ORDER BY name");

        include PM_PLUGIN_DIR . 'templates/admin-teachers.php';
    }
    
    /**
     * Author: Tobalt — https://tobalt.lt
     */
    public function bookings_page() {
        global $wpdb;

        // Get data for filters
        $all_projects = PM_Projects::get_all();
        $all_teachers = $wpdb->get_results(
            "SELECT id, first_name, last_name FROM {$wpdb->prefix}pm_teachers ORDER BY last_name, first_name"
        );

        // Get filter parameters
        $filter_project_id = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;
        $filter_teacher_id = isset($_GET['teacher_filter']) ? intval($_GET['teacher_filter']) : 0;
        $filter_status = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $filter_attendance = isset($_GET['attendance_filter']) ? sanitize_text_field($_GET['attendance_filter']) : '';

        // Build dynamic query
        $where_clauses = [];
        $params = [];

        if ($filter_project_id > 0) {
            $where_clauses[] = 'b.project_id = %d';
            $params[] = $filter_project_id;
        }

        if ($filter_teacher_id > 0) {
            $where_clauses[] = 'b.teacher_id = %d';
            $params[] = $filter_teacher_id;
        }

        if ($filter_status !== '' && in_array($filter_status, ['confirmed', 'cancelled', 'pending'])) {
            $where_clauses[] = 'b.status = %s';
            $params[] = $filter_status;
        }

        if ($filter_attendance !== '' && in_array($filter_attendance, ['pending', 'attended', 'missed'])) {
            $where_clauses[] = 'b.attendance = %s';
            $params[] = $filter_attendance;
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        $query = "
            SELECT b.*, t.first_name, t.last_name, s.start_time, s.end_time, c.name as class_name, p.name as project_name
            FROM {$wpdb->prefix}pm_bookings b
            JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
            JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
            LEFT JOIN {$wpdb->prefix}pm_classes c ON b.class_id = c.id
            LEFT JOIN {$wpdb->prefix}pm_projects p ON b.project_id = p.id
            {$where_sql}
            ORDER BY s.start_time DESC
        ";

        if (!empty($params)) {
            $bookings = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $bookings = $wpdb->get_results($query);
        }

        include PM_PLUGIN_DIR . 'templates/admin-bookings.php';
    }

    public function analytics_page() {
        global $wpdb;

        // Get all projects for filter
        $all_projects = PM_Projects::get_all();

        include PM_PLUGIN_DIR . 'templates/admin-analytics.php';
    }
}
