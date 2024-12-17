<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use Illuminate\Support\Facades\Validator;
use DB;

class NewController extends Controller
{
    public function getCaretakerList(){
        $caretakerlist = Teacher::where('designation', '=', 'Caretaker')
        ->get();
        return response()->json([
                'status'=> 200,
                'message'=>'Caretaker List',
                'data' =>$caretakerlist,
                'success'=>true
              ]);
    }

    public function storeCaretaker(Request $request){
            
            try{
            $validator = Validator::make($request->all(),[
                'employee_id' => 'required|string|unique:teacher,employee_id',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'status' => 422,
                            'errors' => $validator->errors(),
                        ], 422);
                }
            $caretaker = new Teacher();
            $caretaker->name=$request->name;
            $caretaker->birthday=$request->birthday;
            $caretaker->date_of_joining=$request->date_of_joining;
            $caretaker->academic_qual=$request->academic_qual;
            $caretaker->aadhar_card_no=$request->aadhar_card_no;
            $caretaker->sex=$request->sex;
            $caretaker->address=$request->address;
            $caretaker->phone =$request->phone;
            $caretaker->employee_id=$request->employee_id;
            $caretaker->designation='Caretaker';
            $caretaker->blood_group = $request->blood_group;
            $caretaker->religion = $request->religion;
            $caretaker->father_spouse_name = 'NULL';
            $caretaker->professional_qual = 'NULL';
            $caretaker->special_sub = 'NULL';
            $caretaker->trained = 'NULL';
            $caretaker->experience = '0';
            $caretaker->teacher_image_name = 'NULL';
            $caretaker->tc_id =$request->teacher_id ;
            $caretaker->save();

            return response()->json([
                'status'=> 201,
                'message'=>'Caretaker Added successfully.',
                'data' =>$caretaker,
                'success'=>true
            ], 201); // 201 Created
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function editCaretaker($id){
            try{
            $caretaker = Teacher::where('designation', '=', 'Caretaker')
            ->where('teacher_id',$id)
            ->get();

            return response()->json([
                'status'=> 200,
                'message'=>'Caretaker edit successfully',
                'data' =>$caretaker,
                'success'=>true
            ], 200);

            }
            catch (\Exception $e) {
                return response()->json([
                    'message' => 'An error occurred while fetching the teacher details',
                    'error' => $e->getMessage()
                ], 500);
            }

     }

    public function updateCaretaker(Request $request,$id){
            $caretaker = Teacher::find($id);
            try{
            $validator = Validator::make($request->all(),[
                'employee_id' => 'required|string|unique:teacher,employee_id,' . $id . ' ,teacher_id',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'errors' => $validator->errors(),
                    ], 422);
                }
            $caretaker->name=$request->name;
            $caretaker->birthday=$request->birthday;
            $caretaker->date_of_joining=$request->date_of_joining;
            $caretaker->academic_qual=$request->academic_qual;
            $caretaker->employee_id=$request->employee_id;
            $caretaker->aadhar_card_no=$request->aadhar_card_no;
            $caretaker->sex=$request->sex;
            $caretaker->address=$request->address;
            $caretaker->phone =$request->phone;
            $caretaker->designation='Caretaker';
            $caretaker->blood_group = $request->blood_group;
            $caretaker->religion = $request->religion;
            $caretaker->father_spouse_name = 'NULL';
            $caretaker->professional_qual = 'NULL';
            $caretaker->special_sub = 'NULL';
            $caretaker->trained = 'NULL';
            $caretaker->experience = '0';
            $caretaker->teacher_image_name = 'NULL';
            $caretaker->tc_id =$request->teacher_id ;
            $caretaker->update();

            return response()->json([
                'status'=> 200,
                'message'=>'Caretaker updated successfully',
                'data' =>$caretaker,
                'success'=>true
            ], 201); // 201 Created
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
     }

    public function deleteCaretaker($id){
            try{
            $caretaker = Teacher::find($id);
            $caretaker->isDelete = 'Y';
            $caretaker->save();

            return response()->json([
                'status'=> 200,
                'message' => 'Caretaker deleted successfully!',
                'data' =>$caretaker,
                'success'=>true
            ]); 
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

     }
     public function getTeacherCategory(){
        try{
            $teacherCategory = DB::table('teacher_category')->get();
            return response()->json([
                'status'=> 200,
                'message'=>'Teacher Category List',
                'data' =>$teacherCategory,
                'success'=>true
              ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

     }
}
