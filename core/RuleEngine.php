<?php
/**
 * RuleEngine - deterministic "smart contract" rule pipeline.
 *
 * Research disclaimer: this class simulates smart-contract style
 * validation logic using ordinary PHP + MySQL. There is no on-chain
 * execution, no gas, no consensus - it is a rule engine that mimics the
 * DECISION FLOW of a smart contract for research/demonstration purposes.
 *
 * Pipeline (per proposal):
 *   submitted -> completeness -> duplicate -> authorization -> evidence
 *   -> amount threshold -> accounting classification -> validator approval?
 *   -> transaction hash -> ledger entry -> audit trail -> accountability update
 */

class RuleEngine
{
    private static function ruleId(string $type): ?int
    {
        static $cache = [];
        if (isset($cache[$type])) return $cache[$type];
        $stmt = Database::connection()->prepare('SELECT id FROM smart_contract_rules WHERE rule_type = ? AND is_active = 1 ORDER BY sort_order ASC LIMIT 1');
        $stmt->execute([$type]);
        $id = $stmt->fetchColumn();
        return $cache[$type] = ($id ? (int) $id : null);
    }

    private static function log(int $trxId, string $type, string $step, string $result, string $notes, ?int $actorId): void
    {
        $ruleId = self::ruleId($type);
        if (!$ruleId) return;
        $stmt = Database::connection()->prepare(
            'INSERT INTO transaction_validations (transaction_id, rule_id, step_name, result, notes, validated_by) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$trxId, $ruleId, $step, $result, $notes, $actorId]);
    }

    /**
     * Runs the full pipeline for a freshly submitted transaction.
     * Returns ['status' => finalStatus, 'log' => [...messages], 'ok' => bool]
     */
    public static function process(int $transactionId, int $actorId): array
    {
        $db = Database::connection();
        $log = [];

        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$transactionId]);
        $trx = $stmt->fetch();
        if (!$trx) {
            return ['status' => null, 'ok' => false, 'log' => ['Transaksi tidak ditemukan.']];
        }

        // 1) Completeness ----------------------------------------------------
        $required = ['transaction_date', 'type', 'amount', 'payment_channel'];
        $missing = [];
        foreach ($required as $f) {
            if (empty($trx[$f])) $missing[] = $f;
        }
        if ($missing) {
            self::log($transactionId, 'completeness', 'Data Completeness Check', 'fail', 'Field kosong: ' . implode(', ', $missing), $actorId);
            self::setStatus($transactionId, 'draft');
            return ['status' => 'draft', 'ok' => false, 'log' => ['Data belum lengkap: ' . implode(', ', $missing)]];
        }
        self::log($transactionId, 'completeness', 'Data Completeness Check', 'pass', 'Semua field wajib terisi.', $actorId);
        $log[] = 'Completeness check: PASS';

        // 2) Duplicate ---------------------------------------------------------
        $windowDays = 1;
        $dup = $db->prepare(
            "SELECT id FROM transactions
             WHERE msme_id = ? AND id != ? AND amount = ? AND party_name <=> ?
             AND status != 'rejected'
             AND ABS(DATEDIFF(transaction_date, ?)) <= ?
             LIMIT 1"
        );
        $dup->execute([$trx['msme_id'], $transactionId, $trx['amount'], $trx['party_name'], $trx['transaction_date'], $windowDays]);
        $isDuplicate = (bool) $dup->fetchColumn();
        if ($isDuplicate) {
            self::log($transactionId, 'duplicate', 'Duplicate Transaction Check', 'warning', 'Terdeteksi kemiripan dengan transaksi lain dalam rentang ' . $windowDays . ' hari.', $actorId);
            $log[] = 'Duplicate check: WARNING (memerlukan validator)';
        } else {
            self::log($transactionId, 'duplicate', 'Duplicate Transaction Check', 'pass', 'Tidak ada indikasi duplikasi.', $actorId);
            $log[] = 'Duplicate check: PASS';
        }

        // 3) Authorization -------------------------------------------------
        $authOk = in_array($trx['created_by'], [$actorId], true) || self::actorIsAdmin($actorId);
        if (!$authOk) {
            self::log($transactionId, 'authorization', 'Authorization Check', 'fail', 'Aktor tidak berwenang atas transaksi ini.', $actorId);
            self::setStatus($transactionId, 'rejected', 'Gagal pemeriksaan otorisasi.');
            HashChain::append($transactionId, 'rejected', $actorId, self::snapshot($trx));
            return ['status' => 'rejected', 'ok' => false, 'log' => array_merge($log, ['Authorization check: FAIL - transaksi ditolak'])];
        }
        self::log($transactionId, 'authorization', 'Authorization Check', 'pass', 'Aktor berwenang.', $actorId);
        $log[] = 'Authorization check: PASS';

        // 4) Evidence --------------------------------------------------------
        $evidenceRequiredAmount = (float) setting('evidence_required_amount', 1000000);
        $evCount = $db->prepare('SELECT COUNT(*) FROM transaction_evidence WHERE transaction_id = ?');
        $evCount->execute([$transactionId]);
        $hasEvidence = (int) $evCount->fetchColumn() > 0;
        if ((float) $trx['amount'] >= $evidenceRequiredAmount && !$hasEvidence) {
            self::log($transactionId, 'evidence', 'Evidence Attachment Check', 'fail', 'Nominal >= ' . format_money($evidenceRequiredAmount) . ' wajib melampirkan bukti.', $actorId);
            self::setStatus($transactionId, 'draft');
            return ['status' => 'draft', 'ok' => false, 'log' => array_merge($log, ['Evidence check: FAIL - lampirkan bukti transaksi terlebih dahulu'])];
        }
        self::log($transactionId, 'evidence', 'Evidence Attachment Check', 'pass', $hasEvidence ? 'Bukti transaksi terlampir.' : 'Di bawah ambang wajib bukti.', $actorId);
        $log[] = 'Evidence check: PASS';

        // 5) Amount threshold --------------------------------------------------
        $validatorRequiredAmount = (float) setting('validator_required_amount', 5000000);
        $needsValidator = ((float) $trx['amount'] >= $validatorRequiredAmount) || $isDuplicate;
        self::log(
            $transactionId,
            'threshold',
            'Amount Threshold Check',
            $needsValidator ? 'warning' : 'pass',
            $needsValidator ? 'Nominal/duplikasi memerlukan validasi validator.' : 'Nominal dalam batas normal.',
            $actorId
        );
        $log[] = 'Threshold check: ' . ($needsValidator ? 'requires validator approval' : 'PASS');

        // 6) Accounting classification ------------------------------------
        $pair = Accounting::classify($trx['type'], $trx['payment_channel']);
        if (!$pair) {
            self::log($transactionId, 'classification', 'Accounting Classification Check', 'fail', 'Jenis transaksi tidak dikenali oleh mapping akun.', $actorId);
            self::setStatus($transactionId, 'rejected', 'Klasifikasi akuntansi gagal.');
            return ['status' => 'rejected', 'ok' => false, 'log' => array_merge($log, ['Classification check: FAIL'])];
        }
        [$debitCode, $creditCode] = $pair;
        $debitId = Accounting::accountIdByCode($debitCode);
        $creditId = Accounting::accountIdByCode($creditCode);
        $upd = $db->prepare('UPDATE transactions SET debit_account_id = ?, credit_account_id = ? WHERE id = ?');
        $upd->execute([$debitId, $creditId, $transactionId]);
        self::log($transactionId, 'classification', 'Accounting Classification Check', 'pass', "Debit: $debitCode, Kredit: $creditCode", $actorId);
        $log[] = 'Classification check: PASS (Debit ' . $debitCode . ' / Kredit ' . $creditCode . ')';

        // 7) Validator approval requirement --------------------------------
        if ($needsValidator) {
            self::log($transactionId, 'validator_approval', 'Validator Approval Requirement', 'warning', 'Menunggu keputusan validator/auditor.', $actorId);
            self::setStatus($transactionId, 'pending');
            HashChain::append($transactionId, 'submitted_pending_validation', $actorId, self::snapshot($trx));
            $log[] = 'Validator approval: REQUIRED - status set to Pending';
            return ['status' => 'pending', 'ok' => true, 'log' => $log];
        }
        self::log($transactionId, 'validator_approval', 'Validator Approval Requirement', 'pass', 'Tidak memerlukan validator (auto-validated).', $actorId);
        $log[] = 'Validator approval: not required (auto-validated)';

        // 8-11) hash -> ledger -> audit trail -> accountability -----------
        self::finalizeRecording($transactionId, $actorId);
        $log[] = 'Transaction hash generated, journal posted, audit trail appended.';

        return ['status' => 'recorded', 'ok' => true, 'log' => $log];
    }

    /**
     * Called by the validator dashboard when a pending transaction is approved.
     */
    public static function approveByValidator(int $transactionId, int $validatorId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$transactionId]);
        $trx = $stmt->fetch();
        if (!$trx || $trx['status'] !== 'pending') {
            return [false, 'Transaksi tidak dalam status Pending.'];
        }

        self::log($transactionId, 'validator_approval', 'Validator Approval Requirement', 'pass', 'Disetujui oleh validator.', $validatorId);
        self::setStatus($transactionId, 'validated', null, $validatorId);
        HashChain::append($transactionId, 'validated', $validatorId, self::snapshot($trx));
        self::finalizeRecording($transactionId, $validatorId);

        return [true, 'Transaksi disetujui dan berhasil dicatat ke jurnal.'];
    }

    public static function rejectByValidator(int $transactionId, int $validatorId, string $reason): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$transactionId]);
        $trx = $stmt->fetch();
        if (!$trx || $trx['status'] !== 'pending') {
            return [false, 'Transaksi tidak dalam status Pending.'];
        }

        self::log($transactionId, 'validator_approval', 'Validator Approval Requirement', 'fail', 'Ditolak oleh validator: ' . $reason, $validatorId);
        self::setStatus($transactionId, 'rejected', $reason, $validatorId);
        HashChain::append($transactionId, 'rejected', $validatorId, self::snapshot($trx));

        return [true, 'Transaksi ditolak.'];
    }

    /** Steps 8-11: hash, ledger entry, audit trail, accountability refresh. */
    private static function finalizeRecording(int $transactionId, int $actorId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$transactionId]);
        $trx = $stmt->fetch();

        self::setStatus($transactionId, 'recorded', null, $trx['approved_by'] ?? null);
        $stmt->execute([$transactionId]);
        $trx = $stmt->fetch();

        Accounting::postJournal($trx);
        HashChain::append($transactionId, 'recorded', $actorId, self::snapshot($trx));
        Accountability::computeAndStore((int) $trx['msme_id']);
    }

    /**
     * Reversal/adjustment entry - validated transactions are never hard-deleted.
     */
    public static function reverse(int $transactionId, int $actorId, string $reason): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM transactions WHERE id = ? LIMIT 1');
        $stmt->execute([$transactionId]);
        $trx = $stmt->fetch();
        if (!$trx || $trx['status'] !== 'recorded') {
            return [false, 'Hanya transaksi berstatus Recorded yang dapat dikoreksi melalui pembalikan (reversal).'];
        }

        $uid = generate_uid('REV');
        $ins = $db->prepare(
            'INSERT INTO transactions (msme_id, transaction_uid, transaction_date, type, party_name, description, amount, payment_channel, debit_account_id, credit_account_id, status, reversal_of_id, created_by, approved_by)
             VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, "recorded", ?, ?, ?)'
        );
        $ins->execute([
            $trx['msme_id'], $uid, $trx['type'], $trx['party_name'],
            'Reversal/Adjustment: ' . $reason . ' (ref ' . $trx['transaction_uid'] . ')',
            $trx['amount'], $trx['payment_channel'], $trx['debit_account_id'], $trx['credit_account_id'],
            $transactionId, $actorId, $actorId,
        ]);
        $reversalId = (int) $db->lastInsertId();

        $rev = $db->prepare('SELECT * FROM transactions WHERE id = ?');
        $rev->execute([$reversalId]);
        $reversalTrx = $rev->fetch();
        Accounting::postJournal($reversalTrx, true);
        HashChain::append($reversalId, 'reversal_created', $actorId, self::snapshot($reversalTrx));

        self::setStatus($transactionId, 'reversed', $reason);
        HashChain::append($transactionId, 'reversed', $actorId, self::snapshot($trx));
        Accountability::computeAndStore((int) $trx['msme_id']);

        return [true, 'Transaksi berhasil dibalik melalui entri penyesuaian ' . $uid . '.'];
    }

    private static function actorIsAdmin(int $actorId): bool
    {
        $stmt = Database::connection()->prepare('SELECT r.code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
        $stmt->execute([$actorId]);
        return $stmt->fetchColumn() === 'admin';
    }

    private static function setStatus(int $transactionId, string $status, ?string $reason = null, ?int $approvedBy = null): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE transactions SET status = ?, rejected_reason = ?, approved_by = COALESCE(?, approved_by) WHERE id = ?');
        $stmt->execute([$status, $reason, $approvedBy, $transactionId]);
    }

    private static function snapshot(array $trx): array
    {
        return [
            'transaction_uid' => $trx['transaction_uid'],
            'amount' => $trx['amount'],
            'party_name' => $trx['party_name'],
            'type' => $trx['type'],
            'status' => $trx['status'],
        ];
    }
}
