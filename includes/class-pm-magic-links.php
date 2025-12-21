<?php

class PM_Magic_Links {
    
    public static function generate_tokens($teacher_id) {
        global $wpdb;
        
        // Delete old tokens
        $wpdb->delete($wpdb->prefix . 'pm_tokens', ['teacher_id' => $teacher_id]);
        
        $expires_at = date('Y-m-d H:i:s', strtotime('+90 days'));
        
        // Manage availability token
        $manage_token = self::generate_token();
        $wpdb->insert($wpdb->prefix . 'pm_tokens', [
            'teacher_id' => $teacher_id,
            'token' => $manage_token,
            'type' => 'manage',
            'expires_at' => $expires_at
        ]);
        
        // Print list token
        $print_token = self::generate_token();
        $wpdb->insert($wpdb->prefix . 'pm_tokens', [
            'teacher_id' => $teacher_id,
            'token' => $print_token,
            'type' => 'print',
            'expires_at' => $expires_at
        ]);
        
        return [
            'manage' => $manage_token,
            'print' => $print_token
        ];
    }
    
    public static function generate_token() {
        return bin2hex(random_bytes(32));
    }
    
    public static function validate_token($token, $type = null) {
        global $wpdb;
        
        $query = "SELECT teacher_id FROM {$wpdb->prefix}pm_tokens 
                  WHERE token = %s AND expires_at > NOW()";
        $params = [$token];
        
        if ($type) {
            $query .= " AND type = %s";
            $params[] = $type;
        }
        
        $teacher_id = $wpdb->get_var($wpdb->prepare($query, $params));
        
        return $teacher_id ? intval($teacher_id) : false;
    }
    
    public static function get_urls($teacher_id) {
        global $wpdb;
        
        $tokens = $wpdb->get_results($wpdb->prepare(
            "SELECT token, type FROM {$wpdb->prefix}pm_tokens WHERE teacher_id = %d",
            $teacher_id
        ));
        
        $urls = [];
        foreach ($tokens as $token) {
            $urls[$token->type] = home_url('/?pm_action=' . $token->type . '&token=' . $token->token);
        }
        
        return $urls;
    }
    
    public static function check_expiring_tokens() {
        global $wpdb;
        
        // Get tokens expiring in 7 days
        $expiring = $wpdb->get_results(
            "SELECT DISTINCT teacher_id FROM {$wpdb->prefix}pm_tokens 
             WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
        );
        
        foreach ($expiring as $row) {
            // Auto-regenerate and send email
            self::generate_tokens($row->teacher_id);
            PM_Emails::send_teacher_invitation($row->teacher_id);
        }
    }
}

// Cron for auto-rotation
add_action('pm_check_tokens', ['PM_Magic_Links', 'check_expiring_tokens']);
if (!wp_next_scheduled('pm_check_tokens')) {
    wp_schedule_event(time(), 'daily', 'pm_check_tokens');
}
