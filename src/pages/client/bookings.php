<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/Status.php';
require_once __DIR__ . '/../../entities/Booking.php';
require_once __DIR__ . '/../../services/BookingService.php';
require_once __DIR__ . '/../../services/StudioService.php';
require_once __DIR__ . '/../../services/PackageService.php';

requireClient();

// Cancel booking
if (isPost() && post('action') === 'cancel') {
    $booking_id = postInt('booking_id');
    $booking    = BookingService::findById($booking_id);

    if ($booking && $booking->getUserId() === currentUserId() && $booking->getStatus() === Status::Pending->value) {
        $booking->setStatus(Status::Canceled->value);
        $bookingService = new BookingService();
        $bookingService->update($booking);
        flashSuccess('Booking #' . $booking_id . ' has been cancelled.');
    } else {
        flashError('Unable to cancel this booking.');
    }
    redirect('/pages/client/bookings.php');
}

// Load bookings
$bookings = BookingService::findByUser(currentUserId());

// Filter by status tab
$tab = get('tab', 'all');
$filtered = match ($tab) {
    'pending'   => array_filter($bookings, fn($b) => $b->getStatus() === Status::Pending->value),
    'confirmed' => array_filter($bookings, fn($b) => $b->getStatus() === Status::Confirmed->value),
    'canceled'  => array_filter($bookings, fn($b) => $b->getStatus() === Status::Canceled->value),
    default     => $bookings,
};

$counts = [
    'all'       => count($bookings),
    'pending'   => count(array_filter($bookings, fn($b) => $b->getStatus() === Status::Pending->value)),
    'confirmed' => count(array_filter($bookings, fn($b) => $b->getStatus() === Status::Confirmed->value)),
    'canceled'  => count(array_filter($bookings, fn($b) => $b->getStatus() === Status::Canceled->value)),
];

$pageTitle = 'My Bookings — PodStudio';
$extraHead = '<link rel="stylesheet" href="/assets/css/client/bookings.css">';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <div class="page-header">
            <div>
                <h1>My Bookings</h1>
                <p class="text-muted text-sm mt-1">Track and manage your studio sessions</p>
            </div>
            <a href="/pages/client/home.php" class="btn btn-primary">+ New Booking</a>
        </div>

        <!-- Status Tabs -->
        <div class="tab-bar">
            <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'canceled' => 'Canceled'] as $key => $label): ?>
                <a href="?tab=<?= $key ?>" class="tab-item <?= $tab === $key ? 'active' : '' ?>">
                    <?= $label ?>
                    <span class="tab-count"><?= $counts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Bookings List -->
        <?php if (empty($filtered)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <h3>No bookings <?= $tab !== 'all' ? "with status \"$tab\"" : 'yet' ?></h3>
                <p>Ready to record? Find a studio and book your first session.</p>
                <a href="/pages/client/home.php" class="btn btn-primary mt-3">Browse Studios</a>
            </div>
        <?php else: ?>
            <div class="bookings-list">
                <?php foreach ($filtered as $booking):
                    $studio  = StudioService::findById($booking->getStudioId());
                    $package = $booking->getPackageId() ? PackageService::findById($booking->getPackageId()) : null;
                    $isPast  = strtotime($booking->getDate()) < strtotime('today');
                ?>
                    <div class="booking-item <?= $isPast ? 'is-past' : '' ?>">
                        <div class="booking-item-left">
                            <div class="booking-date-block">
                                <span class="booking-day"><?= date('d', strtotime($booking->getDate())) ?></span>
                                <span class="booking-month"><?= date('M Y', strtotime($booking->getDate())) ?></span>
                            </div>
                        </div>

                        <div class="booking-item-body">
                            <div class="booking-item-top">
                                <div>
                                    <h3 class="booking-studio-name">
                                        <?= $studio ? e($studio->getName()) : 'Studio #' . $booking->getStudioId() ?>
                                    </h3>
                                    <div class="booking-item-meta">
                                        <span>🕐 <?= formatTimeRange($booking->getStartTime(), $booking->getEndTime()) ?></span>
                                        <?php if ($studio?->getLocation()): ?>
                                            <span>📍 <?= e($studio->getLocation()) ?></span>
                                        <?php endif; ?>
                                        <?php if ($package): ?>
                                            <span>📦 <?= e($package->getName()) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($booking->getNotes()): ?>
                                        <p class="booking-notes">"<?= e(truncate($booking->getNotes(), 80)) ?>"</p>
                                    <?php endif; ?>
                                </div>
                                <div class="booking-item-right">
                                    <span class="badge <?= statusBadgeClass($booking->getStatus()) ?>">
                                        <?= statusLabel($booking->getStatus()) ?>
                                    </span>
                                    <span class="booking-price"><?= formatPrice($booking->getTotalPrice()) ?></span>
                                </div>
                            </div>

                            <?php if ($booking->getStatus() === Status::Pending->value && !$isPast): ?>
                                <div class="booking-item-actions">
                                    <a href="/pages/client/studio.php?id=<?= $booking->getStudioId() ?>" class="btn btn-outline btn-sm">
                                        View Studio
                                    </a>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="booking_id" value="<?= $booking->getId() ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            data-confirm="Cancel booking #<?= $booking->getId() ?>?">
                                            Cancel
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>