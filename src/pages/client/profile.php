<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/upload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/User.php';
require_once __DIR__ . '/../../entities/Client.php';
require_once __DIR__ . '/../../services/ClientService.php';
require_once __DIR__ . '/../../services/BookingService.php';

requireClient();

$client = ClientService::findById(currentUserId());
if (!$client) {
    flashError('Account not found.');
    redirect('/pages/auth/logout.php');
}

$errors = [];
$success = false;

if (isPost()) {
    $action = post('action');

    // Update profile info
    if ($action === 'update_profile') {
        $full_name = trim(post('full_name'));
        $phone     = trim(post('phone'));

        if (empty($full_name)) $errors['full_name'] = 'Full name is required.';

        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['name'])) {
            try {
                $picture = uploadImage($_FILES['profile_picture'], 'profiles', $client->getProfilePicture());
                $client->setProfilePicture($picture);
                $_SESSION['profile_picture'] = '/public/uploads/profiles/' . $picture;
            } catch (RuntimeException $e) {
                $errors['profile_picture'] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            $client->setFullName($full_name);
            $client->setPhone($phone);

            $clientService = new ClientService();
            if ($clientService->update($client)) {
                flashSuccess('Profile updated successfully.');
                redirect('/pages/client/profile.php');
            } else {
                $errors['general'] = 'Update failed. Please try again.';
            }
        }
    }

    // Change password
    if ($action === 'change_password') {
        $current  = post('current_password');
        $new      = post('new_password');
        $confirm  = post('confirm_password');

        if (empty($current))
            $errors['current'] = 'Current password is required.';

        if (empty($new))
            $errors['new'] = 'New password is required.';

        elseif (strlen($new) < 6)
            $errors['new'] = 'Password must be at least 6 characters.';

        if ($new !== $confirm)
            $errors['confirm'] = 'Passwords do not match.';

        if (md5($current) !== $client->getPassword())
            $errors['current'] = 'Current password is incorrect.';

        if (empty($errors)) {
            $client->setPassword(md5($new));
            // Direct PDO update for password (already hashed)
            $pdo  = \Database::getConnection();
            $stmt = $pdo->prepare("UPDATE clients SET password = :password WHERE id = :id");
            if ($stmt->execute([':password' => md5($new), ':id' => $client->getId()])) {
                flashSuccess('Password changed successfully.');
                redirect('/pages/client/profile.php');
            } else {
                $errors['general_pw'] = 'Failed to update password. Try again.';
            }
        }
    }
}

// Stats
$bookings   = BookingService::findByUser(currentUserId());
$confirmed  = count(array_filter($bookings, fn($b) => $b->getStatus() === 'confirmed'));
$pending    = count(array_filter($bookings, fn($b) => $b->getStatus() === 'pending'));
$totalSpent = array_sum(array_map(fn($b) => $b->getStatus() !== 'canceled' ? $b->getTotalPrice() : 0, $bookings));

$pageTitle = 'My Profile — PodStudio';
$extraHead = '<link rel="stylesheet" href="/assets/css/client/profile.css">';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width: 900px;">

        <div class="page-header">
            <h1>My Profile</h1>
        </div>

        <!-- Stats Row -->
        <div class="profile-stats">
            <div class="profile-stat">
                <span class="stat-value"><?= count($bookings) ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
            <div class="profile-stat">
                <span class="stat-value"><?= $confirmed ?></span>
                <span class="stat-label">Confirmed</span>
            </div>
            <div class="profile-stat">
                <span class="stat-value"><?= $pending ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="profile-stat">
                <span class="stat-value"><?= number_format($totalSpent / 1000, 1) ?>k</span>
                <span class="stat-label">TND Spent</span>
            </div>
        </div>

        <div class="profile-layout">

            <!-- Profile Info Form -->
            <div class="profile-card">
                <div class="profile-avatar-section">
                    <img
                        src="<?= e(currentPicture()) ?>"
                        alt="Avatar"
                        class="profile-avatar"
                        id="avatar-preview">
                    <div>
                        <h2><?= e($client->getFullName()) ?></h2>
                        <p class="text-muted text-sm">@<?= e($client->getUsername()) ?></p>
                        <p class="text-muted text-sm">Member since <?= formatDate($client->getCreatedAt(), 'M Y') ?></p>
                    </div>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="flash flash-error" style="position:static; margin-bottom:1rem; animation:none;">
                        <span><?= e($errors['general']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <div class="upload-zone" style="padding: 1rem;">
                            <input type="file" name="profile_picture" accept="image/*" id="avatar-input">
                            <div class="upload-zone-inner">
                                <p class="upload-label text-sm text-muted">Click to change photo</p>
                            </div>
                            <img class="upload-preview" id="avatar-new-preview" alt="New preview" style="max-height:100px; margin: 0.5rem auto 0;">
                        </div>
                        <?php if (isset($errors['profile_picture'])): ?>
                            <span class="form-error"><?= e($errors['profile_picture']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-error' : '' ?>"
                            value="<?= e($client->getFullName()) ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="form-error"><?= e($errors['full_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= e($client->getUsername()) ?>" disabled>
                        <span class="form-hint">Username cannot be changed.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?= e($client->getEmail()) ?>" disabled>
                        <span class="form-hint">Email cannot be changed.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control"
                            value="<?= e($client->getPhone()) ?>" placeholder="0661234567">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h3 style="margin-bottom: 1.5rem;">🔐 Change Password</h3>

                <?php if (!empty($errors['general_pw'])): ?>
                    <div class="flash flash-error" style="position:static; margin-bottom:1rem; animation:none;">
                        <span><?= e($errors['general_pw']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password"
                            class="form-control <?= isset($errors['current']) ? 'is-error' : '' ?>"
                            placeholder="••••••••" required>
                        <?php if (isset($errors['current'])): ?>
                            <span class="form-error"><?= e($errors['current']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password"
                            class="form-control <?= isset($errors['new']) ? 'is-error' : '' ?>"
                            placeholder="Min. 6 characters" required>
                        <?php if (isset($errors['new'])): ?>
                            <span class="form-error"><?= e($errors['new']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password"
                            class="form-control <?= isset($errors['confirm']) ? 'is-error' : '' ?>"
                            placeholder="Repeat new password" required>
                        <?php if (isset($errors['confirm'])): ?>
                            <span class="form-error"><?= e($errors['confirm']) ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-outline">Update Password</button>
                </form>

                <hr class="divider">

                <div>
                    <h4 style="margin-bottom: 0.5rem; color: var(--red);">Danger Zone</h4>
                    <p class="text-sm text-muted mb-2">Sign out from your account on this device.</p>
                    <a href="/pages/auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="/assets/js/index.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>