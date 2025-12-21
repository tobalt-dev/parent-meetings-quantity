<?php

class PM_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Projects table
        $sql = "CREATE TABLE {$wpdb->prefix}pm_projects (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            form_fields_config text,
            show_class_selection tinyint(1) DEFAULT 1,
            booking_mode varchar(20) DEFAULT 'time',
            daily_capacity int DEFAULT 15,
            secondary_email varchar(255),
            secondary_email_enabled tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Classes table
        $sql = "CREATE TABLE {$wpdb->prefix}pm_classes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Teachers table
        $sql = "CREATE TABLE {$wpdb->prefix}pm_teachers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            class_ids text,
            meeting_types text,
            default_duration int DEFAULT 15,
            buffer_time int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        dbDelta($sql);

        // Teacher-Project junction table
        $sql = "CREATE TABLE {$wpdb->prefix}pm_teacher_projects (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY teacher_project (teacher_id, project_id),
            KEY teacher_id (teacher_id),
            KEY project_id (project_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Class-Project junction table
        $sql = "CREATE TABLE {$wpdb->prefix}pm_class_projects (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            class_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY class_project (class_id, project_id),
            KEY class_id (class_id),
            KEY project_id (project_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Availability periods
        $sql = "CREATE TABLE {$wpdb->prefix}pm_availability (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned DEFAULT 0,
            date date NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            duration int DEFAULT 15,
            buffer_time int DEFAULT 0,
            meeting_type varchar(50) DEFAULT 'both',
            message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY teacher_id (teacher_id),
            KEY project_id (project_id),
            KEY date (date)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Time slots (also used for quantity mode with slot_date)
        $sql = "CREATE TABLE {$wpdb->prefix}pm_slots (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            availability_id bigint(20) unsigned NOT NULL,
            teacher_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned DEFAULT 0,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            slot_date date DEFAULT NULL,
            capacity int DEFAULT 0,
            status varchar(20) DEFAULT 'available',
            meeting_type varchar(50) DEFAULT 'both',
            is_hidden tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY availability_id (availability_id),
            KEY teacher_id (teacher_id),
            KEY project_id (project_id),
            KEY start_time (start_time),
            KEY slot_date (slot_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Bookings
        $sql = "CREATE TABLE {$wpdb->prefix}pm_bookings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slot_id bigint(20) unsigned NOT NULL,
            teacher_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned DEFAULT 0,
            position_number int DEFAULT NULL,
            parent_name varchar(200) NOT NULL,
            parent_email varchar(100) NOT NULL,
            parent_phone varchar(20),
            student_name varchar(200),
            class_id bigint(20) unsigned,
            meeting_type varchar(50) NOT NULL,
            remote_link varchar(500),
            status varchar(20) DEFAULT 'confirmed',
            attendance varchar(20) DEFAULT 'pending',
            cancel_token varchar(64),
            reminder_sent tinyint(1) DEFAULT 0,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slot_id (slot_id),
            KEY teacher_id (teacher_id),
            KEY project_id (project_id),
            KEY position_number (position_number),
            KEY parent_email (parent_email),
            KEY cancel_token (cancel_token),
            KEY reminder_sent (reminder_sent),
            KEY created_at (created_at),
            KEY status (status),
            KEY attendance (attendance),
            KEY status_created (status, created_at),
            KEY project_status (project_id, status),
            KEY teacher_status (teacher_id, status)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Magic tokens
        $sql = "CREATE TABLE {$wpdb->prefix}pm_tokens (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) unsigned NOT NULL,
            token varchar(64) NOT NULL,
            type varchar(20) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY teacher_id (teacher_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        dbDelta($sql);

        // Waiting list
        $sql = "CREATE TABLE {$wpdb->prefix}pm_waiting_list (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            teacher_id bigint(20) unsigned NOT NULL,
            project_id bigint(20) unsigned DEFAULT 0,
            parent_name varchar(200) NOT NULL,
            parent_email varchar(100) NOT NULL,
            parent_phone varchar(20),
            student_name varchar(200),
            class_id bigint(20) unsigned,
            meeting_type varchar(50) NOT NULL,
            preferred_date date,
            status varchar(20) DEFAULT 'waiting',
            notified_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY teacher_id (teacher_id),
            KEY project_id (project_id),
            KEY parent_email (parent_email),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        update_option('pm_db_version', PM_VERSION);

        // Run migrations if needed
        self::migrate_existing_data();
        self::migrate_quantity_mode();
    }

    public static function migrate_existing_data() {
        global $wpdb;

        // Check if migration already done
        if (get_option('pm_projects_migration_done')) {
            return;
        }

        // Check if there are any projects - if yes, migration already happened
        $project_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pm_projects");
        if ($project_count > 0) {
            update_option('pm_projects_migration_done', true);
            return;
        }

        // Create default project
        $default_form_config = json_encode([
            'parent_name' => ['enabled' => true, 'required' => true, 'label' => 'Tėvų vardas ir pavardė'],
            'student_name' => ['enabled' => true, 'required' => true, 'label' => 'Mokinio vardas ir pavardė'],
            'parent_email' => ['enabled' => true, 'required' => true, 'label' => 'El. paštas'],
            'parent_phone' => ['enabled' => true, 'required' => false, 'label' => 'Tel. nr.'],
            'notes' => ['enabled' => true, 'required' => false, 'label' => 'Pastaba (tikslas, tema)']
        ]);

        $wpdb->insert($wpdb->prefix . 'pm_projects', [
            'name' => 'Tėvų dienos 2025.12',
            'description' => 'Susitikimai su mokytojais tėvų dienų metu',
            'form_fields_config' => $default_form_config,
            'show_class_selection' => 1
        ]);

        $default_project_id = $wpdb->insert_id;

        // Assign all existing teachers to default project
        $teachers = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}pm_teachers");
        foreach ($teachers as $teacher) {
            $wpdb->insert($wpdb->prefix . 'pm_teacher_projects', [
                'teacher_id' => $teacher->id,
                'project_id' => $default_project_id
            ]);
        }

        // Assign all existing classes to default project
        $classes = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}pm_classes");
        foreach ($classes as $class) {
            $wpdb->insert($wpdb->prefix . 'pm_class_projects', [
                'class_id' => $class->id,
                'project_id' => $default_project_id
            ]);
        }

        // Update existing records with default project_id
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}pm_availability SET project_id = %d WHERE project_id IS NULL OR project_id = 0",
            $default_project_id
        ));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}pm_slots SET project_id = %d WHERE project_id IS NULL OR project_id = 0",
            $default_project_id
        ));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}pm_bookings SET project_id = %d WHERE project_id IS NULL OR project_id = 0",
            $default_project_id
        ));

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}pm_waiting_list SET project_id = %d WHERE project_id IS NULL OR project_id = 0",
            $default_project_id
        ));

        update_option('pm_projects_migration_done', true);
    }

    /**
     * Author: Tobalt — https://tobalt.lt
     * Migrate database for quantity booking mode support
     */
    public static function migrate_quantity_mode() {
        global $wpdb;

        // Check if migration already done
        if (get_option('pm_quantity_mode_migration_done')) {
            return;
        }

        // Add booking_mode column to projects if not exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}pm_projects LIKE 'booking_mode'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_projects ADD COLUMN booking_mode varchar(20) DEFAULT 'time' AFTER show_class_selection");
        }

        // Add daily_capacity column to projects if not exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}pm_projects LIKE 'daily_capacity'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_projects ADD COLUMN daily_capacity int DEFAULT 15 AFTER booking_mode");
        }

        // Add slot_date column to slots if not exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}pm_slots LIKE 'slot_date'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_slots ADD COLUMN slot_date date DEFAULT NULL AFTER end_time");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_slots ADD INDEX slot_date (slot_date)");
        }

        // Add capacity column to slots if not exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}pm_slots LIKE 'capacity'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_slots ADD COLUMN capacity int DEFAULT 0 AFTER slot_date");
        }

        // Add position_number column to bookings if not exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}pm_bookings LIKE 'position_number'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_bookings ADD COLUMN position_number int DEFAULT NULL AFTER project_id");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pm_bookings ADD INDEX position_number (position_number)");
        }

        update_option('pm_quantity_mode_migration_done', true);
    }

    public static function cleanup() {
        // Optional: Clean up expired tokens on deactivation
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}pm_tokens WHERE expires_at < NOW()");
    }
    
    public static function create_demo_data() {
        global $wpdb;

        // Get or create demo project
        $project = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}pm_projects LIMIT 1");
        if (!$project) {
            $default_form_config = json_encode([
                'parent_name' => ['enabled' => true, 'required' => true, 'label' => 'Tėvų vardas ir pavardė'],
                'student_name' => ['enabled' => true, 'required' => true, 'label' => 'Mokinio vardas ir pavardė'],
                'parent_email' => ['enabled' => true, 'required' => true, 'label' => 'El. paštas'],
                'parent_phone' => ['enabled' => true, 'required' => false, 'label' => 'Tel. nr.'],
                'notes' => ['enabled' => true, 'required' => false, 'label' => 'Pastaba (tikslas, tema)']
            ]);

            $wpdb->insert($wpdb->prefix . 'pm_projects', [
                'name' => 'Tėvų dienos 2025.12',
                'description' => 'Susitikimai su mokytojais tėvų dienų metu',
                'form_fields_config' => $default_form_config,
                'show_class_selection' => 1
            ]);
            $project_id = $wpdb->insert_id;
        } else {
            $project_id = $project->id;
        }

        // Create classes
        $classes = [
            '1A', '1B', '2A', '2B', '3A', '3B',
            '4A', '4B', '5A', '5B', '6A', '6B'
        ];

        $class_ids = [];
        foreach ($classes as $class_name) {
            $wpdb->insert($wpdb->prefix . 'pm_classes', ['name' => $class_name]);
            $class_id = $wpdb->insert_id;
            $class_ids[] = $class_id;

            // Assign class to project
            $wpdb->insert($wpdb->prefix . 'pm_class_projects', [
                'class_id' => $class_id,
                'project_id' => $project_id
            ]);
        }
        
        // Create teachers
        $teachers_data = [
            [
                'first_name' => 'Ona',
                'last_name' => 'Petraitienė',
                'email' => 'ona.petraitiene@mokykla.lt',
                'phone' => '+370 600 12345',
                'classes' => array_slice($class_ids, 0, 3),
                'types' => ['vietoje', 'nuotoliu']
            ],
            [
                'first_name' => 'Jonas',
                'last_name' => 'Kazlauskas',
                'email' => 'jonas.kazlauskas@mokykla.lt',
                'phone' => '+370 600 23456',
                'classes' => array_slice($class_ids, 3, 3),
                'types' => ['vietoje']
            ],
            [
                'first_name' => 'Gražina',
                'last_name' => 'Jankevičienė',
                'email' => 'grazina.jankeviciene@mokykla.lt',
                'phone' => '+370 600 34567',
                'classes' => array_slice($class_ids, 6, 3),
                'types' => ['nuotoliu']
            ],
            [
                'first_name' => 'Vytautas',
                'last_name' => 'Balčiūnas',
                'email' => 'vytautas.balciunas@mokykla.lt',
                'phone' => '+370 600 45678',
                'classes' => array_slice($class_ids, 9, 3),
                'types' => ['vietoje', 'nuotoliu']
            ]
        ];
        
        $teacher_ids = [];
        foreach ($teachers_data as $teacher) {
            $wpdb->insert($wpdb->prefix . 'pm_teachers', [
                'first_name' => $teacher['first_name'],
                'last_name' => $teacher['last_name'],
                'email' => $teacher['email'],
                'phone' => $teacher['phone'],
                'class_ids' => json_encode($teacher['classes']),
                'meeting_types' => json_encode($teacher['types']),
                'default_duration' => 15,
                'buffer_time' => 5
            ]);
            
            $teacher_id = $wpdb->insert_id;
            $teacher_ids[] = $teacher_id;

            // Assign teacher to project
            $wpdb->insert($wpdb->prefix . 'pm_teacher_projects', [
                'teacher_id' => $teacher_id,
                'project_id' => $project_id
            ]);

            // Generate magic tokens
            PM_Magic_Links::generate_tokens($teacher_id);
        }
        
        // Create availability periods and slots for next 2 weeks
        $start_date = strtotime('next Monday');
        
        foreach ($teacher_ids as $teacher_id) {
            // Get teacher data
            $teacher = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pm_teachers WHERE id = %d",
                $teacher_id
            ));
            
            $meeting_types = json_decode($teacher->meeting_types, true);
            $meeting_type = count($meeting_types) > 1 ? 'both' : $meeting_types[0];
            
            // Demo message
            $demo_message = '<h4>Sveiki, gerbiami tėvai!</h4>
<p>Kviečiu Jus į tėvų susitikimus. Susitikimo metu aptarsime:</p>
<ul>
<li>Mokinio akademinius pasiekimus</li>
<li>Elgesio įvertinimą</li>
<li>Artėjančius renginius ir projektus</li>
</ul>
<p><strong>Prašome turėti:</strong> mokinio dienyną ir užrašų knygelę.</p>
<p>Laukiu susitikimo!</p>';
            
            // Create availability for 5 working days (Monday-Friday)
            for ($i = 0; $i < 5; $i++) {
                $date = date('Y-m-d', $start_date + ($i * 86400));
                $start_time = '14:00:00';
                $end_time = '17:00:00';
                
                // Insert availability period
                $wpdb->insert($wpdb->prefix . 'pm_availability', [
                    'teacher_id' => $teacher_id,
                    'project_id' => $project_id,
                    'date' => $date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'duration' => 15,
                    'buffer_time' => 5,
                    'meeting_type' => $meeting_type,
                    'message' => $demo_message
                ]);

                $availability_id = $wpdb->insert_id;

                // Generate time slots (15 min + 5 min buffer = 20 min)
                $current = strtotime($date . ' ' . $start_time);
                $end = strtotime($date . ' ' . $end_time);
                $slot_duration = 20 * 60; // 20 minutes total

                while ($current < $end) {
                    $slot_start = date('Y-m-d H:i:s', $current);
                    $slot_end = date('Y-m-d H:i:s', $current + (15 * 60)); // Actual meeting time

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
        }
        
        // Create a few sample bookings
        $parents = [
            ['name' => 'Rasa Jurgaitienė', 'email' => 'rasa.j@gmail.com', 'phone' => '+370 611 11111', 'student' => 'Mindaugas Jurgaitis'],
            ['name' => 'Petras Lietuvninkas', 'email' => 'petras.l@gmail.com', 'phone' => '+370 622 22222', 'student' => 'Laura Lietuvninkaitė'],
            ['name' => 'Aldona Kazlauskienė', 'email' => 'aldona.k@gmail.com', 'phone' => '+370 633 33333', 'student' => 'Jonas Kazlauskas']
        ];
        
        // Book first 2 slots of first teacher
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_slots WHERE teacher_id = %d AND status = 'available' LIMIT 2",
            $teacher_ids[0]
        ));
        
        foreach ($slots as $index => $slot) {
            if (isset($parents[$index])) {
                $parent = $parents[$index];
                $cancel_token = bin2hex(random_bytes(32));
                
                $wpdb->insert($wpdb->prefix . 'pm_bookings', [
                    'slot_id' => $slot->id,
                    'teacher_id' => $slot->teacher_id,
                    'project_id' => $project_id,
                    'parent_name' => $parent['name'],
                    'parent_email' => $parent['email'],
                    'parent_phone' => $parent['phone'],
                    'student_name' => $parent['student'],
                    'class_id' => $class_ids[0],
                    'meeting_type' => 'vietoje',
                    'status' => 'confirmed',
                    'attendance' => 'pending',
                    'cancel_token' => $cancel_token
                ]);
                
                // Mark slot as booked
                $wpdb->update(
                    $wpdb->prefix . 'pm_slots',
                    ['status' => 'booked'],
                    ['id' => $slot->id]
                );
            }
        }
        
        update_option('pm_demo_data_created', true);
    }
}

