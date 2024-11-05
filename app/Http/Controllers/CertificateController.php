<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use DateTime;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Auth;
use App\Models\BonafideCertificate;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\SimpleBonafide;
use App\Models\CasteBonafide;
use App\Models\CharacterCertificate;
use App\Models\PercentageCertificate;
use App\Models\PercentageMarksCertificate;

class CertificateController extends Controller
{
    public function getSrnobonafide($id){
        try{
            $srnobonafide = DB::table('bonafide_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation=DB::table('student')->where('student_id',$id)->first();
            if(is_null($studentinformation)){
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student information is not there',
                    'data' =>$studentinformation,
                    'success'=>true
                 ]);
              }
            $classname = DB::table('class')->where('class_id',$studentinformation->class_id)->first();
            $sectionname = DB::table('section')->where('section_id',$studentinformation->section_id)->first();
            $parentinformation=DB::table('parent')->where('parent_id',$studentinformation->parent_id)->first();
            
            if (is_null($srnobonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation'] = $studentinformation; 
                $data['classname']=$classname;
                $data['sectionname']=$sectionname;
                $data['parentinformation']=$parentinformation;
            }
            else{
                $data['sr_no'] = $srnobonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation'] = $studentinformation;
                $data['classname']=$classname;
                $data['sectionname']=$sectionname;
                $data['parentinformation']=$parentinformation;
            }
            $dob_in_words =  $studentinformation->dob;
            $dateTime = DateTime::createFromFormat('Y-m-d', $dob_in_words);
        
            // Check if the date is valid
            if ($dateTime === false) {
                return 'Invalid date format';
            }
            
            // Format the date as 'Day Month Year'
            $dateInWords = $dateTime->format('j F Y'); // e.g., 24th October, 2024
            
            $dobinwords = $this->convertDateToWords($dateInWords);
            $data['dobinwords']= $dobinwords;
           
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Certificate SrNo.',
                'data' =>$data,
                'success'=>true
              ]);
           }
           catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
           }
        }




    public function convertDateToWords($dateInWords) {

        function numberToWords($number) {
            $words = [
                0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
                5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
                14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
                18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 
                30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 
                70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
            ];

            if ($number < 21) {
                return $words[$number];
            } elseif ($number < 100) {
                $tens = floor($number / 10) * 10;
                $units = $number % 10;
                return $words[$tens] . ($units ? '-' . $words[$units] : '');
            } elseif ($number < 3000) {
                $hundreds = floor($number / 1000);
                return $words[$hundreds] . ' thousand' . ($number % 100 ? ' and ' . numberToWords($number % 100) : '');
            } else {
                return 'number too large';
            }
        }

        // Create a DateTime object from the input date
        $dateTime = DateTime::createFromFormat('d F Y', $dateInWords);
        
        // Check if the date is valid
        if ($dateTime === false) {
            return 'Invalid date format';
        }
        
        // Get the day, month, and year
        $day = $dateTime->format('j'); // Day without leading zeros
        $month = $dateTime->format('F'); // Full textual representation of the month
        $year = $dateTime->format('Y'); // Full year
        // Convert day and year to words
        $dayInWords = numberToWords($day);
        $yearInWords = numberToWords($year);
    
        // Construct the output string
        $dateInWords = "{$dayInWords}  {$month} {$yearInWords}";
    
        return $dateInWords;
    }

    public function downloadPdf(Request $request){
        // Sample dynamic data

        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');

        $data = [
            'stud_name'=>$request->stud_name,
            'father_name'=>$request->father_name,
            'class_division'=>$request->class_division,
            'dob'=>$request->dob,
            'dob_words'=>$request->dob_words,
            'purpose' =>$request ->purpose,
            'stud_id' =>$request ->stud_id,
            'issue_date_bonafide'=>$request->date,
            'academic_yr'=>$customClaims,
            'IsGenerated'=> 'Y',
            'IsDeleted'  => 'N',
            'IsIssued'   => 'N',
            'generated_by'=>Auth::user()->id,

        ];
        
        BonafideCertificate::create($data);
        
        $data= DB::table('bonafide_certificate')->orderBy('sr_no', 'desc')->first();
        // Load a view and pass the data to it
        $pdf = PDF::loadView('pdf.template', compact('data'));

        // Download the generated PDF
        return response()->stream(
            function () use ($pdf) {
                echo $pdf->output();
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Bonafide_Certificate.pdf"',
            ]
        );
    }

    public function bonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');
        
        $results = BonafideCertificate::where('class_division', 'LIKE', "%{$searchTerm}%")
                                       ->where('academic_yr','LIKE',"%{$customClaims}%")
                                       ->get();
        
        if($results->isEmpty()){
            return response()->json([
            'status'=> 200,
            'message'=>'No Student Found for this Class',
            'data' =>$results,
            'success'=>true
            ]);
        }
        else{
        return response()->json([
            'status'=> 200,
            'message'=>'Student found for this Class are-',
            'data' => $results,
            'success'=>true
            ]);
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

    public function updateisIssued(Request $request,$sr_no){
        try{
        $bondafidecertificateinfo = BonafideCertificate::find($sr_no);
        $bondafidecertificateinfo->isGenerated = 'N';
        $bondafidecertificateinfo->isIssued    = 'Y';
        $bondafidecertificateinfo->isDeleted   = 'N';
        $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
        $bondafidecertificateinfo->issued_by   = Auth::user()->id;
        $bondafidecertificateinfo->update();
        return response()->json([
            'status'=> 200,
            'message'=>'Bonafide Certificate Issued Successfully',
            'data' => $bondafidecertificateinfo,
            'success'=>true
            ]);

        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function updateisDeleted(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = BonafideCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Certificate Deleted Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }

    }

    public function getSrnosimplebonafide($id){
        try{
            $srnosimplebonafide = DB::table('simple_bonafide_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation = DB::table('student')
            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('section', 'section.section_id', '=', 'student.section_id')
            ->join('class', 'class.class_id', '=', 'student.class_id')
            ->where('student_id',$id)
            ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
            ->first();

            if(is_null($studentinformation)){
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student information is not there',
                    'data' =>$studentinformation,
                    'success'=>true
                 ]);
              }

            if (is_null($srnosimplebonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
            }
            else{
                $data['sr_no'] = $srnosimplebonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
            }
            $dob_in_words =  $studentinformation->dob;
        $dateTime = DateTime::createFromFormat('Y-m-d', $dob_in_words);
    
        // Check if the date is valid
        if ($dateTime === false) {
            return 'Invalid date format';
        }
        
        // Format the date as 'Day Month Year'
        $dateInWords = $dateTime->format('j F Y'); // e.g., 24th October, 2024
        
        $dobinwords = $this->convertDateToWords($dateInWords);
        $data['dobinwords']= $dobinwords;
       
        return response()->json([
            'status'=> 200,
            'message'=>'Bonafide Certificate SrNo.',
            'data' =>$data,
            'success'=>true
         ]);      
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function downloadsimplePdf(Request $request){
        try{

        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');
        $data = [
            'stud_name'=>$request->stud_name,
            'father_name'=>$request->father_name,
            'class_division'=>$request->class_division,
            'dob'=>$request->dob,
            'dob_words'=>$request->dob_words,
            'stud_id' =>$request ->stud_id,
            'issue_date_bonafide'=>$request->date,
            'academic_yr'=>$customClaims,
            'IsGenerated'=> 'Y',
            'IsDeleted'  => 'N',
            'IsIssued'   => 'N',
            'generated_by'=>Auth::user()->id,

        ];
        
        SimpleBonafide::create($data);
        
        $data= DB::table('simple_bonafide_certificate')->orderBy('sr_no', 'desc')->first();
        // Load a view and pass the data to it
        $pdf = PDF::loadView('pdf.simplebonafide', compact('data'));
        // Download the generated PDF
        return response()->stream(
            function () use ($pdf) {
                echo $pdf->output();
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Bonafide_Certificate.pdf"',
            ]
        );

        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function simplebonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');
        
        $results = SimpleBonafide::where('class_division', 'LIKE', "%{$searchTerm}%")
                                       ->where('academic_yr','LIKE',"%{$customClaims}%")
                                       ->get();
        
        if($results->isEmpty()){
            return response()->json([
            'status'=> 200,
            'message'=>'No Student Found for this Class',
            'data' =>$results,
            'success'=>true
            ]);
        }
        else{
        return response()->json([
            'status'=> 200,
            'message'=>'Student found for this Class are-',
            'data' => $results,
            'success'=>true
            ]);
          }
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }   
    }

    public function updatesimpleisIssued(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = SimpleBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Certificate Issued Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function deletesimpleisDeleted(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = SimpleBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Certificate Deleted Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function getSrnocastebonafide($id){
        try{
            $srnosimplebonafide = DB::table('bonafide_caste_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation = DB::table('student')
            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('section', 'section.section_id', '=', 'student.section_id')
            ->join('class', 'class.class_id', '=', 'student.class_id')
            ->where('student_id',$id)
            ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
            ->first();
            if(is_null($studentinformation)){
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student information is not there',
                    'data' =>$studentinformation,
                    'success'=>true
                 ]);
              }

            if (is_null($srnosimplebonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
            }
            else{
                $data['sr_no'] = $srnosimplebonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
            }
            $dob_in_words =  $studentinformation->dob;
            $dateTime = DateTime::createFromFormat('Y-m-d', $dob_in_words);
    
        // Check if the date is valid
        if ($dateTime === false) {
            return 'Invalid date format';
        }
        
        // Format the date as 'Day Month Year'
        $dateInWords = $dateTime->format('j F Y'); // e.g., 24th October, 2024
        
        $dobinwords = $this->convertDateToWords($dateInWords);
        $data['dobinwords']= $dobinwords;
       
        return response()->json([
            'status'=> 200,
            'message'=>'Bonafide Caste Certificate SrNo.',
            'data' =>$data,
            'success'=>true
         ]);      
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function downloadcastePDF(Request $request){
        try{

            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            $data = [
                'reg_no' => $request->reg_no,
                'stud_name'=>$request->stud_name,
                'father_name'=>$request->father_name,
                'class_division'=>$request->class_division,
                'caste'=> $request->caste,
                'religion'=>$request->religion,
                'birth_place'=>$request->birth_place,
                'dob'=>$request->dob,
                'dob_words'=>$request->dob_words,
                'stud_id_no'=>$request->stud_id_no,
                'stu_aadhaar_no'=>$request->stu_aadhaar_no,
                'admission_class_when'=>$request->admission_class_when,
                'nationality'=>$request->nationality,
                'prev_school_class'=>$request->prev_school_class,
                'admission_date'=>$request->admission_date,
                'class_when_learning'=>$request->class_when_learning,
                'progress'=>$request->progress,
                'behaviour'=>$request->behaviour,
                'leaving_reason'=>$request->leaving_reason,
                'lc_date_n_no' => $request->lc_date_n_no,
                'subcaste' =>$request->subcaste,
                'mother_tongue'=>$request->mother_tongue,
                'stud_id' =>$request ->stud_id,
                'issue_date_bonafide'=>$request->date,
                'academic_yr'=>$customClaims,
                'IsGenerated'=> 'Y',
                'IsDeleted'  => 'N',
                'IsIssued'   => 'N',
                'generated_by'=>Auth::user()->id,
    
            ];
            
            CasteBonafide::create($data);
            
            $data= DB::table('bonafide_caste_certificate')
                    ->join('student','student.student_id','=','bonafide_caste_certificate.stud_id')
                    ->join('parent','parent.parent_id','=','student.parent_id')
                    ->select('bonafide_caste_certificate.*','parent.mother_name')
                    ->orderBy('sr_no', 'desc')
                    ->first();
           // Load a view and pass the data to it
            $pdf = PDF::loadView('pdf.bonafidecaste', compact('data'));
            $dynamicFilename = "Caste_Certificate_$data->stud_name.pdf";
            // Download the generated PDF
            return response()->stream(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $dynamicFilename . '"',
                ]
            );
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function castebonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');
        
        $results = CasteBonafide::where('class_division', 'LIKE', "%{$searchTerm}%")
                                       ->where('academic_yr','LIKE',"%{$customClaims}%")
                                       ->get();
        
        if($results->isEmpty()){
            return response()->json([
            'status'=> 200,
            'message'=>'No Student Found for this Class',
            'data' =>$results,
            'success'=>true
            ]);
        }
        else{
        return response()->json([
            'status'=> 200,
            'message'=>'Student found for this Class are-',
            'data' => $results,
            'success'=>true
            ]);
          }
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }   
    }
    
    public function updatecasteisIssued(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = CasteBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Caste Certificate Issued Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function deletecasteisDeleted(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = CasteBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Caste Certificate Deleted Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function getSrnocharacterbonafide($id){
        try{
            $srnosimplebonafide = DB::table('character_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation = DB::table('student')
            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('section', 'section.section_id', '=', 'student.section_id')
            ->join('class', 'class.class_id', '=', 'student.class_id')
            ->where('student_id',$id)
            ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
            ->first();
            if(is_null($studentinformation)){
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student information is not there',
                    'data' =>$studentinformation,
                    'success'=>true
                 ]);
              }
            if (is_null($srnosimplebonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
            }
            else{
                $data['sr_no'] = $srnosimplebonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
            }
            $dob_in_words =  $studentinformation->dob;
            $dateTime = DateTime::createFromFormat('Y-m-d', $dob_in_words);
    
        // Check if the date is valid
        if ($dateTime === false) {
            return 'Invalid date format';
        }
        
        // Format the date as 'Day Month Year'
        $dateInWords = $dateTime->format('j F Y'); // e.g., 24th October, 2024
        
        $dobinwords = $this->convertDateToWords($dateInWords);
        $data['dobinwords']= $dobinwords;
       
        return response()->json([
            'status'=> 200,
            'message'=>'Bonafide Character Certificate SrNo.',
            'data' =>$data,
            'success'=>true
         ]);      
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function downloadcharacterPDF(Request $request){
        try{

            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            $data = [
                'stud_name'=>$request->stud_name,
                'class_division'=>$request->class_division,
                'dob'=>$request->dob,
                'dob_words'=>$request->dob_words,
                'attempt' =>$request->attempt,
                'stud_id' =>$request ->stud_id,
                'issue_date_bonafide'=>$request->date,
                'academic_yr'=>$customClaims,
                'IsGenerated'=> 'Y',
                'IsDeleted'  => 'N',
                'IsIssued'   => 'N',
                'generated_by'=>Auth::user()->id,
    
            ];
            
            CharacterCertificate::create($data);
            
            $data= DB::table('character_certificate')->orderBy('sr_no', 'desc')->first();
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.charactercertificate', compact('data'))->setPaper('A4','portrait');
            $dynamicFilename = "Caste_Certificate_$data->stud_name.pdf";
            // Download the generated PDF
            return response()->stream(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $dynamicFilename . '"',
                ]
            );
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function characterbonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');
        
        $results = CharacterCertificate::where('class_division', 'LIKE', "%{$searchTerm}%")
                                       ->where('academic_yr','LIKE',"%{$customClaims}%")
                                       ->get();
        
        if($results->isEmpty()){
            return response()->json([
            'status'=> 200,
            'message'=>'No Student Found for this Class',
            'data' =>$results,
            'success'=>true
            ]);
        }
        else{
        return response()->json([
            'status'=> 200,
            'message'=>'Student found for this Class are-',
            'data' => $results,
            'success'=>true
            ]);
          }
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }   
    }
    public function updatecharacterisIssued(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = CharacterCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Character Certificate Issued Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function deletecharacterisDeleted(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = CharacterCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Character Certificate Deleted Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }
    public function getSrnopercentagebonafide($id){
        try{
            $srnopercentagebonafide = DB::table('percentage_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation = DB::table('student')
            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('section', 'section.section_id', '=', 'student.section_id')
            ->join('class', 'class.class_id', '=', 'student.class_id')
            ->where('student_id',$id)
            ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
            ->first();

            if(is_null($studentinformation)){
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student information is not there',
                    'data' =>$studentinformation,
                    'success'=>true
                 ]);
              }
            if (is_null($srnopercentagebonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname == "10"){
                   $class10subjects = DB::table('class10_subject_master')->get();
                   $data['class10subjects'] = $class10subjects;
                   $count = count($class10subjects);
                   $data['subjectCount'] = $count;
                }
                else{
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id','shsm.name as subject_name', 'shsm.subject_type','shs_op.name as optional_sub_name','stream.stream_name')
                    ->where('shs.student_id', $id)
                    ->get();
                    $data['classsubject'] = $result;
                    $count = count($result);
                    $data['subjectCount'] = $count;
                    
                }
            }
            else{
                $data['sr_no'] = $srnopercentagebonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname == "10"){
                    $class10subjects = DB::table('class10_subject_master')->get();
                    $data['class10subjects'] = $class10subjects;
                    $count = count($class10subjects);
                    $data['subjectCount'] = $count;
                 }
                 else{
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id', 'shsm.name as subject_name', 'shsm.subject_type', 'stream.stream_name', 'shs_op.name as optional_sub_name')
                    ->where('shs.student_id', $id)
                    ->get();
                   $data['classsubject'] = $result;
                   $count = count($result);
                   $data['subjectCount'] = $count;
                 }
            }
            return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Percentage Certificate SrNo.',
                'data' =>$data,
                'success'=>true
             ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }
    public function downloadpercentagePDF(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_yr');
            
            
            $percentageCertificate = PercentageCertificate::create([
            'roll_no' => $request->roll_no,
            'stud_name' => $request->stud_name,
            'class_division' => $request->class_division,
            'percentage' => $request->percentage,
            'total' => $request->total,
            'stud_id' => $request->stud_id,
            'certi_issue_date' => $request->date,
            'academic_yr'=>$customClaims,
            'IsGenerated'=> 'Y',
            'IsDeleted'  => 'N',
            'IsIssued'   => 'N',
            'generated_by'=>Auth::user()->id,
            ]);
            
            
            $marksData = [];
            foreach ($request->class as $mark) {
                $marksData[] = [
                    'sr_no' => $percentageCertificate->sr_no,
                    'c_sm_id' => $mark['c_sm_id'],
                    'marks' => $mark['marks'],
                ];
            }

            PercentageMarksCertificate::insert($marksData);
            $data= DB::table('percentage_certificate')
                   ->join('student','student.student_id','=','percentage_certificate.stud_id')
                   ->select('percentage_certificate.roll_no as rollno','percentage_certificate.*','student.*')
                   ->orderBy('sr_no', 'desc')->first();
            $dynamicFilename = "Percentage_Certificate_$data->stud_name.pdf";
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.percentagecertificate', compact('data'));
            return response()->stream(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $dynamicFilename . '"',
                ]
            );
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function percentagebonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_yr');
        
        $results = PercentageCertificate::where('class_division', 'LIKE', "%{$searchTerm}%")
                                       ->where('academic_yr','LIKE',"%{$customClaims}%")
                                       ->get();
        
        if($results->isEmpty()){
            return response()->json([
            'status'=> 200,
            'message'=>'No Student Found for this Class',
            'data' =>$results,
            'success'=>true
            ]);
        }
        else{
        return response()->json([
            'status'=> 200,
            'message'=>'Student found for this Class are-',
            'data' => $results,
            'success'=>true
            ]);
          }
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }
    
    public function updatepercentageisIssued(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = PercentageCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Percentage Certificate Issued Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function deletepercentageisDeleted(Request $request,$sr_no){
        try{
            $bondafidecertificateinfo = PercentageCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = Auth::user()->id;
            $bondafidecertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Percentage Certificate Deleted Successfully',
                'data' => $bondafidecertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }
}
