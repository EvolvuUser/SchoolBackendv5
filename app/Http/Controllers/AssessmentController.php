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


}