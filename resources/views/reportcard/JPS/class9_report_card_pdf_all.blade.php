{{-- Reuse the single layout with one page break between student reports. --}}
@include('reportcard.JPS.class9_report_card_pdf', ['reports' => $reportCardData['reports']])
