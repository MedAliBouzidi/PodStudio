<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/upload.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/Equipment.php';
require_once __DIR__ . '/../../../entities/Studio.php';
require_once __DIR__ . '/../../../services/EquipmentService.php';
require_once __DIR__ . '/../../../services/StudioService.php';

requireAdmin();

$id        = getInt('id');
$equipment = EquipmentService::findById($id);

if (!$equipment) {
    flashError('Equipment not found.');
    redirect('/pages/admin/equipments/index.php');
}

$studios = StudioService::findAll();
$errors  = [];

if (isPost()) {
    $studio_id   = postInt('studio_id');
    $name        = trim(post('name'));
    $brand       = trim(post('brand'));
    $description = trim(post('description'));
    $quantity    = postInt('quantity');
    $status      = post('status', Status::Available->value);

    // Validation
    if (!$studio_id)   $errors['studio_id'] = 'Please select a studio.';
    if (empty($name))  $errors['name']       = 'Equipment name is required.';
    if ($quantity < 1) $errors['quantity']   = 'Quantity must be at least 1.';

    // Image upload (optional on edit)
    $image = $equipment->getImage();
    if (!empty($_FILES['image']['name'])) {
        try {
            $image = uploadImage($_FILES['image'], 'equipments', $equipment->getImage());
        } catch (RuntimeException $e) {
            $errors['image'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $equipment->setStudioId($studio_id);
        $equipment->setName($name);
        $equipment->setBrand($brand ?: null);
        $equipment->setDescription($description ?: null);
        $equipment->setQuantity($quantity);
        $equipment->setStatus(Status::from($status));
        $equipment->setImage($image);

        $equipmentService = new EquipmentService();
        if ($equipmentService->update($equipment)) {
            flashSuccess('Equipment "' . $name . '" updated successfully.');
            redirect('/pages/admin/equipments/index.php');
        } else {
            $errors['general'] = 'Failed to update equipment. Please try again.';
        }
    }
}

$pageTitle = 'Edit Equipment — Admin';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 760px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/equipments/index.php" class="back-link">← Back to Equipments</a>
                <h1 class="mt-1">Edit Equipment</h1>
            </div>
            <form method="POST" action="/pages/admin/equipments/index.php" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $equipment->getId() ?>">
                <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Delete &quot;<?= e($equipment->getName()) ?>&quot;?">
                    🗑 Delete
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

                <h3 class="form-section-title">Equipment Details</h3>

                <div class="form-group">
                    <label class="form-label">Studio *</label>
                    <select name="studio_id" class="form-control <?= isset($errors['studio_id']) ? 'is-error' : '' ?>">
                        <option value="">— Select a studio —</option>
                        <?php foreach ($studios as $s): ?>
                            <option value="<?= $s->getId() ?>"
                                <?= (isPost() ? postInt('studio_id') : $equipment->getStudioId()) === $s->getId() ? 'selected' : '' ?>>
                                <?= e($s->getName()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['studio_id'])): ?>
                        <span class="form-error"><?= e($errors['studio_id']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Equipment Name *</label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-error' : '' ?>"
                            value="<?= e(isPost() ? post('name') : $equipment->getName()) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <span class="form-error"><?= e($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control"
                            value="<?= e(isPost() ? post('brand') : $equipment->getBrand()) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e(isPost() ? post('description') : $equipment->getDescription()) ?></textarea>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" class="form-control <?= isset($errors['quantity']) ? 'is-error' : '' ?>"
                            min="1" max="100" value="<?= isPost() ? postInt('quantity') : $equipment->getQuantity() ?>" required>
                        <?php if (isset($errors['quantity'])): ?>
                            <span class="form-error"><?= e($errors['quantity']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ([Status::Available, Status::InUse, Status::Maintenance] as $s): ?>
                                <option value="<?= $s->value ?>"
                                    <?= (isPost() ? post('status') : $equipment->getStatus()->value) === $s->value ? 'selected' : '' ?>>
                                    <?= statusLabel($s->value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="divider">
                <h3 class="form-section-title">Equipment Image</h3>

                <div class="current-image-wrap">
                    <img
                        src="<?= e(uploadUrl($equipment->getImage(), 'equipments')) ?>"
                        alt="Current image"
                        class="current-image">
                    <span class="text-muted text-sm">Current image</span>
                </div>

                <div class="form-group mt-2">
                    <label class="form-label">Replace Image <span class="text-muted">(optional)</span></label>
                    <?php if (isset($errors['image'])): ?>
                        <span class="form-error"><?= e($errors['image']) ?></span>
                    <?php endif; ?>
                    <div class="upload-zone">
                        <input type="file" name="image" accept="image/*">
                        <div class="upload-zone-inner">
                            <span style="font-size: 2rem;">📷</span>
                            <p class="upload-label text-sm text-muted mt-1">Click or drag to replace image</p>
                        </div>
                        <img class="upload-preview" alt="New preview">
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <a href="/pages/admin/equipments/index.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>