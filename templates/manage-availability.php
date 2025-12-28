<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tvarkyti prieinamumo laikus</title>
    <link rel="stylesheet" href="<?php echo PM_PLUGIN_URL; ?>assets/css/teacher.css">
</head>
<body class="pm-teacher-page">
    <div class="pm-container">
        <header class="pm-header">
            <h1>Sveiki, <?php echo esc_html($teacher->first_name . ' ' . $teacher->last_name); ?></h1>
            <p>Čia galite tvarkyti savo prieinamumo laikus tėvų susitikimams.</p>
        </header>
        
        <div class="pm-section">
            <h2>Pridėti naują laikotarpį</h2>
            <form method="post" class="pm-availability-form">
                <?php wp_nonce_field('pm_availability_' . $teacher_id); ?>

                <div class="pm-form-row">
                    <div class="pm-form-group" style="grid-column: 1 / -1;">
                        <label>Projektas *</label>
                        <select name="project_id" id="pm-project-select" required>
                            <option value="">Pasirinkite projektą...</option>
                            <?php foreach ($teacher_projects as $project) : ?>
                                <option value="<?php echo esc_attr($project->id); ?>"
                                        data-capacity="<?php echo esc_attr($project->daily_capacity ?? 15); ?>">
                                    <?php echo esc_html($project->name); ?>
                                    (<?php echo intval($project->daily_capacity ?? 15); ?> vietų/dieną)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Pasirinkite projektą, kuriam kuriate prieinamumo dienas</p>
                    </div>
                </div>

                <!-- Always quantity mode -->
                <input type="hidden" name="booking_mode" value="quantity">

                <!-- Date selection -->
                <div class="pm-form-row">
                    <div class="pm-form-group">
                        <label>Pradžios Data *</label>
                        <input type="date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="pm-form-group">
                        <label>Pabaigos Data *</label>
                        <input type="date" name="end_date" min="<?php echo date('Y-m-d'); ?>" required>
                        <p class="description">Vietos bus sukurtos visoms dienoms šiame intervale</p>
                    </div>
                </div>

                <!-- Capacity field -->
                <div class="pm-form-row">
                    <div class="pm-form-group">
                        <label>Vietų skaičius per dieną *</label>
                        <input type="number" name="capacity" id="pm-capacity" value="15" min="1" max="100" required>
                        <p class="description">Kiek registracijų galite priimti per dieną</p>
                    </div>
                </div>
                <div class="pm-notice pm-notice-info" style="margin: 15px 0; padding: 12px; background: #e7f3ff; border-left: 4px solid #0073aa;">
                    <strong>Kiekio režimas:</strong> Tėvai matys laisvas pozicijas (pvz. "Laisva 4 iš 15"), be konkretaus laiko.
                </div>
                
                <div class="pm-form-group">
                    <label>Žinutė tėvams</label>
                    <?php 
                    wp_editor('', 'availability_message', [
                        'textarea_name' => 'availability_message',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => false,
                        'tinymce' => [
                            'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink',
                            'toolbar2' => ''
                        ]
                    ]);
                    ?>
                    <p class="description">Ši žinutė bus rodoma tėvams renkantis susitikimo laiką (pvz: temų sąrašas, ką atsinešti)</p>
                </div>
                
                <button type="submit" name="pm_add_availability" class="pm-button pm-button-primary">
                    Pridėti Laikotarpį
                </button>
            </form>
        </div>
        
        <div class="pm-section">
            <h2>Jūsų Prieinamumo Dienos</h2>
            <?php if (empty($availabilities)): ?>
                <p>Prieinamumo dienų nėra.</p>
            <?php else:
                // Group by project and date range
                $grouped = [];
                foreach ($availabilities as $avail) {
                    $key = $avail->project_id . '_' . $avail->date;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'dates' => [$avail->date],
                            'project_name' => $avail->project_name,
                            'capacity' => $avail->capacity ?? 0,
                            'ids' => [$avail->id]
                        ];
                    } else {
                        $grouped[$key]['dates'][] = $avail->date;
                        $grouped[$key]['ids'][] = $avail->id;
                    }
                }
            ?>
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th>Projektas</th>
                            <th>Data</th>
                            <th>Vietų kiekis</th>
                            <th>Užimta</th>
                            <th>Veiksmai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grouped as $group):
                            $dates = $group['dates'];
                            sort($dates);

                            $date_display = date_i18n(get_option('date_format'), strtotime($dates[0]));

                            // For quantity mode, count bookings against the slot
                            $booked_count = 0;
                            $capacity = $group['capacity'];
                            foreach ($group['ids'] as $slot_id) {
                                $booked_count += $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}pm_bookings WHERE slot_id = %d AND status != 'cancelled'",
                                    $slot_id
                                ));
                            }
                            $first_slot_id = $group['ids'][0];
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($group['project_name'] ?: '-'); ?></strong></td>
                            <td><?php echo esc_html($date_display); ?></td>
                            <td><?php echo intval($capacity); ?> vietų</td>
                            <td><?php echo absint($booked_count); ?> / <?php echo intval($capacity); ?></td>
                            <td>
                                <button type="button"
                                        class="pm-button pm-button-small pm-button-danger pm-delete-availability"
                                        data-availability-id="<?php echo absint($first_slot_id); ?>"
                                        data-booked-count="<?php echo absint($booked_count); ?>">Ištrinti</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update capacity from project selection
        const projectSelect = document.getElementById('pm-project-select');
        const capacityInput = document.getElementById('pm-capacity');

        function updateCapacity() {
            const selected = projectSelect.options[projectSelect.selectedIndex];
            const capacity = selected ? selected.getAttribute('data-capacity') : 15;
            if (capacity) {
                capacityInput.value = capacity;
            }
        }

        if (projectSelect) {
            projectSelect.addEventListener('change', updateCapacity);
        }

        // Delete availability handler
        const deleteButtons = document.querySelectorAll('.pm-delete-availability');

        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const availId = this.getAttribute('data-availability-id');
                const bookedCount = parseInt(this.getAttribute('data-booked-count'));

                if (bookedCount > 0) {
                    alert('Negalima ištrinti prieinamumo dienos, kurioje yra ' + bookedCount + ' rezervacijų.');
                    return;
                }

                if (confirm('Ar tikrai norite ištrinti šią prieinamumo dieną?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="pm_delete_availability" value="1">' +
                                   '<input type="hidden" name="availability_id" value="' + availId + '">' +
                                   '<?php echo wp_nonce_field("pm_delete_availability_" . $teacher_id, "_wpnonce", true, false); ?>';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    </script>
</body>
</html>
