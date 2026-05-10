<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../utils/helpers.php';

$flash = getFlash();
?>

<nav class="navbar">
    <a class="navbar-brand" href="<?= isAdmin() ? '/pages/admin/dashboard.php' : '/pages/client/home.php' ?>">
        🎙️ <span>PodStudio</span>
    </a>

    <ul class="navbar-links">
        <?php if (isAdmin()): ?>
            <li><a href="/pages/admin/dashboard.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'dashboard')  ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="/pages/admin/studios/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'studios')    ? 'active' : '' ?>">Studios</a></li>
            <li><a href="/pages/admin/equipments/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'equipments') ? 'active' : '' ?>">Equipments</a></li>
            <li><a href="/pages/admin/packages/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'packages')   ? 'active' : '' ?>">Packages</a></li>
            <li><a href="/pages/admin/bookings/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'bookings')   ? 'active' : '' ?>">Bookings</a></li>
            <li><a href="/pages/admin/clients/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'clients')    ? 'active' : '' ?>">Clients</a></li>
        <?php elseif (isClient()): ?>
            <li><a href="/pages/client/home.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'home')     ? 'active' : '' ?>">Studios</a></li>
            <li><a href="/pages/client/bookings.php" class="<?= str_contains($_SERVER['REQUEST_URI'], 'bookings') ? 'active' : '' ?>">My Bookings</a></li>
        <?php endif; ?>
    </ul>

    <?php if (isLoggedIn()): ?>
        <div class="navbar-user">
            <img src="<?= e(currentPicture()) ?>" alt="avatar" class="navbar-avatar">
            <span class="navbar-username"><?= e(currentUsername()) ?></span>
            <div class="navbar-dropdown">
                <a href="<?= isAdmin() ? '#' : '/pages/client/profile.php' ?>">Profile</a>
                <a href="/pages/auth/logout.php" class="logout-link">Logout</a>
            </div>
        </div>
    <?php endif; ?>
</nav>

<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" id="flash-message">
        <span><?= e($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="flash-close">&times;</button>
    </div>
<?php endif; ?>