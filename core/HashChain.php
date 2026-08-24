<?php
/**
 * HashChain - conceptual hash-chain simulation for the audit trail.
 *
 * IMPORTANT (research disclaimer): this is a deterministic SHA-256 chain
 * stored in the relational database (audit_trails table). It demonstrates
 * the tamper-evidence CONCEPT behind blockchain/smart-contract ledgers but
 * is NOT a distributed ledger, is NOT mined/replicated, and is NOT connected
 * to any real blockchain network.
 */

class HashChain
{
    public static function lastHash(): string
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT current_hash FROM audit_trails ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        return $row['current_hash'] ?? str_repeat('0', 64);
    }

    public static function computeHash(int $transactionId, string $timestamp, string $amount, string $party, string $previousHash): string
    {
        $payload = $transactionId . '|' . $timestamp . '|' . $amount . '|' . $party . '|' . $previousHash;
        return hash('sha256', $payload);
    }

    /**
     * Appends one immutable audit-trail entry chained to the previous hash.
     */
    public static function append(int $transactionId, string $action, ?int $actorId, array $snapshot): array
    {
        $db = Database::connection();
        $previousHash = self::lastHash();
        $timestamp = date('Y-m-d H:i:s');
        $amount = (string) ($snapshot['amount'] ?? '0');
        $party = (string) ($snapshot['party_name'] ?? '-');

        $currentHash = self::computeHash($transactionId, $timestamp, $amount, $party, $previousHash);

        $stmt = $db->prepare(
            'INSERT INTO audit_trails (transaction_id, action, actor_id, current_hash, previous_hash, payload_snapshot, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $transactionId,
            $action,
            $actorId,
            $currentHash,
            $previousHash,
            json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            $timestamp,
        ]);

        return ['current_hash' => $currentHash, 'previous_hash' => $previousHash, 'created_at' => $timestamp];
    }

    /**
     * Recomputes every link in the global chain and reports the first break, if any.
     */
    public static function verifyChain(): array
    {
        $db = Database::connection();
        $rows = $db->query('SELECT * FROM audit_trails ORDER BY id ASC')->fetchAll();

        $expectedPrevious = str_repeat('0', 64);
        $broken = [];

        foreach ($rows as $row) {
            if ($row['previous_hash'] !== $expectedPrevious) {
                $broken[] = $row['id'];
            }
            $snapshot = json_decode($row['payload_snapshot'], true) ?: [];
            $recomputed = self::computeHash(
                (int) $row['transaction_id'],
                $row['created_at'],
                (string) ($snapshot['amount'] ?? '0'),
                (string) ($snapshot['party_name'] ?? '-'),
                $row['previous_hash']
            );
            if ($recomputed !== $row['current_hash']) {
                $broken[] = $row['id'];
            }
            $expectedPrevious = $row['current_hash'];
        }

        return [
            'total_entries' => count($rows),
            'is_valid' => empty($broken),
            'broken_entry_ids' => array_values(array_unique($broken)),
            'rows' => $rows,
        ];
    }

    public static function trailFor(int $transactionId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT at.*, u.name AS actor_name
             FROM audit_trails at LEFT JOIN users u ON u.id = at.actor_id
             WHERE at.transaction_id = ? ORDER BY at.id ASC'
        );
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll();
    }
}
