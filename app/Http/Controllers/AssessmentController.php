<?php

namespace App\Http\Controllers;

use Exception;
use Validator;
use App\Models\User;
use App\Models\Classes;
use App\Models\Parents;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Division;
use App\Models\MarksHeadings;
use App\Models\SubjectForReportCard;
use App\Models\Grades;
use App\Models\Exams;
use App\Models\Term;
use App\Models\Allot_mark_headings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;
use PDF;


class AssessmentController extends Controller
{
    public function getMarksheadingsList(Request $request)
    {

        $marks_headings = MarksHeadings::orderBy('sequence')->get();

        return response()->json($marks_headings);
    }

    public function saveMarksheadings(Request $request)
    {

        $messages = [
            'name.required' => 'Name field is required.',
            'written_exam.required' => 'Written exam field is required.',
            'sequence.required' => 'Sequence field is required.',
            'sequence.unique'   => 'Sequence field Should be unique.',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required'
                ],
                'written_exam' => [
                    'required'
                ],
                'sequence' => [
                    'required',
                    'unique:marks_headings,sequence',
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $marks_headings = new MarksHeadings();
        $marks_headings->name = trim($validatedData['name']);
        $marks_headings->written_exam = $validatedData['written_exam'];
        $marks_headings->sequence = $validatedData['sequence'];

        // Check if mark heading exists, if not, create one

        $existing_markheading = MarksHeadings::where('name', $validatedData['name'])->first();
        if (!$existing_markheading) {
            $marks_headings->save();
            return response()->json([
                'status' => 201,
                'message' => 'Marksheading is saved successfully.',
            ], 201);
        } else {
            return response()->json([
                'error' => 404,
                'message' => 'Marksheading already exists.',
            ], 404);
        }
    }
    public function updateMarksheadings(Request $request, $marks_headings_id)
    {
        $messages = [
            'name.required' => 'Name field is required.',
            'written_exam.required' => 'Written exam field is required.',
            'sequence.required' => 'Sequence field is required.',
            'name.unique' => 'Name field should be unique.',
            'sequence.unique'   => 'Sequence field should be unique',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    Rule::unique('marks_headings') // Ensure uniqueness of name
                        ->ignore($marks_headings_id, 'marks_headings_id') // Ignore the current record
                ],
                'written_exam' => [
                    'required'
                ],
                'sequence' => [
                    'required',
                    Rule::unique('marks_headings')
                        ->ignore($marks_headings_id, 'marks_headings_id')
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $marks_headings = MarksHeadings::find($marks_headings_id);
        if (!$marks_headings) {
            return response()->json(['message' => 'Marksheading not found', 'success' => false], 404);
        }

        // Update the Marksheading
        $marks_headings->name = trim($validatedData['name']);
        $marks_headings->written_exam = $validatedData['written_exam'];
        $marks_headings->sequence = $validatedData['sequence'];
        $marks_headings->save();

        // Return success response
        return response()->json([
            'status' => 200,
            'message' => 'Marksheading updated successfully',
        ]);
    }

    public function deleteMarksheading($marks_headings_id)
    {
        $heading = DB::table('allot_mark_headings')
            ->where('marks_headings_id', $marks_headings_id)
            ->first();

        if ($heading) {
            return response()->json([
                'error' => 'This markheadings is in use. Deletion failed!'
            ], 400);
        }

        $marks_headings = MarksHeadings::find($marks_headings_id);

        if (!$marks_headings) {
            return response()->json([
                'status' => 404,
                'message' => 'Marksheading not found',
            ]);
        } else {

            $marks_headings->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Marksheading data deleted successfully',
                'success' => true
            ]);
        }
    }

    public function editMarksheading($marks_headings_id)
    {
        $marks_headings = MarksHeadings::find($marks_headings_id);

        if (!$marks_headings) {
            return response()->json([
                'status' => 404,
                'message' => 'Marksheading data not found',
            ]);
        }

        return response()->json($marks_headings);
    }

    public function getGradesList(Request $request)
    {

        //$grades = Grades::orderBy('grade_id')->get();
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $query = Grades::with('Class');
        $grades = $query
            ->where('academic_yr', $customClaims)
            ->orderBy('grade_id', 'DESC')
            ->get();

        return response()->json($grades);
    }

    public function saveGrades(Request $request)
    {
        $status_msg = "";
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $messages = [
            'class_id.required' => 'Class field is required.',
            'subject_type.required' => 'Subject type is required.',
            'name.required' => 'Name is required.',
            'mark_from.required' => 'Marks from is required.',
            'mark_upto.required' => 'Marks upto is required.'
        ];

        // Validate the request parameters
        $request->validate([
            'subject_type'     => 'required|string',
            'class_id'      => 'array',
            'class_id.*'    => 'integer',
            'name'     => 'required|string',
            'mark_from'     => 'required',
            'mark_upto'     => 'required',
            'comment'     => 'nullable|string',
        ]);

        // Log the incoming request
        Log::info('Received request to create/update subject allotment', [
            'class_id' => $request->input('class_id'),
            'subject_type' => $request->input('subject_type'),
            'name' => $request->input('name'),
            'mark_upto' => $request->input('mark_upto'),
            'mark_from' => $request->input('mark_from'),
            'comment' => $request->input('comment'),
        ]);


        /*
        try {
            $validatedData = $request->validate([
                'class_id' => [
                    'array,'
                ],
                'subject_type' => [
                    'required'
                ],
                'name' => [
                    'required'
                ],
                'mark_from' => [
                    'required'
                ],
                'mark_upto' => [
                    'required'
                ],
                'comment' => [
                    'nullable'
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }
    */

        $class_id_list = $request->input('class_id');
        foreach ($class_id_list as $class_id) {
            $grades = new Grades();
            $grades->class_id = $class_id;
            $grades->subject_type = $request->input('subject_type'); //$validatedData['subject_type'];
            $grades->name = $request->input('name'); //$validatedData['name'];
            $grades->mark_from = $request->input('mark_from'); //$validatedData['mark_from'];
            $grades->mark_upto = $request->input('mark_upto'); //$validatedData['mark_upto'];
            $grades->comment = $request->input('comment'); //$validatedData['comment'];
            $grades->academic_yr = $academicYr;

            $existing_grades = Grades::where('name', $request->input('name'))->where('class_id', $class_id)->where('subject_type', $request->input('subject_type'))->first();
            if (!$existing_grades) {
                $grades->save();
                $status = 201;
                $status_msg = "Grade is saved successfully.";
            } else {
                $status = 400;
                $status_msg = "Grade already exist for this class.";
            }
        }
        return response()->json([
            'status' => $status,
            'message' => $status_msg,
        ]);
    }

    public function updateGrades(Request $request, $grade_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $messages = [
            'class_id.required' => 'Class field is required.',
            'subject_type.required' => 'Subject type is required.',
            'name.required' => 'Name is required.',
            'mark_from.required' => 'Marks from is required.',
            'mark_upto.required' => 'Marks upto is required.'
        ];

        try {
            $validatedData = $request->validate([
                'class_id' => [
                    'required'
                ],
                'subject_type' => [
                    'required'
                ],
                'name' => [
                    'required'
                ],
                'mark_from' => [
                    'required'
                ],
                'mark_upto' => [
                    'required'
                ],
                'comment' => [
                    'nullable'
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $grades = Grades::find($grade_id);
        if (!$grades) {
            return response()->json(['message' => 'Grade not found', 'success' => false], 404);
        }

        // Update the Marksheading
        $grades->class_id = $validatedData['class_id'];
        $grades->subject_type = $validatedData['subject_type'];
        $grades->name = $validatedData['name'];
        $grades->mark_from = $validatedData['mark_from'];
        $grades->mark_upto = $validatedData['mark_upto'];
        $grades->comment = $validatedData['comment'];
        $grades->academic_yr = $academicYr;
        $grades->save();

        // Return success response
        return response()->json([
            'status' => 200,
            'message' => 'Grade updated successfully',
        ]);
    }

    public function deleteGrades($grade_id)
    {
        $grades = Grades::find($grade_id);

        if (!$grades) {
            return response()->json([
                'status' => 404,
                'message' => 'Grade not found',
            ]);
        } else {

            $grades->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Grade data deleted successfully',
                'success' => true
            ]);
        }
    }

    public function editGrades($grade_id)
    {
        $grades = Grades::find($grade_id);

        if (!$grades) {
            return response()->json([
                'status' => 404,
                'message' => 'Grade data not found',
            ]);
        }

        return response()->json($grades);
    }

    public function getTerm(Request $request)
    {
        $term = Term::orderBy('term_id')->get();

        return response()->json($term);
    }

    public function getExamsList(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $exams = Exams::where('academic_yr', $academicYr)->orderBy('exam_id', 'DESC')->get();

        return response()->json($exams);
    }

    public function saveExams(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $messages = [
            'name.required' => 'Name field is required.',
            'term_id.required' => 'Term is required.',
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'open_day.required' => 'Open day date is required.'
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required'
                ],
                'term_id' => [
                    'required'
                ],
                'start_date' => [
                    'required'
                ],
                'end_date' => [
                    'required'
                ],
                'open_day' => [
                    'required'
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $exams = new Exams();
        $exams->name = trim($validatedData['name']);
        $exams->term_id = $validatedData['term_id'];
        $exams->start_date = $validatedData['start_date'];
        $exams->end_date = $validatedData['end_date'];
        $exams->open_day = $validatedData['open_day'];
        $exams->comment = $request->comment;
        $exams->academic_yr = $academicYr;

        $exams->save();
        return response()->json([
            'status' => 201,
            'message' => 'Exam is saved successfully.',
        ], 201);
    }
    public function updateExam(Request $request, $exam_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $messages = [
            'name.required' => 'Name field is required.',
            'term_id.required' => 'Term is required.',
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'open_day.required' => 'Open day date is required.'
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required'
                ],
                'term_id' => [
                    'required'
                ],
                'start_date' => [
                    'required'
                ],
                'end_date' => [
                    'required'
                ],
                'open_day' => [
                    'required'
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $exams = Exams::find($exam_id);
        if (!$exams) {
            return response()->json(['message' => 'Exam not found', 'success' => false], 404);
        }

        // Update the Exam
        $exams->name = trim($validatedData['name']);
        $exams->term_id = $validatedData['term_id'];
        $exams->start_date = $validatedData['start_date'];
        $exams->end_date = $validatedData['end_date'];
        $exams->open_day = $validatedData['open_day'];
        $exams->comment = $request->comment;
        $exams->academic_yr = $academicYr;
        $exams->save();

        // Return success response
        return response()->json([
            'status' => 200,
            'message' => 'Exam updated successfully',
        ]);
    }

    public function deleteExam($exam_id)
    {
        $examInUse = DB::table('allot_mark_headings')
            ->where('exam_id', $exam_id)
            ->count();

        if ($examInUse > 0) {
            return response()->json([
                'error' => 'This Exam is in use. Deletion failed!'
            ], 400);
        }

        $examInUsee = DB::table('exam_timetable')
            ->where('exam_id', $exam_id)
            ->count();

        if ($examInUsee > 0) {
            return response()->json([
                'error' => 'This Exam is in use. Deletion failed!'
            ], 400);
        }

        $exams = Exams::find($exam_id);

        if (!$exams) {
            return response()->json([
                'status' => 404,
                'message' => 'Exam not found',
            ]);
        } else {

            $exams->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Exam data deleted successfully',
                'success' => true
            ]);
        }
    }

    public function editExam($exam_id)
    {
        $exams = Exams::find($exam_id);

        if (!$exams) {
            return response()->json([
                'status' => 404,
                'message' => 'Exam data not found',
            ]);
        }

        return response()->json($exams);
    }

    public function getAllotMarkheadingsList(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $allot_mark_headings = Allot_mark_headings::with('getClass', 'getSubject', 'getExam', 'getMarksheading')->where('class_id', $class_id)->where('academic_yr', $academicYr)->get();

        return response()->json($allot_mark_headings);
    }

    public function saveAllotMarksheadings(Request $request)
    {
        $payload = getTokenPayload($request);
        if (!$payload) {
            return response()->json(['error' => 'Invalid or missing token'], 401);
        }
        $academicYr = $payload->get('academic_year');

        $messages = [
            'class_id.required' => 'Class is required.',
            'subject_id.required' => 'Subject is required.',
            'exam_id.required' => 'Exam is required.'
        ];

        try {
            $validatedData = $request->validate([
                'class_id' => [
                    'required'
                ],
                'subject_id' => [
                    'required'
                ],
                'exam_id' => [
                    'required'
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }
        //Check if allot mark heading exist for selected class_id, sm_id and exam_id
        $existingMarkheadingsAllotments = Allot_mark_headings::where('class_id',  $request->input('class_id'))
            ->where('sm_id', $request->input('subject_id'))
            ->where('exam_id', $request->input('exam_id'))
            ->where('academic_yr', $academicYr)
            ->get();

        foreach ($existingMarkheadingsAllotments as $result) {
            $allot_mark_heading = Allot_mark_headings::find($result->allot_markheadings_id);
            $allot_mark_heading->delete();
        }

        $highest_marks_allocation_list = $request->input('highest_marks_allocation');

        foreach ($highest_marks_allocation_list as $highest_marks_allocation) {

            $allot_mark_heading = new Allot_mark_headings();
            $allot_mark_heading->class_id = $request->input('class_id');
            $allot_mark_heading->sm_id = $request->input('subject_id');
            $allot_mark_heading->exam_id = $request->input('exam_id');
            $allot_mark_heading->marks_headings_id = $highest_marks_allocation['marks_heading_id'];
            $allot_mark_heading->highest_marks = $highest_marks_allocation['highest_marks'];
            $allot_mark_heading->reportcard_highest_marks = $highest_marks_allocation['reportcard_highest_marks'];
            $allot_mark_heading->academic_yr = $academicYr;
            $allot_mark_heading->save();
            $status_msg = "Marks heading is allocated successfully.";
        }
        return response()->json([
            'status' => 201,
            'message' => $status_msg,
        ], 201);
    }

    public function deleteAllotMarkheading($allot_markheadings_id)
    {

        $allot_mark_heading = Allot_mark_headings::find($allot_markheadings_id);

        if (!$allot_mark_heading) {
            return response()->json([
                'status' => 404,
                'message' => 'Allot markheading not found',
            ]);
        } else {

            $allot_mark_heading->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Allot markheading data deleted successfully',
                'success' => true
            ]);
        }
    }

    public function editAllotMarkheadings($allot_markheadings_id)
    {
        $allot_mark_heading = Allot_mark_headings::with('getClass', 'getSubject', 'getExam', 'getMarksheading')->where('allot_markheadings_id', $allot_markheadings_id)->get();


        if (!$allot_mark_heading) {
            return response()->json([
                'status' => 404,
                'message' => 'Allot markheading data not found',
            ]);
        }

        return response()->json($allot_mark_heading);
    }

    public function updateAllotMarkheadings(Request $request, $allot_markheadings_id)
    {
        $messages = [
            'highest_marks.required' => 'Highest marks is required.'
        ];

        try {
            $validatedData = $request->validate([
                'highest_marks' => [
                    'required'
                ]
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $allot_mark_heading = Allot_mark_headings::find($allot_markheadings_id);
        if (!$allot_mark_heading) {
            return response()->json(['message' => 'Allot markheading not found', 'success' => false], 404);
        }

        // Update the Marksheading
        $allot_mark_heading->highest_marks = $request->input('highest_marks');
        $allot_mark_heading->reportcard_highest_marks = $request->input('reportcard_highest_marks');
        $allot_mark_heading->save();

        // Return success response
        return response()->json([
            'status' => 200,
            'message' => 'Allot markheading data updated successfully',
        ]);
    }

    public function deleteAllotMarksheadingg(Request $request, $class_id, $subject_id, $exam_id)
    {
        $allotmarkheading = DB::table('student_marks')->where('class_id', $class_id)->where('subject_id', $subject_id)->where('exam_id', $exam_id)->first();
        if ($allotmarkheading) {
            $classname = DB::table('class')->where('class_id', $class_id)->select('name')->first();
            //  dd($classname);
            if ($classname) {
                $className = $classname->name;
            } else {
                $className = 'Unknown Class'; // If class not found, provide a default name
            }
            $examname = DB::table('exam')->where('exam_id', $exam_id)->select('name')->first();
            if ($examname) {
                $examName = $examname->name;
            } else {
                $examName = 'Unknown Exam'; // If class not found, provide a default name
            }

            $subjectname = DB::table('subject_master')->where('sm_id', $subject_id)->select('name')->first();
            if ($subjectname) {
                $subjectName = $subjectname->name;
            } else {
                $subjectName = 'Unknown Subject'; // If class not found, provide a default name
            }

            return response([
                'status' => 400,
                'message' => "This Allot marks heading for class " . $className . " , Exam " . $examName . " and subject " . $subjectName . " is in use. Delete failed!!!",
                'success' => false
            ]);
        } else {
            DB::table('allot_mark_headings')->where('class_id', $class_id)->where('sm_id', $subject_id)->where('exam_id', $exam_id)->delete();
            $classname = DB::table('class')->where('class_id', $class_id)->select('name')->first();
            //  dd($classname);
            if ($classname) {
                $className = $classname->name;
            } else {
                $className = 'Unknown Class'; // If class not found, provide a default name
            }
            $examname = DB::table('exam')->where('exam_id', $exam_id)->select('name')->first();
            if ($examname) {
                $examName = $examname->name;
            } else {
                $examName = 'Unknown Exam'; // If class not found, provide a default name
            }

            $subjectname = DB::table('subject_master')->where('sm_id', $subject_id)->select('name')->first();
            if ($subjectname) {
                $subjectName = $subjectname->name;
            } else {
                $subjectName = 'Unknown Subject'; // If class not found, provide a default name
            }
            return response([
                'status' => 200,
                'message' => "Allot Mark Headings for class " . $className . " , Exam " . $examName . " and subject " . $subjectName . " Deleted Successfully.",
                'success' => true

            ]);
        }
    }

    public function getMarkheadingsForClassSubExam($class_id, $subject_id, $exam_id)
    {
        $allot_mark_heading = Allot_mark_headings::where('class_id', $class_id)->where('sm_id', $subject_id)->where('exam_id', $exam_id)->get(['marks_headings_id', 'highest_marks', 'reportcard_highest_marks']);


        if (!$allot_mark_heading) {
            return response()->json([
                'status' => 404,
                'message' => 'Allot markheading data not found',
            ]);
        }

        return response()->json($allot_mark_heading);
    }

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function getSubjects()
    {
        $subjects = DB::table('subject_master')
            ->select('sm_id', 'name')
            ->get();

        return response()->json($subjects);
    }

    public function saveSubjectMapping(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M') {
                // 3. Validate required inputs
                $validated = $request->validate([
                    'subject_name' => 'required|integer',
                    'report_sub_name' => 'required|integer'
                ]);

                $sm_id = $validated['subject_name'];
                $sub_rc_master_id = $validated['report_sub_name'];

                // 4. Check for duplicate entry
                $exists = DB::table('sub_subreportcard_mapping')
                    ->where('sm_id', $sm_id)
                    ->where('sub_rc_master_id', $sub_rc_master_id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'status' => 409,
                        'message' => 'Mapping already exists!',
                        'success' => false
                    ]);
                }

                // 5. Insert new mapping
                DB::table('sub_subreportcard_mapping')->insert([
                    'sm_id' => $sm_id,
                    'sub_rc_master_id' => $sub_rc_master_id
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Subject mapped successfully!',
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This User Doesnot have Permission for the Deleting of Data',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // public function getSubjectMappingList()
    // {
    //     try {
    //         $user = $this->authenticateUser();
    //         $academicYear = JWTAuth::getPayload()->get('academic_year');

    //         // Allow only specific roles
    //         if (in_array($user->role_id, ['A', 'U', 'M'])) {

    //             // JOIN with subject_master and subjects_on_report_card_master
    //             $subjectmappinglist = DB::table('sub_subreportcard_mapping')
    //                 ->select(
    //                     'sub_subreportcard_mapping.sub_mapping',
    //                     'sub_subreportcard_mapping.sm_id',
    //                     'subject_master.name as sub_name',
    //                     'sub_subreportcard_mapping.sub_rc_master_id',
    //                     'subjects_on_report_card_master.name as report_sub_name',
    //                     'subjects_on_report_card_master.sequence'
    //                 )
    //                 ->join('subject_master', 'sub_subreportcard_mapping.sm_id', '=', 'subject_master.sm_id')
    //                 ->join('subjects_on_report_card_master', 'sub_subreportcard_mapping.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
    //                 ->get();

    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Subject Mapping List.',
    //                 'data' => $subjectmappinglist,
    //                 'success' => true
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'status' => 401,
    //                 'message' => 'This user does not have permission to access this data.',
    //                 'data' => $user->role_id,
    //                 'success' => false
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Error fetching subject mapping list: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'An error occurred: ' . $e->getMessage(),
    //             'success' => false
    //         ], 500);
    //     }
    // }

    public function getSubjectMappingList()
    {
        try {
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            // Allow only specific roles
            if (in_array($user->role_id, ['A', 'U', 'M'])) {

                $subjectmappinglist = DB::table('sub_subreportcard_mapping')
                    ->select(
                        'sub_subreportcard_mapping.sub_mapping',
                        'sub_subreportcard_mapping.sm_id',
                        'subject_master.name as sub_name',
                        'sub_subreportcard_mapping.sub_rc_master_id',
                        'subjects_on_report_card_master.name as report_sub_name',
                        'subjects_on_report_card_master.sequence',
                        DB::raw("CASE 
                                WHEN EXISTS (
                                    SELECT 1 
                                    FROM student_marks 
                                    WHERE student_marks.subject_id = sub_subreportcard_mapping.sub_rc_master_id
                                    LIMIT 1
                                ) 
                                THEN 'N' 
                                ELSE 'Y' 
                             END as isDelete")
                    )
                    ->join('subject_master', 'sub_subreportcard_mapping.sm_id', '=', 'subject_master.sm_id')
                    ->join(
                        'subjects_on_report_card_master',
                        'sub_subreportcard_mapping.sub_rc_master_id',
                        '=',
                        'subjects_on_report_card_master.sub_rc_master_id'
                    )
                    ->get();

                // Attach class names using helper
                foreach ($subjectmappinglist as $subject) {
                    $subject->class_names = getClassNamesBySubject($subject->sm_id);
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Subject Mapping List fetched successfully.',
                    'data' => $subjectmappinglist,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This user does not have permission to access this data.',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching subject mapping list: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }


    // public function updateSubjectMapping(Request $request, $id)
    // {
    //     try {
    //         $user = $this->authenticateUser();
    //         $academicYear = JWTAuth::getPayload()->get('academic_year');

    //         if ($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M') {
    //             $request->validate([
    //                 'subject_name' => 'required|integer', // sm_id
    //                 'report_sub_name' => 'required|integer', // sub_rc_master_id
    //             ]);

    //             // Prepare update data
    //             // $data = [
    //             //     'sm_id' => $request->input('subject_name'),
    //             //     'sub_rc_master_id' => $request->input('report_sub_name'),
    //             // ];

    //             // Check if record exists
    //             $mapping = DB::table('sub_subreportcard_mapping')->where('sub_mapping', $id)->first();

    //             if (!$mapping) {
    //                 return response()->json([
    //                     'status' => 404,
    //                     'message' => 'Mapping record not found.',
    //                     'success' => false,
    //                 ]);
    //             }

    //             // Check for duplicate mapping (excluding current record)
    //             $exists = DB::table('sub_subreportcard_mapping')
    //                 ->where('sm_id', $sm_id)
    //                 ->where('sub_rc_master_id', $sub_rc_master_id)
    //                 ->exists();

    //             if ($exists) {
    //                 return response()->json([
    //                     'status' => 409,
    //                     'message' => 'Mapping already exists!',
    //                     'success' => false,
    //                 ]);
    //             }

    //             // Prepare update data
    //             $data = [
    //                 'sm_id' => $sm_id,
    //                 'sub_rc_master_id' => $sub_rc_master_id,
    //             ];

    //             // Perform the update
    //             DB::table('sub_subreportcard_mapping')
    //                 ->where('sub_mapping', $id)
    //                 ->update($data);

    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'Subject mapping updated successfully.',
    //                 'success' => true,
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'status' => 401,
    //                 'message' => 'This User Doesnot have Permission for the Deleting of Data',
    //                 'data' => $user->role_id,
    //                 'success' => false
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error($e);
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }

    public function updateSubjectMapping(Request $request, $id)
    {
        try {
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M') {
                $request->validate([
                    'subject_name' => 'required|integer', // sm_id
                    'report_sub_name' => 'required|integer', // sub_rc_master_id
                ]);

                $sm_id = $request->input('subject_name');
                $sub_rc_master_id = $request->input('report_sub_name');

                // Check if record exists
                $mapping = DB::table('sub_subreportcard_mapping')->where('sub_mapping', $id)->first();

                if (!$mapping) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Mapping record not found.',
                        'success' => false,
                    ]);
                }

                // Check for duplicate mapping (excluding current record)
                $exists = DB::table('sub_subreportcard_mapping')
                    ->where('sm_id', $sm_id)
                    ->where('sub_rc_master_id', $sub_rc_master_id)
                    ->where('sub_mapping', '!=', $id) // Exclude current record
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'status' => 409,
                        'message' => 'Mapping already exists!',
                        'success' => false,
                    ]);
                }

                // Prepare update data
                $data = [
                    'sm_id' => $sm_id,
                    'sub_rc_master_id' => $sub_rc_master_id,
                ];

                // Perform the update
                DB::table('sub_subreportcard_mapping')
                    ->where('sub_mapping', $id)
                    ->update($data);

                return response()->json([
                    'status' => 200,
                    'message' => 'Subject mapping updated successfully.',
                    'success' => true,
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This user does not have permission to update this data.',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function deleteSubjectMapping($id)
    {
        try {
            $user = $this->authenticateUser();

            if ($user->role_id === 'A' || $user->role_id === 'U') {
                $mapping = DB::table('sub_subreportcard_mapping')->where('sub_mapping', $id)->first();

                if ($mapping) {
                    DB::table('sub_subreportcard_mapping')->where('sub_mapping', $id)->delete();

                    return response()->json([
                        'status' => 200,
                        'message' => 'Subject mapping deleted successfully!',
                        'success' => true
                    ]);
                } else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Subject mapping not found!',
                        'success' => false
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized: You do not have permission to delete subject mappings.',
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while deleting the subject mapping.',
                'error' => $e->getMessage(),
                'success' => false
            ]);
        }
    }



    public function getClassNamesBySubject(Request $request, $sm_id)
    {
        try {
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M') {
                if (!$academicYear) {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'Academic year not found.',
                    ], 400);
                }
                // Get class names for the given subject and academic year
                $classNames = DB::table('class')
                    ->whereIn('class_id', function ($query) use ($sm_id, $academicYear) {
                        $query->select(DB::raw('DISTINCT class_id'))
                            ->from('subject')
                            ->where('sm_id', $sm_id)
                            ->where('academic_yr', $academicYear);
                    })
                    ->pluck('name')
                    ->toArray();

                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Class names fetched successfully.',
                    'data' => $classNames,
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This User Doesnot have Permission for the Deleting of Data',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // Book Requistion
    // Book Requistion
    public function createBookRequisition(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method to get logged-in user
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional, if needed

            // 3. Validate request data
            $validated = $request->validate([
                'title'     => 'required|string',
                'author'    => 'nullable|string',
                'publisher' => 'nullable|string',
            ]);
            $library_member = DB::table('library_member')->where('member_id', $user->reg_id)->first();
            if (!$library_member) {
                return response()->json([
                    'status' => 400,
                    'message' => 'This user is not a library member!',
                    'success' => false
                ]);
            }

            // 4. Prepare data
            $data = [
                'title'       => $validated['title'],
                'author'      => $validated['author'] ?? null,
                'publisher'   => $validated['publisher'] ?? null,
                'status'      => 'A',
                'req_date'    => now()->toDateString(),
                'member_type' => $user->role_id == 'S' ? 'S' : 'T',
                'member_id'   => $user->reg_id,
            ];

            // 5. Save data
            DB::table('book_req')->insert($data);

            // 6. Return success response
            return response()->json([
                'status' => 200,
                'message' => 'Book Requisition created successfully!',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getBookRequisitionInfo($book_req_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method to get logged-in user
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Check role permission

            // 3. Fetch requisition
            $book = DB::table('book_req')
                ->where('book_req_id', $book_req_id)
                ->first();

            // 4. Check if record exists
            if ($book) {
                // 5. Optional: Only allow access to own request for students/teachers
                if (($user->role_id == 'S' || $user->role_id == 'T') && $book->member_id != $user->reg_id) {
                    return response()->json([
                        'status' => 403,
                        'message' => 'You are not authorized to view this requisition.',
                        'success' => false
                    ]);
                }

                // 6. Return requisition data
                return response()->json([
                    'status' => 200,
                    'data' => $book,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'Book Requisition not found',
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getAllBookRequisitions(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');




            $query = DB::table('book_req');

            //  only S and T in existing
            if ($user->role_id == 'S' || $user->role_id == 'T' || $user->role_id == 'A' || $user->role_id == 'U' || $user->role_id == 'M') {
                $query->where('member_id', $user->reg_id);
            }

            $bookRequisitions = $query->orderByDesc('book_req_id')->get();

            // 4. Check if any records found
            if ($bookRequisitions->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No book requisitions found.',
                    'success' => false
                ]);
            }

            // 5. Return data
            return response()->json([
                'status' => 200,
                'data' => $bookRequisitions,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getBookRequisition($reg_id, $member_type)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // optional

            // 2. Build query using both params
            $query = DB::table('book_req')
                ->where('member_id', $reg_id)
                ->where('member_type', $member_type);

            // 3. Execute query
            $books = $query->orderByDesc('book_req_id')->get();

            // 4. If empty, return not found
            if ($books->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No book requisitions found for this reg_id and member_type.',
                    'success' => false
                ]);
            }

            // 5. Return data
            return response()->json([
                'status' => 200,
                'data' => $books,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }


    public function updateBookRequisition(Request $request, $book_req_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional if needed

            // 3. Validate request
            $validated = $request->validate([
                'title'     => 'required|string',
                'author'    => 'nullable|string',
                'publisher' => 'nullable|string',
            ]);

            // 4. Fetch existing record
            $existing = DB::table('book_req')->where('book_req_id', $book_req_id)->first();

            if (!$existing) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Book requisition not found.',
                    'success' => false
                ]);
            }


            // 6. Prepare update data
            $data = [
                'title'       => $validated['title'],
                'author'      => $validated['author'] ?? null,
                'publisher'   => $validated['publisher'] ?? null,
                'status'      => 'A',
                'req_date'    => now()->toDateString(),
                'member_type' => $user->role_id == 'S' ? 'S' : 'T',
                'member_id'   => $user->reg_id,
            ];

            // 7. Update database
            DB::table('book_req')->where('book_req_id', $book_req_id)->update($data);

            // 8. Return success response
            return response()->json([
                'status' => 200,
                'message' => 'Book requisition updated successfully.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function deleteBookRequisition($book_req_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional


            // 3. Check if the book requisition exists
            $book = DB::table('book_req')->where('book_req_id', $book_req_id)->first();

            if (!$book) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Book requisition not found.',
                    'success' => false
                ]);
            }

            // 4. Prevent deletion if requisition is not owned (for Students/Teachers)
            if (in_array($user->role_id, ['S', 'T']) && $book->member_id != $user->reg_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to delete this requisition.',
                    'success' => false
                ]);
            }

            // 5. Check if purchase request exists (mimicking CI logic)
            $purchaseReq = DB::table('book_pur_req')
                ->where('book_req_id', $book_req_id)
                ->exists();

            if ($purchaseReq) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Purchase requisition exists for this book. Delete failed.',
                    'success' => false
                ]);
            }

            // 6. Perform deletion
            DB::table('book_req')->where('book_req_id', $book_req_id)->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Book requisition deleted successfully.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function createImportantLink(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Check role permission
            if (in_array($user->role_id, ['A', 'U', 'M', 'P'])) {

                // 3. Validate request data
                $validated = $request->validate([
                    'title'       => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'url'         => 'required|url',
                    'type_link'   => 'required|string|max:50',
                ]);

                // 4. Prepare data
                $data = [
                    'title'       => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'create_date' => now()->toDateString(),
                    'url'         => $validated['url'],
                    'type_link'   => $validated['type_link'],
                    'publish'     => 'N',
                    'IsDelete'    => 'N',
                    'posted_by'   => $user->reg_id,
                ];

                // 5. Insert into DB
                DB::table('important_links')->insert($data);

                // 6. Return success response
                return response()->json([
                    'status' => 200,
                    'message' => 'Important Link created successfully!',
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'You do not have permission to create important links.',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }


    public function getImportantLinks(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method for auth

            // 2. Check role permission (optional: adjust as per access policy)
            if (in_array($user->role_id, ['A', 'U', 'M', 'P'])) {

                // 3. Fetch data ordered by create_date
                $links = DB::table('important_links')
                    ->orderBy('create_date', 'DESC')
                    ->get();

                // 4. Return success response
                return response()->json([
                    'status' => 200,
                    'message' => 'Important links fetched successfully!',
                    'data' => $links,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'You do not have permission to access important links.',
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getImportantLinkById(Request $request, $link_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom auth method

            // 2. Check role permission
            if (in_array($user->role_id, ['A', 'U', 'M', 'P'])) {

                // 3. Get the link info
                $link = DB::table('important_links')
                    ->where('link_id', $link_id)
                    ->first();

                // 4. Check if found
                if ($link) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Important link fetched successfully!',
                        'data' => $link,
                        'success' => true
                    ]);
                } else {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Important link not found.',
                        'success' => false
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'You do not have permission to access this data.',
                    'success' => false
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }


    public function updateImportantLink(Request $request, $link_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Role-based permission check
            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'You do not have permission to update important links.',
                    'success' => false
                ]);
            }

            // 3. Validate request
            $validated = $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'url'         => 'required|url',
                'type_link'   => 'required|string|max:50'
            ]);

            // 4. Check if the record exists
            $existing = DB::table('important_links')->where('link_id', $link_id)->first();

            if (!$existing) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Important Link not found.',
                    'success' => false
                ]);
            }

            // 5. Prepare update data
            $data = [
                'title'       => $validated['title'],
                'description' => $validated['description'] ?? null,
                'url'         => $validated['url'],
                'type_link'   => $validated['type_link'],
                'create_date' => now()->toDateString(), // Same as original logic
                'posted_by'   => $user->reg_id
            ];

            // 6. Update DB
            DB::table('important_links')
                ->where('link_id', $link_id)
                ->update($data);

            // 7. Return success
            return response()->json([
                'status' => 200,
                'message' => 'Important Link updated successfully!',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function deleteImportantLink(Request $request, $link_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Check permissions
            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to delete important links.',
                    'success' => false
                ]);
            }

            // 3. Get the link
            $link = DB::table('important_links')->where('link_id', $link_id)->first();

            if (!$link) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Important link not found.',
                    'success' => false
                ]);
            }

            // 4. Delete logic based on 'publish' value
            if ($link->publish === 'N') {
                // Hard delete
                DB::table('important_links')->where('link_id', $link_id)->delete();

                return response()->json([
                    'status' => 200,
                    'message' => 'Important Link permanently deleted.',
                    'success' => true
                ]);
            } else {
                // Soft delete
                DB::table('important_links')
                    ->where('link_id', $link_id)
                    ->update(['IsDelete' => 'Y']);

                return response()->json([
                    'status' => 200,
                    'message' => 'Important Link soft-deleted (marked as deleted).',
                    'success' => true
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function publishImportantLink(Request $request, $link_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Your custom auth method
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Permission check
            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to publish links.',
                    'success' => false
                ]);
            }

            // 3. Check if link exists
            $link = DB::table('important_links')->where('link_id', $link_id)->first();

            if (!$link) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Important link not found.',
                    'success' => false
                ]);
            }

            // 4. Update publish status to 'Y'
            DB::table('important_links')
                ->where('link_id', $link_id)
                ->update(['publish' => 'Y']);

            // 5. Return success response
            return response()->json([
                'status' => 200,
                'message' => 'Important Link published successfully!',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    //  News
    public function createNews(Request $request)
    {
        try {
            $user = $this->authenticateUser();

            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'You do not have permission to create news.',
                    'success' => false,
                ]);
            }



            // 4. Prepare data
            $data = [
                'title'        => $request->input('title'),
                'description'  => $request->input('description'),
                'date_posted'  => now()->toDateString(),
                'active_till_date' => $request->input('active_till_date'),
                'url'          => $request->input('url'),
                'publish'      => 'N',
                'isDelete'     => 'N',
                'posted_by'    => $user->reg_id,
                'image_name'   => null,
            ];

            // 5. Insert news and get ID
            $newsId = DB::table('news')->insertGetId($data);

            // 6. Handle image upload (if any)
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = $image->getClientOriginalName();
                $folder = 'news/'  . $newsId;
                $path = $image->storeAs($folder, $filename, 'public');

                $publicUrl = Storage::url($path);
                $docTypeFolder = 'news';
                $uploadDate = '2025-08-12';
                $datafiles[] = base64_encode(file_get_contents($image->getRealPath()));
                $filenames[] = $image->getClientOriginalName();
                $response = upload_files_for_laravel($filenames, $datafiles, $uploadDate, $docTypeFolder, $newsId);


                // Update DB with image name
                DB::table('news')->where('news_id', $newsId)->update([
                    'image_name' => $filename
                ]);
            }

            // 7. Return success response
            return response()->json([
                'status' => 200,
                'message' => 'News created successfully.',
                'news_id' => $newsId,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false,
            ]);
        }
    }

    public function getAllNews(Request $request)
    {
        try {
            $user = $this->authenticateUser();

            $todayDate = now()->toDateString();
            $globalVariables = App::make('global_variables');
            $parent_app_url = $globalVariables['parent_app_url'];
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            $news = DB::table('news')
                ->orderBy('date_posted', 'DESC')
                ->get()
                ->map(function ($item) use ($codeigniter_app_url) {
                    $concatprojecturl = $codeigniter_app_url . 'uploads/news/' . $item->news_id . '/';
                    $item->image_name = $item->image_name
                        ? $concatprojecturl . $item->image_name
                        : null;
                    return $item;
                });

            // 5. Return the result
            return response()->json([
                'status' => 200,
                'message' => 'News fetched successfully.',
                'data' => $news,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getNewsById(Request $request, $id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();

            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized access.',
                    'success' => false
                ]);
            }

            $news = DB::table('news')
                ->where('news_id', $id)
                ->where('IsDelete', '!=', 'Y')
                ->first();

            // 4. Check if found
            if (!$news) {
                return response()->json([
                    'status' => 404,
                    'message' => 'News not found.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'News fetched successfully.',
                'data' => $news,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function updateNews(Request $request, $id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();

            // 2. Role check
            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'You do not have permission to edit news.',
                    'success' => false
                ]);
            }



            // 4. Check if news exists
            $news = DB::table('news')->where('news_id', $id)->first();
            $filenottobedeleted = $request->input('filenottobedeleted');
            DB::table('news')->where('news_id', $id)->update([
                'image_name' => $filenottobedeleted
            ]);
            if (!$news) {
                return response()->json([
                    'status' => 404,
                    'message' => 'News not found.',
                    'success' => false
                ]);
            }

            // 5. Prepare update data
            $updateData = [
                'title'            => $request->input('title'),
                'description'      => $request->input('description'),
                'active_till_date' => $request->input('active_till_date'),
                'url'              => $request->input('url'),
            ];

            // 6. Handle image upload (if new one provided)
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = $image->getClientOriginalName();
                $folder = 'news/'  . $id;
                $path = $image->storeAs($folder, $filename, 'public');

                $publicUrl = Storage::url($path);
                $docTypeFolder = 'news';
                $uploadDate = '2025-08-12';
                $datafiles[] = base64_encode(file_get_contents($image->getRealPath()));
                $filenames[] = $image->getClientOriginalName();
                $response = upload_files_for_laravel($filenames, $datafiles, $uploadDate, $docTypeFolder, $id);

                $updateData['image_name'] = $filename;
            }

            // 7. Update record
            DB::table('news')->where('news_id', $id)->update($updateData);

            return response()->json([
                'status' => 200,
                'message' => 'News updated successfully.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function deleteNews(Request $request, $id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();

            // 2. Check permission
            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to delete news.',
                    'success' => false
                ]);
            }

            // 3. Find news by ID
            $news = DB::table('news')->where('news_id', $id)->first();

            if (!$news) {
                return response()->json([
                    'status' => 404,
                    'message' => 'News not found.',
                    'success' => false
                ]);
            }

            // 4. Delete logic
            if ($news->publish === 'N') {
                // Hard delete
                DB::table('news')->where('news_id', $id)->delete();
                $msg = 'News hard deleted.';
            } else {
                // Soft delete
                DB::table('news')->where('news_id', $id)->update(['isDelete' => 'Y']);
                $msg = 'News soft deleted (marked IsDelete = Y).';
            }

            return response()->json([
                'status' => 200,
                'message' => $msg,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function publishNews(Request $request, $id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();

            // 2. Check permission
            if (!in_array($user->role_id, ['A', 'U', 'M', 'P'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to publish news.',
                    'success' => false
                ]);
            }

            // 3. Fetch news
            $news = DB::table('news')->where('news_id', $id)->first();

            if (!$news) {
                return response()->json([
                    'status' => 404,
                    'message' => 'News not found.',
                    'success' => false
                ]);
            }

            // 4. Publish the news
            DB::table('news')->where('news_id', $id)->update([
                'publish' => 'Y'
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'News published successfully.',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    // Approve Stationery

    public function getStationeryApprove()
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            // Allowed roles
            if (!in_array($user->role_id, ['A', 'T', 'M', 'P', 'U'])) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'This user does not have permission to view stationery approvals.',
                    'data'    => $user->role_id,
                    'success' => false
                ]);
            }

            $status = ['A', 'H'];

            $stationeryApprove = DB::table('stationery_req as sr')
                ->leftJoin('teacher as t', 'sr.staff_id', '=', 't.teacher_id')
                ->leftJoin('stationery_master as sm', 'sr.stationery_id', '=', 'sm.stationery_id')
                ->whereIn('sr.status', $status)
                ->select(
                    'sr.*',
                    DB::raw('COALESCE(t.name, "") as teacher_name'),
                    DB::raw('COALESCE(sm.name, "") as stationery_name')
                )
                ->get();


            return response()->json([
                'status'  => 200,
                'message' => 'Approved / On Hold Stationery Requests.',
                'data'    => $stationeryApprove,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status'  => 500,
                'error'   => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function saveOrUpdateStationeryApprove(Request $request, $requisition_id = null)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M') {

                // Validation
                $request->validate([
                    'status' => 'required|string',
                    'comments' => 'nullable|string',
                    'approved_by' => 'nullable|string'
                ]);

                $data = [
                    'status'        => $request->status,
                    'comments'      => $request->comments,
                    'approved_by'   => $request->approved_by,
                    'approved_date' => now()->format('Y-m-d')
                ];

                if ($requisition_id) {
                    // EDIT existing record
                    $result = DB::table('stationery_req')
                        ->where('requisition_id', $requisition_id)
                        ->update($data);

                    $message = 'Stationery Requisition Status Updated Successfully.';
                } else {
                    // CREATE new record
                    $result = DB::table('stationery_req')->insert($data);

                    $message = 'Stationery Requisition Saved Successfully.';
                }

                return response()->json([
                    'status'  => 200,
                    'message' => $message,
                    'data'    => $result,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status'  => 401,
                    'message' => 'This User Does not have Permission to Save or Update Stationery Approval.',
                    'data'    => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // View Book Availability

    public function getBooksOnCopyId(Request $request, $copy_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();

            // 3. Fetch book details with joins
            $query = DB::table('book as a')
                ->select('a.*', 'b.*', 'c.*')
                ->join('book_copies as b', 'a.book_id', '=', 'b.book_id')
                ->join('category as c', 'c.category_id', '=', 'a.category_id');
            // ->where('b.status', 'A'); // uncomment if needed

            if (!empty($copy_id)) {
                $query->where('b.copy_id', $copy_id);
            }

            $books = $query->get();

            // 4. Check if found
            if ($books->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No books found for the given copy ID.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Books fetched successfully.',
                'data' => $books,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getCategoryName(Request $request, $category_id)
    {
        try {
            $user = $this->authenticateUser();

            $category = DB::table('category as c')
                ->select('c.call_no', 'c.category_name')
                ->where('c.category_id', $category_id)
                ->first();

            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Category fetched successfully.',
                'data' => $category->call_no . ' / ' . $category->category_name,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getCategoryGroupName()
    {
        try {
            $user = $this->authenticateUser();

            $groups = DB::table('category_group')
                ->select('category_group_id as value', 'category_group_name as label')
                ->get();

            if ($groups->isEmpty()) {
                return response()->json([]);
            }

            return response()->json($groups);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }


    public function getCategory()
    {
        try {
            // Authenticate user if needed
            $user = $this->authenticateUser(); // optional, if you’re using auth

            $categories = DB::table('category')
                ->select('category_id as value', 'category_name as label')
                ->get();

            if ($categories->isEmpty()) {
                return response()->json([]);
            }

            return response()->json($categories);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getAllCategoryName(Request $request)
    {
        try {
            $user = $this->authenticateUser();

            // $categoryid = DB::select("SELECT a.* FROM category AS a JOIN category_categorygroup on a.category_id = category_categorygroup.category_id WHERE category_categorygroup.category_group_id = '" . $categoryGroupId . "'");
            // dd($categoryid);
            $categoryGroupId = $request->input('category_group_id');
            // Always fetch all categories with their group info
            $categories = DB::table('category as c')
                ->leftJoin('category_categorygroup as ccg', 'c.category_id', '=', 'ccg.category_id')
                ->select(
                    'c.category_id',
                    'c.call_no',
                    'c.category_name',
                    'ccg.category_group_id'
                )
                ->orderBy('c.call_no', 'asc');

            // 👉 Add in_group flag AFTER fetching
            if ($categoryGroupId) {
                $categories->where('ccg.category_group_id', $categoryGroupId);
            }

            // Execute query
            $categories = $categories->get();

            if ($categories->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No categories found.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Categories fetched successfully.',
                'data' => $categories,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function searchBooks(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $status = $request->input('status');
            $category_group_id = $request->input('category_group_id');
            $category_id = $request->input('category_id');
            $author = $request->input('author');
            $title = $request->input('title');
            $isNew = $request->input('is_new');
            $accession_no = $request->input('accession_no');

            $query = DB::table('book')
                ->join('book_copies', 'book.book_id', '=', 'book_copies.book_id')
                ->join('category', 'category.category_id', '=', 'book.category_id')
                ->select(
                    'book.*',
                    'book_copies.*',
                    'category.category_name',
                    'category.call_no'
                );

            if (!empty($status)) {
                $query->where('book_copies.status', $status);
            }

            if (!empty($category_group_id)) {
                $query->join('category_categorygroup as b', 'category.category_id', '=', 'b.category_id')
                    ->where('b.category_group_id', $category_group_id);
            }

            if (!empty($category_id)) {
                $query->where('book.category_id', $category_id);
            }

            if (!empty($author)) {
                $query->where('book.author', 'like', '%' . $author . '%');
            }

            if (!empty($title)) {
                $query->where('book.book_title', 'like', '%' . $title . '%');
            }

            if (!empty($isNew) && $isNew == true) {
                $query->whereRaw('DATEDIFF(NOW(), book_copies.added_date) <= 60');
            }


            if (!empty($accession_no)) {
                $query->where('book_copies.copy_id', $accession_no);
            }

            $books = $query->get();

            if ($books->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No books found.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Books fetched successfully.',
                'data' => $books,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    // Methods for  Subject Master  API 
    public function getHPCSubjects(Request $request)
    {
        $subjects = DB::table('HPC_subject_master')->get();
        return response()->json([
            'status' => 200,
            'message' => 'HPC subjects',
            'data' => $subjects,
            'success' => true
        ]);
    }

    public function checkHPCSubjectName(Request $request)
    {

        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:30',
            'subject_type' => 'required|string|max:30',
        ]);

        $name = $validatedData['name'];
        $subjectType = $validatedData['subject_type'];

        // Check if the combination of name and subject_type exists
        $exists = DB::table('HPC_subject_master')->whereRaw('LOWER(name) = ? AND LOWER(subject_type) = ?', [strtolower($name), strtolower($subjectType)])->exists();

        return response()->json(['exists' => $exists]);
    }


    public function storeHPCSubject(Request $request)
    {
        $messages = [
            'name.required' => 'The name field is required.',
            // 'name.unique' => 'The name has already been taken.',
            'subject_type.required' => 'The subject type field is required.',
            'subject_type.unique' => 'The subject type has already been taken.',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    // Rule::unique('subject_master', 'name')
                ],
                'subject_type' => [
                    'required',
                    'string',
                    'max:255'
                ],
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        DB::table('HPC_subject_master')->insert([
            'name' => $validatedData['name'],
            'subject_type' => $validatedData['subject_type']
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Subject created successfully',
            'success' => true
        ], 201);
    }

    public function updateHPCSubject(Request $request, $id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $subjectType = $request->subject_type;
        // dd($subjectType);
        $messages = [
            'name.required' => 'The name field is required.',
            // 'name.unique' => 'The name has already been taken.',
            'subject_type.required' => 'The subject type field is required.',
            // 'subject_type.unique' => 'The subject type has already been taken.',
        ];

        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('HPC_subject_master')
                        ->ignore($id, 'hpc_sm_id')
                        ->where(function ($query) use ($subjectType) {
                            $query->where('subject_type', $subjectType);
                        })
                ],
                'subject_type' => [
                    'required',
                    'string',
                    'max:255'
                ],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        $subject = DB::table('HPC_subject_master')->where('hpc_sm_id', $id)->first();

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ], 404);
        }

        // Update the record
        DB::table('HPC_subject_master')
            ->where('hpc_sm_id', $id)
            ->update([
                'name' => $validatedData['name'],
                'subject_type' => $validatedData['subject_type']
            ]);

        return response()->json([
            'status' => 200,
            'message' => 'Subject updated successfully',
            'success' => true
        ]);
    }



    public function editHPCSubject($id)
    {
        $subject = DB::table('HPC_subject_master')->where('hpc_sm_id', $id)->first();

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $subject,
            'message' => 'Subject retrieved successfully',
            'success' => true
        ]);
    }

    public function deleteHPCSubject($id)
    {
        $subject = DB::table('HPC_subject_master')->where('hpc_sm_id', $id)->first();

        if (!$subject) {
            return response()->json([
                'status' => 404,
                'message' => 'Subject not found',
            ]);
        }
        $subjectAllotmentExists = DB::table('HPC_subject')->where('hpc_sm_id', $id)->exists();
        if ($subjectAllotmentExists) {
            return response()->json([
                'status' => 400,
                'message' => 'Subject cannot be deleted because it is associated with other records.',
            ]);
        }

        $domainmasterexists = DB::table('domain_master')->where('HPC_sm_id', $id)->exists();
        if ($domainmasterexists) {
            return response()->json([
                'status' => 400,
                'message' => 'Subject cannot be deleted because it is associated with other records.',
                'success' => false
            ]);
        }
        DB::table('HPC_subject_master')->where('hpc_sm_id', $id)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Subject deleted successfully',
            'success' => true
        ]);
    }

    // Method for Subject Allotment for the report Card 

    public function getHPCSubjectAllotmentForReportCard(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $subjectAllotments = DB::table('HPC_subject')
            ->leftJoin('HPC_subject_master as s', 'HPC_subject.hpc_sm_id', '=', 's.hpc_sm_id')
            ->leftJoin('class as c', 'HPC_subject.class_id', '=', 'c.class_id')
            ->where('HPC_subject.academic_yr', $academicYr)
            ->where('HPC_subject.class_id', $class_id)
            ->select(
                'HPC_subject.*',
                's.name as subject_name',
                's.subject_type',
                'c.name as classname'
            )
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'HPC Subject allotment by class.',
            'subjectAllotments' => $subjectAllotments,
            'success' => true
        ]);
    }


    // for delete
    public function deleteHPCSubjectAllotmentforReportcard($sub_reportcard_id)
    {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $subjectAllotment = DB::table('HPC_subject')
            ->where('hpc_subject_id', $sub_reportcard_id)
            ->first();
        if (!$subjectAllotment) {
            return response()->json(['error' => 'Subject Allotment not found'], 404);
        }

        $exists = DB::table('domain_master')
            ->where('HPC_sm_id', $subjectAllotment->hpc_sm_id)
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => 400,
                'message' => 'Subject allotment cannot be deleted because it is associated with other records.',
                'success' => false
            ]);
        }

        DB::table('HPC_subject')
            ->where('hpc_subject_id', $sub_reportcard_id)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Subject allotment deleted successfully',
            'success' => true
        ]);
    }

    public function createOrUpdateHPCSubjectAllotment(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year'); // Extract academic year

        // Validate request input
        $request->validate([
            'subject_ids'   => 'array',
            'subject_ids.*' => 'integer',
        ]);

        // Get subject IDs safely
        $inputSubjectIds = $request->input('subject_ids', []);

        Log::info('Received request to create/update subject allotment', [
            'class_id'     => $class_id,
            'subject_ids'  => $inputSubjectIds,
            'academic_yr'  => $academicYr,
        ]);

        // Fetch existing allotments
        $existingAllotments = DB::table('HPC_subject')
            ->where('class_id', $class_id)
            ->where('academic_yr', $academicYr)
            ->pluck('hpc_sm_id')
            ->toArray();

        Log::info('Fetched existing subject allotments', [
            'existingAllotments' => $existingAllotments
        ]);

        // Determine differences
        $newSubjectIds      = array_diff($inputSubjectIds, $existingAllotments);
        $deallocateSubjectIds = array_diff($existingAllotments, $inputSubjectIds);
        $updateSubjectIds   = array_intersect($inputSubjectIds, $existingAllotments);

        Log::info('Comparison results', [
            'newSubjectIds'       => $newSubjectIds,
            'updateSubjectIds'    => $updateSubjectIds,
            'deallocateSubjectIds' => $deallocateSubjectIds
        ]);

        // 1. Bulk Insert New Allotments
        if (!empty($newSubjectIds)) {
            $insertData = [];
            foreach ($newSubjectIds as $subjectId) {
                $insertData[] = [
                    'class_id'    => $class_id,
                    'hpc_sm_id'   => $subjectId,
                    'academic_yr' => $academicYr,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            DB::table('HPC_subject')->insert($insertData);

            Log::info('Created new subject allotments', [
                'inserted_subjects' => $newSubjectIds
            ]);
        }

        // 2. Update Existing Allotments (only timestamps or extra fields if needed)
        if (!empty($updateSubjectIds)) {
            $updatedRows = DB::table('HPC_subject')
                ->where('class_id', $class_id)
                ->where('academic_yr', $academicYr)
                ->whereIn('hpc_sm_id', $updateSubjectIds)
                ->update([
                    'updated_at' => now(),
                ]);

            Log::info('Updated existing subject allotments', [
                'updated_subjects' => $updateSubjectIds,
                'affected_rows'    => $updatedRows
            ]);
        }

        // 3. Bulk Deallocate Subjects
        if (!empty($deallocateSubjectIds)) {
            $deletedRows = DB::table('HPC_subject')
                ->where('class_id', $class_id)
                ->where('academic_yr', $academicYr)
                ->whereIn('hpc_sm_id', $deallocateSubjectIds)
                ->delete();

            Log::info('Deallocated subject allotments', [
                'deleted_subjects' => $deallocateSubjectIds,
                'affected_rows'    => $deletedRows
            ]);
        }

        Log::info('Subject allotments successfully processed', [
            'class_id'    => $class_id,
            'academic_yr' => $academicYr
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Subject allotments updated successfully',
            'success' => true
        ]);
    }

    public function editHPCSubjectAllotmentforReportCard(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        // Fetch the list of subjects for the selected class_id and subject_type
        $subjectAllotments = DB::table('HPC_subject as h')
            ->join('HPC_subject_master as s', 'h.hpc_sm_id', '=', 's.hpc_sm_id')
            ->where('h.academic_yr', $academicYr)
            ->where('h.class_id', $class_id)
            ->select('h.*', 's.name as subject_name', 's.subject_type')
            ->get();

        // Check if subject allotments are found
        if ($subjectAllotments->isEmpty()) {
            return response()->json([]);
        }

        return response()->json([
            'message' => 'Subject allotments retrieved successfully',
            'subjectAllotments' => $subjectAllotments,
        ]);
    }

    public function saveDomainCompetencies(Request $request)
    {
        $competenciesname = $request->name;
        DB::table('domain_competencies')->insert([
            'name' => $competenciesname
        ]);
        return response()->json([
            'status'  => 200,
            'message' => 'Domain competency created successfully.',
            'success' => true
        ]);
    }

    public function getDomainCompetencies(Request $request)
    {
        $competencies = DB::table('domain_competencies')->get();

        return response()->json([
            'status'  => 200,
            'data' => $competencies,
            'message' => 'Domain competency list.',
            'success' => true
        ]);
    }

    public function updateDomainCompetencies(Request $request, $dm_competency_id)
    {
        $competenciesname = $request->name;
        DB::table('domain_competencies')
            ->where('dm_competency_id', $dm_competency_id)
            ->update(['name' => $competenciesname]);

        return response()->json([
            'status' => 200,
            'message' => 'Domain competency updated successfully.',
            'success' => true
        ]);
    }

    public function deleteDomainCompetencies(Request $request, $dm_competency_id)
    {
        $exists = DB::table('domain_parameter_details')
            ->where('dm_competency_id', $dm_competency_id)
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => 409,
                'message' => 'Cannot delete.Domain competency is in use.',
                'success' => false
            ]);
        }
        DB::table('domain_competencies')
            ->where('dm_competency_id', $dm_competency_id)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Domain competency deleted successfully.',
            'success' => true
        ]);
    }

    public function saveDomainParameters(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $class_id = $request->input('class_id');
        $hpc_sm_id = $request->input('hpc_sm_id');
        $name = $request->input('name');
        $curriculum_goal = $request->input('curriculum_goal');

        $dm_id = DB::table('domain_master')->insertGetId([
            'academic_yr'      => $academicYr,
            'class_id'         => $class_id,
            'HPC_sm_id'        => $hpc_sm_id,
            'name'             => $name,
            'curriculum_goal'  => $curriculum_goal,
        ]);

        $parameters = $request->input('parameters');

        $insertData = [];
        foreach ($parameters as $param) {
            $insertData[] = [
                'dm_id'             => $dm_id,
                'dm_competency_id'      => $param['competencies'],
                'learning_outcomes' => $param['learning_outcomes'],
                'academic_yr'       => $academicYr
            ];
        }

        DB::table('domain_parameter_details')->insert($insertData);

        return response()->json([
            'status' => 200,
            'message' => 'Domain master and parameters saved successfully.',
            'success' => true
        ]);
    }

    public function getDomainParameters(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $domains = DB::table('domain_master as dm')
            ->join('class', 'dm.class_id', '=', 'class.class_id')
            ->leftjoin('HPC_subject_master', 'HPC_subject_master.hpc_sm_id', '=', 'dm.HPC_sm_id')
            ->where('dm.academic_yr', $academicYr)
            ->select('class.name as classname', 'dm.name as domainname', 'HPC_subject_master.name as subjectname', 'dm.dm_id')
            ->get();

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $domains
        ]);
    }

    public function editDomainParameters(Request $request, $dm_id)
    {
        // dd($dm_id);
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $domain = DB::table('domain_master')
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->first();

        if (!$domain) {
            return response()->json([
                'status' => 404,
                'message' => 'Domain not found.',
                'success' => false
            ]);
        }

        $parameters = DB::table('domain_parameter_details')
            ->leftjoin('domain_competencies', 'domain_competencies.dm_competency_id', '=', 'domain_parameter_details.dm_competency_id')
            ->where('domain_parameter_details.dm_id', $dm_id)
            ->where('domain_parameter_details.academic_yr', $academicYr)
            ->get();

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Domain parameters list.',
            'data' => [
                'domain' => $domain,
                'parameters' => $parameters
            ]
        ]);
    }

    public function updateDomainParameters(Request $request, $dm_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        // Update main domain record
        DB::table('domain_master')
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->update([
                'class_id'        => $request->input('class_id'),
                'HPC_sm_id'       => $request->input('hpc_sm_id'),
                'name'            => $request->input('name'),
                'curriculum_goal' => $request->input('curriculum_goal'),
            ]);

        // Delete old parameters
        DB::table('domain_parameter_details')
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->delete();

        // Insert new parameters
        $insertData = [];
        foreach ($request->input('parameters', []) as $param) {
            $insertData[] = [
                'dm_id'             => $dm_id,
                'dm_competency_id'      => $param['competencies'],
                'learning_outcomes' => $param['learning_outcomes'],
                'academic_yr'       => $academicYr
            ];
        }

        if (!empty($insertData)) {
            DB::table('domain_parameter_details')->insert($insertData);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Domain and parameters updated successfully.',
            'success' => true
        ]);
    }

    public function deleteDomainParameters(Request $request, $dm_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $studentdomaindetails = DB::table('student_domain_details')->where('dm_id', $dm_id)->exists();
        if ($studentdomaindetails) {
            return response()->json([
                'status' => 409,
                'message' => 'This domain is in use. Delete failed!!!',
                'success' => false
            ]);
        }
        DB::table('domain_parameter_details')
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->delete();


        DB::table('domain_master')
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Domain and its parameters deleted successfully.',
            'success' => true
        ]);
    }

    public function getStudentParameterValue(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $dm_id = $request->input('dm_id');
        $term_id = $request->input('term_id');
        $subject_id = $request->input('subject_id');


        // Fetch students (same as get_students)
        $students = DB::table('student as a')
            ->leftJoin('parent as b', 'a.parent_id', '=', 'b.parent_id')
            ->join('user_master as c', 'a.parent_id', '=', 'c.reg_id')
            ->join('class as d', 'a.class_id', '=', 'd.class_id')
            ->join('section as e', 'a.section_id', '=', 'e.section_id')
            ->leftJoin('house as f', 'f.house_id', '=', 'a.house')
            ->where('a.IsDelete', 'N')
            ->where('a.academic_yr', $academicYr)
            ->where('a.class_id', $class_id)
            ->where('a.section_id', $section_id)
            ->where('c.role_id', 'P')
            ->orderBy('a.roll_no')
            ->orderBy('a.reg_no')
            ->select('a.student_id', 'a.roll_no', 'a.first_name', 'a.last_name', 'b.*', 'c.user_id', 'd.name as class_name', 'e.name as sec_name', 'f.house_name')
            ->get();

        // Fetch parameters (same as get_parameter_by_dm_id)
        $parameters = DB::table('domain_parameter_details')
            ->where('dm_id', $dm_id)
            ->get();

        // Fetch existing parameter values (like get_domain_parameter_value_by_id)
        $existingValues = DB::table('student_domain_details')
            ->where('term_id', $term_id)
            ->where('academic_yr', $academicYr)
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('hpc_sm_id', $subject_id)
            ->where('dm_id', $dm_id)
            ->whereIn('parameter_id', $parameters->pluck('parameter_id'))
            ->get()
            ->groupBy(function ($item) {
                return $item->student_id . '_' . $item->parameter_id;
            });
        $publish = $existingValues->flatten()->first()->publish ?? null;
        // Build response
        $response = [];
        foreach ($students as $student) {
            $paramData = [];
            foreach ($parameters as $param) {
                $key = $student->student_id . '_' . $param->parameter_id;
                $paramData[] = [
                    'parameter_id' => $param->parameter_id,
                    'parameter' => $param->learning_outcomes,
                    'value' => $existingValues[$key][0]->parameter_value ?? null
                ];
            }

            $response[] = [
                'student_id' => $student->student_id,
                'roll_no' => $student->roll_no,
                'name' => $student->first_name . ' ' . $student->last_name,
                'parameters' => $paramData

            ];
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $response,
            'publish' => $publish
        ]);
    }

    public function saveStudentParameterValue(Request $request)
    {
        $user = $this->authenticateUser();
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $term_id = $request->input('term_id');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $dm_id = $request->input('dm_id');
        $records = $request->input('records');
        $subject_id = $request->input('subject_id', 0);

        DB::beginTransaction();
        try {
            foreach ($records as $record) {
                $student_id = $record['student_id'];
                $parameter_id = $record['parameter_id'];
                $parameter_value = $record['value'];

                // Delete existing record (if any)
                DB::table('student_domain_details')
                    ->where('term_id', $term_id)
                    ->where('dm_id', $dm_id)
                    ->where('academic_yr', $academicYr)
                    ->where('student_id', $student_id)
                    ->where('parameter_id', $parameter_id)
                    ->delete();

                // Insert new
                DB::table('student_domain_details')->insert([
                    'parameter_value' => $parameter_value,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'parameter_id' => $parameter_id,
                    'student_id' => $student_id,
                    'hpc_sm_id' => $subject_id,
                    'term_id' => $term_id,
                    'dm_id' => $dm_id,
                    'date' => now(),
                    'academic_yr' => $academicYr,
                    'data_entry_by' => $user->reg_id,
                    'publish' => 'N'
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Domain data saved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Failed to save domain data.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function savenpublishStudentParameterValue(Request $request)
    {
        $user = $this->authenticateUser();
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');

        $term_id = $request->input('term_id');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $dm_id = $request->input('dm_id');
        $records = $request->input('records');
        $subject_id = $request->input('subject_id');

        DB::beginTransaction();
        try {
            foreach ($records as $record) {
                $student_id = $record['student_id'];
                $parameter_id = $record['parameter_id'];
                $parameter_value = $record['value'];

                DB::table('student_domain_details')
                    ->where('term_id', $term_id)
                    ->where('dm_id', $dm_id)
                    ->where('academic_yr', $academicYr)
                    ->where('student_id', $student_id)
                    ->where('parameter_id', $parameter_id)
                    ->delete();

                DB::table('student_domain_details')->insert([
                    'parameter_value' => $parameter_value,
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'parameter_id' => $parameter_id,
                    'student_id' => $student_id,
                    'hpc_sm_id' => $subject_id,
                    'term_id' => $term_id,
                    'dm_id' => $dm_id,
                    'date' => now(),
                    'academic_yr' => $academicYr,
                    'data_entry_by' => $user->reg_id,
                    'publish' => 'Y'
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Domain saved and published successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => 'Failed to publish domain data.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function unpublishStudentParameterValue(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $term_id = $request->input('term_id');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $dm_id = $request->input('dm_id');
        $subject_id = $request->input('subject_id');

        DB::table('student_domain_details')
            ->where('term_id', $term_id)
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('hpc_sm_id', $subject_id)
            ->update(['publish' => 'N']);

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Domain unpublished successfully.'
        ]);
    }

    public function publishStudentParameterValue(Request $request)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $term_id = $request->input('term_id');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $dm_id = $request->input('dm_id');
        $subject_id = $request->input('subject_id');

        DB::table('student_domain_details')
            ->where('term_id', $term_id)
            ->where('dm_id', $dm_id)
            ->where('academic_yr', $academicYr)
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('hpc_sm_id', $subject_id)
            ->update(['publish' => 'Y']);

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Domain published successfully.'
        ]);
    }

    public function getDomainsClass(Request $request, $class_id)
    {
        $payload = getTokenPayload($request);
        $academicYr = $payload->get('academic_year');
        $subject_id = $request->input('subject_id');

        $query = DB::table('domain_master as dm')
            ->where('dm.class_id', $class_id)
            ->where('dm.academic_yr', $academicYr);

        if (!empty($subject_id)) {
            $query->where('dm.HPC_sm_id', $subject_id);
        }

        $domains = $query->select('dm.name as domainname', 'dm.dm_id')->get();

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $domains
        ]);
    }

    public function getStudentsforReportCard(Request $request)
    {
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        // $academicYr = $request->input('academic_yr');

        $students = DB::table('student as a')
            ->leftJoin('parent as b', 'a.parent_id', '=', 'b.parent_id')
            ->join('user_master as c', 'a.parent_id', '=', 'c.reg_id')
            ->join('class as d', 'a.class_id', '=', 'd.class_id')
            ->join('section as e', 'a.section_id', '=', 'e.section_id')
            ->leftJoin('house as f', 'f.house_id', '=', 'a.house')
            ->select(
                'a.*',
                'b.*',
                'c.user_id',
                'd.name as class_name',
                'e.name as sec_name',
                'f.house_name'
            )
            ->where('a.IsDelete', 'N')
            // ->where('a.academic_yr', $academicYr)
            ->where('a.class_id', $class_id)
            ->where('a.section_id', $section_id)
            ->where('c.role_id', 'P')
            ->orderBy('a.roll_no')
            ->orderBy('a.reg_no')
            ->get();


        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

        $students->each(function ($student) use ($codeigniter_app_url) {
            // Student image
            $studentConcatUrl = $codeigniter_app_url . 'uploads/student_image/';
            if (!empty($student->image_name)) {
                $student->image_url = $studentConcatUrl . $student->image_name;
            } else {
                $student->image_url = '';
            }

            // family  images
            $parentConcatUrl = $codeigniter_app_url . 'uploads/family_image/';
            $student->family_image_url = !empty($student->family_image_name)
                ? $parentConcatUrl . $student->family_image_name
                : '';
        });

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Students fetched successfully.',
            'data' => $students
        ]);
    }


    public function savePhotoUploadForRC(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academicYr = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M') {

                $class_id = $request->input('class_id');
                $section_id = $request->input('section_id');

                // Fetch students automatically
                $students = DB::table('student as s')
                    ->join('parent as p', 's.parent_id', '=', 'p.parent_id')
                    ->where('s.class_id', $class_id)
                    ->where('s.section_id', $section_id)
                    ->where('s.IsDelete', 'N')
                    ->select('s.student_id', 'p.parent_id')
                    ->get();



                $photo_for_upload = 0;

                // Ensure folders exist
                $student_image_folder = storage_path("app/public/student_image/");
                if (!file_exists($student_image_folder)) mkdir($student_image_folder, 0777, true);

                $family_image_folder = storage_path("app/public/family_image/");
                if (!file_exists($family_image_folder)) mkdir($family_image_folder, 0777, true);

                foreach ($students as $student) {
                    if ($photo_for_upload >= 20) break;

                    // Student image
                    $sImageBase = $request->input('s_image_' . $student->student_id);
                    if ($sImageBase && $sImageBase != '') {
                        $photo_for_upload++;
                        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $sImageBase);
                        $ext = 'jpg'; // or detect from base64 if needed
                        $dataI = base64_decode($base64Data);
                        $imgNameEnd = $student->student_id . '.' . $ext;
                        $imagePath = $student_image_folder . $imgNameEnd;
                        file_put_contents($imagePath, $dataI);

                        DB::table('student')
                            ->where('student_id', $student->student_id)
                            ->update(['image_name' => $imgNameEnd]);

                        // Optional: call your upload helper
                        $doc_type_folder = 'student_image';
                        upload_student_profile_image_into_folder($student->student_id, $imgNameEnd, $doc_type_folder, $base64Data);
                    }

                    // Family image
                    $fImageBase = $request->input('f_image_' . $student->parent_id);
                    if ($fImageBase && $fImageBase != '') {
                        $photo_for_upload++;
                        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $fImageBase);
                        $ext = 'jpg';
                        $dataI = base64_decode($base64Data);
                        $f_imgNameEnd = $student->parent_id . '.' . $ext;
                        $imagePath = $family_image_folder . $f_imgNameEnd;
                        file_put_contents($imagePath, $dataI);

                        DB::table('parent')
                            ->where('parent_id', $student->parent_id)
                            ->update(['family_image_name' => $f_imgNameEnd]);

                        $docTypeFolder = 'family_image';
                        // upload_student_profile_image_into_folder($student->parent_id, $imgNameEnd, $doc_type_folder, $base64Data);
                        upload_parent_related_images($f_imgNameEnd, $base64Data, $docTypeFolder, $student->parent_id);
                    }
                }


                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => $photo_for_upload > 0
                        ? 'Photos uploaded successfully.'
                        : 'No photos were added for upload.'
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'success' => false,
                    'message' => 'This user does not have permission for uploading images.',
                    'data' => $user->role_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 500,
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function getReportCardPublishValue(Request $request)
    {
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $termId = $request->input('term_id');

        $query = DB::table('report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId);

        if (!empty($termId)) {
            $query->where('term_id', $termId);
        }

        $publish = $query->first();

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $publish,
            'message' => 'Report card publish value.'
        ]);
    }

    public function saveReportCardPublishValue(Request $request)
    {
        $data = [
            'class_id'   => $request->class_id,
            'section_id' => $request->section_id,
            'term_id'    => $request->term_id,
            'publish'    => $request->publish,
        ];

        $exists = DB::table('report_card_publish')
            ->where('class_id', $data['class_id'])
            ->where('section_id', $data['section_id'])
            ->where('term_id', $data['term_id'])
            ->exists();

        if ($exists) {
            DB::table('report_card_publish')
                ->where('class_id', $data['class_id'])
                ->where('section_id', $data['section_id'])
                ->where('term_id', $data['term_id'])
                ->update($data);

            $message = $request->publish == 'N'
                ? 'Report Card unpublished successfully!!!'
                : 'Report Card published successfully!!!';
        } else {
            DB::table('report_card_publish')->insert($data);

            $message = $request->publish == 'N'
                ? 'Report Card unpublished successfully!!!'
                : 'Report Card published successfully!!!';
        }

        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => $message,
        ]);
    }

    public function saveReportCardReopenDate(Request $request)
    {
        $data = [
            'class_id'    => $request->class_id,
            'section_id'  => $request->section_id,
            'reopen_date' => date('Y-m-d', strtotime($request->reopen_date)),
        ];

        // Check if record exists
        $exists = DB::table('report_card_publish')
            ->where('class_id', $data['class_id'])
            ->where('section_id', $data['section_id'])
            ->exists();

        if ($exists) {
            DB::table('report_card_publish')
                ->where('class_id', $data['class_id'])
                ->where('section_id', $data['section_id'])
                ->update($data);
        } else {
            // Optional: If reopen should only update existing records, skip this insert
            DB::table('report_card_publish')->insert($data);
        }

        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Re-open date saved successfully!!!',
        ]);
    }

    public function getReportCardRemarkValue(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $classId   = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $termId    = $request->input('term_id');

        $students = DB::table('student as a')
            ->leftJoin('parent as b', 'a.parent_id', '=', 'b.parent_id')
            ->join('user_master as c', 'a.parent_id', '=', 'c.reg_id')
            ->join('class as d', 'a.class_id', '=', 'd.class_id')
            ->join('section as e', 'a.section_id', '=', 'e.section_id')
            ->leftJoin('house as f', 'a.house', '=', 'f.house_id')
            ->select(
                'a.student_id',
                DB::raw("CONCAT_WS(' ', a.first_name, a.mid_name, a.last_name) as student_name"),
                'a.roll_no',
                'a.reg_no',
                'a.academic_yr',
                'b.*',
                'c.user_id',
                'd.name as class_name',
                'e.name as section_name',
                'f.house_name'
            )
            ->where('a.IsDelete', 'N')
            ->where('a.academic_yr', $academicYr)
            ->where('a.class_id', $classId)
            ->where('a.section_id', $sectionId)
            ->where('c.role_id', 'P')
            ->orderBy('a.roll_no')
            ->orderBy('a.reg_no')
            ->get();


        $students = $students->map(function ($student) use ($termId) {
            $remarkData = DB::table('report_card_remarks')
                ->where('student_id', $student->student_id)
                ->where('term_id', $termId)
                ->select('remark', 'promot')
                ->first();

            $student->remark = $remarkData->remark ?? null;
            $student->promote_to = $remarkData->promot ?? null;

            return $student;
        });

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => $students,
            'message' => 'Students with report card remarks and promotion info'
        ]);
    }

    public function getPromoteToValue(Request $request)
    {
        $classId  = $request->input('class_id');
        $termName = $request->input('term_name');

        // Fetch current class
        $class = DB::table('class')
            ->where('class_id', $classId)
            ->first();

        if (!$class) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Class not found.'
            ]);
        }

        $className = $class->name;

        // Get next class (by incrementing id)
        $nextClass = null;
        $next_class_id = $classId + 1;

        $nextClassObj = DB::table('class')
            ->where('class_id', $next_class_id)
            ->first();
        // dd($nextClassObj);
        if ($nextClassObj) {
            $nextClass = $nextClassObj->name;
        }

        // Prepare promote options
        $options = [];

        if ($termName !== 'Term 1') {
            if ($className === '9') {
                $options = [
                    "X",
                    "Re-Examination",
                    "Detained in IX A",
                    "Detained in IX B",
                    "Detained in IX C",
                ];
            } elseif ($nextClass) {
                $options = [$nextClass];
            }
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'options' => $options,
            'message' => 'Promote to options fetched successfully.'
        ]);
    }

    public function saveReportCardRemark(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $termId     = $request->input('term_id');
        $classId    = $request->input('class_id');
        $sectionId  = $request->input('section_id');
        $studentIds = $request->input('student_id', []);
        $remarks    = $request->input('remark', []);
        $promotes   = $request->input('promot', []);

        if (empty($studentIds)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'No students provided.'
            ]);
        }

        foreach ($studentIds as $i => $studentId) {
            $existing = DB::table('report_card_remarks')
                ->where('term_id', $termId)
                ->where('student_id', $studentId)
                ->first();

            if (!$existing) {
                // Insert new record
                DB::table('report_card_remarks')->insert([
                    'term_id'     => $termId,
                    'academic_yr' => $academicYr,
                    'student_id'  => $studentId,
                    'remark'      => $remarks[$i] ?? null,
                    'promot'      => $promotes[$i] ?? null
                ]);
            } else {
                // Update existing record
                DB::table('report_card_remarks')
                    ->where('student_id', $studentId)
                    ->where('term_id', $termId)
                    ->update([
                        'remark'     => $remarks[$i] ?? null,
                        'promot'     => $promotes[$i] ?? null
                    ]);
            }
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Remarks updated successfully!'
        ]);
    }



    public function saveSelfAssessmentMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');

        // Validate input
        $data = $request->validate([
            'parameter'     => 'required',
            'class_id'      => 'required',
            'control_type'  => 'required',
            'options'       => 'nullable'
        ]);

        if (in_array($data['control_type'], ['checkbox', 'radio', 'rating'])) {
            $options = json_encode($data['options']);
        } else {
            $options = null;
        }

        // Insert record
        $id = DB::table('self_assessment_master')->insertGetId([
            'parameter'    => $data['parameter'],
            'class_id'     => $data['class_id'],
            'control_type' => $data['control_type'],
            'options'      => $options,
            'academic_yr'  => $academicYr
        ]);

        $saved = DB::table('self_assessment_master')->where('sam_id', $id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Self assessment question saved successfully.',
            'data'    => $saved,
            'success' => true
        ]);

        // $id = DB::table('self_assessment_master')->insertGetId([
        //     'parameter'   => $request->parameter,
        //     'class_id'    => $request->class_id,
        //     'control_type'=> $request->control_type,
        //     'academic_yr' => $academicYr
        // ]);

        // return response()->json([
        //     'status'  => 200,
        //     'message' => 'Self Assessment saved successfully',
        //     'data'    => DB::table('self_assessment_master')->where('sam_id', $id)->first(),
        //     'success' =>true
        // ], 201);
    }

    public function getSelfAssessmentMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $records = DB::table('self_assessment_master')
            ->join('class', 'class.class_id', '=', 'self_assessment_master.class_id')
            ->where('self_assessment_master.academic_yr', $academicYr)
            ->select('self_assessment_master.*', 'class.name as classname')
            ->get();

        return response()->json([
            'status'  => 200,
            'data'    => $records,
            'message' => 'Self assessment master listing.',
            'success' => true
        ]);
    }

    public function updateSelfAssessmentMaster(Request $request, $sam_id)
    {
        $record = DB::table('self_assessment_master')->where('sam_id', $sam_id)->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        // Validate input
        $data = $request->validate([
            'parameter'     => 'required',
            'class_id'      => 'required',
            'control_type'  => 'required',
            'options'       => 'nullable',
        ]);

        $updateData = [];

        if (isset($data['parameter'])) {
            $updateData['parameter'] = $data['parameter'];
        }

        if (isset($data['class_id'])) {
            $updateData['class_id'] = $data['class_id'];
        }

        if (isset($data['control_type'])) {
            $updateData['control_type'] = $data['control_type'];


            if (in_array($data['control_type'], ['checkbox', 'radio', 'rating'])) {
                $updateData['options'] = isset($data['options'])
                    ? json_encode($data['options'])
                    : null;
            } else {
                $updateData['options'] = null;
            }
        } elseif (isset($data['options'])) {

            $updateData['options'] = json_encode($data['options']);
        }

        if (!empty($updateData)) {
            DB::table('self_assessment_master')
                ->where('sam_id', $sam_id)
                ->update($updateData);
        }

        $updated = DB::table('self_assessment_master')->where('sam_id', $sam_id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Self assessment master updated successfully',
            'data'    => $updated,
            'success' => true
        ]);
    }

    public function deleteSelfAssessmentMaster(Request $request, $sam_id)
    {
        $exists = DB::table('self_assessment')
            ->where('sam_id', $sam_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 422,
                'message' => 'Cannot delete. Record already used in self assessment.',
                'success' => false
            ]);
        }
        DB::table('self_assessment_master')->where('sam_id', $sam_id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Record deleted successfully',
            'success' => true
        ]);
    }

    public function getSelfAssessment(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;

        // Get master parameters
        $parameters = DB::table('self_assessment_master')
            ->where('class_id', $class_id)
            ->get();

        // Get students in class/section
        $students = DB::select("
            select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name 
            from student a 
            left join parent b on a.parent_id=b.parent_id 
            join user_master c on a.parent_id = c.reg_id 
            join class d on a.class_id=d.class_id 
            join section e on a.section_id=e.section_id 
            left join house f on f.house_id=a.house 
            where a.IsDelete='N' 
              and a.academic_yr=? 
              and a.class_id=? 
              and a.section_id=? 
              and c.role_id='P' 
            order by a.roll_no,a.reg_no", [$academic_yr, $class_id, $section_id]);


        $publish = DB::table('self_assessment')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->value('publish') ?? 'N';
        // Get assessments for each student+parameter
        $results = [];
        foreach ($students as $stu) {
            $stu_assessments = [];
            foreach ($parameters as $param) {
                $value = DB::table('self_assessment')
                    ->where('student_id', $stu->student_id)
                    ->where('sam_id', $param->sam_id)
                    ->where('term_id', $term_id)
                    ->where('academic_yr', $academic_yr)
                    ->value('parameter_value');

                $stu_assessments[] = [
                    'sam_id'   => $param->sam_id,
                    'parameter_name' => $param->parameter,
                    'options'  => $param->options,
                    'control_type' => $param->control_type,
                    'value'          => $value ?? ''
                ];
            }

            $results[] = [
                'student_id'   => $stu->student_id,
                'roll_no'      => $stu->roll_no,
                'student_name' => $stu->first_name . ' ' . $stu->last_name,
                'assessments'  => $stu_assessments
            ];
        }

        return response()->json([
            'status'     => 200,
            'parameters' => $parameters,
            'students'   => $results,
            'publish'    => $publish,
            'message'    => 'Self assessment.',
            'success'    => true
        ]);
    }

    public function saveSelfAssessment(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('self_assessment')
                ->where('student_id', $item['student_id'])
                ->where('sam_id', $item['sam_id'])
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('self_assessment')->insert([
                'student_id'      => $item['student_id'],
                'sam_id'          => $item['sam_id'],
                'term_id'         => $term_id,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'parameter_value' => $item['value'],
                'publish'         => 'N'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Self assessments saved successfully',
            'success' => true
        ]);
    }

    public function savenPublishSelfAssessment(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('self_assessment')
                ->where('student_id', $item['student_id'])
                ->where('sam_id', $item['sam_id'])
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->delete();

            DB::table('self_assessment')->insert([
                'student_id'      => $item['student_id'],
                'sam_id'          => $item['sam_id'],
                'term_id'         => $term_id,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'parameter_value' => $item['value'],
                'publish'         => 'Y'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Self assessments saved and published successfully',
            'success' => true
        ]);
    }

    public function unpublishSelfAssessment(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->input('class_id');
        $section_id = $request->input('section_id');
        $term_id    = $request->input('term_id');
        DB::table('self_assessment')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->update([
                'publish'         => 'N',
            ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Self assessments unpublished successfully',
            'success' => true
        ]);
    }

    public function savePeerFeedbackMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');

        // Validate input
        $data = $request->validate([
            'parameter'     => 'required',
            'class_id'      => 'required',
            'control_type'  => 'required',
            'options'       => 'nullable'
        ]);

        if (in_array($data['control_type'], ['checkbox', 'radio', 'rating'])) {
            $options = json_encode($data['options']);
        } else {
            $options = null;
        }

        // Insert record
        $id = DB::table('peer_feedback_master')->insertGetId([
            'parameter'    => $data['parameter'],
            'class_id'     => $data['class_id'],
            'control_type' => $data['control_type'],
            'options'      => $options,
            'academic_yr'  => $academicYr
        ]);

        $saved = DB::table('peer_feedback_master')->where('pfm_id', $id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Peer feedback saved successfully.',
            'data'    => $saved,
            'success' => true
        ]);
    }

    public function getPeerFeedbackMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $records = DB::table('peer_feedback_master')
            ->join('class', 'class.class_id', '=', 'peer_feedback_master.class_id')
            ->where('peer_feedback_master.academic_yr', $academicYr)
            ->select('peer_feedback_master.*', 'class.name as classname')
            ->get();

        return response()->json([
            'status'  => 200,
            'data'    => $records,
            'message' => 'Peer feedback master listing.',
            'success' => true
        ]);
    }

    public function updatePeerFeedbackMaster(Request $request, $pfm_id)
    {

        $record = DB::table('peer_feedback_master')->where('pfm_id', $pfm_id)->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        // Validate input
        $data = $request->validate([
            'parameter'     => 'required',
            'class_id'      => 'required',
            'control_type'  => 'required',
            'options'       => 'nullable',
        ]);

        $updateData = [];

        if (isset($data['parameter'])) {
            $updateData['parameter'] = $data['parameter'];
        }

        if (isset($data['class_id'])) {
            $updateData['class_id'] = $data['class_id'];
        }

        if (isset($data['control_type'])) {
            $updateData['control_type'] = $data['control_type'];


            if (in_array($data['control_type'], ['checkbox', 'radio', 'rating'])) {
                $updateData['options'] = isset($data['options'])
                    ? json_encode($data['options'])
                    : null;
            } else {
                $updateData['options'] = null;
            }
        } elseif (isset($data['options'])) {

            $updateData['options'] = json_encode($data['options']);
        }

        if (!empty($updateData)) {
            DB::table('peer_feedback_master')
                ->where('pfm_id', $pfm_id)
                ->update($updateData);
        }

        $updated = DB::table('peer_feedback_master')->where('pfm_id', $pfm_id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Peer feedback master updated successfully.',
            'data'    => $updated,
            'success' => true
        ]);
    }

    public function deletePeerFeedbackMaster(Request $request, $pfm_id)
    {
        $exists = DB::table('peer_feedback')
            ->where('pfm_id', $pfm_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 422,
                'message' => 'Cannot delete. Record already used in peer feedback.',
                'success' => false
            ]);
        }
        DB::table('peer_feedback_master')->where('pfm_id', $pfm_id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Record deleted successfully',
            'success' => true
        ]);
    }

    public function getPeerFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;

        // Get master parameters
        $parameters = DB::table('peer_feedback_master')
            ->where('class_id', $class_id)
            ->get();

        // Get students in class/section
        $students = DB::select("
            select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name 
            from student a 
            left join parent b on a.parent_id=b.parent_id 
            join user_master c on a.parent_id = c.reg_id 
            join class d on a.class_id=d.class_id 
            join section e on a.section_id=e.section_id 
            left join house f on f.house_id=a.house 
            where a.IsDelete='N' 
              and a.academic_yr=? 
              and a.class_id=? 
              and a.section_id=? 
              and c.role_id='P' 
            order by a.roll_no,a.reg_no", [$academic_yr, $class_id, $section_id]);


        $publish = DB::table('peer_feedback')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->value('publish') ?? 'N';
        // Get assessments for each student+parameter
        $results = [];
        foreach ($students as $stu) {
            $stu_assessments = [];
            foreach ($parameters as $param) {
                $value = DB::table('peer_feedback')
                    ->where('student_id', $stu->student_id)
                    ->where('pfm_id', $param->pfm_id)
                    ->where('term_id', $term_id)
                    ->where('academic_yr', $academic_yr)
                    ->value('parameter_value');

                $stu_assessments[] = [
                    'pfm_id'   => $param->pfm_id,
                    'parameter_name' => $param->parameter,
                    'options'  => $param->options,
                    'control_type' => $param->control_type,
                    'value'          => $value ?? ''
                ];
            }

            $results[] = [
                'student_id'   => $stu->student_id,
                'roll_no'      => $stu->roll_no,
                'student_name' => $stu->first_name . ' ' . $stu->last_name,
                'assessments'  => $stu_assessments
            ];
        }

        return response()->json([
            'status'     => 200,
            'parameters' => $parameters,
            'students'   => $results,
            'publish'    => $publish,
            'message'    => 'Peer feedback.',
            'success'    => true
        ]);
    }

    public function savePeerFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('peer_feedback')
                ->where('student_id', $item['student_id'])
                ->where('pfm_id', $item['pfm_id'])
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('peer_feedback')->insert([
                'student_id'      => $item['student_id'],
                'pfm_id'          => $item['pfm_id'],
                'term_id'         => $term_id,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'parameter_value' => $item['value'],
                'publish'         => 'N'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Peer feedback saved successfully.',
            'success' => true
        ]);
    }

    public function savenPublishPeerFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('peer_feedback')
                ->where('student_id', $item['student_id'])
                ->where('pfm_id', $item['pfm_id'])
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('peer_feedback')->insert([
                'student_id'      => $item['student_id'],
                'pfm_id'          => $item['pfm_id'],
                'term_id'         => $term_id,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'parameter_value' => $item['value'],
                'publish'         => 'Y'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Peer feedback saved and published successfully',
            'success' => true
        ]);
    }

    public function unpublishPeerFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->input('class_id');
        $section_id = $request->input('section_id');
        $term_id    = $request->input('term_id');
        DB::table('peer_feedback')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->update([
                'publish'         => 'N',
            ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Peer feedback unpublished successfully',
            'success' => true
        ]);
    }



    public function saveAllAboutMeMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $id = DB::table('allaboutme_master')->insertGetId([
            'name'   => $request->parameter,
            'class_id'    => $request->class_id,
            'academic_yr' => $academicYr
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'All about me saved successfully.',
            'data'    => DB::table('allaboutme_master')->where('am_id', $id)->first(),
            'success' => true
        ]);
    }

    public function getAllAboutMeMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $records = DB::table('allaboutme_master')
            ->join('class', 'class.class_id', '=', 'allaboutme_master.class_id')
            ->where('allaboutme_master.academic_yr', $academicYr)
            ->select('allaboutme_master.*', 'class.name as classname')
            ->get();

        return response()->json([
            'status'  => 200,
            'data'    => $records,
            'message' => 'All about me master listing.',
            'success' => true
        ]);
    }

    public function updateAllAboutMeMaster(Request $request, $am_id)
    {
        $record = DB::table('allaboutme_master')->where('am_id', $am_id)->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        DB::table('allaboutme_master')
            ->where('am_id', $am_id)
            ->update([
                'name'   => $request->name,
                'class_id'    => $request->class_id
            ]);

        $updated = DB::table('allaboutme_master')->where('am_id', $am_id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'All about me master updated successfully',
            'data'    => $updated,
            'success' => true
        ]);
    }

    public function deleteAllAboutMeMaster(Request $request, $am_id)
    {
        $exists = DB::table('student_allaboutme_details')
            ->where('am_id', $am_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 422,
                'message' => 'Cannot delete. Record already used in student all about me.',
                'success' => false
            ]);
        }
        DB::table('allaboutme_master')->where('am_id', $am_id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Record deleted successfully',
            'success' => true
        ]);
    }

    public function getAllAboutMe(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $am_id    = $request->am_id;

        // Get master parameters
        $parameters = DB::table('allaboutme_master')
            ->where('am_id', $am_id)
            ->get();

        // Get students in class/section
        $students = DB::select("
            select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name 
            from student a 
            left join parent b on a.parent_id=b.parent_id 
            join user_master c on a.parent_id = c.reg_id 
            join class d on a.class_id=d.class_id 
            join section e on a.section_id=e.section_id 
            left join house f on f.house_id=a.house 
            where a.IsDelete='N' 
              and a.academic_yr=? 
              and a.class_id=? 
              and a.section_id=? 
              and c.role_id='P' 
            order by a.roll_no,a.reg_no", [$academic_yr, $class_id, $section_id]);


        $publish = DB::table('student_allaboutme_details')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('am_id', $am_id)
            ->where('academic_yr', $academic_yr)
            ->value('publish') ?? 'N';
        // Get assessments for each student+parameter
        $results = [];
        foreach ($students as $stu) {
            $stu_assessments = [];
            foreach ($parameters as $param) {
                $value = DB::table('student_allaboutme_details')
                    ->where('student_id', $stu->student_id)
                    ->where('am_id', $param->am_id)
                    ->where('academic_yr', $academic_yr)
                    ->value('aboutme_value');

                $stu_assessments[] = [
                    'am_id'   => $param->am_id,
                    'parameter_name' => $param->name,
                    'value'          => $value ?? ''
                ];
            }

            $results[] = [
                'student_id'   => $stu->student_id,
                'roll_no'      => $stu->roll_no,
                'student_name' => $stu->first_name . ' ' . $stu->last_name,
                'assessments'  => $stu_assessments
            ];
        }

        return response()->json([
            'status'     => 200,
            'parameters' => $parameters,
            'students'   => $results,
            'publish'    => $publish,
            'message'    => 'All about me.',
            'success'    => true
        ]);
    }

    public function saveAllAboutMe(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $am_id    = $request->am_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('student_allaboutme_details')
                ->where('student_id', $item['student_id'])
                ->where('am_id', $item['am_id'])
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('student_allaboutme_details')->insert([
                'student_id'      => $item['student_id'],
                'am_id'          => $item['am_id'],
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'aboutme_value' => $item['value'],
                'publish'         => 'N'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'All about me saved successfully.',
            'success' => true
        ]);
    }

    public function savenPublishAllAboutMe(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $am_id    = $request->am_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('student_allaboutme_details')
                ->where('student_id', $item['student_id'])
                ->where('am_id', $item['am_id'])
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('student_allaboutme_details')->insert([
                'student_id'      => $item['student_id'],
                'am_id'          => $item['am_id'],
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'aboutme_value' => $item['value'],
                'publish'         => 'Y'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Student all about me saved and published successfully',
            'success' => true
        ]);
    }

    public function unpublishAllAboutme(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->input('class_id');
        $section_id = $request->input('section_id');
        $am_id    =   $request->input('am_id');
        DB::table('student_allaboutme_details')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('am_id', $am_id)
            ->where('academic_yr', $academic_yr)
            ->update([
                'publish'         => 'N',
            ]);

        return response()->json([
            'status'  => 200,
            'message' => 'All about me unpublished successfully',
            'success' => true
        ]);
    }

    public function saveParentFeedbackMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');

        // Validate input
        $data = $request->validate([
            'parameter'     => 'required',
            'class_id'      => 'required',
            'control_type'  => 'required',
            'options'       => 'nullable'
        ]);

        if (in_array($data['control_type'], ['checkbox', 'radio', 'rating'])) {
            $options = json_encode($data['options']);
        } else {
            $options = null;
        }

        // Insert record
        $id = DB::table('parent_feedback_master')->insertGetId([
            'parameter'    => $data['parameter'],
            'class_id'     => $data['class_id'],
            'control_type' => $data['control_type'],
            'options'      => $options,
            'academic_yr'  => $academicYr
        ]);

        $saved = DB::table('parent_feedback_master')->where('pfm_id', $id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Parent feedback question saved successfully.',
            'data'    => $saved,
            'success' => true
        ]);
    }

    public function getParentFeedbackMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $records = DB::table('parent_feedback_master')
            ->join('class', 'class.class_id', '=', 'parent_feedback_master.class_id')
            ->where('parent_feedback_master.academic_yr', $academicYr)
            ->select('parent_feedback_master.*', 'class.name as classname')
            ->get();

        return response()->json([
            'status'  => 200,
            'data'    => $records,
            'message' => 'Parent feedback master listing.',
            'success' => true
        ]);
    }

    public function updateParentFeedbackMaster(Request $request, $pfm_id)
    {
        $record = DB::table('parent_feedback_master')->where('pfm_id', $pfm_id)->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        // Validate input
        $data = $request->validate([
            'parameter'     => 'required',
            'class_id'      => 'required',
            'control_type'  => 'required',
            'options'       => 'nullable',
        ]);

        $updateData = [];

        if (isset($data['parameter'])) {
            $updateData['parameter'] = $data['parameter'];
        }

        if (isset($data['class_id'])) {
            $updateData['class_id'] = $data['class_id'];
        }

        if (isset($data['control_type'])) {
            $updateData['control_type'] = $data['control_type'];


            if (in_array($data['control_type'], ['checkbox', 'radio', 'rating'])) {
                $updateData['options'] = isset($data['options'])
                    ? json_encode($data['options'])
                    : null;
            } else {
                $updateData['options'] = null;
            }
        } elseif (isset($data['options'])) {

            $updateData['options'] = json_encode($data['options']);
        }

        if (!empty($updateData)) {
            DB::table('parent_feedback_master')
                ->where('pfm_id', $pfm_id)
                ->update($updateData);
        }

        $updated = DB::table('parent_feedback_master')->where('pfm_id', $pfm_id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Parent feedback master updated successfully',
            'data'    => $updated,
            'success' => true
        ]);
    }

    public function deleteParentFeedbackMaster(Request $request, $pfm_id)
    {
        $exists = DB::table('parent_feedback')
            ->where('pfm_id', $pfm_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 422,
                'message' => 'Cannot delete. Record already used in parent feedback.',
                'success' => false
            ]);
        }
        DB::table('parent_feedback_master')->where('pfm_id', $pfm_id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Record deleted successfully',
            'success' => true
        ]);
    }

    public function getParentFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;

        // Get master parameters
        $parameters = DB::table('parent_feedback_master')
            ->where('class_id', $class_id)
            ->get();

        // Get students in class/section
        $students = DB::select("
            select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name 
            from student a 
            left join parent b on a.parent_id=b.parent_id 
            join user_master c on a.parent_id = c.reg_id 
            join class d on a.class_id=d.class_id 
            join section e on a.section_id=e.section_id 
            left join house f on f.house_id=a.house 
            where a.IsDelete='N' 
              and a.academic_yr=? 
              and a.class_id=? 
              and a.section_id=? 
              and c.role_id='P' 
            order by a.roll_no,a.reg_no", [$academic_yr, $class_id, $section_id]);


        $publish = DB::table('parent_feedback')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->value('publish') ?? 'N';
        // Get assessments for each student+parameter
        $results = [];
        foreach ($students as $stu) {
            $stu_assessments = [];
            foreach ($parameters as $param) {
                $value = DB::table('parent_feedback')
                    ->where('student_id', $stu->student_id)
                    ->where('pfm_id', $param->pfm_id)
                    ->where('term_id', $term_id)
                    ->where('academic_yr', $academic_yr)
                    ->value('parameter_value');

                $stu_assessments[] = [
                    'pfm_id'   => $param->pfm_id,
                    'options'  => $param->options,
                    'control_type' => $param->control_type,
                    'parameter_name' => $param->parameter,
                    'value'          => $value ?? ''
                ];
            }

            $results[] = [
                'student_id'   => $stu->student_id,
                'roll_no'      => $stu->roll_no,
                'student_name' => $stu->first_name . ' ' . $stu->last_name,
                'assessments'  => $stu_assessments
            ];
        }

        return response()->json([
            'status'     => 200,
            'parameters' => $parameters,
            'students'   => $results,
            'publish'    => $publish,
            'message'    => 'Parent feedback.',
            'success'    => true
        ]);
    }

    public function saveParentFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('parent_feedback')
                ->where('student_id', $item['student_id'])
                ->where('pfm_id', $item['pfm_id'])
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('parent_feedback')->insert([
                'student_id'      => $item['student_id'],
                'pfm_id'          => $item['pfm_id'],
                'term_id'         => $term_id,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'parameter_value' => $item['value'],
                'publish'         => 'N'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Parent feedback saved successfully.',
            'success' => true
        ]);
    }

    public function savenPublishParentFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('parent_feedback')
                ->where('student_id', $item['student_id'])
                ->where('pfm_id', $item['pfm_id'])
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('parent_feedback')->insert([
                'student_id'      => $item['student_id'],
                'pfm_id'          => $item['pfm_id'],
                'term_id'         => $term_id,
                'class_id'        => $class_id,
                'section_id'      => $section_id,
                'academic_yr'     => $academic_yr,
                'date'            => now()->toDateString(),
                'data_entry_by'   => $user->reg_id,
                'parameter_value' => $item['value'],
                'publish'         => 'Y'
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Parent feedback saved and published successfully',
            'success' => true
        ]);
    }

    public function unpublishParentFeedback(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->input('class_id');
        $section_id = $request->input('section_id');
        $term_id    = $request->input('term_id');
        DB::table('parent_feedback')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->update([
                'publish'         => 'N',
            ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Parent feedback unpublished successfully',
            'success' => true
        ]);
    }

    public function gethpcreportcard(Request $request)
    {
        $studentId = $request->input('student_id');
        $studentdata = DB::table('student')
            ->join('class', 'student.class_id', '=', 'class.class_id')
            ->join('section', 'student.section_id', '=', 'section.section_id')
            ->where('student.student_id', $studentId)
            ->select('student.first_name', 'student.mid_name', 'student.last_name', 'student.dob', 'class.name as classname', 'section.name as sectionname', 'student.academic_yr')
            ->first();
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();

        $classId   = $studentDetails->class_id;
        $sectionId = $studentDetails->section_id;
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();
        $studentData = DB::table('student')
            ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('class', 'student.class_id', '=', 'class.class_id')
            ->join('section', 'student.section_id', '=', 'section.section_id')
            ->where('student.student_id', $studentId)
            ->select(
                'student.student_id',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'student.dob',
                'class.name as classname',
                'section.name as sectionname',
                'student.image_name',
                'parent.family_image_name'
            )
            ->first();

        if (!$studentData) {
            return response()->json([
                'status' => false,
                'message' => 'Student not found',
                'data' => null
            ], 404);
        }


        $allAboutMe = DB::table('allaboutme_master')
            ->leftJoin('student_allaboutme_details', function ($join) use ($studentId) {
                $join->on('allaboutme_master.am_id', '=', 'student_allaboutme_details.am_id')
                    ->where('student_allaboutme_details.student_id', '=', $studentId)
                    ->where('student_allaboutme_details.publish', '=', 'Y');
            })
            ->where('allaboutme_master.class_id', $classId)
            ->select(
                'allaboutme_master.am_id',
                'allaboutme_master.name',
                'student_allaboutme_details.aboutme_value',
                'student_allaboutme_details.publish'
            )
            ->get();

        $termList = DB::table('term')->whereIn('term_id', $publishedTerms)->get();
        $termDates = get_term_dates($studentdata->academic_yr, count($termList)) ?? [];
        $attendanceData = [];

        foreach ($termDates as $index => $term) {
            $present = get_total_stu_attendance($studentId, $term['from'], $term['to'], $studentdata->academic_yr);
            $working = get_total_stu_workingdays($studentId, $term['from'], $term['to'], $studentdata->academic_yr);

            $attendanceData[] = [
                'term' => 'Term ' . ($index + 1),
                'from' => $term['from'],
                'to'   => $term['to'],
                'present' => $present,
                'working' => $working,
            ];
        }
        // $globalVariables = App::make('global_variables');
        $baseUrl = 'https://sms.arnoldcentralschool.org/';

        $studentImage = $studentData->image_name
            ? $baseUrl . 'uploads/student_image/' . $studentData->image_name
            : null;

        $familyImage = $studentData->family_image_name
            ? $baseUrl . 'uploads/family_image/' . $studentData->family_image_name
            : null;


        $data = [
            'student' => [
                'student_id' => $studentData->student_id,
                'first_name' => $studentData->first_name,
                'mid_name' => $studentData->mid_name,
                'last_name' => $studentData->last_name,
                'dob' => $studentData->dob,
                'classname' => $studentData->classname,
                'sectionname' => $studentData->sectionname,
                'studentimage' => $studentImage,
                'familyimage' => $familyImage,
            ],
            'attendance' => $attendanceData,
            'allAboutMe' => $allAboutMe
        ];
        $academicYr = $studentdata->academic_yr;
        $studentDomains = DB::table('domain_master as dm')
            ->leftjoin('HPC_subject_master as sm', 'sm.hpc_sm_id', '=', 'dm.HPC_sm_id')
            ->join('domain_parameter_details as dpd', 'dpd.dm_id', '=', 'dm.dm_id')
            ->leftJoin('domain_competencies as dc', 'dc.dm_competency_id', '=', 'dpd.dm_competency_id')
            ->leftJoin('student_domain_details as sdd', function ($join) use ($studentId, $academicYr) {
                $join->on('sdd.dm_id', '=', 'dpd.dm_id')
                    ->on('sdd.parameter_id', '=', 'dpd.parameter_id')
                    ->where('sdd.student_id', '=', $studentId)
                    ->where('sdd.academic_yr', '=', $academicYr)
                    ->where('sdd.publish', '=', 'Y');
            })
            ->where('dm.class_id', $classId)
            ->select(
                'sm.hpc_sm_id',
                'dm.class_id',
                'sm.name as subjectname',
                'dm.name as domainname',
                'dm.color_code',
                'dm.curriculum_goal',
                'dc.name as competency',
                'dpd.learning_outcomes',
                'sdd.parameter_value',
                'sdd.term_id',
                'sdd.publish',
            )

            ->orderBy('sm.hpc_sm_id')
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        // dd($studentDomains);
        $terms = DB::table('term')->pluck('name', 'term_id');

        $subjectsGrouped = $studentDomains->groupBy(function ($item) {
            return $item->subjectname . '||' . $item->domainname;
        })->map(function ($subjectItems) use ($terms) {
            $first = $subjectItems->first();

            // Group by competency
            $competenciesGrouped = $subjectItems->groupBy('competency')->map(function ($compItems) use ($terms) {
                // Group learning outcomes
                $details = $compItems->groupBy('learning_outcomes')->map(function ($outcomeItems) use ($terms) {
                    $termValues = [];
                    foreach ($outcomeItems as $item) {
                        if ($item->term_id) {
                            $termName = $item->term_id;
                            $termValues[$termName] = $item->parameter_value;
                        }
                    }
                    return [
                        'learning_outcomes' => $outcomeItems->first()->learning_outcomes,
                        'parameter_value' => $termValues
                    ];
                })->values();

                return [
                    'competency' => $compItems->first()->competency,
                    'details' => $details
                ];
            })->values();

            return [
                'subjectname' => $first->subjectname,
                'domainname' => $first->domainname,
                'curriculum_goal' => $first->curriculum_goal,
                'color_code' => $first->color_code,
                'competencies' => $competenciesGrouped
            ];
        })->values();
        $rawResults = DB::table('self_assessment_master as sam')
            ->leftJoin('self_assessment as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.sam_id', '=', 'sa.sam_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.class_id', $classId)
                    ->where('sa.academic_yr', $academicYr)
                    ->where('sa.publish', 'Y');
            })
            ->select(
                'sam.sam_id',
                'sam.parameter',
                'sam.control_type',
                'sam.options',
                'sa.parameter_value',
                'sa.term_id'
            )
            ->where('sam.class_id', $classId)
            ->where('sam.academic_yr', $academicYr)
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $results = $rawResults->groupBy('sam_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->parameter_value;
                }
            }

            return [
                'sam_id' => $first->sam_id,
                'parameter' => $first->parameter,
                'control_type' => $first->control_type,
                'options' => json_decode($first->options, true),
                'parameter_values' => $termValues
            ];
        })->values();
        $rawpeerfeedback = DB::table('peer_feedback_master as sam')
            ->leftJoin('peer_feedback as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.pfm_id', '=', 'sa.pfm_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.class_id', $classId)
                    ->where('sa.academic_yr', $academicYr)
                    ->where('sa.publish', 'Y');
            })
            ->select(
                'sam.pfm_id',
                'sam.parameter',
                'sam.control_type',
                'sam.options',
                'sa.parameter_value',
                'sa.term_id'
            )
            ->where('sam.class_id', $classId)
            ->where('sam.academic_yr', $academicYr)
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $peerfeedback = $rawpeerfeedback->groupBy('pfm_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->parameter_value;
                }
            }

            return [
                'pfm_id' => $first->pfm_id,
                'parameter' => $first->parameter,
                'control_type' => $first->control_type,
                'options' => json_decode($first->options, true), // decode JSON
                'parameter_values' => $termValues
            ];
        })->values();
        $rawparentfeedback = DB::table('parent_feedback_master as sam')
            ->leftJoin('parent_feedback as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.pfm_id', '=', 'sa.pfm_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.class_id', $classId)
                    ->where('sa.academic_yr', $academicYr)
                    ->where('sa.publish', 'Y');
            })
            ->select(
                'sam.pfm_id',
                'sam.parameter',
                'sam.control_type',
                'sam.options',
                'sa.parameter_value',
                'sa.term_id'
            )
            ->where('sam.class_id', $classId)
            ->where('sam.academic_yr', $academicYr)
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $parentfeedback = $rawparentfeedback->groupBy('pfm_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->parameter_value;
                }
            }

            return [
                'pfm_id' => $first->pfm_id,
                'parameter' => $first->parameter,
                'control_type' => $first->control_type,
                'options' => json_decode($first->options, true), // decode JSON
                'parameter_values' => $termValues
            ];
        })->values();
        $rawclassteacherremark = DB::table('hpc_remark_master as sam')
            ->leftJoin('student_hpc_remarks as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.hpc_remark_master_id', '=', 'sa.hpc_remark_master_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.academic_yr', $academicYr);
            })
            ->select(
                'sam.hpc_remark_master_id',
                'sam.remark_head',
                'sa.term_id',
                'sa.remark'
            )
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $classteacherremark = $rawclassteacherremark->groupBy('hpc_remark_master_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->remark;
                }
            }

            return [
                'hpc_remark_master_id' => $first->hpc_remark_master_id,
                'parameter' => $first->remark_head,
                'parameter_values' => $termValues
            ];
        })->values();
        // dd($studentDomains); 
        //  return view('hpcreportcard.sacsstd2hpcreportcard', compact('studentdata','data','subjectsGrouped','results','peerfeedback','parentfeedback','classteacherremark'));
        if (strtolower($studentdata->classname ?? '') == '2') {
            $pdf = PDF::loadView('hpcreportcard.sacsstd2hpcreportcard', compact('studentdata', 'data', 'subjectsGrouped', 'results', 'peerfeedback', 'parentfeedback', 'classteacherremark'));
        } elseif (strtolower($studentdata->classname ?? '') == '1') {
            $pdf = PDF::loadView('hpcreportcard.sacsstd1hpcreportcard', compact('studentdata', 'data', 'subjectsGrouped', 'results', 'peerfeedback', 'parentfeedback', 'classteacherremark'));
        } elseif (strtolower($studentdata->classname ?? '') == 'nursery') {
            $pdf = PDF::loadView('hpcreportcard.sacsnurseryhpcreportcard', compact('studentdata', 'data', 'subjectsGrouped', 'results', 'peerfeedback', 'parentfeedback', 'classteacherremark'));
        } elseif (strtolower($studentdata->classname ?? '') == 'lkg') {
            $pdf = PDF::loadView('hpcreportcard.sacslkghpcreportcard', compact('studentdata', 'data', 'subjectsGrouped', 'results', 'peerfeedback', 'parentfeedback', 'classteacherremark'));
        } elseif (strtolower($studentdata->classname ?? '') == 'ukg') {
            $pdf = PDF::loadView('hpcreportcard.sacsukghpcreportcard', compact('studentdata', 'data', 'subjectsGrouped', 'results', 'peerfeedback', 'parentfeedback', 'classteacherremark'));
        } else {
        }
        $dynamicFilename = $studentdata->first_name . "_" . $studentdata->last_name . "_HPC.pdf";
        //  return $pdf->stream($dynamicFilename);
        return $pdf->download($dynamicFilename);
    }

    public function saveClassTeacherRemarkMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $id = DB::table('hpc_remark_master')->insertGetId([
            'remark_head'   => $request->remark_head
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'Save class teacher remark master saved successfully.',
            'data'    => DB::table('hpc_remark_master')->where('hpc_remark_master_id', $id)->first(),
            'success' => true
        ]);
    }

    public function getClassTeacherRemarkMaster(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $records = DB::table('hpc_remark_master')
            ->get();

        return response()->json([
            'status'  => 200,
            'data'    => $records,
            'message' => 'Class teacher remark master listing.',
            'success' => true
        ]);
    }

    public function updateClassTeacherRemarkMaster(Request $request, $id)
    {
        $record = DB::table('hpc_remark_master')->where('hpc_remark_master_id', $id)->first();

        if (!$record) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        DB::table('hpc_remark_master')
            ->where('hpc_remark_master_id', $id)
            ->update([
                'remark_head'   => $request->remark_head
            ]);

        $updated = DB::table('hpc_remark_master')->where('hpc_remark_master_id', $id)->first();

        return response()->json([
            'status'  => 200,
            'message' => 'Class teacher remark master updated successfully',
            'data'    => $updated,
            'success' => true
        ]);
    }

    public function deleteClassTeacherRemarkMaster(Request $request, $id)
    {
        $exists = DB::table('student_hpc_remarks')
            ->where('hpc_remark_master_id', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 422,
                'message' => 'Cannot delete. Record already used in student hpc remarks.',
                'success' => false
            ]);
        }
        DB::table('hpc_remark_master')->where('hpc_remark_master_id', $id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Record deleted successfully',
            'success' => true
        ]);
    }

    public function getClassTeacherRemark(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;

        // Get master parameters
        $parameters = DB::table('hpc_remark_master')
            ->get();

        // Get students in class/section
        $students = DB::select("
            select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name 
            from student a 
            left join parent b on a.parent_id=b.parent_id 
            join user_master c on a.parent_id = c.reg_id 
            join class d on a.class_id=d.class_id 
            join section e on a.section_id=e.section_id 
            left join house f on f.house_id=a.house 
            where a.IsDelete='N' 
              and a.academic_yr=? 
              and a.class_id=? 
              and a.section_id=? 
              and c.role_id='P' 
            order by a.roll_no,a.reg_no", [$academic_yr, $class_id, $section_id]);




        $results = [];
        foreach ($students as $stu) {
            $stu_assessments = [];
            foreach ($parameters as $param) {
                $value = DB::table('student_hpc_remarks')
                    ->where('student_id', $stu->student_id)
                    ->where('hpc_remark_master_id', $param->hpc_remark_master_id)
                    ->where('academic_yr', $academic_yr)
                    ->where('term_id', $term_id)
                    ->value('remark');

                $stu_assessments[] = [
                    'hpc_remark_master_id' => $param->hpc_remark_master_id,
                    'parameter_name' => $param->remark_head,
                    'value'          => $value ?? ''
                ];
            }

            $results[] = [
                'student_id'   => $stu->student_id,
                'roll_no'      => $stu->roll_no,
                'student_name' => $stu->first_name . ' ' . $stu->last_name,
                'assessments'  => $stu_assessments
            ];
        }

        return response()->json([
            'status'     => 200,
            'parameters' => $parameters,
            'students'   => $results,
            'message'    => 'Class teacher remark.',
            'success'    => true
        ]);
    }

    public function saveClassTeacherRemark(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id   = $request->class_id;
        $section_id = $request->section_id;
        $term_id    = $request->term_id;
        $assessments = $request->assessments;

        foreach ($assessments as $item) {
            DB::table('student_hpc_remarks')
                ->where('student_id', $item['student_id'])
                ->where('term_id', $term_id)
                ->where('hpc_remark_master_id', $item['hpc_remark_master_id'])
                ->where('academic_yr', $academic_yr)
                ->delete();


            DB::table('student_hpc_remarks')->insert([
                'student_id'      => $item['student_id'],
                'term_id'          => $term_id,
                'academic_yr'     => $academic_yr,
                'hpc_remark_master_id' => $item['hpc_remark_master_id'],
                'remark' => $item['value']
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Class teacher remark saved successfully.',
            'success' => true
        ]);
    }

    public function getAllAboutMeMasterByClassId(Request $request)
    {

        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $records = DB::table('allaboutme_master')
            ->join('class', 'class.class_id', '=', 'allaboutme_master.class_id')
            ->where('allaboutme_master.academic_yr', $academicYr)
            ->where('allaboutme_master.class_id', $class_id)
            ->select('allaboutme_master.*', 'class.name as classname')
            ->get();

        return response()->json([
            'status'  => 200,
            'data'    => $records,
            'message' => 'All about me master by classid listing.',
            'success' => true
        ]);
    }

    function getHSCClassesOfADepartment(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $dep_name =     'Higher Secondary';
        $classesdata =  getClassesOfADepartment($dep_name);

        return response()->json([
            'status'  => 200,
            'data'    => $classesdata,
            'message' => 'Classes by department.',
            'success' => true
        ]);
    }

    public function getClassesOfClassTeacher(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $teacher_id = $request->input('teacher_id');
        $classes = DB::table('class_teachers')
            ->join('class', 'class_teachers.class_id', '=', 'class.class_id')
            ->join('section', 'class_teachers.section_id', '=', 'section.section_id')
            ->select(
                'class.name as classname',
                'section.name as sectionname',
                'class_teachers.class_id',
                'class_teachers.section_id',
                DB::raw('1 as is_class_teacher')
            )
            ->where('class_teachers.teacher_id', $teacher_id)
            ->where('class_teachers.academic_yr', $academicYr)
            ->orderBy('class_teachers.section_id')
            ->get();
        return response()->json([
            'status'  => 200,
            'data'    => $classes,
            'message' => 'Classes by class teacher.',
            'success' => true
        ]);
    }

    public function getAllAboutMeByStudentId(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $studentId = $request->input('student_id');
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();

        $classId   = $request->input('class_id');
        $sectionId = $studentDetails->section_id;
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();
        $studentData = DB::table('student')
            ->leftJoin('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('class', 'student.class_id', '=', 'class.class_id')
            ->join('section', 'student.section_id', '=', 'section.section_id')
            ->where('student.student_id', $studentId)
            ->select(
                'student.student_id',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'student.dob',
                'class.name as classname',
                'section.name as sectionname',
                'student.image_name',
                'parent.family_image_name'
            )
            ->first();

        if (!$studentData) {
            return response()->json([
                'status' => false,
                'message' => 'Student not found',
                'data' => null
            ], 404);
        }


        $allAboutMe = DB::table('allaboutme_master')
            ->leftJoin('student_allaboutme_details', function ($join) use ($studentId) {
                $join->on('allaboutme_master.am_id', '=', 'student_allaboutme_details.am_id')
                    ->where('student_allaboutme_details.student_id', '=', $studentId)
                    ->where('student_allaboutme_details.publish', '=', 'Y');
            })
            ->where('allaboutme_master.class_id', $classId)
            ->select(
                'allaboutme_master.am_id',
                'allaboutme_master.name',
                'student_allaboutme_details.aboutme_value',
                'student_allaboutme_details.publish'
            )
            ->get();

        $termList = DB::table('term')->whereIn('term_id', $publishedTerms)->get();
        $termDates = get_term_dates($academicYr, count($termList)) ?? [];
        $attendanceData = [];

        foreach ($termDates as $index => $term) {
            $present = get_total_stu_attendance($studentId, $term['from'], $term['to'], $academicYr);
            $working = get_total_stu_workingdays($studentId, $term['from'], $term['to'], $academicYr);

            $attendanceData[] = [
                'term' => 'Term ' . ($index + 1),
                'from' => $term['from'],
                'to'   => $term['to'],
                'present' => $present,
                'working' => $working,
            ];
        }
        $globalVariables = App::make('global_variables');
        $baseUrl = $globalVariables['codeigniter_app_url'];

        $studentImage = $studentData->image_name
            ? $baseUrl . 'uploads/student_image/' . $studentData->image_name
            : null;

        $familyImage = $studentData->family_image_name
            ? $baseUrl . 'uploads/family_image/' . $studentData->family_image_name
            : null;


        $data = [
            'student' => [
                'student_id' => $studentData->student_id,
                'first_name' => $studentData->first_name,
                'mid_name' => $studentData->mid_name,
                'last_name' => $studentData->last_name,
                'dob' => $studentData->dob,
                'classname' => $studentData->classname,
                'sectionname' => $studentData->sectionname,
                'studentimage' => $studentImage,
                'familyimage' => $familyImage,
            ],
            'attendance' => $attendanceData,
            'allAboutMe' => $allAboutMe
        ];

        return response()->json([
            'status' => 200,
            'message' => 'Student All About Me data fetched successfully',
            'data' => $data,
            'success' => true
        ]);
    }

    public function getDomainDetailsByStudentId(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $studentId = $request->input('student_id');
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();
        $classId = $studentDetails->class_id;
        $sectionId = $studentDetails->section_id;
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();
        $studentDomains = DB::table('domain_master as dm')
            ->leftjoin('HPC_subject_master as sm', 'sm.hpc_sm_id', '=', 'dm.HPC_sm_id')
            ->join('domain_parameter_details as dpd', 'dpd.dm_id', '=', 'dm.dm_id')
            ->leftJoin('domain_competencies as dc', 'dc.dm_competency_id', '=', 'dpd.dm_competency_id')
            ->leftJoin('student_domain_details as sdd', function ($join) use ($studentId, $academicYr) {
                $join->on('sdd.dm_id', '=', 'dpd.dm_id')
                    ->on('sdd.parameter_id', '=', 'dpd.parameter_id')
                    ->where('sdd.student_id', '=', $studentId)
                    ->where('sdd.academic_yr', '=', $academicYr)
                    ->where('sdd.publish', '=', 'Y');
            })
            ->where('dm.class_id', $classId)
            ->select(
                'sm.hpc_sm_id',
                'dm.class_id',
                'sm.name as subjectname',
                'dm.name as domainname',
                'dm.curriculum_goal',
                'dc.name as competency',
                'dpd.learning_outcomes',
                'sdd.parameter_value',
                'sdd.term_id',
                'sdd.publish',
            )

            ->orderBy('sm.hpc_sm_id')
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        // dd($studentDomains);
        $terms = DB::table('term')->pluck('name', 'term_id');

        $subjectsGrouped = $studentDomains->groupBy(function ($item) {
            return $item->subjectname . '||' . $item->domainname;
        })->map(function ($subjectItems) use ($terms) {
            $first = $subjectItems->first();

            // Group by competency
            $competenciesGrouped = $subjectItems->groupBy('competency')->map(function ($compItems) use ($terms) {
                // Group learning outcomes
                $details = $compItems->groupBy('learning_outcomes')->map(function ($outcomeItems) use ($terms) {
                    $termValues = [];
                    foreach ($outcomeItems as $item) {
                        if ($item->term_id) {
                            $termName = $item->term_id;
                            $termValues[$termName] = $item->parameter_value;
                        }
                    }
                    return [
                        'learning_outcomes' => $outcomeItems->first()->learning_outcomes,
                        'parameter_value' => $termValues
                    ];
                })->values();

                return [
                    'competency' => $compItems->first()->competency,
                    'details' => $details
                ];
            })->values();

            return [
                'subjectname' => $first->subjectname,
                'domainname' => $first->domainname,
                'curriculum_goal' => $first->curriculum_goal,
                'competencies' => $competenciesGrouped
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'message' => 'Student domain details.',
            'data' => $subjectsGrouped,
            'success' => true
        ]);
    }

    public function getSelfAssessmentByStudentId(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $studentId = $request->input('student_id');
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();
        $classId = $studentDetails->class_id;
        $sectionId = $studentDetails->section_id;
        // dd($classId,$sectionId);
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();


        $rawResults = DB::table('self_assessment_master as sam')
            ->leftJoin('self_assessment as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.sam_id', '=', 'sa.sam_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.class_id', $classId)
                    ->where('sa.academic_yr', $academicYr)
                    ->where('sa.publish', 'Y');
            })
            ->select(
                'sam.sam_id',
                'sam.parameter',
                'sam.control_type',
                'sam.options',
                'sa.parameter_value',
                'sa.term_id'
            )
            ->where('sam.class_id', $classId)
            ->where('sam.academic_yr', $academicYr)
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $results = $rawResults->groupBy('sam_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->parameter_value;
                }
            }

            return [
                'sam_id' => $first->sam_id,
                'parameter' => $first->parameter,
                'control_type' => $first->control_type,
                'options' => json_decode($first->options, true),
                'parameter_values' => $termValues
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'message' => 'Student self assessment details.',
            'data' => $results,
            'success' => true
        ]);
    }

    public function getPeerFeedbackByStudentId(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $studentId = $request->input('student_id');
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();
        $classId = $studentDetails->class_id;
        $sectionId = $studentDetails->section_id;
        // dd($classId,$sectionId);
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();


        $rawResults = DB::table('peer_feedback_master as sam')
            ->leftJoin('peer_feedback as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.pfm_id', '=', 'sa.pfm_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.class_id', $classId)
                    ->where('sa.academic_yr', $academicYr)
                    ->where('sa.publish', 'Y');
            })
            ->select(
                'sam.pfm_id',
                'sam.parameter',
                'sam.control_type',
                'sam.options',
                'sa.parameter_value',
                'sa.term_id'
            )
            ->where('sam.class_id', $classId)
            ->where('sam.academic_yr', $academicYr)
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $results = $rawResults->groupBy('pfm_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->parameter_value;
                }
            }

            return [
                'pfm_id' => $first->pfm_id,
                'parameter' => $first->parameter,
                'control_type' => $first->control_type,
                'options' => json_decode($first->options, true), // decode JSON
                'parameter_values' => $termValues
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'message' => 'Student peer feedback details.',
            'data' => $results,
            'success' => true
        ]);
    }

    public function getParentFeedbackByStudentId(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $studentId = $request->input('student_id');
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();
        $classId = $studentDetails->class_id;
        $sectionId = $studentDetails->section_id;
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();


        $rawResults = DB::table('parent_feedback_master as sam')
            ->leftJoin('parent_feedback as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.pfm_id', '=', 'sa.pfm_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.class_id', $classId)
                    ->where('sa.academic_yr', $academicYr)
                    ->where('sa.publish', 'Y');
            })
            ->select(
                'sam.pfm_id',
                'sam.parameter',
                'sam.control_type',
                'sam.options',
                'sa.parameter_value',
                'sa.term_id'
            )
            ->where('sam.class_id', $classId)
            ->where('sam.academic_yr', $academicYr)
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $results = $rawResults->groupBy('pfm_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->parameter_value;
                }
            }

            return [
                'pfm_id' => $first->pfm_id,
                'parameter' => $first->parameter,
                'control_type' => $first->control_type,
                'options' => json_decode($first->options, true), // decode JSON
                'parameter_values' => $termValues
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'message' => 'Student peer feedback details.',
            'data' => $results,
            'success' => true
        ]);
    }

    public function getClassTeacherRemarkByStudentId(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $studentId = $request->input('student_id');
        $studentDetails = DB::table('student')->where('student_id', $studentId)->first();
        $classId = $studentDetails->class_id;
        $sectionId = $studentDetails->section_id;
        $publishedTerms = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('publish', 'Y')
            ->pluck('term_id')
            ->toArray();
        $rawResults = DB::table('hpc_remark_master as sam')
            ->leftJoin('student_hpc_remarks as sa', function ($join) use ($studentId, $classId, $academicYr) {
                $join->on('sam.hpc_remark_master_id', '=', 'sa.hpc_remark_master_id')
                    ->where('sa.student_id', $studentId)
                    ->where('sa.academic_yr', $academicYr);
            })
            ->select(
                'sam.hpc_remark_master_id',
                'sam.remark_head',
                'sa.term_id',
                'sa.remark'
            )
            ->get()
            ->filter(function ($item) use ($publishedTerms) {
                return is_null($item->term_id) || in_array($item->term_id, $publishedTerms);
            });
        $results = $rawResults->groupBy('hpc_remark_master_id')->map(function ($items) {
            $first = $items->first();
            $termValues = [];
            foreach ($items as $row) {
                if ($row->term_id) {
                    $termValues[$row->term_id] = $row->remark;
                }
            }

            return [
                'hpc_remark_master_id' => $first->hpc_remark_master_id,
                'parameter' => $first->remark_head,
                'parameter_values' => $termValues
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'message' => 'Student class teachers remark details.',
            'data' => $results,
            'success' => true
        ]);
    }

    public function getHpcClasses(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $hpcclasses = DB::table('hpc_classes')
            ->join('class', 'class.class_id', '=', 'hpc_classes.class_id')
            ->where('hpc_classes.academic_yr', $academicYr)
            ->select('class.class_id', 'class.name as classname')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Hpc classes.',
            'data' => $hpcclasses,
            'success' => true
        ]);
    }

    public function getHpcReportCardPublishValue(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $termId = $request->input('term_id');
        $hpc_report_card_publish = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('term_id', $termId)
            ->first();
        $publish = $hpc_report_card_publish ? $hpc_report_card_publish->publish : 'N';
        return response()->json([
            'status' => 200,
            'message' => 'Publish details.',
            'data' => $publish,
            'success' => true
        ]);
    }

    public function saveHpcReportCardPublishValue(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $termId = $request->input('term_id');
        $publish = $request->input('publish');
        $existing = DB::table('hpc_report_card_publish')
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('term_id', $termId)
            ->first();

        if ($existing) {
            // Update existing record
            DB::table('hpc_report_card_publish')
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('term_id', $termId)
                ->update([
                    'publish'      => $publish
                ]);
        } else {
            // Insert new record
            DB::table('hpc_report_card_publish')->insert([
                'class_id'     => $classId,
                'section_id'   => $sectionId,
                'term_id'      => $termId,
                'publish'      => $publish
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Report card status saved.',
            'success' => true
        ]);
    }

    public function getSubjectByClass(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $classes = get_class_of_classteacher($user->reg_id, $academicYr);
        // dd($classes);
        $isClassTeacher = collect($classes)->contains(function ($class) use ($classId, $sectionId) {
            return $class->class_id == $classId && $class->section_id == $sectionId;
        });
        if ($isClassTeacher) {
            //  dd("Hello");
            // Case 1: Class teacher → subjects by class
            $subjects = get_subjects_by_class($classId, $academicYr);
        } else {
            // Case 2: Subject teacher → subjects by teacher
            $subjects = get_subjects_for_teacher($classId, $sectionId, $user->reg_id, $academicYr);
        }

        // Prepare response
        $data = [];
        foreach ($subjects as $row) {
            $data[] = [
                'sub_rc_master_id' => $row->sub_rc_master_id,
                'name' => $row->name,
            ];
        }

        return response()->json([
            'status' => 200,
            'data' => $data,
            'message' => 'Subjects for the report card marks.',
            'success' => true
        ]);
    }

    public function getExamsByClassSubject(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $classId = $request->input('class_id');
        $subjectrcId =  $request->input('sub_rc_master_id');
        $exams = get_exams_by_class_subject($classId, $subjectrcId, $academicYr);
        return response()->json([
            'status' => 200,
            'data' => $exams,
            'message' => 'Exams for the classes.',
            'success' => true
        ]);
    }

    public function getMarksHeadingClass(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $classId = $request->input('class_id');
        $subjectrcId =  $request->input('sub_rc_master_id');
        $examId =  $request->input('exam_id');
        $marks_headings = get_marks_heading_class($classId, $subjectrcId, $examId, $academicYr);
        return response()->json([
            'status' => 200,
            'data' => $marks_headings,
            'message' => 'Marks heading according to the class_id,subject_id,exam_id.',
            'success' => true
        ]);
    }

    public function updatePublishStudentMarks(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $examId     = $request->input('exam_id');
        $classId    = $request->input('class_id');
        $sectionId  = $request->input('section_id');
        $subjectId  = $request->input('subject_id');

        // 1. Check if marks exist
        $exists = DB::table('student_marks')
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->exists();

        if (! $exists) {
            return response()->json([
                'status'  => 404,
                'message' => 'Please enter the marks first.',
                'success' => false,
            ]);
        }

        DB::table('student_marks')
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->update(['publish' => 'Y']);

        return response()->json([
            'status'  => 200,
            'message' => 'Marks published successfully!',
            'success' => true,
        ]);
    }

    public function deleteStudentMarks(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $examId     = $request->input('exam_id');
        $classId    = $request->input('class_id');
        $sectionId  = $request->input('section_id');
        $subjectId  = $request->input('subject_id');

        $deleted = DB::table('student_marks')
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->where('academic_yr', $academicYr)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'status'  => 404,
                'message' => 'No marks found to delete for given criteria.',
                'success' => false,
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Marks deleted successfully.',
            'success' => true,
        ]);
    }

    public function getStudentMarks(Request $request)
    {
        $user = $this->authenticateUser();
        $class_id   = $request->input('class_id');
        $section_id = $request->input('section_id');
        $subject_id = $request->input('subject_id');
        $exam_id    = $request->input('exam_id');
        $acd_yr     = JWTAuth::getPayload()->get('academic_year');
        $open_day = get_open_day($exam_id);
        // Get class name
        $class_name = DB::table('class')->where('class_id', $class_id)->value('name');

        $student_marks = [];

        if (in_array($class_name, [11, 12])) {
            $student_marks = DB::table(DB::raw("(
                SELECT b.student_id, b.first_name, b.mid_name, b.last_name, b.roll_no, b.reg_no, a.marks_id, a.present, a.mark_obtained, a.highest_marks, a.comment
                FROM student_marks a
                JOIN student b ON a.student_id = b.student_id
                WHERE b.class_id = ? AND b.section_id = ? AND a.subject_id = ? AND a.exam_id = ? AND a.academic_yr = ? AND b.IsDelete = 'N'
                
                UNION
    
                SELECT a.student_id, a.first_name, a.mid_name, a.last_name, a.roll_no, a.reg_no, NULL AS marks_id, NULL AS present, NULL AS mark_obtained, NULL AS highest_marks, NULL AS comment
                FROM student a
                JOIN view_hsc_student_rc_subjects b ON a.student_id = b.student_id
                WHERE a.class_id = ? AND a.section_id = ? AND b.sub_rc_master_id = ? AND a.IsDelete = 'N'
                AND a.student_id NOT IN (
                    SELECT student_id 
                    FROM student_marks 
                    WHERE class_id = ? AND subject_id = ? AND exam_id = ?
                )
            ) as x"))
                ->setBindings([$class_id, $section_id, $subject_id, $exam_id, $acd_yr, $class_id, $section_id, $subject_id, $class_id, $subject_id, $exam_id])
                ->orderBy('roll_no')
                ->orderBy('reg_no')
                ->orderBy('student_id')
                ->get();

            // If no marks found, get HSC students
            if ($student_marks->isEmpty()) {
                $student_marks = DB::table('student as a')
                    ->join('parent as b', 'a.parent_id', '=', 'b.parent_id')
                    ->join('view_hsc_student_rc_subjects as c', 'a.student_id', '=', 'c.student_id')
                    ->select('a.*', 'b.father_name')
                    ->where('a.IsDelete', 'N')
                    ->where('a.academic_yr', $acd_yr)
                    ->where('a.class_id', $class_id)
                    ->where('a.section_id', $section_id)
                    ->where('c.sub_rc_master_id', $subject_id)
                    ->orderBy('a.roll_no')
                    ->orderBy('a.reg_no')
                    ->orderBy('a.student_id')
                    ->get();
            }
        } else {
            // Regular class: get marks with fallback
            $student_marks = DB::table(DB::raw("(
                SELECT b.student_id, b.first_name, b.mid_name, b.last_name, b.roll_no, b.reg_no, a.marks_id, a.present, a.mark_obtained, a.highest_marks, a.comment
                FROM student_marks a
                JOIN student b ON a.student_id = b.student_id
                WHERE b.class_id = ? AND b.section_id = ? AND a.subject_id = ? AND a.exam_id = ? AND a.academic_yr = ? AND b.IsDelete = 'N'
                
                UNION
    
                SELECT student_id, first_name, mid_name, last_name, roll_no, reg_no, NULL AS marks_id, NULL AS present, NULL AS mark_obtained, NULL AS highest_marks, NULL AS comment
                FROM student
                WHERE class_id = ? AND section_id = ? AND IsDelete = 'N'
                AND student_id NOT IN (
                    SELECT student_id
                    FROM student_marks
                    WHERE class_id = ? AND section_id = ? AND subject_id = ? AND exam_id = ?
                )
            ) as x"))
                ->setBindings([$class_id, $section_id, $subject_id, $exam_id, $acd_yr, $class_id, $section_id, $class_id, $section_id, $subject_id, $exam_id])
                ->orderBy('roll_no')
                ->orderBy('reg_no')
                ->orderBy('student_id')
                ->get();

            // If no marks found, get all students
            if ($student_marks->isEmpty()) {
                $student_marks = DB::table('student as a')
                    ->leftJoin('parent as b', 'a.parent_id', '=', 'b.parent_id')
                    ->join('user_master as c', 'a.parent_id', '=', 'c.reg_id')
                    ->join('class as d', 'a.class_id', '=', 'd.class_id')
                    ->join('section as e', 'a.section_id', '=', 'e.section_id')
                    ->leftJoin('house as f', 'a.house', '=', 'f.house_id')
                    ->select('a.*', 'b.*', 'c.user_id', 'd.name as class_name', 'e.name as sec_name', 'f.house_name')
                    ->where('a.IsDelete', 'N')
                    ->where('a.academic_yr', $acd_yr)
                    ->where('a.class_id', $class_id)
                    ->where('a.section_id', $section_id)
                    ->where('c.role_id', 'P')
                    ->orderBy('a.roll_no')
                    ->orderBy('a.reg_no')
                    ->get();
            }
        }

        // Attach highest marks for each mark heading if needed
        foreach ($student_marks as $student) {
            // Example: attach highest_marks array for frontend
            $student->highest_marks_array = []; // populate as needed using DB query
        }

        return response()->json([
            'status' => 200,
            'open_day' => $open_day,
            'data' => $student_marks,
            'message' => 'Student marks.',
            'success' => true
        ]);
    }

    public function saveStudentMarks(Request $request)
    {
        $user = $this->authenticateUser();
        $userId = $user->reg_id;
        $academicYr = JWTAuth::getPayload()->get('academic_year');
        $examId = $request->input('exam_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $subjectId = $request->input('subject_id');
        $studentIds = $request->input('student_id');
        $marksIds = $request->input('marks_id', []);

        $marksHeadings = DB::select("SELECT allot_mark_headings.*,marks_headings.marks_headings_id,marks_headings.name as marks_headings_name,subjects_on_report_card_master.* FROM allot_mark_headings JOIN subjects_on_report_card_master ON allot_mark_headings.sm_id= subjects_on_report_card_master.sub_rc_master_id JOIN marks_headings on allot_mark_headings.marks_headings_id= marks_headings.marks_headings_id WHERE allot_mark_headings.class_id = " . $classId . " AND allot_mark_headings.sm_id = " . $subjectId . " AND allot_mark_headings.exam_id = " . $examId . " and allot_mark_headings.academic_yr = '" . $academicYr . "' order by marks_headings.sequence");
        // dd($marksHeadings);

        foreach ($studentIds as $i => $studentId) {
            $verifyMasterData = [
                'exam_id' => $examId,
                'class_id' => $classId,
                'academic_yr' => $academicYr,
                'subject_id' => $subjectId,
                'student_id' => $studentId,
            ];

            $existingMarks = DB::table('student_marks')->where($verifyMasterData)->first();

            $presentData = [];
            $marksObtainedData = [];
            $highestMarksData = [];
            $marksBeforeChangeData = [];
            $marksAfterChangeData = [];
            $reportcardMarksData = [];
            $reportcardHighestMarksData = [];

            $totalReportcardMarksObtained = 0;
            $totalReportcardHighestMarks = 0;

            foreach ($marksHeadings as $heading) {
                $id = $heading->marks_headings_id;

                $markBeforeChange = $request->input("mark_before_change_$id")[$i] ?? null;
                $markObtained = $request->input("mark_obtained_$id")[$i] ?? null;
                $highestMarks = $request->input("highest_marks_$id")[$i] ?? null;
                $present = $request->input("present_{$id}_$studentId") === 'Y' ? 'Y' : 'N';

                $presentData[$id] = $present;
                $marksObtainedData[$id] = $markObtained;
                $highestMarksData[$id] = $highestMarks;

                if ($markObtained != $markBeforeChange) {
                    $marksBeforeChangeData[$id] = $markBeforeChange;
                    $marksAfterChangeData[$id] = $markObtained;
                }
                // dd($markBeforeChange,$markObtained,$highestMarks,$present);
                // Calculate reportcard marks (simplified: adapt rules for each class)
                if ($present == 'N') {
                    $reportcardMarks = 'Ab';
                } else {
                    $reportcardMarks = $markObtained;
                    $totalReportcardMarksObtained += $reportcardMarks;
                }
                $reportcardHighestMarks = $highestMarks;
                $totalReportcardHighestMarks += $reportcardHighestMarks;

                $reportcardMarksData[$heading->marks_headings_name] = $reportcardMarks;
                $reportcardHighestMarksData[$heading->marks_headings_name] = $reportcardHighestMarks;
            }

            $percent = $totalReportcardHighestMarks > 0
                ? ($totalReportcardMarksObtained * 100) / $totalReportcardHighestMarks
                : 0;

            // Replace with your own grade logic
            // $grade = $this->getGrade($percent, $classId, $subjectId);

            $marksData = [
                'exam_id' => $examId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'academic_yr' => $academicYr,
                'subject_id' => $subjectId,
                'student_id' => $studentId,
                'present' => json_encode($presentData),
                'mark_obtained' => json_encode($marksObtainedData),
                'highest_marks' => json_encode($highestMarksData),
                'reportcard_marks' => json_encode($reportcardMarksData),
                'reportcard_highest_marks' => json_encode($reportcardHighestMarksData),
                'total_marks' => $totalReportcardMarksObtained,
                'highest_total_marks' => $totalReportcardHighestMarks,
                'percent' => $percent,
                // 'grade' => $grade,
                'comment' => $comments[$i] ?? '',
                'data_entry_by' => $userId,
                'date' => Carbon::now()->toDateString(),
                'publish' => 'N',
            ];

            if ($existingMarks) {

                DB::table('student_marks')->where('marks_id', $marksIds[$i])->update($marksData);

                // Log changes if marks changed and already published
                if (!empty($marksBeforeChangeData) && $existingMarks->publish == 'Y') {
                    DB::table('marks_changed_log')->insert([
                        'exam_id' => $examId,
                        'subject_id' => $subjectId,
                        'student_id' => $studentId,
                        'mark_obtained_before' => json_encode($marksBeforeChangeData),
                        'mark_obtained_after' => json_encode($marksAfterChangeData),
                        'date_of_change' => Carbon::now()->toDateString(),
                        'changed_by' => $userId,
                        'academic_yr' => $academicYr,
                    ]);
                }
            } else {
                DB::table('student_marks')->insert($marksData);
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Marks saved successfully.',
            'success' => true
        ]);
    }

    public function getMarksGenerateCsv(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = JWTAuth::getPayload()->get('academic_year');

        $classId   = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $subjectId = $request->input('subject_id');
        $examId    = $request->input('exam_id');

        if (empty($classId) || empty($sectionId)) {
            return response()->json(['error' => 'Class ID and Section ID are required'], 400);
        }

        // === Get names for filename ===
        $className   = DB::table('class')->where('class_id', $classId)->value('name');
        $sectionName = DB::table('section')->where('section_id', $sectionId)->value('name');
        $subjectName = DB::table('subjects_on_report_card_master')->where('sub_rc_master_id', $subjectId)->value('name');
        $examName    = DB::table('exam')->where('exam_id', $examId)->value('name');

        $filename = $className . $sectionName . "_" .
            str_replace([' ', '/'], '', $subjectName) . "_" .
            str_replace(' ', '', $examName) . ".csv";

        // === Fetch marks headings ===
        $marksHeadings = DB::select("SELECT allot_mark_headings.*,marks_headings.marks_headings_id,marks_headings.name as marks_headings_name,subjects_on_report_card_master.* FROM allot_mark_headings JOIN subjects_on_report_card_master ON allot_mark_headings.sm_id= subjects_on_report_card_master.sub_rc_master_id JOIN marks_headings on allot_mark_headings.marks_headings_id= marks_headings.marks_headings_id WHERE allot_mark_headings.class_id = " . $classId . " AND allot_mark_headings.sm_id = " . $subjectId . " AND allot_mark_headings.exam_id = " . $examId . " and allot_mark_headings.academic_yr = '" . $academicYr . "' order by marks_headings.sequence");

        // === Fetch students ===
        if ($className == 11) {
            $students = DB::select("select a.*,b.father_name from student a, parent b, view_hsc_student_rc_subjects c where a.IsDelete='N' and a.academic_yr='" . $academicYr . "' and a.parent_id=b.parent_id and a.class_id='" . $classId . "' and a.section_id='" . $sectionId . "' and a.student_id=c.student_id and c.sub_rc_master_id=" . $subjectId . " order by a.roll_no,a.reg_no,a.student_id");
        } else {
            $students = DB::select("select a.*,b.*,c.user_id,d.name as class_name,e.name as sec_name,f.house_name from student a left join parent b on a.parent_id=b.parent_id join user_master c on a.parent_id = c.reg_id join class d on a.class_id=d.class_id join section e on a.section_id=e.section_id left join house f on a.house=f.house_id where a.IsDelete='N' and a.academic_yr='" . $academicYr . "'  and a.class_id='" . $classId . "' and a.section_id='" . $sectionId . "' and c.role_id='P' order by a.roll_no,a.reg_no");
        }

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($classId, $sectionId, $subjectId, $examId, $className, $sectionName, $subjectName, $examName, $marksHeadings, $students) {
            $file = fopen('php://output', 'w');

            // === First rows (headers info) ===
            fputcsv($file, [$classId . "/" . $sectionId . "/" . $subjectId . "/" . $examId, $className . $sectionName, $subjectName, $examName]);
            fputcsv($file, ['']); // empty line

            // === Column headings ===
            $headingRow = ['Code', 'Roll No.', 'Name'];
            $highestMarksRow = ['', '', ''];

            foreach ($marksHeadings as $mh) {
                $headingRow[] = $mh->marks_headings_id . '/ ' . $mh->marks_headings_name;
                $highestMarksRow[] = 'Out of-' . $mh->highest_marks;
            }

            fputcsv($file, $headingRow);
            fputcsv($file, $highestMarksRow);

            // === Student rows ===
            foreach ($students as $stu) {
                $fullName = trim($stu->first_name . " " . $stu->mid_name . " " . $stu->last_name);
                $row = [$stu->student_id, $stu->roll_no, $fullName];

                foreach ($marksHeadings as $mh) {
                    $row[] = ''; // empty marks columns
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function saveUploadsMarksCsv(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYr = '2025-2026';
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');

        if (($handle = fopen($file->getRealPath(), "r")) !== false) {
            $c = 1;
            $marks_headings_id_array = [];
            $marks_headings_name_array = [];

            while (($filesop = fgetcsv($handle, 1000, ",")) !== false) {
                if ($c == 1) {
                    $all_ids = trim($filesop[0] ?? '');

                    if ($all_ids == '') {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Please do not delete the contents of cell 0. Use the downloaded format."
                        ], 400);
                    }

                    $all_ids_array = explode('/', $all_ids, 5);
                    $class_id   = $all_ids_array[0] ?? '';
                    $section_id = $all_ids_array[1] ?? '';
                    $subject_id = $all_ids_array[2] ?? '';
                    $exam_id    = $all_ids_array[3] ?? '';

                    if ($class_id == '' || $section_id == '' || $subject_id == '' || $exam_id == '') {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Please do not change the contents of cell 0. Use the downloaded format."
                        ], 400);
                    }

                    $acd_yr = $academicYr;
                }

                if ($c == 3) {
                    $marks_headings_count = count($filesop) - 3;

                    for ($i = 0; $i < $marks_headings_count; $i++) {
                        if (preg_match("/[0-9]/", $filesop[$i + 3])) {
                            $val = trim($filesop[$i + 3]);

                            $marks_headings_id_array[] = substr($val, 0, strpos($val, '/'));
                            $marks_headings_name_array[] = trim(substr($val, strpos($val, '/') + 2));
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => "Contents of row 3 are changed. Use the downloaded format."
                            ], 400);
                        }
                    }
                }
                if ($c >= 5) {
                    // dd("Hello");
                    $percent = "";
                    $grade = "";
                    $present = "";
                    $student_id = trim($filesop[0]);
                    if ($student_id == '') {
                        return response()->json([
                            'status' => 400,
                            'success' => false,
                            'message' => "Please do not delete the Code for " . $filesop[2] . "."
                        ], 400);
                    } else {
                        $master_data    =    array(
                            'exam_id' => $exam_id,
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'academic_yr' => $acd_yr,
                            'subject_id' => $subject_id,
                            'student_id' => $student_id
                        );
                        // 			dd($master_data);
                        $query = DB::table('student_marks')
                            ->where($master_data)
                            ->get();
                        $class_name = DB::table('class')->where('class_id', $class_id)->value('name'); //Lija for report card
                        $term_id = DB::table('exam')
                            ->where('exam_id', $exam_id)
                            ->value('term_id');

                        $present_string = "{";
                        $marks_obtained_string = "{";
                        $highest_marks_string = "{";
                        $percent_string = "{";
                        //$grade_markheading_wise_string="{";
                        $grade_string = "{";
                        $reportcard_marks_string = "{"; //Lija for report card
                        $reportcard_highest_marks_string = "{"; //Lija for report card

                        //$total_marks_obtained=0;
                        //$total_highest_marks=0;
                        $total_reportcard_marks_obtained = 0;
                        $total_reportcard_highest_marks = 0;
                        $present = "";
                        $percent = "";
                        $grade = "";
                        //$grade_markheading_wise="";
                        $subject_det = DB::table('subjects_on_report_card as a')
                            ->join('subjects_on_report_card_master as b', 'a.sub_rc_master_id', '=', 'b.sub_rc_master_id')
                            ->select('a.subject_type as subject_type', 'b.name as subject_name')
                            ->where('a.class_id', $class_id)
                            ->where('a.sub_rc_master_id', $subject_id)
                            ->get();
                        // dd($subject_det);                 
                        foreach ($subject_det as $sub_row) {
                            //   dd($sub_row);
                            $subject_type = $sub_row->subject_type;
                            $subject_name = $sub_row->subject_name;
                            // dd($subject_type,$subject_name);
                        }

                        for ($i = 0; $i < $marks_headings_count; $i++) {
                            $marks_obtained = trim($filesop[$i + 3]);
                            $marks_headings_id = $marks_headings_id_array[$i];
                            $marks_headings_name = $marks_headings_name_array[$i]; //Lija for report card

                            if ($marks_obtained <> "") {
                                if (is_numeric($marks_obtained) == false) {
                                    // $studentName = $this->crud_model->get_student_name($student_id);

                                    $studentName = DB::table('student')
                                        ->where('student_id', $student_id)
                                        ->value(DB::raw("CONCAT(first_name,' ',mid_name,' ',last_name)"));

                                    // $markHeadingName = $this->assessment_model->get_mark_heading_name($marks_headings_id);

                                    $markHeadingName = DB::table('marks_headings')
                                        ->where('marks_headings_id', $marks_headings_id)
                                        ->value('name');

                                    return response()->json([
                                        'success' => false,
                                        'message' => "Incorrect marks. Please enter a blank or numeric value for $studentName for $markHeadingName."
                                    ], 400);
                                }
                            }
                            if ($marks_obtained <> '') {
                                $present = 'Y';
                            } else {
                                $present = 'N';
                            }

                            $highest_marks = DB::table('allot_mark_headings')
                                ->where('exam_id', $exam_id)
                                ->where('class_id', $class_id)
                                ->where('marks_headings_id', $marks_headings_id)
                                ->where('academic_yr', $acd_yr)
                                ->value('highest_marks');
                            //$total_highest_marks=$total_highest_marks+$highest_marks;

                            if ($marks_obtained > $highest_marks) {
                                // dd($marks_obtained,$highest_marks);
                                // $this->session->set_flashdata('error_message', 'Incorrect marks. Marks entered is greater than the highest marks for ' . $this->crud_model->get_student_name($student_id) . ' for ' . $this->assessment_model->get_mark_heading_name($marks_headings_id));
                                // redirect(base_url() . 'index.php/assessment/student_marks/' . $exam_id . '/' . $class_id . '/' . $section_id . '/' . $subject_id, 'refresh');
                                // return response()->json([
                                //     'success' => false,
                                //     'message' => "Incorrect marks. Marks entered is greater than the highest marks for " . $this->crud_model->get_student_name($student_id) . " for " . $this->assessment_model->get_mark_heading_name($marks_headings_id) . "."
                                // ], 400);

                                $studentName = DB::table('student')
                                        ->where('student_id', $student_id)
                                        ->value(DB::raw("CONCAT(first_name,' ',mid_name,' ',last_name)"));

                                    // $markHeadingName = $this->assessment_model->get_mark_heading_name($marks_headings_id);

                                    $markHeadingName = DB::table('marks_headings')
                                        ->where('marks_headings_id', $marks_headings_id)
                                        ->value('name');

                                    return response()->json([
                                        'success' => false,
                                        'message' => "Incorrect marks. Marks entered is greater than the highest marks for $studentName for $markHeadingName."
                                    ], 400);
                            }


                            /*if($marks_obtained<>""){
									$total_marks_obtained=$total_marks_obtained+(float)$marks_obtained;
									//$percent = $marks_obtained * 100 / $highest_marks;
									//$grade = $this->assessment_model->get_grade_based_on_marks($marks_obtained,$class_id); //Lija for report card
								}*/
                            $present_string = $present_string . '"' . $marks_headings_id . '":"' . $present . '",';
                            $marks_obtained_string = $marks_obtained_string . '"' . $marks_headings_id . '":"' . $marks_obtained . '",';
                            $highest_marks_string = $highest_marks_string . '"' . $marks_headings_id . '":"' . $highest_marks . '",';
                            //$percent_string=$percent_string.'"'.$marks_headings_id.'":"'.$percent.'",';

                            //Calculate repord card marks and set the string //Lija report card

                            $reportcard_marks = "";
                            $reportcard_highest_marks = "";
                            //echo "marks_obtained ".$marks_obtained."<br/>";
                            //echo "highest_marks ".$highest_marks."<br/>";
                            switch ($class_name) {
                                case "Nursery":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        if ($highest_marks == 25) {
                                            if ($marks_obtained <= 25 && $marks_obtained >= 24) {
                                                //echo "msg 1<br/>";
                                                $reportcard_marks = 3;
                                            } elseif ($marks_obtained <= 23 && $marks_obtained >= 15) {
                                                //echo "msg 2<br/>";
                                                $reportcard_marks = 2;
                                            } elseif ($marks_obtained < 15) {
                                                //echo "msg 3<br/>";
                                                $reportcard_marks = 1;
                                            }
                                            //Lija 28-02-22
                                        } elseif ($highest_marks == 15) {
                                            if ($marks_obtained <= 15 && $marks_obtained >= 14) {
                                                $reportcard_marks = 3;
                                            } elseif ($marks_obtained <= 13 && $marks_obtained >= 10) {
                                                $reportcard_marks = 2;
                                            } elseif ($marks_obtained < 10) {
                                                $reportcard_marks = 1;
                                            }
                                        } elseif ($highest_marks == 10) {
                                            if ($marks_obtained <= 10 && $marks_obtained >= 9) {
                                                //echo "msg 4<br/>";
                                                $reportcard_marks = 3;
                                            } elseif ($marks_obtained <= 8 && $marks_obtained >= 6) {
                                                //echo "msg 5<br/>";
                                                $reportcard_marks = 2;
                                            } elseif ($marks_obtained < 6) {
                                                //echo "msg 6<br/>";
                                                $reportcard_marks = 1;
                                            }
                                        } elseif ($highest_marks == 5) {
                                            if ($marks_obtained <= 5 && $marks_obtained >= 4) {
                                                //echo "msg 7<br/>";
                                                $reportcard_marks = 3;
                                            } elseif ($marks_obtained <= 3 && $marks_obtained >= 2) {
                                                //echo "msg 8<br/>";
                                                $reportcard_marks = 2;
                                            } elseif ($marks_obtained < 2) {
                                                //echo "msg 9<br/>";
                                                $reportcard_marks = 1;
                                            }
                                        }
                                    }
                                    $reportcard_highest_marks = 3;
                                    break;

                                case "LKG":
                                    //$reportcard_highest_marks=100;//22-09-22 Lija This was till acd yr 2021-2022
                                    $reportcard_highest_marks = $highest_marks; //22-09-22 Lija This is from acd yr 2022-2023
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        //$reportcard_marks = $marks_obtained * 100 / $highest_marks;//22-09-22 Lija This was till acd yr 2021-2022
                                        $reportcard_marks = $marks_obtained; //22-09-22 Lija This is from acd yr 2022-2023
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                case "UKG":
                                    //$reportcard_highest_marks=100;//22-09-22 Lija This was till acd yr 2021-2022
                                    $reportcard_highest_marks = $highest_marks; //22-09-22 Lija This is from acd yr 2022-2023
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        //$reportcard_marks = $marks_obtained * 100 / $highest_marks;//22-09-22 Lija This was till acd yr 2021-2022
                                        $reportcard_marks = $marks_obtained; //22-09-22 Lija This is from acd yr 2022-2023
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                case "1":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        $reportcard_marks = $marks_obtained;
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $reportcard_highest_marks = $highest_marks;
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                case "2":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        $reportcard_marks = $marks_obtained;
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $reportcard_highest_marks = $highest_marks;
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                case "3":
                                    if ($subject_type == 'Scholastic') {
                                        if ($term_id == 1) { //Lija seperated Term 1 n Term 2 condition 07-12-20
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                //$reportcard_marks = $marks_obtained*2;// for acd_yr 2020-21 //Lija 10-07-21
                                                $reportcard_marks = $marks_obtained; //Lija 10-07-21
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            //$reportcard_highest_marks=$highest_marks*2;// for acd_yr 2020-21 //Lija 10-07-21
                                            $reportcard_highest_marks = $highest_marks; //Lija 10-07-21
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                            //Lija added Term 2 condition 07-12-20
                                        } else {
                                            if ($present == 'N') {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        }
                                    } elseif ($subject_type == 'Co-Scholastic') {
                                        if ($marks_obtained == "") {
                                            $reportcard_marks = 'Ab';
                                        } else {
                                            $reportcard_marks = $marks_obtained;
                                            $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                        }
                                        $reportcard_highest_marks = $highest_marks;
                                        $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    }
                                    break;
                                case "4":
                                    if ($subject_type == 'Scholastic') {
                                        if ($term_id == 1) { //Lija seperated Term 1 n Term 2 condition 07-12-20
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                //$reportcard_marks = $marks_obtained*2;// for acd_yr 2020-21 //Lija 10-07-21
                                                $reportcard_marks = $marks_obtained; //Lija 10-07-21
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            //$reportcard_highest_marks=$highest_marks*2;// for acd_yr 2020-21 //Lija 10-07-21
                                            $reportcard_highest_marks = $highest_marks; //Lija 10-07-21
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                            //Lija added Term 2 condition 07-12-20
                                        } else {
                                            if ($present == 'N') {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        }
                                    } elseif ($subject_type == 'Co-Scholastic') {
                                        if ($marks_obtained == "") {
                                            $reportcard_marks = 'Ab';
                                        } else {
                                            $reportcard_marks = $marks_obtained;
                                            $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                        }
                                        $reportcard_highest_marks = $highest_marks;
                                        $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    }
                                    break;
                                case "5":
                                    if ($subject_type == 'Scholastic') {
                                        if ($term_id == 1) { //Lija seperated Term 1 n Term 2 condition 07-12-20
                                            if ($marks_headings_name == 'Internal') {
                                                if ($marks_obtained == "") {
                                                    $reportcard_marks = 'Ab';
                                                } else {
                                                    $reportcard_marks = $marks_obtained;
                                                    $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                                }
                                                $reportcard_highest_marks = $highest_marks;
                                                $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                            } else {
                                                if ($marks_obtained == "") {
                                                    $reportcard_marks = 'Ab';
                                                } else {
                                                    //$reportcard_marks = $marks_obtained*2;// for acd_yr 2020-21 //Lija 10-07-21
                                                    $reportcard_marks = $marks_obtained; //Lija 10-07-21
                                                    $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                                }
                                                //$reportcard_highest_marks=$highest_marks*2;// for acd_yr 2020-21 //Lija 10-07-21
                                                $reportcard_highest_marks = $highest_marks; //Lija 10-07-21
                                                $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                            }
                                            //Lija added Term 2 condition 07-12-20
                                        } else {
                                            if ($present == 'N') {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        }
                                    } elseif ($subject_type == 'Co-Scholastic') {
                                        if ($marks_obtained == "") {
                                            $reportcard_marks = 'Ab';
                                        } else {
                                            $reportcard_marks = $marks_obtained;
                                            $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                        }
                                        $reportcard_highest_marks = $highest_marks;
                                        $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    }
                                    break;
                                case "6":
                                    //Lija 13-03-21
                                    if ($subject_type == 'Scholastic') {
                                        //if($term_id==1){ //Lija 10-09-21
                                        //Lija term marks was doubled for Term 1 2020-2021
                                        if (($marks_headings_name == 'Term' || $marks_headings_name == 'Practical') && !($subject_name == 'Marathi' || $subject_name == 'Sanskrit')  && $acd_yr == '2020-2021' && $term_id == 1) { //Lija 10-09-21	
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained * 2;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks * 2;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } elseif ($marks_headings_name == 'Periodic Test') {
                                            //07-12-20 Convert periodic marks out of 50 to 10 n save as report card marks

                                            if ($present == 'N') {
                                                $reportcard_marks = 'Ab';
                                            } else {

                                                $reportcard_marks = ($marks_obtained / $highest_marks) * 10;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = 10;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } elseif ($subject_name == 'Computer Applications' || $subject_name == 'Computer') {
                                            if ($marks_obtained == "") {
                                                //print_r("In Absent Term Practical not Marathi, Sanslrit<br/>");
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained * 2;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks * 2;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } else {
                                            //print_r("In else present".$present."<br/>");
                                            if ($marks_obtained == "") {
                                                //print_r("In else absentt<br/>");
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        }
                                        /*}elseif($term_id==2){
												if($subject_name=='Computer Applications'){
													if($marks_obtained==""){
														//print_r("In Absent Term Practical not Marathi, Sanslrit<br/>");
														$reportcard_marks='Ab';
													}else{
														$reportcard_marks = $marks_obtained*2;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=$highest_marks*2;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}elseif($marks_headings_name=='Periodic Test'){
													//07-12-20 Convert periodic marks out of 50 to 10 n save as report card marks
													
													if($present=='N'){
														$reportcard_marks='Ab';
													}else{

														$reportcard_marks = ($marks_obtained/$highest_marks)*10;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=10;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}else{
													//print_r("In else present".$present."<br/>");
													if($marks_obtained==""){
														//print_r("In else absentt<br/>");
														$reportcard_marks='Ab';
													}else{
														$reportcard_marks = $marks_obtained;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=$highest_marks;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}
											}*/
                                    } elseif ($subject_type == 'Co-Scholastic') {

                                        if ($marks_obtained == "") {
                                            $reportcard_marks = 'Ab';
                                        } else {
                                            $reportcard_marks = $marks_obtained * 2;
                                            $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                        }
                                        $reportcard_highest_marks = $highest_marks * 2;
                                        $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    }

                                    break;
                                case "7": //Lija 13-03-21
                                    if ($subject_type == 'Scholastic') {
                                        //if($term_id==1){ //Lija 10-09-21
                                        //Lija term marks was doubled for Term 1 2020-2021
                                        if (($marks_headings_name == 'Term' || $marks_headings_name == 'Practical') && !($subject_name == 'Marathi' || $subject_name == 'Sanskrit')  && $acd_yr == '2020-2021' && $term_id == 1) { //Lija 10-09-21
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained * 2;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks * 2;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } elseif ($marks_headings_name == 'Periodic Test') {
                                            //07-12-20 Convert periodic marks out of 50 to 10 n save as report card marks

                                            if ($present == 'N') {
                                                $reportcard_marks = 'Ab';
                                            } else {

                                                $reportcard_marks = ($marks_obtained / $highest_marks) * 10;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = 10;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } elseif ($subject_name == 'Computer Applications' || $subject_name == 'Computer') {
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained * 2;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks * 2;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } else {
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        }
                                        /*}elseif($term_id==2){
												if($subject_name=='Computer Applications'){
													if($marks_obtained==""){
														$reportcard_marks='Ab';
													}else{
														$reportcard_marks = $marks_obtained*2;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=$highest_marks*2;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}elseif($marks_headings_name=='Periodic Test'){
													//07-12-20 Convert periodic marks out of 50 to 10 n save as report card marks
													
													if($present=='N'){
														$reportcard_marks='Ab';
													}else{

														$reportcard_marks = ($marks_obtained/$highest_marks)*10;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=10;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}else{
													if($marks_obtained==""){
														$reportcard_marks='Ab';
													}else{
														$reportcard_marks = $marks_obtained;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=$highest_marks;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}
											}*/
                                    } elseif ($subject_type == 'Co-Scholastic') {
                                        if ($marks_obtained == "") {
                                            $reportcard_marks = 'Ab';
                                        } else {
                                            $reportcard_marks = $marks_obtained * 2;
                                            $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                        }
                                        $reportcard_highest_marks = $highest_marks * 2;
                                        $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    }
                                    break;
                                case "8": //Lija 13-03-21
                                    if ($subject_type == 'Scholastic') {
                                        //if($term_id==1){ //Lija 10-09-21
                                        //Lija term marks was doubled for Term 1 2020-2021
                                        if (($marks_headings_name == 'Term' || $marks_headings_name == 'Internal') && !($subject_name == 'Marathi' || $subject_name == 'Sanskrit') && $acd_yr == '2020-2021' && $term_id == 1) { //Lija 10-09-21
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained * 2;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks * 2;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } elseif ($marks_headings_name == 'Periodic Test') {
                                            //07-12-20 Convert periodic marks out of 50 to 10 n save as report card marks

                                            if ($present == 'N') {
                                                $reportcard_marks = 'Ab';
                                            } else {

                                                $reportcard_marks = ($marks_obtained / $highest_marks) * 10;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = 10;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } elseif ($subject_name == 'Artificial Intelligence') {
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained * 2;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks * 2;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        } else {
                                            if ($marks_obtained == "") {
                                                $reportcard_marks = 'Ab';
                                            } else {
                                                $reportcard_marks = $marks_obtained;
                                                $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                            }
                                            $reportcard_highest_marks = $highest_marks;
                                            $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                        }
                                        /*}elseif($term_id==2){
												if($subject_name=='Artificial Intelligence'){
													if($marks_obtained==""){
														$reportcard_marks='Ab';
													}else{
														$reportcard_marks = $marks_obtained*2;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=$highest_marks*2;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}elseif($marks_headings_name=='Periodic Test'){
													//07-12-20 Convert periodic marks out of 50 to 10 n save as report card marks
													
													if($present=='N'){
														$reportcard_marks='Ab';
													}else{

														$reportcard_marks = ($marks_obtained/$highest_marks)*10;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=10;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}else{
													if($marks_obtained==""){
														$reportcard_marks='Ab';
													}else{
														$reportcard_marks = $marks_obtained;
														$total_reportcard_marks_obtained=$total_reportcard_marks_obtained+$reportcard_marks;
													}
													$reportcard_highest_marks=$highest_marks;
													$total_reportcard_highest_marks=$total_reportcard_highest_marks+$reportcard_highest_marks;
												}
											}*/
                                    } elseif ($subject_type == 'Co-Scholastic') {
                                        if ($marks_obtained == "") {
                                            $reportcard_marks = 'Ab';
                                        } else {
                                            $reportcard_marks = $marks_obtained * 2;
                                            $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                        }
                                        $reportcard_highest_marks = $highest_marks * 2;
                                        $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    }
                                    break;
                                case "9":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        $reportcard_marks = $marks_obtained;
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $reportcard_highest_marks = $highest_marks;
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                case "10":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        $reportcard_marks = $marks_obtained;
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $reportcard_highest_marks = $highest_marks;
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                //Lija 15-07-21
                                case "11":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        $reportcard_marks = $marks_obtained;
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $reportcard_highest_marks = $highest_marks;
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                //Lija 14-07-22
                                case "12":
                                    if ($marks_obtained == "") {
                                        $reportcard_marks = 'Ab';
                                    } else {
                                        $reportcard_marks = $marks_obtained;
                                        $total_reportcard_marks_obtained = $total_reportcard_marks_obtained + $reportcard_marks;
                                    }
                                    $reportcard_highest_marks = $highest_marks;
                                    $total_reportcard_highest_marks = $total_reportcard_highest_marks + $reportcard_highest_marks;
                                    break;
                                default:
                            }

                            $reportcard_marks_string = $reportcard_marks_string . '"' . $marks_headings_name . '":"' . $reportcard_marks . '",';
                            $reportcard_highest_marks_string = $reportcard_highest_marks_string . '"' . $marks_headings_name . '":"' . $reportcard_highest_marks . '",';

                            /*if($reportcard_marks<>""){
									$grade_markheading_wise = $this->assessment_model->get_grade_based_on_marks($reportcard_marks,$subject_type,$class_id); //Lija for report card
								}
								$grade_markheading_wise_string=$grade_markheading_wise_string.'"'.$marks_headings_name.'":"'.$grade_markheading_wise.'",';
								*/
                        }

                        $present_string = rtrim($present_string, ",");
                        $present_string = $present_string . "}";

                        $marks_obtained_string = rtrim($marks_obtained_string, ",");
                        $marks_obtained_string = $marks_obtained_string . "}";

                        $highest_marks_string = rtrim($highest_marks_string, ",");
                        $highest_marks_string = $highest_marks_string . "}";

                        //$grade_markheading_wise_string=rtrim($grade_markheading_wise_string,",");
                        //$grade_markheading_wise_string=$grade_markheading_wise_string."}";

                        $reportcard_marks_string = rtrim($reportcard_marks_string, ","); //Lija report card
                        $reportcard_marks_string = $reportcard_marks_string . "}";

                        $reportcard_highest_marks_string = rtrim($reportcard_highest_marks_string, ","); //Lija report card
                        $reportcard_highest_marks_string = $reportcard_highest_marks_string . "}";
                        // dd($total_reportcard_marks_obtained);
                        if ($total_reportcard_marks_obtained <> "") {
                            $percent = $total_reportcard_marks_obtained * 100 / $total_reportcard_highest_marks;
                            if ($class_name == 'LKG' || $class_name == 'UKG') {
                                $grade = $this->getGradeBasedOnMarks($percent, $subject_type, $class_id);
                            } else {
                                $grade = $this->getGradeBasedOnMarks($total_reportcard_marks_obtained, $subject_type, $class_id);
                            }
                        }

                        //print_r("reportcard_marks_string".$reportcard_marks_string."<br/>");
                        //exit;
                        $marksData = [
                            'exam_id' => $exam_id,
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'academic_yr' => $acd_yr,
                            'subject_id' => $subject_id,
                            'student_id' => $student_id,
                            'present' => $present_string,
                            'mark_obtained' => $marks_obtained_string,
                            'highest_marks' => $highest_marks_string,
                            'reportcard_marks' => $reportcard_marks_string,
                            'reportcard_highest_marks' => $reportcard_highest_marks_string,
                            // 'grade_marksheading_wise' => $grade_markheading_wise_string,
                            'total_marks' => $total_reportcard_marks_obtained,
                            'highest_total_marks' => $total_reportcard_highest_marks,
                            'percent' => $percent,
                            'grade' => $grade,
                            'date' => date('Y-m-d'),
                            'publish' => 'N',
                            'comment' => '',
                            'data_entry_by' => $user->reg_id
                        ];

                        // Using updateOrInsert (best for your case)
                        DB::table('student_marks')->updateOrInsert(
                            [
                                'exam_id' => $exam_id,
                                'class_id' => $class_id,
                                'section_id' => $section_id,
                                'subject_id' => $subject_id,
                                'student_id' => $student_id,
                            ],
                            $marksData
                        );
                    }
                }

                $c++;
            }

            fclose($handle);

            return response()->json([
                'status' => 'success',
                'message' => "CSV processed successfully",
                'data' => [
                    'class_id' => $class_id,
                    'section_id' => $section_id,
                    'subject_id' => $subject_id,
                    'exam_id' => $exam_id,
                    'academic_year' => $acd_yr,
                    'marks_headings' => $marks_headings_name_array,
                    'marks_headings_ids' => $marks_headings_id_array
                ]
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => "Unable to read uploaded file."
        ], 400);
    }

    function getGradeBasedOnMarks($mark, $subject_type, $class_id)
    {
        // Check for invalid marks
        if (is_nan($mark) || !is_numeric($mark)) {
            return "";
        }

        $grade = DB::table('grade')
            ->where('class_id', $class_id)
            ->where('subject_type', $subject_type)
            ->where('mark_from', '<=', $mark)
            ->where('mark_upto', '>=', $mark)
            ->value('name'); // fetch only the "name" column

        return $grade ?? "";
    }

    public function getPublishDeleteStatusStudentMarks(Request $request)
    {
        $exam_id    = $request->exam_id;
        $class_id   = $request->class_id;
        $subject_id = $request->subject_id;
        $section_id = $request->section_id;

        if (!$exam_id || !$class_id || !$subject_id || !$section_id) {
            return response()->json(['status' => false, 'message' => 'Missing required parameters'], 400);
        }

        // Check unpublished marks
        $unpublishedCount = DB::table('student_marks')
            ->where([
                'exam_id' => $exam_id,
                'class_id' => $class_id,
                'subject_id' => $subject_id,
                'section_id' => $section_id,
                'publish' => 'N'
            ])
            ->count();

        // Check all marks
        $totalCount = DB::table('student_marks')
            ->where([
                'exam_id' => $exam_id,
                'class_id' => $class_id,
                'subject_id' => $subject_id,
                'section_id' => $section_id
            ])
            ->count();

        return response()->json([
            'status' => 200,
            'show_publish' => $unpublishedCount > 0,
            'show_delete' => $totalCount > 0,
            'success' => true
        ]);
    }

    public function saveChapters(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $validated = $request->validate([
            'class_id'     => 'required|integer',
            'subject_id'   => 'required|integer',
            'chapter_no'   => 'required|integer',
            'name'         => 'required|string|max:255',
            'sub_subject'  => 'nullable|string|max:255',
            'description'  => 'nullable|string',
        ]);


        $data = [
            'class_id'     => $validated['class_id'],
            'subject_id'   => $validated['subject_id'],
            'chapter_no'   => $validated['chapter_no'],
            'name'         => $validated['name'],
            'sub_subject'  => $validated['sub_subject'] ?? null,
            'description'  => $validated['description'] ?? null,
            'IsDelete'     => 'N',
            'created_by'   => $user->reg_id,
            'academic_yr'  => JWTAuth::getPayload()->get('academic_year')
        ];


        $data['publish'] = 'N';

        // check if chapter_number_already_exists
        $exists = DB::table('chapters')->where([
            ['class_id', '=', $data['class_id']],
            ['subject_id', '=', $data['subject_id']],
            ['chapter_no', '=', $data['chapter_no']],
            ['IsDelete', '=', 'N'],
        ])
            ->when(!empty($data['sub_subject']), function ($q) use ($data) {
                return $q->whereRaw('UPPER(sub_subject) = ?', [strtoupper($data['sub_subject'])]);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 409,
                'message' => 'Duplicate lesson number is not allowed',
                'success' => false,
            ], 409);
        }

        DB::table('chapters')->insert($data);

        return response()->json([
            'status'  => 200,
            'message' => 'Chapter Created Successfully!',
            'success' => true
        ]);
    }

    // LEO CHANGES - 09/12/2025 11:23 - START
    public function savenpublishChapters(Request $request)
    {

        /*
            Duplicate lesson number is created
        */

        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $validated = $request->validate([
            'class_id'     => 'required|integer',
            'subject_id'   => 'required|integer',
            'chapter_no'   => 'required|integer',
            'name'         => 'required|string|max:255',
            'sub_subject'  => 'nullable|string|max:255',
            'description'  => 'nullable|string',
        ]);


        $data = [
            'class_id'     => $validated['class_id'],
            'subject_id'   => $validated['subject_id'],
            'chapter_no'   => $validated['chapter_no'],
            'name'         => $validated['name'],
            'sub_subject'  => $validated['sub_subject'] ?? null,
            'description'  => $validated['description'] ?? null,
            'IsDelete'     => 'N',
            'created_by'   => $user->reg_id,
            'academic_yr'  => JWTAuth::getPayload()->get('academic_year')
        ];


        $data['publish'] = 'Y';

        // check if chapter_number_already_exists
        $exists = DB::table('chapters')->where([
            ['class_id', '=', $data['class_id']],
            ['subject_id', '=', $data['subject_id']],
            ['chapter_no', '=', $data['chapter_no']],
            ['IsDelete', '=', 'N'],
        ])
            ->when(!empty($data['sub_subject']), function ($q) use ($data) {
                return $q->whereRaw('UPPER(sub_subject) = ?', [strtoupper($data['sub_subject'])]);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 409,
                'message' => 'Duplicate lesson number is not allowed',
                'success' => false,
            ], 409);
        }


        DB::table('chapters')->insert($data);

        return response()->json([
            'status'  => 200,
            'message' => 'Chapter created and published successfully!',
            'success' => true
        ]);
    }
    // LEO CHANGES - 09/12/2025 11:23 - END

    public function deleteChapters(Request $request, $chapter_id)
    {
        $chapter = DB::table('chapters')->where('chapter_id', $chapter_id)->first();

        if (!$chapter) {
            return response()->json([
                'status'  => false,
                'message' => 'Chapter not found!',
            ], 404);
        }

        if ($chapter->publish === 'N') {
            DB::table('chapters')->where('chapter_id', $chapter_id)->delete();

            return response()->json([
                'status'  => 200,
                'message' => 'Chapter permanently deleted.',
                'success' => true
            ]);
        } else {
            $lessonPlanExists = DB::table('lesson_plan_template')
                ->where('chapter_id', $chapter_id)
                ->exists();

            DB::table('chapters')
                ->where('chapter_id', $chapter_id)
                ->update(['IsDelete' => 'Y']);

            return response()->json([
                'status'  => 200,
                'message' => $lessonPlanExists
                    ? 'Chapter marked as deleted (linked lesson plan found).'
                    : 'Chapter marked as deleted.',
                'success' => true
            ]);
        }
    }

    public function getChapters(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        if ($user->role_id === 'U') {

            $chapters_list = DB::table('chapters')
                ->select([
                    'chapters.*',
                    'class.name as class_name',
                    'subject_master.name as sub_name',
                    'subject.sm_id',
                    'subject.teacher_id',
                    'teacher.name as tec_name'
                ])
                ->join('class', 'chapters.class_id', '=', 'class.class_id')
                ->join('subject_master', 'chapters.subject_id', '=', 'subject_master.sm_id')
                ->join('subject', 'chapters.subject_id', '=', 'subject.sm_id')
                ->join('teacher', 'chapters.created_by', '=', 'teacher.teacher_id')
                ->whereColumn('chapters.class_id', 'subject.class_id')
                ->where('chapters.academic_yr', $academic_yr)
                ->groupBy('chapters.chapter_id')
                ->orderBy('chapters.class_id', 'asc')
                ->orderBy('chapters.subject_id', 'asc')
                ->orderBy('chapters.chapter_no', 'asc')
                ->get();
        } else {

            $chapters_list = DB::table('chapters')
                ->select([
                    'chapters.*',
                    'class.name as class_name',
                    'subject_master.name as sub_name',
                    'subject.sm_id',
                    'subject.teacher_id',
                    'teacher.name as tec_name'
                ])
                ->join('class', 'chapters.class_id', '=', 'class.class_id')
                ->join('subject_master', 'chapters.subject_id', '=', 'subject_master.sm_id')
                ->join('subject', 'chapters.subject_id', '=', 'subject.sm_id')
                ->join('teacher', 'chapters.created_by', '=', 'teacher.teacher_id')
                ->whereColumn('chapters.class_id', 'subject.class_id')
                ->where('chapters.academic_yr', $academic_yr)
                ->where('subject.teacher_id', $user->reg_id)
                ->groupBy('chapters.chapter_id')
                ->orderBy('chapters.class_id', 'asc')
                ->orderBy('chapters.subject_id', 'asc')
                ->orderBy('chapters.chapter_no', 'asc')
                ->get();
        }

        return response()->json([
            'status' => 200,
            'data'   => $chapters_list,
            'success' => true
        ]);
    }

    public function getChapter(Request $request)
    {
        $chapter_id = $request->input('chapter_id');
        $chapters_list = DB::table('chapters')
            ->select([
                'chapters.*',
                'class.name as class_name',
                'subject_master.name as sub_name',
                'subject.sm_id',
                'subject.teacher_id',
                'teacher.name as tec_name'
            ])
            ->join('class', 'chapters.class_id', '=', 'class.class_id')
            ->join('subject_master', 'chapters.subject_id', '=', 'subject_master.sm_id')
            ->join('subject', 'chapters.subject_id', '=', 'subject.sm_id')
            ->join('teacher', 'chapters.created_by', '=', 'teacher.teacher_id')
            ->whereColumn('chapters.class_id', 'subject.class_id')
            ->where('chapters.chapter_id', $chapter_id)
            ->groupBy('chapters.chapter_id')
            ->orderBy('chapters.class_id', 'asc')
            ->orderBy('chapters.subject_id', 'asc')
            ->orderBy('chapters.chapter_no', 'asc')
            ->get();
        return response()->json([
            'status' => 200,
            'data'   => $chapters_list,
            'success' => true
        ]);
    }

    public function publishChapters(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $validated = $request->validate([
            'chapter_ids'   => 'required|array|min:1',
            'chapter_ids.*' => 'integer|exists:chapters,chapter_id'
        ]);

        $chapterIds = $validated['chapter_ids'];

        $updateData = [
            'publish'      => 'Y',
        ];

        $affectedRows = DB::table('chapters')
            ->whereIn('chapter_id', $chapterIds)
            ->update($updateData);
        // dd($affectedRows);
        if ($affectedRows > 0) {
            return response()->json([
                'status'  => 200,
                'message' => 'Selected chapters have been published successfully.',
                'count'   => $affectedRows,
                'success' => true
            ], 200);
        }

        return response()->json([
            'status'  => 400,
            'message' => 'No chapters were updated. Please check the IDs.',
            'success' => true
        ], 400);
    }

    public function getOnlyClassesAllotedToTeacher(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $classes = DB::select("select class.name as class_name, subject.class_id from subject, class where subject.class_id = class.class_id and subject.teacher_id= " . $user->reg_id . " AND subject.academic_yr = '" . $academic_yr . "' GROUP BY class_id;");


        return response()->json([
            'status'  => 200,
            'message' => 'Classes list by teacher id.',
            'data'   => $classes,
            'success' => true
        ]);
    }

    public function getSubjectsAccordingClass(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $subjects = DB::select("select subject_master.sm_id, subject_master.name from subject,subject_master where subject_master.sm_id= subject.sm_id and subject.class_id='" . $class_id . "' and subject.teacher_id='" . $user->reg_id . "' AND subject.academic_yr ='" . $academic_yr . "' group by subject.sm_id");

        return response()->json([
            'status'  => 200,
            'message' => 'Subjects according to class',
            'data'   => $subjects,
            'success' => true
        ]);
    }

    public function getSubjectsAccordingClassMultiple(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $class_id = $request->input('class_id');
        $section_ids = $request->input('section_ids'); // ['A','B','C']

        if (!is_array($section_ids) || empty($section_ids)) {
            return response()->json([
                'status' => 400,
                'message' => 'section_ids must be a non-empty array',
                'success' => false
            ]);
        }

        // 1️⃣ Base subquery for first section
        $subQuery = DB::table('subject as a')
            ->select('a.sm_id')
            ->where('a.class_id', $class_id)
            ->where('a.section_id', $section_ids[0])
            ->where('a.teacher_id', $user->reg_id)
            ->where('a.academic_yr', $academic_yr);

        // 2️⃣ Add intersection for the remaining sections
        for ($i = 1; $i < count($section_ids); $i++) {
            $subQuery->whereIn('a.sm_id', function ($q) use ($class_id, $section_ids, $i, $academic_yr, $user) {
                $q->select('b.sm_id')
                ->from('subject as b')
                ->where('b.class_id', $class_id)
                ->where('b.section_id', $section_ids[$i])
                ->where('b.teacher_id', $user->reg_id)
                ->where('b.academic_yr', $academic_yr);
            });
        }

        // 3️⃣ Get the final subject IDs
        $finalSmIds = $subQuery->pluck('sm_id');

        // 4️⃣ Return subject names
        $subjects = DB::table('subject_master')
            ->whereIn('sm_id', $finalSmIds)
            ->select('sm_id', 'name')
            ->get();

        return response()->json([
            'status'  => 200,
            'message' => 'Subjects according to class',
            'data'    => $subjects,
            'success' => true
        ]);
    }

    public function updateChapters(Request $request, $chapter_id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $validated = $request->validate([
            'class_id'     => 'required|integer',
            'subject_id'   => 'required|integer',
            'chapter_no'   => 'required|integer',
            'name'         => 'required|string|max:255',
            'sub_subject'  => 'nullable|string|max:255',
            'description'  => 'nullable|string',
        ]);

        $data = [
            'class_id'     => $validated['class_id'],
            'subject_id'   => $validated['subject_id'],
            'chapter_no'   => $validated['chapter_no'],
            'name'         => $validated['name'],
            'sub_subject'  => $validated['sub_subject'] ?? null,
            'description'  => $validated['description'] ?? null,
        ];

        $exists = DB::table('chapters')->where([
            ['class_id', '=', $data['class_id']],
            ['subject_id', '=', $data['subject_id']],
            ['chapter_no', '!=', $data['chapter_no']],
            ['IsDelete', '=', 'N'],
        ])
            ->when(!empty($data['sub_subject']), function ($q) use ($data) {
                return $q->whereRaw('UPPER(sub_subject) = ?', [strtoupper($data['sub_subject'])]);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 409,
                'message' => 'Duplicate lesson number is not allowed',
                'success' => false,
            ], 409);
        }

        $updated = DB::table('chapters')
            ->where('chapter_id', $chapter_id)
            ->update($data);


        return response()->json([
            'status'  => 200,
            'message' => 'Chapter updated !',
            'success' => true
        ]);
    }

    public function generateCsvFileForChapters(Request $request)
    {
        $request->validate([
            'class_id' => 'required|integer',
            'sm_id'    => 'required|integer',
        ]);

        $class_id = $request->input('class_id');
        $sm_id    = $request->input('sm_id');

        // Fetch class and subject names (adjust if you use Eloquent models)
        $classname   = DB::table('class')->where('class_id', $class_id)->value('name');
        $subjectname = DB::table('subject_master')->where('sm_id', $sm_id)->value('name');

        $filename = "Chapters_{$classname}_{$subjectname}.csv";


        // Return CSV as a streamed response
        return response()->stream(function () {
            $file = fopen('php://output', 'w');

            // Column headers
            fputcsv($file, ['Lesson Number', 'Name', 'Sub-subject', 'Description']);


            fclose($file);
        }, 200, [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // LEO CHANGES - 09/12/2025 - START
    public function uploadChaptersThroughExcelsheet(Request $request)
    {

        /*
            On entering same lesson no in excel sheet , chapter is created . Error msg not shown for unique chapter no.
        */

        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|integer',
            'sm_id'    => 'required|integer',
            'file'      => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
            ]);
        }

        $class_id = $request->input('class_id');
        $sm_id    = $request->input('sm_id');
        $file     = $request->file('file');

        $lines = file($file->getRealPath(), FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        if (count($lines) <= 1) {
            // Only header or empty
            return response()->json([
                'status'  => 422,
                'message' => 'Empty CSV cannot be uploaded. Please add data!',
                'success' => false
            ]);
        }

        $handle = fopen($file->getRealPath(), 'r');

        $row = 1;
        $errors = [];
        $insertData = [];
        $seen = []; // to track duplicates inside the CSV

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if ($row === 1) {
                // Validate header
                if (trim($data[0]) !== 'Lesson Number') {
                    return response()->json([
                        'status'  => 422,
                        'message' => "Invalid CSV header. Please use the correct format.",
                        'success' => false
                    ]);
                }
                $row++;
                continue;
            }

            $chapter_no = isset($data[0]) ? trim($data[0]) : null;
            $name       = isset($data[1]) ? trim($data[1]) : null;
            $sub_subject = isset($data[2]) ? strtoupper(trim($data[2])) : null;
            $description = isset($data[3]) ? trim($data[3]) : null;

            $key = $chapter_no . '_' . strtoupper($sub_subject ?? '');

            if (isset($seen[$key])) {
                $errors[] = "Row $row: Duplicate Lesson Number '$chapter_no' in the CSV file.";
            } else {
                $seen[$key] = true;
            }

            // Validation
            if (!$chapter_no || !$name) {
                $errors[] = "Row $row: Lesson Number and Name are required.";
            } else {
                if (!is_numeric($chapter_no)) {
                    $errors[] = "Row $row: Lesson Number must be numeric.";
                } elseif (strlen($chapter_no) > 3) {
                    $errors[] = "Row $row: Lesson Number must be up to 3 digits.";
                }

                // Check duplicate
                $query = DB::table('chapters')
                    ->where('class_id', $class_id)
                    ->where('subject_id', $sm_id)
                    ->where('chapter_no', $chapter_no)
                    ->where('IsDelete', 'N')
                    ->when($sub_subject, function ($q) use ($sub_subject) {
                        return $q->whereRaw('UPPER(sub_subject) = ?', [strtoupper($sub_subject)]);
                    })
                    ->exists();

                if ($query) {
                    $errors[] = "Row $row: Lesson Number already exists.";
                }
            }

            if (empty($errors)) {
                $insertData[] = [
                    'class_id'    => $class_id,
                    'subject_id'  => $sm_id,
                    'chapter_no'  => $chapter_no,
                    'name'        => $name,
                    'sub_subject' => $sub_subject,
                    'description' => $description,
                    'IsDelete'    => 'N',
                    'publish'     => 'N',
                    'created_by'  => $user->reg_id,
                    'academic_yr' => $academic_yr,
                ];
            }

            $row++;
        }

        fclose($handle);

        if (!empty($errors)) {
            return response()->json([
                'status'  => 422,
                'message' => implode(" ", $errors),
            ]);
        }

        DB::table('chapters')->insert($insertData);

        return response()->json([
            'status'  => 200,
            'message' => 'Chapters uploaded successfully!',
            'success' => true
        ]);
    }
    // LEO CHANGES - 09/12/2025 - END

    public function saveLessonPlanHeading(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer',
        ]);


        $data = [
            'name' => $request->input('name'),
            'sequence' => $request->input('sequence'),
            'change_daily' => $request->input('change_daily'),
        ];



        // Check if heading already exists
        $exists = DB::table('lesson_plan_heading')
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 400,
                'message' => 'Lesson plan heading already exists!',
                'success' => false
            ]);
        }


        $inserted = DB::table('lesson_plan_heading')->insert($data);

        if ($inserted) {
            return response()->json([
                'status' => '200',
                'message' => 'Lesson plan heading created successfully!',
                'success' => true
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Failed to create Lesson Plan Heading.',
                'success' => false
            ]);
        }
    }

    public function getLessonPlanHeading(Request $request)
    {
        $headings = DB::table('lesson_plan_heading')->orderBy('sequence', 'asc')->get();

        // Map each heading to include edit/delete flags
        $result = $headings->map(function ($heading) {
            $usedCount = DB::table('lesson_plan_template_details')
                ->where('lesson_plan_headings_id', $heading->lesson_plan_headings_id)
                ->count();

            return [
                'lesson_plan_headings_id' => $heading->lesson_plan_headings_id,
                'name' => $heading->name,
                'sequence' => $heading->sequence,
                'change_daily' => $heading->change_daily,
                'edit' => $usedCount == 0 ? 'Y' : 'N',
                'delete' => $usedCount == 0 ? 'Y' : 'N',
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $result,
            'success' => true
        ]);
    }

    public function deleteLessonPlanHeading(Request $request, $lesson_plan_heading_id)
    {
        $heading = DB::table('lesson_plan_heading')->where('lesson_plan_headings_id', $lesson_plan_heading_id)->first();

        if (!$heading) {
            return response()->json([
                'status' => 400,
                'message' => 'Lesson plan heading not found.',
                'success' => false
            ]);
        }

        // Optional: Check if it's used in lesson_plan_template_details
        $usedCount = DB::table('lesson_plan_template_details')
            ->where('lesson_plan_headings_id', $lesson_plan_heading_id)
            ->count();

        if ($usedCount > 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Cannot delete. This heading is in use.',
                'success' => false
            ]);
        }

        // Delete using Query Builder
        DB::table('lesson_plan_heading')->where('lesson_plan_headings_id', $lesson_plan_heading_id)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Lesson plan heading deleted successfully.',
            'success' => true
        ]);
    }

    public function updateLessonPlanHeading(Request $request, $lesson_plan_heading_id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'required|integer',
            'change_daily' => 'nullable',
        ]);

        $data = [
            'name' => $request->input('name'),
            'sequence' => $request->input('sequence'),
            'change_daily' => $request->input('change_daily'),
        ];


        $updated = DB::table('lesson_plan_heading')
            ->where('lesson_plan_headings_id', $lesson_plan_heading_id)
            ->update($data);


        return response()->json([
            'success' => true,
            'message' => 'Lesson Plan Heading updated successfully.',
            'status'  => 200
        ]);
    }

    public function getChapterInfoClassSubId(Request $request)
    {
        $class_id = $request->input('class_id');
        $subject_id = $request->input('subject_id');

        $chapters = DB::table('chapters')
            ->where('class_id', $class_id)
            ->where('subject_id', $subject_id)
            ->where('publish', 'Y')
            ->where('isDelete', 'N')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $chapters,
            'message' => 'Chatper according to class and subject.',
            'status'  => 200
        ]);
    }

    // public function getLessonPlanTemplate(Request $request){
    //     $class_id = $request->input('class_id');
    //     $subject_id = $request->input('subject_id');
    //     $chapter_id = $request->input('chapter_id');

    //     $lessonplantemplate = DB::select("select lesson_plan_template.*,lesson_plan_template_details.*,lesson_plan_heading.name from lesson_plan_template,lesson_plan_template_details,lesson_plan_heading where lesson_plan_template.les_pln_temp_id = lesson_plan_template_details.les_pln_temp_id and lesson_plan_heading.lesson_plan_headings_id = lesson_plan_template_details.lesson_plan_headings_id and lesson_plan_template.chapter_id='".$chapter_id."' and subject_id='".$subject_id."' and class_id='".$class_id."'");

    //     return response()->json([
    //         'success' => true,
    //         'data' =>$lessonplantemplate,
    //         'message' => 'Lesson plan template fetched successfully.',
    //         'status'  =>200
    //     ]);

    // }


    public function getLessonPlanTemplate(Request $request)
    {
        $class_id   = $request->input('class_id');
        $subject_id = $request->input('subject_id');
        $chapter_id = $request->input('chapter_id');

        // Authenticate user
        $user   = $this->authenticateUser();
        $reg_id = JWTAuth::getPayload()->get('reg_id');

        $lessonplantemplate = DB::select("
            SELECT lpt.*, lptd.*, lph.name , lpt.reg_id as teacher_id
            FROM lesson_plan_template AS lpt
            JOIN lesson_plan_template_details AS lptd 
                ON lpt.les_pln_temp_id = lptd.les_pln_temp_id
            JOIN lesson_plan_heading AS lph 
                ON lph.lesson_plan_headings_id = lptd.lesson_plan_headings_id
            WHERE lpt.chapter_id = ?
            AND lpt.subject_id = ?
            AND lpt.class_id = ?
        ", [$chapter_id, $subject_id, $class_id]);

        if (count($lessonplantemplate) == 0) {
            return response()->json([
                'success' => true,
                'isCreatedByRequestedUser' => false,
                'data'    => [],
                'message' => "No lesson plan template created",
                'status'  => 404
            ]);
        }

        // Use object properties instead of ['']
        $first = $lessonplantemplate[0];

        $status  = false;
        $message = "";

        if ($first->teacher_id == $reg_id) {
            $status = true;
        } else {
            if ($first->publish == 'Y') {
                $message = "Lesson Plan Template is already created and published!!!";
            } else {
                $message = "Lesson Plan Template is already created!!!";
            }
        }

        if (!$status) {
            $lessonplantemplate = [];
        }

        return response()->json([
            'success' => true,
            'isCreatedByRequestedUser' => $status,
            'data'    => $lessonplantemplate,
            'message' => $message,
            'status'  => 200
        ]);
    }

    public function getLessonPlanHeadingNonDaily(Request $request)
    {
        $lessonPlanHeadings = DB::table('lesson_plan_heading')
            ->where('change_daily', '!=', 'Y')
            ->orderBy('sequence', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lessonPlanHeadings,
            'message' => 'Lesson plan headings non daily fetched successfully.',
            'status'  => 200
        ]);
    }

    public function getLessonPlanHeadingDaily(Request $request)
    {
        $lessonPlanHeadings = DB::table('lesson_plan_heading')
            ->where('change_daily', '=', 'Y')
            ->orderBy('sequence', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lessonPlanHeadings,
            'message' => 'Lesson plan headings daily fetched successfully.',
            'status'  => 200
        ]);
    }

    public function saveLessonPlanTemplate(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $data = [
            'class_id'    => $request->input('class_id'),
            'subject_id'  => $request->input('subject_id'),
            'chapter_id'  => $request->input('chapter_id'),
            'reg_id'      => $user->reg_id,
            'publish'     => 'N',
            'academic_yr' => $academic_yr,
        ];


        $templateId = $this->lessonPlanTemplateCreate($data);

        if (!$templateId) {
            return response()->json([
                'status' => 400,
                'message' => 'Template already exists.',
                'success' => false
            ]);
        }

        // Handle all descriptions in one clean loop
        foreach ($request->input('descriptions', []) as $desc) {
            DB::table('lesson_plan_template_details')->insert([
                'les_pln_temp_id'          => $templateId,
                'lesson_plan_headings_id'  => $desc['lesson_plan_headings_id'],
                'description'              => $desc['description'],
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Lesson Plan Template Created Successfully!',
            'success' => true
        ]);
    }

    public function savenPublishLessonPlanTemplate(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $data = [
            'class_id'    => $request->input('class_id'),
            'subject_id'  => $request->input('subject_id'),
            'chapter_id'  => $request->input('chapter_id'),
            'reg_id'      => $user->reg_id,
            'publish'     => 'Y',
            'academic_yr' => $academic_yr,
        ];


        $templateId = $this->lessonPlanTemplateCreate($data);

        if (!$templateId) {
            return response()->json([
                'status' => 400,
                'message' => 'Template already exists.',
                'success' => false
            ]);
        }

        // Handle all descriptions in one clean loop
        foreach ($request->input('descriptions', []) as $desc) {
            DB::table('lesson_plan_template_details')->insert([
                'les_pln_temp_id'          => $templateId,
                'lesson_plan_headings_id'  => $desc['lesson_plan_headings_id'],
                'description'              => $desc['description'],
            ]);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Lesson Plan Template Created and Published Successfully!',
            'success' => true
        ]);
    }

    private function lessonPlanTemplateCreate(array $data)
    {
        $exists = DB::table('lesson_plan_template')
            ->where('chapter_id', $data['chapter_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('class_id', $data['class_id'])
            ->exists();

        if ($exists) {
            return false;
        }

        $k = DB::table('lesson_plan_template')->insertGetId($data);
        return $k;
    }


    public function deleteLessonPlanTemplate(Request $request, $les_pln_temp_id)
    {

        DB::table('lesson_plan_template_details')
            ->where('les_pln_temp_id', $les_pln_temp_id)
            ->delete();

        DB::table('lesson_plan_template')
            ->where('les_pln_temp_id', $les_pln_temp_id)
            ->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'lesson plan template deleted.',
            'success' => true
        ]);
    }

    public function getLessonPlanTemplateList(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $lessonplantemplatelist = DB::select("
        select lesson_plan_template.*,
        lesson_plan_template_details.*,
        class.class_id,
        class.name as c_name,
        chapters.chapter_id,
        chapters.name,
        subject_master.name as sub_name 
        from lesson_plan_template,
        lesson_plan_template_details,
        class,
        chapters,
        subject_master 
        where 
        lesson_plan_template.les_pln_temp_id = lesson_plan_template_details.les_pln_temp_id 
        and lesson_plan_template.class_id=class.class_id 
        and lesson_plan_template.chapter_id = chapters.chapter_id 
        and chapters.isDelete!='Y' 
        and lesson_plan_template.subject_id = subject_master.sm_id  
        and lesson_plan_template.reg_id='" . $user->reg_id . "' 
        and lesson_plan_template.academic_yr='" . $academic_yr . "'  
        group by lesson_plan_template.les_pln_temp_id");

        return response()->json([
            'status'  => 200,
            'data'    => $lessonplantemplatelist,
            'message' => 'lesson plan template list.',
            'success' => true
        ]);
    }

    // Lesson Plan Template Dev Name :- Lesson Plan Template 
    public function updateLessonPlanTemplate(Request $request, $id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $data = [
            'class_id'    => $request->input('class_id'),
            'subject_id'  => $request->input('subject_id'),
            'chapter_id'  => $request->input('chapter_id'),
            'reg_id'      => $user->reg_id,
            'publish'     => 'N',
            'academic_yr' => $academic_yr,
        ];

        DB::beginTransaction();
        try {

            $template = DB::table('lesson_plan_template')
                ->where('les_pln_temp_id', $id)
                ->first();

            if (!$template) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Template not found.',
                    'success' => false
                ]);
            }

            // Optional: update template metadata
            DB::table('lesson_plan_template')
                ->where('les_pln_temp_id', $id)
                ->update($data);

            // Insert new descriptions WITHOUT deleting old ones
            // foreach ($request->input('descriptions', []) as $desc) {
            //     DB::table('lesson_plan_template_details')->insert([
            //         'les_pln_temp_id'          => $id,
            //         'lesson_plan_headings_id'  => $desc['lesson_plan_headings_id'],
            //         'description'              => $desc['description'],
            //     ]);
            // }

            foreach ($request->input('descriptions', []) as $desc) {
                DB::table('lesson_plan_template_details')
                    ->where('les_pln_temp_id', $id)
                    ->where('lesson_plan_headings_id', $desc['lesson_plan_headings_id'])
                    ->update([
                        'description' => $desc['description'],
                    ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Lesson Plan Template Updated Successfully!',
                'success' => true,
                'les_pln_temp_id' => $id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error updating template: ' . $e->getMessage(),
                'success' => false,
            ]);
        }
    }

    public function updatePublishLessonPlanTemplate(Request $request, $id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $data = [
            'class_id'    => $request->input('class_id'),
            'subject_id'  => $request->input('subject_id'),
            'chapter_id'  => $request->input('chapter_id'),
            'reg_id'      => $user->reg_id,
            'publish'     => 'Y',
            'academic_yr' => $academic_yr,
        ];

        DB::beginTransaction();
        try {

            $template = DB::table('lesson_plan_template')
                ->where('les_pln_temp_id', $id)
                ->first();

            if (!$template) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Template not found.',
                    'success' => false
                ]);
            }

            // Optional: update template metadata
            DB::table('lesson_plan_template')
                ->where('les_pln_temp_id', $id)
                ->update($data);

            // Insert new descriptions WITHOUT deleting old ones
            // foreach ($request->input('descriptions', []) as $desc) {
            //     DB::table('lesson_plan_template_details')->insert([
            //         'les_pln_temp_id'          => $id,
            //         'lesson_plan_headings_id'  => $desc['lesson_plan_headings_id'],
            //         'description'              => $desc['description'],
            //     ]);
            // }

            foreach ($request->input('descriptions', []) as $desc) {
                DB::table('lesson_plan_template_details')
                    ->where('les_pln_temp_id', $id)
                    ->where('lesson_plan_headings_id', $desc['lesson_plan_headings_id'])
                    ->update([
                        'description' => $desc['description'],
                    ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Lesson Plan Template Updated & Published Successfully!',
                'success' => true,
                'les_pln_temp_id' => $id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error updating template: ' . $e->getMessage(),
                'success' => false,
            ]);
        }
    }

    public function unpublishLessonPlanTemplate(Request $request)
    {
        $les_pln_temp_id = $request->input('les_pln_temp_id');
        $class_id = $request->input('class_id');
        $subject_id = $request->input('subject_id');
        $chapter_id = $request->input('chapter_id');

        if (!$les_pln_temp_id || !$class_id || !$subject_id || !$chapter_id) {
            return response()->json([
                'status' => 400,
                'message' => 'Lesson Plan Template ID, Class ID, Subject ID, and Chapter ID are required.',
                'success' => false,
            ]);
        }

        try {
            $updated = DB::table('lesson_plan_template')
                ->where('les_pln_temp_id', $les_pln_temp_id)
                ->where('class_id', $class_id)
                ->where('subject_id', $subject_id)
                ->where('chapter_id', $chapter_id)
                ->update(['publish' => 'N']);

            if ($updated) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Lesson Plan Template unpublished successfully.',
                    'success' => true,
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'Lesson Plan Template not found or already unpublished.',
                    'success' => false,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error unpublishing template: ' . $e->getMessage(),
                'success' => false,
            ]);
        }
    }

    public function getLessonPlanTemplateID(Request $request)
    {
        $class_id = $request->input('class_id');
        $subject_id = $request->input('subject_id');
        $chapter_id = $request->input('chapter_id');
        $les_pln_temp_id = $request->input('les_pln_temp_id');

        $query = DB::table('lesson_plan_template as t')
            ->leftJoin('lesson_plan_template_details as d', 't.les_pln_temp_id', '=', 'd.les_pln_temp_id')
            ->leftJoin('lesson_plan_heading as h', 'd.lesson_plan_headings_id', '=', 'h.lesson_plan_headings_id')
            ->select(
                't.les_pln_temp_id',
                't.class_id',
                't.subject_id',
                't.chapter_id',
                't.publish',
                'd.les_pln_tempdetails_id as detail_id',
                'd.lesson_plan_headings_id',
                'h.name as heading_name',
                'd.description'
            );

        if ($les_pln_temp_id) {
            $query->where('t.les_pln_temp_id', $les_pln_temp_id);
        } else {
            $query->where('t.class_id', $class_id)
                ->where('t.subject_id', $subject_id)
                ->where('t.chapter_id', $chapter_id);
        }

        $lessonPlans = $query->orderBy('d.lesson_plan_headings_id', 'ASC')->get();

        if ($lessonPlans->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No lesson plan templates found.',
                'status' => 404
            ]);
        }

        $groupedData = $lessonPlans->groupBy('les_pln_temp_id')->map(function ($items) {
            return [
                'les_pln_temp_id' => $items[0]->les_pln_temp_id,
                'class_id' => $items[0]->class_id,
                'subject_id' => $items[0]->subject_id,
                'chapter_id' => $items[0]->chapter_id,
                'publish' => $items[0]->publish,
                'details' => $items->map(function ($i) {
                    return [
                        'detail_id' => $i->detail_id,
                        'lesson_plan_headings_id' => $i->lesson_plan_headings_id,
                        'heading_name' => $i->heading_name,
                        'description' => $i->description,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $groupedData,
            'message' => 'Lesson plan template fetched successfully.',
            'status' => 200
        ]);
    }

    public function getSubSubjectByClassSub(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $subject_id = $request->input('subject_id');
        $subSubjects = DB::table('chapters')
            ->distinct()
            ->select('sub_subject')
            ->where('sub_subject', '<>', '')
            ->where('class_id', $class_id)
            ->where('subject_id', $subject_id)
            ->where('academic_yr', $academic_yr)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subSubjects,
            'message' => 'Sub subjects fetched successfully.',
            'status' => 200
        ]);
    }
    public function getLessonPlan(Request $request)
    {
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $sm_id = $request->input('sm_id');
        $sub_subject = $request->input('sub_subject');
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');


        // if class_id OR section_id OR sm_id exists, call 1st query
        if (!empty($class_id) || !empty($section_id) || !empty($sm_id)) {

            $query = DB::table('lesson_plan')
                ->select(
                    'lesson_plan.*',
                    'class.name as c_name',
                    'section.name as secname',
                    'subject_master.name as sub_name',
                    'chapters.chapter_no',
                    'chapters.name',
                    'chapters.sub_subject'
                )
                ->join('class', 'lesson_plan.class_id', '=', 'class.class_id')
                ->join('section', 'lesson_plan.section_id', '=', 'section.section_id')
                ->join('subject_master', 'lesson_plan.subject_id', '=', 'subject_master.sm_id')
                ->join('chapters', 'lesson_plan.chapter_id', '=', 'chapters.chapter_id')
                ->where('chapters.isDelete', '!=', 'Y')
                ->where('lesson_plan.reg_id', $user->reg_id)
                ->where('lesson_plan.class_id', $class_id)
                ->where('lesson_plan.section_id', $section_id)
                ->where('lesson_plan.academic_yr', $academic_yr);

            // Optional filters
            if (!empty($sm_id)) {
                $query->where('lesson_plan.subject_id', $sm_id);
            }

            if (!empty($sub_subject)) {
                $query->where('chapters.sub_subject', 'like', '%' . $sub_subject . '%');
            }

            return $query->get();
        } else {
            // otherwise call second query
            $query = DB::select("select lesson_plan.*,lesson_plan_details.*,class.class_id,class.name as c_name,chapters.chapter_id,chapters.name,subject_master.name as sub_name from lesson_plan,lesson_plan_details,class,chapters,subject_master where lesson_plan.lesson_plan_id = lesson_plan_details.lesson_plan_id and lesson_plan.class_id=class.class_id and lesson_plan.chapter_id = chapters.chapter_id and chapters.isDelete!='Y' and lesson_plan.subject_id = subject_master.sm_id  and lesson_plan.reg_id='" . $user->reg_id . "' and lesson_plan.academic_yr='" . $academic_yr . "' group by lesson_plan.unq_id order by lesson_plan.lesson_plan_id DESC");
        }

        // send API response
        return response()->json([
            'status' => 200,
            'data' => $query,
            'success' => true
        ]);
    }

    public function getLPClassesByUnqId(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $unq_id = $request->input('unq_id');
        $classes = DB::select("SELECT lesson_plan_id,a.class_id,a.section_id, b.name as class_name, c.name as sec_name FROM lesson_plan a, class b, section c WHERE a.class_id=b.class_id and a.section_id=c.section_id and a.academic_yr = '" . $academic_yr . "' and unq_id= $unq_id order by class_id");
        return response()->json([
            'status' => 200,
            'message' => 'Lesson plan classes by unq id.',
            'data' => $classes,
            'success' => true
        ]);
    }

    public function deleteLessonPlan(Request $request, $unq_id)
    {
        $lp_details = DB::table('lesson_plan')
            ->select('lesson_plan_id')
            ->where('unq_id', $unq_id)
            ->get();

        // Step 2: If lesson plans found, delete related records
        if ($lp_details->count() > 0) {
            foreach ($lp_details as $row) {
                DB::table('lesson_plan_details')
                    ->where('lesson_plan_id', $row->lesson_plan_id)
                    ->delete();

                DB::table('lesson_plan')
                    ->where('lesson_plan_id', $row->lesson_plan_id)
                    ->delete();
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Lesson Plan deleted successfully',
            'success' => true
        ]);
    }

    public function updateStatusOfLessonPlan(Request $request, $unq_id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        DB::table('lesson_plan')
            ->where('unq_id', $unq_id)
            ->update([
                'status' => $request->status
            ]);

        return response()->json([
            'status' => 200,
            'message' => 'Lesson Plan status updated successfully',
            'success' => true
        ]);
    }

    public function saveLessonPlan(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        DB::beginTransaction();
        try {
            $data = [];
            $data['class_id'] = $request->input('class_id');

            $section_id_str = $request->input('section_id');
            $sec_id = explode(",", $section_id_str);

            $data['subject_id'] = $request->input('sm_id');
            $data['chapter_id'] = $request->input('chapter_id');
            $data['no_of_periods'] = $request->input('no_of_periods');
            $data['week_date'] = $request->input('weeklyDatePicker');
            $data['status'] = 'I';
            $data['les_pln_temp_id'] = $request->input('les_pln_temp_id');
            $data['reg_id'] = $user->reg_id;
            $data['academic_yr'] = $academic_yr;
            $data['approve'] = $request->input('approve');


            $data['lesson_plan_id'] = $request->input('lesson_plan_id');

            do {
                $unq = mt_rand(10000, 99999);
                $exists = DB::table('lesson_plan')->where('unq_id', $unq)->exists();
            } while ($exists);

            $data['unq_id'] = $unq;

            foreach ($sec_id as $section) {
                $data['section_id'] = $section;

                // Insert into lesson_plan
                $lessonPlanId = DB::table('lesson_plan')->insertGetId($data);

                // Non-daily headings
                $lesson_plan_headings = DB::table('lesson_plan_heading')
                    ->where('change_daily', '!=', 'Y')
                    ->orderBy('sequence', 'asc')
                    ->get();

                $d = 1;
                foreach ($lesson_plan_headings as $heading) {
                    $data1 = [];
                    $data1['lesson_plan_id'] = $lessonPlanId;
                    $data1['lesson_plan_headings_id'] = $heading->lesson_plan_headings_id;
                    $data1['description'] = $request->input('description_' . $heading->lesson_plan_headings_id . '_' . $d);
                    $data1['start_date'] = now();

                    DB::table('lesson_plan_details')->insert($data1);
                }

                // Daily change headings
                $lph_daily_change = $request->input('lph_dc_row', 0);
                $start_date = $request->input('start_date', []);

                for ($r = 1; $r <= $lph_daily_change; $r++) {
                    $lesson_plan_headings_daily_change = DB::table('lesson_plan_heading')
                        ->where('change_daily', 'Y')
                        ->orderBy('sequence', 'asc')
                        ->get();

                    foreach ($lesson_plan_headings_daily_change as $heading) {
                        $data2 = [];
                        $data2['lesson_plan_id'] = $lessonPlanId;
                        $data2['lesson_plan_headings_id'] = $heading->lesson_plan_headings_id;
                        $data2['description'] = $request->input('dc_description_' . $heading->lesson_plan_headings_id . '_' . $r);
                        $data2['start_date'] = !empty($start_date[$r - 1]) ? date('Y-m-d', strtotime($start_date[$r - 1])) : null;

                        DB::table('lesson_plan_details')->insert($data2);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson Plan Created Successfully',
                'status'  => 200
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLessonPlanDetails(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $classId = $request->query('class_id');
        $subjectId = $request->query('sm_id');
        $chapterId = $request->query('chapter_id');
        $classIdArray = $request->query('class_id_array', '');
        $lessonPlanTemplate = DB::table('lesson_plan_template')
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('chapter_id', $chapterId)
            ->where('publish', 'Y')
            ->first();
        if (!$lessonPlanTemplate) {
            return response()->json([
                'status' => 400,
                'message' => 'Lesson Plan Template is not created!!!',
                'success' => false
            ]);
        }


        $cIdArray = explode(',', $classIdArray);
        $secArray = [];
        foreach ($cIdArray as $val) {
            $secArray[] = substr($val, strpos($val, '^') + 1);
        }
        $sectionIds = implode(',', $secArray);


        $pageData = [];
        $pageData['class_id'] = $classId;
        $pageData['section_id'] = $sectionIds;
        $pageData['sm_id'] = $subjectId;
        $pageData['chapter_id'] = $chapterId;


        $lessonPlanData = DB::select("select lesson_plan_template.*,lesson_plan_template_details.* from lesson_plan_template,lesson_plan_template_details where lesson_plan_template.les_pln_temp_id = lesson_plan_template_details.les_pln_temp_id and lesson_plan_template.chapter_id='" . $chapterId . "' and subject_id='" . $subjectId . "' and class_id='" . $classId . "' and lesson_plan_template.publish='Y'");

        if (count($lessonPlanData) > 0) {
            $pageData['lesson_plan_info1'] = $lessonPlanData;
            $pageData['present_data'] = true;
            $lessonPlan = DB::table('lesson_plan')
                ->select('unq_id')
                ->where('chapter_id', $chapterId)
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->whereIn('section_id', explode(',', $sectionIds))
                ->first();

            $pageData['unq_id'] = $lessonPlan->unq_id ?? null;
        } else {
            $pageData['header_info'] = 'N';
            $pageData['create'] = true;
        }


        return response()->json([
            'status' => 200,
            'data'  => $pageData,
            'success' => true
        ]);
    }

    public function updateLessonPlan(Request $request, $unq_id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        DB::beginTransaction();
        try {
            $lpDetails = DB::table('lesson_plan')
                ->select('lesson_plan_id')
                ->where('unq_id', $unq_id)
                ->get();

            if ($lpDetails->count() > 0) {
                foreach ($lpDetails as $row) {

                    DB::table('lesson_plan_details')
                        ->where('lesson_plan_id', $row->lesson_plan_id)
                        ->delete();


                    DB::table('lesson_plan')
                        ->where('lesson_plan_id', $row->lesson_plan_id)
                        ->delete();
                }
            }
            $data = [];
            $data['class_id'] = $request->input('class_id');

            $section_id_str = $request->input('section_id');
            $sec_id = explode(",", $section_id_str);

            $data['subject_id'] = $request->input('sm_id');
            $data['chapter_id'] = $request->input('chapter_id');
            $data['no_of_periods'] = $request->input('no_of_periods');
            $data['week_date'] = $request->input('weeklyDatePicker');
            $data['status'] = 'I';
            $data['les_pln_temp_id'] = $request->input('les_pln_temp_id');
            $data['reg_id'] = $user->reg_id;
            $data['academic_yr'] = $academic_yr;
            $data['approve'] = $request->input('approve');


            $data['lesson_plan_id'] = $request->input('lesson_plan_id');

            do {
                $unq = mt_rand(10000, 99999);
                $exists = DB::table('lesson_plan')->where('unq_id', $unq)->exists();
            } while ($exists);

            $data['unq_id'] = $unq;

            foreach ($sec_id as $section) {
                $data['section_id'] = $section;

                // Insert into lesson_plan
                $lessonPlanId = DB::table('lesson_plan')->insertGetId($data);

                // Non-daily headings
                $lesson_plan_headings = DB::table('lesson_plan_heading')
                    ->where('change_daily', '!=', 'Y')
                    ->orderBy('sequence', 'asc')
                    ->get();

                $d = 1;
                foreach ($lesson_plan_headings as $heading) {
                    $data1 = [];
                    $data1['lesson_plan_id'] = $lessonPlanId;
                    $data1['lesson_plan_headings_id'] = $heading->lesson_plan_headings_id;
                    $data1['description'] = $request->input('description_' . $heading->lesson_plan_headings_id . '_' . $d);
                    $data1['start_date'] = null;

                    DB::table('lesson_plan_details')->insert($data1);
                }

                // Daily change headings
                $lph_daily_change = $request->input('lph_dc_row', 0);
                $start_date = $request->input('start_date', []);

                for ($r = 1; $r <= $lph_daily_change; $r++) {
                    $lesson_plan_headings_daily_change = DB::table('lesson_plan_heading')
                        ->where('change_daily', 'Y')
                        ->orderBy('sequence', 'asc')
                        ->get();

                    foreach ($lesson_plan_headings_daily_change as $heading) {
                        $data2 = [];
                        $data2['lesson_plan_id'] = $lessonPlanId;
                        $data2['lesson_plan_headings_id'] = $heading->lesson_plan_headings_id;
                        $data2['description'] = $request->input('dc_description_' . $heading->lesson_plan_headings_id . '_' . $r);
                        $data2['start_date'] = !empty($start_date[$r - 1]) ? date('Y-m-d', strtotime($start_date[$r - 1])) : null;

                        DB::table('lesson_plan_details')->insert($data2);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson Plan Updated Successfully',
                'status'  => 200
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLessonPlanByUnqId(Request $request, $unq_id)
    {
        $lessonPlan = DB::table('lesson_plan')
            ->where('unq_id', $unq_id)
            ->first();

        $lessonPlanDetails = DB::table('lesson_plan_details')
            ->where('lesson_plan_id', $lessonPlan->lesson_plan_id)
            ->get();

        $response = [
            'lesson_plan' => $lessonPlan,
            'details' => $lessonPlanDetails,
        ];

        return response()->json([
            'success' => true,
            'data'  => $response,
            'message' => 'Lesson Plan edit data.',
            'status'  => 200
        ]);
    }

    public function getSubjectName(Request $request, $sm_id)
    {
        $subject = DB::table('subject_master')
            ->where('sm_id', $sm_id)
            ->first(); // directly gets single column value

        return response()->json([
            'success' => true,
            'data' => $subject,
            'message' => 'Subject name.',
            'status' => 200
        ]);
    }

    public function getSubSubjectNameByChapterId(Request $request, $chapter_id)
    {
        $subSubject = DB::table('chapters')
            ->where('chapter_id', $chapter_id)
            ->get(); // returns a single column value

        if ($subSubject) {
            return response()->json([
                'success' => true,
                'data' => $subSubject,
                'message' => 'Sub Subject name.',
                'status' => 200
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No sub_subject found for the given chapter_id.'
        ], 404);
    }


    public function showListingOfProficiencyStudentsClass9(Request $request)
    {
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $from = $request->input('from');
        $to = $request->input('to');
        $studentslist = show_listing_of_proficiency_students_class9($class_id, $section_id, $from, $to);
        return response()->json([
            'success' => true,
            'data' => $studentslist,
            'message' => 'Students listing for proficiency certificates.',
            'status' => 200
        ]);
    }

    public function showListingOfProficiencyStudentsClass11(Request $request)
    {
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $from = $request->input('from');
        $to = $request->input('to');
        $studentslist = show_listing_of_proficiency_students_class11($class_id, $section_id, $from, $to);
        return response()->json([
            'success' => true,
            'data' => $studentslist,
            'message' => 'Students listing for proficiency certificates.',
            'status' => 200
        ]);
    }

    public function showListingOfProficiencyStudents(Request $request)
    {
        $user = $this->authenticateUser();
        $acd_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $from = $request->input('from');
        $to = $request->input('to');
        $term_id = $request->input('term_id');
        $max_highest_marks = $request->input('max_highest_marks');
        $studentslist = show_listing_of_proficiency_students($class_id, $section_id, $term_id, $from, $to, $acd_yr, $max_highest_marks);
        return response()->json([
            'success' => true,
            'data' => $studentslist,
            'message' => 'Students listing for proficiency certificates.',
            'status' => 200
        ]);
    }

    public function getMaxHighestMarksPerTerm(Request $request)
    {
        $user = $this->authenticateUser();
        $acd_yr = JWTAuth::getPayload()->get('academic_year');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $term_id = $request->input('term_id');
        $max_highest_marks = get_max_highest_marks_per_term($class_id, $section_id, $term_id);
        return response()->json([
            'success' => true,
            'data' => $max_highest_marks,
            'message' => 'Maximum highest marks per term.',
            'status' => 200
        ]);
    }

    public function dailyNotes(Request $request)
    {
        if ($request->login_type == 'T') {
            $str_array    =    $request->input('str_array');
            $str_classes =  str_replace(array('[', ']', '"'), '', $str_array);
            $str_classes_array = explode(",", $str_classes);

            if ($request->input('subject_id') == '') {
                $data['subject_id'] = 0;
            } else {
                $data['subject_id']            =    $request->input('subject_id');
            }

            $data['teacher_id']            =    $request->input('teacher_id');
            $data['description']        =    $request->input('description');
            $data['publish']        =    'N';
            $data['date'] = date_format(date_create($request->input('dailynote_date')), 'Y-m-d');
            $data['academic_yr'] = $request->input('academic_yr');

            if ($request->input('datafile') != '') {
                $datafile = $request->input('datafile');
            } else {
                $datafile = '';
            }

            $random_no = $request->input('random_no');
            $filename = $request->input('filename');
            $operation = $request->input('operation');

            if ($operation == 'create') {
                if ($filename == '' || $filename == '[]') {
                    //crudhelper
                    $status = daily_notes_create($data, $str_classes_array, '', '', $random_no);
                } else {
                    //crudhelper
                    $status = daily_notes_create($data, $str_classes_array, "", $filename, $random_no);
                }
                if (isset($status)) {
                    $response_array["dailynote"] = 'Records found';
                    $response_array["status"] = true;
                    return response()->json($response_array, 200);
                } else {
                    $response_array["Dailynotes"] = 'NO record Found';
                    $response_array["status"] = false;
                    return response()->json($response_array, 200);
                }
            }
            if ($operation == 'edit') {
                // return response()->json($request->all());
                $data['notes_id']        =    $request->input('notes_id');
                $data['section_id'] = $request->input('section_id');
                $data['class_id'] = $request->input('class_id');
                if ($request->input('datafile') != '') {
                    $datafile = $request->input('datafile'); // 21-07-20  
                } else {
                    $datafile = '';
                }
                // if there is a new file
                $filename = $request->input('filename');
                $deletefiledata =  $request->input('deleteimagelist');
                // start here 21-07-20 29-07-20
                if ($filename == '') {
                    //crudhelper
                    $statusedit = daily_notes_edit($data, $deletefiledata, '', '');
                } else {
                    //crudhelper
                    // deletefiledata => array of files to be deleted ["Image_file_1.jpg"]
                    $statusedit = daily_notes_edit($data, $deletefiledata, $datafile, $filename);
                }
                if ($statusedit) {
                    $response_array["dailynote"] = 'Records found';
                    $response_array["status"] = true;
                    return response()->json($response_array, 200);
                } else {
                    $response_array["Dailynotes"] = 'NO record Found';
                    $response_array["status"] = false;
                    return response()->json($response_array, 200);
                }
            }
            if ($operation == 'delete') {
                $notes_id        =    $request->input('notes_id');
                //crud helper
                $statusdelete = daily_notes_delete($notes_id);
                if ($statusdelete) {
                    $response_array["dailynote"] = 'Record Delete Successfully';
                    $response_array["status"] = TRUE;
                    return response()->json($response_array, 200);
                } else {
                    $response_array["Dailynotes"] = 'NO record Found';
                    $response_array["status"] = FALSE;
                    return response()->json($response_array, 200);
                }
            }
            if ($operation == 'publish') {
                $notes_id        =    $request->input('notes_id');
                $class_id        =   $request->input('class_id');
                $section_id        =    $request->input('section_id');
                $statuspublish = daily_notes_publish($notes_id, $class_id, $section_id);
                if ($statuspublish) {
                    $response_array["dailynotespublish"] = 'Teachers note published';
                    $response_array["status"] = TRUE;
                    return response()->json($response_array, 200);
                } else {
                    $response_array["dailynotespublish"] = 'Teachers note couldnot be published';
                    $response_array["status"] = FALSE;
                    return response()->json($response_array, 200);
                }
            }
        }
    }

    public function getdailyNotes(Request $request)
    {
        $teacher_id =     $request->input('reg_id');
        $acd_yr =     $request->input('acd_yr');
        //crud helper
        $daily_notes    =    get_daily_notes_teacherwise($teacher_id, $acd_yr);
        if ($daily_notes) {
            $response_array["status"] = TRUE;
            $response_array["daily_notes"] = $daily_notes;

            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No data found";
            return response()->json($response_array, 200);
        }
    }

    public function getImagesDailyNotes(Request $request)
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        $note_id = $request->input('note_id');
        $date = $request->input('dailynote_date');
        //crud helper
        $notes    =    get_notes_images_onnoteid($note_id);
        if ($notes) {
            $response_array["status"] = TRUE;
            $response_array["images"] = $notes;
            $response_array["url"] = $codeigniter_app_url . 'uploads/daily_notes/' . $date . '/' . $note_id;
            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No Records Found";
            return response()->json($response_array, 200);
        }
    }

    public function getStudentsNotesViewed(Request $request)
    {
        //crud helper 
        $students    =    get_students_viewed_note($request->input('notes_id'), $request->input('class_id'), $request->input('section_id'), $request->input('acd_yr'));
        if ($students) {
            $response_array["status"] = TRUE;
            $response_array["student_list"] = $students;
            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No Records Found";
            return response()->json($response_array, 200);
        }
    }

    public function uploadFiles(Request $request)
    {
        $upload_date      =   date_format(date_create($request->input('upload_date')), 'Y-m-d');
        $datafile = $request->input('datafile');
        $filename = $request->input('filename');
        $doc_type_folder = $request->input('doc_type_folder');
        $random_no = $request->input('random_no');
        $uploadfiles = upload_files($filename, $datafile, $upload_date, $doc_type_folder, $random_no);
        return response()->json($uploadfiles, 200);
    }

    public function deleteUploadedFiles(Request $request)
    {
        $upload_date      =   date_format(date_create($request->input('upload_date')), 'Y-m-d');
        $filename = $request->input('filename');
        $doc_type_folder = $request->input('doc_type_folder');
        $random_no = $request->input('random_no');
        $deletefiles = delete_uploaded_files($filename, $upload_date, $doc_type_folder, $random_no);
        return response()->json($deletefiles, 200);
    }

    public function getSubjectAllotedToTeacherByMultipleClass(Request $request)
    {
        $str_array    =    $request->input('str_array');
        $str_classes =  str_replace(array('[', ']', '"'), '', $str_array);

        $teacher_id    =    $request->input('reg_id');
        $academic_yr    =    $request->input('academic_yr');
        //crud helper
        $subject_name    =    get_subject_alloted_to_teacher_by_multipleclass($str_classes, $teacher_id, $academic_yr);

        if ($subject_name) {
            $response_array["status"] = TRUE;
            $response_array["subject_name"] = $subject_name;

            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No data found";
            return response()->json($response_array, 200);
        }
    }

    public function HomeworkCreateEditPublishDelete(Request $request)
    {
        if ($request->input('login_type') == 'A' || $request->input('login_type') == 'T') {
            // dd("Hello");
            $data['end_date']       =   date_format(date_create($request->input('end_date')), 'Y-m-d');
            $data['description'] =    $request->input('description');
            $data['teacher_id'] =   $request->input('teacher_id');
            $data['class_id'] =   $request->input('class_id');
            $data['section_id'] =  $request->input('section_id');
            $data['start_date']       = date_format(date_create($request->input('start_date')), 'Y-m-d');
            $data['sm_id'] =   $request->input('sm_id');
            $data['academic_yr'] = $request->input('academic_yr');
            $data['publish'] = 'N';
            if ($request->input('datafile') != '') {
                $datafile = $request->input('datafile'); // 21-07-20  
            } else {
                $datafile = '';
            }

            $filename = $request->input('filename');
            if ($request->input('operation') == 'create') {

                $random_no = $request->input('random_no');
                if ($filename == '') {
                    //crud helper
                    $status = homework_create($data, '', '', $random_no);
                } else {
                    //crud helper
                    $status = homework_create($data, $datafile, $filename, $random_no);
                }

                if ($status == TRUE) {
                    $response_array["status"] = TRUE;
                    $response_array["success_msg"] = "New homework created!!!";
                    return response()->json($response_array, 200);
                } else {
                    $response_array["status"] = FALSE;
                    $response_array["error_msg"] = "Homework could not be created!!!";
                    return response()->json($response_array, 200);
                }
            }

            if ($request->input('operation') == 'edit') {
                $homework_id            =    $request->input('homework_id');
                if ($request->input('datafile') != '') {
                    $datafile = $request->input('datafile'); // 21-07-20  
                } else {
                    $datafile = '';
                }
                $filename = $request->input('filename');
                $deletefiledata =  $request->input('deleteimagelist');
                // 29-07-20
                if ($filename == '') {
                    //crud helper
                    $status = homework_edit($homework_id, $data, $deletefiledata, '', '');
                } else {
                    //crud helper
                    $status = homework_edit($homework_id, $data, $deletefiledata, $datafile, $filename);
                }
                // ends here
                if ($status == TRUE) {
                    $response_array["status"] = TRUE;
                    $response_array["success_msg"] = "Homework updated!!!";
                    return response()->json($response_array, 200); // 200 being the HTTP response code
                } else {
                    $response_array["status"] = FALSE;
                    $response_array["error_msg"] = "Homework could not be updated!!!";
                    return response()->json($response_array, 200); // 200 being the HTTP response code
                }
            }

            if ($request->input('operation') == 'publish') {
                // dd("Hello");
                $homework_id            =    $request->input('homework_id');
                $class_id            =    $request->input('class_id');
                $section_id            =    $request->input('section_id');
                //crud helper
                $status = homework_publish($homework_id, $class_id, $section_id);

                if ($status == TRUE) {
                    $response_array["status"] = TRUE;
                    $response_array["success_msg"] = "Homework published!!!";
                    return response()->json($response_array, 200); // 200 being the HTTP response code
                } else {
                    $response_array["status"] = FALSE;
                    $response_array["error_msg"] = "Homework could not be published!!!";
                    return response()->json($response_array, 200); // 200 being the HTTP response code
                }
            }
            if ($request->input('operation') == 'delete') {
                $homework_id            =    $request->input('homework_id');
                $status = homework_delete($homework_id);
                if ($status == TRUE) {
                    $response_array["status"] = TRUE;
                    $response_array["success_msg"] = "Homework deleted!!!";
                    return response()->json($response_array, 200); // 200 being the HTTP response code
                } else {
                    $response_array["status"] = FALSE;
                    $response_array["error_msg"] = "Homework could not be delete!!!";
                    return response()->json($response_array, 200); // 200 being the HTTP response code
                }
            }
        }
    }

    public function getImagesHomework(Request $request)
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        $homework_id = $request->input('homework_id');
        $date = $request->input('homework_date');
        $homework    =    get_homework_images_onnoteid($homework_id);
        if ($homework) {
            $response_array["status"] = TRUE;
            $response_array["images"] = $homework;
            $response_array["url"] = $codeigniter_app_url . 'uploads/homework/' . $date . '/' . $homework_id;
            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No Records Found";
            return response()->json($response_array, 200);
        }
    }

    public function getHomework(Request $request)
    {
        $teacher_id = $request->input('reg_id');
        $acd_yr = $request->input('acd_yr');

        $homework_data    =    get_homework_teacherwise($teacher_id, $acd_yr);
        if ($homework_data) {
            $response_array["status"] = TRUE;
            $response_array["homework_details"] = $homework_data;
            return response()->json($response_array, 200); // 200 being the HTTP response code
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No data found";
            return response()->json($response_array, 200);
        }
    }

    public function getStudentWithHomeworkStatus(Request $request)
    {
        $data['homework_id']            =    $request->input('homework_id');
        $homework_comment_info    = homework_join_hw_comments($data['homework_id']);
        if ($data == TRUE) {
            $response_array["student_details"] = $homework_comment_info;
            return response()->json($response_array, 200);
        } else {
            $response_array["student_details"] = FALSE;
            $response_array["error_msg"] = "Homework Status Not Updated!!!";
            return response()->json($response_array, 200);
        }
    }

    public function getCountOfHomeworkComments(Request $request)
    {
        $homework_id = $request->input('homework_id');
        //crud helper
        $hw_comment_count    = get_count_of_homework_comments($homework_id);
        if ($hw_comment_count) {
            $response_array["status"] = TRUE;
            $response_array["comment_count"] = $hw_comment_count;
            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["comment_count"] = 0;
            return response()->json($response_array, 200);
        }
    }

    public function getStudentsHomeworkViewed(Request $request)
    {
        //crud helper
        $students    =    get_students_viewed_homework($request->input('homework_id'), $request->input('class_id'), $request->input('section_id'), $request->input('acd_yr'));
        if ($students) {
            $response_array["status"] = TRUE;
            $response_array["student_list"] = $students;
            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "No Records Found";
            return response()->json($response_array, 200);
        }
    }

    public function getProficiencyCertificatePublishValue(Request $request)
    {

        $student_id = $request->input('student_id');
        $term_id = $request->input('term_id');
        $publish_value = get_proficiency_certificate_publish_value($student_id, $term_id);
        return response()->json([
            'success' => true,
            'data' => $publish_value,
            'message' => 'Publish value.',
            'status' => 200
        ]);
    }

    public function publishProficiencyCertificate(Request $request)
    {

        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $action = $request->input('action'); // 'publish' or 'unpublish'
        $student_id = $request->input('student_id');
        $term_id = $request->input('term_id');
        $type_param = $request->input('type'); // 'g', 's', or 'b'


        $created_by = $user->reg_id;
        $login_type = $user->role_id;

        if ($login_type != 'T') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        if (!in_array($action, ['publish', 'unpublish'])) {
            return response()->json(['error' => 'Invalid action'], 400);
        }

        // Build data array
        $data = [
            'student_id'  => $student_id,
            'term_id'     => $term_id,
            'academic_yr' => $academic_yr,
            'created_by'  => $created_by,
            'publish'     => $action === 'publish' ? 'Y' : 'N',
            'type'        => $type_param === 'g' ? 'Gold' : ($type_param === 's' ? 'Silver' : 'Bronze'),
        ];

        // Check if record exists
        $existing = DB::table('proficiency_certificate')
            ->where('student_id', $student_id)
            ->where('term_id', $term_id)
            ->where('academic_yr', $academic_yr)
            ->first();

        if ($existing) {
            DB::table('proficiency_certificate')
                ->where('student_id', $student_id)
                ->where('term_id', $term_id)
                ->where('academic_yr', $academic_yr)
                ->update($data);
        } else {
            DB::table('proficiency_certificate')->insert($data);
        }

        return response()->json([
            'status' => 200,
            'message' => $action === 'publish'
                ? 'Proficiency certificate published successfully.'
                : 'Proficiency certificate unpublished successfully.',
            'success' => true
        ]);
    }

    public function downloadProficiencyCertificate(Request $request, $student_id, $term_id, $type)
    {
        try {
            $user = $this->authenticateUser();
            $academic_yr = JWTAuth::getPayload()->get('academic_year');

            // Get exam name
            $exam = DB::table('exam')->where('exam_id', $term_id)->value('name');
            $exam_name = $exam ?? 'Exam';

            if (stripos($exam_name, 'Term 1') !== false)
                $exam_name = substr($exam_name, stripos($exam_name, 'Term 1'), 6);
            if (stripos($exam_name, 'Term 2') !== false)
                $exam_name = substr($exam_name, stripos($exam_name, 'Term 2'), 6);
            // dd($exam_name);
            // Fetch student name
            $student = DB::table('student')->where('student_id', $student_id)->first();
            if (!$student) {
                return response()->json(['error' => 'Student not found'], 404);
            }

            $student_name = trim($student->first_name . ' ' . $student->mid_name . ' ' . $student->last_name);
            $term_name = DB::table('term')->where('term_id', $term_id)->value('name');
            // Get class and section
            $class = DB::table('class')->where('class_id', $student->class_id)->value('name');
            $section = DB::table('section')->where('section_id', $student->section_id)->value('name');

            // Determine term/exam label
            $term_label = "Examination " . $academic_yr;

            // Generate PDF HTML using view
            $pdf = PDF::loadView('pdf.proficiency_certificate', [
                'student_id' => $student_id,
                'term_id' => $term_id,
                'type' => $type,
                'exam_name' => $exam_name,
                'student_name' => $student_name,
                'class' => $class,
                'section' => $section,
                'acd_yr' => $academic_yr,
                'term_label' => $term_label,
                'term_name' => $term_name
            ])->setPaper('A4', 'landscape');

            $file_name = "Proficiency_Certificate_{$term_id}_{$student_name}.pdf";

            return $pdf->stream($file_name);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function updateHomework(Request $request)
    {
        $jsonencode = $request->data;
        $data = json_decode($jsonencode);
        $updated = 0;
        for ($i = 0; $i < (count($data->arraylist)); $i++) {
            $data1['comment'] = $data->arraylist[$i]->teachercomment;
            $data1['homework_status'] = $data->arraylist[$i]->homework_status;
            $data1['student_id'] = $data->arraylist[$i]->student_id;
            $homework_id = $data->arraylist[$i]->homework_id;

            $status = homework_updatestatus($homework_id, $data1);
            if ($status) {
                $updated++;
            }
        }
        if ($updated > 0) {
            $response_array["status"] = TRUE;
            $response_array["success_msg"] = "Homework comment updated!!!";
            return response()->json($response_array, 200);
        } else {
            $response_array["status"] = FALSE;
            $response_array["error_msg"] = "Homework comment could not be created!!!";
            return response()->json($response_array, 200);
        }
    }

    public function getStationeryReq(Request $request)
    {
        try {
            $staff_id = $request->input('staff_id'); // optional


            $query = DB::table('stationery_req')->select('*');

            if (!empty($staff_id)) {
                $query->where('staff_id', $staff_id);
            }

            $data = $query->get();

            return response()->json([
                'status' => 200,
                'message' => 'Stationery Request List.',
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching stationery requests',
                'error' => $e->getMessage()
            ]);
        }
    }

    // create
    public function createStationeryReq(Request $request)
    {
        try {

            $request->validate([
                'stationery_id' => 'required|integer',
                'quantity' => 'required|numeric|min:1',
                'description' => 'nullable|string|max:500',
                'staff_id' => 'required|integer',
                'status' => 'required|string|max:1'
            ]);

            $data = [
                'stationery_id' => $request->stationery_id,
                'quantity'      => $request->quantity,
                'description'   => $request->description,
                'staff_id'      => $request->staff_id,
                'status'        => $request->status,
                'date'          => Carbon::now()->format('Y-m-d'),
            ];


            $inserted = DB::table('stationery_req')->insert($data);

            if ($inserted) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'New Stationery Requisition Created!',
                    'success' => true,
                    'data'    => $data
                ]);
            } else {
                return response()->json([
                    'status'  => 500,
                    'message' => 'Failed to create stationery requisition.',
                    'success' => false,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error creating stationery requisition.',
                'error'   => $e->getMessage()
            ]);
        }
    }


    // edit
    public function updateStationeryReq(Request $request, $id)
    {
        try {
            // Validate request data
            $request->validate([
                'stationery_id' => 'required|integer',
                'quantity'      => 'required|numeric|min:1',
                'description'   => 'nullable|string|max:500',
                'staff_id'      => 'required|integer',
                'status'        => 'required|string|max:1'
            ]);

            // Build update data
            $data = [
                'stationery_id' => $request->stationery_id,
                'quantity'      => $request->quantity,
                'description'   => $request->description,
                'staff_id'      => $request->staff_id,
                'status'        => $request->status,
                'date'          => Carbon::now()->format('Y-m-d')
            ];

            // Check if requisition exists
            $exists = DB::table('stationery_req')->where('requisition_id', $id)->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stationery Requisition not found.'
                ], 404);
            }

            // Update record
            DB::table('stationery_req')->where('requisition_id', $id)->update($data);

            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Stationery Requisition updated successfully!',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating stationery requisition: ' . $e->getMessage()
            ], 500);
        }
    }

    // delete
    public function deleteStationeryReq($id)
    {
        try {
            // Check if requisition exists
            $requisition = DB::table('stationery_req')
                ->where('requisition_id', $id)
                ->first();

            if (!$requisition) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stationery requisition not found.'
                ], 404);
            }

            // Check approval status
            if ($requisition->status === 'P') {
                return response()->json([
                    'success' => false,
                    'message' => 'This stationery requisition is approved. Delete failed!'
                ], 400);
            }

            // Delete the record
            DB::table('stationery_req')->where('requisition_id', $id)->delete();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Stationery requisition deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting stationery requisition: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getExamsByYear($academic_yr)
    {
        try {
            $exams = DB::table('exam')
                ->where('academic_yr', $academic_yr)
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $exams
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pullFromPrevYear(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            // Only roles A or U allowed
            if (!in_array($user->role_id, ['A', 'U'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $request->validate([
                'exam_from_id' => 'required|integer',
                'exam_to_id'   => 'required|integer|different:exam_from_id',
            ]);

            $examFromId = $request->exam_from_id;
            $examToId   = $request->exam_to_id;

            $fromRows = DB::table('allot_mark_headings')
                ->where('exam_id', $examFromId)
                ->get();

            if ($fromRows->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No data found for exam_from_id.'
                ]);
            }

            foreach ($fromRows as $row) {
                // Get class name from previous year
                $className = DB::table('class')
                    ->where('class_id', $row->class_id)
                    ->value('name');

                if (!$className) continue;

                // Get class_id for the current academic year
                $classIdNew = DB::table('class')
                    ->where('name', $className)
                    ->where('academic_yr', $academicYear)
                    ->value('class_id');

                if (!$classIdNew) continue;

                // Check if data already exists
                $exists = DB::table('allot_mark_headings')
                    ->where('class_id', $classIdNew)
                    ->where('exam_id', $examToId)
                    ->where('sm_id', $row->sm_id)
                    ->where('marks_headings_id', $row->marks_headings_id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Data is already pulled'
                    ], 400);
                }

                // Insert the data
                DB::table('allot_mark_headings')->insert([
                    'class_id'          => $classIdNew,
                    'exam_id'           => $examToId,
                    'sm_id'             => $row->sm_id,
                    'marks_headings_id' => $row->marks_headings_id,
                    'academic_yr'       => $academicYear,
                    'highest_marks'     => $row->highest_marks,
                    'reportcard_highest_marks' => $row->reportcard_highest_marks
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Marks Allotment Data pulled successfully!'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    // Pull Marks Allotment
    public function pullMarksAllotment(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year');

            // Only roles A or U allowed
            if (!in_array($user->role_id, ['A', 'U'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Validate input
            $request->validate([
                'exam_from_id' => 'required|integer',
                'exam_to_id' => 'required|integer'
            ]);

            // Start transaction
            DB::beginTransaction();

            // Fetch headings from source exam
            $fromHeadings = DB::table('allot_mark_headings')
                ->where('exam_id', $request->exam_from_id)
                ->get();

            foreach ($fromHeadings as $row) {

                // Check if record already exists in target exam
                $exists = DB::table('allot_mark_headings')
                    ->where('class_id', $row->class_id)
                    ->where('exam_id', $request->exam_to_id)
                    ->where('sm_id', $row->sm_id)
                    ->where('marks_headings_id', $row->marks_headings_id)
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => 'Data is already pulled.'
                    ], 400);
                }

                // Insert new record
                DB::table('allot_mark_headings')->insert([
                    'class_id'          => $row->class_id,
                    'exam_id'           => $request->exam_to_id,
                    'sm_id'             => $row->sm_id,
                    'marks_headings_id' => $row->marks_headings_id,
                    'academic_yr'       => $row->academic_yr,
                    'highest_marks'     => $row->highest_marks,
                    'reportcard_highest_marks' => $row->reportcard_highest_marks,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Marks Allotment Data pulled successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while pulling data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Pull Previous Year Grades Data
    public function pullPreviousAcademicGrades(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $currentAcdYear = JWTAuth::getPayload()->get('academic_year');

            // Allow only user type U or A
            if (!in_array($user->role_id, ['A', 'U'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            DB::beginTransaction();

            //---------------------------------------
            // STEP 1: Check if grades already exist
            //---------------------------------------
            $existingGrades = DB::table('grade')
                ->join('class', 'class.class_id', '=', 'grade.class_id')
                ->where('grade.academic_yr', $currentAcdYear)
                ->count();

            if ($existingGrades > 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Data already present. Data cannot be pulled!'
                ]);
            }

            //---------------------------------------
            // STEP 2: Get current academic year details
            //---------------------------------------
            $acdDetails = DB::table('settings')
                ->where('academic_yr', $currentAcdYear)
                ->first();

            if (!$acdDetails) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Academic year details not found.'
                ]);
            }

            // Extract from and to years
            $fromYear = date('Y', strtotime($acdDetails->academic_yr_from));
            $toYear   = date('Y', strtotime($acdDetails->academic_yr_to));

            //---------------------------------------
            // STEP 3: Calculate previous academic year
            //---------------------------------------
            $prevAcademicYr = ($fromYear - 1) . "-" . ($toYear - 1);

            //---------------------------------------
            // STEP 4: Get previous year’s grades
            //---------------------------------------
            $prevGrades = DB::table('grade')
                ->join('class', 'grade.class_id', '=', 'class.class_id')
                ->where('grade.academic_yr', $prevAcademicYr)
                ->select('grade.*', 'class.name as class_name')
                ->orderBy('class.name')
                ->orderBy('grade.name')
                ->get();

            if ($prevGrades->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => "No grades found for previous academic year: $prevAcademicYr"
                ]);
            }

            //---------------------------------------
            // STEP 5: Insert into current academic year
            //---------------------------------------
            foreach ($prevGrades as $row) {

                $className = $row->class_name;

                // Get class id in the current academic year
                $newClass = DB::table('class')
                    ->where('name', $className)
                    ->where('academic_yr', $currentAcdYear)
                    ->first();

                if (!$newClass) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => "Class mapping not found for class: $className"
                    ]);
                }

                // Insert grade
                DB::table('grade')->insert([
                    'name'              => $row->name,
                    'class_id'          => $newClass->class_id,  // FIXED
                    'subject_type'      => $row->subject_type,
                    'grade_point_from'  => $row->grade_point_from,
                    'grade_point_upto'  => $row->grade_point_upto,
                    'mark_from'         => $row->mark_from,
                    'mark_upto'         => $row->mark_upto,
                    'comment'           => $row->comment,
                    'academic_yr'       => $currentAcdYear,
                    'created_at'        => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Data pulled successfully!'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while pulling data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateStaffDetails(Request $request, $id)
    {
        $user = $this->authenticateUser();

        DB::beginTransaction();

        try {
            // Fetch teacher record
            $teacher = Teacher::findOrFail($id);

            // Fields to update
            $updateData = [
                'name'              => $request->name,
                'sex'               => $request->sex,
                'address'           => $request->address,
                'permanent_address' => $request->permanent_address,
                'phone'             => trim($request->phone),
                'emergency_phone'   => trim($request->emergency_phone),
                'employee_id'       => $request->employee_id,
                'blood_group'       => $request->blood_group,
            ];

            // Update teacher record
            $teacher->fill($updateData);
            $teacher->updated_by = $user->reg_id;
            $teacher->save();

            // Get primary key
            $teacherPrimaryKey = $teacher->getKey();

            // =======================
            // 🔹 Handle confirmation
            // =======================
            $confirmValue = $request->confirm_status == "Y" ? "Y" : "N";

            // Check existing record
            $existing = DB::table('confirmation_teacher_idcard')
                ->where('teacher_id', $teacherPrimaryKey)
                ->first();

            if ($existing) {
                // Update
                DB::table('confirmation_teacher_idcard')
                    ->where('teacher_id', $teacherPrimaryKey)
                    ->update([
                        'confirm' => $confirmValue
                    ]);
            } else {
                // Insert
                DB::table('confirmation_teacher_idcard')
                    ->insert([
                        'teacher_id' => $teacherPrimaryKey,
                        'confirm' => $confirmValue
                    ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Teacher updated successfully!',
                'teacher' => $teacher,
                'confirm_status' => $confirmValue
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the teacher',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getTeacherIdCardDetails(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M') {

                $globalVariables = App::make('global_variables');
                $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

                $tc_id = $request->tc_id;
                $teacher_id = $request->teacher_id;

                //    both tc_id and teacher_id
                if (!empty($tc_id) && !empty($teacher_id)) {

                    $teacher = DB::table('teacher')
                        ->where('isDelete', 'N')
                        ->where('teacher_id', $teacher_id)
                        ->first();

                    if (!$teacher) {
                        return response()->json([
                            'status' => 404,
                            'message' => 'Teacher not found.',
                            'success' => false
                        ]);
                    }

                    // Check if tc_id matches
                    if ($teacher->tc_id != $tc_id) {
                        return response()->json([
                            'status' => 400,
                            'message' => 'This teacher is not present in that particular teacher category.',
                            'success' => false
                        ]);
                    }

                    // If tc_id matches → return the teacher
                    $teacher->teacher_image_url = $teacher->teacher_image_name
                        ? $codeigniter_app_url . 'uploads/teacher_image/' . $teacher->teacher_image_name
                        : null;

                    return response()->json([
                        'status' => 200,
                        'message' => 'Teacher details matched successfully.',
                        'data' => $teacher,
                        'success' => true
                    ]);
                }

                //   tc_id
                $query = DB::table('teacher')->where('isDelete', 'N');

                if (!empty($tc_id)) {
                    $query->where('tc_id', $tc_id);
                }

                //    teacher_id
                if (!empty($teacher_id)) {
                    $query->where('teacher_id', $teacher_id);
                }

                //  fetch all
                $staffdata = $query->orderBy('teacher_id', 'asc')->get()
                    ->map(function ($staff) use ($codeigniter_app_url) {
                        $imgUrl = $codeigniter_app_url . 'uploads/teacher_image/';
                        $staff->teacher_image_url = $staff->teacher_image_name
                            ? $imgUrl . $staff->teacher_image_name
                            : null;
                        return $staff;
                    });

                return response()->json([
                    'status' => 200,
                    'message' => 'Teacher ID card details.',
                    'data' => $staffdata,
                    'success' => true
                ]);
            }

            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized user.',
                'success' => false
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function UpdateTeacherProfileImage(Request $request)
    {
        $id = $request->teacher_id;
        $filename = $request->filename;
        $doc_type_folder = 'teacher_image';
        $base64File = $request->base64;
        DB::table('teacher')->where('teacher_id', $id)->update(['teacher_image_name' => $filename]);
        upload_teacher_profile_image_into_folder($id, $filename, $doc_type_folder, $base64File);
        return response()->json([
            'status' => 200,
            'message' => 'Teacher profile image update successfully.',
            'success' => true
        ]);
    }


    // public function getpendingteacheridcardreport(Request $request)
    // {
    //     try {
    //         $user = $this->authenticateUser();
    //         $customClaims = JWTAuth::getPayload()->get('academic_year');

    //         if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M') {

    //             $globalVariables = App::make('global_variables');
    //             $parent_app_url = $globalVariables['parent_app_url'];
    //             $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

    //             // JOIN teacher + confirmation_teacher_idcard and filter confirm == 'Y'
    //             $staffdata = DB::table('teacher as t')
    //                 ->leftJoin('confirmation_teacher_idcard as c', 'c.teacher_id', '=', 't.teacher_id')
    //                 ->select('t.*', 'c.confirm')
    //                 ->where('t.isDelete', 'N')
    //                 ->where('c.confirm', 'N')      // Only confirmed teachers
    //                 ->orderBy('t.teacher_id', 'asc')
    //                 ->get()
    //                 ->map(function ($staff) use ($codeigniter_app_url) {

    //                     $concatprojecturl = $codeigniter_app_url . 'uploads/teacher_image/';

    //                     if ($staff->teacher_image_name) {
    //                         $staff->teacher_image_url = $concatprojecturl . $staff->teacher_image_name;
    //                     } else {
    //                         $staff->teacher_image_url = null;
    //                     }

    //                     return $staff;
    //                 });

    //             return response()->json([
    //                 'status' => 200,
    //                 'message' => 'ID card details for the Staffs.',
    //                 'data' => $staffdata,
    //                 'success' => true
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'status' => 401,
    //                 'message' => 'This user does not have permission.',
    //                 'data' => $user->role_id,
    //                 'success' => false
    //             ]);
    //         }
    //     } catch (Exception $e) {
    //         \Log::error($e);
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }

    public function getpendingteacheridcardreport(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');

            if ($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M') {

                $globalVariables = App::make('global_variables');
                $parent_app_url = $globalVariables['parent_app_url'];
                $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

                // JOIN teacher + confirmation_teacher_idcard and filter confirm == 'Y'
                // $staffdata = DB::table('teacher as t')
                //     ->leftJoin('confirmation_teacher_idcard as c', 'c.teacher_id', '=', 't.teacher_id')
                //     ->select('t.*', 'c.confirm')
                //     ->where('t.isDelete', 'N')
                //     ->where('c.confirm', 'N')      // Only confirmed teachers
                //     ->orderBy('t.teacher_id', 'asc')
                //     ->get()
                $staffdata = DB::table('teacher as t')
                    ->leftJoin(
                        'confirmation_teacher_idcard as c',
                        'c.teacher_id',
                        '=',
                        't.teacher_id'
                    )
                    ->select('t.*')
                    ->where('t.isDelete', 'N')
                    ->whereNull('c.teacher_id') //  NOT present in confirmation table
                    ->orderBy('t.teacher_id', 'asc')
                    ->get()
                    ->map(function ($staff) use ($codeigniter_app_url) {

                        $concatprojecturl = $codeigniter_app_url . 'uploads/teacher_image/';

                        if ($staff->teacher_image_name) {
                            $staff->teacher_image_url = $concatprojecturl . $staff->teacher_image_name;
                        } else {
                            $staff->teacher_image_url = null;
                        }

                        return $staff;
                    });

                return response()->json([
                    'status' => 200,
                    'message' => 'ID card details for the Staffs.',
                    'data' => $staffdata,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This user does not have permission.',
                    'data' => $user->role_id,
                    'success' => false
                ]);
            }
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function showReportCard(Request $request)
    {
        $short_name = JWTAuth::getPayload()->get('short_name');
        $class_id = $request->input('class_id');
        $academic_yr = $request->input('academic_yr');
        $student_id = $request->input('student_id');
        $class_name = DB::table('class')->where('class_id', $class_id)->value('name');
        if ($short_name == 'SACS') {
            switch ($class_name) {
                case 'Nursery':
                    return PDF::loadView('reportcard.SACS.nursery_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'LKG':
                    return PDF::loadView('reportcard.SACS.lkg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'UKG':
                    return PDF::loadView('reportcard.SACS.ukg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '1':
                case '2':
                    return PDF::loadView('reportcard.SACS.class1to2_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '3':
                case '4':
                case '5':
                    return PDF::loadView('reportcard.SACS.class3to5_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '6':
                case '7':
                case '8':
                    return PDF::loadView('reportcard.SACS.class6to8_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '9':
                case '10':
                    return PDF::loadView('reportcard.SACS.class9to10_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                default:
                    abort(404, 'Invalid class');
            }
        } elseif ($short_name == 'HSCS') {
            switch ($class_name) {
                case 'Nursery':
                    return PDF::loadView('reportcard.HSCS.nursery_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'LKG':
                    return PDF::loadView('reportcard.SACS.lkg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'UKG':
                    return PDF::loadView('reportcard.SACS.ukg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '1':
                case '2':
                    return PDF::loadView('reportcard.HSCS.class1to2_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '3':
                case '4':
                case '5':
                    return PDF::loadView('reportcard.HSCS.class1to5_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '6':
                case '7':
                case '8':
                    return PDF::loadView('reportcard.HSCS.class6to8_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '9':
                case '10':
                    return PDF::loadView('reportcard.HSCS.class9to10_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                default:
                    abort(404, 'Invalid class');
            }
        } else {
        }

        $pdf = PDF::loadView('pdf.template', compact('data'));

        // $pdf = PDF::loadView('pdf.simplebonafide', compact('data'))->setPaper('A5', 'landscape');

    }

    // tecaher data using reg_id for teacher id card detials
    // public function teacherDataIdCard($id)
    // {
    //     try {
    //         // Find the teacher by ID
    //         $teacher = DB::table('teacher')
    //             ->where('teacher.teacher_id', $id)
    //             ->select('teacher.*') // or any user fields you need
    //             ->first();
    //         $globalVariables = App::make('global_variables');
    //         $parent_app_url = $globalVariables['parent_app_url'];
    //         $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
    //         $concatprojecturl = $codeigniter_app_url . "" . 'uploads/teacher_image/';

    //         // Check if the teacher has an image and generate the URL if it exists
    //         if ($teacher->teacher_image_name) {
    //             $teacher->teacher_image_name = $concatprojecturl . "" . "$teacher->teacher_image_name";
    //         } else {
    //             $teacher->teacher_image_name = null;
    //         }


    //         // Find the associated user record
    //         $user = DB::table('user_master')->where('reg_id', $id)->whereNotIn('role_id', ['P', 'S'])->first();

    //         return response()->json([
    //             'teacher' => $teacher,
    //             'user' => $user,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while fetching the teacher details',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function teacherDataIdCard($id)
    {
        try {
            $teacher = DB::table('teacher as t')
                ->leftJoin(
                    'confirmation_teacher_idcard as c',
                    'c.teacher_id',
                    '=',
                    't.teacher_id'
                )
                ->where('t.teacher_id', $id)
                ->select(
                    't.*',
                    DB::raw("CASE 
                    WHEN c.teacher_id IS NOT NULL THEN 'Y' 
                    ELSE 'N' 
                END as confirm")
                )
                ->first();

            if (!$teacher) {
                return response()->json([
                    'message' => 'Teacher not found'
                ], 404);
            }

            $globalVariables = App::make('global_variables');
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
            $concatprojecturl = $codeigniter_app_url . 'uploads/teacher_image/';

            // ✅ Image handling
            if (!empty($teacher->teacher_image_name)) {
                $teacher->teacher_image_name = $concatprojecturl . $teacher->teacher_image_name;
            } else {
                $teacher->teacher_image_name = null;
            }

            // ✅ User data (excluding Parent & Student)
            $user = DB::table('user_master')
                ->where('reg_id', $id)
                ->whereNotIn('role_id', ['P', 'S'])
                ->first();

            return response()->json([
                'teacher' => $teacher,
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the teacher details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // get api for fetch the teacher image 

    public function getTeacherImageById(Request $request, $teacher_id)
    {
        try {
            $user = $this->authenticateUser();

            if (!in_array($user->role_id, ['A', 'T', 'M', 'P', 'T'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'No permission',
                    'success' => false
                ]);
            }

            $globalVariables = App::make('global_variables');
            $codeigniter_app_url = $globalVariables['codeigniter_app_url'];

            $teacher = DB::table('teacher')
                ->select('teacher_id', 'teacher_image_name')
                ->where('teacher_id', $teacher_id)
                ->where('isDelete', 'N')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Teacher not found',
                    'success' => false
                ]);
            }

            // ✅ Build image URL
            $teacher->teacher_image_url = $teacher->teacher_image_name
                ? $codeigniter_app_url . 'uploads/teacher_image/' . $teacher->teacher_image_name
                : null;

            return response()->json([
                'status' => 200,
                'message' => 'Teacher image fetched successfully',
                'data' => $teacher,
                'success' => true
            ]);
        } catch (Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function pdfDownloadAllReportCard(Request $request){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $short_name = JWTAuth::getPayload()->get('short_name');
        $class_id = $request->input('class_id');
        $academic_yr = $request->input('academic_yr');
        $section_id = $request->input('section_id');
        $stud_count = $request->input('stud_count');
        $class_name = DB::table('class')->where('class_id', $class_id)->value('name');
        if ($short_name == 'SACS') {
            switch ($class_name) {
                case 'Nursery':
                    return PDF::loadView('reportcard.SACS.nursery_report_card_pdf', compact('section_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'LKG':
                    return PDF::loadView('reportcard.SACS.lkg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'UKG':
                    return PDF::loadView('reportcard.SACS.ukg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '1':
                case '2':
                    return PDF::loadView('reportcard.SACS.class1to2_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '3':
                case '4':
                case '5':
                    return PDF::loadView('reportcard.SACS.class3to5_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '6':
                case '7':
                case '8':
                    return PDF::loadView('reportcard.SACS.class6to8_report_card_pdf_all', compact('section_id', 'class_id','stud_count', 'academic_yr'))->stream();
                    break;

                case '9':
                case '10':
                    return PDF::loadView('reportcard.SACS.class9to10_report_card_pdf_all', compact('section_id', 'class_id','stud_count', 'academic_yr'))->stream();
                    break;

                default:
                    abort(404, 'Invalid class');
            }
        } elseif ($short_name == 'HSCS') {
            switch ($class_name) {
                case 'Nursery':
                    return PDF::loadView('reportcard.HSCS.nursery_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'LKG':
                    return PDF::loadView('reportcard.SACS.lkg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case 'UKG':
                    return PDF::loadView('reportcard.SACS.ukg_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '1':
                case '2':
                    return PDF::loadView('reportcard.HSCS.class1to2_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '3':
                case '4':
                case '5':
                    return PDF::loadView('reportcard.HSCS.class1to5_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '6':
                case '7':
                case '8':
                    return PDF::loadView('reportcard.HSCS.class6to8_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                case '9':
                case '10':
                    return PDF::loadView('reportcard.HSCS.class9to10_report_card_pdf', compact('student_id', 'class_id', 'academic_yr'))->stream();
                    break;

                default:
                    abort(404, 'Invalid class');
            }
        } else {
        }

        $pdf = PDF::loadView('pdf.template', compact('data'));

    }

    public function checkPublishStatusOfReportCard(Request $request){
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');
        $short_name = JWTAuth::getPayload()->get('short_name');
        $class_name = DB::table('class')->where('class_id', $class_id)->value('name');
        if($class_name=='9' || $class_name=='11'){
            $publish = check_cbse_rc_publish_of_a_class($class_id,$section_id);
        }else{
            $publish = check_rc_publish_of_a_class($class_id,$section_id,'');
        }
        return response()->json([
          'status'=>200,
          'message'=>'Publish status of report card',
          'data'=>$publish,
          'success'=>true
        ]);

    }

    public function getStudentIdOfStudentParticularYear(Request $request){
        $academic_yr = $request->input('academic_yr');
        $current_student_id = $request->input('student_id');
        $student_id_in_particular_yr= get_student_id_of_a_student_in_particular_yr($current_student_id,$academic_yr);//Lija 21-07-22
        return response()->json([
          'status'=>200,
          'message'=>'Student id of student particular year',
          'data'=>$student_id_in_particular_yr,
          'success'=>true
        ]);
    }
}
