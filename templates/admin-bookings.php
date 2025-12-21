<div class="wrap">
    <h1>Rezervacijos</h1>

    <?php if (isset($_GET['booking_deleted'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>Rezervacija sėkmingai ištrinta.</p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['booking_cancelled'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>Rezervacija sėkmingai atšaukta.</p>
        </div>
    <?php endif; ?>

    <div class="pm-admin-section" style="margin-bottom: 20px;">
        <form method="get" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="page" value="pm-bookings">

            <?php if (!empty($all_projects)) : ?>
            <div>
                <label for="project_filter"><strong>Projektas:</strong></label><br>
                <select name="project_filter" id="project_filter">
                    <option value="0">Visi</option>
                    <?php foreach ($all_projects as $project) : ?>
                        <option value="<?php echo esc_attr($project->id); ?>" <?php selected($filter_project_id, $project->id); ?>>
                            <?php echo esc_html($project->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($all_teachers)) : ?>
            <div>
                <label for="teacher_filter"><strong>Mokytojas:</strong></label><br>
                <select name="teacher_filter" id="teacher_filter">
                    <option value="0">Visi</option>
                    <?php foreach ($all_teachers as $teacher) : ?>
                        <option value="<?php echo esc_attr($teacher->id); ?>" <?php selected($filter_teacher_id, $teacher->id); ?>>
                            <?php echo esc_html($teacher->first_name . ' ' . $teacher->last_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label for="status_filter"><strong>Statusas:</strong></label><br>
                <select name="status_filter" id="status_filter">
                    <option value="">Visi</option>
                    <option value="confirmed" <?php selected($filter_status, 'confirmed'); ?>>Patvirtinta</option>
                    <option value="cancelled" <?php selected($filter_status, 'cancelled'); ?>>Atšaukta</option>
                </select>
            </div>

            <div>
                <label for="attendance_filter"><strong>Lankomumas:</strong></label><br>
                <select name="attendance_filter" id="attendance_filter">
                    <option value="">Visi</option>
                    <option value="pending" <?php selected($filter_attendance, 'pending'); ?>>Laukiama</option>
                    <option value="attended" <?php selected($filter_attendance, 'attended'); ?>>Atvyko</option>
                    <option value="missed" <?php selected($filter_attendance, 'missed'); ?>>Neatvyko</option>
                </select>
            </div>

            <div>
                <button type="submit" class="button">Filtruoti</button>
                <?php if ($filter_project_id || $filter_teacher_id || $filter_status || $filter_attendance) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pm-bookings')); ?>" class="button">Išvalyti</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($bookings)): ?>
        <p>Rezervacijų nėra.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Projektas</th>
                    <th>Data/Laikas</th>
                    <th>Mokytojas</th>
                    <th>Tėvas</th>
                    <th>El. paštas</th>
                    <th>Klasė</th>
                    <th>Tipas</th>
                    <th>Statusas</th>
                    <th>Lankomumas</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo absint($booking->id); ?></td>
                    <td><strong><?php echo esc_html($booking->project_name ?: '-'); ?></strong></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->start_time))); ?></td>
                    <td><?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?></td>
                    <td><?php echo esc_html($booking->parent_name); ?></td>
                    <td><?php echo esc_html($booking->parent_email); ?></td>
                    <td><?php echo esc_html($booking->class_name ?: '-'); ?></td>
                    <td><?php echo esc_html($booking->meeting_type); ?></td>
                    <td>
                        <span class="pm-status pm-status-<?php echo esc_attr($booking->status); ?>">
                            <?php
                            $status_labels = [
                                'confirmed' => 'Patvirtinta',
                                'cancelled' => 'Atšaukta',
                                'pending' => 'Laukiama'
                            ];
                            echo esc_html($status_labels[$booking->status] ?? $booking->status);
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="pm-attendance pm-attendance-<?php echo esc_attr($booking->attendance); ?>">
                            <?php
                            $attendance_labels = [
                                'pending' => 'Laukiama',
                                'attended' => 'Atvyko',
                                'missed' => 'Neatvyko'
                            ];
                            echo esc_html($attendance_labels[$booking->attendance] ?? $booking->attendance);
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($booking->status === 'confirmed') : ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pm-bookings&cancel_booking=' . $booking->id), 'pm_cancel_booking_' . $booking->id)); ?>"
                               class="button button-small"
                               onclick="return confirm('Ar tikrai norite atšaukti šią rezervaciją?')">
                                Atšaukti
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pm-bookings&delete_booking=' . $booking->id), 'pm_delete_booking_' . $booking->id)); ?>"
                           class="button button-small"
                           onclick="return confirm('Ar tikrai norite ištrinti šią rezervaciją? Šio veiksmo negalima atšaukti.')">
                            Ištrinti
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
