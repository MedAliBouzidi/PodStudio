<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/Status.php';
require_once __DIR__ . '/../../entities/Studio.php';
require_once __DIR__ . '/../../entities/Booking.php';
require_once __DIR__ . '/../../entities/Client.php';
require_once __DIR__ . '/../../entities/Package.php';
require_once __DIR__ . '/../../entities/Equipment.php';
require_once __DIR__ . '/../../services/StudioService.php';
require_once __DIR__ . '/../../services/BookingService.php';
require_once __DIR__ . '/../../services/ClientService.php';
require_once __DIR__ . '/../../services/PackageService.php';
require_once __DIR__ . '/../../services/EquipmentService.php';

requireAdmin();

$pdo = Database::getConnection();

// Core counts 
$allBookings   = BookingService::findAll();
$allStudios    = StudioService::findAll();
$allClients    = ClientService::findAll();
$allPackages   = PackageService::findAll();
$allEquipments = EquipmentService::findAll();

$totalBookings   = count($allBookings);
$totalStudios    = count($allStudios);
$totalClients    = count($allClients);
$availableStudios = count(array_filter($allStudios, fn($s) => $s->getStatus() === Status::Available));

// Revenue 
$totalRevenue = array_sum(array_map(
    fn($b) => $b->getStatus() === Status::Confirmed->value ? (float)$b->getTotalPrice() : 0,
    $allBookings
));

$pendingRevenue = array_sum(array_map(
    fn($b) => $b->getStatus() === Status::Pending->value ? (float)$b->getTotalPrice() : 0,
    $allBookings
));

// Booking status breakdown 
$statusCounts = [
    'confirmed' => 0,
    'pending'   => 0,
    'canceled'  => 0,
];
foreach ($allBookings as $b) {
    if (isset($statusCounts[$b->getStatus()])) {
        $statusCounts[$b->getStatus()]++;
    }
}

// Bookings per studio 
$studioBookings = [];
foreach ($allBookings as $b) {
    $name = $b->getStudio()->getName() ?? 'Studio #' . $b->getStudioId();
    $studioBookings[$name] = ($studioBookings[$name] ?? 0) + 1;
}
arsort($studioBookings);

// Revenue per month (last 6 months) 
$monthlyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $monthlyRevenue[$label] = 0;
}
foreach ($allBookings as $b) {
    if ($b->getStatus() !== Status::Confirmed->value) continue;
    $month = date('M Y', strtotime($b->getDate()));
    if (isset($monthlyRevenue[$month])) {
        $monthlyRevenue[$month] += (float)$b->getTotalPrice();
    }
}

// Most booked package 
$packageCounts = [];
foreach ($allBookings as $b) {
    if ($b->getPackage() && !empty($b->getPackage()->getName())) {
        $packageCounts[$b->getPackage()->getName()] = ($packageCounts[$b->getPackage()->getName()] ?? 0) + 1;
    }
}
arsort($packageCounts);

// Recent bookings (last 6) 
$recentBookings = array_slice($allBookings, 0, 6);

// Equipment status 
$brokenEquipment = count(array_filter($allEquipments, fn($e) => $e->getStatus() === Status::Maintenance));

$pageTitle = 'Dashboard — PodStudio Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/dashboard.css">';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="text-muted text-sm mt-1">Welcome back, <?= e(currentUsername()) ?> — here's what's happening today.</p>
            </div>
            <span class="text-muted text-sm"><?= date('l, d F Y') ?></span>
        </div>

        <!-- KPI Cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?= number_format($totalRevenue / 1000, 1) ?>k</div>
                <div class="stat-label">Revenue (TND)</div>
                <div class="stat-sub">+<?= number_format($pendingRevenue / 1000, 1) ?>k pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?= $totalBookings ?></div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-sub"><?= $statusCounts['confirmed'] ?> confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎙️</div>
                <div class="stat-value"><?= $availableStudios ?>/<?= $totalStudios ?></div>
                <div class="stat-label">Studios Available</div>
                <div class="stat-sub"><?= $totalStudios - $availableStudios ?> unavailable</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= $totalClients ?></div>
                <div class="stat-label">Clients</div>
                <div class="stat-sub"><?= count($allPackages) ?> packages offered</div>
            </div>
            <div class="stat-card <?= $brokenEquipment > 0 ? 'stat-card-warn' : '' ?>">
                <div class="stat-icon">🔧</div>
                <div class="stat-value"><?= $brokenEquipment ?></div>
                <div class="stat-label">Equipment Issues</div>
                <div class="stat-sub"><?= count($allEquipments) ?> total items</div>
            </div>
            <div class="stat-card stat-card-pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?= $statusCounts['pending'] ?></div>
                <div class="stat-label">Pending Approval</div>
                <div class="stat-sub"><?= $statusCounts['canceled'] ?> canceled</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">

            <!-- Monthly Revenue Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Monthly Revenue</h3>
                    <span class="text-muted text-sm">Last 6 months (confirmed)</span>
                </div>
                <div class="chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Booking Status Donut -->
            <div class="chart-card chart-card-sm">
                <div class="chart-header">
                    <h3>Booking Status</h3>
                    <span class="text-muted text-sm"><?= $totalBookings ?> total</span>
                </div>
                <div class="chart-wrap" style="max-height: 200px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="donut-legend">
                    <div class="legend-item"><span class="legend-dot" style="background:#22c55e"></span>Confirmed <b><?= $statusCounts['confirmed'] ?></b></div>
                    <div class="legend-item"><span class="legend-dot" style="background:#f97316"></span>Pending <b><?= $statusCounts['pending'] ?></b></div>
                    <div class="legend-item"><span class="legend-dot" style="background:#ef4444"></span>Canceled <b><?= $statusCounts['canceled'] ?></b></div>
                </div>
            </div>

        </div>

        <!-- Bottom Row -->
        <div class="bottom-row">

            <!-- Recent Bookings -->
            <div class="dash-card dash-card-lg">
                <div class="dash-card-header">
                    <h3>Recent Bookings</h3>
                    <a href="/pages/admin/bookings/index.php" class="btn btn-outline btn-sm">View all</a>
                </div>
                <?php if (empty($recentBookings)): ?>
                    <div class="empty-state" style="padding: 2rem;">
                        <p>No bookings yet.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Studio</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td class="text-muted">#<?= $b->getId() ?></td>
                                    <td><?= e($b->getUser()->getUsername()) ?></td>
                                    <td><?= e($b->getStudio()->getName()) ?></td>
                                    <td><?= formatDate($b->getDate()) ?></td>
                                    <td class="text-accent"><?= formatPrice($b->getTotalPrice()) ?></td>
                                    <td><span class="badge <?= statusBadgeClass($b->getStatus()) ?>"><?= statusLabel($b->getStatus()) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Top Studios + Quick Links -->
            <div class="dash-right-col">

                <!-- Studio bookings ranking -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3>Top Studios</h3>
                    </div>
                    <div class="studio-rank-list">
                        <?php foreach (array_slice($studioBookings, 0, 5, true) as $name => $count):
                            $max = max(array_values($studioBookings) ?: [1]);
                            $pct = round(($count / $max) * 100);
                        ?>
                            <div class="rank-item">
                                <div class="rank-info">
                                    <span class="rank-name"><?= e($name) ?></span>
                                    <span class="rank-count"><?= $count ?> booking<?= $count !== 1 ? 's' : '' ?></span>
                                </div>
                                <div class="rank-bar-wrap">
                                    <div class="rank-bar" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($studioBookings)): ?>
                            <p class="text-muted text-sm" style="padding: 1rem;">No bookings yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <a href="/pages/admin/studios/create.php" class="quick-action">🎙️ Add Studio</a>
                        <a href="/pages/admin/equipments/create.php" class="quick-action">🔧 Add Equipment</a>
                        <a href="/pages/admin/packages/create.php" class="quick-action">📦 Add Package</a>
                        <a href="/pages/admin/bookings/index.php" class="quick-action">📅 Manage Bookings</a>
                        <a href="/pages/admin/clients/index.php" class="quick-action">👥 View Clients</a>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script id="data-to-js" type="application/json">
    <?php
    // Pass data to JS
    echo json_encode([
        "revenueLabels" => json_encode(array_keys($monthlyRevenue)),
        "revenueData"   => json_encode(array_values($monthlyRevenue)),
        "statusData"    => json_encode(array_values($statusCounts)),
    ]);
    ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/assets/js/admin/dashboard.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>