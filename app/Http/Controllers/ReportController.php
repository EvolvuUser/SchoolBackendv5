<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReportController extends Controller
{
    //Reports Dev Name - Manish Kumar Sharma 01-03-2025
     public function getClassofNewStudent(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $classes = DB::table('new_admission_class as n')
                            ->join('class', 'n.class_id', '=', 'class.class_id')
                            ->where('n.academic_yr',$customClaims)
                            ->select('n.*', 'class.name')
                            ->get();
                            
                return response([
                    'status'=>200,
                    'data'=>$classes,
                    'message'=>'Classes for New Admission',
                    'success'=>true
                ]);

            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
     }
     //Reports Dev Name - Manish Kumar Sharma 01-03-2025
     private function authenticateUser()
        {
            try {
                return JWTAuth::parseToken()->authenticate();
            } catch (JWTException $e) {
                return null;
            }
        }
    //Reports Dev Name - Manish Kumar Sharma 03-03-2025
    public function getReportofNewAdmission(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                $class_id = $request->input('class_id'); // Get class_id from request
                $status = $request->input('status'); // Get status from request
                $admissionreport = DB::table('online_admission_form')
                                    ->join('online_admfee', 'online_admission_form.form_id', '=', 'online_admfee.form_id')
                                    ->join('class', 'class.class_id', '=', 'online_admission_form.class_id')
                                    ->select('online_admission_form.*',
                                            'online_admfee.form_id',
                                            'online_admfee.status',
                                            'online_admfee.payment_date',
                                            'online_admfee.OrderId',
                                            'class.name as classname')
                                    ->where('online_admission_form.academic_yr', '2024-2025')
                                    ->where('online_admfee.status', 'S')
                                    ->orderBy('online_admission_form.adm_form_pk', 'ASC');

                                // Apply filters for class_id and status if they are provided
                                if ($class_id) {
                                    $admissionreport->where('online_admission_form.class_id', $class_id);
                                }

                                if ($status) {
                                    $admissionreport->where('online_admission_form.admission_form_status', $status);
                                }

                                // Get results and map sibling info
                                $admissionreport = $admissionreport->get()->map(function ($row) {
                                    if ($row->sibling == 'Y') {
                                        if($row->sibling_class_id != '0'){
                                        $class_id = substr($row->sibling_class_id, 0, strpos($row->sibling_class_id, '^'));
                                        $section_id = substr($row->sibling_class_id, strpos($row->sibling_class_id, '^') + 1);
                                        // dd($section_id);
                                        $class_name = DB::table('class')
                                                        ->where('class_id', $class_id)
                                                        ->select('name')
                                                        ->first();
                                                        // dd($class_name);

                                        $section_name = DB::table('section')
                                                        ->where('section_id', $section_id)
                                                        ->select('name')
                                                        ->first();
                                        //  dd($section_name);
                                        // Set sibling student info
                                        $row->sibling_student_info = $row->sibling_student_id . " (" . $class_name->name . " " . $section_name->name . ")";
                                        }
                                        else{
                                             $row->sibling_student_info = $row->sibling_student_id."()";
                                        }
                                        // $row->sibling_student_info = $row->sibling_student_id."()";

                                    } 
                                    else {
                                        $row->sibling_student_info = "No";
                                    }

                                    return $row;
                                });

                                     return response([
                                        'status'=>200,
                                        'data'=>$admissionreport,
                                        'message'=>'Classes for New Admission',
                                        'success'=>true
                                    ]);
                                     
            }
            else{
                return response()->json([
                    'status'=> 401,
                    'message'=>'This User Doesnot have Permission for the Deleting of Data',
                    'data' =>$user->role_id,
                    'success'=>false
                        ]);
                }
    
            }
            catch (Exception $e) {
            \Log::error($e); 
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

    }
}
