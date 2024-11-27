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


class AssessmentController extends Controller
{
    public function getMarksheadingsList(Request $request)
    {
         
        $marks_headings = MarksHeadings::orderBy('marks_headings_id')->get();
        
        return response()->json($marks_headings);
    }
    
    public function saveMarksheadings(Request $request)
    {
        
        $messages = [
            'name.required' => 'Name field is required.',
            'written_exam.required' => 'Written exam field is required.',
            'sequence.required' => 'Sequence field is required.'
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
                    'required'
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
                'sequence.required' => 'Sequence field is required.'
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
                    'required'
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
                $status_msg="Grade is saved successfully.";
                
            }

        }
        return response()->json([
            'status' => 201,
            'message' => $status_msg,
        ], 201);
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

        $exams = Exams::where('academic_yr', $academicYr)->orderBy('name', 'asc')->get();
       
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
        $exams->comment = $validatedData['comment'];
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
        $exams->comment = $validatedData['comment'];
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
                'error' => 'This subject is in use. Deletion failed!'
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
    
    public function saveAllotMarkheadings(Request $request)
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
}