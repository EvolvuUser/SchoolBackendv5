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
         
        $grades = Grades::orderBy('grade_id')->get();
        
        return response()->json($grades);
    }
    
    public function saveGrades(Request $request)
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
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }
    
        $grades = new Grades();
        $grades->class_id = trim($validatedData['class_id']);
        $grades->subject_type = $validatedData['subject_type'];
        $grades->name = $validatedData['name'];
        $grades->mark_from = trim($validatedData['mark_from']);
        $grades->mark_upto = $validatedData['mark_upto'];
        $grades->comment = $validatedData['comment'];
        $grades->academic_yr = $academicYr;

        
        $existing_grades = Grades::where('name', $validatedData['name'])->where('class_id', $validatedData['class_id'])->where('subject_type', $validatedData['subject_type'])->first();
        if (!$existing_grades) {
            $grades->save();
            return response()->json([
                'status' => 201,
                'message' => 'Grade is saved successfully.',
            ], 201);
        }else{
            return response()->json([
                'error' => 404,
                'message' => 'Grade already exists.',
            ], 404);
        }
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
        $grades->class_id = trim($validatedData['class_id']);
        $grades->subject_type = $validatedData['subject_type'];
        $grades->name = $validatedData['name'];
        $grades->mark_from = trim($validatedData['mark_from']);
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

}