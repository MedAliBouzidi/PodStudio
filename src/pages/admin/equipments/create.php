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
    if (!$studio_id)    $errors['studio_id'] = 'Please select a studio.';
    if (empty($name))   $errors['name']       = 'Equipment name is required.';
    if ($quantity < 1)  $errors['quantity']   = 'Quantity must be at least 1.';

    // Image upload
    $image = 'equipment_default.png';
    if (!empty($_FILES['image']['name'])) {
        try {
            $image = uploadImage($_FILES['image'], 'equipments');
        } catch (RuntimeException $e) {
            $errors['image'] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $equipment = new Equipment(
            $studio_id,
            $name,
            $brand ?: null,
            $description ?: null,
            $image,
            $quantity,
            Status::from($status)
        );

        $equipmentService = new EquipmentService();
        $id = $equipmentService->save($equipment);

        if ($id) {
            flashSuccess('Equipment "' . $name . '" added successfully.');
            redirect('/pages/admin/equipments/index.php');
        } else {
            $errors['general'] = 'Failed to add equipment. Please try again.';
        }
    }
}

$pageTitle = 'Add Equipment — Admin';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 760px;">

        <div class="page-header">
            <div>
                <a href="/pages/admin/equipments/index.php" class="back-link">← Back to Equipments</a>
                <h1 class="mt-1">Add New Equipment</h1>
            </div>
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
                            <option value="<?= $s->getId() ?>" <?= postInt('studio_id') === $s->getId() ? 'selected' : '' ?>>
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
                            placeholder="e.g. Condenser Microphone" value="<?= e(post('name')) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <span class="form-error"><?= e($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control"
                            placeholder="e.g. Rode, Shure, Sony" value="<?= e(post('brand')) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Brief description of the equipment..."><?= e(post('description')) ?></textarea>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" class="form-control <?= isset($errors['quantity']) ? 'is-error' : '' ?>"
                            min="1" max="100" placeholder="1" value="<?= e(post('quantity', '1')) ?>" required>
                        <?php if (isset($errors['quantity'])): ?>
                            <span class="form-error"><?= e($errors['quantity']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ([Status::Available, Status::InUse, Status::Maintenance] as $s): ?>
                                <option value="<?= $s->value ?>" <?= post('status', Status::Available->value) === $s->value ? 'selected' : '' ?>>
                                    <?= statusLabel($s->value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="divider">
                <h3 class="form-section-title">Equipment Image</h3>

                <div class="form-group">
                    <?php if (isset($errors['image'])): ?>
                        <span class="form-error"><?= e($errors['image']) ?></span>
                    <?php endif; ?>
                    <div class="upload-zone">
                        <input type="file" name="image" accept="image/*">
                        <div class="upload-zone-inner">
                            <span style="font-size: 2.5rem;">📷</span>
                            <p class="upload-label text-sm text-muted mt-1">Click or drag to upload equipment image</p>
                            <p class="text-sm text-muted">JPG, PNG, WEBP — max 5MB</p>
                        </div>
                        <img class="upload-preview" alt="Preview">
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <a href="/pages/admin/equipments/index.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Equipment</button>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>