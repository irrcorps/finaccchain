<?php
/**
 * ReportBuilder - builds print-friendly HTML for each of the 9 research
 * report types, later rendered to PDF via DomPDF (see reports/generate.php).
 */

class ReportBuilder
{
    private static function shell(string $title, string $subtitle, string $body): string
    {
        $generated = date('d M Y H:i');
        $disclaimer = e(RESEARCH_DISCLAIMER);
        return <<<HTML
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color:#1f2430; }
  h1 { font-size: 18px; margin-bottom:2px; }
  h2 { font-size: 13px; margin-top:18px; margin-bottom:6px; border-bottom:1px solid #ccc; padding-bottom:4px;}
  .muted { color:#666; }
  table { width:100%; border-collapse: collapse; margin-bottom:10px; }
  th, td { border:1px solid #ddd; padding:5px 7px; text-align:left; }
  th { background:#f0f2f8; }
  .text-end { text-align:right; }
  .header { border-bottom:2px solid #2f5fff; padding-bottom:8px; margin-bottom:14px; }
  .disclaimer { font-size:9px; color:#7a5c00; background:#fff8e6; border:1px solid #ffe4a3; padding:6px 8px; margin-top:18px; }
  .footer { font-size:9px; color:#999; margin-top:24px; }
  .badge { padding:2px 6px; border-radius:3px; font-size:9px; background:#eee; }
</style>
</head>
<body>
  <div class="header">
    <h1>FinAccChain <span class="muted" style="font-size:12px;">— Smart Financial Accountability for MSMEs</span></h1>
    <div class="muted">{$title}</div>
    <div class="muted" style="font-size:10px;">{$subtitle} · Dicetak: {$generated}</div>
  </div>
  {$body}
  <div class="disclaimer">Disclaimer Riset: {$disclaimer}</div>
  <div class="footer">Dokumen ini dihasilkan otomatis oleh prototipe riset FinAccChain untuk keperluan dokumentasi/publikasi penelitian PDP 2026.</div>
</body>
</html>
HTML;
    }

    public static function msmeProfile(int $msmeId): string
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT m.*, u.email FROM msmes m JOIN users u ON u.id=m.user_id WHERE m.id=?');
        $stmt->execute([$msmeId]);
        $m = $stmt->fetch();
        if (!$m) return self::shell('Profil UMKM', 'Data tidak ditemukan', '<p>UMKM tidak ditemukan.</p>');

        $rows = [
            'Nama Usaha' => $m['business_name'], 'Nama Pemilik' => $m['owner_name'], 'Email' => $m['email'],
            'Sektor' => $m['sector'], 'Alamat' => $m['address'], 'Usia Usaha' => $m['business_age_years'] . ' tahun',
            'Jumlah Karyawan' => $m['employee_count'], 'Omzet Bulanan' => $m['monthly_turnover_category'],
            'Penggunaan Pembayaran Digital' => $m['digital_payment_usage'], 'Penggunaan Fintech' => $m['fintech_usage'],
            'Metode Akuntansi' => $m['accounting_method'], 'Status Usaha' => $m['business_status'],
            'Terdaftar Sejak' => format_datetime($m['created_at']),
        ];
        $body = '<h2>Data Profil Usaha</h2><table>';
        foreach ($rows as $k => $v) { $body .= '<tr><th style="width:35%">' . e($k) . '</th><td>' . e((string) $v) . '</td></tr>'; }
        $body .= '</table>';

        return self::shell('Laporan Profil UMKM', $m['business_name'], $body);
    }

    public static function transactionReport(int $msmeId, ?string $from, ?string $to): string
    {
        $db = Database::connection();
        $sql = 'SELECT * FROM transactions WHERE msme_id = ?';
        $params = [$msmeId];
        if ($from) { $sql .= ' AND transaction_date >= ?'; $params[] = $from; }
        if ($to) { $sql .= ' AND transaction_date <= ?'; $params[] = $to; }
        $sql .= ' ORDER BY transaction_date ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $name = self::msmeName($msmeId);
        $body = '<h2>Daftar Transaksi (' . count($rows) . ' entri)</h2><table><tr><th>ID</th><th>Tanggal</th><th>Jenis</th><th>Pihak</th><th class="text-end">Nominal</th><th>Channel</th><th>Status</th></tr>';
        foreach ($rows as $r) {
            $body .= '<tr><td>' . e($r['transaction_uid']) . '</td><td>' . format_date($r['transaction_date']) . '</td><td>' . e(TransactionService::TYPES[$r['type']] ?? $r['type']) . '</td><td>' . e($r['party_name']) . '</td><td class="text-end">' . format_money($r['amount']) . '</td><td>' . e($r['payment_channel']) . '</td><td>' . e($r['status']) . '</td></tr>';
        }
        $body .= '</table>';

        return self::shell('Laporan Transaksi', $name, $body);
    }

    public static function journalLedger(int $msmeId): string
    {
        $ledger = Accounting::ledger($msmeId);
        $name = self::msmeName($msmeId);
        $body = '<h2>Jurnal &amp; Buku Besar</h2><table><tr><th>Tanggal</th><th>No. Ref</th><th>Akun</th><th>Deskripsi</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr>';
        foreach ($ledger as $l) {
            $body .= '<tr><td>' . format_date($l['journal_date']) . '</td><td>' . e($l['reference_no']) . '</td><td>' . e($l['account_code'].' - '.$l['account_name']) . '</td><td>' . e($l['journal_desc']) . '</td><td class="text-end">' . ($l['debit']>0?format_money($l['debit']):'-') . '</td><td class="text-end">' . ($l['credit']>0?format_money($l['credit']):'-') . '</td></tr>';
        }
        $body .= '</table>';
        return self::shell('Laporan Jurnal & Buku Besar', $name, $body);
    }

    public static function auditTrail(int $msmeId): string
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT at.*, t.transaction_uid, u.name AS actor_name FROM audit_trails at
             JOIN transactions t ON t.id = at.transaction_id LEFT JOIN users u ON u.id = at.actor_id
             WHERE t.msme_id = ? ORDER BY at.id ASC'
        );
        $stmt->execute([$msmeId]);
        $rows = $stmt->fetchAll();
        $name = self::msmeName($msmeId);

        $body = '<h2>Audit Trail (Hash Chain Simulasi)</h2><table><tr><th>#</th><th>Transaksi</th><th>Aksi</th><th>Aktor</th><th>Hash</th><th>Hash Sebelumnya</th><th>Waktu</th></tr>';
        foreach ($rows as $r) {
            $body .= '<tr><td>' . $r['id'] . '</td><td>' . e($r['transaction_uid']) . '</td><td>' . e($r['action']) . '</td><td>' . e($r['actor_name'] ?? 'sistem') . '</td><td>' . substr($r['current_hash'],0,20) . '…</td><td>' . substr($r['previous_hash'],0,20) . '…</td><td>' . format_datetime($r['created_at']) . '</td></tr>';
        }
        $body .= '</table>';
        return self::shell('Laporan Audit Trail', $name, $body);
    }

    public static function transactionVerification(int $msmeId): string
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT tv.*, t.transaction_uid, r.rule_name FROM transaction_validations tv
             JOIN transactions t ON t.id = tv.transaction_id JOIN smart_contract_rules r ON r.id = tv.rule_id
             WHERE t.msme_id = ? ORDER BY t.id, tv.id'
        );
        $stmt->execute([$msmeId]);
        $rows = $stmt->fetchAll();
        $name = self::msmeName($msmeId);

        $body = '<h2>Hasil Verifikasi Rule Engine per Transaksi</h2><table><tr><th>Transaksi</th><th>Aturan</th><th>Hasil</th><th>Catatan</th><th>Waktu</th></tr>';
        foreach ($rows as $r) {
            $body .= '<tr><td>' . e($r['transaction_uid']) . '</td><td>' . e($r['rule_name']) . '</td><td>' . e(strtoupper($r['result'])) . '</td><td>' . e($r['notes']) . '</td><td>' . format_datetime($r['validated_at']) . '</td></tr>';
        }
        $body .= '</table>';
        return self::shell('Laporan Verifikasi Transaksi', $name, $body);
    }

    public static function accountabilityAssessment(int $msmeId): string
    {
        $a = Accountability::latest($msmeId);
        $name = self::msmeName($msmeId);
        if (!$a) return self::shell('Laporan Akuntabilitas Keuangan', $name, '<p>Belum ada penilaian akuntabilitas untuk UMKM ini.</p>');

        $body = '<h2>Indeks Akuntabilitas Keuangan: ' . number_format($a['overall_score'],1) . ' / 100</h2>';
        $body .= '<p class="muted">Dihitung: ' . format_datetime($a['created_at']) . ' · Periode: ' . e($a['period_label']) . '</p>';
        $body .= '<table><tr><th>Indikator</th><th>Skor</th><th>Bobot</th><th>Catatan</th></tr>';
        foreach ($a['details'] as $d) {
            $body .= '<tr><td>' . e(Accountability::INDICATORS[$d['indicator_code']] ?? $d['indicator_code']) . '</td><td>' . number_format($d['score'],1) . '</td><td>' . number_format($d['weight'],1) . '%</td><td>' . e($d['notes']) . '</td></tr>';
        }
        $body .= '</table>';
        $body .= '<p class="muted" style="font-size:10px;">Catatan: Indeks ini merupakan formula riset yang dapat dikonfigurasi peneliti, ' . (Accountability::hasExpertValidation() ? 'dan telah dilengkapi data validasi ahli pada sistem.' : 'dan BELUM divalidasi secara ilmiah oleh ahli karena data validasi ahli belum tersedia.') . '</p>';

        return self::shell('Laporan Penilaian Akuntabilitas Keuangan', $name, $body);
    }

    public static function expertValidationResults(): string
    {
        $db = Database::connection();
        $rows = $db->query('SELECT ev.*, u.name AS expert_name, m.business_name FROM expert_validations ev JOIN users u ON u.id=ev.expert_user_id LEFT JOIN msmes m ON m.id=ev.msme_id ORDER BY ev.id')->fetchAll();
        $body = '<h2>Hasil Validasi Ahli (' . count($rows) . ' penilaian)</h2><table><tr><th>Ahli</th><th>Objek</th><th>Rata-rata</th><th>Komentar</th></tr>';
        foreach ($rows as $r) {
            $criteria = ['relevance','clarity','feasibility','accounting_adequacy','technological_adequacy','fintech_integration','smart_contract_logic','accountability_contribution','usefulness'];
            $avg = array_sum(array_map(fn($c) => (int) $r[$c], $criteria)) / count($criteria);
            $body .= '<tr><td>' . e($r['expert_name']) . '</td><td>' . e($r['business_name'] ?? 'Model Umum') . '</td><td>' . number_format($avg,2) . '/5</td><td>' . e($r['comments']) . '</td></tr>';
        }
        $body .= '</table>';
        return self::shell('Laporan Hasil Validasi Ahli', 'Seluruh UMKM/Model', $body);
    }

    public static function researchSummary(): string
    {
        $db = Database::connection();
        $msmeCount = (int) $db->query('SELECT COUNT(*) FROM msmes')->fetchColumn();
        $trxCount = (int) $db->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        $recorded = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status='recorded'")->fetchColumn();
        $rejected = (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status='rejected'")->fetchColumn();
        $avgScore = (float) $db->query('SELECT AVG(overall_score) FROM accountability_assessments aa WHERE aa.id IN (SELECT MAX(id) FROM accountability_assessments GROUP BY msme_id)')->fetchColumn();
        $expertCount = (int) $db->query('SELECT COUNT(*) FROM expert_validations')->fetchColumn();
        $chain = HashChain::verifyChain();

        $body = '<h2>Ringkasan Riset</h2><table>
            <tr><th style="width:45%">Jumlah UMKM Terdaftar</th><td>' . $msmeCount . '</td></tr>
            <tr><th>Total Transaksi</th><td>' . $trxCount . '</td></tr>
            <tr><th>Transaksi Tercatat (Recorded)</th><td>' . $recorded . '</td></tr>
            <tr><th>Transaksi Ditolak</th><td>' . $rejected . '</td></tr>
            <tr><th>Rata-rata Indeks Akuntabilitas</th><td>' . ($avgScore ? number_format($avgScore,1) : '-') . '</td></tr>
            <tr><th>Jumlah Penilaian Ahli</th><td>' . $expertCount . '</td></tr>
            <tr><th>Integritas Rantai Hash</th><td>' . ($chain['is_valid'] ? 'Terverifikasi (' . $chain['total_entries'] . ' entri)' : 'Bermasalah pada ' . count($chain['broken_entry_ids']) . ' entri') . '</td></tr>
        </table>';
        return self::shell('Ringkasan Riset FinAccChain', 'Seluruh Ekosistem Prototipe', $body);
    }

    public static function asIsToBe(): string
    {
        $body = '<h2>Ringkasan Kondisi AS-IS vs TO-BE</h2>
        <table><tr><th>Aspek</th><th>AS-IS (Kondisi Umum UMKM Saat Ini)</th><th>TO-BE (Model FinAccChain)</th></tr>
        <tr><td>Pencatatan Transaksi</td><td>Manual/spreadsheet, rawan tidak konsisten</td><td>Terstandarisasi via rule engine dengan status pipeline jelas</td></tr>
        <tr><td>Integrasi Fintech</td><td>Terpisah dari pembukuan</td><td>Tersimulasikan terhubung otomatis ke klasifikasi akuntansi</td></tr>
        <tr><td>Audit Trail</td><td>Minim/tidak terstruktur</td><td>Rantai hash append-only, dapat diverifikasi integritasnya</td></tr>
        <tr><td>Otorisasi &amp; Kontrol</td><td>Longgar</td><td>Rule authorization, threshold, dan validator approval</td></tr>
        <tr><td>Pengukuran Akuntabilitas</td><td>Tidak terukur secara kuantitatif</td><td>Indeks 8 indikator berbasis data transaksi riil</td></tr>
        <tr><td>Koreksi Data</td><td>Umumnya edit/hapus langsung</td><td>Reversal/adjustment entry, data asli tetap tertelusuri</td></tr>
        </table>
        <p class="muted" style="font-size:10px;">Catatan: perbandingan bersifat generalisasi untuk kebutuhan dokumentasi riset TKT 3, bukan hasil studi lapangan menyeluruh.</p>';
        return self::shell('Ringkasan AS-IS / TO-BE', 'Model Integrasi Smart Contract-Fintech', $body);
    }

    private static function msmeName(int $msmeId): string
    {
        $stmt = Database::connection()->prepare('SELECT business_name FROM msmes WHERE id=?');
        $stmt->execute([$msmeId]);
        return (string) $stmt->fetchColumn();
    }
}
