<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/upload.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/Booking.php';
require_once __DIR__ . '/../../../services/BookingService.php';
require_once __DIR__ . '/../../../services/StudioService.php';
require_once __DIR__ . '/../../../services/ClientService.php';
require_once __DIR__ . '/../../../services/PackageService.php';

requireAdmin();

$id      = getInt('id');
$booking = BookingService::findById($id);

if (!$booking) {
    flashError('Booking not found.');
    redirect('/pages/admin/bookings/index.php');
}

// Load related data
$studio  = StudioService::findById($booking->getStudioId());
$client  = ClientService::findById($booking->getUserId());
$package = $booking->getPackageId() ? PackageService::findById($booking->getPackageId()) : null;

// ── Handle status update ──────────────────────────────────────
if (isPost() && post('action') === 'update_status') {
    $new_status = post('status');
    $allowed    = [Status::Pending->value, Status::Confirmed->value, Status::Canceled->value];

    if (in_array($new_status, $allowed)) {
        $booking->setStatus($new_status);
        $bookingService = new BookingService();
        $bookingService->update($booking);
        flashSuccess('Booking status updated to ' . statusLabel($new_status) . '.');
        redirect('/pages/admin/bookings/edit.php?id=' . $id);
    }
}

// ── Handle notes update ───────────────────────────────────────
if (isPost() && post('action') === 'update_notes') {
    $notes = trim(post('notes'));
    $booking->setNotes($notes ?: null);
    $bookingService = new BookingService();
    $bookingService->update($booking);
    flashSuccess('Booking notes updated.');
    redirect('/pages/admin/bookings/edit.php?id=' . $id);
}

$isPast = strtotime($booking->getDate()) < strtotime('today');

$pageTitle = 'Booking #' . $id . ' — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/bookings/edit.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 860px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/bookings/index.php" class="back-link">← Back to Bookings</a>
                <h1 class="mt-1">Booking <span class="text-accent">#<?= $id ?></span></h1>
            </div>
            <div class="flex gap-1">
                <span class="badge <?= statusBadgeClass($booking->getStatus()) ?>" style="font-size:0.85rem; padding: 0.4rem 1rem;">
                    <?= statusLabel($booking->getStatus()) ?>
                </span>
                <?php if ($isPast): ?>
                    <span class="badge badge-default">Past</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="booking-detail-layout">

            <!-- Left col: main info -->
            <div class="booking-detail-main">

                <!-- Studio Info -->
                <div class="detail-card">
                    <h3 class="detail-card-title">🎙️ Studio</h3>
                    <?php if ($studio): ?>
                        <div class="studio-detail-row">
                            <img
                                src="<?= e(uploadUrl($studio->getCoverImage(), 'studios')) ?>"
                                alt="<?= e($studio->getName()) ?>"
                                class="studio-detail-thumb">
                            <div>
                                <div class="fw-600"><?= e($studio->getName()) ?></div>
                                <?php if ($studio->getLocation()): ?>
                                    <div class="text-muted text-sm">📍 <?= e($studio->getLocation()) ?></div>
                                <?php endif; ?>
                                <div class="text-muted text-sm">👥 <?= $studio->getCapacity() ?> people max</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Studio #<?= $booking->getStudioId() ?> (deleted)</p>
                    <?php endif; ?>
                </div>

                <!-- Session Info -->
                <div class="detail-card">
                    <h3 class="detail-card-title">📅 Session Details</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Date</span>
                            <span class="detail-value"><?= formatDate($booking->getDate(), 'l, d F Y') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Time</span>
                            <span class="detail-value"><?= formatTimeRange($booking->getStartTime(), $booking->getEndTime()) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Duration</span>
                            <?php
                            $hours = (strtotime($booking->getEndTime()) - strtotime($booking->getStartTime())) / 3600;
                            ?>
                            <span class="detail-value"><?= $hours ?>h session</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Price</span>
                            <span class="detail-value text-accent fw-600"><?= number_format($booking->getTotalPrice(), 2) ?> TND</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Package</span>
                            <span class="detail-value"><?= $package ? e($package->getName()) : '—' ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Booked on</span>
                            <span class="detail-value"><?= formatDate($booking->getCreatedAt(), 'd M Y, H:i') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Client Notes -->
                <div class="detail-card">
                    <h3 class="detail-card-title">📝 Notes</h3>
                    <?php if ($booking->getNotes()): ?>
                        <blockquote class="booking-notes-block"><?= e($booking->getNotes()) ?></blockquote>
                    <?php else: ?>
                        <p class="text-muted text-sm">No notes provided by client.</p>
                    <?php endif; ?>

                    <!-- Admin can update notes -->
                    <form method="POST" action="" class="mt-3">
                        <input type="hidden" name="action" value="update_notes">
                        <div class="form-group">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                placeholder="Add internal notes about this booking..."><?= e($booking->getNotes() ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm">Save Notes</button>
                    </form>
                </div>

            </div>

            <!-- Right col: client + actions -->
            <div class="booking-detail-side">

                <!-- Client Info -->
                <div class="detail-card">
                    <h3 class="detail-card-title">👤 Client</h3>
                    <?php if ($client): ?>
                        <div class="client-detail-row">
                            <img
                                src="<?= e(uploadUrl($client->getProfilePicture(), 'profiles')) ?>"
                                alt="<?= e($client->getUsername()) ?>"
                                class="client-avatar">
                            <div>
                                <div class="fw-600"><?= e($client->getFullName()) ?></div>
                                <div class="text-muted text-sm">@<?= e($client->getUsername()) ?></div>
                                <div class="text-muted text-sm"><?= e($client->getEmail()) ?></div>
                                <?php if ($client->getPhone()): ?>
                                    <div class="text-muted text-sm">📞 <?= e($client->getPhone()) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Client #<?= $booking->getUserId() ?> (deleted)</p>
                    <?php endif; ?>
                </div>

                <!-- Status Management -->
                <div class="detail-card">
                    <h3 class="detail-card-title">⚙️ Update Status</h3>
                    <form method="POST" action="" class="status-form">
                        <input type="hidden" name="action" value="update_status">
                        <div class="status-options">
                            <?php foreach ([Status::Pending, Status::Confirmed, Status::Canceled] as $s): ?>
                                <label class="status-option <?= $booking->getStatus() === $s->value ? 'active' : '' ?> status-opt-<?= $s->value ?>">
                                    <input type="radio" name="status" value="<?= $s->value ?>"
                                        <?= $booking->getStatus() === $s->value ? 'checked' : '' ?>>
                                    <span class="status-option-dot"></span>
                                    <?= statusLabel($s->value) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-3">Apply Status</button>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div class="detail-card detail-card-danger">
                    <h3 class="detail-card-title" style="color: var(--red);">⚠️ Danger Zone</h3>
                    <p class="text-sm text-muted mb-2">Permanently delete this booking record.</p>
                    <form method="POST" action="/pages/admin/bookings/index.php">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-danger btn-block"
                            data-confirm="Permanently delete booking #<?= $id ?>? This cannot be undone.">
                            Delete Booking
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="/assets/js/index.js"></script>
<script src="/assets/js/admin/bookings/edit.js"></script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>