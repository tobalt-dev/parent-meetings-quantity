<?php
/**
 * Parent Meetings Quantity Mode - Booking Form Template
 * Author: Tobalt — https://tobalt.lt
 * Scandinavian Minimalistic Design with Inline Styles
 */

// Get project configuration if project_id is set
$project = null;
$form_fields = null;
$show_class_selection = true;
$booking_mode = 'quantity'; // Always quantity mode
$daily_capacity = 15;

if (isset($booking_project_id) && $booking_project_id > 0) {
    $project = PM_Projects::get($booking_project_id);
    if ($project) {
        $form_fields = PM_Projects::parse_form_config($project->form_fields_config);
        $show_class_selection = (bool) $project->show_class_selection;
        $daily_capacity = $project->daily_capacity ?? 15;
    }
}

// Output CSS only once per page load
static $styles_outputted = false;
$form_id = 'pm-booking-form-' . ($booking_project_id ?? 0);
?>

<?php if (!$styles_outputted) : $styles_outputted = true; ?>
<style>
/* Reset and Base Styles - Scandinavian Minimalistic */
.pm-booking-form *,
.pm-booking-form *::before,
.pm-booking-form *::after {
    box-sizing: border-box !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif !important;
    line-height: 1.6 !important;
}

/* Reset only specific elements, not everything */
.pm-booking-form h1,
.pm-booking-form h2,
.pm-booking-form h3,
.pm-booking-form h4,
.pm-booking-form h5,
.pm-booking-form h6,
.pm-booking-form p,
.pm-booking-form ul,
.pm-booking-form ol,
.pm-booking-form li {
    margin: 0 !important;
    padding: 0 !important;
}

.pm-booking-form {
    max-width: 680px !important;
    margin: 60px auto !important;
    padding: 0 !important;
    background: transparent !important;
    font-size: 16px !important;
    color: #2c3e50 !important;
}

/* Step Container */
.pm-step {
    display: none !important;
    background: #ffffff !important;
    padding: 48px 40px !important;
    margin-bottom: 24px !important;
    border-radius: 2px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06) !important;
    border: 1px solid #e8ecef !important;
}

.pm-step-active {
    display: block !important;
    animation: pmFadeIn 0.3s ease-in !important;
}

@keyframes pmFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.pm-step h3 {
    font-size: 20px !important;
    font-weight: 600 !important;
    color: #1a1a1a !important;
    margin: 0 0 32px 0 !important;
    padding: 0 !important;
    letter-spacing: -0.02em !important;
    line-height: 1.3 !important;
}

/* Form Groups */
.pm-form-group {
    margin-bottom: 24px !important;
}

.pm-form-group label {
    display: block !important;
    margin-bottom: 8px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #4a5568 !important;
    letter-spacing: 0 !important;
}

/* Input Fields */
.pm-input {
    width: 100% !important;
    padding: 14px 16px !important;
    border: 1px solid #d4dae0 !important;
    border-radius: 2px !important;
    font-size: 15px !important;
    color: #2c3e50 !important;
    background: #ffffff !important;
    transition: all 0.2s ease !important;
    outline: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
}

.pm-input:focus {
    border-color: #607d8b !important;
    box-shadow: 0 0 0 3px rgba(96, 125, 139, 0.08) !important;
}

.pm-input::placeholder {
    color: #a0aec0 !important;
}

textarea.pm-input {
    resize: vertical !important;
    min-height: 100px !important;
}

/* List Grid (Classes, Teachers) */
.pm-list {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important;
    gap: 12px !important;
    margin-top: 0 !important;
}

.pm-list-item {
    padding: 20px 16px !important;
    background: #f7f9fa !important;
    border: 1px solid #e8ecef !important;
    border-radius: 2px !important;
    cursor: pointer !important;
    text-align: center !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #4a5568 !important;
    transition: all 0.15s ease !important;
}

.pm-list-item:hover {
    background: #ffffff !important;
    border-color: #607d8b !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.pm-list-item.pm-selected {
    background: #607d8b !important;
    color: #ffffff !important;
    border-color: #607d8b !important;
    box-shadow: 0 2px 8px rgba(96, 125, 139, 0.2) !important;
}

/* Meeting Type Selection */
.pm-type-select {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 16px !important;
}

.pm-type-option {
    padding: 24px 20px !important;
    background: #f7f9fa !important;
    border: 1px solid #e8ecef !important;
    border-radius: 2px !important;
    cursor: pointer !important;
    text-align: center !important;
    transition: all 0.15s ease !important;
    display: block !important;
}

.pm-type-option input[type="radio"] {
    display: none !important;
}

.pm-type-option span {
    font-size: 15px !important;
    font-weight: 500 !important;
    color: #4a5568 !important;
    display: block !important;
}

.pm-type-option:hover {
    background: #ffffff !important;
    border-color: #607d8b !important;
    transform: translateY(-1px) !important;
}

.pm-type-option:has(input[type="radio"]:checked) {
    background: #607d8b !important;
    border-color: #607d8b !important;
    box-shadow: 0 2px 8px rgba(96, 125, 139, 0.2) !important;
}

.pm-type-option:has(input[type="radio"]:checked) span {
    color: #ffffff !important;
}

/* Calendar */
.pm-calendar {
    display: flex !important;
    flex-direction: column !important;
    gap: 16px !important;
    margin-top: 0 !important;
}

.pm-teacher-message {
    background: #f0f4f8 !important;
    border-left: 3px solid #607d8b !important;
    padding: 20px 24px !important;
    border-radius: 2px !important;
    margin-bottom: 24px !important;
    color: #2c3e50 !important;
}

.pm-teacher-message h3,
.pm-teacher-message h4,
.pm-teacher-message h5 {
    margin: 0 0 12px 0 !important;
    color: #1a1a1a !important;
    font-size: 16px !important;
    font-weight: 600 !important;
}

.pm-teacher-message p {
    margin: 8px 0 !important;
    color: #4a5568 !important;
    font-size: 14px !important;
}

.pm-teacher-message ul,
.pm-teacher-message ol {
    margin: 8px 0 !important;
    padding-left: 20px !important;
    color: #4a5568 !important;
}

/* Day Columns */
.pm-day-column {
    background: #ffffff !important;
    border: 1px solid #e8ecef !important;
    border-radius: 2px !important;
    overflow: hidden !important;
    transition: box-shadow 0.15s ease !important;
}

.pm-day-column:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.pm-day-header {
    padding: 16px 20px !important;
    background: #607d8b !important;
    color: #ffffff !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.pm-day-name {
    font-size: 15px !important;
    font-weight: 600 !important;
    letter-spacing: 0 !important;
}

.pm-day-date {
    font-size: 13px !important;
    opacity: 0.9 !important;
    font-weight: 400 !important;
}

/* Time Slots */
.pm-slots-list {
    padding: 16px !important;
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)) !important;
    gap: 8px !important;
}

.pm-slot-btn {
    padding: 12px 8px !important;
    background: #f7f9fa !important;
    border: 1px solid #e8ecef !important;
    border-radius: 2px !important;
    cursor: pointer !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #4a5568 !important;
    transition: all 0.15s ease !important;
    text-align: center !important;
}

.pm-slot-btn:hover {
    background: #ffffff !important;
    border-color: #607d8b !important;
    transform: translateY(-1px) !important;
}

.pm-slot-btn.pm-selected {
    background: #607d8b !important;
    color: #ffffff !important;
    border-color: #607d8b !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 6px rgba(96, 125, 139, 0.25) !important;
}

/* Buttons */
.pm-button {
    padding: 14px 32px !important;
    border: none !important;
    border-radius: 2px !important;
    font-size: 15px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    display: inline-block !important;
    text-decoration: none !important;
    letter-spacing: 0 !important;
}

.pm-button-primary {
    background: #607d8b !important;
    color: #ffffff !important;
}

.pm-button-primary:hover {
    background: #546e7a !important;
    box-shadow: 0 2px 8px rgba(96, 125, 139, 0.25) !important;
    transform: translateY(-1px) !important;
}

/* Author: Tobalt — https://tobalt.lt */
/* Back Button */
.pm-back-button {
    background: transparent !important;
    color: #607d8b !important;
    border: 1px solid #e8ecef !important;
    padding: 12px 24px !important;
    border-radius: 2px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    display: inline-block !important;
    margin-top: 24px !important;
    text-decoration: none !important;
}

.pm-back-button:hover {
    background: #f8f9fa !important;
    border-color: #607d8b !important;
    color: #546e7a !important;
}

.pm-form-actions {
    display: flex !important;
    gap: 12px !important;
    align-items: center !important;
    margin-top: 24px !important;
}

.pm-form-actions .pm-back-button {
    margin-top: 0 !important;
}

/* Confirmation Screen - Centered modal */
.pm-confirmation {
    text-align: center !important;
    padding: 60px 40px !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #e8ecef !important;
    max-width: 480px !important;
    margin: 40px auto !important;
}

.pm-success-icon {
    width: 72px !important;
    height: 72px !important;
    margin: 0 auto 24px !important;
    background: #607d8b !important;
    color: #ffffff !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 40px !important;
}

.pm-confirmation h2 {
    color: #1a1a1a !important;
    margin: 0 0 12px 0 !important;
    font-size: 24px !important;
    font-weight: 600 !important;
    letter-spacing: -0.02em !important;
}

.pm-confirmation p {
    color: #4a5568 !important;
    font-size: 15px !important;
    margin: 0 !important;
}

/* Loading Overlay */
.pm-loading {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(255, 255, 255, 0.95) !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 99999 !important;
}

.pm-loading[style*="display: block"],
.pm-loading[style*="display:block"] {
    display: flex !important;
}

.pm-spinner {
    width: 48px !important;
    height: 48px !important;
    border: 3px solid #e8ecef !important;
    border-top: 3px solid #607d8b !important;
    border-radius: 50% !important;
    animation: pmSpin 0.8s linear infinite !important;
}

@keyframes pmSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Empty State */
.pm-empty {
    padding: 40px 20px !important;
    text-align: center !important;
    color: #a0aec0 !important;
    font-size: 14px !important;
}

/* Quantity Mode Date Selection */
.pm-quantity-dates {
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
}

.pm-quantity-date-card {
    background: #ffffff !important;
    border: 1px solid #e8ecef !important;
    border-radius: 2px !important;
    padding: 20px !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.pm-quantity-date-card:hover {
    border-color: #607d8b !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.pm-quantity-date-card.pm-selected {
    background: #607d8b !important;
    border-color: #607d8b !important;
}

.pm-quantity-date-card.pm-selected .pm-date-info,
.pm-quantity-date-card.pm-selected .pm-availability-info {
    color: #ffffff !important;
}

.pm-quantity-date-card.pm-full {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
}

.pm-date-info {
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #1a1a1a !important;
}

.pm-availability-info {
    font-size: 14px !important;
    color: #607d8b !important;
    font-weight: 500 !important;
}

.pm-availability-info.pm-low {
    color: #e67e22 !important;
}

.pm-availability-info.pm-full {
    color: #e74c3c !important;
}

/* Notice */
.pm-notice {
    padding: 16px 20px !important;
    border-radius: 2px !important;
    background: #fef5e7 !important;
    color: #856404 !important;
    border-left: 3px solid #f39c12 !important;
    margin-bottom: 20px !important;
    font-size: 14px !important;
}

/* Mobile Responsive */
@media (max-width: 640px) {
    .pm-booking-form {
        margin: 24px auto !important;
    }

    .pm-step {
        padding: 32px 24px !important;
        margin-bottom: 16px !important;
    }

    .pm-step h3 {
        font-size: 18px !important;
        margin-bottom: 24px !important;
    }

    .pm-list {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }

    .pm-type-select {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
    }

    .pm-slots-list {
        grid-template-columns: repeat(auto-fill, minmax(75px, 1fr)) !important;
        gap: 6px !important;
    }

    .pm-slot-btn {
        padding: 10px 6px !important;
        font-size: 12px !important;
    }

    .pm-confirmation {
        padding: 60px 24px !important;
    }
}
</style>
<?php endif; ?>

<div class="pm-booking-form" id="<?php echo esc_attr($form_id); ?>" data-project-id="<?php echo esc_attr($booking_project_id ?? 0); ?>" data-booking-mode="<?php echo esc_attr($booking_mode); ?>" data-daily-capacity="<?php echo esc_attr($daily_capacity); ?>">

    <?php if ($show_class_selection) : ?>
    <div class="pm-step pm-step-active" data-step="1">
        <h3>1. Pasirinkite klasę</h3>
        <div id="pm-class-list-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-list pm-class-list"></div>
    </div>
    <?php endif; ?>

    <div class="pm-step <?php echo !$show_class_selection ? 'pm-step-active' : ''; ?>" data-step="2">
        <h3>2. Pasirinkite mokytoją</h3>
        <div id="pm-teacher-list-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-list pm-teacher-list"></div>
        <?php if ($show_class_selection) : ?>
        <button type="button" class="pm-back-button" data-back-to="1">← Atgal į klasių pasirinkimą</button>
        <?php endif; ?>
    </div>

    <!-- QUANTITY MODE: Date/position selection -->
    <div class="pm-step" data-step="3">
        <h3>3. Pasirinkite datą</h3>
        <div id="pm-teacher-message-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-teacher-message" style="display:none;"></div>
        <div id="pm-quantity-calendar-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-quantity-dates"></div>
        <button type="button" class="pm-back-button" data-back-to="2">← Atgal į mokytojų pasirinkimą</button>
    </div>

    <div class="pm-step" data-step="4">
        <h3>4. Jūsų duomenys</h3>
        <form id="pm-final-form-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-final-form">
            <?php
            // Default form fields configuration
            if (!$form_fields) {
                $form_fields = [
                    'parent_name' => ['enabled' => true, 'required' => true, 'label' => 'Vardas Pavardė'],
                    'parent_email' => ['enabled' => true, 'required' => true, 'label' => 'El. paštas'],
                    'parent_phone' => ['enabled' => true, 'required' => false, 'label' => 'Telefonas'],
                    'student_name' => ['enabled' => true, 'required' => false, 'label' => 'Mokinio vardas'],
                    'notes' => ['enabled' => false, 'required' => false, 'label' => 'Pastabos']
                ];
            }

            // Render form fields based on configuration
            foreach ($form_fields as $field_key => $field_config) :
                if (!$field_config['enabled']) continue;

                $required = $field_config['required'] ? 'required' : '';
                $required_mark = $field_config['required'] ? ' *' : '';
                $label = esc_html($field_config['label']) . $required_mark;

                $input_type = ($field_key === 'parent_email') ? 'email' : (($field_key === 'parent_phone') ? 'tel' : 'text');

                if ($field_key === 'notes') :
            ?>
                    <div class="pm-form-group">
                        <label><?php echo $label; ?></label>
                        <textarea name="<?php echo esc_attr($field_key); ?>" class="pm-input" rows="3" <?php echo $required; ?>></textarea>
                    </div>
            <?php else : ?>
                    <div class="pm-form-group">
                        <label><?php echo $label; ?></label>
                        <input type="<?php echo esc_attr($input_type); ?>" name="<?php echo esc_attr($field_key); ?>" class="pm-input" <?php echo $required; ?>>
                    </div>
            <?php
                endif;
            endforeach;
            ?>

            <input type="hidden" name="slot_id" id="pm-selected-slot-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-selected-slot">
            <input type="hidden" name="class_id" id="pm-selected-class-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-selected-class">
            <input type="hidden" name="meeting_type" id="pm-selected-type-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-selected-type" value="quantity">
            <input type="hidden" name="project_id" id="pm-selected-project-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-selected-project" value="<?php echo esc_attr($booking_project_id ?? 0); ?>">
            <input type="hidden" name="position_number" id="pm-position-number-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-position-number" value="">
            <input type="hidden" name="booking_mode" value="quantity">

            <div class="pm-form-actions">
                <button type="button" class="pm-back-button" data-back-to="3">← Atgal į datos pasirinkimą</button>
                <button type="submit" class="pm-button pm-button-primary">Rezervuoti</button>
            </div>
        </form>
    </div>

    <div id="pm-confirmation-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-confirmation" style="display:none;">
        <div class="pm-success-icon">✓</div>
        <h2>Susitikimas sėkmingai užregistruotas!</h2>
        <p>Patvirtinimas išsiųstas į jūsų el. paštą.</p>
    </div>

    <div id="pm-loading-<?php echo esc_attr($booking_project_id ?? 0); ?>" class="pm-loading" style="display:none;">
        <div class="pm-spinner"></div>
    </div>
</div>

<script>
// Pass project configuration to frontend.js
(function($) {
    var projectId = <?php echo intval($booking_project_id ?? 0); ?>;
    var formId = '<?php echo esc_js($form_id); ?>';

    // Ensure loading is hidden on page load
    $('#pm-loading-' + projectId).hide();

    // Wait for pmFrontend to be available
    var initAttempts = 0;
    var maxAttempts = 50; // 5 seconds max

    function initBookingForm() {
        if (typeof pmFrontend === 'undefined') {
            initAttempts++;
            if (initAttempts < maxAttempts) {
                setTimeout(initBookingForm, 100);
                return;
            } else {
                $('#pm-loading-' + projectId).hide();
                $('#' + formId).html('<div style="padding: 20px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24;">Klaida: Sistema nepasikrovė. Prašome perkrauti puslapį arba susisiekti su administratoriumi.</div>');
                return;
            }
        }

        // Store project_id and form_id in the booking form container for JS access
        $('#' + formId).data('project-id', projectId);
        $('#' + formId).data('form-id', formId);
    }

    $(document).ready(function() {
        initBookingForm();
    });
})(jQuery);
</script>
