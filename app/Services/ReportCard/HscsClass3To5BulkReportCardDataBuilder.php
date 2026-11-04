<?php

namespace App\Services\ReportCard;

use Illuminate\Support\Facades\DB;

class HscsClass3To5BulkReportCardDataBuilder extends Class3To5BulkReportCardDataBuilder
{
    protected string $firstTermAttendanceEnd = '-10-31';

    public function build($classId, $sectionId, $academicYr, $studCount = null): array
    {
        $data = parent::build($classId, $sectionId, $academicYr, $studCount);
        $data['section_name'] = DB::table('section')->where('section_id', $sectionId)->value('name');

        // HSCS uses the class's maximum recorded working days, including students outside this batch.
        if ($data['students'] !== [] && $data['date_from'] && $data['date_to']) {
            $workingDays = DB::table('attendance')->where('class_id', $classId)->where('section_id', $sectionId)
                ->where('academic_yr', $academicYr)->whereBetween('only_date', [$data['date_from'], $data['date_to']])
                ->select('student_id')->selectRaw('COUNT(*) as working_days')->groupBy('student_id');
            $working = DB::query()->fromSub($workingDays, 'class_working_days')->max('working_days');
            foreach ($data['attendance_by_student'] as &$attendance) {
                $attendance['working'] = $working;
            }
            unset($attendance);
        }

        return $data;
    }
}
