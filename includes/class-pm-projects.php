<?php
/**
 * Project Management Class
 *
 * Author: Tobalt — https://tobalt.lt
 */

class PM_Projects {

    /**
     * Get all projects
     */
    public static function get_all() {
        global $wpdb;

        $projects = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pm_projects ORDER BY created_at DESC"
        );

        return $projects;
    }

    /**
     * Get project by ID
     */
    public static function get($project_id) {
        global $wpdb;

        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_projects WHERE id = %d",
            $project_id
        ));

        return $project;
    }

    /**
     * Create new project
     */
    public static function create($data) {
        global $wpdb;

        $defaults = [
            'name' => '',
            'description' => '',
            'form_fields_config' => self::get_default_form_config(),
            'show_class_selection' => 1,
            'booking_mode' => 'time',
            'daily_capacity' => 15,
            'secondary_email' => '',
            'secondary_email_enabled' => 0
        ];

        $data = wp_parse_args($data, $defaults);

        // Ensure form_fields_config is JSON
        if (is_array($data['form_fields_config'])) {
            $data['form_fields_config'] = json_encode($data['form_fields_config']);
        }

        // Validate booking_mode
        if (!in_array($data['booking_mode'], ['time', 'quantity'])) {
            $data['booking_mode'] = 'time';
        }

        // Ensure daily_capacity is valid
        $data['daily_capacity'] = max(1, min(100, intval($data['daily_capacity'])));

        // Only keep allowed fields to prevent wpdb errors
        $allowed_fields = ['name', 'description', 'form_fields_config', 'show_class_selection', 'booking_mode', 'daily_capacity', 'secondary_email', 'secondary_email_enabled'];
        $data = array_intersect_key($data, array_flip($allowed_fields));

        $result = $wpdb->insert(
            $wpdb->prefix . 'pm_projects',
            $data
        );

        if ($result === false) {
            error_log('PM Projects create failed: ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update project
     */
    public static function update($project_id, $data) {
        global $wpdb;

        // Ensure form_fields_config is JSON
        if (isset($data['form_fields_config']) && is_array($data['form_fields_config'])) {
            $data['form_fields_config'] = json_encode($data['form_fields_config']);
        }

        // Validate booking_mode if provided
        if (isset($data['booking_mode']) && !in_array($data['booking_mode'], ['time', 'quantity'])) {
            $data['booking_mode'] = 'time';
        }

        // Ensure daily_capacity is valid if provided
        if (isset($data['daily_capacity'])) {
            $data['daily_capacity'] = max(1, min(100, intval($data['daily_capacity'])));
        }

        // Only keep allowed fields to prevent wpdb errors
        $allowed_fields = ['name', 'description', 'form_fields_config', 'show_class_selection', 'booking_mode', 'daily_capacity', 'secondary_email', 'secondary_email_enabled'];
        $data = array_intersect_key($data, array_flip($allowed_fields));

        $result = $wpdb->update(
            $wpdb->prefix . 'pm_projects',
            $data,
            ['id' => $project_id]
        );

        return $result !== false;
    }

    /**
     * Delete project
     */
    public static function delete($project_id) {
        global $wpdb;

        // Don't allow deletion if there are bookings
        $booking_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings WHERE project_id = %d",
            $project_id
        ));

        if ($booking_count > 0) {
            return new WP_Error('has_bookings', __('Cannot delete project with existing bookings', 'parent-meetings'));
        }

        // Delete associated records
        $wpdb->delete($wpdb->prefix . 'pm_teacher_projects', ['project_id' => $project_id]);
        $wpdb->delete($wpdb->prefix . 'pm_class_projects', ['project_id' => $project_id]);
        $wpdb->delete($wpdb->prefix . 'pm_slots', ['project_id' => $project_id]);
        $wpdb->delete($wpdb->prefix . 'pm_availability', ['project_id' => $project_id]);
        $wpdb->delete($wpdb->prefix . 'pm_waiting_list', ['project_id' => $project_id]);

        // Delete project
        $result = $wpdb->delete(
            $wpdb->prefix . 'pm_projects',
            ['id' => $project_id]
        );

        return $result !== false;
    }

    /**
     * Get teachers for a project
     */
    public static function get_teachers($project_id) {
        global $wpdb;

        $teachers = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*
            FROM {$wpdb->prefix}pm_teachers t
            INNER JOIN {$wpdb->prefix}pm_teacher_projects tp ON t.id = tp.teacher_id
            WHERE tp.project_id = %d
            ORDER BY t.last_name, t.first_name",
            $project_id
        ));

        return $teachers;
    }

    /**
     * Get classes for a project
     */
    public static function get_classes($project_id) {
        global $wpdb;

        $classes = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*
            FROM {$wpdb->prefix}pm_classes c
            INNER JOIN {$wpdb->prefix}pm_class_projects cp ON c.id = cp.class_id
            WHERE cp.project_id = %d
            ORDER BY c.name",
            $project_id
        ));

        return $classes;
    }

    /**
     * Assign teachers to a project
     */
    public static function assign_teachers($project_id, $teacher_ids) {
        global $wpdb;

        // First, remove all existing assignments
        $wpdb->delete(
            $wpdb->prefix . 'pm_teacher_projects',
            ['project_id' => $project_id]
        );

        // Add new assignments
        if (!empty($teacher_ids)) {
            foreach ($teacher_ids as $teacher_id) {
                $wpdb->insert($wpdb->prefix . 'pm_teacher_projects', [
                    'teacher_id' => $teacher_id,
                    'project_id' => $project_id
                ]);
            }
        }

        return true;
    }

    /**
     * Assign classes to a project
     */
    public static function assign_classes($project_id, $class_ids) {
        global $wpdb;

        // First, remove all existing assignments
        $wpdb->delete(
            $wpdb->prefix . 'pm_class_projects',
            ['project_id' => $project_id]
        );

        // Add new assignments
        if (!empty($class_ids)) {
            foreach ($class_ids as $class_id) {
                $wpdb->insert($wpdb->prefix . 'pm_class_projects', [
                    'class_id' => $class_id,
                    'project_id' => $project_id
                ]);
            }
        }

        return true;
    }

    /**
     * Get projects for a teacher
     */
    public static function get_teacher_projects($teacher_id) {
        global $wpdb;

        $projects = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*
            FROM {$wpdb->prefix}pm_projects p
            INNER JOIN {$wpdb->prefix}pm_teacher_projects tp ON p.id = tp.project_id
            WHERE tp.teacher_id = %d
            ORDER BY p.name",
            $teacher_id
        ));

        return $projects;
    }

    /**
     * Get projects for a class
     */
    public static function get_class_projects($class_id) {
        global $wpdb;

        $projects = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*
            FROM {$wpdb->prefix}pm_projects p
            INNER JOIN {$wpdb->prefix}pm_class_projects cp ON p.id = cp.project_id
            WHERE cp.class_id = %d
            ORDER BY p.name",
            $class_id
        ));

        return $projects;
    }

    /**
     * Get default form configuration
     */
    public static function get_default_form_config() {
        return json_encode([
            'parent_name' => [
                'enabled' => true,
                'required' => true,
                'label' => 'Tėvų vardas ir pavardė'
            ],
            'student_name' => [
                'enabled' => true,
                'required' => true,
                'label' => 'Mokinio vardas ir pavardė'
            ],
            'parent_email' => [
                'enabled' => true,
                'required' => true,
                'label' => 'El. paštas'
            ],
            'parent_phone' => [
                'enabled' => true,
                'required' => false,
                'label' => 'Tel. nr.'
            ],
            'notes' => [
                'enabled' => true,
                'required' => false,
                'label' => 'Pastaba (tikslas, tema)'
            ]
        ]);
    }

    /**
     * Parse form config JSON
     */
    public static function parse_form_config($config) {
        if (is_string($config)) {
            return json_decode($config, true);
        }
        return $config;
    }

    /**
     * Check if teacher belongs to project
     */
    public static function teacher_in_project($teacher_id, $project_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_teacher_projects
            WHERE teacher_id = %d AND project_id = %d",
            $teacher_id,
            $project_id
        ));

        return $count > 0;
    }

    /**
     * Check if class belongs to project
     */
    public static function class_in_project($class_id, $project_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_class_projects
            WHERE class_id = %d AND project_id = %d",
            $class_id,
            $project_id
        ));

        return $count > 0;
    }
}
