<!--
/**
 * Admin Projects Template
 *
 * Author: Tobalt — https://tobalt.lt
 */
-->

<link rel="stylesheet" href="<?php echo esc_url(includes_url('css/dashicons.min.css')); ?>" type="text/css" media="all" />
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
    white-space: normal;
    word-wrap: break-word;
    min-width: 200px;
    max-width: 400px;
    width: max-content;
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
th .pm-help-tip::after {
    left: auto;
    right: 100%;
    margin-left: 0;
    margin-right: 10px;
}
th .pm-help-tip::before {
    left: auto;
    right: 100%;
    margin-left: 0;
    margin-right: 5px;
    border-right-color: transparent;
    border-left-color: #2c3338;
}
</style>

<?php
// Helper function for tooltips
function pm_help_tip($text) {
    return '<span class="dashicons dashicons-editor-help pm-help-tip" data-tip="' . esc_attr($text) . '" style="width: 18px; height: 18px; font-size: 18px;"></span><span class="pm-help-tip" data-tip="' . esc_attr($text) . '" style="display: inline-block; background: #fff; color: #000; width: 18px; height: 18px; line-height: 18px; text-align: center; border-radius: 50%; font-weight: bold; font-size: 12px; cursor: help; border: 2px solid #ddd;">?</span>';
}
?>

<div class="wrap">
    <h1>Projektai</h1>

    <p>Tvarkykite rezervacijų projektus. Kiekvienas projektas gali turėti savo mokytojus, klases ir formų konfigūraciją.</p>

    <!-- Add/Edit Project Form -->
    <div class="card" style="max-width: 800px; margin: 20px 0;">
        <h2><?php echo $edit_project ? 'Redaguoti projektą' : 'Pridėti naują projektą'; ?></h2>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'create_failed'): ?>
            <div class="notice notice-error">
                <p><strong>Klaida:</strong> Nepavyko sukurti projekto. Patikrinkite duomenų bazės leidimus ir bandykite dar kartą.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php
            if ($edit_project) {
                wp_nonce_field('pm_edit_project_' . $edit_project->id);
                echo '<input type="hidden" name="project_id" value="' . esc_attr($edit_project->id) . '">';
            } else {
                wp_nonce_field('pm_project_action');
            }

            $form_config = $edit_project ? PM_Projects::parse_form_config($edit_project->form_fields_config) : PM_Projects::parse_form_config(PM_Projects::get_default_form_config());
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="name">
                            Projekto Pavadinimas *
                            <?php echo pm_help_tip("Unikalus projekto pavadinimas, kuris padės atskirti skirtingus renginius. Pvz: 'Tėvų dienos 2025.12', 'Projektų pristatymai', 'Kabinetų rezervacijos'"); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="name" id="name" class="regular-text" required
                            value="<?php echo $edit_project ? esc_attr($edit_project->name) : ''; ?>">
                        <p class="description">Pvz: "Tėvų dienos 2025.12"</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="description">
                            Aprašymas
                            <?php echo pm_help_tip("Papildoma informacija apie projektą, kuri matoma tik administratoriams. Čia galite nurodyti projekto tikslą, datą ar kitas pastabas."); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="description" id="description" rows="3" class="large-text"><?php echo $edit_project ? esc_textarea($edit_project->description) : ''; ?></textarea>
                        <p class="description">Neprivalomas aprašymas vidaus naudojimui</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Mokytojai
                        <?php echo pm_help_tip("Pasirinkite mokytojus, kurie dalyvaus šiame projekte. Tik pasirinkti mokytojai galės kurti savo grafikus ir priimti rezervacijas šiam projektui."); ?>
                    </th>
                    <td>
                        <?php if (!empty($all_teachers)) : ?>
                            <fieldset>
                                <legend class="screen-reader-text">Pasirinkite mokytojus</legend>
                                <?php
                                $edit_teacher_ids = $edit_project ? array_column($edit_project_teachers, 'id') : [];
                                foreach ($all_teachers as $teacher) :
                                    $checked = in_array($teacher->id, $edit_teacher_ids) ? 'checked' : '';
                                ?>
                                    <label style="display: block; margin: 5px 0;">
                                        <input type="checkbox" name="teacher_ids[]" value="<?php echo esc_attr($teacher->id); ?>" <?php echo $checked; ?>>
                                        <?php echo esc_html($teacher->first_name . ' ' . $teacher->last_name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php else : ?>
                            <p class="description">Mokytojų nėra. Pirmiausia pridėkite mokytojus.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Klasės
                        <?php echo pm_help_tip("Pasirinkite klases, kurios dalyvaus šiame projekte. Tėvai galės pasirinkti savo vaiko klasę užpildydami rezervacijos formą."); ?>
                    </th>
                    <td>
                        <?php if (!empty($all_classes)) : ?>
                            <fieldset>
                                <legend class="screen-reader-text">Pasirinkite klases</legend>
                                <?php
                                $edit_class_ids = $edit_project ? array_column($edit_project_classes, 'id') : [];
                                foreach ($all_classes as $class) :
                                    $checked = in_array($class->id, $edit_class_ids) ? 'checked' : '';
                                ?>
                                    <label style="display: block; margin: 5px 0;">
                                        <input type="checkbox" name="class_ids[]" value="<?php echo esc_attr($class->id); ?>" <?php echo $checked; ?>>
                                        <?php echo esc_html($class->name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php else : ?>
                            <p class="description">Klasių nėra. Pirmiausia pridėkite klases.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Klasių Pasirinkimas
                        <?php echo pm_help_tip("Įjunkite šią parinktį tėvų susitikimams, kur svarbu žinoti mokinio klasę. Išjunkite kabinetų ar įrangos rezervacijoms, kur klasė nereikalinga."); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="show_class_selection" value="1"
                                <?php checked($edit_project ? $edit_project->show_class_selection : 1, 1); ?>>
                            Rodyti klasių pasirinkimą rezervacijos formoje
                        </label>
                        <p class="description">Atžymėkite kabinetų/įrangos rezervacijoms</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Vietų Kiekis
                        <?php echo pm_help_tip("Nustatykite, kiek registracijų galima vienam mokytojui per dieną. Tėvai matys laisvas pozicijas eilėje, pvz. 'Laisva 4 iš 15'."); ?>
                    </th>
                    <td>
                        <input type="hidden" name="booking_mode" value="quantity">
                        <div style="padding: 15px; background: #f0f7ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                            <p style="margin: 0 0 10px 0; color: #2271b1;"><strong>Kiekio režimas</strong> — tėvai renkasi poziciją eilėje, ne konkretų laiką</p>
                            <label for="daily_capacity">
                                <strong>Vietų skaičius per dieną:</strong>
                            </label>
                            <input type="number" name="daily_capacity" id="daily_capacity" min="1" max="100" style="width: 80px; margin-left: 10px;"
                                value="<?php echo esc_attr($edit_project ? ($edit_project->daily_capacity ?? 15) : 15); ?>">
                            <p class="description" style="margin-top: 8px;">Kiek registracijų vienam mokytojui galima per dieną (pvz. 15). Tėvai matys: "Laisva 4 iš 15"</p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Papildomas El. Paštas
                        <?php echo pm_help_tip("Įjunkite, jei norite, kad kopija visų pranešimų būtų siunčiama papildomu el. paštu (pvz. koordinatoriui ar administratoriui). Visi pranešimai, kurie siunčiami mokytojui, bus siunčiami ir šiuo adresu."); ?>
                    </th>
                    <td>
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" name="secondary_email_enabled" value="1" id="secondary_email_enabled"
                                <?php checked($edit_project ? $edit_project->secondary_email_enabled : 0, 1); ?>>
                            Siųsti pranešimus papildomu el. paštu
                        </label>
                        <label for="secondary_email" style="display: block;">
                            El. pašto adresas:
                        </label>
                        <input type="email" name="secondary_email" id="secondary_email" class="regular-text"
                            value="<?php echo esc_attr($edit_project ? $edit_project->secondary_email : ''); ?>"
                            placeholder="koordinatorius@mokykla.lt">
                        <p class="description">Šis el. paštas gaus kopijas visų pranešimų apie rezervacijas, atšaukimus ir perkėlimus.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        Formos Laukai
                        <?php echo pm_help_tip("Sukonfigūruokite rezervacijos formos laukus pagal savo poreikius. Galite pasirinkti kuriuos laukus rodyti, kurie yra privalomi, ir pakeisti jų pavadinimus."); ?>
                    </th>
                    <td>
                        <p class="description" style="margin-bottom: 10px;">Nustatykite, kuriuos laukus rodyti rezervacijos formoje:</p>

                        <table class="widefat" style="max-width: 600px;">
                            <thead>
                                <tr>
                                    <th>Laukas</th>
                                    <th>
                                        Įjungta
                                        <?php echo pm_help_tip("Pažymėkite, jei norite, kad šis laukas būtų rodomas formoje."); ?>
                                    </th>
                                    <th>
                                        Privaloma
                                        <?php echo pm_help_tip("Pažymėkite, jei šis laukas turi būti privalomas užpildyti."); ?>
                                    </th>
                                    <th>
                                        Pavadinimas
                                        <?php echo pm_help_tip("Pakeiskite lauko pavadinimą, kuris bus rodomas formoje."); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $default_fields = [
                                    'parent_name' => 'Tėvo/Mamos vardas',
                                    'student_name' => 'Mokinio vardas',
                                    'parent_email' => 'El. paštas',
                                    'parent_phone' => 'Telefonas',
                                    'notes' => 'Pastabos'
                                ];

                                foreach ($default_fields as $field_key => $field_label) :
                                    $field_config = $form_config[$field_key] ?? ['enabled' => true, 'required' => in_array($field_key, ['parent_name', 'parent_email']), 'label' => $field_label];
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($field_label); ?></strong></td>
                                    <td>
                                        <input type="checkbox"
                                            name="form_fields[<?php echo esc_attr($field_key); ?>][enabled]"
                                            value="1"
                                            <?php checked($field_config['enabled'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox"
                                            name="form_fields[<?php echo esc_attr($field_key); ?>][required]"
                                            value="1"
                                            <?php checked($field_config['required'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="text"
                                            name="form_fields[<?php echo esc_attr($field_key); ?>][label]"
                                            value="<?php echo esc_attr($field_config['label']); ?>"
                                            style="width: 100%;">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php if ($edit_project) : ?>
                    <button type="submit" name="pm_edit_project" class="button button-primary">
                        Atnaujinti projektą
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pm-projects')); ?>" class="button">
                        Atšaukti
                    </a>
                <?php else : ?>
                    <button type="submit" name="pm_add_project" class="button button-primary">
                        Pridėti projektą
                    </button>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <!-- Projects List -->
    <?php if (!$edit_project && !empty($projects)) : ?>
    <h2>Esami Projektai</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Pavadinimas</th>
                <th>Aprašymas</th>
                <th>Vietų kiekis</th>
                <th>Mokytojai</th>
                <th>Klasės</th>
                <th>Trumpasis kodas</th>
                <th>Veiksmai</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project) :
                $project_teachers = PM_Projects::get_teachers($project->id);
                $project_classes = PM_Projects::get_classes($project->id);
            ?>
            <tr>
                <td><strong><?php echo esc_html($project->name); ?></strong></td>
                <td><?php echo esc_html($project->description); ?></td>
                <td>
                    <span style="background:#e7f3ff; padding:2px 8px; border-radius:3px; font-size:12px;"><?php echo intval($project->daily_capacity ?? 15); ?> vietų/dieną</span>
                </td>
                <td><?php echo esc_html(count($project_teachers)); ?></td>
                <td><?php echo esc_html(count($project_classes)); ?></td>
                <td>
                    <code>[parent_meetings id="<?php echo esc_attr($project->id); ?>"]</code>
                    <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[parent_meetings id=&quot;<?php echo esc_js($project->id); ?>&quot;]')">
                        Kopijuoti
                    </button>
                </td>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pm-projects&edit=' . $project->id)); ?>" class="button button-small">
                        Redaguoti
                    </a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Ar tikrai norite ištrinti šį projektą?');">
                        <?php wp_nonce_field('pm_delete_project_' . $project->id); ?>
                        <input type="hidden" name="pm_delete_project" value="<?php echo esc_attr($project->id); ?>">
                        <button type="submit" class="button button-small">Ištrinti</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
