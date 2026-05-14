<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/upload.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/Studio.php';
require_once __DIR__ . '/../../../services/StudioService.php';

requireAdmin();

$errors = [];

if (isPost()) {
    $name          = trim(post('name'));
    $description   = trim(post('description'));
    $location      = trim(post('location'));
    $capacity      = postInt('capacity');
    $price_per_hour = (float) post('price_per_hour');
    $status        = post('status', Status::Available->value);

    // Validation
    if (empty($name))          $errors['name']           = 'Studio name is required.';
    if ($capacity < 1)         $errors['capacity']        = 'Capacity must be at least 1.';
    if ($price_per_hour <= 0)  $errors['price_per_hour']  = 'Price must be greater than 0.';

    // Image upload
    $cover_image = 'studio_default.png';
    if (!empty($_FILES['cover_image']['name'])) {
        try {
            $cover_image = uploadImage($_FILES['cover_image'], 'studios');
        } catch (RuntimeException $e) {
            $errors['cover_image'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $studio = new Studio(
            $name,
            $capacity,
            $price_per_hour,
            $description ?: null,
            $location ?: null,
            $cover_image,
            Status::from($status)
        );

        $studioService = new StudioService();
        $id = $studioService->save($studio);

        if ($id) {
            flashSuccess('Studio "' . $name . '" created successfully.');
            redirect('/pages/admin/studios/index.php');
        } else {
            $errors['general'] = 'Failed to create studio. Please try again.';
        }
    }
}

$pageTitle = 'Add Studio — Admin';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 760px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/studios/index.php" class="back-link">← Back to Studios</a>
                <h1 class="mt-1">Add New Studio</h1>
            </div>
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
                        placeholder="e.g. Studio Alpha" value="<?= e(post('name')) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="form-error"><?= e($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Describe the studio — equipment, atmosphere, best use cases..."><?= e(post('description')) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control"
                        placeholder="e.g. Algiers - Hydra" value="<?= e(post('location')) ?>">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Capacity (people) *</label>
                        <input type="number" name="capacity" class="form-control <?= isset($errors['capacity']) ? 'is-error' : '' ?>"
                            min="1" max="50" placeholder="4" value="<?= e(post('capacity')) ?>" required>
                        <?php if (isset($errors['capacity'])): ?>
                            <span class="form-error"><?= e($errors['capacity']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price per Hour (TND) *</label>
                        <input type="number" name="price_per_hour" class="form-control <?= isset($errors['price_per_hour']) ? 'is-error' : '' ?>"
                            min="0" placeholder="2500" value="<?= e(post('price_per_hour')) ?>" required>
                        <?php if (isset($errors['price_per_hour'])): ?>
                            <span class="form-error"><?= e($errors['price_per_hour']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (Status::cases() as $s):
                            if (!in_array($s, [Status::Available, Status::InUse, Status::Maintenance])) continue;
                        ?>
                            <option value="<?= $s->value ?>" <?= post('status', Status::Available->value) === $s->value ? 'selected' : '' ?>>
                                <?= statusLabel($s->value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr class="divider">
                <h3 class="form-section-title">Cover Image</h3>

                <div class="form-group">
                    <?php if (isset($errors['cover_image'])): ?>
                        <span class="form-error"><?= e($errors['cover_image']) ?></span>
                    <?php endif; ?>
                    <div class="upload-zone">
                        <input type="file" name="cover_image" accept="image/*">
                        <div class="upload-zone-inner">
                            <span style="font-size: 2.5rem;">🖼️</span>
                            <p class="upload-label text-sm text-muted mt-1">Click or drag to upload studio cover image</p>
                            <p class="text-sm text-muted">JPG, PNG, WEBP — max 5MB</p>
                        </div>
                        <img class="upload-preview" alt="Preview">
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <a href="/pages/admin/studios/index.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Studio</button>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>