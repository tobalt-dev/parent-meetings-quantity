<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Susitikimų Sąrašas - <?php echo esc_html($teacher->first_name . ' ' . $teacher->last_name); ?></title>
    <link rel="stylesheet" href="<?php echo PM_PLUGIN_URL; ?>assets/css/print.css">
    <script src="<?php echo includes_url('js/jquery/jquery.min.js'); ?>"></script>
</head>
<body class="pm-print-page">
    <div class="pm-print-container">
        <header class="pm-print-header">
            <h1>Susitikimų Sąrašas</h1>
            <p><strong>Mokytojas:</strong> <?php echo esc_html($teacher->first_name . ' ' . $teacher->last_name); ?></p>
            <p><strong>Data:</strong> <?php echo date_i18n(get_option('date_format')); ?></p>
        </header>
        
        <?php if (empty($bookings)): ?>
            <p class="pm-no-bookings">Artimiausiomis dienomis susitikimų nėra.</p>
        <?php else: ?>
            <table class="pm-print-table">
                <thead>
                    <tr>
                        <th>Projektas</th>
                        <th>Laikas</th>
                        <th>Tėvas/Mama</th>
                        <th>Mokinys/Klasė</th>
                        <th>Tipas</th>
                        <th>Pastabos</th>
                        <th class="pm-no-print">Būsena</th>
                        <th class="pm-no-print">Veiksmai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $current_date = '';
                    foreach ($bookings as $booking):
                        $booking_date = date_i18n(get_option('date_format'), strtotime($booking->start_time));
                        if ($booking_date !== $current_date):
                            $current_date = $booking_date;
                    ?>
                        <tr class="pm-date-separator">
                            <td colspan="8"><strong><?php echo esc_html($current_date); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <tr data-booking-id="<?php echo absint($booking->id); ?>" class="<?php echo $booking->status === 'cancelled' ? 'pm-booking-cancelled' : ''; ?>">
                        <td><small><?php echo esc_html($booking->project_name ?: '-'); ?></small></td>
                        <td class="pm-time">
                            <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($booking->start_time)) . ' - ' . date_i18n(get_option('time_format'), strtotime($booking->end_time))); ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($booking->parent_name); ?></strong>
                            <?php if ($booking->status === 'cancelled'): ?>
                                <br><span class="pm-cancelled-badge">ATŠAUKTA</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            echo esc_html($booking->student_name ?: '-');
                            if ($booking->class_name) {
                                echo ' / ' . esc_html($booking->class_name);
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(ucfirst($booking->meeting_type)); ?></td>
                        <td class="pm-notes"></td>
                        <td class="pm-attendance pm-no-print">
                            <?php if ($booking->status === 'cancelled'): ?>
                                <span class="pm-status-label" style="background:#fff3cd;color:#856404;">Atšaukta</span>
                            <?php else: ?>
                            <div class="pm-attendance-buttons" data-booking="<?php echo absint($booking->id); ?>" data-status="<?php echo esc_attr($booking->attendance); ?>">
                                <?php if ($booking->attendance === 'pending'): ?>
                                    <button type="button" class="pm-attendance-btn pm-btn-attended" data-action="attended">✓ Atvyko</button>
                                    <button type="button" class="pm-attendance-btn pm-btn-missed" data-action="missed">✗ Neatvyko</button>
                                <?php elseif ($booking->attendance === 'attended'): ?>
                                    <span class="pm-status-label pm-status-attended">✓ Atvyko</span>
                                    <button type="button" class="pm-attendance-btn pm-btn-undo" data-action="pending">Atšaukti</button>
                                <?php else: ?>
                                    <span class="pm-status-label pm-status-missed">✗ Neatvyko</span>
                                    <button type="button" class="pm-attendance-btn pm-btn-undo" data-action="pending">Atšaukti</button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="pm-actions pm-no-print">
                            <?php if ($booking->status === 'confirmed'): ?>
                                <button type="button" class="pm-cancel-btn" data-booking="<?php echo absint($booking->id); ?>">✕ Atšaukti</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="pm-print-footer pm-no-print">
            <button onclick="window.print()" class="pm-button pm-button-primary">Spausdinti</button>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.pm-attendance-buttons').on('click', '.pm-attendance-btn', function() {
            var btn = $(this);
            var container = btn.closest('.pm-attendance-buttons');
            var bookingId = container.data('booking');
            var newStatus = btn.data('action');
            var token = '<?php echo esc_js($token); ?>';

            btn.prop('disabled', true);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'pm_set_attendance',
                    booking_id: bookingId,
                    status: newStatus,
                    token: token,
                    nonce: '<?php echo wp_create_nonce('pm_frontend'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var html = '';
                        if (newStatus === 'pending') {
                            html = '<button type="button" class="pm-attendance-btn pm-btn-attended" data-action="attended">✓ Atvyko</button>' +
                                   '<button type="button" class="pm-attendance-btn pm-btn-missed" data-action="missed">✗ Neatvyko</button>';
                        } else if (newStatus === 'attended') {
                            html = '<span class="pm-status-label pm-status-attended">✓ Atvyko</span>' +
                                   '<button type="button" class="pm-attendance-btn pm-btn-undo" data-action="pending">Atšaukti</button>';
                        } else {
                            html = '<span class="pm-status-label pm-status-missed">✗ Neatvyko</span>' +
                                   '<button type="button" class="pm-attendance-btn pm-btn-undo" data-action="pending">Atšaukti</button>';
                        }
                        container.html(html);
                        container.data('status', newStatus);
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });

        // Teacher cancel booking
        $('.pm-cancel-btn').on('click', function() {
            var btn = $(this);
            var bookingId = btn.data('booking');
            var row = btn.closest('tr');

            if (!confirm('Ar tikrai norite atšaukti šią rezervaciją? Tėvas bus informuotas el. paštu.')) {
                return;
            }

            btn.prop('disabled', true).text('...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'pm_teacher_cancel_booking',
                    booking_id: bookingId,
                    token: '<?php echo esc_js($token); ?>',
                    nonce: '<?php echo wp_create_nonce('pm_frontend'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        row.addClass('pm-booking-cancelled');
                        row.find('td').css('text-decoration', 'line-through');
                        row.find('.pm-attendance-buttons').html('<span class="pm-status-label" style="background:#fff3cd;color:#856404;">Atšaukta</span>');
                        row.find('.pm-cancelled-badge').length || row.find('td:eq(2) strong').after('<br><span class="pm-cancelled-badge">ATŠAUKTA</span>');
                        btn.remove();
                    } else {
                        alert(response.data || 'Klaida atšaukiant rezervaciją');
                        btn.prop('disabled', false).text('✕ Atšaukti');
                    }
                },
                error: function() {
                    alert('Klaida atšaukiant rezervaciją');
                    btn.prop('disabled', false).text('✕ Atšaukti');
                }
            });
        });
    });
    </script>
</body>
</html>
