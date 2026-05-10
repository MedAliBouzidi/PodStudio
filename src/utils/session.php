<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth Checkers
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function isClient(): bool
{
    return isLoggedIn() && $_SESSION['user_role'] === 'client';
}

// Auth Guards

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: /pages/auth/login.php");
        exit;
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header("Location: /pages/client/home.php");
        exit;
    }
}

function requireClient()
{
    requireLogin();
    if (!isClient()) {
        header("Location: /pages/admin/dashboard.php");
        exit;
    }
}

// Session Helpers

function loginUser(int $id, string $role, string $username, string $picture)
{
    $_SESSION['user_id']      = $id;
    $_SESSION['user_role']    = $role;
    $_SESSION['username']     = $username;
    $_SESSION['profile_picture'] = $picture;
}

function logoutUser()
{
    $_SESSION = [];
    session_destroy();
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentUsername(): string
{
    return $_SESSION['username'] ?? 'Guest';
}

function currentPicture(): string
{
    return $_SESSION['profile_picture'] ?? '/public/images/default_profile.png';
}

function currentRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

// Flash Messages

function setFlash(string $type, string $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function flashSuccess(string $message)
{
    setFlash('success', $message);
}
function flashError(string $message)
{
    setFlash('error',   $message);
}
function flashInfo(string $message)
{
    setFlash('info',    $message);
}
