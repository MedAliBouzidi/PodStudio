<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/upload.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/Studio.php';
require_once __DIR__ . '/../../../services/StudioService.php';

requireAdmin();

$id     = getInt('id');
$studio = StudioService::findById($id);

if (!$studio) {
    flashError('Studio not found.');
    redirect('/pages/admin/studios/index.php');
}

$errors = [];

if (isPost()) {
    $name           = trim(post('name'));
    $description    = trim(post('description'));
    $location       = trim(post('location'));
    $capacity       = postInt('capacity');
    $price_per_hour = (float) post('price_per_hour');
    $status         = post('status', Status::Available->value);

    // Validation
    if (empty($name))         $errors['name']          = 'Studio name is required.';
    if ($capacity < 1)        $errors['capacity']       = 'Capacity must be at least 1.';
    if ($price_per_hour <= 0) $errors['price_per_hour'] = 'Price must be greater than 0.';

    // Image upload (optional on edit)
    $cover_image = $studio->getCoverImage();
    if (!empty($_FILES['cover_image']['name'])) {
        try {
            $cover_image = uploadImage($_FILES['cover_image'], 'studios', $studio->getCoverImage());
        } catch (RuntimeException $e) {
            $errors['cover_image'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $studio->setName($name);
        $studio->setDescription($description ?: null);
        $studio->setLocation($location ?: null);
        $studio->setCapacity($capacity);
        $studio->setPricePerHour($price_per_hour);
        $studio->setStatus(Status::from($status));
        $studio->setCoverImage($cover_image);

        $studioService = new StudioService();
        if ($studioService->update($studio)) {
            flashSuccess('Studio "' . $name . '" updated successfully.');
            redirect('/pages/admin/studios/index.php');
        } else {
            $errors['general'] = 'Failed to update studio. Please try again.';
        }
    }
}

$pageTitle = 'Edit Studio — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/studio/edit.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 760px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/studios/index.php" class="back-link">← Back to Studios</a>
                <h1 class="mt-1">Edit Studio</h1>
            </div>
            <!-- Delete button -->
            <form method="POST" action="/pages/admin/studios/index.php" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $studio->getId() ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Delete studio &quot;<?= e($studio->getName()) ?>&quot;?">
                    🗑 Delete Studio
                </button>
            </form>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error" style="position:static; margin-bottom:1.25rem; animation:none;">
                <span><?= e($errors['general']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-card">

                <h3 class="form-section-title">Basic Info</h3>

                <div class="form-group">
                    <label class="form-label">Studio Name *</label>
                    <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-error' : '' ?>"
                        value="<?= e(isPost() ? post('name') : $studio->getName()) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="form-error"><?= e($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e(isPost() ? post('description') : $studio->getDescription()) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control"
                        value="<?= e(isPost() ? post('location') : $studio->getLocation()) ?>">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Capacity (people) *</label>
                        <input type="number" name="capacity" class="form-control <?= isset($errors['capacity']) ? 'is-error' : '' ?>"
                            min="1" max="50" value="<?= isPost() ? postInt('capacity') : $studio->getCapacity() ?>" required>
                        <?php if (isset($errors['capacity'])): ?>
                            <span class="form-error"><?= e($errors['capacity']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price per Hour (TND) *</label>
                        <input type="number" name="price_per_hour" class="form-control <?= isset($errors['price_per_hour']) ? 'is-error' : '' ?>"
                            min="1" value="<?= isPost() ? post('price_per_hour') : $studio->getPricePerHour() ?>" required>
                        <?php if (isset($errors['price_per_hour'])): ?>
                            <span class="form-error"><?= e($errors['price_per_hour']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php foreach ([Status::Available, Status::InUse, Status::Maintenance] as $s): ?>
                            <option value="<?= $s->value ?>"
                                <?= (isPost() ? post('status') : $studio->getStatus()->value) === $s->value ? 'selected' : '' ?>>
                                <?= statusLabel($s->value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr class="divider">
                <h3 class="form-section-title">Cover Image</h3>

                <!-- Current image preview -->
                <div class="current-image-wrap">
                    <img
                        src="<?= e(uploadUrl($studio->getCoverImage(), 'studios')) ?>"
                        alt="Current cover"
                        class="current-image"
                        id="current-cover">
                    <span class="text-muted text-sm">Current cover image</span>
                </div>

                <div class="form-group mt-2">
                    <label class="form-label">Replace Image <span class="text-muted">(optional)</span></label>
                    <?php if (isset($errors['cover_image'])): ?>
                        <span class="form-error"><?= e($errors['cover_image']) ?></span>
                    <?php endif; ?>
                    <div class="upload-zone">
                        <input type="file" name="cover_image" accept="image/*">
                        <div class="upload-zone-inner">
                            <span style="font-size: 2rem;">🖼️</span>
                            <p class="upload-label text-sm text-muted mt-1">Click or drag to replace cover image</p>
                        </div>
                        <img class="upload-preview" alt="New preview">
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <a href="/pages/admin/studios/index.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>