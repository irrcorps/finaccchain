<?php
/**
 * Accountability Engine.
 *
 * Computes a 0-100 "Financial Accountability Index" from 8 research
 * indicators. The formula and its weights are DELIBERATELY simple and
 * fully configurable via the `research_settings` table so the researcher
 * can adjust/justify them during the study. This index is a research
 * construct - it is NOT presented as a scientifically validated
 * instrument unless expert_validations data has actually been entered
 * (see hasExpertValidation()).
 */

class Accountability
{
    public const INDICATORS = [
        'completeness' => 'Completeness',
        'accuracy' => 'Accuracy',
        'transparency' => 'Transparency',
        'traceability' => 'Traceability',
        'timeliness' => 'Timeliness',
        'authorization' => 'Authorization',
        'internal_control' => 'Internal Control',
        'auditability' => 'Auditability',
    ];

    public static function weights(): array
    {
        $out = [];
        foreach (array_keys(self::INDICATORS) as $code) {
            $out[$code] = (float) setting('accountability_weight_' . $code, 12.5);
        }
        return $out;
    }

    private static function ratio($num, $den): float
    {
        $den = (float) $den;
        if ($den <= 0) return 100.0; // no data yet -> neutral/default full score
        return round(min(100, max(0, ($num / $den) * 100)), 2);
    }

    /**
     * Computes the 8 indicator scores (0-100) for one MSME from current data.
     */
    public static function computeIndicators(int $msmeId): array
    {
        $db = Database::connection();

        $total = (int) self::scalar($db, 'SELECT COUNT(*) FROM transactions WHERE msme_id = ? AND status != "draft"', [$msmeId]);
        $recorded = (int) self::scalar($db, 'SELECT COUNT(*) FROM transactions WHERE msme_id = ? AND status = "recorded"', [$msmeId]);
        $rejected = (int) self::scalar($db, 'SELECT COUNT(*) FROM transactions WHERE msme_id = ? AND status = "rejected"', [$msmeId]);

        // 1. Completeness: submitted transactions that passed the completeness rule.
        $completePass = (int) self::scalar($db,
            'SELECT COUNT(DISTINCT tv.transaction_id) FROM transaction_validations tv
             JOIN transactions t ON t.id = tv.transaction_id
             JOIN smart_contract_rules r ON r.id = tv.rule_id AND r.rule_type = "completeness"
             WHERE t.msme_id = ? AND tv.result = "pass"', [$msmeId]);
        $completeness = self::ratio($completePass, $total);

        // 2. Accuracy: of all processed transactions, how many ended up correctly recorded (not rejected).
        $accuracy = self::ratio($recorded, $recorded + $rejected);

        // 3. Transparency: recorded transactions that carry supporting evidence.
        $withEvidence = (int) self::scalar($db,
            'SELECT COUNT(DISTINCT t.id) FROM transactions t
             JOIN transaction_evidence te ON te.transaction_id = t.id
             WHERE t.msme_id = ? AND t.status = "recorded"', [$msmeId]);
        $transparency = self::ratio($withEvidence, $recorded);

        // 4. Traceability: integrity of the hash-chain covering this MSME's transactions.
        $chain = HashChain::verifyChain();
        $msmeTrxIds = array_column(
            (array) $db->query("SELECT id FROM transactions WHERE msme_id = $msmeId")->fetchAll(),
            'id'
        );
        $relevantEntries = array_filter($chain['rows'], fn($r) => in_array((int) $r['transaction_id'], $msmeTrxIds, true));
        $brokenRelevant = array_filter($relevantEntries, fn($r) => in_array((int) $r['id'], $chain['broken_entry_ids'], true));
        $traceability = self::ratio(count($relevantEntries) - count($brokenRelevant), count($relevantEntries));

        // 5. Timeliness: transactions recorded within an acceptable input lag.
        $maxDays = (int) setting('timeliness_max_days', 3);
        $onTime = (int) self::scalar($db,
            'SELECT COUNT(*) FROM transactions WHERE msme_id = ? AND status != "draft" AND ABS(DATEDIFF(created_at, transaction_date)) <= ?',
            [$msmeId, $maxDays]);
        $timeliness = self::ratio($onTime, $total);

        // 6. Authorization: transactions that passed the authorization rule.
        $authPass = (int) self::scalar($db,
            'SELECT COUNT(DISTINCT tv.transaction_id) FROM transaction_validations tv
             JOIN transactions t ON t.id = tv.transaction_id
             JOIN smart_contract_rules r ON r.id = tv.rule_id AND r.rule_type = "authorization"
             WHERE t.msme_id = ? AND tv.result = "pass"', [$msmeId]);
        $authorization = self::ratio($authPass, $total);

        // 7. Internal Control: high-risk transactions (validator required) that were properly resolved.
        $needValidator = (int) self::scalar($db,
            'SELECT COUNT(DISTINCT tv.transaction_id) FROM transaction_validations tv
             JOIN transactions t ON t.id = tv.transaction_id
             JOIN smart_contract_rules r ON r.id = tv.rule_id AND r.rule_type = "validator_approval"
             WHERE t.msme_id = ? AND tv.result = "warning"', [$msmeId]);
        $resolvedValidator = (int) self::scalar($db,
            'SELECT COUNT(*) FROM transactions WHERE msme_id = ? AND status IN ("recorded","rejected") AND id IN (
                SELECT tv.transaction_id FROM transaction_validations tv
                JOIN smart_contract_rules r ON r.id = tv.rule_id AND r.rule_type = "validator_approval"
                WHERE tv.result = "warning")', [$msmeId]);
        $internal_control = self::ratio($resolvedValidator, $needValidator);

        // 8. Auditability: recorded transactions that have a matching journal + audit trail entry.
        $withJournalAndAudit = (int) self::scalar($db,
            'SELECT COUNT(DISTINCT t.id) FROM transactions t
             JOIN journals j ON j.transaction_id = t.id
             JOIN audit_trails at ON at.transaction_id = t.id
             WHERE t.msme_id = ? AND t.status = "recorded"', [$msmeId]);
        $auditability = self::ratio($withJournalAndAudit, $recorded);

        return compact(
            'completeness', 'accuracy', 'transparency', 'traceability',
            'timeliness', 'authorization', 'internal_control', 'auditability'
        );
    }

    private static function scalar(PDO $db, string $sql, array $params)
    {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function computeAndStore(int $msmeId, ?int $computedBy = null): array
    {
        $indicators = self::computeIndicators($msmeId);
        $weights = self::weights();

        $map = [
            'completeness' => $indicators['completeness'],
            'accuracy' => $indicators['accuracy'],
            'transparency' => $indicators['transparency'],
            'traceability' => $indicators['traceability'],
            'timeliness' => $indicators['timeliness'],
            'authorization' => $indicators['authorization'],
            'internal_control' => $indicators['internal_control'],
            'auditability' => $indicators['auditability'],
        ];

        $overall = 0.0;
        $weightSum = array_sum($weights) ?: 100;
        foreach ($map as $code => $score) {
            $overall += $score * ($weights[$code] / $weightSum);
        }
        $overall = round($overall, 2);

        $db = Database::connection();
        $ins = $db->prepare(
            'INSERT INTO accountability_assessments (msme_id, assessment_date, period_label, overall_score, computed_by)
             VALUES (?, CURDATE(), ?, ?, ?)'
        );
        $ins->execute([$msmeId, date('M Y'), $overall, $computedBy]);
        $assessmentId = (int) $db->lastInsertId();

        $insDetail = $db->prepare(
            'INSERT INTO accountability_details (assessment_id, indicator_code, score, weight, notes) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($map as $code => $score) {
            $insDetail->execute([$assessmentId, $code, $score, $weights[$code], self::note($code, $score)]);
        }

        return ['assessment_id' => $assessmentId, 'overall_score' => $overall, 'indicators' => $map];
    }

    private static function note(string $code, float $score): string
    {
        if ($score >= 85) return 'Baik';
        if ($score >= 70) return 'Cukup, perlu perhatian';
        return 'Lemah, perlu tindak lanjut';
    }

    public static function recommendation(string $code): string
    {
        $map = [
            'completeness' => 'Lengkapi seluruh field wajib (tanggal, jenis, nominal, channel pembayaran) sebelum submit transaksi.',
            'accuracy' => 'Tingkatkan ketelitian input transaksi agar tidak sering ditolak oleh rule engine.',
            'transparency' => 'Lampirkan bukti transaksi (struk/invoice/screenshot) terutama untuk nominal signifikan.',
            'traceability' => 'Pastikan tidak ada intervensi manual pada basis data yang dapat memutus rantai hash audit trail.',
            'timeliness' => 'Input transaksi sesegera mungkin, idealnya di hari yang sama dengan tanggal transaksi.',
            'authorization' => 'Pastikan hanya pengguna berwenang (pemilik UMKM/admin) yang menginput transaksi.',
            'internal_control' => 'Percepat tindak lanjut validator/auditor atas transaksi yang memerlukan persetujuan.',
            'auditability' => 'Pastikan setiap transaksi yang tercatat memiliki jurnal dan jejak audit yang lengkap.',
        ];
        return $map[$code] ?? 'Tinjau kembali proses terkait indikator ini.';
    }

    public static function latest(int $msmeId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM accountability_assessments WHERE msme_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$msmeId]);
        $assessment = $stmt->fetch();
        if (!$assessment) return null;

        $d = $db->prepare('SELECT * FROM accountability_details WHERE assessment_id = ?');
        $d->execute([$assessment['id']]);
        $assessment['details'] = $d->fetchAll();
        return $assessment;
    }

    public static function hasExpertValidation(): bool
    {
        $db = Database::connection();
        return (int) $db->query('SELECT COUNT(*) FROM expert_validations')->fetchColumn() > 0;
    }
}
