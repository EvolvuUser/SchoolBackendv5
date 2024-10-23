<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use Illuminate\Support\Facades\Validator;

class NewController extends Controller
{
    public function getCaretakerList(){
        $caretakerlist = Teacher::where('designation', '=', 'Caretaker')
        ->get();
        return response()->json($caretakerlist);
    }

    public function storeCaretaker(Request $request){
            $validator = Validator::make($request->all(),[
                'name' => 'required|string|max:255',
                'birthday' => 'required|date',
                'date_of_joining' => 'required|date',
                'academic_qual' => 'required|string|max:255',
                'aadhar_card_no' => 'required|string|unique:teacher,aadhar_card_no',
                'sex' => 'required|string',
                'address' => 'required|string',
                'phone' => 'required|string|unique:teacher,phone',
                'employee_id' => 'required|string|unique:teacher,employee_id',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
            try{
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
                'message' => 'Caretaker created successfully!',
                'data' => $caretaker
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
            ->where('isDelete','N')
            ->where('teacher_id',$id)
            ->get();

            return response()->json([
                'caretaker' => $caretaker,
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
            $validator = Validator::make($request->all(),[
            
                'name' => 'sometimes|required|string|max:255',
                'birthday' => 'sometimes|required|date',
                'date_of_joining' => 'sometimes|required|date',
                'academic_qual' => 'sometimes|required|string|max:255',
                'aadhar_card_no' => 'sometimes|required',
                'sex' => 'sometimes|required|string',
                'address' => 'sometimes|required|string',
                'phone' => 'sometimes|required',
                'employee_id' => 'sometimes|required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
            try{
            
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
                'message' => 'Caretaker updated successfully!',
                'data' => $caretaker
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
                'message' => 'Caretaker deleted successfully!',
            ], 201); // 201 Created
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }

     }
}
