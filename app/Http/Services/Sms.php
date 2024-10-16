<?php
namespace App\Http\Services;

use App\Models\SmsHistory;
use Illuminate\Support\Facades\Log;

class Sms{

    public function sendSms(array $data){
        $data['url'] = $this->getUrl();
        switch($data['sms_type']){
            //mobile app login
            case 'login-otp':
                $data['sms'] = urlencode(" Your OTP is ".$data['otp'].", Valid For 10 Minutes - Greenwins");
                $data['temp_id'] = "1007166012345655070";
                break;

            //Noitce for the farmer.
            case 'notice':
                $data['sms'] =urlencode($data['title']."Notice -".$data['firstTwentyChars'].$data['url1']." is waiting on the app . Login ".$data['url1']." - Greenwins");
                $data['temp_id'] = "1007163411447866679";
                break;    
            
            //fpo created event in program menu
           // {#var#} Event - {#var#}{#var#} starts on {#var#} is waiting on the app . Login {#var#} - Greenwins
            case 'event-created':
                $data['sms'] = $data['title'].' Event - '.$data['desc'].' starts on '.date('d-m-Y h:i A', strtotime($data['date'])).' is waiting on the app . Login '.$data['url'].' - Greenwins';
                $data['temp_id'] = "1007163411461355163";
                break;

           

            //after admin create fpo
            case 'fpo-created':
                $data['sms'] = 'Dear '.$data['name'].', Your registered email '.$data['email'].', userid '.$data['username'].', password '.$data['password'].', login to '.$data['url'].' - Greenwins';
                $data['temp_id'] = "1007163342450180811";
                break;
            //after farmer update kyc
            case 'farmer-kyc-updated':
                $data['sms'] = 'Dear ' . $data['name'] . ', Changes to your profile details have been made successfully. For details please check ' . $data['url'] . ' - Greenwins';
                $data['temp_id'] = "1007164438625564178";
                break;
            //Yield update
            case 'farmer-crop-detail-updated':
                $data['sms'] = 'Hi ' . $data['name'] . ' Your crop detail is updated at ' . date('d-m-Y') . ' on Arthagri. Login ' . $data['url'] . ' with mobile no %26 OTP - Greenwins';
                $data['temp_id'] = "1007163411123090552";
                break;
            //Purchase Sale
            case 'purchase-sale':
                $data['sms'] = $data['farmer_name'] . ' Your crop sales data is updated to ' . $data['fpo_name'] . '. Login ' . $data['url'] . ' with mobile no %26 OTP - Greenwins';
                $data['temp_id'] = "1007163411243858254";
                break;
            //FPO Agreement
            case 'fpo-agreement':
                $data['sms'] = 'Hi ' . $data['fpo_name'] . ', Crop ' . $data['crop_name'] . '- Qty. ' . $data['crop_qty'] . '- Price ' . $data['expected_amt'] . ' has been agreed with FPO ' . $data['farmer_name'] . ' - Greenwins';
                $data['temp_id'] = "1007163411353942759";
                break;
            //FPO Price Update 
            case 'fpo-price-update':
                $data['sms'] = $data['farmer_name'] . ' Company has updated price ' . $data['expected_amt'] . ' on the app. Login ' . $data['url'] . ' with mobile no %26 OTP - Greenwins';
                $data['temp_id'] = "1007163411479455578";
                break;
            //After Farmer registration
            case 'farmer-registration':
                $data['sms'] = 'Dear ' . $data['name'] . ', Your registration is accepted.Login ' . $data['mobile'] . ' to access your account- ' . $data['url'] . ' - GreenWins';
                $data['temp_id'] = "1007163326215585984";
                break;
            case 'farmer-kyc-approved':
                $data['sms'] = 'HI ' . $data['name'] . ' Your KYC details is approved by company on Arthagri. Login ' . $data['url'] . ' with mobile no %26 OTP - Greenwins';
                $data['temp_id'] = "1007163410998071176";
                break;
            case 'farmer-edit':
                $data['sms'] = $data['name'] . ' Your personal details is updated on Arthagri Fpo Support Admin by  Login ' . $data['url'] . ' with mobile no %26 OTP ' . $data['mobile'] . ' - Greenwins';
                $data['temp_id'] = "1007163411072313193";
                break;
            default:
                return;
        }
        // $this->curl($data);
        $response = $this->curl($data);

        // Store the response in SmsHistory model
        SmsHistory::create([
            'fpo_id' => auth()->user()->id,
            'sms_message_id' => $data['sms_message_id'],
            'response' => $response,
        ]);
    }
    public function getUrl(){
        if(env('APP_API_ENV')=='demo'){
            return 'bit.ly/3EsNnGA';
        }else if(env('APP_API_ENV')=='stage'){
            return 'bit.ly/3xEs2ZA';
        }else if(env('APP_API_ENV')=='production'){
            return 'bit.ly/37vmOVd';
        }else{
            return url("");
        }
    }

    public function curl(array $data)
    {
        // $auth_key = "19684ATWnIsjC2o61ee8f23P15";
        $auth_key = "3738686167726933333057";
        $sender = "GRNWIN";
        $AppURLinPlayStore = 'https://bit.ly/3w5WjOT';
        $mobiles = $data['mobile'];
        $templateId = $data['temp_id'];
        $sms = $data['sms'];
        $url = 'http://control.yourbulksms.com/api/sendhttp.php?authkey=' . $auth_key . '&mobiles=' . $mobiles . '&message=' . $sms . '&sender=' . $sender . '&route=2&country=91&DLT_TE_ID=' . $templateId;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log::info('SMS API Response:', ['response' => $response, 'http_code' => $httpCode]);
        return $response;
    }
}
