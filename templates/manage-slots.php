<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tvarkyti laikus</title>
    <link rel="stylesheet" href="<?php echo PM_PLUGIN_URL; ?>assets/css/teacher.css">
    <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
</head>
<body class="pm-teacher-page">
    <div class="pm-container">
        <header class="pm-header">
            <h1>Tvarkyti individualius laikus</h1>
            <p><a href="?pm_action=manage&token=<?php echo esc_attr($token); ?>">&larr; Grįžti atgal</a></p>
        </header>
        
        <?php if (!empty($message)): ?>
        <div class="pm-section pm-message-preview">
            <h3>Žinutė tėvams:</h3>
            <div class="pm-message-content">
                <?php echo wp_kses_post($message); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="pm-section">
            <p class="pm-help-text">
                <strong>Instrukcijos:</strong> Paspauskite ant laiko, kad paslėptumėte jį nuo tėvų (pvz: pertrauka, ilgesnis susitikimas). 
                Paslėpti laikai bus rodomi pilkai. Užimti laikai negali būti paslėpti.
            </p>
            
            <div class="pm-slots-grid">
                <?php 
                $current_date = '';
                foreach ($slots as $slot): 
                    $slot_date = date('Y-m-d', strtotime($slot->start_time));
                    if ($slot_date !== $current_date):
                        if ($current_date !== '') echo '</div>'; // Close previous day
                        $current_date = $slot_date;
                ?>
                    <div class="pm-day-slots">
                        <h3 class="pm-day-title"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($slot_date))); ?></h3>
                        <div class="pm-slots-list-grid">
                <?php endif; ?>

                        <button
                            class="pm-slot-item pm-slot-<?php echo esc_attr($slot->status); ?> <?php echo $slot->is_hidden ? 'pm-slot-hidden' : ''; ?>"
                            data-slot-id="<?php echo absint($slot->id); ?>"
                            data-status="<?php echo esc_attr($slot->status); ?>"
                            data-hidden="<?php echo absint($slot->is_hidden); ?>"
                            <?php echo $slot->status === 'booked' ? 'disabled' : ''; ?>
                            onclick="toggleSlotVisibility(<?php echo absint($slot->id); ?>, this)">
                            <span class="pm-slot-time">
                                <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($slot->start_time))); ?>
                            </span>
                            <span class="pm-slot-status-label">
                                <?php 
                                if ($slot->status === 'booked') {
                                    echo 'Užimtas';
                                } elseif ($slot->is_hidden) {
                                    echo 'Paslėptas';
                                } else {
                                    echo 'Matomas';
                                }
                                ?>
                            </span>
                        </button>
                
                <?php endforeach; ?>
                        </div>
                    </div>
                <?php if (!empty($slots)): ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function toggleSlotVisibility(slotId, button) {
        if (button.disabled) return;
        
        var isHidden = button.getAttribute('data-hidden') === '1';
        var newHidden = isHidden ? 0 : 1;
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'pm_toggle_slot_visibility',
                slot_id: slotId,
                hidden: newHidden,
                token: '<?php echo esc_js($token); ?>',
                nonce: '<?php echo wp_create_nonce('pm_frontend'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    button.setAttribute('data-hidden', newHidden);
                    if (newHidden) {
                        button.classList.add('pm-slot-hidden');
                        button.querySelector('.pm-slot-status-label').textContent = 'Paslėptas';
                    } else {
                        button.classList.remove('pm-slot-hidden');
                        button.querySelector('.pm-slot-status-label').textContent = 'Matomas';
                    }
                }
            }
        });
    }
    </script>
</body>
</html>
