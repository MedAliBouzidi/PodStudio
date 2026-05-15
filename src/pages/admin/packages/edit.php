<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Package.php';
require_once __DIR__ . '/../../../services/PackageService.php';

requireAdmin();

$id      = getInt('id');
$package = PackageService::findById($id);

if (!$package) {
    flashError('Package not found.');
    redirect('/pages/admin/packages/index.php');
}

$errors = [];

if (isPost()) {
    $name               = trim(post('name'));
    $description        = trim(post('description'));
    $price              = (float) post('price');
    $duration_hours     = postInt('duration_hours');
    $includes_equipment = (bool) postInt('includes_equipment');

    // Validation
    if (empty($name))        $errors['name']           = 'Package name is required.';
    if ($price <= 0)         $errors['price']          = 'Price must be greater than 0.';
    if ($duration_hours < 1) $errors['duration_hours'] = 'Duration must be at least 1 hour.';

    if (empty($errors)) {
        $package->setName($name);
        $package->setDescription($description ?: null);
        $package->setPrice($price);
        $package->setDurationHours($duration_hours);
        $package->setIncludesEquipment($includes_equipment);

        $packageService = new PackageService();
        if ($packageService->update($package)) {
            flashSuccess('Package "' . $name . '" updated successfully.');
            redirect('/pages/admin/packages/index.php');
        } else {
            $errors['general'] = 'Failed to update package. Please try again.';
        }
    }
}

// Use submitted values on error, otherwise use current package values
$val = [
    'name'               => isPost() ? post('name')                        : $package->getName(),
    'description'        => isPost() ? post('description')                 : $package->getDescription(),
    'price'              => isPost() ? post('price')                       : $package->getPrice(),
    'duration_hours'     => isPost() ? postInt('duration_hours')           : $package->getDurationHours(),
    'includes_equipment' => isPost() ? (bool) postInt('includes_equipment') : $package->getIncludesEquipment(),
];

$pageTitle = 'Edit Package — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/packages/edit.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 680px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/packages/index.php" class="back-link">← Back to Packages</a>
                <h1 class="mt-1">Edit Package</h1>
            </div>
            <form method="POST" action="/pages/admin/packages/index.php" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $package->getId() ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Delete package &quot;<?= e($package->getName()) ?>&quot;?">
                    🗑 Delete
                </button>
            </form>
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
                        value="<?= e($val['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="form-error"><?= e($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($val['description']) ?></textarea>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Price (TND) *</label>
                        <input type="number" name="price" class="form-control <?= isset($errors['price']) ? 'is-error' : '' ?>"
                            min="0.01" step="0.01" value="<?= e($val['price']) ?>" required>
                        <?php if (isset($errors['price'])): ?>
                            <span class="form-error"><?= e($errors['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Duration (hours) *</label>
                        <input type="number" name="duration_hours" class="form-control <?= isset($errors['duration_hours']) ? 'is-error' : '' ?>"
                            min="1" max="24" value="<?= e($val['duration_hours']) ?>" required>
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
                                <?= $val['includes_equipment'] ? 'checked' : '' ?>>
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
                        <span class="pkg-preview-name" id="prev-name"><?= e($val['name']) ?></span>
                        <span class="pkg-preview-price"><span id="prev-price"><?= number_format($val['price'], 2) ?></span> TND</span>
                    </div>
                    <div class="pkg-preview-meta">
                        <span>⏱ <span id="prev-hours"><?= $val['duration_hours'] ?></span>h session</span>
                        <span id="prev-eq"><?= $val['includes_equipment'] ? '✅ Equipment included' : '❌ No equipment' ?></span>
                    </div>
                    <p class="pkg-preview-desc text-sm text-muted" id="prev-desc"><?= e($val['description'] ?: 'Description will appear here...') ?></p>
                </div>

            </div>

            <div class="form-actions">
                <a href="/pages/admin/packages/index.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

    </div>
</div>

<script src="/assets/js/index.js"></script>
<script src="/assets/js/admin/packages/edit.js"></script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>