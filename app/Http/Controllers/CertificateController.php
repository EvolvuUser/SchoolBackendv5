<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use DateTime;
use Carbon\Carbon;

class CertificateController extends Controller
{
    public function getSrnobonafide($id){
        try{
        $srnobonafide = DB::table('bonafide_certificate')->first();
        $studentinformation=DB::table('student')->where('student_id',$id)->first();
        if (is_null($srnobonafide)) {
            $data['sr_no'] = '1';
            $data['date']  = Carbon::today()->format('Y-m-d');
            $data['studentinformation'] = $studentinformation; 
        }
        else{
            $data['sr_no'] = $srnobonafide->sr_no + 1 ;
            $data['date']  = Carbon::today()->format('Y-m-d');
            $data['studentinformation'] = $studentinformation;
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

}
