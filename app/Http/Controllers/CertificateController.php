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
use App\Models\LeavingCertificate;
use App\Models\Student;
use App\Models\UserMaster;
use Http;
use Illuminate\Support\Facades\App;


class CertificateController extends Controller
{
    public function getSrnobonafide($id){
            try{
                $checkstudentbonafide = DB::table('bonafide_certificate')->where('stud_id',$id)->where('isDeleted','N')->first();
                if(is_null($checkstudentbonafide)){
                    $srnobonafide = DB::table('bonafide_certificate')->orderBy('sr_no', 'desc')->first();
                    $studentinformation=DB::table('student')->where('student_id',$id)->first();
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
                else{
                    return response()->json([
                        'status'=> 403,
                        'message'=>'Bonafide Certificate Already Generated.Please go to manage to download the Bonafide Certificate',
                        'success'=>false
                    ]);
                }            
        }
            
           catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
           }
        }


        public function convertDateToWords($dateInWords) {

            // Helper function to convert number to words (for years, etc.)
            function numberToWords($number) {
                $words = [
                    0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
                    5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
                    10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
                    14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
                    18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 
                    30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 
                    70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
                ];
        
                if ($number < 21) {
                    return $words[$number];
                } elseif ($number < 100) {
                    $tens = floor($number / 10) * 10;
                    $units = $number % 10;
                    return $words[$tens] . ($units ? '-' . $words[$units] : '');
                } elseif ($number < 3000) {
                    $hundreds = floor($number / 1000);
                    return $words[$hundreds] . ' Thousand' . ($number % 100 ? ' and ' . numberToWords($number % 100) : '');
                } else {
                    return 'number too large';
                }
            }
        
            // Helper function to convert day number to words (like first, second, third, ...)
            function dayToWords($day) {
                $days = [
                    1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 
                    5 => 'Fifth', 6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 
                    9 => 'Ninth', 10 => 'Tenth', 11 => 'Eleventh', 12 => 'Twelfth',
                    13 => 'Thirteenth', 14 => 'Fourteenth', 15 => 'Fifteenth',
                    16 => 'Sixteenth', 17 => 'Seventeenth', 18 => 'Eighteenth',
                    19 => 'Nineteenth', 20 => 'Twentieth', 21 => 'Twenty-First',
                    22 => 'Twenty-Second', 23 => 'Twenty-Third', 24 => 'Twenty-Fourth',
                    25 => 'Twenty-Fifth', 26 => 'Twenty-Sixth', 27 => 'Twenty-Seventh',
                    28 => 'Twenty-Eighth', 29 => 'Twenty-Ninth', 30 => 'Thirtieth', 
                    31 => 'Thirty-first'
                ];
                
                return isset($days[$day]) ? $days[$day] : $day;
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
            
            // Convert day to its word form like 'first', 'second', etc.
            $dayInWords = dayToWords($day);
            
            // Convert year to words using numberToWords
            $yearInWords = numberToWords($year);
            
            // Construct the output string
            $dateInWords = "{$dayInWords} {$month} {$yearInWords}";
            
            return $dateInWords;
        }

    public function downloadPdf(Request $request){
        // Sample dynamic data

        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');

        $data = [
            'stud_name'=>$request->stud_name,
            'father_name'=>$request->father_name,
            'class_division'=>$request->class_division,
            'dob'=>$request->dob,
            'dob_words'=>$request->dob_words,
            'purpose' =>$request ->purpose,
            'stud_id' =>$request ->stud_id,
            'issue_date_bonafide'=>$request->date,
            'nationality' =>$request->nationality,
            'academic_yr'=>$customClaims,
            'IsGenerated'=> 'Y',
            'IsDeleted'  => 'N',
            'IsIssued'   => 'N',
            'generated_by'=>$user->reg_id,

        ];
        
        BonafideCertificate::create($data);
        
        $data= DB::table('bonafide_certificate')->orderBy('sr_no', 'desc')->first();
        $dynamicFilename = "Bonafide_Certificate_$data->stud_name.pdf";
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
                'Content-Disposition' => 'inline; filename="' . $dynamicFilename . '"',
            ]
        );
    }

    public function bonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        
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
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $bondafidecertificateinfo = BonafideCertificate::find($sr_no);
        $bondafidecertificateinfo->isGenerated = 'N';
        $bondafidecertificateinfo->isIssued    = 'Y';
        $bondafidecertificateinfo->isDeleted   = 'N';
        $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
        $bondafidecertificateinfo->issued_by   = $user->reg_id;
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = BonafideCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = $user->reg_id;
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

    public function getPDFdownloadBonafide(Request $request,$sr_no){
        try{
            $data= DB::table('bonafide_certificate')
                    ->where('sr_no',$sr_no)  
                    ->orderBy('sr_no','desc')->first();
            
            
            $dynamicFilename = "Bonafide_Certificate_$data->stud_name.pdf";
            
            $pdf = PDF::loadView('pdf.template', compact('data'));
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

    public function DataStudentBonafide(Request $request,$sr_no){
        try{
             $bonafidecertificateinfo = BonafideCertificate::find($sr_no);
             return response()->json([
                'status'=> 200,
                'message'=>'Bonafide Certificate Student Data',
                'data' => $bonafidecertificateinfo,
                'success'=>true
                ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
        
    }

    public function updateBonafideCertificate(Request $request,$sr_no){
        try{
            $bonafidecertificate = BonafideCertificate::find($sr_no);
            $bonafidecertificate->stud_name = $request->stud_name;
            $bonafidecertificate->father_name = $request->father_name;
            $bonafidecertificate->class_division = $request->class_division;
            $bonafidecertificate->dob=$request->dob;
            $bonafidecertificate->dob_words=$request->dob_words;
            $bonafidecertificate->purpose =$request->purpose;
            $bonafidecertificate->nationality =$request->nationality;
            $bonafidecertificate->stud_id=$request->stud_id;
            $bonafidecertificate->issue_date_bonafide=$request->date;
            $bonafidecertificate->update();

            $data= DB::table('bonafide_certificate')
                    ->where('sr_no',$sr_no)  
                    ->orderBy('sr_no','desc')->first();
            
            
            $dynamicFilename = "Bonafide_Certificate_$data->stud_name.pdf";
            
            $pdf = PDF::loadView('pdf.template', compact('data'));
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

    public function getSrnosimplebonafide($id){
        try{
            $checkstudentbonafide = DB::table('simple_bonafide_certificate')->where('stud_id',$id)->where('isDeleted','N')->first();
            if(is_null($checkstudentbonafide)){
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
            'message'=>'Simple Bonafide Certificate SrNo.',
            'data' =>$data,
            'success'=>true
         ]); 
        }
        else{
            return response()->json([
                'status'=> 403,
                'message'=>'Simple Bonafide Certificate Already Generated.Please go to manage to download the Simple Bonafide Certificate',
                'success'=>false
             ]);
        }     
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function downloadsimplePdf(Request $request){
        try{

        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
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
            'generated_by'=>$user->reg_id,

        ];
        
        SimpleBonafide::create($data);
        
        $data= DB::table('simple_bonafide_certificate')->orderBy('sr_no', 'desc')->first();
        $dynamicFilename = "Simple_Bonafide_Certificate_$data->stud_name.pdf";
        // Load a view and pass the data to it
        $pdf = PDF::loadView('pdf.simplebonafide', compact('data'))->setPaper('A5', 'landscape');
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

    public function simplebonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');
        try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = SimpleBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = $user->reg_id;
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = SimpleBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = $user->reg_id;
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

    public function simpleBonafideDownload(Request $request,$sr_no){
        try{
            $data = SimpleBonafide::find($sr_no);
            $dynamicFilename = "Simple_Bonafide_Certificate_$data->stud_name.pdf";
            $pdf = PDF::loadView('pdf.simplebonafide', compact('data'))->setPaper('A5', 'landscape');
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

    public function DataStudentSimpleBonafide(Request $request,$sr_no){
        try{

            $simplebonafidecertificateinfo = SimpleBonafide::find($sr_no);
             return response()->json([
                'status'=> 200,
                'message'=>'Simple Bonafide Certificate Student Data',
                'data' => $simplebonafidecertificateinfo,
                'success'=>true
                ]);

        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }

    }

    public function updateSimpleBonafide(Request $request,$sr_no){
        try{
           $bonafidecertificate = SimpleBonafide::find($sr_no);
           $bonafidecertificate->stud_name = $request->stud_name;
           $bonafidecertificate->father_name = $request->father_name;
           $bonafidecertificate->class_division = $request->class_division;
           $bonafidecertificate->dob=$request->dob;
           $bonafidecertificate->dob_words=$request->dob_words;
           $bonafidecertificate->stud_id=$request->stud_id;
           $bonafidecertificate->issue_date_bonafide=$request->date;
           $bonafidecertificate->update();

           $data= DB::table('simple_bonafide_certificate')->where('sr_no',$sr_no)->first();
           $dynamicFilename = "Simple_Bonafide_Certificate_$data->stud_name.pdf";
            // Load a view and pass the data to it
            $pdf = PDF::loadView('pdf.simplebonafide', compact('data'))->setPaper('A5', 'landscape');
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

    public function getSrnocastebonafide($id){
        try{
            $checkstudentbonafide = DB::table('bonafide_caste_certificate')->where('stud_id',$id)->where('isDeleted','N')->first();
            if(is_null($checkstudentbonafide)){
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
        else{
            return response()->json([
                'status'=> 403,
                'message'=>'Caste Bonafide Certificate Already Generated.Please go to manage to download the Caste Bonafide Certificate',
                'success'=>false
             ]);
        }     
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function downloadcastePDF(Request $request){
        try{

            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
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
                'generated_by'=>$user->reg_id,
    
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
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = CasteBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = $user->reg_id;
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = CasteBonafide::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = $user->reg_id;
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

    public function CasteBonafideDownload(Request $request,$sr_no){
        try{

            $data= DB::table('bonafide_caste_certificate')
                    ->join('student','student.student_id','=','bonafide_caste_certificate.stud_id')
                    ->join('parent','parent.parent_id','=','student.parent_id')
                    ->select('bonafide_caste_certificate.*','parent.mother_name')
                    ->where('sr_no',$sr_no)
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

    public function DataCasteBonafide(Request $request,$sr_no){
        try{
            $data= DB::table('bonafide_caste_certificate')
                        ->join('student','student.student_id','=','bonafide_caste_certificate.stud_id')
                        ->join('parent','parent.parent_id','=','student.parent_id')
                        ->select('bonafide_caste_certificate.*','parent.*','student.*')
                        ->where('sr_no',$sr_no)
                        ->first();
            
                    return response()->json([
                        'status'=> 200,
                        'message'=>'Bonafide Caste Certificate Student Data',
                        'data' => $data,
                        'success'=>true
                        ]);

        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }

    }

    public function updateCasteBonafide(Request $request,$sr_no){
        try{
            $castebonafide = CasteBonafide::find($sr_no);
            $castebonafide->reg_no = $request->reg_no;
            $castebonafide->stud_name = $request->stud_name;
            $castebonafide->father_name = $request->father_name;
            $castebonafide->class_division = $request->class_division;
            $castebonafide->caste = $request->caste;
            $castebonafide->religion = $request->religion;
            $castebonafide->birth_place = $request->birth_place;
            $castebonafide->dob = $request->religion;
            $castebonafide->dob_words = $request->dob_words;
            $castebonafide->stud_id_no = $request->stud_id_no;
            $castebonafide->stu_aadhaar_no = $request->stu_aadhaar_no;
            $castebonafide->admission_class_when = $request->admission_class_when;
            $castebonafide->nationality = $request->nationality;
            $castebonafide->prev_school_class = $request->prev_school_class;
            $castebonafide->admission_date = $request->admission_date;
            $castebonafide->class_when_learning = $request->class_when_learning;
            $castebonafide->progress = $request->progress;
            $castebonafide->behaviour = $request->behaviour;
            $castebonafide->leaving_reason = $request->leaving_reason;
            $castebonafide->lc_date_n_no = $request->lc_date_n_no;
            $castebonafide->subcaste = $request->subcaste;
            $castebonafide->mother_tongue = $request->mother_tongue;
            $castebonafide->stud_id = $request->stud_id;
            $castebonafide->issue_date_bonafide = $request->date;
            $castebonafide->update();

            $data= DB::table('bonafide_caste_certificate')
                    ->join('student','student.student_id','=','bonafide_caste_certificate.stud_id')
                    ->join('parent','parent.parent_id','=','student.parent_id')
                    ->select('bonafide_caste_certificate.*','parent.mother_name')
                    ->where('sr_no',$sr_no)
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

    public function getSrnocharacterbonafide($id){
        try{
            $checkstudentbonafide = DB::table('character_certificate')->where('stud_id',$id)->where('isDeleted','N')->first();
            if(is_null($checkstudentbonafide)){
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
        else{
            return response()->json([
                'status'=> 403,
                'message'=>'Character Bonafide Certificate Already Generated.Please go to manage to download the Character Bonafide Certificate',
                'success'=>false
             ]);
           } 
       }
    
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function downloadcharacterPDF(Request $request){
        try{

            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
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
                'generated_by'=>$user->reg_id,
    
            ];
            
            CharacterCertificate::create($data);
            
            $data= DB::table('character_certificate')->orderBy('sr_no', 'desc')->first();
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.charactercertificate', compact('data'))->setPaper('A4','landscape');
            $dynamicFilename = "Character_Certificate_$data->stud_name.pdf";
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
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = CharacterCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = $user->reg_id;
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = CharacterCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = $user->reg_id;
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

    public function CharacterBonafideDownload(Request $request,$sr_no){
        try{
            $data= DB::table('character_certificate')->where('sr_no',$sr_no)->orderBy('sr_no', 'desc')->first();
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.charactercertificate', compact('data'))->setPaper('A4','landscape');
            $dynamicFilename = "Character_Certificate_$data->stud_name.pdf";
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

    public function DataCharacterBonafide(Request $request,$sr_no){
        try{
             $charactercertificate = CharacterCertificate::find($sr_no);
             return response()->json([
                'status'=> 200,
                'message'=>'Character Certificate Data of Single Student',
                'data' =>$charactercertificate,
                'success'=>true
             ]);

        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function updateCharacterBonafide(Request $request,$sr_no){
        try{
            $charactercertificate = CharacterCertificate::find($sr_no);
            $charactercertificate->stud_name = $request->stud_name;
            $charactercertificate->class_division = $request->class_division;
            $charactercertificate->dob = $request->dob;
            $charactercertificate->dob_words = $request->dob_words;
            $charactercertificate->attempt = $request->attempt;
            $charactercertificate->stud_id = $request->stud_id;
            $charactercertificate->issue_date_bonafide = $request->date;
            $charactercertificate->update();
           
            $data= DB::table('character_certificate')->where('sr_no',$sr_no)->orderBy('sr_no', 'desc')->first();
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.charactercertificate', compact('data'))->setPaper('A4','landscape');
            $dynamicFilename = "Character_Certificate_$data->stud_name.pdf";
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

    public function getSrnopercentagebonafide($id){
        try{
            $checkstudentbonafide = DB::table('percentage_certificate')->where('stud_id',$id)->where('isDeleted','N')->first();
            if(is_null($checkstudentbonafide)){
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
                   $data['classsubject'] = $class10subjects;
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
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'c_sm_id' => $results->sm_hsc_id,
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                               ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                               ->select('shs.opt_subject_id','shsm.name')
                               ->where('shs.student_id',$id)
                               ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'c_sm_id' => $results1->opt_subject_id,
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
                    $data['subjectCount'] = $count;
                    
                }
            }
            else{
                $data['sr_no'] = $srnopercentagebonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname == "10"){
                    $class10subjects = DB::table('class10_subject_master')->get();
                    $data['classsubject'] = $class10subjects;
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
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'c_sm_id' => $results->sm_hsc_id,
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                               ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                               ->select('shs.opt_subject_id','shsm.name')
                               ->where('shs.student_id',$id)
                               ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'c_sm_id' => $results1->opt_subject_id,
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
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
            else{
                return response()->json([
                    'status'=> 403,
                    'message'=>'Percentage Bonafide Certificate Already Generated.Please go to manage to download the Percentage Bonafide Certificate',
                    'success'=>false
                 ]);
               } 
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }
    
    public function downloadpercentagePDF(Request $request){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            
            
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
            'generated_by'=>$user->reg_id,
            ]);
            
            
            $marksData = [];
            foreach ($request->class as $mark) {
              if ($mark['marks'] !== null) { 
                $marksData[] = [
                    'sr_no' => $percentageCertificate->sr_no,
                    'c_sm_id' => $mark['c_sm_id'],
                    'marks' => $mark['marks'],
                ];
             }
            }
            if (!empty($marksData)) {
            PercentageMarksCertificate::insert($marksData);
            }
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
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = PercentageCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'Y';
            $bondafidecertificateinfo->isDeleted   = 'N';
            $bondafidecertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->issued_by   = $user->reg_id;
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
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $bondafidecertificateinfo = PercentageCertificate::find($sr_no);
            $bondafidecertificateinfo->isGenerated = 'N';
            $bondafidecertificateinfo->isIssued    = 'N';
            $bondafidecertificateinfo->isDeleted   = 'Y';
            $bondafidecertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $bondafidecertificateinfo->	deleted_by   = $user->reg_id;
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

    public function PercentageDownload(Request $request,$sr_no){
        try{
             
            $data= DB::table('percentage_certificate')
                   ->join('student','student.student_id','=','percentage_certificate.stud_id')
                   ->where('percentage_certificate.sr_no',$sr_no)
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

    public function getPercentageData(Request $request,$sr_no){
        try{
              $studentinfo = DB::table('percentage_certificate')->where('sr_no',$sr_no)->first();
            //   dd($studentinfo);
              $studentinformation = DB::table('student')
                                        ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                                        ->join('section', 'section.section_id', '=', 'student.section_id')
                                        ->join('class', 'class.class_id', '=', 'student.class_id')
                                        ->where('student_id',$studentinfo->stud_id)
                                        ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
                                        ->first();
              $data['studentinfo'] = $studentinfo;
              $data['studentinformation'] = $studentinformation;
              if($studentinformation->classname == "10"){
                $class10subjects = DB::table('class10_subject_master')
                                       ->join('percentage_marks_certificate','percentage_marks_certificate.c_sm_id','=','class10_subject_master.c_sm_id')
                                       ->join('percentage_certificate','percentage_certificate.sr_no','=','percentage_marks_certificate.sr_no')
                                       ->where('percentage_certificate.sr_no',$sr_no)
                                       ->select('class10_subject_master.c_sm_id','class10_subject_master.name','percentage_marks_certificate.marks')
                                       ->get();
                                $data['classsubject'] = $class10subjects;
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
                 ->join('percentage_marks_certificate','percentage_marks_certificate.c_sm_id','=','grpd.sm_hsc_id')
                 ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id','shsm.name as subject_name', 'shsm.subject_type','shs_op.name as optional_sub_name','stream.stream_name','percentage_marks_certificate.marks')
                 ->where('percentage_marks_certificate.sr_no',$sr_no)
                 ->where('shs.student_id', $studentinformation->student_id)
                 ->get();

                 $result = $result->map(function ($results) {
                     // Change 'old_key' to 'new_key' 
                     return [
                         'c_sm_id' => $results->sm_hsc_id,
                         'name' => $results->subject_name,
                         'marks'=> $results->marks,
                     ];
                 });
                 $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                            ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                            ->join('percentage_marks_certificate','percentage_marks_certificate.c_sm_id','=','shs.opt_subject_id')
                            ->select('shs.opt_subject_id','shsm.name','percentage_marks_certificate.marks')
                            ->where('percentage_marks_certificate.sr_no',$sr_no)
                            ->where('shs.student_id',$studentinformation->student_id)
                            ->get();
                     $result1 = $result1->map(function ($results1) {
                     // Change 'old_key' to 'new_key'
                     return [
                         'c_sm_id' => $results1->opt_subject_id,
                         'name' => $results1->name,
                         'marks' => $results1->marks
                     ];
                     });
                     $mergedResult = $result->merge($result1);

                 $data['classsubject'] = $mergedResult;
                 $count = count($mergedResult);
                 $data['subjectCount'] = $count;
                 
             }
             return response()->json([
                'status'=> 200,
                'message'=>'Percentage Data',
                'data' =>$data,
                'success'=>true
             ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function updatePercentagePDF(Request $request,$sr_no){
        try{
              $percentagecertificateinfo = PercentageCertificate::find($sr_no);
              $percentagecertificateinfo->roll_no = $request->roll_no;
              $percentagecertificateinfo->stud_name = $request->stud_name;
              $percentagecertificateinfo->class_division = $request->class_division;
              $percentagecertificateinfo->percentage = $request->percentage;
              $percentagecertificateinfo->stud_id = $request->stud_id;
              $percentagecertificateinfo->total = $request->total;
              $percentagecertificateinfo->certi_issue_date = $request->date;
              $percentagecertificateinfo->update();

              foreach ($request->class as $mark) {
                PercentageMarksCertificate::where('sr_no', $percentagecertificateinfo->sr_no)
                    ->where('c_sm_id', $mark['c_sm_id'])  // If there's a second condition to uniquely identify the row
                    ->update([
                        'marks' => $mark['marks'],
                    ]);
            }
            $data= DB::table('percentage_certificate')
                        ->join('student','student.student_id','=','percentage_certificate.stud_id')
                        ->where('percentage_certificate.sr_no',$sr_no)
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

    public function getSrnoLeavingCertificate($id){
        try{
            $checkstudentbonafide = DB::table('leaving_certificate')->where('stud_id',$id)->where('isDelete','N')->first();
            if(is_null($checkstudentbonafide)){
            $srnoleavingbonafide = DB::table('leaving_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation = DB::table('student')
            ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
            ->join('section', 'section.section_id', '=', 'student.section_id')
            ->join('class', 'class.class_id', '=', 'student.class_id')
            ->where('student_id',$id)
            ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
            ->first();

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

            if(is_null($studentinformation)){
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student information is not there',
                    'data' =>$studentinformation,
                    'success'=>true
                 ]);
              }
            if (is_null($srnoleavingbonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname==11 || $studentinformation->classname==12){
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id', 'shsm.name as subject_name', 'shsm.subject_type', 'stream.stream_name', 'shs_op.name as optional_sub_name')
                    ->where('shs.student_id', $id)
                    ->get();
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                               ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                               ->select('shs.opt_subject_id','shsm.name')
                               ->where('shs.student_id',$id)
                               ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
                    $data['subjectCount'] = $count;
                }else{
                    $result = DB::table('subjects_on_report_card')
                        ->join('subjects_on_report_card_master', 'subjects_on_report_card.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
                        ->where('subjects_on_report_card.class_id', $studentinformation->class_id)
                        ->where('subjects_on_report_card.subject_type', 'Scholastic')
                        ->where('subjects_on_report_card.academic_yr', $studentinformation->academic_yr)
                        ->orderBy('subjects_on_report_card.class_id', 'asc')
                        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
                        ->select('subjects_on_report_card_master.*')  // Select all columns from subjects_on_report_card_master
                        ->get();  // Retrieve the results as a collection
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->name,
                        ];
                    });
                    $data['classsubject'] = $result;
                    $count = count($result);
                    $data['subjectCount'] = $count;

                }

                $totalattendance = DB::table('attendance')
                ->select(
                    DB::raw('SUM(IF(attendance_status = 0, 1, 0)) AS total_present_days'),
                    DB::raw('COUNT(*) AS total_working_days')
                )
                ->where('student_id', $id)
                ->where('academic_yr', $studentinformation->academic_yr)
                ->first();  // Get the first (and only) row as we expect a single result
               
            // Initialize the result variables
            $total_present_days = $totalattendance->total_present_days ?? 0;
            $total_working_days = $totalattendance->total_working_days ?? 0;
            
            // Calculate total attendance
            if ($total_present_days !== null) {
                $total_attendance = $total_present_days . "/" . $total_working_days;
            } else {
                $total_attendance = "";
            }
             
            $data['total_attendance'] = $total_attendance;
            
            $paymentdetails = DB::table('view_student_fees_payment')
                              ->where('student_id',$id)
                              ->where('academic_yr',$studentinformation->academic_yr)
                              ->orderBy('installment','desc')
                              ->select('installment','academic_yr')
                              ->first();
                              $paymentdetailsarray = (array) $paymentdetails;

                    if(isset($paymentdetailsarray) && count($paymentdetailsarray)>0){
                        $installment = $paymentdetailsarray['installment'];
                        if($installment==1){
                            $last_fee_paid_month="September (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==2){
                            $last_fee_paid_month="December (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==3){
                            $last_fee_paid_month="Whole year (".$paymentdetailsarray['academic_yr'].")";
                        }
                        $data['last_fee_paid_month']=$last_fee_paid_month;
                    }
            

            $academicStudent = DB::table('student')
                                ->where('parent_id',$studentinformation->parent_id)
                                ->where('first_name',$studentinformation->first_name)
                                ->select('academic_yr')
                                ->get();

            $data['academicStudent'] = $academicStudent;
            }
            else{
                $data['sr_no'] = $srnoleavingbonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname==11 || $studentinformation->classname==12){
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id', 'shsm.name as subject_name', 'shsm.subject_type', 'stream.stream_name', 'shs_op.name as optional_sub_name')
                    ->where('shs.student_id', $id)
                    ->get();
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                               ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                               ->select('shs.opt_subject_id','shsm.name')
                               ->where('shs.student_id',$id)
                               ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
                    $data['subjectCount'] = $count;
                }else{
                    $result = DB::table('subjects_on_report_card')
                        ->join('subjects_on_report_card_master', 'subjects_on_report_card.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
                        ->where('subjects_on_report_card.class_id', $studentinformation->class_id)
                        ->where('subjects_on_report_card.subject_type', 'Scholastic')
                        ->where('subjects_on_report_card.academic_yr', $studentinformation->academic_yr)
                        ->orderBy('subjects_on_report_card.class_id', 'asc')
                        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
                        ->select('subjects_on_report_card_master.*')  // Select all columns from subjects_on_report_card_master
                        ->get();  // Retrieve the results as a collection
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->name,
                        ];
                    });
                    $data['classsubject'] = $result;
                    $count = count($result);
                    $data['subjectCount'] = $count;

                }

                $totalattendance = DB::table('attendance')
                ->select(
                    DB::raw('SUM(IF(attendance_status = 0, 1, 0)) AS total_present_days'),
                    DB::raw('COUNT(*) AS total_working_days')
                )
                ->where('student_id', $id)
                ->where('academic_yr', $studentinformation->academic_yr)
                ->first();  // Get the first (and only) row as we expect a single result
               
            // Initialize the result variables
            $total_present_days = $totalattendance->total_present_days ?? 0;
            $total_working_days = $totalattendance->total_working_days ?? 0;
            
            // Calculate total attendance
            if ($total_present_days !== null) {
                $total_attendance = $total_present_days . "/" . $total_working_days;
            } else {
                $total_attendance = "";
            }
             
            $data['total_attendance'] = $total_attendance;
            
            $paymentdetails = DB::table('view_student_fees_payment')
                              ->where('student_id',$id)
                              ->where('academic_yr',$studentinformation->academic_yr)
                              ->orderBy('installment','desc')
                              ->select('installment','academic_yr')
                              ->first();
                              $paymentdetailsarray = (array) $paymentdetails;

                    if(isset($paymentdetailsarray) && count($paymentdetailsarray)>0){
                        $installment = $paymentdetailsarray['installment'];
                        if($installment==1){
                            $last_fee_paid_month="September (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==2){
                            $last_fee_paid_month="December (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==3){
                            $last_fee_paid_month="Whole year (".$paymentdetailsarray['academic_yr'].")";
                        }
                      $data['last_fee_paid_month']=$last_fee_paid_month;
                    }
            

            $academicStudent = DB::table('student')
                                ->where('parent_id',$studentinformation->parent_id)
                                ->where('first_name',$studentinformation->first_name)
                                ->select('academic_yr')
                                ->get();

            $data['academicStudent'] = $academicStudent;

            }
            return response()->json([
                'status'=> 200,
                'message'=>'Leaving Certificate SrNo.',
                'data' =>$data,
                'success'=>true
             ]);
            }
            else{
                return response()->json([
                    'status'=> 403,
                    'message'=>'Leaving Certificate Already Generated.Please go to manage to download the Leaving Certificate',
                    'success'=>false
                 ]);
            }
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function getSrnoLeavingCertificateAcademicYr($id,$academic_yr){
        try{
            $studentinfo = DB::table('student')
                           ->where('student_id',$id)
                           ->select('parent_id','first_name')
                           ->distinct('student_id')
                           ->first();

            $studentinfoacademic = DB::table('student')
                                   ->where('parent_id',$studentinfo->parent_id)
                                   ->where('first_name',$studentinfo->first_name)
                                   ->where('academic_yr',$academic_yr)
                                   ->first();
            
            $checkstudentbonafide = DB::table('leaving_certificate')->where('stud_id',$id)->where('isDelete','N')->first();
            if(is_null($checkstudentbonafide)){
            $srnoleavingbonafide = DB::table('leaving_certificate')->orderBy('sr_no', 'desc')->first();
            $studentinformation = DB::table('student')
                                    ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                                    ->join('section', 'section.section_id', '=', 'student.section_id')
                                    ->join('class', 'class.class_id', '=', 'student.class_id')
                                    ->where('student_id',$studentinfoacademic->student_id)
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
            if (is_null($srnoleavingbonafide)) {
                $data['sr_no'] = '1';
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname==11 || $studentinformation->classname==12){
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id', 'shsm.name as subject_name', 'shsm.subject_type', 'stream.stream_name', 'shs_op.name as optional_sub_name')
                    ->where('shs.student_id', $studentinformation->student_id)
                    ->get();
                    $result = $result->map(function ($results) {
                        return [
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                               ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                               ->select('shs.opt_subject_id','shsm.name')
                               ->where('shs.student_id',$id)
                               ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
                    $data['subjectCount'] = $count;
                }else{
                    $result = DB::table('subjects_on_report_card')
                        ->join('subjects_on_report_card_master', 'subjects_on_report_card.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
                        ->where('subjects_on_report_card.class_id', $studentinformation->class_id)
                        ->where('subjects_on_report_card.subject_type', 'Scholastic')
                        ->where('subjects_on_report_card.academic_yr', $studentinformation->academic_yr)
                        ->orderBy('subjects_on_report_card.class_id', 'asc')
                        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
                        ->select('subjects_on_report_card_master.*')  // Select all columns from subjects_on_report_card_master
                        ->get();  // Retrieve the results as a collection
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->name,
                        ];
                    });
                    $data['classsubject'] = $result;
                    $count = count($result);
                    $data['subjectCount'] = $count;

                }

                $totalattendance = DB::table('attendance')
                ->select(
                    DB::raw('SUM(IF(attendance_status = 0, 1, 0)) AS total_present_days'),
                    DB::raw('COUNT(*) AS total_working_days')
                )
                ->where('student_id', $studentinformation->student_id)
                ->where('academic_yr', $studentinformation->academic_yr)
                ->first();  // Get the first (and only) row as we expect a single result
               
            // Initialize the result variables
            $total_present_days = $totalattendance->total_present_days ?? 0;
            $total_working_days = $totalattendance->total_working_days ?? 0;
            
            // Calculate total attendance
            if ($total_present_days !== null) {
                $total_attendance = $total_present_days . "/" . $total_working_days;
            } else {
                $total_attendance = "";
            }
             
            $data['total_attendance'] = $total_attendance;
            
            $paymentdetails = DB::table('view_student_fees_payment')
                              ->where('student_id',$studentinformation->student_id)
                              ->where('academic_yr',$studentinformation->academic_yr)
                              ->orderBy('installment','desc')
                              ->select('installment','academic_yr')
                              ->first();
                              $paymentdetailsarray = (array) $paymentdetails;

                    if(isset($paymentdetailsarray) && count($paymentdetailsarray)>0){
                        $installment = $paymentdetailsarray['installment'];
                        if($installment==1){
                            $last_fee_paid_month="September (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==2){
                            $last_fee_paid_month="December (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==3){
                            $last_fee_paid_month="Whole year (".$paymentdetailsarray['academic_yr'].")";
                        }
                        $data['last_fee_paid_month']=$last_fee_paid_month;
                    }
            

            $academicStudent = DB::table('student')
                                ->where('parent_id',$studentinformation->parent_id)
                                ->where('first_name',$studentinformation->first_name)
                                ->select('academic_yr')
                                ->get();

            $data['academicStudent'] = $academicStudent;
            }
            else{
                $data['sr_no'] = $srnoleavingbonafide->sr_no + 1 ;
                $data['date']  = Carbon::today()->format('Y-m-d');
                $data['studentinformation']=$studentinformation;
                if($studentinformation->classname==11 || $studentinformation->classname==12){
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id', 'shsm.name as subject_name', 'shsm.subject_type', 'stream.stream_name', 'shs_op.name as optional_sub_name')
                    ->where('shs.student_id', $id)
                    ->get();
                    
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                               ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                               ->select('shs.opt_subject_id','shsm.name')
                               ->where('shs.student_id',$id)
                               ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
                    $data['subjectCount'] = $count;
                }else{
                    $result = DB::table('subjects_on_report_card')
                        ->join('subjects_on_report_card_master', 'subjects_on_report_card.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
                        ->where('subjects_on_report_card.class_id', $studentinformation->class_id)
                        ->where('subjects_on_report_card.subject_type', 'Scholastic')
                        ->where('subjects_on_report_card.academic_yr', $studentinformation->academic_yr)
                        ->orderBy('subjects_on_report_card.class_id', 'asc')
                        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
                        ->select('subjects_on_report_card_master.*')  // Select all columns from subjects_on_report_card_master
                        ->get();  
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->name,
                        ];
                    });
                    $data['classsubject'] = $result;
                    $count = count($result);
                    $data['subjectCount'] = $count;

                }

                $totalattendance = DB::table('attendance')
                ->select(
                    DB::raw('SUM(IF(attendance_status = 0, 1, 0)) AS total_present_days'),
                    DB::raw('COUNT(*) AS total_working_days')
                )
                ->where('student_id', $studentinformation->student_id)
                ->where('academic_yr', $studentinformation->academic_yr)
                ->first();  // Get the first (and only) row as we expect a single result
               
            // Initialize the result variables
            $total_present_days = $totalattendance->total_present_days ?? 0;
            $total_working_days = $totalattendance->total_working_days ?? 0;
            
            // Calculate total attendance
            if ($total_present_days !== null) {
                $total_attendance = $total_present_days . "/" . $total_working_days;
            } else {
                $total_attendance = "";
            }
             
            $data['total_attendance'] = $total_attendance;
            
            $paymentdetails = DB::table('view_student_fees_payment')
                              ->where('student_id',$studentinformation->student_id)
                              ->where('academic_yr',$studentinformation->academic_yr)
                              ->orderBy('installment','desc')
                              ->select('installment','academic_yr')
                              ->first();
                              $paymentdetailsarray = (array) $paymentdetails;

                    if(isset($paymentdetailsarray) && count($paymentdetailsarray)>0){
                        $installment = $paymentdetailsarray['installment'];
                        if($installment==1){
                            $last_fee_paid_month="September (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==2){
                            $last_fee_paid_month="December (".$paymentdetailsarray['academic_yr'].")";
                        }elseif($installment==3){
                            $last_fee_paid_month="Whole year (".$paymentdetailsarray['academic_yr'].")";
                        }
                      $data['last_fee_paid_month']=$last_fee_paid_month;
                    }
            

            $academicStudent = DB::table('student')
                                ->where('parent_id',$studentinformation->parent_id)
                                ->where('first_name',$studentinformation->first_name)
                                ->select('academic_yr')
                                ->get();

            $data['academicStudent'] = $academicStudent;

            }
            return response()->json([
                'status'=> 200,
                'message'=>'Leaving Certificate SrNo. By Academic Yr',
                'data' =>$data,
                'success'=>true
             ]);
            }
            else{
                return response()->json([
                    'status'=> 403,
                    'message'=>'Leaving Certificate Already Generated.Please go to manage to download the Leaving Certificate',
                    'success'=>false
                 ]);
            }
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function saveLeavingCertificatePDF(Request $request){
        try{
            $user = $this->authenticateUser();
            
            $tagsString = implode(',', $request->subjects);
            $tagsString1 = implode(',', $request->games);
            $leavingCertificate = LeavingCertificate::create([
            'grn_no' => $request->grn_no,
            'issue_date' => $request->issue_date,
            'stud_id_no' => $request->stud_id_no,
            'aadhar_no'=>$request->stu_aadhaar_no,
            'stud_name'=>$request->first_name,
            'mid_name' =>$request->mid_name,
            'last_name'=>$request->last_name,
            'father_name'=>$request->father_name,
            'mother_name'=>$request->mother_name,
            'nationality'=>$request->nationality,
            'mother_tongue'=>$request->mother_tongue,
            'state'=>$request->state,
            'religion' => $request->religion,
            'caste' => $request->caste,
            'subcaste' => $request->subcaste,
            'birth_place'=>$request->birth_place,
            'dob' => $request->dob,
            'dob_words'=>$request->dob_words,
            'dob_proof'=>$request->dob_proof,
            'last_school_attended_standard'=>$request->previous_school_attended,
            'date_of_admission'=>$request->admission_date,
            'admission_class'=>$request->admission_class,
            'leaving_date'=>$request->leaving_date,
            'standard_studying'=>$request->standard_studying,
            'last_exam'=>$request->last_exam,
            'subjects_studied'=>$tagsString,
            'promoted_to'=>$request->promoted_to,
            'attendance' =>$request->attendance,
            'fee_month' => $request->fee_month,
            'part_of'=>$request->part_of,
            'games' => $tagsString1,
            'application_date'=>$request->application_date,
            'conduct'=>$request->conduct,
            'reason_leaving'=>$request->reason_leaving,
            'remark'=>$request->remark,
            'academic_yr'=>$request->academic_yr,
            'stud_id' => $request->stud_id,
            'udise_pen_no'=>$request->udise_pen_no,
            'IsGenerated'=> 'Y',
            'IsDelete'  => 'N',
            'IsIssued'   => 'N',
            'generated_by'=>$user->reg_id,
            ]);
            
            $data= DB::table('leaving_certificate')
                    ->where('sr_no',$leavingCertificate->sr_no)  
                    ->orderBy('sr_no','desc')->first();

                    DB::table('student')
                        ->where('student_id', $data->stud_id)
                        ->update([
                            'last_date' => $data->leaving_date,
                            'slc_no' => $data->sr_no,
                            'slc_issue_date' => $data->issue_date,
                            'leaving_remark' =>$data->remark,
                        ]);
                    
            $dynamicFilename = "LC_{$data->sr_no}_{$data->stud_name}_{$data->mid_name}_{$data->last_name}.pdf";
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.leavingcertificate', compact('data'));
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

    public function getLeavingCertificateList(Request $request){
        $sr_no = $request->query('sr_no');
        $class_id = $request->query('class_id');
        
       try{
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $query = DB::table('leaving_certificate')
                    ->join('student', 'leaving_certificate.stud_id', '=', 'student.student_id')
                    ->join('section','section.section_id','=','student.section_id')
                    ->join('class','class.class_id','=','section.class_id')
                    ->select('section.name as sectionname','section.class_id' , 'class.name as classname','leaving_certificate.*')
                    ->where('leaving_certificate.academic_yr', $customClaims)
                    ->orderBy('sr_no', 'desc');
       
                // Conditionally add filters
                if ($class_id && $sr_no) {
                    // Filter by both class_id and section_id
                    $query->where('student.class_id', $class_id)
                        ->where('leaving_certificate.sr_no', $sr_no);
                } elseif ($class_id) {
                    // Filter by class_id only
                    $query->where('student.class_id', $class_id);
                } elseif ($sr_no) {
                    // Filter by section_id only
                    $query->where('leaving_certificate.sr_no', $sr_no);
                }

                // Execute the query and get the results
                $leavingCertificates = $query->get();
        return response()->json([
            'status'=> 200,
            'message'=>'Leaving Certificate List',
            'data' =>$leavingCertificates,
            'success'=>true
         ]);
             
       }
       catch (Exception $e) {
        \Log::error($e); // Log the exception
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }  
    }

    public function leavingCertificateisIssued(Request $request ,$sr_no){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $leavingcertificateinfo = LeavingCertificate::find($sr_no);
            $leavingcertificateinfo->isGenerated = 'N';
            $leavingcertificateinfo->isIssued    = 'Y';
            $leavingcertificateinfo->isDelete   = 'N';
            $leavingcertificateinfo->issued_date = Carbon::today()->format('Y-m-d');
            $leavingcertificateinfo->issued_by   = $user->reg_id;
            $leavingcertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Leaving Certificate Issued Successfully',
                'data' => $leavingcertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function leavingCertificateisDeleted(Request $request,$sr_no){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $leavingcertificateinfo = LeavingCertificate::find($sr_no);
            $leavingcertificateinfo->isGenerated = 'N';
            $leavingcertificateinfo->isIssued    = 'N';
            $leavingcertificateinfo->isDelete   = 'Y';
            $leavingcertificateinfo->deleted_date = Carbon::today()->format('Y-m-d');
            $leavingcertificateinfo->	deleted_by   = $user->reg_id;
            $leavingcertificateinfo->cancel_reason = $request->cancel_reason;
            $leavingcertificateinfo->update();
            return response()->json([
                'status'=> 200,
                'message'=>'Leaving Certificate Deleted Successfully',
                'data' => $leavingcertificateinfo,
                'success'=>true
                ]);
    
            }
            catch (Exception $e) {
                \Log::error($e); // Log the exception
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function leavingCertificatePDFDownload(Request $request,$sr_no){      
        try{
            $data= DB::table('leaving_certificate')
                    ->where('sr_no',$sr_no)  
                    ->orderBy('sr_no','desc')->first();
            $dynamicFilename = "LC_{$data->sr_no}_{$data->stud_name}_{$data->mid_name}_{$data->last_name}.pdf";
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.leavingcertificate', compact('data'));
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

     public function getLeavingCertificateDataSingle(Request $request,$sr_no){
        try{
            $data['leavingcertificatesingle']= DB::table('leaving_certificate')
                    ->where('sr_no',$sr_no)  
                    ->first();
            
            $studentinfo = DB::table('leaving_certificate')
                            ->join('student','student.student_id','=','leaving_certificate.stud_id')
                            ->where('leaving_certificate.sr_no',$sr_no)
                            ->first();
                        

            $academicStudent = DB::table('student')
                                ->where('parent_id',$studentinfo->parent_id)
                                ->where('first_name',$studentinfo->first_name)
                                ->select('academic_yr')
                                ->get();

            $data['academicStudent'] = $academicStudent; 
            
            $studentinformation = DB::table('student')
                                    ->join('parent', 'student.parent_id', '=', 'parent.parent_id')
                                    ->join('section', 'section.section_id', '=', 'student.section_id')
                                    ->join('class', 'class.class_id', '=', 'student.class_id')
                                    ->where('student_id',$data['leavingcertificatesingle']->stud_id)
                                    ->select('class.class_id','class.name as classname', 'section.section_id','section.name as sectionname', 'parent.*', 'student.*') // Adjust select as needed
                                    ->first();  
            
                if($studentinformation->classname==11 || $studentinformation->classname==12){
                    $result = DB::table('subjects_higher_secondary_studentwise AS shs')
                    ->join('subject_group AS grp', 'shs.sub_group_id', '=', 'grp.sub_group_id')
                    ->join('subject_group_details AS grpd', 'grp.sub_group_id', '=', 'grpd.sub_group_id')
                    ->join('subject_master AS shsm', 'grpd.sm_hsc_id', '=', 'shsm.sm_id')
                    ->join('subject_master AS shs_op', 'shs.opt_subject_id', '=', 'shs_op.sm_id')
                    ->join('stream', 'grp.stream_id', '=', 'stream.stream_id')
                    ->select('shs.*', 'grp.sub_group_name', 'grpd.sm_hsc_id', 'shsm.name as subject_name', 'shsm.subject_type', 'stream.stream_name', 'shs_op.name as optional_sub_name')
                    ->where('shs.student_id', $studentinformation->student_id)
                    ->get();
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->subject_name,
                        ];
                    });
                    $result1 = DB::table('subjects_higher_secondary_studentwise AS shs')
                                ->join('subject_master AS shsm','shs.opt_subject_id','=','shsm.sm_id')
                                ->select('shs.opt_subject_id','shsm.name')
                                ->where('shs.student_id',$studentinformation->student_id)
                                ->get();
                        $result1 = $result1->map(function ($results1) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results1->name,
                        ];
                        });
                        $mergedResult = $result->merge($result1);

                    $data['classsubject'] = $mergedResult;
                    $count = count($mergedResult);
                    $data['subjectCount'] = $count;
                }else{
                    $result = DB::table('subjects_on_report_card')
                        ->join('subjects_on_report_card_master', 'subjects_on_report_card.sub_rc_master_id', '=', 'subjects_on_report_card_master.sub_rc_master_id')
                        ->where('subjects_on_report_card.class_id', $studentinformation->class_id)
                        ->where('subjects_on_report_card.subject_type', 'Scholastic')
                        ->where('subjects_on_report_card.academic_yr', $studentinformation->academic_yr)
                        ->orderBy('subjects_on_report_card.class_id', 'asc')
                        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
                        ->select('subjects_on_report_card_master.*')  // Select all columns from subjects_on_report_card_master
                        ->get();  // Retrieve the results as a collection
                    $result = $result->map(function ($results) {
                        // Change 'old_key' to 'new_key'
                        return [
                            'name' => $results->name,
                        ];
                    });
                    $data['classsubject'] = $result;
                    $count = count($result);
                    $data['subjectCount'] = $count;

                }
                return response()->json([
                    'status'=> 200,
                    'message'=>'Leaving Certificate Data Single Successfully',
                    'data' => $data,
                    'success'=>true
                    ]);
            
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }
     
    public function updateLeavingCertificateDownload(Request $request,$sr_no){
        try{
            $tagsString = implode(',', $request->subjects);
            $tagsString1 = implode(',', $request->games);
            $leavingcertificate = LeavingCertificate::find($sr_no);
            $leavingcertificate->grn_no = $request->grn_no;
            $leavingcertificate->issue_date = $request->issue_date;
            $leavingcertificate->stud_id_no = $request->student_id_no;
            $leavingcertificate->aadhar_no=$request->aadhar_no;
            $leavingcertificate->stud_name=$request->first_name;
            $leavingcertificate->mid_name =$request->mid_name;
            $leavingcertificate->last_name=$request->last_name;
            $leavingcertificate->father_name=$request->father_name;
            $leavingcertificate->mother_name=$request->mother_name;
            $leavingcertificate->nationality=$request->nationality;
            $leavingcertificate->mother_tongue=$request->mother_tongue;
            $leavingcertificate->religion = $request->religion;
            $leavingcertificate->caste = $request->caste;
            $leavingcertificate->subcaste = $request->subcaste;
            $leavingcertificate->birth_place=$request->birth_place;
            $leavingcertificate->dob = $request->dob;
            $leavingcertificate->dob_words = $request->dob_words;
            $leavingcertificate->dob_proof = $request->dob_proof;
            $leavingcertificate->last_school_attended_standard = $request->previous_school_attended;
            $leavingcertificate->date_of_admission = $request->date_of_admission;
            $leavingcertificate->admission_class = $request->admission_class;
            $leavingcertificate->leaving_date = $request->leaving_date;
            $leavingcertificate->standard_studying = $request->standard_studying;
            $leavingcertificate->last_exam = $request->last_exam;
            $leavingcertificate->subjects_studied = $tagsString;
            $leavingcertificate->promoted_to = $request->promoted_to;
            $leavingcertificate->attendance  = $request->attendance;
            $leavingcertificate->fee_month = $request->fee_month;
            $leavingcertificate->part_of =  $request->part_of;
            $leavingcertificate->games = $tagsString1;
            $leavingcertificate->application_date = $request->application_date;
            $leavingcertificate->conduct = $request->conduct;
            $leavingcertificate->reason_leaving = $request->reason_leaving;
            $leavingcertificate->remark = $request->remark;
            $leavingcertificate->academic_yr = $request->academic_yr;
            $leavingcertificate->stud_id = $request->stud_id;
            $leavingcertificate->udise_pen_no= $request->udise_pen_no;
            $leavingcertificate->update();
   
            $data= DB::table('leaving_certificate')
                    ->where('sr_no',$leavingcertificate->sr_no)  
                    ->orderBy('sr_no','desc')->first();
                    
            $dynamicFilename = "LC_{$data->sr_no}_{$data->stud_name}_{$data->mid_name}_{$data->last_name}.pdf";
            // Load a view and pass the data to it
            
            $pdf = PDF::loadView('pdf.leavingcertificate', compact('data'));
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
    
    public function getLeavingCertificateStudent(Request $request){
        $section_id = $request->query('section_id');
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        try{
            if(isset($section_id)){
                $students = DB::table('student as a')
                    ->join('class as b', 'a.class_id', '=', 'b.class_id')
                    ->join('section as c', 'a.section_id', '=', 'c.section_id')
                    ->where(function($query) {
                        $query->where('slc_no', '!=', '')
                            ->orWhere('slc_no', '!=', 0);
                    })
                    ->where('a.section_id', '=', $section_id)
                    ->where('a.academic_yr', '=', $customClaims)
                    ->select('a.*','b.name as classname','c.name as sectionname')
                    ->orderByDesc('a.slc_no')
                    ->get();
                    $globalVariables = App::make('global_variables');
                    $parent_app_url = $globalVariables['parent_app_url'];
                    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                $students->each(function ($student) use($parent_app_url,$codeigniter_app_url){
                    $concatprojecturl = $codeigniter_app_url."".'uploads/student_image/';
                    if (!empty($student->image_name)) {
                        $student->image_name = $concatprojecturl."".$student->image_name;
                    } else {
                       
                        $student->image_name = '';
                    }
                }); 
            }
            else{
                $students = DB::table('student as a')
                    ->join('class as b', 'a.class_id', '=', 'b.class_id')
                    ->join('section as c', 'a.section_id', '=', 'c.section_id')
                    ->where(function($query) {
                        $query->where('slc_no', '!=', '')
                            ->where('slc_no', '!=', 0);
                    })
                    ->where('a.academic_yr', '=', $customClaims)
                    ->select('a.*','b.name as classname','c.name as sectionname')
                    ->orderByDesc('a.slc_no')
                    ->get();
                     $globalVariables = App::make('global_variables');
                    $parent_app_url = $globalVariables['parent_app_url'];
                    $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
                    $students->each(function ($student)  use($parent_app_url,$codeigniter_app_url){
                         $concatprojecturl = $codeigniter_app_url."".'uploads/student_image/';
                        if (!empty($student->image_name)) {
                            $student->image_name = $concatprojecturl."".$student->image_name;
                        } else {
                           
                            $student->image_name = '';
                        }
                    }); 
                    
            }
            
            return response()->json([
                'status'=> 200,
                'message'=>'Leaving Certificate Student List',
                'data' => $students,
                'success'=>true
                ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function getLeavingCertificateDetailStudent(Request $request,$student_id){
        try{
            
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            $leavingdetails = DB::table('student')
                                  ->where('student_id',$student_id)
                                  ->where('academic_yr',$customClaims)
                                  ->select('last_date','slc_no','slc_issue_date','leaving_remark')
                                  ->get();
                return response()->json([
                'status'=> 200,
                'message'=>'Leaving Certificate Student List',
                'data' => $leavingdetails,
                'success'=>true
                ]);       
            
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function getStudentInformationleaving(Request $request,$student_id){
           try{
                  $studentinfo = DB::table('student')
                                    ->join('class','class.class_id','=','student.class_id')
                                    ->join('section','section.section_id','=','student.section_id')
                                    ->join('parent','parent.parent_id','=','student.parent_id')
                                    ->join('user_master','user_master.reg_id','parent.parent_id')
                                    ->where('student.student_id',$student_id)
                                    ->select('student.*','class.name as classname','parent.*','section.name as sectionname','user_master.user_id as UserId')
                                    ->get();
               
                    if (!empty($studentinfo->image_name)) {
                        // Generate the full URL for the student image based on their unique image_name
                        $studentinfo->image_name = asset('storage/uploads/student_image/' . $students->image_name);
                    } else {
                        
                        $studentinfo->image_name = asset('storage/uploads/student_image/default.png');
                    }
                    $data['studentinformation'] = $studentinfo;
                    $data['studentimage'] = $studentinfo->image_name;

                    return response()->json([
                        'status'=> 200,
                        'message'=>'Leaving Certificate Student Information All Details',
                        'data' => $data,
                        'success'=>true
                        ]); 
           }
           catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
             }
    }

    public function deleteStudentLeaving(Request $request,$student_id){
        try{

              $user = $this->authenticateUser();
              $customClaims = JWTAuth::getPayload()->get('academic_year');
              $studentinfo = Student::find($student_id);
              $studentinfo->IsDelete = 'Y' ;
              $studentinfo->isModify = 'Y';
              $studentinfo->deleted_date = Carbon::today()->format('Y-m-d');
              $studentinfo->deleted_by = $user->reg_id;
              $studentinfo->update();

              $usermasterinfo = DB::table('user_master')->where('reg_id',$student_id)->where('role_id','S')->delete();
              
              $getstudentbyparent = DB::table('student')
                            ->where('parent_id', $studentinfo->parent_id)
                            ->where('IsDelete', 'N')
                            ->where('academic_yr', $customClaims)
                            ->get();

              if(count($getstudentbyparent) > 0){
                    

              } 
              else{
                    
                        DB::table('parent')
                            ->where('parent_id', $studentinfo->parent_id)
                            ->update(['IsDelete' => 'Y']);
                    
                      
                        DB::table('user_master')
                            ->where('reg_id', $studentinfo->parent_id)
                            ->where('role_id', 'P')
                            ->update(['IsDelete' => 'Y']);

                        $user_id = DB::table('user_master as a')
                            ->join('parent as b', 'a.reg_id', '=', 'b.parent_id')
                            ->where('a.role_id', 'P')
                            ->where('b.parent_id', $studentinfo->parent_id)
                            ->value('a.user_id');
                        
                            $user_data1 = [
                                "user_id" => $user_id,
                                "school_id" => "1"
                            ];
                            
                            // Send POST request to external service using Laravel's HTTP client
                            $response = Http::withHeaders([
                                'Content-Type' => 'application/json',
                            ])->post('http://aceventura.in/demo/evolvuUserService/user_delete_post', $user_data1);
                            
                            
                            if ($response->successful()) {
                                
                            } else {
                                
                                $error = $response->body();
                            }

                            $parentdetail = DB::table('contact_details')
                                                        ->where('id', $studentinfo->parent_id)
                                                        ->first();
                                                
                            $data3 = [
                                'id' => $studentinfo->parent_id ?? null,
                                'phone_no' => $parentdetail->phone_no ?? '',
                                'm_emailid' => $parentdetail->m_emailid ?? '',
                                'email_id' => $parentdetail->email_id ?? '',
                            ];
                            // Step 6: Check if the contact details exist in the 'deleted_contact_details' table
                            $existingContact = DB::table('deleted_contact_details')
                                ->where('id', $studentinfo->parent_id)
                                ->first();
                            
                            if ($existingContact) {
                                // Update if the contact already exists
                                DB::table('deleted_contact_details')
                                    ->where('id', $studentinfo->parent_id)
                                    ->update($data3);
                            } else {
                                // Insert if the contact does not exist
                                DB::table('deleted_contact_details')->insert($data3);
                            }
                            
                            // Step 7: Delete from the 'contact_details' table
                            DB::table('contact_details')->where('id', $studentinfo->parent_id)->delete();
              }
              return response()->json([
                'status'=> 200,
                'message'=>'Student Deleted Successfully',
                'data' => $studentinfo,
                'success'=>true
                ]);           
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
          }
    }

    public function getDeletedStudentList(Request $request){
        $section_id = $request->query('section_id');
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        try{
            if(isset($section_id)){
                $students = DB::table('student as a')
                ->join('class as b', 'a.class_id', '=', 'b.class_id')
                ->join('section as c', 'a.section_id', '=', 'c.section_id')
                ->where('a.IsDelete', '=', 'Y')
                ->where('a.section_id', '=', $section_id)
                ->where('a.academic_yr', '=', $customClaims)
                ->select('a.*', 'b.name as classname', 'c.name as sectionname')
                ->get();

                $students->each(function ($student) {
                    if (!empty($student->image_name)) {
                        // Generate the full URL for the student image based on their unique image_name
                        $student->image_name = asset('storage/uploads/student_image/' . $student->image_name);
                    } else {
                        
                        $student->image_name = asset('storage/uploads/student_image/default.png');
                    }
                }); 
            }
            else
            {
                $students = DB::table('student as a')
                        ->join('class as b', 'a.class_id', '=', 'b.class_id')
                        ->join('section as c', 'a.section_id', '=', 'c.section_id')
                        ->where('a.IsDelete', '=', 'Y')
                        ->where('a.academic_yr', '=', $customClaims)
                        ->select('a.*', 'b.name as classname', 'c.name as sectionname')
                        ->get();

                $students->each(function ($student) {
                    if (!empty($student->image_name)) {
                        // Generate the full URL for the student image based on their unique image_name
                        $student->image_name = asset('storage/uploads/student_image/' . $student->image_name);
                    } else {
                        
                        $student->image_name = asset('storage/uploads/student_image/default.png');
                    }
                }); 
            }
            
            return response()->json([
                'status'=> 200,
                'message'=>'Deleted Student List',
                'data' => $students,
                'success'=>true
                ]);

        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }

    public function addDeletedStudent(Request $request,$student_id){
        try{
            $user = $this->authenticateUser();
            $customClaims = JWTAuth::getPayload()->get('academic_year');
            Student::where('student_id', $student_id)
            ->where('academic_yr', $customClaims) 
            ->update([
                'IsDelete' => 'N',
                'IsModify' => 'Y',
                'deleted_date' => null,  
                'deleted_by' => null     
            ]);

            $first_name = DB::table('student')
                        ->where('student_id', $student_id)
                        ->value('student_name');
                $password = 'arnolds';
                $user_id = 'S' . str_pad($student_id, 4, "0", STR_PAD_LEFT);  // Building the user_id without quotes
                $first_name = addslashes($first_name);  // Ensure proper escaping if $first_name is a string
                
                DB::table('user_master')->insert([
                    'user_id'   => $user_id,
                    'name'      => $first_name,
                    'password'  => $password,
                    'reg_id'    => $student_id,
                    'role_id'   => 'S'
                ]);
                $parent_id = DB::table('student')
                                ->where('student_id', $student_id)
                                ->value('parent_id');

                $students = Student::where([
                    ['parent_id', '=', $parent_id],
                    ['IsDelete', '=', 'N'],
                    ['academic_yr', '=', $customClaims]
                ])->get();
                
                
                if(count($students) > 1){
                }
                else{
                    DB::table('parent')
                        ->where('parent_id', $parent_id)
                        ->update(['IsDelete' => 'N']);
                    DB::table('user_master')
                        ->where('reg_id', $parent_id)
                        ->where('role_id', 'P')
                        ->update(['IsDelete' => 'N']);

                    $currentUserName = DB::table('user_master as a')
                                            ->join('parent as b', 'a.reg_id', '=', 'b.parent_id')
                                            ->where('a.role_id', 'P')
                                            ->where('b.parent_id', $parent_id)
                                            ->select('a.user_id as user_id')
                                            ->first();
                        $user_data1 = [
                            "user_id" => $currentUserName,
                            "school_id" => "1"
                        ];
                        $user_data = json_encode($user_data1);
                        
                        $response = Http::withHeaders([
                            'Content-Type' => 'application/json',
                        ])->post('http://aceventura.in/demo/evolvuUserService/user_create_post', $user_data);
                        
                        $token_data = $response->body();
                        $data = DB::table('deleted_contact_details')
                                ->where('id', $parent_id)
                                ->get();
                        $data3 = [
                            'id' => $parent_id,
                            'phone_no' => $data[0]->phone_no ?? '',
                            'm_emailid' => $data[0]->m_emailid ?? '',
                            'email_id' => $data[0]->email_id ?? '',
                        ];

                        $contactExists = DB::table('contact_details')->where('id', $parent_id)->exists();

                        if ($contactExists) {
                        // Update the existing record
                        DB::table('contact_details')
                        ->where('id', $parent_id)
                        ->update($data3);
                        } else {
                        // Insert a new record
                        DB::table('contact_details')->insert($data3);
                        }

                        // Delete the record from 'deleted_contact_details'
                        DB::table('deleted_contact_details')
                        ->where('id', $parent_id)
                        ->delete();

                       
                }
                return response()->json([
                    'status'=> 200,
                    'message'=>'Student Added Successfully ',
                    'data' => $students,
                    'success'=>true
                    ]);
        }
        catch (Exception $e) {
            \Log::error($e); // Log the exception
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
         }
    }
}
