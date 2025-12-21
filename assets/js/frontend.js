/**
 * Parent Meetings Quantity Mode - Frontend JavaScript
 * Author: Tobalt — https://tobalt.lt
 */

(function($) {
    'use strict';

    // Wait for both DOM and pmFrontend to be ready
    var initializationAttempts = 0;
    var maxAttempts = 50;

    function waitForPmFrontend() {
        if (typeof pmFrontend === 'undefined') {
            initializationAttempts++;
            if (initializationAttempts < maxAttempts) {
                setTimeout(waitForPmFrontend, 100);
            } else {
                // Error handling for all forms on page
                $('.pm-booking-form').each(function() {
                    var pid = $(this).data('project-id') || 0;
                    $('#pm-loading-' + pid).hide();
                    $(this).html('<div style="padding: 20px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24;">Klaida: Sistema nepasikrovė. Prašome perkrauti puslapį arba susisiekti su administratoriumi.</div>');
                });
            }
            return;
        }

        // Initialize all booking forms on the page
        $('.pm-booking-form').each(function() {
            initSingleBookingForm($(this));
        });
    }

    function initSingleBookingForm($formContainer) {
        var projectId = $formContainer.data('project-id') || 0;
        var dailyCapacity = $formContainer.data('daily-capacity') || 15;
        var selectedClass = null;
        var selectedTeacher = null;
        var selectedQuantitySlot = null;
        var allClasses = [];

        // Helper function to get element within this form with project-specific ID
        function getEl(baseId) {
            return $('#' + baseId + '-' + projectId);
        }

        // Hide loading spinner
        getEl('pm-loading').hide();

        // Check if class selection step exists in this form
        var hasClassSelection = $formContainer.find('.pm-step[data-step="1"]').length > 0;

        if (hasClassSelection) {
            // Load classes only if step 1 exists
            loadClasses();
        } else {
            // No class selection - load teachers directly
            goToStep(2);
            loadTeachers(0); // 0 means no class filter
        }

        function loadClasses() {
            $.ajax({
                url: pmFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pm_get_classes',
                    project_id: projectId,
                    nonce: pmFrontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        allClasses = response.data;
                        renderClasses(allClasses);
                    } else {
                        getEl('pm-class-list').html('<p class="pm-empty">Klasių nerasta</p>');
                    }
                },
                error: function(xhr, status, error) {
                    getEl('pm-class-list').html('<p class="pm-empty">Įvyko klaida. Bandykite perkrauti puslapį.</p>');
                }
            });
        }

        function renderClasses(classes) {
            const list = getEl('pm-class-list');
            list.empty();

            if (classes.length === 0) {
                list.html('<p class="pm-empty">Klasių nerasta</p>');
                return;
            }

            classes.forEach(function(cls) {
                const item = $('<div class="pm-list-item">')
                    .text(cls.name)
                    .data('class', cls)
                    .on('click', function() {
                        selectClass(cls);
                    });
                list.append(item);
            });
        }

        function selectClass(cls) {
            selectedClass = cls;
            getEl('pm-selected-class').val(cls.id);

            // Only affect class list items, not teacher list items
            getEl('pm-class-list').find('.pm-list-item').removeClass('pm-selected');
            getEl('pm-class-list').find('.pm-list-item').filter(function() {
                var classData = $(this).data('class');
                return classData && classData.id === cls.id;
            }).addClass('pm-selected');

            goToStep(2);
            loadTeachers(cls.id);
        }

        function loadTeachers(classId) {
            showLoading();

            $.ajax({
                url: pmFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pm_get_teachers',
                    class_id: classId,
                    project_id: projectId,
                    nonce: pmFrontend.nonce
                },
                success: function(response) {
                    hideLoading();

                    if (response.success) {
                        renderTeachers(response.data);
                    } else {
                        getEl('pm-teacher-list').html('<p class="pm-empty">' + (response.data || 'Mokytojų nerasta') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    showError('Įvyko klaida. Bandykite perkrauti puslapį.');
                }
            });
        }

        function renderTeachers(teachers) {
            const list = getEl('pm-teacher-list');
            list.empty();

            if (teachers.length === 0) {
                list.html('<p class="pm-empty">Šiai klasei mokytojų nėra arba neturi prieinamų dienų</p>');
                return;
            }

            teachers.forEach(function(teacher) {
                const item = $('<div class="pm-list-item">')
                    .html('<strong>' + teacher.first_name + ' ' + teacher.last_name + '</strong>')
                    .data('teacher', teacher)
                    .on('click', function() {
                        selectTeacher(teacher);
                    });
                list.append(item);
            });
        }

        function selectTeacher(teacher) {
            selectedTeacher = teacher;

            // Only affect teacher list items
            getEl('pm-teacher-list').find('.pm-list-item').removeClass('pm-selected');
            getEl('pm-teacher-list').find('.pm-list-item').filter(function() {
                var teacherData = $(this).data('teacher');
                return teacherData && teacherData.id === teacher.id;
            }).addClass('pm-selected');

            // Quantity mode - go directly to date selection
            getEl('pm-selected-type').val('quantity');
            goToStep(3);
            loadQuantitySlots(selectedTeacher.id);
        }

        function loadQuantitySlots(teacherId) {
            showLoading();

            $.ajax({
                url: pmFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pm_get_quantity_slots',
                    teacher_id: teacherId,
                    project_id: projectId,
                    nonce: pmFrontend.nonce
                },
                success: function(response) {
                    hideLoading();

                    if (response.success) {
                        renderQuantityCalendar(response.data.slots, response.data.message);
                    } else {
                        getEl('pm-quantity-calendar').html('<p class="pm-empty">Laisvų dienų nėra</p>');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    showError('Įvyko klaida. Bandykite perkrauti puslapį.');
                }
            });
        }

        function renderQuantityCalendar(slots, message) {
            const calendar = getEl('pm-quantity-calendar');
            calendar.empty();

            // Show teacher message if exists
            if (message) {
                getEl('pm-teacher-message').html(message).show();
            } else {
                getEl('pm-teacher-message').hide();
            }

            if (slots.length === 0) {
                calendar.append('<p class="pm-empty">Šiuo metu laisvų dienų nėra</p>');
                return;
            }

            const weekDays = ['Sekmadienis', 'Pirmadienis', 'Antradienis', 'Trečiadienis', 'Ketvirtadienis', 'Penktadienis', 'Šeštadienis'];
            const monthNames = ['Sausio', 'Vasario', 'Kovo', 'Balandžio', 'Gegužės', 'Birželio', 'Liepos', 'Rugpjūčio', 'Rugsėjo', 'Spalio', 'Lapkričio', 'Gruodžio'];

            slots.forEach(function(slot) {
                const dateObj = new Date(slot.date + 'T00:00:00');
                const dayName = weekDays[dateObj.getDay()];
                const day = dateObj.getDate();
                const month = monthNames[dateObj.getMonth()];

                const dateCard = $('<div class="pm-quantity-date-card">')
                    .data('slot', slot);

                const dateHeader = $('<div class="pm-quantity-date-header">');
                dateHeader.append('<div class="pm-quantity-day-name">' + dayName + '</div>');
                dateHeader.append('<div class="pm-quantity-day-date">' + day + ' ' + month + '</div>');
                dateCard.append(dateHeader);

                const availInfo = $('<div class="pm-quantity-available">');
                availInfo.html('<strong>Laisva:</strong> ' + slot.available + ' iš ' + slot.capacity);
                dateCard.append(availInfo);

                if (slot.available > 0) {
                    dateCard.addClass('pm-quantity-date-available');
                    dateCard.on('click', function() {
                        selectQuantitySlot(slot);
                    });
                } else {
                    dateCard.addClass('pm-quantity-date-full');
                    dateCard.append('<div class="pm-quantity-full-badge">Užimta</div>');
                }

                calendar.append(dateCard);
            });
        }

        function selectQuantitySlot(slot) {
            selectedQuantitySlot = slot;
            getEl('pm-selected-slot').val(slot.id);
            getEl('pm-position-number').val(''); // Position will be assigned by server

            $formContainer.find('.pm-quantity-date-card').removeClass('pm-selected');
            $formContainer.find('.pm-quantity-date-card').filter(function() {
                return $(this).data('slot').id === slot.id;
            }).addClass('pm-selected');

            goToStep(4); // Step 4 is the confirmation form
        }

        // Final form submission
        getEl('pm-final-form').on('submit', function(e) {
            e.preventDefault();

            if (!selectedQuantitySlot) {
                showError('Prašome pasirinkti dieną');
                return;
            }

            showLoading();

            // Check if reCAPTCHA is configured and loaded
            if (typeof grecaptcha !== 'undefined' && pmFrontend.recaptcha_key && pmFrontend.recaptcha_key !== 'YOUR_RECAPTCHA_SITE_KEY_HERE') {
                grecaptcha.ready(function() {
                    grecaptcha.execute(pmFrontend.recaptcha_key, {action: 'booking'}).then(function(token) {
                        submitBooking(token);
                    });
                });
            } else {
                // Submit without reCAPTCHA if not configured
                submitBooking('');
            }
        });

        function submitBooking(recaptchaToken) {
            const formData = getEl('pm-final-form').serializeArray();
            const data = {
                action: 'pm_book_meeting',
                nonce: pmFrontend.nonce,
                recaptcha_token: recaptchaToken,
                booking_mode: 'quantity'
            };

            formData.forEach(function(item) {
                data[item.name] = item.value;
            });

            $.ajax({
                url: pmFrontend.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    hideLoading();

                    if (response.success) {
                        showConfirmation();
                    } else {
                        var errorMsg = typeof response.data === 'string' ? response.data : 'Įvyko klaida';
                        showError(errorMsg);

                        // Reload slots if date was taken
                        if (errorMsg.includes('užpildyta') || errorMsg.includes('just booked')) {
                            loadQuantitySlots(selectedTeacher.id);
                            goToStep(3);
                        }
                    }
                },
                error: function() {
                    hideLoading();
                    showError('Įvyko klaida. Prašome bandyti dar kartą.');
                }
            });
        }

        function goToStep(step) {
            $formContainer.find('.pm-step').removeClass('pm-step-active');
            $formContainer.find('.pm-step[data-step="' + step + '"]').addClass('pm-step-active');

            // Show previous steps as completed, remove completed class from current and future steps
            $formContainer.find('.pm-step').each(function() {
                const stepNum = parseInt($(this).data('step'));
                if (stepNum < step) {
                    $(this).addClass('pm-step-completed');
                } else {
                    $(this).removeClass('pm-step-completed');
                }
            });

            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $formContainer.offset().top - 50
            }, 300);
        }

        function showConfirmation() {
            $formContainer.find('.pm-step').removeClass('pm-step-active');
            getEl('pm-confirmation').fadeIn();
            // Scroll to confirmation message
            $('html, body').animate({
                scrollTop: $formContainer.offset().top - 50
            }, 300);
        }

        function showLoading() {
            getEl('pm-loading').show();
        }

        function hideLoading() {
            getEl('pm-loading').hide();
        }

        function showError(message) {
            alert(message);
        }

        /**
         * Author: Tobalt — https://tobalt.lt
         * Handle back button navigation
         */
        $formContainer.on('click', '.pm-back-button', function() {
            const backToStep = parseInt($(this).data('back-to'));
            if (backToStep) {
                goToStep(backToStep);
            }
        });
    }

    // Start initialization when DOM is ready
    $(document).ready(function() {
        waitForPmFrontend();
    });

})(jQuery);
