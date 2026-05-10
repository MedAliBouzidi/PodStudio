<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/upload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/Status.php';
require_once __DIR__ . '/../../entities/Studio.php';
require_once __DIR__ . '/../../entities/Equipment.php';
require_once __DIR__ . '/../../entities/Package.php';
require_once __DIR__ . '/../../entities/Booking.php';
require_once __DIR__ . '/../../services/StudioService.php';
require_once __DIR__ . '/../../services/EquipmentService.php';
require_once __DIR__ . '/../../services/PackageService.php';
require_once __DIR__ . '/../../services/BookingService.php';

requireClient();

$id     = getInt('id');
$studio = StudioService::findById($id);

if (!$studio || $studio->getStatus() !== Status::Available) {
    flashError('Studio not found or unavailable.');
    redirect('/pages/client/home.php');
}

$equipments = EquipmentService::findByStudio($id);
$packages   = PackageService::findAll();
$errors     = [];

// ── Handle booking form submission ────────────────────────────
if (isPost()) {
    $package_id  = postInt('package_id') ?: null;
    $date        = post('date');
    $start_time  = post('start_time');
    $end_time    = post('end_time');
    $notes       = trim(post('notes'));

    // Validation
    if (empty($date))       $errors['date']       = 'Please select a date.';
    if (empty($start_time)) $errors['start_time'] = 'Please select a start time.';
    if (empty($end_time))   $errors['end_time']   = 'Please select an end time.';

    if (empty($errors)) {
        if ($start_time >= $end_time) {
            $errors['end_time'] = 'End time must be after start time.';
        } elseif (strtotime($date) < strtotime('today')) {
            $errors['date'] = 'Please select a future date.';
        } elseif (BookingService::isSlotTaken($id, $date, $start_time, $end_time)) {
            $errors['date'] = 'This time slot is already booked. Please choose another.';
        }
    }

    if (empty($errors)) {
        // Calculate price
        $hours       = (strtotime($end_time) - strtotime($start_time)) / 3600;
        $total_price = $hours * $studio->getPricePerHour();

        // Add package price if selected
        if ($package_id) {
            $pkg = PackageService::findById($package_id);
            if ($pkg) $total_price += $pkg->getPrice();
        }

        $booking = new Booking(
            currentUserId(),
            $studio->getId(),
            $date,
            $start_time,
            $end_time,
            $total_price,
            $package_id,
            Status::Pending->value,
            $notes ?: null
        );

        $bookingService = new BookingService();
        $booking_id = $bookingService->save($booking);

        if ($booking_id) {
            flashSuccess('Booking #' . $booking_id . ' submitted! Waiting for confirmation.');
            redirect('/pages/client/bookings.php');
        } else {
            $errors['general'] = 'Booking failed. Please try again.';
        }
    }
}

$pageTitle = e($studio->getName()) . ' — PodStudio';
$extraHead = "<link rel='stylesheet' href='/assets/css/client/studio.css'>";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <!-- Back -->
        <div class="mt-3 mb-2">
            <a href="/pages/client/home.php" class="back-link">← Back to studios</a>
        </div>

        <div class="studio-detail-layout">

            <!-- Left: Info -->
            <div class="studio-info">
                <div class="studio-cover-wrap">
                    <img
                        src="<?= e(uploadUrl($studio->getCoverImage(), 'studios')) ?>"
                        alt="<?= e($studio->getName()) ?>"
                        class="studio-cover">
                    <div class="studio-cover-badge">
                        <span class="badge badge-available">Available</span>
                    </div>
                </div>

                <div class="studio-meta">
                    <h1><?= e($studio->getName()) ?></h1>
                    <div class="studio-meta-row">
                        <?php if ($studio->getLocation()): ?>
                            <span class="meta-pill">📍 <?= e($studio->getLocation()) ?></span>
                        <?php endif; ?>
                        <span class="meta-pill">👥 <?= $studio->getCapacity() ?> people max</span>
                        <span class="meta-pill accent">💰 <?= formatPrice($studio->getPricePerHour()) ?>/hr</span>
                    </div>
                    <?php if ($studio->getDescription()): ?>
                        <p class="studio-desc"><?= e($studio->getDescription()) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Equipment -->
                <?php if (!empty($equipments)): ?>
                    <div class="studio-section">
                        <h3>🔧 Equipment</h3>
                        <div class="equipment-list">
                            <?php foreach ($equipments as $eq): ?>
                                <div class="equipment-item <?= $eq->getStatus() === Status::Maintenance ? 'eq-broken' : '' ?>">
                                    <img
                                        src="<?= e(uploadUrl($eq->getImage(), 'equipments')) ?>"
                                        alt="<?= e($eq->getName()) ?>"
                                        class="eq-img">
                                    <div class="eq-info">
                                        <span class="eq-name"><?= e($eq->getName()) ?></span>
                                        <span class="eq-brand"><?= e($eq->getBrand() ?? '') ?></span>
                                    </div>
                                    <span class="eq-qty">×<?= $eq->getQuantity() ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Booking Form -->
            <div class="booking-panel">
                <div class="booking-card">
                    <h2 class="booking-title">Book this studio</h2>
                    <p class="booking-subtitle">Fill in your session details below</p>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="flash flash-error" style="position:static; margin-bottom:1rem; animation:none;">
                            <span><?= e($errors['general']) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">

                        <div class="form-group">
                            <label class="form-label">Add-on Package <span class="text-muted">(optional)</span></label>
                            <select name="package_id" class="form-control" id="package-select">
                                <option value="">— No package —</option>
                                <?php foreach ($packages as $pkg): ?>
                                    <option
                                        value="<?= $pkg->getId() ?>"
                                        data-price="<?= $pkg->getPrice() ?>"
                                        data-hours="<?= $pkg->getDurationHours() ?>"
                                        <?= postInt('package_id') === $pkg->getId() ? 'selected' : '' ?>>
                                        <?= e($pkg->getName()) ?> — <?= formatPrice($pkg->getPrice()) ?> (<?= $pkg->getDurationHours() ?>h)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="date">Date</label>
                            <input
                                type="date"
                                id="date"
                                name="date"
                                class="form-control <?= isset($errors['date']) ? 'is-error' : '' ?>"
                                min="<?= date('Y-m-d') ?>"
                                value="<?= e(post('date')) ?>"
                                required>
                            <?php if (isset($errors['date'])): ?>
                                <span class="form-error"><?= e($errors['date']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="time-row">
                            <div class="form-group">
                                <label class="form-label" for="start_time">Start Time</label>
                                <input type="time" id="start_time" name="start_time"
                                    class="form-control <?= isset($errors['start_time']) ? 'is-error' : '' ?>"
                                    value="<?= e(post('start_time')) ?>"
                                    min="08:00" max="22:00" required>
                                <?php if (isset($errors['start_time'])): ?>
                                    <span class="form-error"><?= e($errors['start_time']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="end_time">End Time</label>
                                <input type="time" id="end_time" name="end_time"
                                    class="form-control <?= isset($errors['end_time']) ? 'is-error' : '' ?>"
                                    value="<?= e(post('end_time')) ?>"
                                    min="08:00" max="22:00" required>
                                <?php if (isset($errors['end_time'])): ?>
                                    <span class="form-error"><?= e($errors['end_time']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="notes">Notes <span class="text-muted">(optional)</span></label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"
                                placeholder="Any special requirements or requests..."><?= e(post('notes')) ?></textarea>
                        </div>

                        <!-- Price Preview -->
                        <div class="price-preview" id="price-preview">
                            <div class="price-row">
                                <span>Studio rate</span>
                                <span id="studio-rate">— TND/hr</span>
                            </div>
                            <div class="price-row" id="pkg-row" style="display:none">
                                <span>Package</span>
                                <span id="pkg-price">—</span>
                            </div>
                            <div class="price-row">
                                <span>Duration</span>
                                <span id="duration-display">—</span>
                            </div>
                            <hr class="divider" style="margin: 0.75rem 0;">
                            <div class="price-row total">
                                <span>Total</span>
                                <span id="total-price">—</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">
                            Request Booking
                        </button>
                        <p class="text-center text-sm text-muted mt-2">
                            Your booking will be confirmed by our team.
                        </p>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="/assets/js/index.js"></script>
<script>
    const pricePerHour = <?= $studio->getPricePerHour() ?>;
    import('/assets/js/client/studio.js');
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>