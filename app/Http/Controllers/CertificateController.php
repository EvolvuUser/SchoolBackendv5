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

class CertificateController extends Controller
{
    public function getSrnobonafide($id){
        try{
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
            'section_id'=>$request->section_id,
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
                'Content-Disposition' => 'inline; filename="document.pdf"',
            ]
        );
    }

    public function bonafideCertificateList(Request $request){
        $searchTerm = $request->query('q');

        
        $results = BonafideCertificate::where('class_division', 'LIKE', "%{$searchTerm}%")
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

}
