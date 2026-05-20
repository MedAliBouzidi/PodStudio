<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/Booking.php';
require_once __DIR__ . '/../../../services/BookingService.php';
require_once __DIR__ . '/../../../services/StudioService.php';

requireAdmin();

// Quick status update from index 
if (isPost() && post('action') === 'update_status') {
    $booking_id = postInt('booking_id');
    $new_status = post('status');
    $booking    = BookingService::findById($booking_id);

    $allowed = [Status::Confirmed->value, Status::Canceled->value, Status::Pending->value];
    if ($booking && in_array($new_status, $allowed)) {
        $booking->setStatus($new_status);
        $bookingService = new BookingService();
        $bookingService->update($booking);
        flashSuccess('Booking #' . $booking_id . ' status updated to ' . statusLabel($new_status) . '.');
    } else {
        flashError('Invalid status update.');
    }
    redirect('/pages/admin/bookings/index.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

// Delete 
if (isPost() && post('action') === 'delete') {
    $id = postInt('id');
    if (BookingService::delete($id)) {
        flashSuccess('Booking #' . $id . ' deleted.');
    } else {
        flashError('Booking not found.');
    }
    redirect('/pages/admin/bookings/index.php');
}

// Load all bookings 
$allBookings = BookingService::findAll();
$studios     = StudioService::findAll();
$studioMap   = [];
foreach ($studios as $s) $studioMap[$s->getId()] = $s->getName();

// Filters 
$filterStatus  = get('status', '');
$filterStudio  = getInt('studio_id');
$filterDate    = get('date', '');
$search        = trim(get('search', ''));

$bookings = $allBookings;

if ($filterStatus) {
    $bookings = array_filter($bookings, fn($b) => $b->getStatus() === $filterStatus);
}
if ($filterStudio) {
    $bookings = array_filter($bookings, fn($b) => (int)$b->getStudioId() === $filterStudio);
}
if ($filterDate) {
    $bookings = array_filter($bookings, fn($b) => $b->getDate() === $filterDate);
}
if ($search) {
    $bookings = array_filter(
        $bookings,
        fn($b) =>
        stripos($b['username'], $search) !== false ||
            stripos($b['studio_name'], $search) !== false ||
            stripos((string)$b['id'], $search) !== false
    );
}
$bookings = array_values($bookings);

// Status counts
$counts = [
    'all'       => count($allBookings),
    'pending'   => count(array_filter($allBookings, fn($b) => $b->getStatus() === Status::Pending->value)),
    'confirmed' => count(array_filter($allBookings, fn($b) => $b->getStatus() === Status::Confirmed->value)),
    'canceled'  => count(array_filter($allBookings, fn($b) => $b->getStatus() === Status::Canceled->value)),
];

$pageTitle = 'Bookings — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/bookings/index.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <div class="page-header">
            <div>
                <h1>Bookings</h1>
                <p class="text-muted text-sm mt-1"><?= count($bookings) ?> booking<?= count($bookings) !== 1 ? 's' : '' ?> shown</p>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="tab-bar">
            <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'canceled' => 'Canceled'] as $key => $label): ?>
                <?php
                $params = $_GET;
                $params['status'] = $key === 'all' ? '' : $key;
                $qs = http_build_query(array_filter($params));
                ?>
                <a href="?<?= $qs ?>" class="tab-item <?= $filterStatus === ($key === 'all' ? '' : $key) ? 'active' : '' ?>">
                    <?= $label ?>
                    <span class="tab-count"><?= $counts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Filters Bar -->
        <div class="filter-bar" style="margin-bottom: 1.5rem;">
            <form method="GET" action="" class="filter-form">
                <?php if ($filterStatus): ?>
                    <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
                <?php endif; ?>

                <div class="filter-search">
                    <span class="filter-icon">🔍</span>
                    <input type="text" name="search" class="filter-input"
                        placeholder="Search client, studio, or #ID..."
                        value="<?= e($search) ?>">
                </div>

                <select name="studio_id" class="form-control" style="width:auto;">
                    <option value="">All Studios</option>
                    <?php foreach ($studios as $s): ?>
                        <option value="<?= $s->getId() ?>" <?= $filterStudio === $s->getId() ? 'selected' : '' ?>>
                            <?= e($s->getName()) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="date" class="form-control" style="width:auto;"
                    value="<?= e($filterDate) ?>">

                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($search || $filterStudio || $filterDate): ?>
                    <a href="/pages/admin/bookings/index.php<?= $filterStatus ? '?status=' . $filterStatus : '' ?>" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
            <span class="filter-count"><?= count($bookings) ?> result<?= count($bookings) !== 1 ? 's' : '' ?></span>
        </div>

        <!-- Bookings Table -->
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <h3>No bookings found</h3>
                <p>Try adjusting your filters.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Studio</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Package</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="text-muted fw-600">#<?= $b->getId() ?></td>
                                <td>
                                    <span class="fw-600"><?= e($b->getUser()->getUsername()) ?></span>
                                </td>
                                <td><?= e($b->getStudio()->getName()) ?></td>
                                <td><?= formatDate($b->getDate()) ?></td>
                                <td class="text-muted text-sm"><?= formatTimeRange($b->getStartTime(), $b->getEndTime()) ?></td>
                                <td class="text-muted text-sm"><?= e($b->getPackage() && $b->getPackage()->getName() ?? '—') ?></td>
                                <td class="text-accent fw-600"><?= number_format($b->getTotalPrice(), 2) ?> TND</td>
                                <td>
                                    <!-- Inline status change -->
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?= $b->getId() ?>">
                                        <select name="status" class="status-select status-<?= $b->getStatus() ?>"
                                            onchange="this.form.submit()">
                                            <option value="pending" <?= $b->getStatus() === 'pending'   ? 'selected' : '' ?>>⏳ Pending</option>
                                            <option value="confirmed" <?= $b->getStatus() === 'confirmed' ? 'selected' : '' ?>>✅ Confirmed</option>
                                            <option value="canceled" <?= $b->getStatus() === 'canceled'  ? 'selected' : '' ?>>❌ Canceled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/pages/admin/bookings/edit.php?id=<?= $b->getId() ?>" class="btn btn-outline btn-sm">Details</a>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $b->getId() ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                data-confirm="Delete booking #<?= $b->getId() ?>?">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>