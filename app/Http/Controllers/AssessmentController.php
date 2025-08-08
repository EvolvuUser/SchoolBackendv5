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
                    'required','unique:marks_headings,sequence',
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
        }else{
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
        }else{
        
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
        
        $query = Grades::with('Class');
        $grades = $query->orderBy('grade_id', 'DESC') 
                             ->get();
 
        return response()->json($grades);
    }
    
    public function saveGrades(Request $request)
    {
        $status_msg="";
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
            $grades->name = $request->input('name');//$validatedData['name'];
            $grades->mark_from = $request->input('mark_from');//$validatedData['mark_from'];
            $grades->mark_upto = $request->input('mark_upto');//$validatedData['mark_upto'];
            $grades->comment = $request->input('comment');//$validatedData['comment'];
            $grades->academic_yr = $academicYr;

            $existing_grades = Grades::where('name', $request->input('name'))->where('class_id', $class_id)->where('subject_type', $request->input('subject_type'))->first();
            if (!$existing_grades) {
                $grades->save();
                $status=201;
                $status_msg="Grade is saved successfully.";
                
            }
            else{
                $status = 400;
                $status_msg="Grade already exist for this class.";
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
        }else{
        
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

        $exams = Exams::where('academic_yr', $academicYr)->orderBy('exam_id','DESC')->get();
       
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
        }else{
        
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
                
        $allot_mark_headings = Allot_mark_headings::with('getClass', 'getSubject', 'getExam','getMarksheading')->where('class_id', $class_id)->where('academic_yr', $academicYr)->get();
        
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
            $allot_mark_heading->academic_yr = $academicYr;
            $allot_mark_heading->save();
            $status_msg="Marks heading is allocated successfully.";
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
        }else{
        
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
        $allot_mark_heading = Allot_mark_headings::with('getClass', 'getSubject', 'getExam','getMarksheading')->where('allot_markheadings_id', $allot_markheadings_id)->get();
        
              
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
            $allot_mark_heading->save();
        
            // Return success response
            return response()->json([
                'status' => 200,
                'message' => 'Allot markheading data updated successfully',
            ]);

    }

    public function deleteAllotMarksheadingg(Request $request,$class_id,$subject_id,$exam_id){
        $allotmarkheading = DB::table('student_marks')->where('class_id',$class_id)->where('subject_id',$subject_id)->where('exam_id',$exam_id)->first();
        if ($allotmarkheading) {
            $classname = DB::table('class')->where('class_id',$class_id)->select('name')->first();
            //  dd($classname);
            if ($classname) {
                $className = $classname->name;
               
            } else {
                $className = 'Unknown Class'; // If class not found, provide a default name
            }
            $examname = DB::table('exam')->where('exam_id',$exam_id)->select('name')->first();
            if ($examname) {
                $examName = $examname->name;
               
            } else {
                $examName = 'Unknown Exam'; // If class not found, provide a default name
            }
            
            $subjectname = DB::table('subject_master')->where('sm_id',$subject_id)->select('name')->first();
            if ($subjectname) {
                $subjectName = $subjectname->name;
               
            } else {
                $subjectName = 'Unknown Subject'; // If class not found, provide a default name
            }
            
             return response([
                   'status'=>400,
                   'message'=>"This Allot marks heading for class ".$className." , Exam ".$examName." and subject ".$subjectName." is in use. Delete failed!!!",
                   'success'=>false
              ]);
         
        } 
        else {
          DB::table('allot_mark_headings')->where('class_id',$class_id)->where('sm_id',$subject_id)->where('exam_id',$exam_id)->delete();
          $classname = DB::table('class')->where('class_id',$class_id)->select('name')->first();
            //  dd($classname);
            if ($classname) {
                $className = $classname->name;
               
            } else {
                $className = 'Unknown Class'; // If class not found, provide a default name
            }
            $examname = DB::table('exam')->where('exam_id',$exam_id)->select('name')->first();
            if ($examname) {
                $examName = $examname->name;
               
            } else {
                $examName = 'Unknown Exam'; // If class not found, provide a default name
            }
            
            $subjectname = DB::table('subject_master')->where('sm_id',$subject_id)->select('name')->first();
            if ($subjectname) {
                $subjectName = $subjectname->name;
               
            } else {
                $subjectName = 'Unknown Subject'; // If class not found, provide a default name
            }
          return response([
                   'status'=>200,
                   'message'=>"Allot Mark Headings for class ".$className." , Exam ".$examName." and subject ".$subjectName." Deleted Successfully.",
                   'success'=>true
              
              ]);
            
        }
        
    }

    public function getMarkheadingsForClassSubExam($class_id,$subject_id,$exam_id)
    {
        $allot_mark_heading = Allot_mark_headings::where('class_id', $class_id)->where('sm_id', $subject_id)->where('exam_id', $exam_id)->get(['marks_headings_id', 'highest_marks']);
        
              
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
                    'sub_rc_master_id' => $sub_rc_master_id,
                    'sub_mapping' => uniqid() // or any mapping logic
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

                // JOIN with subject_master and subjects_on_report_card_master
                $subjectmappinglist = DB::table('sub_subreportcard_mapping')
                    ->select(
                        'sub_subreportcard_mapping.sub_mapping',
                        'sub_subreportcard_mapping.sm_id',
                        'subject_master.name as sub_name',
                        'sub_subreportcard_mapping.sub_rc_master_id',
                        'subjects_on_report_card_master.name as report_sub_name',
                        'subjects_on_report_card_master.sequence'
                    )
                    ->join('subject_master', 'sub_subreportcard_mapping.sm_id', '=', 'subject_master.sm_id')
                    ->join('subjects_on_report_card_master', 'sub_subreportcard_mapping.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
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
    public function createBookRequisition(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method to get logged-in user
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional, if needed

            // 2. Check role permission
            if (in_array($user->role_id, ['S', 'T', 'U', 'A'])) {
                // 3. Validate request data
                $validated = $request->validate([
                    'title'     => 'required|string',
                    'author'    => 'nullable|alpha',
                    'publisher' => 'nullable|alpha',
                ]);

                // 4. Prepare data
                $data = [
                    'title'       => $validated['title'],
                    'author'      => $validated['author'] ?? null,
                    'publisher'   => $validated['publisher'] ?? null,
                    'status'      => 'A',
                    'req_date'    => now()->toDateString(),
                    'member_type' => $user->role_id,
                    // == 'S' ? 'S' : 'T'
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
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'This user does not have permission to create a book requisition.',
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

    public function getBookRequisitionInfo($book_req_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method to get logged-in user
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Check role permission
            if (in_array($user->role_id, ['S', 'T', 'U', 'A'])) {
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
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized access to requisition info.',
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
            $user = $this->authenticateUser(); // Custom method to get logged-in user
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional


            if (in_array($user->role_id, ['S', 'T', 'U', 'A'])) {

                $query = DB::table('book_req');

                //  only S and T in existing
                if ($user->role_id == 'S' || $user->role_id == 'T' || $user->role_id == 'A' || $user->role_id == 'U') {
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
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to view book requisitions.',
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

    public function getBookRequisition(Request $request)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser(); // Custom method to get logged-in user
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional

            // 2. Check role permission
            if (in_array($user->role_id, ['S', 'T', 'U', 'A'])) {
                // 3. Get optional filters from request
                $reg_id = $request->input('reg_id', '');
                $user_type = $request->input('user_type', '');

                // 4. Build query
                $query = DB::table('book_req');

                if (!empty($reg_id)) {
                    $query->where('member_id', $reg_id);
                }

                if (!empty($user_type)) {
                    $query->where('member_type', $user_type);
                }

                // Optional: Students/Teachers can only view their own records
                if (in_array($user->role_id, ['S', 'T'])) {
                    $query->where('member_id', $user->reg_id);
                }

                // 5. Execute query
                $books = $query->orderByDesc('book_req_id')->get();

                // 6. Check if empty
                if ($books->isEmpty()) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'No book requisitions found.',
                        'success' => false
                    ]);
                }

                // 7. Return data
                return response()->json([
                    'status' => 200,
                    'data' => $books,
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to view book requisitions.',
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

    public function updateBookRequisition(Request $request, $book_req_id)
    {
        try {
            // 1. Authenticate user
            $user = $this->authenticateUser();
            $academicYear = JWTAuth::getPayload()->get('academic_year'); // Optional if needed

            // 2. Check role permission
            if (!in_array($user->role_id, ['S', 'T', 'U', 'A'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to update book requisition.',
                    'success' => false
                ]);
            }

            // 3. Validate request
            $validated = $request->validate([
                'title'     => 'required|string',
                'author'    => 'nullable|alpha',
                'publisher' => 'nullable|alpha',
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

            // 5. Prevent update if not owned (for Students/Teachers)
            if (in_array($user->role_id, ['S', 'T']) && $existing->member_id != $user->reg_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to edit this requisition.',
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
                'member_type' => $user->role_id,
                // == 'S' ? 'S' : 'T'
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

            // 2. Check role permission
            if (!in_array($user->role_id, ['S', 'T', 'U', 'A'])) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthorized to delete book requisition.',
                    'success' => false
                ]);
            }

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

}