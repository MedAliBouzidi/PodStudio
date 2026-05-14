<?php
require_once __DIR__ . '/../../../utils/session.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/upload.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../entities/Status.php';
require_once __DIR__ . '/../../../entities/Studio.php';
require_once __DIR__ . '/../../../services/StudioService.php';

requireAdmin();

// Delete
if (isPost() && post('action') === 'delete') {
    $id     = postInt('id');
    $studio = StudioService::findById($id);
    if ($studio) {
        deleteUploadedImage($studio->getCoverImage(), 'studios');
        StudioService::delete($id);
        flashSuccess('Studio "' . $studio->getName() . '" deleted.');
    } else {
        flashError('Studio not found.');
    }
    redirect('/pages/admin/studios/index.php');
}

$studios = StudioService::findAll();

$pageTitle = 'Studios — Admin';
$extraHead = '<link rel="stylesheet" href="/assets/css/admin/studio/index.css">';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/navbar.php';
?>

<div class="page-wrapper">
<div class="container">

    <div class="page-header">
        <div>
            <h1>Studios</h1>
            <p class="text-muted text-sm mt-1"><?= count($studios) ?> studio<?= count($studios) !== 1 ? 's' : '' ?> total</p>
        </div>
        <a href="/pages/admin/studios/create.php" class="btn btn-primary">+ Add Studio</a>
    </div>

    <?php if (empty($studios)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🎙️</div>
            <h3>No studios yet</h3>
            <p>Add your first studio to get started.</p>
            <a href="/pages/admin/studios/create.php" class="btn btn-primary mt-3">Add Studio</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Studio</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Price/hr</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studios as $studio): ?>
                    <tr>
                        <td class="text-muted"><?= $studio->getId() ?></td>
                        <td>
                            <div class="studio-cell">
                                <img
                                    src="<?= e(uploadUrl($studio->getCoverImage(), 'studios')) ?>"
                                    alt="<?= e($studio->getName()) ?>"
                                    class="studio-thumb"
                                >
                                <div>
                                    <div class="fw-600"><?= e($studio->getName()) ?></div>
                                    <div class="text-muted text-sm"><?= e(truncate($studio->getDescription() ?? '', 50)) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($studio->getLocation() ?? '—') ?></td>
                        <td>👥 <?= $studio->getCapacity() ?></td>
                        <td class="text-accent fw-600"><?= formatPrice($studio->getPricePerHour()) ?></td>
                        <td>
                            <span class="badge <?= statusBadgeClass($studio->getStatus()->value) ?>">
                                <?= statusLabel($studio->getStatus()->value) ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="/pages/admin/studios/edit.php?id=<?= $studio->getId() ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $studio->getId() ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Delete studio &quot;<?= e($studio->getName()) ?>&quot;? This will also delete all its equipment.">
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