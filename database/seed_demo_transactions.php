<?php
/**
 * CLI seeder: runs realistic demo transactions THROUGH the actual
 * RuleEngine / Accounting / HashChain / Accountability classes so the
 * demo dataset is produced by the same code path as real usage (also
 * doubles as an integration smoke test of the engine).
 *
 * Usage: php database/seed_demo_transactions.php
 */

require_once __DIR__ . '/../core/bootstrap.php';

$db = Database::connection();
$db->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['audit_trails','transaction_validations','journal_details','journals','fintech_transactions','transaction_evidence','transactions','accountability_details','accountability_assessments'] as $t) {
    $db->exec("DELETE FROM $t");
}
$db->exec('SET FOREIGN_KEY_CHECKS=1');

$validatorUserId = (int) $db->query("SELECT id FROM users WHERE email='validator@finaccchain.demo'")->fetchColumn();

function demo_evidence(int $trxId, int $uploaderId): void
{
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = 'EV-DEMO-' . $trxId . '.pdf';
    file_put_contents(UPLOAD_DIR . '/' . $name, "%PDF-1.4\n% Demo evidence placeholder for research prototype transaction #$trxId\n");
    Database::connection()->prepare(
        'INSERT INTO transaction_evidence (transaction_id, file_path, original_name, file_type, file_size, uploaded_by) VALUES (?, ?, ?, "pdf", ?, ?)'
    )->execute([$trxId, 'uploads/evidence/' . $name, 'bukti_' . $trxId . '.pdf', filesize(UPLOAD_DIR . '/' . $name), $uploaderId]);
}

function make_trx(int $msmeId, int $actorId, string $date, string $type, float $amount, string $party, string $channel, string $desc, bool $withEvidence): int
{
    $id = TransactionService::create($msmeId, $actorId, [
        'transaction_date' => $date, 'type' => $type, 'party_name' => $party,
        'description' => $desc, 'amount' => $amount, 'payment_channel' => $channel,
    ]);
    if ($withEvidence) demo_evidence($id, $actorId);
    return $id;
}

echo "Seeding demo transactions...\n";

// ---------------------------------------------------------------- MSME 1
$u1 = (int) $db->query("SELECT user_id FROM msmes WHERE id=1")->fetchColumn();
$t = make_trx(1, $u1, date('Y-m-d', strtotime('-9 days')), 'sales', 250000, 'Pelanggan Umum', 'qr_payment', 'Penjualan kopi harian', false);
RuleEngine::process($t, $u1);

$t = make_trx(1, $u1, date('Y-m-d', strtotime('-8 days')), 'sales', 1500000, 'Kantor ABC (Katering)', 'bank_transfer', 'Pesanan katering rapat kantor', true);
RuleEngine::process($t, $u1);

$t = make_trx(1, $u1, date('Y-m-d', strtotime('-7 days')), 'purchase', 800000, 'Supplier Kopi Sumatra', 'cash', 'Pembelian biji kopi', false);
RuleEngine::process($t, $u1);

$t = make_trx(1, $u1, date('Y-m-d', strtotime('-6 days')), 'operating_expense', 500000, 'PLN & PDAM', 'e_wallet', 'Listrik dan air bulanan', false);
RuleEngine::process($t, $u1);

$t = make_trx(1, $u1, date('Y-m-d', strtotime('-5 days')), 'financing', 8000000, 'Fintech Lending Co (simulasi)', 'digital_financing', 'Pembiayaan modal kerja renovasi kedai', true);
$res = RuleEngine::process($t, $u1);
if ($res['status'] === 'pending') RuleEngine::approveByValidator($t, $validatorUserId);

// ---------------------------------------------------------------- MSME 2
$u2 = (int) $db->query("SELECT user_id FROM msmes WHERE id=2")->fetchColumn();
$dupDate = date('Y-m-d', strtotime('-4 days'));

$t = make_trx(2, $u2, $dupDate, 'sales', 3000000, 'Distro Fashion Medan', 'bank_transfer', 'Penjualan seragam batch 1', true);
$res = RuleEngine::process($t, $u2);
if ($res['status'] === 'pending') RuleEngine::approveByValidator($t, $validatorUserId);

$t = make_trx(2, $u2, $dupDate, 'sales', 3000000, 'Distro Fashion Medan', 'bank_transfer', 'Penjualan seragam batch 1 (kemungkinan duplikat input)', true);
$res = RuleEngine::process($t, $u2);
if ($res['status'] === 'pending') RuleEngine::rejectByValidator($t, $validatorUserId, 'Terindikasi entri duplikat dari transaksi sebelumnya pada tanggal & nominal yang sama.');

$t = make_trx(2, $u2, date('Y-m-d', strtotime('-3 days')), 'purchase', 2000000, 'PT Sinar Tekstil', 'cash', 'Pembelian bahan kain', true);
RuleEngine::process($t, $u2);

$t = make_trx(2, $u2, date('Y-m-d', strtotime('-2 days')), 'payable', 6000000, 'Supplier Benang Nusantara', 'bank_transfer', 'Utang usaha pembelian benang partai besar', true);
$res = RuleEngine::process($t, $u2);
if ($res['status'] === 'pending') RuleEngine::approveByValidator($t, $validatorUserId);

$t = make_trx(2, $u2, date('Y-m-d', strtotime('-1 days')), 'operating_expense', 300000, 'Gaji Harian Karyawan', 'cash', 'Gaji harian 3 karyawan', false);
RuleEngine::process($t, $u2);

// ---------------------------------------------------------------- MSME 3
$u3 = (int) $db->query("SELECT user_id FROM msmes WHERE id=3")->fetchColumn();
$t = make_trx(3, $u3, date('Y-m-d', strtotime('-6 days')), 'sales', 150000, 'Pelanggan Pasar Belawan', 'cash', 'Penjualan kerupuk eceran', false);
RuleEngine::process($t, $u3);

$t = make_trx(3, $u3, date('Y-m-d', strtotime('-4 days')), 'sales', 200000, 'Pelanggan Pasar Belawan', 'cash', 'Penjualan kerupuk eceran', false);
RuleEngine::process($t, $u3);

$t = make_trx(3, $u3, date('Y-m-d', strtotime('-2 days')), 'operating_expense', 100000, 'Bahan Bakar Gorengan', 'cash', 'Pembelian minyak goreng', false);
RuleEngine::process($t, $u3);

// Intentionally left WITHOUT evidence to demonstrate a transaction stuck
// awaiting evidence (realistic incomplete state for research demo variety).
$t = make_trx(3, $u3, date('Y-m-d'), 'purchase', 1200000, 'Supplier Tepung Belawan', 'cash', 'Pembelian tepung tapioka jumlah besar', false);
RuleEngine::process($t, $u3);

foreach ([1, 2, 3] as $msmeId) {
    Accountability::computeAndStore($msmeId);
}

// ------------------------------------------------------------- Expert validations (demo)
$db->prepare(
    'INSERT INTO expert_validations (expert_user_id, msme_id, relevance, clarity, feasibility, accounting_adequacy, technological_adequacy, fintech_integration, smart_contract_logic, accountability_contribution, usefulness, comments)
     VALUES (?, NULL, 5, 4, 4, 4, 4, 4, 5, 4, 4, ?)'
)->execute([$validatorUserId, 'Model rule engine cukup jelas mensimulasikan alur smart contract untuk kebutuhan riset TKT 3. Perlu penambahan contoh kasus pembiayaan digital multi-tahap pada iterasi berikutnya.']);

$db->prepare(
    'INSERT INTO expert_validations (expert_user_id, msme_id, relevance, clarity, feasibility, accounting_adequacy, technological_adequacy, fintech_integration, smart_contract_logic, accountability_contribution, usefulness, comments)
     VALUES (?, 1, 5, 5, 4, 4, 4, 4, 4, 5, 5, ?)'
)->execute([$validatorUserId, 'Untuk Kedai Kopi Deli, alur validasi transaksi fintech dan audit trail sudah cukup representatif terhadap kebutuhan akuntabilitas UMKM kuliner skala kecil.']);

// ------------------------------------------------------------- Questionnaire responses (demo)
$q1 = (int) $db->query("SELECT id FROM questionnaires WHERE target_role='msme' LIMIT 1")->fetchColumn();
$q2 = (int) $db->query("SELECT id FROM questionnaires WHERE target_role='validator' LIMIT 1")->fetchColumn();

$answers1 = json_encode(['Q1'=>'4','Q2'=>'3','Q3'=>'3','Q4'=>'Ya','Q5'=>'5','Q6'=>'Keterbatasan waktu untuk mencatat transaksi setiap hari.'], JSON_UNESCAPED_UNICODE);
$db->prepare('INSERT INTO questionnaire_responses (questionnaire_id, respondent_user_id, respondent_name, respondent_type, answers_json) VALUES (?, ?, ?, "msme", ?)')
   ->execute([$q1, $u1, 'Siti Rahma (Kedai Kopi Deli)', $answers1]);

$answers2 = json_encode(['Q1'=>'3','Q2'=>'2','Q3'=>'3','Q4'=>'Tidak','Q5'=>'4','Q6'=>'Belum terbiasa memisahkan kas usaha dan pribadi.'], JSON_UNESCAPED_UNICODE);
$db->prepare('INSERT INTO questionnaire_responses (questionnaire_id, respondent_user_id, respondent_name, respondent_type, answers_json) VALUES (?, ?, ?, "msme", ?)')
   ->execute([$q1, $u2, 'Budi Santoso (Konveksi Medan Jaya)', $answers2]);

$answers3 = json_encode(['E1'=>'5','E2'=>'4','E3'=>'4'], JSON_UNESCAPED_UNICODE);
$db->prepare('INSERT INTO questionnaire_responses (questionnaire_id, respondent_user_id, respondent_name, respondent_type, answers_json) VALUES (?, ?, ?, "validator", ?)')
   ->execute([$q2, $validatorUserId, 'Dr. Andi Pratama, Ak.', $answers3]);

echo "Done. Transactions, accountability assessments, expert validations, and questionnaire responses seeded.\n";

$chain = HashChain::verifyChain();
echo 'Hash chain entries: ' . $chain['total_entries'] . ', valid: ' . ($chain['is_valid'] ? 'YES' : 'NO') . "\n";
