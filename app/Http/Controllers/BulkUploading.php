<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class BulkUploading extends Controller
{
    public function downloadTeacherCsvTemplate(Request $request)
    {
        $roles = \DB::table('role_master')
            ->select('name', 'role_id')
            ->get();

        $roleString = $roles->map(function ($role) {
            return $role->name . '=' . $role->role_id;
        })->implode(',');
        $categories = \DB::table('teacher_category')
            ->select('name', 'tc_id')
            ->get();

        $categoryString = $categories->map(function ($cat) {
            return $cat->name . '=' . $cat->tc_id;
        })->implode(',');
        $teachers = \App\Models\Teacher::select(
            'employee_id as *Employee ID',
            'name as *Staff Name',
            'birthday as *DOB(dd/mm/yyyy)',
            'date_of_joining as *Date Of Joining(dd/mm/yyyy)',
            'sex as *Gender',
            'religion as Religion(Hindu,Christian,Muslim,Sikh,Jain,Buddhist)',
            'blood_group as Blood group (as A+,B+,O+,AB+ etc)',
            'address as *Address',
            'phone as Phone(Without +91)',
            'email as *Email',
            'designation as *Designation',
            'academic_qual as *Academic Qualification (eg. Bsc, Bcom, BA etc)',
            'professional_qual as Professional Qualification (eg. B.Ed, D.Ed)',
            'special_sub as Special Subject',
            'trained as *Training status (eg. Trained TGT,Trained PGT, Untrained)',
            'experience as *Experience in years',
            'aadhar_card_no as *Aadhar card No.',
            'emergency_phone as Emergency contact no.(Without +91)',
            'permanent_address as *Permanent Address',
            'tc_id as Teacher Category (' . $categoryString . ')',
        )->get()->toArray();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teacher_template.csv"',
        ];

        $columns = [
            '*Employee ID',
            '*Staff Name',
            '*DOB(dd/mm/yyyy)',
            '*Date Of Joining(dd/mm/yyyy)',
            '*Gender',
            'Religion(Hindu,Christian,Muslim,Sikh,Jain,Buddhist)',
            'Blood group (as A+,B+,O+,AB+ etc)',
            '*Address',
            'Phone(Without +91)',
            '*Email',
            '*Designation',
            '*Academic Qualification (eg. Bsc, Bcom, BA etc)',
            'Professional Qualification (eg. B.Ed, D.Ed)',
            'Special Subject',
            '*Training status (eg. Trained TGT,Trained PGT, Untrained)',
            '*Experience in years',
            '*Aadhar card No.',
            'Emergency contact no.(Without +91)',
            '*Permanent Address',
            'Teacher Category (' . $categoryString . ')',
            'Role(' . $roleString . ')',
        ];

        $callback = function () use ($columns, $teachers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NOTE: Fields with * are mandatory']);
            fputcsv($file, []);
            fputcsv($file, $columns);

            foreach ($teachers as $teacher) {
                fputcsv($file, $teacher);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function uploadTeacherCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        $csvData = file_get_contents($file->getRealPath());
        $rows = array_map('str_getcsv', explode("\n", $csvData));
        $header = array_shift($rows);

        // ✅ PRELOAD MASTER DATA (IMPORTANT)
        $roles = \DB::table('role_master')
            ->pluck('role_id', 'role_name')
            ->mapWithKeys(fn($v, $k) => [strtolower(trim($k)) => $v])
            ->toArray();

        $categories = \DB::table('teacher_category')
            ->pluck('tc_id', 'teacher_category')
            ->mapWithKeys(fn($v, $k) => [strtolower(trim($k)) => $v])
            ->toArray();

        $columnMap = [
            '*Employee ID' => 'employee_id',
            '*Staff Name' => 'name',
            '*DOB(dd/mm/yyyy)' => 'birthday',
            '*Date Of Joining(dd/mm/yyyy)' => 'date_of_joining',
            '*Gender' => 'sex',
            'Religion(Hindu,Christian,Muslim,Sikh,Jain,Buddhist)' => 'religion',
            'Blood group (as A+,B+,O+,AB+ etc)' => 'blood_group',
            '*Address' => 'address',
            'Phone(Without +91)' => 'phone',
            '*Email' => 'email',
            '*Designation' => 'designation',
            '*Academic Qualification (eg. Bsc, Bcom, BA etc)' => 'academic_qual',
            'Professional Qualification (eg. B.Ed, D.Ed)' => 'professional_qual',
            'Special Subject' => 'special_sub',
            '*Training status (eg. Trained TGT,Trained PGT, Untrained)' => 'trained',
            '*Experience in years' => 'experience',
            '*Aadhar card No.' => 'aadhar_card_no',
            'Emergency contact no.(Without +91)' => 'emergency_phone',
            '*Permanent Address' => 'permanent_address',
            'Teacher Category' => 'tc_id',
            'Role' => 'role',
        ];

        $invalidRows = [];
        $successCount = 0;

        foreach ($rows as $row) {
            if (empty(array_filter($row)))
                continue;

            $data = [];

            foreach ($header as $i => $columnName) {
                if (isset($columnMap[$columnName])) {
                    $data[$columnMap[$columnName]] = trim($row[$i] ?? null);
                }
            }

            // ✅ CLEANING
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
            $data['emergency_phone'] = preg_replace('/\D/', '', $data['emergency_phone']);
            $data['aadhar_card_no'] = preg_replace('/\D/', '', $data['aadhar_card_no']);
            $data['birthday'] = preg_replace('/[^0-9\/]/', '', $data['birthday']);
            $data['date_of_joining'] = preg_replace('/[^0-9\/]/', '', $data['date_of_joining']);

            $errors = [];

            // ✅ VALIDATIONS
            if (empty($data['employee_id']))
                $errors[] = 'Employee ID required';
            if (empty($data['name']))
                $errors[] = 'Name required';

            if (empty($data['sex']) || !in_array($data['sex'], ['M', 'F', 'O'])) {
                $errors[] = 'Gender must be M/F/O';
            }

            if (!empty($data['phone']) && strlen($data['phone']) != 10) {
                $errors[] = 'Phone must be 10 digits';
            }

            if (!empty($data['aadhar_card_no']) && strlen($data['aadhar_card_no']) != 12) {
                $errors[] = 'Aadhar must be 12 digits';
            }

            if (!empty($data['birthday']) && !$this->validateDate($data['birthday'], 'd/m/Y')) {
                $errors[] = 'Invalid DOB format';
            }

            if (!empty($data['date_of_joining']) && !$this->validateDate($data['date_of_joining'], 'd/m/Y')) {
                $errors[] = 'Invalid DOJ format';
            }

            // ✅ ROLE MAPPING
            if (!empty($data['role'])) {
                $key = strtolower(trim($data['role']));
                if (!isset($roles[$key])) {
                    $errors[] = 'Invalid Role';
                } else {
                    $data['role'] = $roles[$key];
                }
            }

            // ✅ CATEGORY MAPPING
            if (!empty($data['tc_id'])) {
                $key = strtolower(trim($data['tc_id']));
                if (!isset($categories[$key])) {
                    $errors[] = 'Invalid Teacher Category';
                } else {
                    $data['tc_id'] = $categories[$key];
                }
            }

            // ❌ STORE INVALID
            if (!empty($errors)) {
                $invalidRows[] = array_merge($row, ['error' => implode(' | ', $errors)]);
                continue;
            }

            // ✅ DATE CONVERSION
            if (!empty($data['birthday'])) {
                $data['birthday'] = \Carbon\Carbon::createFromFormat('d/m/Y', $data['birthday'])->format('Y-m-d');
            }

            if (!empty($data['date_of_joining'])) {
                $data['date_of_joining'] = \Carbon\Carbon::createFromFormat('d/m/Y', $data['date_of_joining'])->format('Y-m-d');
            }

            try {
                \App\Models\Teacher::updateOrCreate(
                    ['employee_id' => $data['employee_id']],
                    array_merge($data, [
                        'isDelete' => 'N',
                        'created_by' => auth()->id() ?? 1,
                    ])
                );

                $successCount++;
            } catch (\Exception $e) {
                $invalidRows[] = array_merge($row, ['error' => $e->getMessage()]);
            }
        }

        // ❌ REJECTED CSV
        if (!empty($invalidRows)) {
            $csv = \League\Csv\Writer::createFromString('');
            $csv->insertOne(array_merge($header, ['error']));

            foreach ($invalidRows as $r) {
                $csv->insertOne($r);
            }

            $filePath = 'public/csv_rejected/teacher_' . now()->format('YmdHis') . '.csv';
            \Storage::put($filePath, $csv->toString());

            return response()->json([
                'message' => 'Some rows failed',
                'file' => str_replace('public/', '', $filePath)
            ], 422);
        }

        if ($successCount === 0) {
            return response()->json([
                'message' => 'No valid data found'
            ], 422);
        }

        return response()->json([
            'message' => 'Teachers uploaded successfully'
        ]);
    }
}
