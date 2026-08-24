<?php
/**
 * Shared helper functions used across the whole application.
 */

function base_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return APP_URL_BASE . '/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . (strpos($path, 'http') === 0 ? $path : base_url($path)));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, $default = '')
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

/* ---------------------------------------------------------------------
 * CSRF protection
 * ------------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Sesi form kedaluwarsa (CSRF tidak valid). Silakan muat ulang halaman dan coba lagi.');
    }
}

/* ---------------------------------------------------------------------
 * Auth/session guards
 * ------------------------------------------------------------------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect('auth/login.php');
    }
}

function require_role($roles): void
{
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array(current_user()['role_code'], $roles, true)) {
        http_response_code(403);
        die('Akses ditolak: peran Anda tidak memiliki izin untuk halaman ini.');
    }
}

/* ---------------------------------------------------------------------
 * Formatting
 * ------------------------------------------------------------------- */
function format_money($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function format_date(?string $date, string $fmt = 'd M Y'): string
{
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date($fmt, $ts) : '-';
}

function format_datetime(?string $date): string
{
    return format_date($date, 'd M Y H:i');
}

function status_badge(string $status): string
{
    $map = [
        'draft' => 'secondary',
        'pending' => 'warning',
        'validated' => 'info',
        'rejected' => 'danger',
        'recorded' => 'success',
        'reversed' => 'dark',
        'pass' => 'success',
        'fail' => 'danger',
        'warning' => 'warning',
        'success' => 'success',
        'failed' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary',
        'suspended' => 'danger',
    ];
    $color = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
}

/* ---------------------------------------------------------------------
 * Misc
 * ------------------------------------------------------------------- */
function generate_uid(string $prefix): string
{
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $rows = Database::connection()->query('SELECT setting_key, setting_value FROM research_settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        die('Metode tidak diizinkan.');
    }
}

function paginate_query(int $total, int $page, int $perPage = 10): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return [$page, $totalPages, $offset];
}

function render_pagination(int $page, int $totalPages, string $baseQuery): void
{
    if ($totalPages <= 1) return;
    echo '<nav><ul class="pagination pagination-sm">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $baseQuery . '&page=' . $i . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

function render_empty_state(string $icon, string $text): void
{
    echo '<div class="fac-empty"><i class="fa-solid ' . e($icon) . '"></i><p class="mb-0">' . e($text) . '</p></div>';
}
