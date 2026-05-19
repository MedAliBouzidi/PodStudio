<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/upload.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/User.php';
require_once __DIR__ . '/../../../entities/Client.php';
require_once __DIR__ . '/../../../services/ClientService.php';
require_once __DIR__ . '/../../../services/BookingService.php';

requireAdmin();

$clients = ClientService::findAll();

// ── Search ────────────────────────────────────────────────────
$search = trim(get('search', ''));
if ($search) {
    $clients = array_filter(
        $clients,
        fn($c) =>
        stripos($c->getFullName(), $search)  !== false ||
            stripos($c->getUsername(), $search)  !== false ||
            stripos($c->getEmail(), $search)     !== false ||
            stripos($c->getPhone() ?? '', $search) !== false
    );
}
$clients = array_values($clients);

// ── Per-client booking counts ─────────────────────────────────
$bookingCounts = [];
foreach ($clients as $client) {
    $bookings = BookingService::findByUser($client->getId());
    $bookingCounts[$client->getId()] = [
        'total'     => count($bookings),
        'confirmed' => count(array_filter($bookings, fn($b) => $b->getStatus() === Status::Confirmed->value)),
        'pending'   => count(array_filter($bookings, fn($b) => $b->getStatus() === Status::Pending->value)),
        'spent'     => array_sum(array_map(
            fn($b) => $b->getStatus() !== Status::Canceled->value ? $b->getTotalPrice() : 0,
            $bookings
        )),
    ];
}

$pageTitle = 'Clients — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/clients/index.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <div class="page-header">
            <div>
                <h1>Clients</h1>
                <p class="text-muted text-sm mt-1"><?= count($clients) ?> client<?= count($clients) !== 1 ? 's' : '' ?> registered</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="filter-bar" style="margin-bottom: 1.5rem;">
            <form method="GET" action="" class="filter-form">
                <div class="filter-search">
                    <span class="filter-icon">🔍</span>
                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Search by name, username, email or phone..."
                        value="<?= e($search) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="/pages/admin/clients/index.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
            <span class="filter-count"><?= count($clients) ?> result<?= count($clients) !== 1 ? 's' : '' ?></span>
        </div>

        <?php if (empty($clients)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <h3>No clients found</h3>
                <p><?= $search ? 'Try a different search term.' : 'No clients have registered yet.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Bookings</th>
                            <th>Confirmed</th>
                            <th>Pending</th>
                            <th>Total Spent</th>
                            <th>Member Since</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client):
                            $stats = $bookingCounts[$client->getId()];
                        ?>
                            <tr>
                                <td class="text-muted"><?= $client->getId() ?></td>
                                <td>
                                    <div class="client-cell">
                                        <img
                                            src="<?= e(uploadUrl($client->getProfilePicture(), 'profiles')) ?>"
                                            alt="<?= e($client->getUsername()) ?>"
                                            class="client-thumb">
                                        <div>
                                            <div class="fw-600"><?= e($client->getFullName()) ?></div>
                                            <div class="text-muted text-sm">@<?= e($client->getUsername()) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-cell">
                                        <span class="text-sm"><?= e($client->getEmail()) ?></span>
                                        <?php if ($client->getPhone()): ?>
                                            <span class="text-muted text-sm">📞 <?= e($client->getPhone()) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="booking-count-badge"><?= $stats['total'] ?></span>
                                </td>
                                <td>
                                    <?php if ($stats['confirmed'] > 0): ?>
                                        <span class="badge badge-confirmed"><?= $stats['confirmed'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stats['pending'] > 0): ?>
                                        <span class="badge badge-pending"><?= $stats['pending'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $stats['spent'] > 0 ? 'text-accent fw-600' : 'text-muted' ?>">
                                    <?= $stats['spent'] > 0 ? number_format($stats['spent'], 2) . ' TND' : '—' ?>
                                </td>
                                <td class="text-muted text-sm">
                                    <?= formatDate($client->getCreatedAt(), 'd M Y') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Footer -->
            <div class="clients-summary">
                <div class="summary-item">
                    <span class="summary-label">Total Clients</span>
                    <span class="summary-value"><?= count($clients) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Bookings</span>
                    <span class="summary-value"><?= array_sum(array_column($bookingCounts, 'total')) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Revenue</span>
                    <span class="summary-value text-accent"><?= number_format(array_sum(array_column($bookingCounts, 'spent')), 2) ?> TND</span>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>