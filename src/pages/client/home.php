<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/upload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/Status.php';
require_once __DIR__ . '/../../entities/Studio.php';
require_once __DIR__ . '/../../services/StudioService.php';

requireClient();

// Filters and sorting
$search   = trim(get('search', ''));
$sort     = get('sort', 'price_asc');

$studios  = StudioService::findAvailable();

// Filter by search
if ($search) {
    $studios = array_filter(
        $studios,
        fn($s) =>
        stripos($s->getName(), $search) !== false ||
            stripos($s->getLocation(), $search) !== false
    );
}

// Sort
usort($studios, function ($a, $b) use ($sort) {
    return match ($sort) {
        'price_asc'  => $a->getPricePerHour() <=> $b->getPricePerHour(),
        'price_desc' => $b->getPricePerHour() <=> $a->getPricePerHour(),
        'capacity'   => $b->getCapacity() <=> $a->getCapacity(),
        default      => $a->getPricePerHour() <=> $b->getPricePerHour(),
    };
});

$studios = array_values($studios);

$pageTitle = 'Studios — PodStudio';
$extraHead = '<link rel="stylesheet" href="/assets/css/client/home.css">';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <!-- Hero -->
        <div class="client-hero">
            <div class="hero-text">
                <h1>Find your perfect<br><span class="text-accent">recording studio</span></h1>
                <p>Professional podcast studios available by the hour.<br> Pick your space, book your session.</p>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-value"><?= count(StudioService::findAvailable()) ?></span>
                    <span class="hero-stat-label">Studios</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-value">24/7</span>
                    <span class="hero-stat-label">Support</span>
                </div>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="filter-bar">
            <form method="GET" action="" class="filter-form">
                <div class="filter-search">
                    <span class="filter-icon">🔍</span>
                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Search by name or location..."
                        value="<?= e($search) ?>">
                </div>
                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="price_asc" <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
                    <option value="capacity" <?= $sort === 'capacity'   ? 'selected' : '' ?>>Capacity</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search || $sort !== 'price_asc'): ?>
                    <a href="/pages/client/home.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
            <span class="filter-count"><?= count($studios) ?> studio<?= count($studios) !== 1 ? 's' : '' ?> found</span>
        </div>

        <!-- Studios Grid -->
        <?php if (empty($studios)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎙️</div>
                <h3>No studios found</h3>
                <p>Try adjusting your search or check back later.</p>
            </div>
        <?php else: ?>
            <div class="studios-grid">
                <?php foreach ($studios as $studio): ?>
                    <a href="/pages/client/studio.php?id=<?= $studio->getId() ?>" class="studio-card">
                        <div class="studio-card-img-wrap">
                            <img
                                src="<?= e(uploadUrl($studio->getCoverImage(), 'studios')) ?>"
                                alt="<?= e($studio->getName()) ?>"
                                class="studio-card-img"
                                loading="lazy">
                            <div class="studio-card-capacity">
                                👥 <?= $studio->getCapacity() ?> people
                            </div>
                        </div>
                        <div class="studio-card-body">
                            <div class="studio-card-top">
                                <h3 class="studio-card-name"><?= e($studio->getName()) ?></h3>
                                <span class="studio-card-price">
                                    <?= formatPrice($studio->getPricePerHour()) ?><small>/hr</small>
                                </span>
                            </div>
                            <?php if ($studio->getLocation()): ?>
                                <p class="studio-card-location">
                                    📍 <?= e($studio->getLocation()) ?>
                                </p>
                            <?php endif; ?>
                            <p class="studio-card-desc">
                                <?= e(truncate($studio->getDescription() ?? '', 90)) ?>
                            </p>
                            <div class="studio-card-footer">
                                <span class="badge badge-available">Available</span>
                                <span class="studio-card-cta">Book now →</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>