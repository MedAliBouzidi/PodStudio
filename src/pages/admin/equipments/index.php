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

// Delete 
if (isPost() && post('action') === 'delete') {
    $id  = postInt('id');
    $eq  = EquipmentService::findById($id);
    if ($eq) {
        deleteUploadedImage($eq->getImage(), 'equipments');
        EquipmentService::delete($id);
        flashSuccess('Equipment "' . $eq->getName() . '" deleted.');
    } else {
        flashError('Equipment not found.');
    }
    redirect('/pages/admin/equipments/index.php');
}

$equipments = EquipmentService::findAll();
$studios    = StudioService::findAll();

// Build studio lookup map
$studioMap = [];
foreach ($studios as $s) {
    $studioMap[$s->getId()] = $s->getName();
}

// Filter by studio
$filterStudio = getInt('studio_id');
if ($filterStudio) {
    $equipments = array_filter($equipments, fn($e) => $e->getStudioId() === $filterStudio);
}

$pageTitle = 'Equipments — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/equipments/index.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <div class="page-header">
            <div>
                <h1>Equipments</h1>
                <p class="text-muted text-sm mt-1"><?= count($equipments) ?> item<?= count($equipments) !== 1 ? 's' : '' ?><?= $filterStudio ? ' in ' . e($studioMap[$filterStudio] ?? '') : ' total' ?></p>
            </div>
            <a href="/pages/admin/equipments/create.php" class="btn btn-primary">+ Add Equipment</a>
        </div>

        <!-- Studio Filter -->
        <div class="filter-bar" style="margin-bottom: 1.5rem;">
            <form method="GET" action="" class="filter-form">
                <div style="position:relative;">
                    <select name="studio_id" class="form-control" style="min-width:220px; cursor:pointer;" onchange="this.form.submit()">
                        <option value="">🎙️ All Studios</option>
                        <?php foreach ($studios as $s): ?>
                            <option value="<?= $s->getId() ?>" <?= $filterStudio === $s->getId() ? 'selected' : '' ?>>
                                <?= e($s->getName()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($filterStudio): ?>
                    <a href="/pages/admin/equipments/index.php" class="btn btn-outline btn-sm">✕ Clear filter</a>
                <?php endif; ?>
            </form>
            <span class="filter-count"><?= count($equipments) ?> item<?= count($equipments) !== 1 ? 's' : '' ?></span>
        </div>


        <?php if (empty($equipments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔧</div>
                <h3>No equipment found</h3>
                <p><?= $filterStudio ? 'No equipment in this studio yet.' : 'Add equipment to your studios.' ?></p>
                <a href="/pages/admin/equipments/create.php" class="btn btn-primary mt-3">Add Equipment</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Equipment</th>
                            <th>Studio</th>
                            <th>Brand</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipments as $eq): ?>
                            <tr>
                                <td class="text-muted"><?= $eq->getId() ?></td>
                                <td>
                                    <div class="eq-cell">
                                        <img
                                            src="<?= e(uploadUrl($eq->getImage(), 'equipments')) ?>"
                                            alt="<?= e($eq->getName()) ?>"
                                            class="eq-thumb">
                                        <div>
                                            <div class="fw-600"><?= e($eq->getName()) ?></div>
                                            <?php if ($eq->getDescription()): ?>
                                                <div class="text-muted text-sm"><?= e(truncate($eq->getDescription(), 45)) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="/pages/admin/equipments/index.php?studio_id=<?= $eq->getStudioId() ?>" class="studio-link">
                                        <?= e($studioMap[$eq->getStudioId()] ?? 'Studio #' . $eq->getStudioId()) ?>
                                    </a>
                                </td>
                                <td><?= e($eq->getBrand() ?? '—') ?></td>
                                <td>
                                    <span class="qty-badge">×<?= $eq->getQuantity() ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= statusBadgeClass($eq->getStatus()->value) ?>">
                                        <?= statusLabel($eq->getStatus()->value) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/pages/admin/equipments/edit.php?id=<?= $eq->getId() ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $eq->getId() ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                data-confirm="Delete &quot;<?= e($eq->getName()) ?>&quot;?">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>