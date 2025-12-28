<div class="wrap">
    <h1>Mokytojai</h1>

    <?php
    // Show success/error messages
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Mokytojo duomenys sėkmingai atnaujinti!</p></div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>Klaida: nepavyko išsaugoti duomenų.</p></div>';
    }
    ?>

    <?php
    // Check if editing
    $edit_teacher = null;
    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        global $wpdb;
        $edit_teacher = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pm_teachers WHERE id = %d",
            $edit_id
        ));
    }
    ?>

    <div class="pm-admin-section">
        <h2><?php echo $edit_teacher ? 'Redaguoti mokytoją' : 'Pridėti naują mokytoją'; ?></h2>
        <form method="post">
            <?php
            if ($edit_teacher) {
                wp_nonce_field('pm_edit_teacher_' . $edit_teacher->id);
                echo '<input type="hidden" name="teacher_id" value="' . esc_attr($edit_teacher->id) . '">';
            } else {
                wp_nonce_field('pm_teacher_action');
            }
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="first_name">Vardas *</label></th>
                    <td><input type="text" id="first_name" name="first_name" class="regular-text" value="<?php echo esc_attr($edit_teacher ? $edit_teacher->first_name : ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="last_name">Pavardė *</label></th>
                    <td><input type="text" id="last_name" name="last_name" class="regular-text" value="<?php echo esc_attr($edit_teacher ? $edit_teacher->last_name : ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="email">El. paštas *</label></th>
                    <td><input type="email" id="email" name="email" class="regular-text" value="<?php echo esc_attr($edit_teacher ? $edit_teacher->email : ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="phone">Telefonas</label></th>
                    <td><input type="text" id="phone" name="phone" class="regular-text" value="<?php echo esc_attr($edit_teacher ? $edit_teacher->phone : ''); ?>"></td>
                </tr>
                <tr>
                    <th><label>Klasės (pasirinktinai)</label></th>
                    <td>
                        <p class="description" style="margin: 0 0 10px 0;">Palikite tuščią, jei mokytojas dirba su visomis klasėmis</p>
                        <?php
                        $edit_class_ids = $edit_teacher ? json_decode($edit_teacher->class_ids, true) : [];
                        foreach ($classes as $class):
                            $checked = in_array($class->id, $edit_class_ids ?: []) ? 'checked' : '';
                        ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="class_ids[]" value="<?php echo absint($class->id); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html($class->name); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th><label>Susitikimo tipai *</label></th>
                    <td>
                        <?php
                        $edit_types = $edit_teacher ? json_decode($edit_teacher->meeting_types, true) : [];
                        ?>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="meeting_types[]" value="vietoje" <?php checked(in_array('vietoje', $edit_types ?: [])); ?>>
                            Vietoje
                        </label>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="meeting_types[]" value="nuotoliu" <?php checked(in_array('nuotoliu', $edit_types ?: [])); ?>>
                            Nuotoliu
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="default_duration">Trukmė (min) *</label></th>
                    <td>
                        <input type="number" id="default_duration" name="default_duration" value="<?php echo esc_attr($edit_teacher ? $edit_teacher->default_duration : 15); ?>" min="5" max="120" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="buffer_time">Pertrauka (min)</label></th>
                    <td>
                        <input type="number" id="buffer_time" name="buffer_time" value="<?php echo esc_attr($edit_teacher ? $edit_teacher->buffer_time : 0); ?>" min="0" max="60">
                    </td>
                </tr>
                <tr>
                    <th><label>Priskirti Projektams</label></th>
                    <td>
                        <?php
                        global $wpdb;
                        $all_projects = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}pm_projects ORDER BY name");
                        $teacher_project_ids = $edit_teacher ? array_column(PM_Projects::get_teacher_projects($edit_teacher->id), 'id') : [];

                        if (!empty($all_projects)):
                            foreach ($all_projects as $project):
                                $checked = in_array($project->id, $teacher_project_ids) ? 'checked' : '';
                        ?>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="checkbox" name="project_ids[]" value="<?php echo absint($project->id); ?>" <?php echo $checked; ?>>
                                    <?php echo esc_html($project->name); ?>
                                </label>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <p class="description">Projektų nėra. Pirmiausia sukurkite projektą.</p>
                        <?php endif; ?>
                        <p class="description">Pasirinkite, kuriuose projektuose dalyvaus šis mokytojas.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <?php if ($edit_teacher): ?>
                    <input type="submit" name="pm_edit_teacher" class="button button-primary" value="Išsaugoti pakeitimus">
                    <a href="<?php echo admin_url('admin.php?page=pm-teachers'); ?>" class="button">Atšaukti</a>
                <?php else: ?>
                    <input type="submit" name="pm_add_teacher" class="button button-primary" value="Pridėti mokytoją">
                <?php endif; ?>
            </p>
        </form>
    </div>
    
    <div class="pm-admin-section">
        <h2>Esami Mokytojai</h2>
        <?php if (empty($teachers)): ?>
            <p>Mokytojų nėra. Pridėkite naują mokytoją.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vardas Pavardė</th>
                        <th>El. paštas</th>
                        <th>Klasės</th>
                        <th>Projektai</th>
                        <th>Tipai</th>
                        <th>Veiksmai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher):
                        $teacher_classes = json_decode($teacher->class_ids, true);
                        $class_names = [];
                        if ($teacher_classes) {
                            foreach ($classes as $class) {
                                if (in_array($class->id, $teacher_classes)) {
                                    $class_names[] = esc_html($class->name);
                                }
                            }
                        }
                        $types = json_decode($teacher->meeting_types, true);

                        // Get teacher's projects
                        $teacher_projects = PM_Projects::get_teacher_projects($teacher->id);
                        $project_names = array_map(function($p) { return esc_html($p->name); }, $teacher_projects);
                    ?>
                    <tr>
                        <td><?php echo absint($teacher->id); ?></td>
                        <td><strong><?php echo esc_html($teacher->first_name . ' ' . $teacher->last_name); ?></strong></td>
                        <td><?php echo esc_html($teacher->email); ?></td>
                        <td><?php echo implode(', ', $class_names); ?></td>
                        <td>
                            <?php if (!empty($project_names)) : ?>
                                <?php foreach ($project_names as $pname) : ?>
                                    <span class="pm-badge" style="display: inline-block; background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 3px; margin: 2px; font-size: 11px;"><?php echo $pname; ?></span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(implode(', ', $types ?: [])); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=pm-teachers&edit=' . $teacher->id); ?>"
                               class="button button-small button-primary">Redaguoti</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=pm-teachers&regenerate=' . $teacher->id), 'pm_regenerate_' . $teacher->id); ?>"
                               class="button button-small">Atnaujinti nuorodas</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Ar tikrai norite ištrinti šį mokytoją?');">
                                <?php wp_nonce_field('pm_delete_teacher_' . $teacher->id); ?>
                                <input type="hidden" name="pm_delete_teacher" value="<?php echo esc_attr($teacher->id); ?>">
                                <button type="submit" class="button button-small">Ištrinti</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
