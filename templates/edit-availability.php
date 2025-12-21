<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redaguoti prieinamumą</title>
    <link rel="stylesheet" href="<?php echo PM_PLUGIN_URL; ?>assets/css/teacher.css">
</head>
<body class="pm-teacher-page">
    <div class="pm-container">
        <header class="pm-header">
            <h1>Redaguoti prieinamumo laikotarpį</h1>
            <p><a href="?pm_action=manage&token=<?php echo esc_attr($token); ?>" class="pm-button pm-button-small">← Atgal į prieinamumą</a></p>
        </header>

        <?php if ($booked_slots_count > 0): ?>
            <div class="pm-notice pm-notice-warning">
                <strong>Dėmesio:</strong> Šis laikotarpis turi <?php echo absint($booked_slots_count); ?> aktyvių rezervacijų.
                Redaguojant, tik laisvi laikai bus atnaujinti. Rezervuoti laikai išliks nepakeisti.
            </div>
        <?php endif; ?>

        <div class="pm-section">
            <form method="post" class="pm-availability-form">
                <?php wp_nonce_field('pm_edit_availability_' . $teacher_id); ?>
                <input type="hidden" name="availability_id" value="<?php echo absint($availability->id); ?>">

                <div class="pm-form-row">
                    <div class="pm-form-group" style="grid-column: 1 / -1;">
                        <label>Projektas</label>
                        <input type="text" value="<?php echo esc_attr($project_name); ?>" disabled>
                        <p class="description">Projekto negalima keisti</p>
                    </div>
                </div>

                <div class="pm-form-row">
                    <div class="pm-form-group">
                        <label>Data</label>
                        <input type="text" value="<?php echo esc_attr(date_i18n(get_option('date_format'), strtotime($availability->date))); ?>" disabled>
                        <p class="description">Datos negalima keisti</p>
                    </div>

                    <div class="pm-form-group">
                        <label>Pradžios Laikas *</label>
                        <input type="time" name="start_time" value="<?php echo esc_attr($availability->start_time); ?>" required>
                    </div>

                    <div class="pm-form-group">
                        <label>Pabaigos Laikas *</label>
                        <input type="time" name="end_time" value="<?php echo esc_attr($availability->end_time); ?>" required>
                    </div>
                </div>

                <div class="pm-form-row">
                    <div class="pm-form-group">
                        <label>Susitikimo Trukmė (min) *</label>
                        <input type="number" name="duration" value="<?php echo absint($availability->duration); ?>" min="5" max="120" required>
                    </div>

                    <div class="pm-form-group">
                        <label>Pertrauka tarp susitikimų (min)</label>
                        <input type="number" name="buffer_time" value="<?php echo absint($availability->buffer_time); ?>" min="0" max="60">
                        <p class="description">Papildomas laikas po susitikimo pasiruošimui</p>
                    </div>
                </div>

                <div class="pm-form-row">
                    <div class="pm-form-group">
                        <label>Susitikimo Tipas *</label>
                        <select name="meeting_type" required>
                            <option value="vietoje" <?php selected($availability->meeting_type, 'vietoje'); ?>>Vietoje</option>
                            <option value="nuotoliu" <?php selected($availability->meeting_type, 'nuotoliu'); ?>>Nuotoliniu būdu</option>
                            <option value="both" <?php selected($availability->meeting_type, 'both'); ?>>Abu</option>
                        </select>
                    </div>
                </div>

                <div class="pm-form-row">
                    <div class="pm-form-group" style="grid-column: 1 / -1;">
                        <label>Žinutė Tėvams</label>
                        <?php
                        wp_editor(
                            $availability->message,
                            'message',
                            [
                                'textarea_name' => 'message',
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                                'teeny' => true,
                                'quicktags' => ['buttons' => 'strong,em,link'],
                            ]
                        );
                        ?>
                        <p class="description">Ši žinutė bus rodoma tėvams, kai jie rinksis šį laikotarpį</p>
                    </div>
                </div>

                <div class="pm-form-actions">
                    <button type="submit" name="pm_update_availability" class="pm-button pm-button-primary">Išsaugoti pakeitimus</button>
                    <a href="?pm_action=manage&token=<?php echo esc_attr($token); ?>" class="pm-button pm-button-secondary">Atšaukti</a>
                </div>
            </form>
        </div>

        <div class="pm-footer">
            <p>Ši nuoroda galioja iki: <strong><?php
                global $wpdb;
                $token_expires = $wpdb->get_var($wpdb->prepare(
                    "SELECT expires_at FROM {$wpdb->prefix}pm_tokens WHERE token = %s",
                    $token
                ));
                echo date_i18n(get_option('date_format'), strtotime($token_expires));
            ?></strong></p>
        </div>
    </div>
</body>
</html>
