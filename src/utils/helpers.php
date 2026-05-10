<?php

// String Helpers 

/** Sanitize output to prevent XSS */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Truncate a string to a max length */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    return mb_strlen($text) > $length
        ? mb_substr($text, 0, $length) . $suffix
        : $text;
}

// Date & Time Helpers 

/** Format a datetime string to a readable format */
function formatDate(string $date, string $format = 'd M Y'): string {
    return date($format, strtotime($date));
}

/** Format a time string to 12h format */
function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

/** Format a date range nicely */
function formatTimeRange(string $start, string $end): string {
    return formatTime($start) . ' → ' . formatTime($end);
}

// Price Helpers

/** Format a price with currency */
function formatPrice(float $price, string $currency = 'TND'): string {
    return number_format($price, 2, '.', ',') . ' ' . $currency;
}

// Status Badge Helpers 

/** Return a CSS class for a booking status */
function statusBadgeClass(string $status): string {
    return match($status) {
        'confirmed' => 'badge-confirmed',
        'pending'   => 'badge-pending',
        'canceled'  => 'badge-canceled',
        'available' => 'badge-available',
        'in_use'    => 'badge-in-use',
        'maintenance' => 'badge-maintenance',
        default     => 'badge-default',
    };
}

/** Return a label for a status value */
function statusLabel(string $status): string {
    return match($status) {
        'confirmed'   => 'Confirmed',
        'pending'     => 'Pending',
        'canceled'    => 'Canceled',
        'available'   => 'Available',
        'in_use'      => 'In Use',
        'maintenance' => 'Maintenance',
        default       => ucfirst($status),
    };
}

// Redirect Helper 

function redirect(string $url) {
    header("Location: $url");
    exit;
}

// Request Helpers 

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function post(string $key, mixed $default = '') {
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = null) {
    return $_GET[$key] ?? $default;
}

function postInt(string $key, int $default = 0): int {
    return (int) ($_POST[$key] ?? $default);
}

function getInt(string $key, int $default = 0): int {
    return (int) ($_GET[$key] ?? $default);
}

// Pagination Helper 

function paginate(array $items, int $page, int $perPage = 10): array {
    $total   = count($items);
    $pages   = (int) ceil($total / $perPage);
    $page    = max(1, min($page, $pages));
    $offset  = ($page - 1) * $perPage;
    $slice   = array_slice($items, $offset, $perPage);

    return [
        'items'    => $slice,
        'total'    => $total,
        'page'     => $page,
        'pages'    => $pages,
        'perPage'  => $perPage,
        'hasNext'  => $page < $pages,
        'hasPrev'  => $page > 1,
    ];
}