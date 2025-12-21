<?php
/**
 * Analytics Class
 *
 * Author: Tobalt — https://tobalt.lt
 */

class PM_Analytics {

    public function __construct() {
        add_action('wp_ajax_pm_get_analytics_data', [$this, 'ajax_get_analytics_data']);
        add_action('wp_ajax_pm_export_analytics_csv', [$this, 'ajax_export_analytics_csv']);
    }

    /**
     * Get booking statistics for KPI cards
     */
    public static function get_booking_statistics($date_from, $date_to, $project_id = 0) {
        global $wpdb;

        $where = ["b.created_at >= %s", "b.created_at <= %s"];
        $params = [$date_from, $date_to];

        if ($project_id > 0) {
            $where[] = "b.project_id = %d";
            $params[] = $project_id;
        }

        $where_clause = implode(' AND ', $where);

        // Total bookings
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings b WHERE {$where_clause}",
            ...$params
        ));

        // Status breakdown
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$wpdb->prefix}pm_bookings b
             WHERE {$where_clause}
             GROUP BY status",
            ...$params
        ), OBJECT_K);

        // Attendance breakdown
        $attendance_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT attendance, COUNT(*) as count
             FROM {$wpdb->prefix}pm_bookings b
             WHERE {$where_clause} AND status = 'confirmed'
             GROUP BY attendance",
            ...$params
        ), OBJECT_K);

        // Slot utilization
        $slot_params = $params;
        $slot_where = ["s.created_at >= %s", "s.created_at <= %s"];
        if ($project_id > 0) {
            $slot_where[] = "s.project_id = %d";
        }
        $slot_where_clause = implode(' AND ', $slot_where);

        $total_slots = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_slots s
             WHERE {$slot_where_clause} AND s.is_hidden = 0",
            ...$slot_params
        ));

        $booked_slots = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pm_slots s
             WHERE {$slot_where_clause} AND s.status = 'booked' AND s.is_hidden = 0",
            ...$slot_params
        ));

        $confirmed = isset($status_counts['confirmed']) ? intval($status_counts['confirmed']->count) : 0;
        $cancelled = isset($status_counts['cancelled']) ? intval($status_counts['cancelled']->count) : 0;
        $pending = isset($status_counts['pending']) ? intval($status_counts['pending']->count) : 0;

        $attended = isset($attendance_counts['attended']) ? intval($attendance_counts['attended']->count) : 0;
        $missed = isset($attendance_counts['missed']) ? intval($attendance_counts['missed']->count) : 0;
        $attendance_pending = isset($attendance_counts['pending']) ? intval($attendance_counts['pending']->count) : 0;

        return [
            'total_bookings' => intval($total),
            'confirmed' => $confirmed,
            'cancelled' => $cancelled,
            'pending' => $pending,
            'attended' => $attended,
            'missed' => $missed,
            'attendance_pending' => $attendance_pending,
            'total_slots' => intval($total_slots),
            'booked_slots' => intval($booked_slots),
            'booking_rate' => $total_slots > 0 ? round(($booked_slots / $total_slots) * 100, 2) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
            'attendance_rate' => ($attended + $missed) > 0 ? round(($attended / ($attended + $missed)) * 100, 2) : 0
        ];
    }

    /**
     * Get trend data for charts (daily, weekly, monthly)
     */
    public static function get_trend_data($date_from, $date_to, $granularity = 'daily', $project_id = 0) {
        global $wpdb;

        $where = ["b.created_at >= %s", "b.created_at <= %s"];
        $params = [$date_from, $date_to];

        if ($project_id > 0) {
            $where[] = "b.project_id = %d";
            $params[] = $project_id;
        }

        $where_clause = implode(' AND ', $where);

        // Date grouping based on granularity
        switch($granularity) {
            case 'weekly':
                $date_format = '%Y-%u';
                break;
            case 'monthly':
                $date_format = '%Y-%m';
                break;
            default:
                $date_format = '%Y-%m-%d';
                break;
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(b.created_at, '{$date_format}') as period,
                COUNT(*) as total,
                SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN b.attendance = 'attended' THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN b.attendance = 'missed' THEN 1 ELSE 0 END) as missed
             FROM {$wpdb->prefix}pm_bookings b
             WHERE {$where_clause}
             GROUP BY period
             ORDER BY period ASC",
            ...$params
        ));

        return $results;
    }

    /**
     * Get teacher utilization metrics
     */
    public static function get_teacher_utilization($date_from, $date_to, $project_id = 0) {
        global $wpdb;

        $where = ["s.start_time >= %s", "s.start_time <= %s"];
        $params = [$date_from, $date_to];

        if ($project_id > 0) {
            $where[] = "s.project_id = %d";
            $params[] = $project_id;
        }

        $where_clause = implode(' AND ', $where);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                t.id,
                CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                COUNT(s.id) as total_slots,
                SUM(CASE WHEN s.status = 'booked' THEN 1 ELSE 0 END) as booked_slots,
                SUM(CASE WHEN s.status = 'available' THEN 1 ELSE 0 END) as available_slots,
                ROUND((SUM(CASE WHEN s.status = 'booked' THEN 1 ELSE 0 END) / COUNT(s.id)) * 100, 2) as utilization_rate
             FROM {$wpdb->prefix}pm_teachers t
             LEFT JOIN {$wpdb->prefix}pm_slots s ON t.id = s.teacher_id
             WHERE {$where_clause} AND s.is_hidden = 0
             GROUP BY t.id, teacher_name
             HAVING total_slots > 0
             ORDER BY utilization_rate DESC",
            ...$params
        ));

        return $results;
    }

    /**
     * Get project comparison data
     */
    public static function get_project_comparison($date_from, $date_to) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.id,
                p.name as project_name,
                COUNT(b.id) as total_bookings,
                SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN b.attendance = 'attended' THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN b.attendance = 'missed' THEN 1 ELSE 0 END) as missed,
                ROUND((SUM(CASE WHEN b.attendance = 'attended' THEN 1 ELSE 0 END) /
                       NULLIF(SUM(CASE WHEN b.attendance IN ('attended', 'missed') THEN 1 ELSE 0 END), 0)) * 100, 2) as attendance_rate
             FROM {$wpdb->prefix}pm_projects p
             LEFT JOIN {$wpdb->prefix}pm_bookings b ON p.id = b.project_id
                 AND b.created_at >= %s AND b.created_at <= %s
             GROUP BY p.id, p.name
             ORDER BY total_bookings DESC",
            $date_from,
            $date_to
        ));

        return $results;
    }

    /**
     * Get peak time analysis (day of week + hour)
     */
    public static function get_peak_times($date_from, $date_to, $project_id = 0) {
        global $wpdb;

        $where = ["s.start_time >= %s", "s.start_time <= %s", "s.status = 'booked'"];
        $params = [$date_from, $date_to];

        if ($project_id > 0) {
            $where[] = "s.project_id = %d";
            $params[] = $project_id;
        }

        $where_clause = implode(' AND ', $where);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DAYOFWEEK(s.start_time) as day_of_week,
                HOUR(s.start_time) as hour_of_day,
                COUNT(*) as booking_count
             FROM {$wpdb->prefix}pm_slots s
             WHERE {$where_clause}
             GROUP BY day_of_week, hour_of_day
             ORDER BY day_of_week, hour_of_day",
            ...$params
        ));

        return $results;
    }

    /**
     * Get detailed bookings list with filters
     */
    public static function get_detailed_bookings($date_from, $date_to, $project_id = 0, $limit = 50, $offset = 0) {
        global $wpdb;

        $where = ["b.created_at >= %s", "b.created_at <= %s"];
        $params = [$date_from, $date_to];

        if ($project_id > 0) {
            $where[] = "b.project_id = %d";
            $params[] = $project_id;
        }

        $where_clause = implode(' AND ', $where);

        $params[] = intval($limit);
        $params[] = intval($offset);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                b.id,
                b.created_at,
                b.parent_name,
                b.parent_email,
                b.student_name,
                b.status,
                b.attendance,
                b.meeting_type,
                CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                c.name as class_name,
                p.name as project_name,
                s.start_time,
                s.end_time
             FROM {$wpdb->prefix}pm_bookings b
             JOIN {$wpdb->prefix}pm_teachers t ON b.teacher_id = t.id
             JOIN {$wpdb->prefix}pm_slots s ON b.slot_id = s.id
             LEFT JOIN {$wpdb->prefix}pm_classes c ON b.class_id = c.id
             LEFT JOIN {$wpdb->prefix}pm_projects p ON b.project_id = p.id
             WHERE {$where_clause}
             ORDER BY b.created_at DESC
             LIMIT %d OFFSET %d",
            ...$params
        ));

        return $results;
    }

    /**
     * Get comparative data for two time periods
     */
    public static function get_comparative_data($period1_start, $period1_end, $period2_start, $period2_end, $project_id = 0) {
        $period1_stats = self::get_booking_statistics($period1_start, $period1_end, $project_id);
        $period2_stats = self::get_booking_statistics($period2_start, $period2_end, $project_id);

        return [
            'period1' => $period1_stats,
            'period2' => $period2_stats,
            'changes' => [
                'total_bookings' => self::calculate_percentage_change($period1_stats['total_bookings'], $period2_stats['total_bookings']),
                'booking_rate' => self::calculate_percentage_change($period1_stats['booking_rate'], $period2_stats['booking_rate']),
                'cancellation_rate' => self::calculate_percentage_change($period1_stats['cancellation_rate'], $period2_stats['cancellation_rate']),
                'attendance_rate' => self::calculate_percentage_change($period1_stats['attendance_rate'], $period2_stats['attendance_rate'])
            ]
        ];
    }

    /**
     * Calculate percentage change between two values
     */
    private static function calculate_percentage_change($old_value, $new_value) {
        if ($old_value == 0) {
            return $new_value > 0 ? 100 : 0;
        }
        return round((($new_value - $old_value) / $old_value) * 100, 2);
    }

    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('pm_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $data_type = sanitize_text_field($_POST['data_type'] ?? 'statistics');
        $date_from = sanitize_text_field($_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
        $date_to = sanitize_text_field($_POST['date_to'] ?? date('Y-m-d'));
        $project_id = intval($_POST['project_id'] ?? 0);
        $granularity = sanitize_text_field($_POST['granularity'] ?? 'daily');

        switch($data_type) {
            case 'statistics':
                $result = self::get_booking_statistics($date_from, $date_to, $project_id);
                break;
            case 'trends':
                $result = self::get_trend_data($date_from, $date_to, $granularity, $project_id);
                break;
            case 'teacher_utilization':
                $result = self::get_teacher_utilization($date_from, $date_to, $project_id);
                break;
            case 'project_comparison':
                $result = self::get_project_comparison($date_from, $date_to);
                break;
            case 'peak_times':
                $result = self::get_peak_times($date_from, $date_to, $project_id);
                break;
            case 'detailed_bookings':
                $result = self::get_detailed_bookings($date_from, $date_to, $project_id, intval($_POST['limit'] ?? 50), intval($_POST['offset'] ?? 0));
                break;
            default:
                $result = ['error' => 'Invalid data type'];
                break;
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX handler for CSV export
     */
    public function ajax_export_analytics_csv() {
        check_ajax_referer('pm_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $report_type = sanitize_text_field($_GET['report_type'] ?? 'bookings');
        $date_from = sanitize_text_field($_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
        $date_to = sanitize_text_field($_GET['date_to'] ?? date('Y-m-d'));
        $project_id = intval($_GET['project_id'] ?? 0);

        $filename = sprintf('pm-analytics-%s-%s-%s.csv', $report_type, $date_from, $date_to);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        switch ($report_type) {
            case 'bookings':
                $this->export_bookings_csv($output, $date_from, $date_to, $project_id);
                break;
            case 'teacher_utilization':
                $this->export_teacher_utilization_csv($output, $date_from, $date_to, $project_id);
                break;
            case 'project_comparison':
                $this->export_project_comparison_csv($output, $date_from, $date_to);
                break;
        }

        fclose($output);
        exit;
    }

    /**
     * Export bookings to CSV
     */
    private function export_bookings_csv($output, $date_from, $date_to, $project_id) {
        fputcsv($output, ['ID', 'Sukurta', 'Mokytojas', 'Tėvas', 'El. paštas', 'Mokinys', 'Klasė', 'Projektas', 'Susitikimo laikas', 'Tipas', 'Statusas', 'Lankomumas']);

        $bookings = self::get_detailed_bookings($date_from, $date_to, $project_id, 10000, 0);

        foreach ($bookings as $booking) {
            fputcsv($output, [
                $booking->id,
                $booking->created_at,
                $booking->teacher_name,
                $booking->parent_name,
                $booking->parent_email,
                $booking->student_name,
                $booking->class_name ?: '-',
                $booking->project_name,
                $booking->start_time,
                $booking->meeting_type,
                $booking->status,
                $booking->attendance
            ]);
        }
    }

    /**
     * Export teacher utilization to CSV
     */
    private function export_teacher_utilization_csv($output, $date_from, $date_to, $project_id) {
        fputcsv($output, ['Mokytojas', 'Viso laisvų vietų', 'Užimta', 'Laisva', 'Užimtumas (%)']);

        $teachers = self::get_teacher_utilization($date_from, $date_to, $project_id);

        foreach ($teachers as $teacher) {
            fputcsv($output, [
                $teacher->teacher_name,
                $teacher->total_slots,
                $teacher->booked_slots,
                $teacher->available_slots,
                $teacher->utilization_rate
            ]);
        }
    }

    /**
     * Export project comparison to CSV
     */
    private function export_project_comparison_csv($output, $date_from, $date_to) {
        fputcsv($output, ['Projektas', 'Viso rezervacijų', 'Patvirtinta', 'Atšaukta', 'Atvyko', 'Neatvyko', 'Lankomumas (%)']);

        $projects = self::get_project_comparison($date_from, $date_to);

        foreach ($projects as $project) {
            fputcsv($output, [
                $project->project_name,
                $project->total_bookings,
                $project->confirmed,
                $project->cancelled,
                $project->attended,
                $project->missed,
                $project->attendance_rate ?: '-'
            ]);
        }
    }
}
