<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_role(['admin', 'msme', 'validator']);
$u = current_user();

$type = $_GET['type'] ?? '';
$msmeId = (int) ($_GET['msme_id'] ?? 0);

if ($u['role_code'] === 'msme') {
    $msmeId = (int) $u['msme_id'];
}

$perMsmeTypes = ['msme_profile', 'transactions', 'journal_ledger', 'audit_trail', 'transaction_verification', 'accountability'];
if (in_array($type, $perMsmeTypes, true) && !$msmeId) {
    die('Parameter UMKM wajib untuk jenis laporan ini.');
}

switch ($type) {
    case 'msme_profile': $html = ReportBuilder::msmeProfile($msmeId); break;
    case 'transactions': $html = ReportBuilder::transactionReport($msmeId, $_GET['from'] ?? null, $_GET['to'] ?? null); break;
    case 'journal_ledger': $html = ReportBuilder::journalLedger($msmeId); break;
    case 'audit_trail': $html = ReportBuilder::auditTrail($msmeId); break;
    case 'transaction_verification': $html = ReportBuilder::transactionVerification($msmeId); break;
    case 'accountability': $html = ReportBuilder::accountabilityAssessment($msmeId); break;
    case 'expert_validation': $html = ReportBuilder::expertValidationResults(); break;
    case 'research_summary': $html = ReportBuilder::researchSummary(); break;
    case 'astobe': $html = ReportBuilder::asIsToBe(); break;
    default: die('Jenis laporan tidak dikenal.');
}

$dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('finaccchain_' . $type . '_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
