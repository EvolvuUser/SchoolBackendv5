<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    private const STATUSES = ['NEW', 'HOLD', 'REJECTED', 'APPROVED'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $academicYears = $this->enquiryAcademicYears();

        $this->normalizeStatus($request);
        $filters = $request->validate([
            'class' => ['nullable', 'string', 'max:100'],
            'academic_year' => ['nullable', 'string', Rule::in($academicYears)],
            'status' => ['nullable', 'string', Rule::in(self::STATUSES)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);

        $enquiries = DB::table('enquiries as e')->whereIn('e.academic_yr', $academicYears);
        $classes = (clone $enquiries)->whereNotNull('e.class')->where('e.class', '<>', '')
            ->distinct()->orderBy('e.class')->pluck('e.class');

        foreach (['class' => 'e.class', 'academic_year' => 'e.academic_yr', 'status' => 'e.status'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $enquiries->where($column, $filters[$filter]);
            }
        }

        if (isset($filters['date_from'])) {
            $enquiries->where('e.created_at', '>=', $filters['date_from'].' 00:00:00');
        }
        if (isset($filters['date_to'])) {
            $enquiries->where('e.created_at', '<', Carbon::parse($filters['date_to'])->addDay()->toDateString().' 00:00:00');
        }

        $results = $enquiries
            ->leftJoin('online_admission_form as f', function ($join) {
                // A registration can be shared by multiple enquiries. Use the individual conversion reference.
                $join->on('f.form_id', '=', 'e.conversion_reference')->on('f.academic_yr', '=', 'e.academic_yr');
            })
            ->leftJoin('student as s', 's.student_id', '=', 'f.student_id')
            ->select('e.id', 'e.enquiry_number', 'e.first_name', 'e.middle_name', 'e.last_name', 'e.class',
                'e.academic_yr', 'e.father_name', 'e.mother_name', 'e.contact_no', 'e.email', 'e.created_at',
                'e.status', 's.admission_date')
            ->orderByDesc('e.created_at')
            ->orderByDesc('e.id')
            ->get()
            ->map(fn ($enquiry) => [
                'id' => $enquiry->id,
                'enquiry_number' => $enquiry->enquiry_number,
                'student_name' => $this->joinNames([$enquiry->first_name, $enquiry->middle_name, $enquiry->last_name], ' '),
                'class' => $enquiry->class,
                'academic_year' => $enquiry->academic_yr,
                'class_display' => $enquiry->class !== null && $enquiry->class !== ''
                    ? $enquiry->class.' ('.$enquiry->academic_yr.')'
                    : null,
                'parent_name' => $this->joinNames([$enquiry->father_name, $enquiry->mother_name], ' / '),
                'phone_no' => $enquiry->contact_no,
                'email' => $enquiry->email,
                'admission_date' => $enquiry->admission_date,
                'created_at' => $enquiry->created_at,
                'status' => $enquiry->status,
            ]);

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => $results->count(),
            ],
            'filter_options' => [
                'academic_years' => $academicYears,
                'classes' => $classes,
                'statuses' => self::STATUSES,
            ],
        ]);
    }

    public function classOptions(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $classes = Classes::whereIn('academic_yr', $this->enquiryAcademicYears())
            ->groupBy('name', 'academic_yr')
            ->orderByRaw('MIN(class_id)')
            ->get(['name as class_name', 'academic_yr']);

        return response()->json(['data' => $classes]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $this->authorizeAccess($request);
        $this->normalizeStatus($request);
        $validated = $request->validate(['status' => ['required', 'string', Rule::in(self::STATUSES)]]);
        $status = $validated['status'];

        $enquiry = DB::transaction(function () use ($id, $status) {
            $query = DB::table('enquiries')->where('id', $id);
            $enquiry = $query->lockForUpdate()->first(['id', 'enquiry_number', 'status', 'updated_at']);
            abort_if($enquiry === null, 404, 'Enquiry not found.');

            if ($enquiry->status !== $status) {
                $enquiry->status = $status;
                $enquiry->updated_at = now()->toDateTimeString();
                $query->update(['status' => $enquiry->status, 'updated_at' => $enquiry->updated_at]);
            }

            return $enquiry;
        });

        return response()->json(['data' => $enquiry]);
    }

    private function enquiryAcademicYears(): array
    {
        $currentYear = Setting::where('active', 'Y')->orderByDesc('academic_yr_from')->value('academic_yr');
        abort_unless(
            is_string($currentYear) && preg_match('/^\d{4}-(\d{4})$/D', $currentYear, $years),
            409,
            'Configure the active academic year in YYYY-YYYY format.'
        );

        $nextStartYear = (int) $years[1];

        return [$currentYear, $nextStartYear.'-'.($nextStartYear + 1)];
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless(in_array($request->user()?->role_id, ['A', 'M', 'U'], true), 403, 'Admissions access required.');
    }

    private function normalizeStatus(Request $request): void
    {
        $status = $request->input('status');
        if (is_string($status)) {
            $request->merge(['status' => strtoupper(trim($status))]);
        }
    }

    private function joinNames(array $names, string $separator): ?string
    {
        $parts = array_filter(array_map(fn ($name) => trim($name ?? ''), $names), fn ($name) => $name !== '');

        return $parts === [] ? null : implode($separator, $parts);
    }
}
