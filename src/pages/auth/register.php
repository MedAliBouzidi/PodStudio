<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/upload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/User.php';
require_once __DIR__ . '/../../entities/Client.php';
require_once __DIR__ . '/../../services/ClientService.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirect(isAdmin() ? '/pages/admin/dashboard.php' : '/pages/client/home.php');
}

$errors = [];
$values = [];

if (isPost()) {
    $values = [
        'full_name' => trim(post('full_name')),
        'username'  => trim(post('username')),
        'email'     => trim(post('email')),
        'phone'     => trim(post('phone')),
        'password'  => post('password'),
        'confirm'   => post('confirm_password'),
    ];

    // Validation
    if (empty($values['full_name']))
        $errors['full_name'] = 'Full name is required.';

    if (empty($values['username']))
        $errors['username'] = 'Username is required.';

    if (empty($values['email']))
        $errors['email'] = 'Email is required.';
    elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Invalid email format.';

    if (empty($values['password']))
        $errors['password'] = 'Password is required.';
    elseif (strlen($values['password']) < 6)
        $errors['password'] = 'Password must be at least 6 characters.';

    if ($values['password'] !== $values['confirm'])
        $errors['confirm'] = 'Passwords do not match.';

    // Check email uniqueness
    if (empty($errors['email']) && ClientService::findByEmail($values['email']))
        $errors['email'] = 'This email is already registered.';

    if (empty($errors)) {
        // Handle optional profile picture upload
        $picture = 'default_profile.png';
        if (!empty($_FILES['profile_picture']['name']))
            try {
                $picture = uploadImage($_FILES['profile_picture'], 'profiles');
            } catch (RuntimeException $e) {
                $errors['profile_picture'] = $e->getMessage();
            }

        if (empty($errors)) {
            $client = new Client(
                $values['full_name'],
                $values['username'],
                $values['password'],
                $values['email'],
                $values['phone']
            );
            $client->setProfilePicture($picture);

            $clientService = new ClientService();
            $id = $clientService->save($client);

            if ($id) {
                loginUser($id, 'client', $values['username'], '/public/uploads/profiles/' . $picture);
                flashSuccess('Account created! Welcome to PodStudio.');
                redirect('/pages/client/home.php');
            } else
                $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register — PodStudio';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/index.css">
</head>

<body>
    <div class="auth-page">
        <div class="auth-card" style="max-width:500px;">

            <div class="auth-logo">🎙️ PodStudio</div>
            <h1 class="auth-title">Create account</h1>
            <p class="auth-subtitle">Join PodStudio and start booking your sessions</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="flash flash-error" style="position:static; margin-bottom:1.25rem; animation:none;">
                    <span><?= e($errors['general']) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-error' : '' ?>"
                            placeholder="Ali Ben Salah" value="<?= e($values['full_name'] ?? '') ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="form-error"><?= e($errors['full_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control <?= isset($errors['username']) ? 'is-error' : '' ?>"
                            placeholder="ali_podcast" value="<?= e($values['username'] ?? '') ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <span class="form-error"><?= e($errors['username']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-error' : '' ?>"
                        placeholder="ali@example.com" value="<?= e($values['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="form-error"><?= e($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone <span class="text-muted">(optional)</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                        placeholder="0661234567" value="<?= e($values['phone'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-eye">
                            <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-error' : '' ?>"
                                placeholder="Min. 6 characters" required>
                            <button type="button" class="eye-btn" data-target="password">👁</button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <span class="form-error"><?= e($errors['password']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <div class="input-eye">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control <?= isset($errors['confirm']) ? 'is-error' : '' ?>"
                                placeholder="Repeat password" required>
                            <button type="button" class="eye-btn" data-target="confirm_password">👁</button>
                        </div>
                        <?php if (isset($errors['confirm'])): ?>
                            <span class="form-error"><?= e($errors['confirm']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Profile Picture <span class="text-muted">(optional)</span></label>
                    <div class="upload-zone">
                        <input type="file" name="profile_picture" accept="image/*">
                        <div class="upload-zone-inner">
                            <span style="font-size:2rem;">📷</span>
                            <p class="upload-label text-sm text-muted mt-1">Click or drag to upload a photo</p>
                        </div>
                        <img class="upload-preview" alt="Preview" style="max-height:120px; margin:0 auto;">
                    </div>
                    <?php if (isset($errors['profile_picture'])): ?>
                        <span class="form-error"><?= e($errors['profile_picture']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                    Create Account
                </button>
            </form>

            <hr class="divider">

            <p class="text-center text-sm text-muted">
                Already have an account?
                <a href="/pages/auth/login.php">Sign in</a>
            </p>

        </div>
    </div>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .input-eye {
            position: relative;
        }

        .input-eye .form-control {
            padding-right: 2.75rem;
        }

        .eye-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            opacity: 0.5;
            transition: opacity 0.2s;
            padding: 0;
            line-height: 1;
        }

        .eye-btn:hover {
            opacity: 1;
        }

        .form-control.is-error {
            border-color: var(--red);
        }

        .upload-zone-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            pointer-events: none;
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="/assets/js/index.js"></script>
    <script>
        document.querySelectorAll('.eye-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.textContent = input.type === 'password' ? '👁' : '🙈';
            });
        });
    </script>
</body>

</html>