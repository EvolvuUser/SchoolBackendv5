<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;

class HscController extends Controller
{

    public function getSubjectGroup(){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                  
                 $subjectgroup = DB::table('subject_group')->get();
                 return response()->json([
                    'status'=> 200,
                    'data'=>$subjectgroup,
                    'message'=>'All Subject Groups',
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


    public function getOptionalSubject(){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                  
                 $optionalsubject = DB::table('subject_master')->where('subject_type','Optional')->get();
                 return response()->json([
                    'status'=> 200,
                    'data'=>$optionalsubject,
                    'message'=>'All Optional Subjects',
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

    public function getSubjectStudentwise($class_id,$section_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            if($user->role_id == 'A' || $user->role_id == 'T' || $user->role_id == 'M'){
                  
                 $studentwisesubject = DB::table('subjects_higher_secondary_studentwise')
                                      ->join('student','student.student_id','=','subjects_higher_secondary_studentwise.student_id')
                                      ->where('student.class_id',$class_id)
                                      ->where('student.section_id',$section_id)
                                      ->select('subjects_higher_secondary_studentwise.student_id', 'subjects_higher_secondary_studentwise.sub_group_id' ,'subjects_higher_secondary_studentwise.opt_subject_id' )
                                      ->get();
                                
                 return response()->json([
                    'status'=> 200,
                    'data'=>$studentwisesubject,
                    'message'=>'Studentwise Subjects',
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

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }
    
}
