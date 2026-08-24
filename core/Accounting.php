<?php
/**
 * Accounting module: chart-of-accounts classification and journal posting.
 * Deliberately simple (no multi-currency, no sub-ledgers) - this is a
 * research prototype demonstrating the fintech-transaction -> journal
 * entry link, not a full ERP/accounting system.
 */

class Accounting
{
    /** Maps a payment channel to the appropriate cash/bank/e-wallet asset account code. */
    public static function cashAccountCodeFor(string $channel): string
    {
        switch ($channel) {
            case 'bank_transfer':
                return '1010'; // Bank
            case 'qr_payment':
            case 'e_wallet':
                return '1020'; // E-Wallet
            case 'digital_financing':
                return '1010'; // financing disbursed to bank
            case 'cash':
            default:
                return '1000'; // Kas
        }
    }

    /**
     * Determines debit/credit account codes for a transaction type.
     * Returns [debitCode, creditCode] or null if the type is unrecognised.
     */
    public static function classify(string $type, string $paymentChannel): ?array
    {
        $cash = self::cashAccountCodeFor($paymentChannel);

        $map = [
            'sales' => [$cash, '4000'],
            'purchase' => ['5000', $cash],
            'operating_expense' => ['5100', $cash],
            'receivable' => ['1100', '4000'],
            'payable' => ['5000', '2000'],
            'capital' => [$cash, '3000'],
            'financing' => [$cash, '2100'],
            'other_income' => [$cash, '4900'],
            'other_expense' => ['5900', $cash],
        ];

        return $map[$type] ?? null;
    }

    public static function accountIdByCode(string $code): ?int
    {
        static $cache = [];
        if (isset($cache[$code])) {
            return $cache[$code];
        }
        $stmt = Database::connection()->prepare('SELECT id FROM accounts WHERE code = ?');
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();
        return $cache[$code] = ($id ? (int) $id : null);
    }

    /**
     * Posts a balanced double-entry journal for a recorded transaction.
     * $flip = true reverses debit/credit (used for reversal entries).
     */
    public static function postJournal(array $transaction, bool $flip = false): int
    {
        $db = Database::connection();
        $refNo = 'JRN-' . date('Ymd') . '-' . str_pad((string) $transaction['id'], 5, '0', STR_PAD_LEFT);

        $ins = $db->prepare('INSERT INTO journals (transaction_id, journal_date, reference_no, description) VALUES (?, ?, ?, ?)');
        $ins->execute([
            $transaction['id'],
            $transaction['transaction_date'],
            $refNo,
            ($flip ? 'Reversal - ' : '') . ucwords(str_replace('_', ' ', $transaction['type'])) . ' - ' . $transaction['transaction_uid'],
        ]);
        $journalId = (int) $db->lastInsertId();

        $debitAccount = $flip ? $transaction['credit_account_id'] : $transaction['debit_account_id'];
        $creditAccount = $flip ? $transaction['debit_account_id'] : $transaction['credit_account_id'];

        $insDetail = $db->prepare('INSERT INTO journal_details (journal_id, account_id, debit, credit, description) VALUES (?, ?, ?, ?, ?)');
        $insDetail->execute([$journalId, $debitAccount, $transaction['amount'], 0, $transaction['description']]);
        $insDetail->execute([$journalId, $creditAccount, 0, $transaction['amount'], $transaction['description']]);

        return $journalId;
    }

    public static function ledger(int $msmeId, ?string $from = null, ?string $to = null): array
    {
        $db = Database::connection();
        $sql = "SELECT jd.*, j.journal_date, j.reference_no, j.description AS journal_desc, a.code AS account_code, a.name AS account_name
                FROM journal_details jd
                JOIN journals j ON j.id = jd.journal_id
                JOIN transactions t ON t.id = j.transaction_id
                JOIN accounts a ON a.id = jd.account_id
                WHERE t.msme_id = ?";
        $params = [$msmeId];
        if ($from) { $sql .= ' AND j.journal_date >= ?'; $params[] = $from; }
        if ($to) { $sql .= ' AND j.journal_date <= ?'; $params[] = $to; }
        $sql .= ' ORDER BY j.journal_date ASC, jd.id ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function cashFlowSummary(int $msmeId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS ym,
                    SUM(CASE WHEN t.type IN ('sales','receivable','capital','financing','other_income') THEN t.amount ELSE 0 END) AS inflow,
                    SUM(CASE WHEN t.type IN ('purchase','operating_expense','payable','other_expense') THEN t.amount ELSE 0 END) AS outflow
             FROM transactions t
             WHERE t.msme_id = ? AND t.status = 'recorded'
             GROUP BY ym ORDER BY ym ASC"
        );
        $stmt->execute([$msmeId]);
        return $stmt->fetchAll();
    }

    public static function incomeExpenseSummary(int $msmeId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT
                SUM(CASE WHEN type IN ('sales','receivable','other_income') THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type IN ('purchase','operating_expense','payable','other_expense') THEN amount ELSE 0 END) AS total_expense
             FROM transactions WHERE msme_id = ? AND status = 'recorded'"
        );
        $stmt->execute([$msmeId]);
        return $stmt->fetch() ?: ['total_income' => 0, 'total_expense' => 0];
    }
}
