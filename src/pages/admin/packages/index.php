<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Package.php';
require_once __DIR__ . '/../../../services/PackageService.php';

requireAdmin();

// ── Delete ────────────────────────────────────────────────────
if (isPost() && post('action') === 'delete') {
    $id  = postInt('id');
    $pkg = PackageService::findById($id);
    if ($pkg) {
        PackageService::delete($id);
        flashSuccess('Package "' . $pkg->getName() . '" deleted.');
    } else {
        flashError('Package not found.');
    }
    redirect('/pages/admin/packages/index.php');
}

$packages = PackageService::findAll();

$pageTitle = 'Packages — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/packages/index.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container">

        <div class="page-header">
            <div>
                <h1>Packages</h1>
                <p class="text-muted text-sm mt-1"><?= count($packages) ?> package<?= count($packages) !== 1 ? 's' : '' ?> available</p>
            </div>
            <a href="/pages/admin/packages/create.php" class="btn btn-primary">+ Add Package</a>
        </div>

        <?php if (empty($packages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>No packages yet</h3>
                <p>Create your first package to offer clients add-on sessions.</p>
                <a href="/pages/admin/packages/create.php" class="btn btn-primary mt-3">Add Package</a>
            </div>
        <?php else: ?>

            <div class="packages-grid">
                <?php foreach ($packages as $pkg): ?>
                    <div class="pkg-card">
                        <div class="pkg-card-header">
                            <div>
                                <h3 class="pkg-name"><?= e($pkg->getName()) ?></h3>
                                <p class="pkg-desc text-sm text-muted"><?= e(truncate($pkg->getDescription() ?? '', 80)) ?></p>
                            </div>
                            <div class="pkg-price">
                                <?= number_format($pkg->getPrice(), 2) ?>
                                <small>TND</small>
                            </div>
                        </div>

                        <div class="pkg-meta">
                            <div class="pkg-meta-item">
                                <span class="pkg-meta-icon">⏱</span>
                                <span><?= $pkg->getDurationHours() ?> hour<?= $pkg->getDurationHours() !== 1 ? 's' : '' ?></span>
                            </div>
                            <div class="pkg-meta-item">
                                <span class="pkg-meta-icon"><?= $pkg->getIncludesEquipment() ? '✅' : '❌' ?></span>
                                <span>Equipment <?= $pkg->getIncludesEquipment() ? 'included' : 'not included' ?></span>
                            </div>
                            <div class="pkg-meta-item">
                                <span class="pkg-meta-icon">📅</span>
                                <span>Added <?= formatDate($pkg->getCreatedAt(), 'd M Y') ?></span>
                            </div>
                        </div>

                        <div class="pkg-actions">
                            <a href="/pages/admin/packages/edit.php?id=<?= $pkg->getId() ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $pkg->getId() ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Delete package &quot;<?= e($pkg->getName()) ?>&quot;?">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>