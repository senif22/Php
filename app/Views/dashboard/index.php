<?= $this->include('layout/header') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <div class="d-flex align-items-center gap-2">
        <small class="text-muted">
            <?= $fromCache ? 'Cached' : 'Fresh' ?> &middot; <?= esc($generatedAt) ?>
        </small>
        <a href="<?= base_url('dashboard/refresh') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h6 class="card-title mb-1">Total Customers</h6>
                <h2 class="mb-0"><?= $summary['total'] ?></h2>
                <small><i class="bi bi-people"></i> All visible to you</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h6 class="card-title mb-1">Active</h6>
                <h2 class="mb-0"><?= $summary['active'] ?></h2>
                <small><i class="bi bi-check-circle"></i> Currently active</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <h6 class="card-title mb-1">New This Month</h6>
                <h2 class="mb-0"><?= $summary['new_this_month'] ?></h2>
                <small><i class="bi bi-calendar-plus"></i> Since the 1st</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-white bg-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-1">Inactive</h6>
                <h2 class="mb-0"><?= $summary['inactive'] ?></h2>
                <small><i class="bi bi-pause-circle"></i> Not active</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Customer Growth (last 6 months)</h6>
            </div>
            <div class="card-body">
                <div style="position:relative;height:280px;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Status Distribution</h6>
            </div>
            <div class="card-body">
                <div style="position:relative;height:220px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <ul class="list-unstyled mb-0 mt-3 small">
                    <?php foreach ($statusDistribution as $slice): ?>
                        <li class="d-flex justify-content-between">
                            <span class="text-capitalize"><?= esc($slice['status']) ?></span>
                            <span><strong><?= $slice['count'] ?></strong> &middot; <?= $slice['percent'] ?>%</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Top 5 Cities</h6>
            </div>
            <div class="card-body">
                <?php if (empty($topCities)): ?>
                    <p class="text-muted mb-0">No city data yet.</p>
                <?php else: ?>
                    <div style="position:relative;height:280px;">
                        <canvas id="cityChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentActivities)): ?>
                    <p class="text-muted m-3 mb-0">No activity recorded yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                <div class="me-auto">
                                    <a href="<?= base_url('customers/view/' . $activity['customer_id']) ?>">
                                        <?= esc($activity['customer_name']) ?>
                                    </a>
                                    <span class="badge bg-light text-dark text-capitalize ms-1"><?= esc($activity['action']) ?></span>
                                    <div class="small text-muted"><?= esc($activity['description']) ?></div>
                                </div>
                                <small class="text-muted text-nowrap"><?= esc($activity['created_at']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.maintainAspectRatio = false;

    var growth = <?= json_encode($growth, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var status = <?= json_encode($statusDistribution, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var cities = <?= json_encode($topCities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    new Chart(document.getElementById('growthChart'), {
        type: 'line',
        data: {
            labels: growth.labels,
            datasets: [{
                label: 'Customers added',
                data: growth.values,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.12)',
                tension: 0.3,
                fill: true,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: {
            labels: status.map(function (s) { return s.status + ' (' + s.count + ')'; }),
            datasets: [{
                data: status.map(function (s) { return s.count; }),
                backgroundColor: ['#198754', '#6c757d', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });

    var cityCanvas = document.getElementById('cityChart');

    if (cityCanvas) {
        new Chart(cityCanvas, {
            type: 'bar',
            data: {
                labels: cities.map(function (c) { return c.city; }),
                datasets: [{
                    label: 'Customers',
                    data: cities.map(function (c) { return c.count; }),
                    backgroundColor: '#0dcaf0'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
})();
</script>

<?= $this->include('layout/footer') ?>
