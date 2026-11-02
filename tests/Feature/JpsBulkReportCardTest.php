<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use App\Services\ReportCard\JpsBulkReportCardDataBuilder;

class JpsBulkReportCardTest extends JpsClass3To5BulkReportCardTest
{
    protected array $classIds = [101, 102];
    protected string $bulkView = 'reportcard.JPS.class1to2_report_card_pdf_all';
    protected string $singleView = 'reportcard.JPS.class1to2_report_card_pdf';

    public function test_subject_specific_headings_and_grade_scale_are_preloaded_without_marks(): void
    {
        DB::table('student')->insert([
            'student_id' => 10101, 'class_id' => 101, 'section_id' => 201, 'academic_yr' => '2026-2027',
            'IsDelete' => 'N', 'roll_no' => 1, 'reg_no' => 1, 'first_name' => 'Student',
            'mid_name' => null, 'last_name' => 'Test', 'dob' => '2020-01-01', 'parent_id' => 1,
        ]);
        DB::table('marks_headings')->insert(['marks_headings_id' => 2, 'name' => 'Portfolio', 'sequence' => 2]);
        DB::table('subjects_on_report_card_master')->insert(['sub_rc_master_id' => 11, 'name' => 'Hindi', 'sequence' => 3]);
        DB::table('subjects_on_report_card')->insert(['class_id' => 101, 'sub_rc_master_id' => 11, 'subject_type' => 'Scholastic', 'academic_yr' => '2026-2027']);
        DB::table('allot_mark_headings')->insert(['class_id' => 101, 'sm_id' => 11, 'exam_id' => 1, 'academic_yr' => '2026-2027', 'marks_headings_id' => 2, 'highest_marks' => 25]);
        $data = app(JpsBulkReportCardDataBuilder::class)->build(101, 201, '2026-2027');
        $this->assertSame('Portfolio', $data['subject_headings'][1][11][0]->marks_headings_name);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view($this->bulkView, ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertStringContainsString('Portfolio', $html);
        $this->assertStringContainsString('(25)', $html);
        $this->assertStringContainsString('Hindi', $html);
        $this->assertStringContainsString('GRADING SCALE FOR SCHOLASTIC AREAS', $html);
    }
}