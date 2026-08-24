<?php
/**
 * TransactionService - creation, CSV import, and evidence handling for
 * financial transactions. Keeps controllers (msme/*.php) thin.
 */

class TransactionService
{
    public const TYPES = [
        'sales' => 'Penjualan',
        'purchase' => 'Pembelian',
        'operating_expense' => 'Beban Operasional',
        'receivable' => 'Piutang',
        'payable' => 'Utang',
        'capital' => 'Modal',
        'financing' => 'Pembiayaan',
        'other_income' => 'Pendapatan Lain-lain',
        'other_expense' => 'Beban Lain-lain',
    ];

    public const CHANNELS = [
        'cash' => 'Tunai',
        'bank_transfer' => 'Transfer Bank',
        'qr_payment' => 'QRIS / Payment Gateway',
        'e_wallet' => 'E-Wallet',
        'digital_financing' => 'Pembiayaan Digital',
    ];

    public static function create(int $msmeId, int $userId, array $d): int
    {
        $db = Database::connection();
        $uid = generate_uid('TRX');
        $stmt = $db->prepare(
            'INSERT INTO transactions (msme_id, transaction_uid, transaction_date, type, party_name, description, amount, payment_channel, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "draft", ?)'
        );
        $stmt->execute([
            $msmeId,
            $uid,
            $d['transaction_date'],
            $d['type'],
            trim($d['party_name'] ?? ''),
            trim($d['description'] ?? ''),
            (float) $d['amount'],
            $d['payment_channel'] ?? 'cash',
            $userId,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function attachEvidence(int $transactionId, int $userId, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [false, 'Tidak ada file valid yang diunggah.'];
        }
        if ($file['size'] > UPLOAD_MAX_BYTES) {
            return [false, 'Ukuran file melebihi 3MB.'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EVIDENCE_TYPES, true)) {
            return [false, 'Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF.'];
        }
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        $safeName = 'EV-' . $transactionId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = UPLOAD_DIR . '/' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return [false, 'Gagal menyimpan file.'];
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO transaction_evidence (transaction_id, file_path, original_name, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$transactionId, 'uploads/evidence/' . $safeName, $file['name'], $ext, $file['size'], $userId]);

        return [true, 'Bukti transaksi berhasil diunggah.'];
    }

    /**
     * Imports transactions from an uploaded CSV file.
     * Expected header: transaction_date,type,party_name,description,amount,payment_channel
     * Returns [insertedCount, errorMessages[]]
     */
    public static function importCsv(int $msmeId, int $userId, string $tmpPath): array
    {
        $inserted = 0;
        $errors = [];
        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            return [0, ['File tidak dapat dibaca.']];
        }
        $header = fgetcsv($handle);
        $expected = ['transaction_date', 'type', 'party_name', 'description', 'amount', 'payment_channel'];
        if (!$header || array_map('trim', $header) !== $expected) {
            fclose($handle);
            return [0, ['Header CSV harus: ' . implode(',', $expected)]];
        }

        $rowNum = 1;
        $ids = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) < 6) { $errors[] = "Baris $rowNum: kolom tidak lengkap."; continue; }
            [$date, $type, $party, $desc, $amount, $channel] = $row;
            if (!array_key_exists($type, self::TYPES)) { $errors[] = "Baris $rowNum: jenis transaksi '$type' tidak dikenal."; continue; }
            if (!array_key_exists($channel, self::CHANNELS)) { $errors[] = "Baris $rowNum: channel '$channel' tidak dikenal."; continue; }
            if (!is_numeric($amount) || (float) $amount <= 0) { $errors[] = "Baris $rowNum: nominal tidak valid."; continue; }
            if (!strtotime($date)) { $errors[] = "Baris $rowNum: tanggal tidak valid."; continue; }

            $id = self::create($msmeId, $userId, [
                'transaction_date' => date('Y-m-d', strtotime($date)),
                'type' => $type,
                'party_name' => $party,
                'description' => $desc,
                'amount' => $amount,
                'payment_channel' => $channel,
            ]);
            $ids[] = $id;
            $inserted++;
        }
        fclose($handle);

        foreach ($ids as $id) {
            RuleEngine::process($id, $userId);
        }

        return [$inserted, $errors];
    }

    public static function createFintechSimulated(int $msmeId, int $userId, array $d): array
    {
        $db = Database::connection();
        $trxId = self::create($msmeId, $userId, [
            'transaction_date' => date('Y-m-d'),
            'type' => $d['type'],
            'party_name' => $d['party_name'] ?? '',
            'description' => $d['description'] ?? '',
            'amount' => $d['amount'],
            'payment_channel' => $d['channel'],
        ]);

        $refId = strtoupper($d['channel']) . '-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $metadata = [
            'simulated' => true,
            'note' => 'Data kanal fintech disimulasikan untuk keperluan riset; tidak terhubung ke penyedia layanan sungguhan.',
            'device' => 'web-prototype',
            'channel_label' => TransactionService::CHANNELS[$d['channel']] ?? $d['channel'],
        ];
        $ins = $db->prepare(
            'INSERT INTO fintech_transactions (transaction_id, channel, reference_id, payment_status, metadata_json) VALUES (?, ?, ?, "success", ?)'
        );
        $ins->execute([$trxId, $d['channel'], $refId, json_encode($metadata, JSON_UNESCAPED_UNICODE)]);

        $result = RuleEngine::process($trxId, $userId);
        return ['transaction_id' => $trxId, 'reference_id' => $refId, 'engine' => $result];
    }
}
