<?php
/**
 * Authentication: register, login, logout, password reset token structure.
 */

class Auth
{
    public static function attempt(string $email, string $password): ?string
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return 'Email atau kata sandi tidak sesuai.';
        }
        if (!password_verify($password, $user['password_hash'])) {
            return 'Email atau kata sandi tidak sesuai.';
        }
        if ((int) $user['is_active'] !== 1) {
            return 'Akun Anda dinonaktifkan. Hubungi admin/peneliti.';
        }

        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        // MSME users also need their msme_id for scoping queries.
        if ($user['role_code'] === 'msme') {
            $m = $db->prepare('SELECT id FROM msmes WHERE user_id = ? LIMIT 1');
            $m->execute([$user['id']]);
            $msme = $m->fetch();
            $_SESSION['user']['msme_id'] = $msme['id'] ?? null;
        }

        $upd = $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $upd->execute([$user['id']]);

        session_regenerate_id(true);
        return null; // success
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Self-service registration is only for the MSME role in this prototype
     * (admin/researcher and validator accounts are provisioned by the admin).
     */
    public static function registerMsme(array $data): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';
        $businessName = trim($data['business_name'] ?? '');
        $sector = trim($data['sector'] ?? '');

        if ($name === '' || mb_strlen($name) < 3) {
            $errors[] = 'Nama minimal 3 karakter.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Kata sandi minimal 8 karakter.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Konfirmasi kata sandi tidak cocok.';
        }
        if ($businessName === '') {
            $errors[] = 'Nama usaha wajib diisi.';
        }
        if ($sector === '') {
            $errors[] = 'Sektor usaha wajib diisi.';
        }

        if ($errors) {
            return [false, $errors];
        }

        $db = Database::connection();
        $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            return [false, ['Email sudah terdaftar. Silakan login.']];
        }

        $roleStmt = $db->prepare("SELECT id FROM roles WHERE code = 'msme'");
        $roleStmt->execute();
        $roleId = $roleStmt->fetchColumn();

        $db->beginTransaction();
        try {
            $ins = $db->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)');
            $ins->execute([$roleId, $name, $email, password_hash($password, PASSWORD_BCRYPT)]);
            $userId = (int) $db->lastInsertId();

            $insM = $db->prepare(
                'INSERT INTO msmes (user_id, business_name, owner_name, sector, address, business_age_years, employee_count, monthly_turnover_category, digital_payment_usage, fintech_usage, accounting_method)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insM->execute([
                $userId,
                $businessName,
                $name,
                $sector,
                trim($data['address'] ?? ''),
                (int) ($data['business_age_years'] ?? 0) ?: null,
                (int) ($data['employee_count'] ?? 0) ?: null,
                $data['monthly_turnover_category'] ?? '<5jt',
                $data['digital_payment_usage'] ?? 'partial',
                $data['fintech_usage'] ?? 'none',
                $data['accounting_method'] ?? 'manual',
            ]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            return [false, ['Registrasi gagal: ' . $e->getMessage()]];
        }

        return [true, []];
    }

    /**
     * Structural forgot-password flow: generates a reset token record.
     * No real e-mail transport is wired up in this research prototype;
     * the reset link is surfaced on-screen for demo purposes only.
     */
    public static function requestPasswordReset(string $email): ?string
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            return null;
        }
        $token = bin2hex(random_bytes(32));
        $ins = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
        $ins->execute([$userId, $token]);
        return $token;
    }

    public static function resetPassword(string $token, string $newPassword): array
    {
        if (mb_strlen($newPassword) < 8) {
            return [false, 'Kata sandi minimal 8 karakter.'];
        }
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        if (!$reset) {
            return [false, 'Tautan reset tidak valid atau sudah kedaluwarsa.'];
        }
        $db->beginTransaction();
        $upd = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([password_hash($newPassword, PASSWORD_BCRYPT), $reset['user_id']]);
        $mark = $db->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        $mark->execute([$reset['id']]);
        $db->commit();
        return [true, 'Kata sandi berhasil diperbarui. Silakan login.'];
    }
}
