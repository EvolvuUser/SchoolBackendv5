<?php

namespace Tests\Concerns;

use App\Services\ReportCard\JpsSecondaryBulkReportCardDataBuilder;
use Illuminate\Support\Facades\DB;
use PDF;

trait TestsJpsSecondaryBulkReportCards
{
    private function bulkFixtureStudent(): object
    {
        return DB::table('student')->orderBy('student_id')->first();
    }

    private function bulkData($studCount = null): array
    {
        $student = $this->bulkFixtureStudent();
        return app(JpsSecondaryBulkReportCardDataBuilder::class)
            ->build($student->class_id, $student->section_id, $student->academic_yr, $studCount);
    }

    private function addBulkStudent(int $id, int $roll, array $overrides = []): void
    {
        $student = (array) $this->bulkFixtureStudent();
        DB::table('student')->insert(array_replace($student, [
            'student_id' => $id, 'roll_no' => $roll, 'first_name' => 'Bulk Student ' . $id,
        ], $overrides));
    }

    public function test_bulk_reports_match_single_calculations_without_student_data_leaking(): void
    {
        $first = $this->bulkFixtureStudent();
        $this->addBulkStudent(20001, 2);
        DB::table('report_card_remarks')->insert([
            'student_id' => 20001, 'term_id' => 21, 'remark' => 'Second student remark', 'promot' => '',
        ]);
        DB::table('attendance')->insert([
            'student_id' => 20001, 'academic_yr' => $first->academic_yr,
            'only_date' => '2026-05-02', 'attendance_status' => 1,
        ]);
        $data = $this->bulkData();
        $this->assertCount(2, $data['reports']);
        $single = $this->build();
        foreach (['groups', 'blocks', 'co_scholastic', 'remarks', 'date_from', 'date_to', 'reopen_date'] as $key) {
            $this->assertEquals($single[$key], $data['reports'][0][$key], $key);
        }
        $this->assertSame($single['attendance']->present, $data['reports'][0]['attendance']->present);
        $this->assertSame($single['attendance']->working, $data['reports'][0]['attendance']->working);
        $second = $data['reports'][1];
        $this->assertSame('', $second['groups'][0]['rows'][0]['terms'][0]['total']);
        $this->assertSame('Second student remark', $second['remarks'][1]->remark);
        $this->assertSame(0, $second['attendance']->present);
        $this->assertSame(1, $second['attendance']->working);

        // Half-year attendance must also match when only Term I is published.
        DB::table('report_card_publish')->whereNotIn('term_id', [11, 12, 21])->update(['publish' => 'N']);
        $this->assertEquals($this->build()['attendance'], (object) array_diff_key(
            (array) $this->bulkData()['reports'][0]['attendance'], ['student_id' => true]
        ));
        $this->assertSame('2026-09-30', $this->bulkData()['reports'][0]['date_to']);
    }

    public function test_bulk_query_count_is_constant_and_batches_filter_and_order_students(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->bulkData();
        $queryCount = count(DB::getQueryLog());
        for ($i = 2; $i <= 23; $i++) {
            $this->addBulkStudent(20000 + $i, $i);
        }
        $this->addBulkStudent(30001, 1, ['IsDelete' => 'Y']);
        $this->addBulkStudent(30002, 1, ['section_id' => 999]);
        $this->addBulkStudent(30003, 1, ['class_id' => 999]);
        $this->addBulkStudent(30004, 1, ['academic_yr' => '2025-2026']);
        DB::flushQueryLog();
        $all = $this->bulkData();
        $this->assertSame($queryCount, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $this->assertCount(23, $all['reports']);
        $this->assertSame(range(1, 23), array_column($all['students'], 'roll_no'));
        $this->assertSame(range(1, 10), array_column($this->bulkData(10)['students'], 'roll_no'));
        $this->assertSame(range(11, 20), array_column($this->bulkData(20)['students'], 'roll_no'));
        $this->assertSame([21, 22, 23], array_column($this->bulkData(3)['students'], 'roll_no'));
        $this->assertSame([], $this->bulkData(100)['reports']);
    }

    public function test_bulk_endpoint_uses_the_current_layout_and_class_section_filename(): void
    {
        $student = $this->bulkFixtureStudent();
        $className = DB::table('class')->where('class_id', $student->class_id)->value('name');
        PDF::shouldReceive('loadView')->once()
            ->with('reportcard.JPS.class' . $className . '_report_card_pdf_all', \Mockery::on(function ($data) use ($student) {
                return count($data['reportCardData']['reports']) === 1
                    && $data['reportCardData']['reports'][0]['student']->student_id === $student->student_id;
            }))->andReturnSelf();
        PDF::shouldReceive('download')->once()->with($className . 'A_All_Report_Cards.pdf')
            ->andReturn(response('PDF', 200));
        $url = '/api/pdf_download_all?class_id=' . $student->class_id;
        $this->getJson($url . '&section_id=201&academic_yr=2026-2027')->assertOk();
        $this->getJson($url)->assertUnprocessable()->assertJsonValidationErrors(['section_id', 'academic_yr']);
        $this->getJson($url . '&section_id=201&academic_yr=2026-2027&stud_count=0')
            ->assertUnprocessable()->assertJsonValidationErrors('stud_count');
        $this->getJson($url . '&section_id=999&academic_yr=2026-2027')->assertNotFound();
    }

    public function test_bulk_pdf_has_separate_reports_with_no_render_queries_or_extra_pages(): void
    {
        $student = $this->bulkFixtureStudent();
        $className = DB::table('class')->where('class_id', $student->class_id)->value('name');
        $this->addBulkStudent(20001, 2);
        $data = $this->bulkData();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view('reportcard.JPS.class' . $className . '_report_card_pdf_all', ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame(1, substr_count($html, '<html'));
        $this->assertSame(1, substr_count($html, '<body>'));
        $this->assertSame(2, substr_count($html, 'class="col-md-12 pdfdiv"'));
        $this->assertStringContainsString('Bulk Student 20001', $html);
        $singlePages = 0;
        foreach ($data['reports'] as $report) {
            $pdf = app('dompdf.wrapper')->loadView('reportcard.JPS.class' . $className . '_report_card_pdf', ['reportCardData' => $report]);
            $pdf->output();
            $singlePages += $pdf->getDomPDF()->getCanvas()->get_page_count();
        }
        $pdf = app('dompdf.wrapper')->loadHTML($html);
        $this->assertStringStartsWith('%PDF-', $pdf->output());
        $this->assertSame($singlePages, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }
}
