<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Package.php';
require_once __DIR__ . '/../../../services/PackageService.php';

requireAdmin();

$errors = [];

if (isPost()) {
    $name               = trim(post('name'));
    $description        = trim(post('description'));
    $price              = (float) post('price');
    $duration_hours     = postInt('duration_hours');
    $includes_equipment = (bool) postInt('includes_equipment');

    // Validation
    if (empty($name))         $errors['name']           = 'Package name is required.';
    if ($price <= 0)          $errors['price']          = 'Price must be greater than 0.';
    if ($duration_hours < 1)  $errors['duration_hours'] = 'Duration must be at least 1 hour.';

    if (empty($errors)) {
        $package = new Package(
            $name,
            $price,
            $duration_hours,
            $description ?: null,
            $includes_equipment
        );

        $packageService = new PackageService();
        $id = $packageService->save($package);

        if ($id) {
            flashSuccess('Package "' . $name . '" created successfully.');
            redirect('/pages/admin/packages/index.php');
        } else {
            $errors['general'] = 'Failed to create package. Please try again.';
        }
    }
}

$pageTitle = 'Add Package — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/packages/create.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 680px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/packages/index.php" class="back-link">← Back to Packages</a>
                <h1 class="mt-1">Add New Package</h1>
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error" style="position:static; margin-bottom:1.25rem; animation:none;">
                <span><?= e($errors['general']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-card">

                <h3 class="form-section-title">Package Details</h3>

                <div class="form-group">
                    <label class="form-label">Package Name *</label>
                    <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-error' : '' ?>"
                        placeholder="e.g. Creator Pack" value="<?= e(post('name')) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="form-error"><?= e($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="What does this package include?"><?= e(post('description')) ?></textarea>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Price (TND) *</label>
                        <input type="number" name="price" class="form-control <?= isset($errors['price']) ? 'is-error' : '' ?>"
                            min="0.01" step="0.01" placeholder="150.00" value="<?= e(post('price')) ?>" required>
                        <?php if (isset($errors['price'])): ?>
                            <span class="form-error"><?= e($errors['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (hours) *</label>
                        <input type="number" name="duration_hours" class="form-control <?= isset($errors['duration_hours']) ? 'is-error' : '' ?>"
                            min="1" max="24" placeholder="4" value="<?= e(post('duration_hours')) ?>" required>
                        <?php if (isset($errors['duration_hours'])): ?>
                            <span class="form-error"><?= e($errors['duration_hours']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="divider">
                <h3 class="form-section-title">Options</h3>

                <div class="toggle-group">
                    <label class="toggle-label">
                        <div>
                            <span class="toggle-title">Includes Equipment</span>
                            <span class="toggle-desc">Client gets access to studio equipment with this package</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="hidden" name="includes_equipment" value="0">
                            <input type="checkbox" name="includes_equipment" value="1" id="includes_equipment"
                                <?= post('includes_equipment', '1') === '1' ? 'checked' : '' ?>>
                            <span class="toggle-track">
                                <span class="toggle-thumb"></span>
                            </span>
                        </div>
                    </label>
                </div>

                <!-- Live Preview -->
                <hr class="divider">
                <h3 class="form-section-title">Preview</h3>
                <div class="pkg-preview">
                    <div class="pkg-preview-top">
                        <span class="pkg-preview-name" id="prev-name">Package Name</span>
                        <span class="pkg-preview-price"><span id="prev-price">0.00</span> TND</span>
                    </div>
                    <div class="pkg-preview-meta">
                        <span>⏱ <span id="prev-hours">0</span>h session</span>
                        <span id="prev-eq">✅ Equipment included</span>
                    </div>
                    <p class="pkg-preview-desc text-sm text-muted" id="prev-desc">Description will appear here...</p>
                </div>

            </div>

            <div class="form-actions">
                <a href="/pages/admin/packages/index.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Package</button>
            </div>
        </form>

    </div>
</div>

<script src="/assets/js/index.js"></script>
<script src="/assets/js/admin/packages/create.js"></script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>