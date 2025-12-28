/**
 * Analytics Dashboard JavaScript
 *
 * Author: Tobalt — https://tobalt.lt
 */

(function($) {
    'use strict';

    let currentPage = 1;
    let currentFilters = {
        dateFrom: null,
        dateTo: null,
        projectId: 0,
        granularity: 'daily'
    };

    // Charts instances
    let charts = {
        trends: null,
        status: null,
        attendance: null,
        teachers: null,
        heatmap: null
    };

    /**
     * Initialize analytics dashboard
     */
    window.pmAnalyticsInit = function() {
        initFilters();
        initDatePresets();
        initEventListeners();
        loadAnalytics();
    };

    /**
     * Initialize date filters
     */
    function initFilters() {
        const dateTo = new Date();
        const dateFrom = new Date();
        dateFrom.setDate(dateFrom.getDate() - 30);

        currentFilters.dateFrom = formatDate(dateFrom);
        currentFilters.dateTo = formatDate(dateTo);

        $('#pm-date-from').val(currentFilters.dateFrom);
        $('#pm-date-to').val(currentFilters.dateTo);
    }

    /**
     * Initialize date preset dropdown
     */
    function initDatePresets() {
        $('#pm-date-preset').on('change', function() {
            const preset = $(this).val();

            if (preset === 'custom') {
                $('.pm-custom-dates').slideDown(200);
                return;
            }

            $('.pm-custom-dates').slideUp(200);

            const dateTo = new Date();
            const dateFrom = new Date();

            switch(preset) {
                case '7':
                    dateFrom.setDate(dateFrom.getDate() - 7);
                    break;
                case '30':
                    dateFrom.setDate(dateFrom.getDate() - 30);
                    break;
                case '90':
                    dateFrom.setDate(dateFrom.getDate() - 90);
                    break;
                case '365':
                    dateFrom.setFullYear(dateFrom.getFullYear() - 1);
                    break;
            }

            currentFilters.dateFrom = formatDate(dateFrom);
            currentFilters.dateTo = formatDate(dateTo);

            $('#pm-date-from').val(currentFilters.dateFrom);
            $('#pm-date-to').val(currentFilters.dateTo);
        });
    }

    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        $('#pm-refresh-analytics').on('click', function() {
            currentFilters.dateFrom = $('#pm-date-from').val();
            currentFilters.dateTo = $('#pm-date-to').val();
            currentFilters.projectId = parseInt($('#pm-project-filter').val());
            currentPage = 1;
            loadAnalytics();
        });

        $('#pm-export-csv').on('click', function() {
            exportCSV('bookings');
        });

        $('#pm-trend-granularity').on('change', function() {
            currentFilters.granularity = $(this).val();
            loadTrendsChart();
        });

        $('#pm-prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadDetailedBookings();
            }
        });

        $('#pm-next-page').on('click', function() {
            currentPage++;
            loadDetailedBookings();
        });

        let searchTimeout;
        $('#pm-search-bookings').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                currentPage = 1;
                loadDetailedBookings();
            }, 500);
        });
    }

    /**
     * Load all analytics data
     */
    function loadAnalytics() {
        showLoading();

        Promise.all([
            loadStatistics(),
            loadTrendsChart(),
            loadStatusChart(),
            loadAttendanceChart(),
            loadTeacherUtilization(),
            loadPeakTimes(),
            loadProjectComparison(),
            loadDetailedBookings()
        ]).then(() => {
            hideLoading();
        }).catch((error) => {
            console.error('Analytics loading error:', error);
            hideLoading();
        });
    }

    /**
     * Load KPI statistics
     */
    function loadStatistics() {
        return ajaxRequest('statistics').then((data) => {
            $('#kpi-total-bookings').text(data.total_bookings.toLocaleString());
            $('#kpi-booking-rate').text(data.booking_rate + '%');
            $('#kpi-cancellation-rate').text(data.cancellation_rate + '%');
            $('#kpi-attendance-rate').text(data.attendance_rate + '%');
        });
    }

    /**
     * Load booking trends chart
     */
    function loadTrendsChart() {
        return ajaxRequest('trends', { granularity: currentFilters.granularity }).then((data) => {
            const categories = data.map(d => d.period);
            const series = [
                {
                    name: 'Patvirtinta',
                    data: data.map(d => parseInt(d.confirmed))
                },
                {
                    name: 'Atšaukta',
                    data: data.map(d => parseInt(d.cancelled))
                },
                {
                    name: 'Viso',
                    data: data.map(d => parseInt(d.total))
                }
            ];

            if (charts.trends) {
                charts.trends.destroy();
            }

            const options = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    }
                },
                series: series,
                xaxis: {
                    categories: categories,
                    labels: {
                        style: {
                            colors: '#546e7a',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#546e7a',
                            fontSize: '12px'
                        }
                    }
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                colors: ['#4caf50', '#ff9800', '#607d8b'],
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                },
                tooltip: {
                    shared: true,
                    intersect: false
                }
            };

            charts.trends = new ApexCharts(document.querySelector('#chart-trends'), options);
            charts.trends.render();
        });
    }

    /**
     * Load status distribution chart
     */
    function loadStatusChart() {
        return ajaxRequest('statistics').then((data) => {
            if (charts.status) {
                charts.status.destroy();
            }

            const options = {
                chart: {
                    type: 'donut',
                    height: 300
                },
                series: [data.confirmed, data.cancelled, data.pending],
                labels: ['Patvirtinta', 'Atšaukta', 'Laukiama'],
                colors: ['#4caf50', '#f44336', '#ff9800'],
                legend: {
                    position: 'bottom'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return Math.round(val) + '%';
                    }
                }
            };

            charts.status = new ApexCharts(document.querySelector('#chart-status'), options);
            charts.status.render();
        });
    }

    /**
     * Load attendance breakdown chart
     */
    function loadAttendanceChart() {
        return ajaxRequest('statistics').then((data) => {
            if (charts.attendance) {
                charts.attendance.destroy();
            }

            const options = {
                chart: {
                    type: 'bar',
                    height: 300
                },
                series: [{
                    name: 'Skaičius',
                    data: [data.attended, data.missed, data.attendance_pending]
                }],
                xaxis: {
                    categories: ['Atvyko', 'Neatvyko', 'Laukiama']
                },
                colors: ['#2196f3'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        borderRadius: 2
                    }
                },
                dataLabels: {
                    enabled: true
                }
            };

            charts.attendance = new ApexCharts(document.querySelector('#chart-attendance'), options);
            charts.attendance.render();
        });
    }

    /**
     * Load teacher utilization chart
     */
    function loadTeacherUtilization() {
        return ajaxRequest('teacher_utilization').then((data) => {
            if (charts.teachers) {
                charts.teachers.destroy();
            }

            const categories = data.map(t => t.teacher_name);
            const series = [{
                name: 'Užimtumas (%)',
                data: data.map(t => parseFloat(t.utilization_rate))
            }];

            const options = {
                chart: {
                    type: 'bar',
                    height: 350
                },
                series: series,
                xaxis: {
                    categories: categories,
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 2,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                colors: ['#607d8b'],
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    },
                    offsetX: 0,
                    style: {
                        fontSize: '12px',
                        colors: ['#fff']
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + '%';
                        }
                    }
                }
            };

            charts.teachers = new ApexCharts(document.querySelector('#chart-teachers'), options);
            charts.teachers.render();
        });
    }

    /**
     * Load peak times heatmap
     */
    function loadPeakTimes() {
        return ajaxRequest('peak_times').then((data) => {
            if (charts.heatmap) {
                charts.heatmap.destroy();
            }

            // Process data into heatmap format
            const days = ['Sekmadienis', 'Pirmadienis', 'Antradienis', 'Trečiadienis', 'Ketvirtadienis', 'Penktadienis', 'Šeštadienis'];
            const hours = Array.from({length: 24}, (_, i) => i);

            // Create series for each hour
            const seriesData = hours.map(hour => {
                return {
                    name: hour + ':00',
                    data: [1, 2, 3, 4, 5, 6, 7].map(dayOfWeek => {
                        const found = data.find(d => parseInt(d.day_of_week) === dayOfWeek && parseInt(d.hour_of_day) === hour);
                        return found ? parseInt(found.booking_count) : 0;
                    })
                };
            });

            const options = {
                chart: {
                    type: 'heatmap',
                    height: 500
                },
                series: seriesData,
                xaxis: {
                    categories: days,
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                colors: ['#607d8b'],
                dataLabels: {
                    enabled: false
                },
                plotOptions: {
                    heatmap: {
                        radius: 2,
                        colorScale: {
                            ranges: [
                                {from: 0, to: 0, color: '#f5f7f8', name: '0'},
                                {from: 1, to: 2, color: '#b0bec5', name: '1-2'},
                                {from: 3, to: 5, color: '#78909c', name: '3-5'},
                                {from: 6, to: 10, color: '#546e7a', name: '6-10'},
                                {from: 11, to: 999, color: '#37474f', name: '10+'}
                            ]
                        }
                    }
                }
            };

            charts.heatmap = new ApexCharts(document.querySelector('#chart-heatmap'), options);
            charts.heatmap.render();
        });
    }

    /**
     * Load project comparison table
     */
    function loadProjectComparison() {
        return ajaxRequest('project_comparison').then((data) => {
            const tbody = $('#project-comparison-body');
            tbody.empty();

            if (data.length === 0) {
                tbody.append('<tr><td colspan="7" style="text-align: center;">Duomenų nėra</td></tr>');
                return;
            }

            data.forEach(project => {
                const row = `
                    <tr>
                        <td><strong>${escapeHtml(project.project_name)}</strong></td>
                        <td>${project.total_bookings}</td>
                        <td>${project.confirmed}</td>
                        <td>${project.cancelled}</td>
                        <td>${project.attended}</td>
                        <td>${project.missed}</td>
                        <td>${project.attendance_rate !== null ? project.attendance_rate + '%' : '-'}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        });
    }

    /**
     * Load detailed bookings list
     */
    function loadDetailedBookings() {
        const limit = 50;
        const offset = (currentPage - 1) * limit;

        return ajaxRequest('detailed_bookings', { limit: limit, offset: offset }).then((data) => {
            const tbody = $('#detailed-bookings-body');
            tbody.empty();

            if (data.length === 0) {
                tbody.append('<tr><td colspan="11" style="text-align: center;">Duomenų nėra</td></tr>');
                $('#pm-prev-page').prop('disabled', true);
                $('#pm-next-page').prop('disabled', true);
                return;
            }

            data.forEach(booking => {
                const statusBadge = `<span class="pm-status-badge pm-status-${booking.status}">${booking.status}</span>`;
                const attendanceBadge = `<span class="pm-status-badge pm-attendance-${booking.attendance}">${booking.attendance}</span>`;

                const row = `
                    <tr>
                        <td>${booking.id}</td>
                        <td>${formatDateTime(booking.created_at)}</td>
                        <td>${escapeHtml(booking.teacher_name)}</td>
                        <td>${escapeHtml(booking.parent_name)}</td>
                        <td>${escapeHtml(booking.student_name || '-')}</td>
                        <td>${escapeHtml(booking.class_name || '-')}</td>
                        <td>${escapeHtml(booking.project_name)}</td>
                        <td>${formatDateTime(booking.start_time)}</td>
                        <td>${booking.meeting_type}</td>
                        <td>${statusBadge}</td>
                        <td>${attendanceBadge}</td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Update pagination
            $('#pm-prev-page').prop('disabled', currentPage === 1);
            $('#pm-next-page').prop('disabled', data.length < limit);
            $('#pm-page-info').text('Puslapis ' + currentPage);
        });
    }

    /**
     * Make AJAX request to analytics endpoint
     */
    function ajaxRequest(dataType, additionalParams = {}) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: pmAnalytics.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pm_get_analytics_data',
                    nonce: pmAnalytics.nonce,
                    data_type: dataType,
                    date_from: currentFilters.dateFrom,
                    date_to: currentFilters.dateTo,
                    project_id: currentFilters.projectId,
                    ...additionalParams
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data || 'Unknown error');
                    }
                },
                error: function(xhr, status, error) {
                    reject(error);
                }
            });
        });
    }

    /**
     * Export data to CSV
     */
    function exportCSV(reportType) {
        const url = pmAnalytics.exportUrl +
            '&report_type=' + reportType +
            '&date_from=' + currentFilters.dateFrom +
            '&date_to=' + currentFilters.dateTo +
            '&project_id=' + currentFilters.projectId;

        window.location.href = url;
    }

    /**
     * Show loading overlay
     */
    function showLoading() {
        $('#pm-analytics-loading').fadeIn(200);
    }

    /**
     * Hide loading overlay
     */
    function hideLoading() {
        $('#pm-analytics-loading').fadeOut(200);
    }

    /**
     * Format date to YYYY-MM-DD
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Format datetime for display
     */
    function formatDateTime(datetime) {
        if (!datetime) return '-';
        const d = new Date(datetime);
        return d.toLocaleString('lt-LT', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);
