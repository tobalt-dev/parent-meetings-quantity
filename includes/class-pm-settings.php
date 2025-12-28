<?php

class PM_Settings {

    private $options;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page'], 20); // Priority 20 to run after main menu
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        $this->options = get_option('pm_settings', []);
    }

    public function enqueue_styles($hook) {
        // Load dashicons on settings page - try multiple hook formats
        if (strpos($hook, 'pm-settings') !== false || strpos($hook, 'parent-meetings') !== false) {
            wp_enqueue_style('dashicons');
            // Debug output
            if (current_user_can('manage_options') && isset($_GET['debug'])) {
                error_log('PM Settings Hook: ' . $hook);
            }
        }
    }

    public function add_settings_page() {
        add_submenu_page(
            'parent-meetings',
            'Nustatymai',
            'Nustatymai',
            'manage_options',
            'pm-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('pm_settings_group', 'pm_settings', [$this, 'sanitize_settings']);

        // General Settings Section
        add_settings_section(
            'pm_general_section',
            'Bendri Nustatymai',
            [$this, 'general_section_callback'],
            'pm-settings'
        );

        add_settings_field(
            'default_duration',
            'Numatytoji susitikimo trukmė (minutės)',
            [$this, 'duration_callback'],
            'pm-settings',
            'pm_general_section'
        );

        add_settings_field(
            'default_buffer_time',
            'Numatytasis pertraukos laikas (minutės)',
            [$this, 'buffer_callback'],
            'pm-settings',
            'pm_general_section'
        );

        add_settings_field(
            'booking_advance_hours',
            'Minimalus iš anksto rezervuoti valandų skaičius',
            [$this, 'advance_hours_callback'],
            'pm-settings',
            'pm_general_section'
        );

        add_settings_field(
            'timezone',
            'Numatytoji laiko juosta',
            [$this, 'timezone_callback'],
            'pm-settings',
            'pm_general_section'
        );

        // reCAPTCHA Settings Section
        add_settings_section(
            'pm_recaptcha_section',
            'reCAPTCHA Nustatymai',
            [$this, 'recaptcha_section_callback'],
            'pm-settings'
        );

        add_settings_field(
            'recaptcha_site_key',
            'reCAPTCHA Site Key',
            [$this, 'site_key_callback'],
            'pm-settings',
            'pm_recaptcha_section'
        );

        add_settings_field(
            'recaptcha_secret_key',
            'reCAPTCHA Secret Key',
            [$this, 'secret_key_callback'],
            'pm-settings',
            'pm_recaptcha_section'
        );

        add_settings_field(
            'recaptcha_enabled',
            'Įjungti reCAPTCHA',
            [$this, 'recaptcha_enabled_callback'],
            'pm-settings',
            'pm_recaptcha_section'
        );

        // Email Settings Section
        add_settings_section(
            'pm_email_section',
            'El. Pašto Nustatymai',
            [$this, 'email_section_callback'],
            'pm-settings'
        );

        add_settings_field(
            'from_name',
            'El. pašto siuntėjo vardas',
            [$this, 'from_name_callback'],
            'pm-settings',
            'pm_email_section'
        );

        add_settings_field(
            'from_email',
            'El. pašto siuntėjo adresas',
            [$this, 'from_email_callback'],
            'pm-settings',
            'pm_email_section'
        );

        add_settings_field(
            'send_reminders',
            'Siųsti priminimus prieš 24 val.',
            [$this, 'send_reminders_callback'],
            'pm-settings',
            'pm_email_section'
        );

        add_settings_field(
            'enable_ical',
            'Pridėti iCal priedus',
            [$this, 'enable_ical_callback'],
            'pm-settings',
            'pm_email_section'
        );

        // Advanced Settings Section
        add_settings_section(
            'pm_advanced_section',
            'Išplėstiniai Nustatymai',
            [$this, 'advanced_section_callback'],
            'pm-settings'
        );

        add_settings_field(
            'enable_waiting_list',
            'Įjungti laukimo sąrašą',
            [$this, 'waiting_list_callback'],
            'pm-settings',
            'pm_advanced_section'
        );

        add_settings_field(
            'magic_link_expiry_days',
            'Magic Link galiojimo laikas (dienomis)',
            [$this, 'magic_link_expiry_callback'],
            'pm-settings',
            'pm_advanced_section'
        );

        add_settings_field(
            'cleanup_old_data_days',
            'Ištrinti senus duomenis po (dienų, 0 = niekada)',
            [$this, 'cleanup_days_callback'],
            'pm-settings',
            'pm_advanced_section'
        );

        add_settings_field(
            'analytics_retention_days',
            'Analitikos duomenų saugojimas (dienų)',
            [$this, 'analytics_retention_callback'],
            'pm-settings',
            'pm_advanced_section'
        );

        add_settings_field(
            'enable_meeting_notes',
            'Įjungti mokytojų pastabas',
            [$this, 'meeting_notes_callback'],
            'pm-settings',
            'pm_advanced_section'
        );
    }

    public function sanitize_settings($input) {
        $sanitized = [];

        $sanitized['default_duration'] = absint($input['default_duration'] ?? 15);
        $sanitized['default_buffer_time'] = absint($input['default_buffer_time'] ?? 5);
        $sanitized['booking_advance_hours'] = absint($input['booking_advance_hours'] ?? 1);
        $sanitized['timezone'] = sanitize_text_field($input['timezone'] ?? 'Europe/Vilnius');

        $sanitized['recaptcha_site_key'] = sanitize_text_field($input['recaptcha_site_key'] ?? '');
        $sanitized['recaptcha_secret_key'] = sanitize_text_field($input['recaptcha_secret_key'] ?? '');
        $sanitized['recaptcha_enabled'] = isset($input['recaptcha_enabled']) ? 1 : 0;

        $sanitized['from_name'] = sanitize_text_field($input['from_name'] ?? get_bloginfo('name'));
        $sanitized['from_email'] = sanitize_email($input['from_email'] ?? get_bloginfo('admin_email'));
        $sanitized['send_reminders'] = isset($input['send_reminders']) ? 1 : 0;
        $sanitized['enable_ical'] = isset($input['enable_ical']) ? 1 : 0;

        $sanitized['enable_waiting_list'] = isset($input['enable_waiting_list']) ? 1 : 0;
        $sanitized['magic_link_expiry_days'] = absint($input['magic_link_expiry_days'] ?? 30);
        $sanitized['cleanup_old_data_days'] = absint($input['cleanup_old_data_days'] ?? 0);
        $sanitized['analytics_retention_days'] = absint($input['analytics_retention_days'] ?? 730);
        $sanitized['enable_meeting_notes'] = isset($input['enable_meeting_notes']) ? 1 : 0;

        return $sanitized;
    }

    // Helper function for tooltips
    private function help_tip($text) {
        return ' <span class="dashicons dashicons-editor-help pm-help-tip" data-tip="' . esc_attr($text) . '" style="width: 18px; height: 18px; font-size: 18px;"></span><span class="pm-help-tip" data-tip="' . esc_attr($text) . '" style="display: inline-block; background: #fff; color: #000; width: 18px; height: 18px; line-height: 18px; text-align: center; border-radius: 50%; font-weight: bold; font-size: 12px; cursor: help; border: 2px solid #ddd;">?</span>';
    }

    // Section Callbacks
    public function general_section_callback() {
        echo '<p>Konfigūruokite pagrindinius įskiepio nustatymus.</p>';
    }

    public function recaptcha_section_callback() {
        echo '<p>Gaukite savo reCAPTCHA raktus iš <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin</a>.</p>';
    }

    public function email_section_callback() {
        echo '<p>Konfigūruokite el. pašto pranešimus.</p>';
    }

    public function advanced_section_callback() {
        echo '<p>Išplėstinės konfigūracijos parinktys.</p>';
    }

    // Field Callbacks
    public function duration_callback() {
        $value = $this->get_option('default_duration', 15);
        echo '<input type="number" name="pm_settings[default_duration]" value="' . esc_attr($value) . '" min="5" max="120" step="5" />';
        echo $this->help_tip('Kiek minučių vidutiniškai turėtų trukti vienas susitikimas su tėvais. Rekomenduojama 15-30 minučių.');
        echo '<p class="description">Numatytoji susitikimų trukmė minutėmis.</p>';
    }

    public function buffer_callback() {
        $value = $this->get_option('default_buffer_time', 5);
        echo '<input type="number" name="pm_settings[default_buffer_time]" value="' . esc_attr($value) . '" min="0" max="60" step="5" />';
        echo $this->help_tip('Pertrauka tarp susitikimų, skirta pasirengimui ar netikėtumams. Rekomenduojama 5-10 minučių.');
        echo '<p class="description">Pertraukos laikas tarp susitikimų.</p>';
    }

    public function advance_hours_callback() {
        $value = $this->get_option('booking_advance_hours', 1);
        echo '<input type="number" name="pm_settings[booking_advance_hours]" value="' . esc_attr($value) . '" min="0" max="168" />';
        echo $this->help_tip('Kiek valandų prieš susitikimą tėvai turi rezervuoti vietą. Pvz., 1 reiškia, kad negalima rezervuoti susitikimo, kuris vyks po valandos.');
        echo '<p class="description">Minimalus valandų skaičius prieš rezervaciją.</p>';
    }

    public function timezone_callback() {
        $value = $this->get_option('timezone', 'Europe/Vilnius');
        $timezones = timezone_identifiers_list();
        echo '<select name="pm_settings[timezone]">';
        foreach ($timezones as $tz) {
            echo '<option value="' . esc_attr($tz) . '"' . selected($value, $tz, false) . '>' . esc_html($tz) . '</option>';
        }
        echo '</select>';
        echo $this->help_tip('Laiko juosta, kuri bus naudojama visam sistemos laikui rodyti. Rekomenduojama: Europe/Vilnius.');
        echo '<p class="description">Numatytoji laiko juosta susitikimams.</p>';
    }

    public function site_key_callback() {
        $value = $this->get_option('recaptcha_site_key', '');
        echo '<input type="text" name="pm_settings[recaptcha_site_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo $this->help_tip('Viešasis raktas, kurį gausite iš Google reCAPTCHA Admin konsolės. Šis raktas bus naudojamas svetainės puslapiuose.');
    }

    public function secret_key_callback() {
        $value = $this->get_option('recaptcha_secret_key', '');
        echo '<input type="text" name="pm_settings[recaptcha_secret_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo $this->help_tip('Privatus raktas, kurį gausite iš Google reCAPTCHA Admin konsolės. Šis raktas bus naudojamas serverio pusėje tikrinimui.');
    }

    public function recaptcha_enabled_callback() {
        $value = $this->get_option('recaptcha_enabled', 0);
        echo '<input type="checkbox" name="pm_settings[recaptcha_enabled]" value="1"' . checked($value, 1, false) . ' />';
        echo $this->help_tip('Apsaugokite rezervacijos formą nuo automatinių botų ir šlamšto. Rekomenduojama įjungti didelėms mokykloms.');
        echo '<p class="description">Įjungti reCAPTCHA patikrinimą rezervacijoms.</p>';
    }

    public function from_name_callback() {
        $value = $this->get_option('from_name', get_bloginfo('name'));
        echo '<input type="text" name="pm_settings[from_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo $this->help_tip('Vardas, kuris bus rodomas kaip siuntėjas el. laiškuose tėvams. Pvz: \'Mokyklos pavadinimas\' arba \'Tėvų susitikimai\'.');
    }

    public function from_email_callback() {
        $value = $this->get_option('from_email', get_bloginfo('admin_email'));
        echo '<input type="email" name="pm_settings[from_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo $this->help_tip('El. pašto adresas, nuo kurio bus siunčiami laiškai tėvams. Įsitikinkite, kad šis adresas yra tikras ir veikiantis.');
    }

    public function send_reminders_callback() {
        $value = $this->get_option('send_reminders', 1);
        echo '<input type="checkbox" name="pm_settings[send_reminders]" value="1"' . checked($value, 1, false) . ' />';
        echo $this->help_tip('Automatiškai siųsti el. laiškus tėvams 24 valandas prieš susitikimą, primenant apie rezervaciją.');
        echo '<p class="description">Siųsti automatinius priminimus prieš 24 valandas.</p>';
    }

    public function enable_ical_callback() {
        $value = $this->get_option('enable_ical', 1);
        echo '<input type="checkbox" name="pm_settings[enable_ical]" value="1"' . checked($value, 1, false) . ' />';
        echo $this->help_tip('Pridėti kalendoriaus failą (.ics) prie patvirtinimo el. laiškų, kad tėvai galėtų lengvai pridėti susitikimą į savo kalendorių.');
        echo '<p class="description">Pridėti iCal priedus prie patvirtinimo el. laiškų.</p>';
    }

    public function waiting_list_callback() {
        $value = $this->get_option('enable_waiting_list', 1);
        echo '<input type="checkbox" name="pm_settings[enable_waiting_list]" value="1"' . checked($value, 1, false) . ' />';
        echo $this->help_tip('Leisti tėvams registruotis į laukimo sąrašą, jei visi laiko tarpsniai yra užimti. Kai atsiras laisva vieta, jie bus informuoti el. paštu.');
        echo '<p class="description">Leisti tėvams prisijungti prie laukimo sąrašo.</p>';
    }

    public function magic_link_expiry_callback() {
        $value = $this->get_option('magic_link_expiry_days', 30);
        echo '<input type="number" name="pm_settings[magic_link_expiry_days]" value="' . esc_attr($value) . '" min="1" max="365" />';
        echo $this->help_tip('Kiek dienų mokytojų prisijungimo nuorodos (magic links) bus galiojančios. Po šio laiko reikės sugeneruoti naują nuorodą.');
        echo '<p class="description">Dienų skaičius prieš nuorodų galiojimo pabaigą.</p>';
    }

    public function cleanup_days_callback() {
        $value = $this->get_option('cleanup_old_data_days', 0);
        echo '<input type="number" name="pm_settings[cleanup_old_data_days]" value="' . esc_attr($value) . '" min="0" max="3650" />';
        echo $this->help_tip('Automatiškai ištrinti rezervacijas ir laiko tarpus senesnius nei nurodyta dienų. Nustatykite 0, jei norite saugoti visus duomenis amžinai.');
        echo '<p class="description">Automatiškai ištrinti duomenis senesnius nei nurodytas dienų skaičius. Nustatykite 0 išsaugoti amžinai.</p>';
    }

    public function analytics_retention_callback() {
        $value = $this->get_option('analytics_retention_days', 730);
        $cleanup_days = $this->get_option('cleanup_old_data_days', 0);

        echo '<input type="number" name="pm_settings[analytics_retention_days]" value="' . esc_attr($value) . '" min="90" max="3650" />';
        echo $this->help_tip('Kiek dienų duomenys turėtų būti saugomi analitikai. Rekomenduojama: 730 dienų (24 mėnesiai) ilgalaikėms tendencijoms ir palyginimams.');
        echo '<p class="description">Minimalus duomenų saugojimas analitikos ataskaitoms. Numatyta: 730 dienų (2 metai).</p>';

        if ($cleanup_days > 0 && $cleanup_days < $value) {
            echo '<p class="description" style="color: #d63638; font-weight: 500;">⚠️ Įspėjimas: Automatinio duomenų valymo nustatymas (' . $cleanup_days . ' d.) yra trumpesnis nei analitikos saugojimas. Tai gali pašalinti analitikos duomenis!</p>';
        }
    }

    public function meeting_notes_callback() {
        $value = $this->get_option('enable_meeting_notes', 1);
        echo '<input type="checkbox" name="pm_settings[enable_meeting_notes]" value="1"' . checked($value, 1, false) . ' />';
        echo $this->help_tip('Leisti mokytojams pridėti privačias pastabas po susitikimų. Šios pastabos matomos tik mokytojui ir administratoriui.');
        echo '<p class="description">Leisti mokytojams pridėti privačias pastabas po susitikimų.</p>';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error('pm_messages', 'pm_message', 'Nustatymai sėkmingai išsaugoti.', 'updated');
        }

        settings_errors('pm_messages');
        ?>
        <?php wp_enqueue_style('dashicons'); ?>
        <link rel="stylesheet" href="<?php echo esc_url(includes_url('css/dashicons.min.css')); ?>" type="text/css" media="all" />
        <script>
        // Force load dashicons if not loaded
        (function() {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '<?php echo esc_js(includes_url('css/dashicons.min.css')); ?>';
            document.head.appendChild(link);
        })();
        </script>
        <style>
        @import url('<?php echo esc_url(includes_url('css/dashicons.min.css')); ?>');

        .pm-help-tip {
            position: relative;
            display: inline-block;
            cursor: help;
            color: #666 !important;
            font-size: 16px !important;
            text-decoration: none !important;
            margin-left: 5px;
            vertical-align: middle;
        }
        .pm-help-tip:hover {
            color: #2271b1 !important;
        }
        .pm-help-tip::after {
            content: attr(data-tip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 10px;
            padding: 10px 14px;
            background: #2c3338 !important;
            color: #fff !important;
            border-radius: 4px;
            font-size: 13px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
            white-space: nowrap;
            min-width: 200px;
            max-width: 350px;
            z-index: 999999;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            font-weight: normal;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
        }
        .pm-help-tip:hover::after {
            opacity: 1 !important;
            visibility: visible !important;
        }
        .pm-help-tip::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 5px;
            border: 6px solid transparent;
            border-right-color: #2c3338;
            z-index: 999999;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
        }
        .pm-help-tip:hover::before {
            opacity: 1 !important;
            visibility: visible !important;
        }
        </style>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('pm_settings_group');
                do_settings_sections('pm-settings');
                submit_button('Išsaugoti Nustatymus');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get a single option value
     */
    public static function get_option($key, $default = '') {
        $options = get_option('pm_settings', []);
        return $options[$key] ?? $default;
    }

    /**
     * Get all options
     */
    public static function get_all_options() {
        return get_option('pm_settings', []);
    }
}
