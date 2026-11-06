<?php

namespace Tests\Unit\JpsReport;

use DOMDocument;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class JpsScholasticReportTest extends TestCase
{
    public static array $results = [];

    public function test_columns_follow_headings_instead_of_saved_json_order(): void
    {
        self::$results = [
            1 => [
                11 => ['Term' => 70, 'Portfolio' => 4, 'Assessment' => 5],
                12 => ['Portfolio' => 0],
            ],
            2 => [11 => ['Assessment' => 3]],
        ];
        [$document, $data] = $this->renderReport();
        $rows = $document->getElementsByTagName('tr');
        // Both terms, including an exam with no saved marks, have identical row shapes.
        foreach ($rows as $index => $row) {
            if ($index > 0) {
                $this->assertSame(11, $row->getElementsByTagName($index === 1 ? 'th' : 'td')->length);
            }
        }
        $cells = $rows->item(2)->getElementsByTagName('td');
        $this->assertSame(['English', '5', '4', '70', '79', 'grade:44', '3', '', '', '3', 'grade:30'],
            array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($cells)));
        $this->assertSame(80.0, $data[1]['headings']['Term']);
        $this->assertSame(95.0, $data[1]['grandMaximum']);
        $this->assertSame(79.0, $data[1]['grandTotal']);
        $this->assertSame(0.0, $data[1]['subjects'][12]['total']);
        $this->assertSame('grade:0', $data[1]['subjects'][12]['grade']);
        $this->assertSame(5.0, $data[2]['grandMaximum']);

        $pdf = new \Dompdf\Dompdf;
        $pdf->loadHtml($document->saveHTML());
        $pdf->setPaper('A4');
        $pdf->render();
        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }

    public function test_missing_and_absent_marks_keep_their_columns(): void
    {
        self::$results = [1 => [11 => ['Assessment' => 'Ab', 'Portfolio' => 'Ab', 'Term' => 'Ab']]];
        [$document, $data] = $this->renderReport();
        $this->assertSame('Ab', $data[1]['subjects'][11]['grade']);
        $this->assertSame('', $data[1]['subjects'][12]['grade']);
        $this->assertSame(90.0, $data[1]['grandMaximum']);
        $cells = $document->getElementsByTagName('tr')->item(3)->getElementsByTagName('td');
        $this->assertSame(11, $cells->length);
        for ($i = 1; $i < $cells->length; $i++) {
            $this->assertSame('', trim($cells->item($i)->textContent));
        }
    }

    public function test_multiple_exams_share_columns_and_accumulate_marks(): void
    {
        self::$results = [
            1 => [11 => ['Term' => 70, 'Portfolio' => 4, 'Assessment' => 5]],
            3 => [11 => ['Assessment' => 4, 'Portfolio' => 5, 'Term' => 60]],
        ];
        [$document, $data] = $this->renderReport();
        $this->assertSame(11, $document->getElementsByTagName('tr')->item(2)->getElementsByTagName('td')->length);
        $this->assertSame(130.0, $data[1]['subjects'][11]['marks']['Term']);
        $this->assertSame(160.0, $data[1]['headings']['Term']);
        $this->assertSame(148.0, $data[1]['grandTotal']);
        $this->assertSame(180.0, $data[1]['grandMaximum']);
    }

    private function renderReport(): array
    {
        $row1 = ['class_id' => 1, 'section_id' => 1, 'student_id' => 1, 'academic_yr' => '2026-2027'];
        $__env = app('view');
        $compiled = Blade::compileString(file_get_contents(resource_path('views/reportcard/JPS/partials/scholastic.blade.php')));
        // Resolve data helpers to the fixtures below; exercise the actual Blade table without a school DB.
        ob_start();
        try {
            eval('namespace ' . __NAMESPACE__ . '; ?>' . $compiled);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $document = new DOMDocument;
        $document->loadHTML($html);
        return [$document, $scholastic];
    }
}

function get_published_terms(...$args)
{
    return [(object) ['term_id' => 1, 'name' => 'TERM 1'], (object) ['term_id' => 2, 'name' => 'TERM 2']];
}

function get_scholastic_subject_alloted_to_class(...$args)
{
    return [(object) ['sub_rc_master_id' => 11, 'name' => 'English'], (object) ['sub_rc_master_id' => 12, 'name' => 'Hindi']];
}

function get_exams_by_class_per_term($class, $term, $year)
{
    if ($term === 1 && isset(JpsScholasticReportTest::$results[3])) {
        return [(object) ['exam_id' => 1], (object) ['exam_id' => 3]];
    }
    return [(object) ['exam_id' => $term]];
}

function get_marks_heading_class(...$args)
{
    return array_map(fn ($name, $maximum) => (object) ['marks_headings_name' => $name, 'highest_marks' => $maximum],
        ['Assessment', 'Portfolio', 'Term'], [5, 5, 40]);
}

function get_marks($exam, $class, $section, $subject, ...$args)
{
    $marks = JpsScholasticReportTest::$results[$exam][$subject] ?? null;
    return $marks === null ? [] : [[
        'reportcard_marks' => json_encode($marks),
        'reportcard_highest_marks' => json_encode(array_intersect_key(['Assessment' => 5, 'Portfolio' => 5, 'Term' => 80], $marks)),
    ]];
}

function get_grade_based_on_marks($marks, ...$args)
{
    return 'grade:' . $marks;
}
