<div class="wrap">
    <h1>Klasės</h1>
    
    <?php if (!get_option('pm_demo_data_created')): ?>
    <div class="notice notice-info">
        <p>
            <strong>Demo duomenys:</strong> Norėdami išbandyti sistemą, galite sukurti demonstracinius duomenis.
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=parent-meetings&create_demo=1'), 'pm_create_demo'); ?>" 
               class="button button-secondary"
               onclick="return confirm('Ar tikrai norite sukurti demo duomenis?\n\nTai sukurs:\n- 12 klasių (1A-6B)\n- 4 mokytojus\n- Susitikimo laikus 5 darbo dienoms\n- 3 rezervacijas');">
                Sukurti Demo Duomenis
            </a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="pm-admin-section">
        <h2>Pridėti naują klasę</h2>
        <form method="post">
            <?php wp_nonce_field('pm_class_action'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="class_name">Klasės pavadinimas</label></th>
                    <td>
                        <input type="text" id="class_name" name="class_name" class="regular-text" required>
                        <p class="description">Pvz: 1A, 2B, 9C</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="pm_add_class" class="button button-primary" value="Pridėti Klasę">
            </p>
        </form>
    </div>
    
    <div class="pm-admin-section">
        <h2>Esamos Klasės</h2>
        <?php if (empty($classes)): ?>
            <p>Klasių nėra. Pridėkite naują klasę.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pavadinimas</th>
                        <th>Projektai</th>
                        <th>Sukurta</th>
                        <th>Veiksmai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class):
                        // Get class's projects
                        $class_projects = PM_Projects::get_class_projects($class->id);
                        $project_names = array_map(function($p) { return esc_html($p->name); }, $class_projects);
                    ?>
                    <tr>
                        <td><?php echo absint($class->id); ?></td>
                        <td><strong><?php echo esc_html($class->name); ?></strong></td>
                        <td>
                            <?php if (!empty($project_names)) : ?>
                                <?php foreach ($project_names as $pname) : ?>
                                    <span class="pm-badge" style="display: inline-block; background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 3px; margin: 2px; font-size: 11px;"><?php echo $pname; ?></span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($class->created_at))); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=parent-meetings&delete=' . $class->id), 'pm_delete_class_' . $class->id); ?>"
                               class="button button-small"
                               onclick="return confirm('Ar tikrai norite ištrinti šią klasę?');">Ištrinti</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
