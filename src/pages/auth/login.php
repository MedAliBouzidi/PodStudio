<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../entities/Status.php';
require_once __DIR__ . '/../../entities/User.php';
require_once __DIR__ . '/../../entities/Admin.php';
require_once __DIR__ . '/../../entities/Client.php';
require_once __DIR__ . '/../../services/AdminService.php';
require_once __DIR__ . '/../../services/ClientService.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirect(isAdmin() ? '/pages/admin/dashboard.php' : '/pages/client/home.php');
}

$error = '';

if (isPost()) {
    $email    = trim(post('email'));
    $password = post('password');
    $role     = post('role', 'client');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
        return;
    }
    if ($role === 'admin') {
        $user = AdminService::login($email, $password);
        if ($user) {
            loginUser($user->getId(), 'admin', $user->getUsername(), $user->getProfilePicture());
            flashSuccess('Welcome back, ' . $user->getUsername() . '!');
            redirect('/pages/admin/dashboard.php');
            return;
        }
    }
    $user = ClientService::login($email, $password);
    if ($user) {
        loginUser($user->getId(), 'client', $user->getUsername(), $user->getProfilePicture());
        flashSuccess('Welcome back, ' . $user->getUsername() . '!');
        redirect('/pages/client/home.php');
        return;
    }

    $error = 'Invalid email or password.';
}

$pageTitle = 'Login — PodStudio';
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
        <div class="auth-card">

            <div class="auth-logo">🎙️ PodStudio</div>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to your account to continue</p>

            <?php if ($error): ?>
                <div class="flash flash-error" style="position:static; margin-bottom:1.25rem; animation:none;">
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <!-- Role Toggle -->
                <div class="role-toggle">
                    <button type="button" class="role-btn <?= post('role', 'client') === 'client' ? 'active' : '' ?>" data-role="client">
                        🎧 Client
                    </button>
                    <button type="button" class="role-btn <?= post('role', 'client') === 'admin' ? 'active' : '' ?>" data-role="admin">
                        ⚙️ Admin
                    </button>
                    <input type="hidden" name="role" id="role-input" value="<?= e(post('role', 'client')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@example.com"
                        value="<?= e(post('email')) ?>"
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-eye">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="••••••••"
                            required>
                        <button type="button" class="eye-btn" data-target="password">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">
                    Sign In
                </button>
            </form>

            <hr class="divider">

            <p class="text-center text-sm text-muted">
                Don't have an account?
                <a href="/pages/auth/register.php">Create one</a>
            </p>

        </div>
    </div>

    <style>
        .role-toggle {
            display: flex;
            gap: 0.5rem;
            background: var(--bg-3);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.3rem;
            margin-bottom: 1.5rem;
        }

        .role-btn {
            flex: 1;
            padding: 0.55rem;
            border: none;
            border-radius: calc(var(--radius) - 2px);
            background: transparent;
            color: var(--text-3);
            font-family: var(--font-body);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition);
        }

        .role-btn.active {
            background: var(--bg);
            color: var(--accent);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
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
            transition: opacity var(--transition);
            padding: 0;
            line-height: 1;
        }

        .eye-btn:hover {
            opacity: 1;
        }
    </style>

    <script src="/assets/js/index.js"></script>
    <script>
        // Role toggle
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('role-input').value = btn.dataset.role;
            });
        });

        // Password visibility toggle
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