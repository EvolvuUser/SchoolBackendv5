<?php

namespace Tests\Feature;

class JpsClass6To8BulkReportCardTest extends JpsClass3To5BulkReportCardTest
{
    protected array $classIds = [106, 107, 108];
    protected string $bulkView = 'reportcard.JPS.class6to8_report_card_pdf_all';
    protected string $singleView = 'reportcard.JPS.class6to8_report_card_pdf';
}