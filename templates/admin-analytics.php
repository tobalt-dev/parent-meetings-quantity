<!--
/**
 * Admin Analytics Template
 *
 * Author: Tobalt — https://tobalt.lt
 */
-->

<div class="wrap pm-analytics-wrap">
    <h1>Analitika</h1>

    <!-- Filter Controls -->
    <div class="pm-analytics-controls">
        <div class="pm-control-group">
            <label for="pm-date-preset">Laikotarpis:</label>
            <select id="pm-date-preset" class="pm-select">
                <option value="7">Paskutinės 7 dienos</option>
                <option value="30" selected>Paskutinės 30 dienų</option>
                <option value="90">Paskutinės 90 dienų</option>
                <option value="365">Paskutiniai 12 mėnesių</option>
                <option value="custom">Pasirinkti datą</option>
            </select>
        </div>

        <div class="pm-control-group pm-custom-dates" style="display: none;">
            <label for="pm-date-from">Nuo:</label>
            <input type="date" id="pm-date-from" class="pm-input" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">

            <label for="pm-date-to">Iki:</label>
            <input type="date" id="pm-date-to" class="pm-input" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="pm-control-group">
            <label for="pm-project-filter">Projektas:</label>
            <select id="pm-project-filter" class="pm-select">
                <option value="0">Visi projektai</option>
                <?php foreach ($all_projects as $project) : ?>
                    <option value="<?php echo esc_attr($project->id); ?>">
                        <?php echo esc_html($project->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pm-control-group">
            <button id="pm-refresh-analytics" class="button button-primary">
                <span class="dashicons dashicons-update"></span> Atnaujinti
            </button>

            <button id="pm-export-csv" class="button">
                <span class="dashicons dashicons-download"></span> Eksportuoti CSV
            </button>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="pm-analytics-loading" class="pm-loading-overlay" style="display: none;">
        <div class="pm-spinner"></div>
    </div>

    <!-- KPI Cards -->
    <div class="pm-kpi-grid">
        <div class="pm-kpi-card">
            <div class="pm-kpi-icon pm-icon-bookings">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="pm-kpi-content">
                <div class="pm-kpi-label">Viso rezervacijų</div>
                <div class="pm-kpi-value" id="kpi-total-bookings">-</div>
                <div class="pm-kpi-change" id="kpi-bookings-change"></div>
            </div>
        </div>

        <div class="pm-kpi-card">
            <div class="pm-kpi-icon pm-icon-rate">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="pm-kpi-content">
                <div class="pm-kpi-label">Rezervacijų lygis</div>
                <div class="pm-kpi-value" id="kpi-booking-rate">-</div>
                <div class="pm-kpi-subtitle">užimta / viso</div>
            </div>
        </div>

        <div class="pm-kpi-card">
            <div class="pm-kpi-icon pm-icon-cancel">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="pm-kpi-content">
                <div class="pm-kpi-label">Atšaukimų lygis</div>
                <div class="pm-kpi-value" id="kpi-cancellation-rate">-</div>
                <div class="pm-kpi-subtitle">atšaukta / viso</div>
            </div>
        </div>

        <div class="pm-kpi-card">
            <div class="pm-kpi-icon pm-icon-attendance">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="pm-kpi-content">
                <div class="pm-kpi-label">Lankomumo lygis</div>
                <div class="pm-kpi-value" id="kpi-attendance-rate">-</div>
                <div class="pm-kpi-subtitle">atvyko / viso</div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="pm-charts-grid">
        <!-- Booking Trends Chart -->
        <div class="pm-chart-card pm-chart-full">
            <div class="pm-chart-header">
                <h2>Rezervacijų tendencijos</h2>
                <div class="pm-chart-controls">
                    <select id="pm-trend-granularity" class="pm-select-small">
                        <option value="daily" selected>Dienos</option>
                        <option value="weekly">Savaitės</option>
                        <option value="monthly">Mėnesiai</option>
                    </select>
                </div>
            </div>
            <div class="pm-chart-body">
                <div id="chart-trends"></div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="pm-chart-card pm-chart-half">
            <div class="pm-chart-header">
                <h2>Rezervacijų statusai</h2>
            </div>
            <div class="pm-chart-body">
                <div id="chart-status"></div>
            </div>
        </div>

        <!-- Attendance Breakdown -->
        <div class="pm-chart-card pm-chart-half">
            <div class="pm-chart-header">
                <h2>Lankomumo pasiskirstymas</h2>
            </div>
            <div class="pm-chart-body">
                <div id="chart-attendance"></div>
            </div>
        </div>

        <!-- Teacher Utilization -->
        <div class="pm-chart-card pm-chart-full">
            <div class="pm-chart-header">
                <h2>Mokytojų užimtumas</h2>
            </div>
            <div class="pm-chart-body">
                <div id="chart-teachers"></div>
            </div>
        </div>

        <!-- Peak Times Heatmap -->
        <div class="pm-chart-card pm-chart-full">
            <div class="pm-chart-header">
                <h2>Populiariausi susitikimų laikai</h2>
                <p class="pm-chart-description">Šilumės žemėlapis rodo, kada dažniausiai užsakomos rezervacijos</p>
            </div>
            <div class="pm-chart-body">
                <div id="chart-heatmap"></div>
            </div>
        </div>

        <!-- Project Comparison -->
        <?php if (count($all_projects) > 1) : ?>
        <div class="pm-chart-card pm-chart-full">
            <div class="pm-chart-header">
                <h2>Projektų Palyginimas</h2>
            </div>
            <div class="pm-chart-body">
                <table class="wp-list-table widefat fixed striped pm-comparison-table">
                    <thead>
                        <tr>
                            <th>Projektas</th>
                            <th>Viso</th>
                            <th>Patvirtinta</th>
                            <th>Atšaukta</th>
                            <th>Atvyko</th>
                            <th>Neatvyko</th>
                            <th>Lankomumas</th>
                        </tr>
                    </thead>
                    <tbody id="project-comparison-body">
                        <tr>
                            <td colspan="7" style="text-align: center;">Kraunama...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Detailed Bookings List -->
    <div class="pm-chart-card pm-chart-full">
        <div class="pm-chart-header">
            <h2>Detalus Rezervacijų Sąrašas</h2>
            <div class="pm-chart-controls">
                <input type="text" id="pm-search-bookings" class="pm-input-small" placeholder="Ieškoti...">
            </div>
        </div>
        <div class="pm-chart-body">
            <div class="pm-table-wrapper">
                <table class="wp-list-table widefat fixed striped" id="pm-detailed-bookings">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sukurta</th>
                            <th>Mokytojas</th>
                            <th>Tėvas</th>
                            <th>Mokinys</th>
                            <th>Klasė</th>
                            <th>Projektas</th>
                            <th>Susitikimo Laikas</th>
                            <th>Tipas</th>
                            <th>Statusas</th>
                            <th>Lankomumas</th>
                        </tr>
                    </thead>
                    <tbody id="detailed-bookings-body">
                        <tr>
                            <td colspan="11" style="text-align: center;">Kraunama...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pm-pagination">
                <button id="pm-prev-page" class="button" disabled>« Ankstesnis</button>
                <span id="pm-page-info">Puslapis 1</span>
                <button id="pm-next-page" class="button">Kitas »</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Inline loading spinner styles */
.pm-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.pm-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #607d8b;
    border-radius: 50%;
    animation: pm-spin 1s linear infinite;
}

@keyframes pm-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Initialize analytics when DOM is ready
jQuery(document).ready(function($) {
    if (typeof window.pmAnalyticsInit === 'function') {
        window.pmAnalyticsInit();
    }
});
</script>
