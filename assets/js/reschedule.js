jQuery(document).ready(function($) {
    // Enable submit button when slot is selected
    $('input[name="new_slot_id"]').on('change', function() {
        $('#pm-reschedule-submit').prop('disabled', false);

        // Visual feedback
        $('.pm-slot-item').removeClass('selected');
        $(this).closest('.pm-slot-item').addClass('selected');
    });

    // Handle form submission
    $('#pm-reschedule-form').on('submit', function(e) {
        e.preventDefault();

        var newSlotId = $('input[name="new_slot_id"]:checked').val();

        if (!newSlotId) {
            showMessage('Please select a new time slot.', 'error');
            return;
        }

        // Disable submit button
        $('#pm-reschedule-submit').prop('disabled', true).text('Rescheduling...');

        $.ajax({
            url: pmReschedule.ajaxurl,
            type: 'POST',
            data: {
                action: 'pm_reschedule_booking',
                nonce: pmReschedule.nonce,
                booking_id: pmReschedule.booking_id,
                new_slot_id: newSlotId,
                cancel_token: pmReschedule.cancel_token
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = window.location.pathname + '?rescheduled=1';
                    }, 2000);
                } else {
                    showMessage(response.data || 'Failed to reschedule. Please try again.', 'error');
                    $('#pm-reschedule-submit').prop('disabled', false).text('Confirm Reschedule');
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
                $('#pm-reschedule-submit').prop('disabled', false).text('Confirm Reschedule');
            }
        });
    });

    function showMessage(message, type) {
        var messageClass = type === 'success' ? 'pm-notice-success' : 'pm-notice-error';
        var messageHtml = '<div class="pm-notice ' + messageClass + '"><p>' + message + '</p></div>';

        $('#pm-reschedule-message').html(messageHtml);

        // Scroll to message
        $('html, body').animate({
            scrollTop: $('#pm-reschedule-message').offset().top - 20
        }, 300);
    }

    // Show success message if redirected after reschedule
    if (window.location.search.indexOf('rescheduled=1') !== -1) {
        showMessage('Meeting rescheduled successfully! You will receive a confirmation email.', 'success');

        // Hide the form
        $('#pm-reschedule-form').hide();
        $('.pm-current-booking').html('<h3>✓ Meeting Rescheduled</h3><p>Check your email for confirmation.</p>');
    }
});
