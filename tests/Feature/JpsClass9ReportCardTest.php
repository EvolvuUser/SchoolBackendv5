<?php

namespace Tests\Feature;

use App\Services\ReportCard\JpsClass9ReportCardDataBuilder;
use Illuminate\Support\Facades\DB;
use PDF;
use Tests\TestCase;
use Tests\Concerns\TestsJpsSecondaryBulkReportCards;
use Tymon\JWTAuth\Facades\JWTAuth;

class JpsClass9ReportCardTest extends TestCase
{
    use TestsJpsSecondaryBulkReportCards;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['database.default' => 'jps9_test', 'database.connections.jps9_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach ([
            'CREATE TABLE student (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, parent_id INTEGER, first_name TEXT, mid_name TEXT, last_name TEXT, roll_no INTEGER, reg_no INTEGER, dob TEXT, IsDelete TEXT DEFAULT "N")',
            'CREATE TABLE parent (parent_id INTEGER, father_name TEXT)',
            'CREATE TABLE class (class_id INTEGER, name TEXT)',
            'CREATE TABLE section (section_id INTEGER, name TEXT)',
            'CREATE TABLE exam (exam_id INTEGER, name TEXT, academic_yr TEXT, start_date TEXT)',
            'CREATE TABLE report_card_publish (term_id INTEGER, class_id INTEGER, section_id INTEGER, publish TEXT, reopen_date TEXT)',
            'CREATE TABLE marks_headings (marks_headings_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE allot_mark_headings (exam_id INTEGER, class_id INTEGER, academic_yr TEXT, sm_id INTEGER, marks_headings_id INTEGER, highest_marks INTEGER, reportcard_highest_marks INTEGER)',
            'CREATE TABLE subjects_on_report_card (class_id INTEGER, academic_yr TEXT, sub_rc_master_id INTEGER, subject_type TEXT)',
            'CREATE TABLE subjects_on_report_card_master (sub_rc_master_id INTEGER, name TEXT, sequence INTEGER)',
            'CREATE TABLE student_marks (student_id INTEGER, class_id INTEGER, section_id INTEGER, academic_yr TEXT, exam_id INTEGER, subject_id INTEGER, publish TEXT, present TEXT, mark_obtained TEXT, highest_marks TEXT, reportcard_marks TEXT, reportcard_highest_marks TEXT)',
            'CREATE TABLE grade (class_id INTEGER, subject_type TEXT, mark_from INTEGER, mark_upto INTEGER, name TEXT)',
            'CREATE TABLE settings (academic_yr TEXT, academic_yr_from TEXT, academic_yr_to TEXT)',
            'CREATE TABLE attendance (student_id INTEGER, academic_yr TEXT, only_date TEXT, attendance_status INTEGER)',
            'CREATE TABLE report_card_remarks (student_id INTEGER, term_id INTEGER, remark TEXT, promot TEXT)',
        ] as $sql) {
            DB::statement($sql);
        }
        DB::table('student')->insert(['student_id' => 9001, 'class_id' => 109, 'section_id' => 201, 'academic_yr' => '2026-2027', 'parent_id' => 1, 'first_name' => 'Student', 'mid_name' => null, 'last_name' => 'Test', 'roll_no' => 1, 'reg_no' => 90, 'dob' => '2012-05-01']);
        DB::table('parent')->insert(['parent_id' => 1, 'father_name' => 'Test Parent']);
        DB::table('class')->insert([['class_id' => 109, 'name' => '9'], ['class_id' => 110, 'name' => '10']]);
        DB::table('section')->insert(['section_id' => 201, 'name' => 'A']);
        foreach ([11 => 'PT - I', 12 => 'PT - II', 13 => 'PT - III', 21 => 'TERM I', 22 => 'TERM II'] as $id => $name) {
            DB::table('exam')->insert(['exam_id' => $id, 'name' => $name, 'academic_yr' => '2026-2027', 'start_date' => '2026-05-' . $id]);
            DB::table('report_card_publish')->insert(['term_id' => $id, 'class_id' => 109, 'section_id' => 201, 'publish' => 'Y']);
        }
        foreach ([101 => 'Periodic test', 102 => 'Multiple assessment', 103 => 'Subject enrichment', 104 => 'Portfolio', 105 => 'SA-1', 106 => 'SA-2', 201 => 'Practical', 202 => 'Oral/Viva', 301 => 'Total Marks'] as $id => $name) {
            DB::table('marks_headings')->insert(['marks_headings_id' => $id, 'name' => $name, 'sequence' => ([101 => 1, 102 => 2, 103 => 3, 104 => 4, 105 => 5, 106 => 5, 201 => 2, 202 => 3, 301 => 1][$id])]);
        }
        foreach ([1 => ['English', 'Scholastic'], 2 => ['IT', 'Scholastic'], 3 => ['Art Education', 'Co-Scholastic']] as $subject => [$name, $type]) {
            DB::table('subjects_on_report_card_master')->insert(['sub_rc_master_id' => $subject, 'name' => $name, 'sequence' => $subject]);
            DB::table('subjects_on_report_card')->insert(['class_id' => 109, 'academic_yr' => '2026-2027', 'sub_rc_master_id' => $subject, 'subject_type' => $type]);
        }
        foreach ([11, 12, 13] as $exam) {
            foreach ([1 => 40, 2 => 20] as $subject => $maximum) {
                $this->heading($exam, $subject, 101, $maximum, $subject === 1 ? 5 : 10);
                $raw = [11 => 32, 12 => 24, 13 => 36][$exam] / ($subject === 1 ? 1 : 2);
                $this->mark($exam, $subject, [101 => $raw], [101 => $maximum], ['Periodic test' => $raw / $maximum * ($subject === 1 ? 5 : 10)]);
                DB::table('student_marks')->where('exam_id', $exam)->where('subject_id', $subject)
                    ->update(['reportcard_highest_marks' => json_encode(['Periodic test' => $subject === 1 ? 5 : 10])]);
            }
        }
        foreach ([21, 22] as $exam) {
            $sa = $exam === 21 ? 105 : 106;
            foreach ([101 => 5, 102 => 5, 103 => 5, 104 => 5, $sa => 80] as $heading => $maximum) {
                $this->heading($exam, 1, $heading, $maximum, $maximum);
            }
            $this->mark($exam, 1, [], [], ['Periodic test' => 1, 'Multiple assessment' => 4, 'Subject enrichment' => 4, 'Portfolio' => 5, $exam === 21 ? 'SA-1' : 'SA-2' => 70]);
            foreach ([101 => 10, 104 => 10, 201 => 20, 202 => 10, $sa => 50] as $heading => $maximum) {
                $this->heading($exam, 2, $heading, $maximum, $maximum);
            }
            $this->mark($exam, 2, [], [], ['Periodic test' => 1, 'Practical' => 18, 'Oral/Viva' => 9, 'Portfolio' => 9, $exam === 21 ? 'SA-1' : 'SA-2' => 45]);
            $this->heading($exam, 3, 301, 50, 50);
            $this->mark($exam, 3, [], [], ['Total Marks' => 40]);
        }
        DB::table('grade')->insert([
            ['class_id' => 109, 'subject_type' => 'Co-Scholastic', 'mark_from' => 41, 'mark_upto' => 50, 'name' => 'A'],
            ['class_id' => 109, 'subject_type' => 'Co-Scholastic', 'mark_from' => 0, 'mark_upto' => 40, 'name' => 'B'],
            ['class_id' => 109, 'subject_type' => 'Scholastic', 'mark_from' => 0, 'mark_upto' => 100, 'name' => 'A1'],
        ]);
        DB::table('settings')->insert(['academic_yr' => '2026-2027', 'academic_yr_from' => '2026-04-01', 'academic_yr_to' => '2027-03-31']);
        DB::table('attendance')->insert(['student_id' => 9001, 'academic_yr' => '2026-2027', 'only_date' => '2026-05-01', 'attendance_status' => 0]);
        DB::table('report_card_remarks')->insert([
            ['student_id' => 9001, 'term_id' => 21, 'remark' => 'Good progress', 'promot' => ''],
            ['student_id' => 9001, 'term_id' => 22, 'remark' => 'Keep it up', 'promot' => '10'],
        ]);
        DB::table('report_card_publish')->update(['reopen_date' => '2027-04-01']);
        JWTAuth::shouldReceive('getPayload')->andReturn(collect(['short_name' => 'JPS']));
        $this->app->instance('global_variables', ['parent_app_url' => '', 'codeigniter_app_url' => '']);
    }

    private function heading(int $exam, int $subject, int $heading, int $rawMax, int $reportMax): void
    {
        DB::table('allot_mark_headings')->insert(['exam_id' => $exam, 'class_id' => 109, 'academic_yr' => '2026-2027', 'sm_id' => $subject, 'marks_headings_id' => $heading, 'highest_marks' => $rawMax, 'reportcard_highest_marks' => $reportMax]);
    }

    private function mark(int $exam, int $subject, array $raw, array $highest, array $report): void
    {
        DB::table('student_marks')->insert(['student_id' => 9001, 'class_id' => 109, 'section_id' => 201, 'academic_yr' => '2026-2027', 'exam_id' => $exam, 'subject_id' => $subject, 'publish' => 'Y', 'present' => '{}', 'mark_obtained' => json_encode($raw), 'highest_marks' => json_encode($highest), 'reportcard_marks' => json_encode($report), 'reportcard_highest_marks' => json_encode(array_map(fn () => 5, $report))]);
    }

    private function build(): array
    {
        return app(JpsClass9ReportCardDataBuilder::class)->build(109, 9001, '2026-2027');
    }

    public function test_best_periodic_is_scaled_to_each_subjects_maximum_and_counted_once(): void
    {
        $data = $this->build();
        $this->assertCount(2, $data['blocks']);
        $this->assertCount(2, $data['groups']);
        $english = $data['groups'][0]['rows'][0]['terms'];
        $this->assertSame(32.0, $english[0]['raw']['value']);
        $this->assertSame(4.0, $english[0]['components'][0]['value']);
        $this->assertSame(36.0, $english[1]['raw']['value']);
        $this->assertSame(5.0, $english[1]['components'][0]['value']);
        $this->assertSame(88.0, $english[1]['total']);
        $this->assertSame(100.0, $english[1]['maximum']);
        $it = $data['groups'][1]['rows'][0]['terms'];
        $this->assertSame(18.0, $it[1]['raw']['value']);
        $this->assertSame(9.0, $it[1]['components'][0]['value']);
        $this->assertSame(90.0, $it[1]['total']);
        $this->assertSame('B', $data['co_scholastic'][0]['terms'][0]['grade']);
        $this->assertSame(50.0, $data['co_scholastic'][0]['terms'][0]['maximum']);
    }

    public function test_published_exams_and_marks_only_are_used(): void
    {
        DB::table('report_card_publish')->where('term_id', 13)->update(['publish' => 'N']);
        $data = $this->build();
        $this->assertSame(24.0, $data['groups'][0]['rows'][0]['terms'][1]['raw']['value']);
        $this->assertSame(3.0, $data['groups'][0]['rows'][0]['terms'][1]['components'][0]['value']);
        DB::table('report_card_publish')->where('term_id', 22)->update(['publish' => 'N']);
        $this->assertCount(1, $this->build()['blocks']);
        DB::table('student_marks')->where('exam_id', 11)->update(['publish' => 'N']);
        $this->assertSame('', $this->build()['groups'][0]['rows'][0]['terms'][0]['raw']['value']);
        DB::table('report_card_publish')->where('term_id', 21)->update(['publish' => 'N']);
        $this->assertSame([], $this->build()['groups']);
    }

    public function test_missing_absent_and_zero_scores_are_distinct(): void
    {
        DB::table('student_marks')->whereIn('exam_id', [12, 13])->where('subject_id', 1)
            ->update(['mark_obtained' => '{"101":"Ab"}', 'present' => '{"101":"N"}']);
        $this->assertSame('Ab', $this->build()['groups'][0]['rows'][0]['terms'][1]['components'][0]['value']);
        DB::table('student_marks')->where('exam_id', 13)->where('subject_id', 1)
            ->update(['mark_obtained' => '{"101":0}', 'present' => '{"101":"Y"}']);
        $this->assertSame(0.0, $this->build()['groups'][0]['rows'][0]['terms'][1]['components'][0]['value']);
        DB::table('student_marks')->where('exam_id', 13)->where('subject_id', 1)
            ->update(['mark_obtained' => '{}', 'highest_marks' => '{}', 'present' => '{}', 'reportcard_marks' => '{"Periodic test":4.5}', 'reportcard_highest_marks' => '{"Periodic test":5}']);
        $this->assertSame(36.0, $this->build()['groups'][0]['rows'][0]['terms'][1]['raw']['value']);
    }

    public function test_view_is_dynamic_query_free_and_renders_a_pdf(): void
    {
        $data = $this->build();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $data])->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        foreach (['TERM I', 'TERM II', 'PT - II / PT - III', 'English', 'IT', 'Practical', 'Oral/Viva', 'Art Education', '88', 'CO- SCHOLASTICS AREA'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringNotContainsString('Hindi', $html);
        $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4', 'portrait');
        $output = $pdf->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringContainsString('/Subtype /Image', $output);
        $this->assertGreaterThan($pdf->getDomPDF()->getCanvas()->get_width(), $pdf->getDomPDF()->getCanvas()->get_height());
    }

    public function test_class9_route_keeps_its_own_format(): void
    {
        PDF::shouldReceive('loadView')->once()->with('reportcard.JPS.class9_report_card_pdf', \Mockery::type('array'))->andReturnSelf();
        PDF::shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        PDF::shouldReceive('stream')->once()->with('Class9_Report_Card_9001.pdf')->andReturn(response('PDF', 200));
        $this->getJson('/api/show_report_card?class_id=109&student_id=9001&acd_yr=2026-2027')->assertOk();

    }

    public function test_full_subject_list_and_exam_suffixes_use_the_copied_portrait_layout(): void
    {
        DB::table('exam')->where('exam_id', 21)->update(['name' => 'TERM I (Half Yearly)']);
        foreach ([4 => 'Hindi', 5 => 'Marathi', 6 => 'Maths', 7 => 'Science', 8 => 'Social Science', 9 => 'Individuals in Society', 10 => 'Skill Education', 11 => 'Physical Education'] as $id => $name) {
            $source = $id < 9 ? 1 : 3;
            DB::table('subjects_on_report_card_master')->insert(['sub_rc_master_id' => $id, 'name' => $name, 'sequence' => $id]);
            DB::table('subjects_on_report_card')->insert(['class_id' => 109, 'academic_yr' => '2026-2027', 'sub_rc_master_id' => $id, 'subject_type' => $source === 1 ? 'Scholastic' : 'Co-Scholastic']);
            foreach (DB::table('allot_mark_headings')->where('sm_id', $source)->get() as $heading) {
                DB::table('allot_mark_headings')->insert(array_replace((array) $heading, ['sm_id' => $id]));
            }
            foreach (DB::table('student_marks')->where('subject_id', $source)->get() as $mark) {
                DB::table('student_marks')->insert(array_replace((array) $mark, ['subject_id' => $id]));
            }
        }
        $data = $this->build();
        $this->assertCount(6, $data['groups'][0]['rows']);
        $this->assertCount(4, $data['co_scholastic']);
        $this->assertSame('TERM I (Half Yearly)', $data['blocks'][0]['exam']->name);
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $data])->render();
        $pdf = app('dompdf.wrapper')->loadHTML($html)->setPaper('a4', 'portrait');
        $this->assertStringStartsWith('%PDF-', $pdf->output());
        $this->assertGreaterThan($pdf->getDomPDF()->getCanvas()->get_width(), $pdf->getDomPDF()->getCanvas()->get_height());
    }

    public function test_copied_styles_footer_data_and_class9_displayed_totals_are_preserved(): void
    {
        preg_match('/<style[^>]*>(.*?)<\/style>/s', file_get_contents(resource_path('views/reportcard/JPS/class6to8_report_card_pdf.blade.php')), $reference);
        preg_match('/<style[^>]*>(.*?)<\/style>/s', file_get_contents(resource_path('views/reportcard/JPS/class9_report_card_pdf.blade.php')), $actual);
        $normalizeCss = static fn ($css) => preg_replace('/[ \t]+$/m', '', str_replace("\r\n", "\n", $css));
        $this->assertSame($normalizeCss($reference[1]), $normalizeCss($actual[1]));

        $data = $this->build();
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $data])->render();
        foreach (['Good progress', 'Keep it up', '1/1', '01-04-2027', "Class Teacher's Sign.", "Parent's Sign.", "Principal's Sign."] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $cells = $xpath->query('//tr[td[1][normalize-space(.)="English"]]/td');
        $this->assertSame(
            ['English', '4', '4', '4', '4', '5', '70', '91', 'A1', '36', '5', '4', '4', '5', '70', '88', 'A1'],
            array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($cells))
        );
        $this->assertStringContainsString('0-100', $html);
        $this->assertStringContainsString('1 Point grading scale', $html);
    }

    public function test_pt1_uses_dynamic_headings_and_saved_reportcard_values_instead_of_raw_marks(): void
    {
        DB::table('marks_headings')->insert([
            ['marks_headings_id' => 401, 'name' => 'PT Written', 'sequence' => 1],
            ['marks_headings_id' => 402, 'name' => 'PT Oral', 'sequence' => 2],
        ]);
        DB::table('allot_mark_headings')->where('exam_id', 11)->where('sm_id', 1)
            ->update(['marks_headings_id' => 401, 'reportcard_highest_marks' => 5]);
        $this->heading(11, 1, 402, 10, 5);
        DB::table('student_marks')->where('exam_id', 11)->where('subject_id', 1)->update([
            'reportcard_marks' => '{"PT Oral":8,"PT Written":27.5}',
            'reportcard_highest_marks' => '{"PT Oral":10,"PT Written":35}',
            'mark_obtained' => '{"401":40,"402":10}',
            'highest_marks' => '{"401":40,"402":10}',
        ]);
        $data = $this->build();
        $term = $data['groups'][0]['rows'][0]['terms'][0];
        $this->assertSame([
            ['label' => 'PT Written', 'value' => 28.0, 'maximum' => 35.0],
            ['label' => 'PT Oral', 'value' => 8.0, 'maximum' => 10.0],
        ], $term['periodic']);
        $this->assertSame(4.0, $term['components'][0]['value']);
        $this->assertSame(123.0, $term['total']);
        $this->assertSame(145.0, $term['maximum']);
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $data])->render();
        foreach (['PT Written', 'PT Oral', '(35)', '(10)', '28', '123'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $cells = $xpath->query('//tr[td[1][normalize-space(.)="English"]]/td');
        $this->assertCount(18, $cells);
        $this->assertSame('28', trim($cells->item(1)->textContent));
        $this->assertSame('8', trim($cells->item(2)->textContent));
    }

    public function test_pt1_saved_zero_absence_and_missing_values_are_distinct(): void
    {
        $query = DB::table('student_marks')->where('exam_id', 11)->where('subject_id', 1);
        $query->update(['reportcard_marks' => '{"Periodic test":0}', 'reportcard_highest_marks' => '{"Periodic test":40}']);
        $term = $this->build()['groups'][0]['rows'][0]['terms'][0];
        $this->assertSame(0.0, $term['periodic'][0]['value']);
        $this->assertSame(40.0, $term['periodic'][0]['maximum']);
        $this->assertSame(0.0, $term['components'][0]['value']);
        $query->update(['reportcard_marks' => '{"Periodic test":"Ab"}']);
        $this->assertSame('Ab', $this->build()['groups'][0]['rows'][0]['terms'][0]['periodic'][0]['value']);
        $query->update(['reportcard_marks' => '{"Periodic test":""}']);
        $term = $this->build()['groups'][0]['rows'][0]['terms'][0];
        $this->assertSame('', $term['periodic'][0]['value']);
        $this->assertSame('', $term['components'][0]['value']);
    }

    public function test_term1_total_includes_pt1_and_rounds_each_mark_before_summing(): void
    {
        // Match the reported case: 5 PT marks plus the remaining 95 term marks.
        DB::table('allot_mark_headings')->where('exam_id', 21)->where('sm_id', 1)
            ->where('marks_headings_id', 101)->delete();
        DB::table('student_marks')->where('exam_id', 11)->where('subject_id', 1)
            ->update(['reportcard_marks' => '{"Periodic test":4.88}', 'reportcard_highest_marks' => '{"Periodic test":5}']);
        DB::table('student_marks')->where('exam_id', 21)->where('subject_id', 1)
            ->update(['reportcard_marks' => '{"Multiple assessment":5,"Subject enrichment":5,"Portfolio":5,"SA-1":76}']);
        $data = $this->build();
        $term = $data['groups'][0]['rows'][0]['terms'][0];
        $this->assertSame(5.0, $term['periodic'][0]['value']);
        $this->assertSame(96.0, $term['total']);
        $this->assertSame(100.0, $term['maximum']);
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $data])->render();
        $this->assertStringContainsString('Total<br/>(100)', $html);
        $this->assertStringContainsString('193/210', $html);
        $this->assertStringNotContainsString('4.88', $html);

        DB::table('student_marks')->where('exam_id', 11)->where('subject_id', 1)
            ->update(['reportcard_marks' => '{"Periodic test":2.5}']);
        DB::table('student_marks')->where('exam_id', 21)->where('subject_id', 1)
            ->update(['reportcard_marks' => '{"Multiple assessment":4.5,"Subject enrichment":4.49,"Portfolio":4.75,"SA-1":75.5}']);
        $term = $this->build()['groups'][0]['rows'][0]['terms'][0];
        $this->assertSame(3.0, $term['periodic'][0]['value']);
        $this->assertSame([5.0, 4.0, 5.0, 76.0], array_column($term['components'], 'value'));
        $this->assertSame(93.0, $term['total']);
    }

    public function test_it_shares_the_scholastic_table_with_clear_headings_and_one_combined_total(): void
    {
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $this->build()])->render();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $english = $xpath->query('//tr[td[1][normalize-space(.)="English"]]')->item(0);
        $it = $xpath->query('//tr[td[1][normalize-space(.)="IT"]]')->item(0);
        $englishTable = $xpath->query('ancestor::table[1]', $english)->item(0);
        $itTable = $xpath->query('ancestor::table[1]', $it)->item(0);
        $this->assertTrue($englishTable->isSameNode($itTable));
        $this->assertSame(1, substr_count($html, '<b>Scholastic Areas</b>'));
        $headings = $xpath->query('preceding-sibling::tr[1]', $it)->item(0)->textContent;
        $this->assertStringContainsString('Practical', $headings);
        $this->assertStringContainsString('Oral/Viva', $headings);
        $totals = $xpath->query('.//tr[td[1][normalize-space(.)="TOTAL"]]', $englishTable);
        $this->assertCount(1, $totals);
        $this->assertStringContainsString('188/215', $totals->item(0)->textContent);
        $this->assertStringContainsString('178/200', $totals->item(0)->textContent);
    }

    public function test_different_assessment_counts_keep_term_columns_aligned(): void
    {
        DB::table('allot_mark_headings')->whereIn('exam_id', [21, 22])
            ->where('sm_id', 2)->where('marks_headings_id', 202)->delete();
        $html = view('reportcard.JPS.class9_report_card_pdf', ['reportCardData' => $this->build()])->render();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        foreach (['English', 'IT', 'TOTAL'] as $label) {
            $row = $xpath->query('//tr[td[1][normalize-space(.)="' . $label . '"]]')->item(0);
            $width = 0;
            foreach ($xpath->query('./td', $row) as $cell) {
                $width += (int) ($cell->getAttribute('colspan') ?: 1);
            }
            $this->assertSame(17, $width);
        }
    }

    public function test_invalid_student_scope_and_missing_parameters_are_rejected(): void
    {
        $this->getJson('/api/show_report_card?class_id=109&student_id=9001')->assertUnprocessable()->assertJsonValidationErrors('acd_yr');
        $this->getJson('/api/show_report_card?class_id=109&student_id=9001&acd_yr=2025-2026')->assertNotFound();
    }
}
