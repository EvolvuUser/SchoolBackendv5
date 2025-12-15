<?php
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Http\Services\SmartMailer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Student;

  function get_teacher_timetable_with_multiple_selection_for_teacher_app($column_name, $teacher_id, $acd_yr)
    {
        // Extract the starting year, e.g. "2025" from "2025-2026"
        $start_year = intval(substr($acd_yr, 0, strpos($acd_yr, '-')));

        if ($start_year < 2025) {
            // Before 2025 logic
            $query = DB::select("
                SELECT 
                    d.name AS class, 
                    e.name AS section, 
                    c.name AS subject, 
                    a.period_no 
                FROM timetable a
                JOIN subject b ON a.class_id = b.class_id AND a.section_id = b.section_id 
                JOIN subject_master c ON b.sm_id = c.sm_id 
                JOIN class d ON a.class_id = d.class_id 
                JOIN section e ON a.section_id = e.section_id 
                WHERE a.$column_name LIKE CONCAT('%', c.name, '%')
                    AND b.teacher_id = ?
                    AND a.academic_yr = ?
                    AND b.academic_yr = ?
                ORDER BY a.period_no
            ", [$teacher_id, $acd_yr, $acd_yr]);
        } else {
            // From 2025 onwards logic
            $query = DB::select("
                SELECT 
                    d.name AS class, 
                    e.name AS section, 
                    c.name AS subject, 
                    a.period_no 
                FROM timetable a
                JOIN subject_master c ON SUBSTRING_INDEX(a.$column_name, '^', 1) = c.sm_id
                JOIN class d ON a.class_id = d.class_id
                JOIN section e ON a.section_id = e.section_id
                WHERE SUBSTRING_INDEX(a.$column_name, '^', -1) = ?
                    AND a.academic_yr = ?
                ORDER BY a.period_no
            ", [$teacher_id, $acd_yr]);
        }

        return $query;
    }
    
     function daily_notes_create($data, $str_classes, $filelist = '', $filenamelist = '', $random_no)
     {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        for ($i = 0; $i < count($str_classes); $i++) {
    
            $data['class_id'] = substr($str_classes[$i], 0, strpos($str_classes[$i], '^'));
            $data['section_id'] = substr($str_classes[$i], strpos($str_classes[$i], '^') + 1);
    
            if ($filenamelist != '') {
                // This part code is to save files coming from app
                $filenamelist1 = str_replace([' ', '"', '[', ']'], "", $filenamelist);
                $filename_str = explode(",", $filenamelist1);
    
                $k = DB::table('notes_master')->insertGetId($data);
    
                $data1['notes_id'] = $k;
                if (str_contains($codeigniter_app_url, 'SACSv4test')) {
                    $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
                } else {
                    $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
                }
                $destination = $filePath."uploads/daily_notes/" . $data['date'] . '/' . $random_no;
                $note_id_folder = $filePath."uploads/daily_notes/" . $data['date'] . '/' . $k;
                
                if (!file_exists($note_id_folder)) {
                    mkdir($note_id_folder, 0777, true);
                }
    
                for ($j = 0; $j < count($filename_str); $j++) {
                    $imgNameEnd = $filename_str[$j];
                    $uploaded_file = $destination . '/' . $imgNameEnd;
    
                    if (file_exists($uploaded_file)) {
                        $data1['file_size'] = filesize($uploaded_file);
                        $data1['image_name'] = $imgNameEnd;
    
                        DB::table('notes_detail')->insert($data1);
                        copy($destination . '/' . $imgNameEnd, $note_id_folder . '/' . $imgNameEnd);
                    }
                }
    
                $last_value = 1 + $i;
                if ($last_value == count($str_classes)) {
                    for ($j = 0; $j < count($filename_str); $j++) {
                        $imgNameEnd = $filename_str[$j];
                        if (file_exists($destination . '/' . $imgNameEnd)) {
                            unlink($destination . '/' . $imgNameEnd);
                        }
                    }
                    if (file_exists($destination)) {
                        rmdir($destination);
                    }
                }
    
            } else {
                // This part code will be executed when there are no attachments in app
                // This part of code will execute every time from web
                if ($filelist != '') {
                    $k = DB::table('notes_master')->insertGetId($data);
                    $data1['notes_id'] = $k;
                    if (str_contains($codeigniter_app_url, 'SACSv4test')) {
                    $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
                    } else {
                        $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
                    }
    
                    $destination = $filePath."uploads/daily_notes/" . $data['date'] . '/' . $random_no;
                    $note_id_folder = $filePath."uploads/daily_notes/" . $data['date'] . '/' . $k;
    
                    if (!file_exists($note_id_folder)) {
                        mkdir($note_id_folder, 0777, true);
                    }
    
                    for ($j = 0; $j < count($filelist); $j++) {
                        $filename = $_FILES['userfile']['name'][$j];
                        $uploadedFile = $destination . '/' . $filename;
    
                        if (file_exists($uploadedFile)) {
                            $file_size = filesize($uploadedFile);
                            $data1['image_name'] = $filename;
                            $data1['file_size'] = $file_size;
                            DB::table('notes_detail')->insert($data1);
    
                            copy($destination . '/' . $filename, $note_id_folder . '/' . $filename);
                        }
                    }
    
                    $last_value = 1 + $i;
                    if ($last_value == count($str_classes)) {
                        for ($j = 0; $j < count($filelist); $j++) {
                            $imgNameEnd = $_FILES['userfile']['name'][$j];
                            if (file_exists($destination . '/' . $imgNameEnd)) {
                                unlink($destination . '/' . $imgNameEnd);
                            }
                        }
                        if (file_exists($destination)) {
                            rmdir($destination);
                        }
                    }
    
                } else {
                    // This part will be executed when there will no attachment from web and app
                    DB::table('notes_master')->insert($data);
                    $k = DB::getPdo()->lastInsertId();
                }
            }
        }
    
        return true;
     }
     
     function daily_notes_edit($data, $deleted_images, $filelist, $filenamelist)
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        if (str_contains($codeigniter_app_url, 'SACSv4test')) {
        $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
        } else {
            $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
        }
        $k = $data['notes_id'];
    
        if ($filenamelist != '') {
            if ($deleted_images != '') {
                $deleted_images1 = str_replace([' ', '"', '[', ']'], "", $deleted_images);
                $deleted_images_string = explode(",", $deleted_images1);
    
                for ($i = 0; $i < count($deleted_images_string); $i++) {
                    
                    $path = $filePath."uploads/daily_notes/" . date('Y-m-d', strtotime($data['date'])) . '/' . $data['notes_id'] . '/' . $deleted_images_string[$i];
                    if (file_exists($path)) {
                        unlink($path);
                    }
    
                    DB::table('notes_detail')
                        ->where('notes_id', $data['notes_id'])
                        ->where('image_name', $deleted_images_string[$i])
                        ->delete();
                }
            }
    
            $filenamelist1 = str_replace([' ', '"', '[', ']'], "", $filenamelist);
            $filename_str = explode(",", $filenamelist1);
    
            DB::table('notes_master')
                ->where('notes_id', $data['notes_id'])
                ->update($data);
    
            $data1['notes_id'] = $data['notes_id'];
    
            if ($filelist == '') {
                
                $destination = $filePath."uploads/daily_notes/" . $data['date'] . '/' . $data['notes_id'];
    
                for ($j = 0; $j < count($filename_str); $j++) {
                    $imgNameEnd = $filename_str[$j];
                    $uploaded_file = $destination . '/' . $imgNameEnd;
    
                    if (file_exists($uploaded_file)) {
                        $data1['file_size'] = filesize($uploaded_file);
                        $data1['image_name'] = $imgNameEnd;
    
                        DB::table('notes_detail')->insert($data1);
                    }
                }
    
            } else {
                $filelist1 = str_replace([' ', '"', '[', ']'], "", $filelist);
                $filelist_string = explode(",", $filelist1);
    
                for ($j = 0; $j < count($filelist_string); $j++) {
                    $destination = $filePath."uploads/daily_notes/" . $data['date'] . '/' . $data['notes_id'] . '/';
    
                    if (!file_exists($destination)) {
                        mkdir($destination, 0777, true);
                    }
    
                    $datadetails = base64_decode($filelist_string[$j]);
                    $imgNameEnd = $filename_str[$j];
                    $destinationData = $destination . '/' . $imgNameEnd;
    
                    if (file_put_contents($destinationData, $datadetails)) {
                        $data1['image_name'] = $imgNameEnd;
                        $data1['file_size'] = filesize($destinationData);
                        DB::table('notes_detail')->insert($data1);
                    }
                }
            }
    
            return true;
    
        } else {
            if ($deleted_images != '') {
                $deleted_images1 = str_replace(['"', '[', ']'], "", $deleted_images);
                $deleted_images_string = explode(",", $deleted_images1);
    
                for ($i = 0; $i < count($deleted_images_string); $i++) {
                    $path = $filePath."uploads/daily_notes/" . date('Y-m-d', strtotime($data['date'])) . '/' . $data['notes_id'] . '/' . $deleted_images_string[$i];
                    if (file_exists($path)) {
                        unlink($path);
                    }
    
                    DB::table('notes_detail')
                        ->where('notes_id', $data['notes_id'])
                        ->where('image_name', $deleted_images_string[$i])
                        ->delete();
                }
            }
    
            if ($filelist != '') {
                DB::table('notes_master')
                    ->where('notes_id', $data['notes_id'])
                    ->update($data);
    
                for ($i = 0; $i < count($filelist); $i++) {
                    $destination = $filePath."uploads/daily_notes/" . date('Y-m-d', strtotime($data['date'])) . '/' . $data['notes_id'] . '/';
                    $data1['notes_id'] = $k;
                    $filename = $_FILES['userfile1']['name'][$i];
                    $uploadedFile = $destination . $filename;
    
                    if (file_exists($uploadedFile)) {
                        $file_size = filesize($uploadedFile);
                        $data1['image_name'] = $filename;
                        $data1['file_size'] = $file_size;
                        DB::table('notes_detail')->insert($data1);
                    }
                }
            } else {
                DB::table('notes_master')
                    ->where('notes_id', $data['notes_id'])
                    ->update($data);
                return true;
            }
        }
    }
    
    function daily_notes_delete($note_id)
    {
        //crudhelper
         $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        if (str_contains($codeigniter_app_url, 'SACSv4test')) {
        $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
        } else {
            $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
        }
        $daily_notes = get_notes_details($note_id);
        
        if ($daily_notes) {
            $daily_notes_date = $daily_notes[0]->date;
    
            foreach ($daily_notes as $row) {
                $path = $filePath.'uploads/daily_notes/' . date('Y-m-d', strtotime($row->date)) . '/' . $note_id . '/' . $row->image_name;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
    
            // Delete the folder named by note_id (if exists)
            $path = $filePath.'uploads/daily_notes/' . $daily_notes_date . '/' . $note_id;
            if (file_exists($path)) {
                @rmdir($path);
            }
        }
    
        
        DB::table('notes_master')->where('notes_id', $note_id)->delete();
        DB::table('notes_detail')->where('notes_id', $note_id)->delete();
    
        return true;
    }
    
   function get_notes_details($notes_id)
    {
        $query = DB::table('notes_master as a')
                    ->join('notes_detail as b', 'a.notes_id', '=', 'b.notes_id')
                    ->select('a.*', 'b.*')
                    ->where('a.notes_id', $notes_id)
                    ->get();
    
        return $query->map(function ($item) {
            return (array) $item; 
        })->toArray();
    }
    
    function daily_notes_publish($note_id,$class_id,$section_id){
       $data2['publish_date']= date('Y-m-d');
       $data2['publish']='Y';
	   DB::table('notes_master')
            ->where('notes_id', $note_id)
            ->update($data2);
//       $dailynotesdata = $this->getClassSectionContactData($class_id,$section_id);
//       //print_r($dailynotesdata);exit;
        
// 		for($i=0; $i< count($dailynotesdata); $i++)
// 		{ 
// 			$smsdata= $this->crud_model->getSmsDataById($dailynotesdata[$i]['parent_id'],$dailynotesdata[$i]['student_id']);
// 			$smsdatacount= count($smsdata);
		   
// 			if($smsdatacount=='0')
// 			{
// 				$data1['student_id']= $dailynotesdata[$i]['student_id'];
// 				$data1['parent_id']= $dailynotesdata[$i]['parent_id'];
// 				$data1['phone']= $dailynotesdata[$i]['phone_no'];
// 				$data1['homework']= 0;
// 				$data1['remark']= 0;
// 				$data1['notice']= 0;
// 				$data1['note']= 1;
// 				$data1['achievement']= 0;
// 				$data1['sms_date']=date('Y-m-d H:i:s');
// 				$this->db->insert('daily_sms',$data1);
// 			}else
// 			{
// 				$smsdata[0]['note']=  1 + $smsdata[0]['note'];
// 				$smsdata[0]['sms_date']=date('Y-m-d H:i:s');
// 				$this->db->where('parent_id' , $smsdata[0]['parent_id']);
// 				$this->db->where('student_id', $smsdata[0]['student_id']);
// 				$this->db->update('daily_sms' , $smsdata[0]);
// 			}
// 		}
        
//     	//Lija 14-07-18 // 28-11-18 Uncommented this code and changed $param3 to $class_id and $param4 to $section_id
// 		$classteacher_data = $this->crud_model->get_classteacher_allotment_info($class_id,$section_id);
// 		for($i=0; $i< count($classteacher_data);$i++)
//         { 
// 			$teachersmsdata= $this->crud_model->getTeacherSmsDataById($classteacher_data[$i]['teacher_id'],$class_id,$section_id);
// 			$teachersmsdatacount= count($teachersmsdata);
//             $teacher_id=$classteacher_data[$i]['teacher_id'];
//             if($teachersmsdatacount=='0')
//             {
// 				$tsmsdata['teacher_id']= $teacher_id;
// 				$tsmsdata['class_id']= $class_id;
// 				$tsmsdata['section_id']= $section_id;
// 				$tsmsdata['phone']= $this->crud_model->get_teacher_phoneno($teacher_id)[0]['phone'];
// 				$tsmsdata['homework']= 0;
// 				$tsmsdata['notice']= 0;
// 				$tsmsdata['note']= 1;
// 				$tsmsdata['sms_date']= date('Y-m-d H:i:s');
// 				$this->db->insert('daily_sms_for_teacher',$tsmsdata);
//             }else{
// 				$teachersmsdata[0]['note']=  1 + $teachersmsdata[0]['note'];
// 				$teachersmsdata[0]['sms_date']= date('Y-m-d H:i:s');
//                 $this->db->where('teacher_id' , $teacher_id);
// 				$this->db->where('class_id', $class_id);
// 				$this->db->where('section_id', $section_id);
// 				$this->db->update('daily_sms_for_teacher' , $teachersmsdata[0]);
//             }
//         }
        
//         $tokendata = $this->crud_model->getClassTokenData($class_id,$section_id);
//         //for loop tokendata
//         for($i=0; $i< count($tokendata);$i++){
//             //Insert data to daily_notification table
//             $dailyNotification['student_id']= $tokendata[$i]['student_id'];
//             $dailyNotification['parent_id']= $tokendata[$i]['parent_teacher_id'];
//             $dailyNotification['homework_id']= 0;
//             $dailyNotification['remark_id']= 0;
//             $dailyNotification['notice_id']= 0;
//             $dailyNotification['notes_id']= $note_id;
//             $dailyNotification['notification_date']= date('Y-m-d');
//             $dailyNotification['token']= $tokendata[$i]['token'];
//             $this->db->insert('daily_notifications',$dailyNotification);
//         }
//         $this->crud_model->push_notification();
        return TRUE;
    }
    
    function get_daily_notes_teacherwise($teacher_id, $acd_yr)
    {
        $query = DB::table('notes_master as a')
            ->select(
                'a.*',
                'b.name as subjectname',
                'b.sm_id',
                'c.name as classname',
                'c.class_id',
                'd.name as sectionname',
                'd.section_id'
            )
            ->leftJoin('subject_master as b', 'a.subject_id', '=', 'b.sm_id')
            ->join('class as c', 'a.class_id', '=', 'c.class_id')
            ->join('section as d', 'a.section_id', '=', 'd.section_id')
            ->where('a.teacher_id', $teacher_id)
            ->where('a.academic_yr', $acd_yr)
            ->orderBy('a.date', 'DESC')
            ->get();
    
        return $query->toArray();
    }
    
    function get_notes_images_onnoteid($notes_id)
    {
        $query = DB::table('notes_detail')
                    ->select('*')
                    ->where('notes_id', $notes_id)
                    ->get();
    
        return $query->toArray();
    }
    
    function get_students_viewed_note($notes_id, $class_id, $section_id, $academic_yr)
    {
        $query = DB::select("
            SELECT 
                b.roll_no, 
                b.first_name, 
                b.mid_name, 
                b.last_name, 
                1 AS read_status, 
                b.student_id 
            FROM notes_read_log AS a 
            JOIN student AS b ON a.parent_id = b.parent_id 
            WHERE a.notes_id = $notes_id 
              AND b.class_id = '$class_id' 
              AND b.section_id = '$section_id' 
              AND b.isDelete = 'N' 
              AND b.academic_yr = '$academic_yr'
            
            UNION 
            
            SELECT 
                b.roll_no, 
                b.first_name, 
                b.mid_name, 
                b.last_name, 
                0 AS read_status, 
                b.student_id 
            FROM student AS b 
            WHERE b.class_id = '$class_id' 
              AND b.section_id = '$section_id' 
              AND b.isDelete = 'N' 
              AND b.academic_yr = '$academic_yr' 
              AND b.parent_id NOT IN (
                  SELECT parent_id 
                  FROM notes_read_log 
                  WHERE notes_id = $notes_id
              )
        ");
    
        return $query;
    }
    
    function get_subject_alloted_to_teacher_by_multipleclass($str_classes,$teacher_id,$acd_yr){
        $str_classes = explode(',', $str_classes);

        $sub_query = '';
        $query_str = "SELECT sm_id, name FROM subject_master WHERE sm_id IN (";
    
        for ($i = 0; $i < count($str_classes); $i++) {
            if ($i == 0) {
                $sub_query = "SELECT a.sm_id FROM subject a 
                              WHERE CONCAT(CONCAT(a.class_id,'^'), a.section_id) = '" . $str_classes[$i] . "' 
                              AND a.teacher_id = " . $teacher_id . " 
                              AND a.academic_yr = '" . $acd_yr . "'";
            }
            if ($i > 0) {
                $sub_query .= " AND a.sm_id IN (
                                  SELECT b.sm_id FROM subject b 
                                  WHERE CONCAT(CONCAT(b.class_id,'^'), b.section_id) = '" . $str_classes[$i] . "' 
                                  AND b.teacher_id = " . $teacher_id . " 
                                  AND b.academic_yr = '" . $acd_yr . "'
                                )";
            }
        }
    
        $final_query = $query_str . $sub_query . ")";
    
        $query = DB::select($final_query);
        return $query;
        
    }
    
    function homework_create($data, $filelist, $filenamelist = '', $random_no)
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        if (str_contains($codeigniter_app_url, 'SACSv4test')) {
        $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
        } else {
            $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
        }
        if ($filenamelist != '') {
            
            // $filenamelist1 = str_replace([' ', '"', '[', ']'], "", $filenamelist);
            // $filename_str = explode(",", $filenamelist1);

            $filename_str = is_array($filenamelist)
                ? $filenamelist
                : [$filenamelist];
    
            $k = DB::table('homework')->insertGetId($data);
            $data1['homework_id'] = $k;
    
            // Get student details
            $stu_details = get_students_by_class_section($data['class_id'], $data['section_id'], $data['academic_yr']);
            $count_stu = count($stu_details);
    
            foreach ($stu_details as $ww) {
                $data2['parent_id'] = $ww['parent_id'];
                $data2['student_id'] = $ww['student_id'];
                $data2['homework_id'] = $k;
                $data2['homework_status'] = "Assigned";
                $data2['comment'] = "";
                DB::table('homework_comments')->insert($data2);
            }
    
            // File upload and renaming
            $destination = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . "/" . $random_no;
    
            for ($j = 0; $j < count($filename_str); $j++) {
                $imgNameEnd = $filename_str[$j];
                $uploaded_file = $destination . '/' . $imgNameEnd;
    
                $data1['file_size'] = filesize($uploaded_file);
                $data1['image_name'] = $imgNameEnd;
    
                DB::table('homework_detail')->insert($data1);
            }
    
            // Rename folder from random_no to homework_id
            $oldFolder = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . "/" . $random_no;
            $newFolder = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . "/" . $k;
            if (file_exists($oldFolder)) {
                rename($oldFolder, $newFolder);
            }
    
            return true;
        } else {
            
            if ($filelist != '') {
                $k = DB::table('homework')->insertGetId($data);
                $data1['homework_id'] = $k;
    
                $stu_details = get_students_by_class_section($data['class_id'], $data['section_id'], $data['academic_yr']);
                $count_stu = count($stu_details);
    
                foreach ($stu_details as $ww) {
                    $data2['parent_id'] = $ww['parent_id'];
                    $data2['student_id'] = $ww['student_id'];
                    $data2['homework_id'] = $k;
                    $data2['homework_status'] = "Assigned";
                    $data2['comment'] = "";
                    DB::table('homework_comments')->insert($data2);
                }
    
                for ($j = 0; $j < count($filelist); $j++) {
                    $destination = $filePath."uploads/homework/" . $data['start_date'] . "/" . $random_no . '/';
    
                    $filename = $_FILES['userfile']['name'][$j];
                    $data1['homework_id'] = $k;
                    $data1['image_name'] = $filename;
    
                    $destinationData = $destination . $filename;
                    $data1['file_size'] = filesize($destinationData);
    
                    DB::table('homework_detail')->insert($data1);
                }
    
                $oldFolder = $filePath."uploads/homework/" . $data['start_date'] . "/" . $random_no;
                $newFolder = $filePath."uploads/homework/" . $data['start_date'] . "/" . $k;
                if (file_exists($oldFolder)) {
                    rename($oldFolder, $newFolder);
                }
            } else {
                $k = DB::table('homework')->insertGetId($data);
                $data1['homework_id'] = $k;
    
                $stu_details = get_students_by_class_section($data['class_id'], $data['section_id'], $data['academic_yr']);
                $count_stu = count($stu_details);
                // dd($stu_details);
                foreach ($stu_details as $ww) {
                    $data2['parent_id'] = $ww['parent_id'];
                    $data2['student_id'] = $ww['student_id'];
                    $data2['homework_id'] = $k;
                    $data2['homework_status'] = "Assigned";
                    $data2['comment'] = "";
                    DB::table('homework_comments')->insert($data2);
                }
    
                return true;
            }
        }
    }
    
    function get_students_by_class_section($class_id, $section_id, $acd_yr)
    {
        $query = DB::table('student')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->where('IsDelete', 'N')
                ->where('academic_yr', $acd_yr)
                ->orderBy('roll_no', 'asc')
                ->orderBy('reg_no', 'asc')
                ->get()
                ->map(function ($item) {
                    return (array)$item;
                })
                ->toArray();
    
        return $query;
    }
    
    function homework_edit($homework_id, $data, $deleted_images, $filedata = '', $filenamelist = '')
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        if (str_contains($codeigniter_app_url, 'SACSv4test')) {
        $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
        } else {
            $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
        }
        $k = $homework_id;
    
        if ($filenamelist != '') {
    
            // Delete images if any
            if ($deleted_images != '') {
                $deleted_images1 = str_replace([' ', '"', '[', ']'], "", $deleted_images);
                $deleted_images_string = explode(",", $deleted_images1);
    
                foreach ($deleted_images_string as $deleted_image) {
                    $path = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . '/' . $k . '/' . $deleted_image;
    
                    if (file_exists($path) && is_file($path)) {
                        unlink($path);
                    }
    
                    DB::table('homework_detail')
                        ->where('homework_id', $k)
                        ->where('image_name', $deleted_image)
                        ->delete();
                }
            }
    
            $filenamelist1 = str_replace([' ', '"', '[', ']'], "", $filenamelist);
            $filename_str = explode(",", $filenamelist1);
    
            DB::table('homework')
                ->where('homework_id', $homework_id)
                ->update($data);
    
            $data1['homework_id'] = $k;
    
            $destination = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . '/' . $k . '/';
    
            foreach ($filename_str as $imgNameEnd) {
                $uploaded_file = $destination . $imgNameEnd;
                $data1['file_size'] = file_exists($uploaded_file) ? filesize($uploaded_file) : 0;
                $data1['image_name'] = $imgNameEnd;
    
                DB::table('homework_detail')->insert($data1);
            }
    
            return true;
        } 
        else { 
            if ($deleted_images != '') {
                $deleted_images1 = str_replace([' ', '"', '[', ']'], "", $deleted_images);
                $deleted_images_string = explode(",", $deleted_images1);
    
                foreach ($deleted_images_string as $deleted_image) {
                    $path = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . '/' . $k . '/' . $deleted_image;
    
                    if (file_exists($path) && is_file($path)) {
                        unlink($path);
                    }
    
                    DB::table('homework_detail')
                        ->where('homework_id', $k)
                        ->where('image_name', $deleted_image)
                        ->delete();
                }
            }
    
            if ($filedata != '') {
    
                DB::table('homework')
                    ->where('homework_id', $homework_id)
                    ->update($data);
    
                $data1['homework_id'] = $k;
    
                for ($j = 0; $j < count($filedata); $j++) {
                    $destination = $filePath."uploads/homework/" . date('Y-m-d', strtotime($data['start_date'])) . '/' . $k . '/';
    
                    if (!file_exists($destination)) {
                        mkdir($destination, 0777, true);
                    }
    
                    $filename = $_FILES['userfile1']['name'][$j];
                    $tmp_name = $_FILES['userfile1']['tmp_name'][$j];
                    $targetPath = $destination . $filename;
    
                    if (copy($tmp_name, $targetPath)) {
                        $data1['image_name'] = $filename;
                        $data1['file_size'] = file_exists($targetPath) ? filesize($targetPath) : 0;
                        DB::table('homework_detail')->insert($data1);
                    }
                }
            } 
            else {
                DB::table('homework')
                    ->where('homework_id', $homework_id)
                    ->update($data);
    
                return true;
            }
        }
    
        return true;
    }
    
    function homework_publish($homework_id, $class_id, $section_id)
    {
        // Step 1: Update Homework Publish Info
        $data = [
            'homework_id' => $homework_id,
            'publish' => 'Y',
            'publish_date' => Carbon::now()->format('Y-m-d'),
        ];
    
        DB::table('homework')
            ->where('homework_id', $homework_id)
            ->update($data);
    
        // Step 2: Get Students in Class/Section
        // $homeworkdata = $this->getClassSectionContactData($class_id, $section_id);
    
        // $pushNotification = [];
    
        // // Step 3: Process Students and Parents for SMS
        // foreach ($homeworkdata as $row) {
    
        //     $smsdata = $this->getSmsDataById($row['parent_id'], $row['student_id']);
        //     $smsdatacount = count($smsdata);
        //     $parentUserId = $this->get_parent_userid($row['parent_id']);
    
        //     $pushNotification[] = $parentUserId;
    
        //     if ($smsdatacount == 0) {
        //         $data1 = [
        //             'student_id' => $row['student_id'],
        //             'parent_id' => $row['parent_id'],
        //             'phone' => $row['phone_no'],
        //             'homework' => 1,
        //             'remark' => 0,
        //             'notice' => 0,
        //             'note' => 0,
        //             'achievement' => 0,
        //             'sms_date' => Carbon::now()->format('Y-m-d H:i:s'),
        //         ];
    
        //         DB::table('daily_sms')->insert($data1);
        //     } else {
        //         $smsdata[0]['homework'] = $smsdata[0]['homework'] + 1;
        //         $smsdata[0]['sms_date'] = Carbon::now()->format('Y-m-d H:i:s');
    
        //         DB::table('daily_sms')
        //             ->where('parent_id', $smsdata[0]['parent_id'])
        //             ->where('student_id', $smsdata[0]['student_id'])
        //             ->update($smsdata[0]);
        //     }
    
        //     // You can build message string if needed later
        //     // $message = "There is a homework posted for " . $this->get_student_name($row['student_id']);
        // }
    
        // // Step 4: Get FCM Tokens and Insert Notifications
        // $tokendata = $this->getClassSectionTokenData($class_id, $section_id);
    
        // foreach ($tokendata as $tokenRow) {
        //     $dailyNotification = [
        //         'student_id' => $tokenRow['student_id'],
        //         'parent_id' => $tokenRow['parent_teacher_id'],
        //         'homework_id' => $homework_id,
        //         'remark_id' => 0,
        //         'notice_id' => 0,
        //         'notes_id' => 0,
        //         'notification_date' => Carbon::now()->format('Y-m-d'),
        //         'token' => $tokenRow['token'],
        //     ];
    
        //     DB::table('daily_notifications')->insert($dailyNotification);
        // }
    
        // // Step 5: Push Notification
        // $this->push_notification();
    
        // // Step 6: Notify Class Teachers
        // $classteacher_data = $this->get_classteacher_allotment_info($class_id, $section_id);
    
        // foreach ($classteacher_data as $teacherRow) {
        //     $teacher_id = $teacherRow['teacher_id'];
    
        //     $teachersmsdata = $this->getTeacherSmsDataById($teacher_id, $class_id, $section_id);
        //     $teachersmsdatacount = count($teachersmsdata);
    
        //     if ($teachersmsdatacount == 0) {
        //         $tsmsdata = [
        //             'teacher_id' => $teacher_id,
        //             'class_id' => $class_id,
        //             'section_id' => $section_id,
        //             'phone' => $this->get_teacher_phoneno($teacher_id)[0]['phone'],
        //             'homework' => 1,
        //             'notice' => 0,
        //             'note' => 0,
        //             'sms_date' => Carbon::now()->format('Y-m-d H:i:s'),
        //         ];
    
        //         DB::table('daily_sms_for_teacher')->insert($tsmsdata);
        //     } else {
        //         $teachersmsdata[0]['homework'] = $teachersmsdata[0]['homework'] + 1;
        //         $teachersmsdata[0]['sms_date'] = Carbon::now()->format('Y-m-d H:i:s');
    
        //         DB::table('daily_sms_for_teacher')
        //             ->where('teacher_id', $teacher_id)
        //             ->where('class_id', $class_id)
        //             ->where('section_id', $section_id)
        //             ->update($teachersmsdata[0]);
        //     }
        // }
    
        return true;
    }
    
    function homework_delete($homework_id)
    {
        $globalVariables = App::make('global_variables');
        $parent_app_url = $globalVariables['parent_app_url'];
        $codeigniter_app_url = $globalVariables['codeigniter_app_url'];
        if (str_contains($codeigniter_app_url, 'SACSv4test')) {
        $filePath = '/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/SACSv4test/';
        } else {
            $filePath ='/home/u333015459/domains/sms.arnoldcentralschool.org/public_html/';
        }
        $homework_info = get_homework_details($homework_id);
    
        if (!empty($homework_info)) {
            foreach ($homework_info as $row) {
                $startDate = Carbon::parse($row->start_date)->format('Y-m-d');
                $path = $filePath."uploads/homework/{$startDate}/{$row->homework_id}/{$row->image_name}";
    
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
        DB::table('homework')->where('homework_id', $homework_id)->delete();
        DB::table('homework_comments')->where('homework_id', $homework_id)->delete();
        DB::table('homework_detail')->where('homework_id', $homework_id)->delete();
    
        return true;
    }
    
    function get_homework_details($homework_id)
    {
        return DB::table('homework as a')
            ->join('homework_detail as b', 'a.homework_id', '=', 'b.homework_id')
            ->select('a.*', 'b.*')
            ->where('a.homework_id', $homework_id)
            ->get()
            ->toArray();
    }
    
    function get_homework_images_onnoteid($homework_id)
    {
        $query = DB::table('homework_detail')
            ->select('*')
            ->where('homework_id', $homework_id)
            ->get();
    
        return $query->toArray();
    }
    
    function get_homework_teacherwise($teacher_id, $acd_yr)
    {
        $query = DB::table('homework')
            ->select(
                'homework.*',
                DB::raw('(SELECT COUNT(*) FROM homework_comments a WHERE a.homework_id = homework.homework_id AND (a.parent_comment != " " OR a.parent_comment IS NOT NULL)) as comment_count'),
                'class.name as cls_name',
                'section.name as sec_name',
                'subject_master.name as sub_name'
            )
            ->join('class', 'class.class_id', '=', 'homework.class_id')
            ->join('section', 'section.section_id', '=', 'homework.section_id')
            ->join('subject_master', 'subject_master.sm_id', '=', 'homework.sm_id')
            ->where('homework.teacher_id', $teacher_id)
            ->where('homework.academic_yr', $acd_yr)
            ->orderBy('homework.start_date', 'desc')
            ->get();
    
        return $query->toArray();
    }
    
    function homework_join_hw_comments($homework_id)
    {
        $query = DB::table('homework as a')
            ->select(
                'a.*',
                'b.*',
                'c.first_name',
                'c.last_name',
                'c.roll_no'
            )
            ->join('homework_comments as b', 'a.homework_id', '=', 'b.homework_id')
            ->join('student as c', 'b.student_id', '=', 'c.student_id')
            ->where('a.homework_id', $homework_id)
            ->orderBy('a.homework_id', 'desc')
            ->get();
    
        return $query->toArray();
    }
    
    function get_count_of_homework_comments($homework_id)
    {
        $count = DB::table('homework_comments')
            ->where('homework_id', $homework_id)
            ->count();
    
        return $count;
    }
    
    function get_students_viewed_homework($homework_id, $class_id, $section_id, $academic_yr)
    {
        $query = DB::select("
            SELECT 
                b.roll_no, 
                b.first_name, 
                b.mid_name, 
                b.last_name, 
                1 AS read_status, 
                b.student_id
            FROM homework_read_log AS a
            JOIN student AS b ON a.parent_id = b.parent_id
            WHERE 
                a.homework_id = ? 
                AND b.class_id = ? 
                AND b.section_id = ? 
                AND b.IsDelete = 'N' 
                AND b.academic_yr = ?
    
            UNION
    
            SELECT 
                b.roll_no, 
                b.first_name, 
                b.mid_name, 
                b.last_name, 
                0 AS read_status, 
                b.student_id
            FROM student AS b
            WHERE 
                b.class_id = ? 
                AND b.section_id = ? 
                AND b.IsDelete = 'N' 
                AND b.academic_yr = ? 
                AND b.parent_id NOT IN (
                    SELECT parent_id 
                    FROM homework_read_log 
                    WHERE homework_id = ?
                )
        ", [
            $homework_id, $class_id, $section_id, $academic_yr,
            $class_id, $section_id, $academic_yr, $homework_id
        ]);
    
        return $query; 
    }
    
    function homework_updatestatus($homework_id, $data1)
    {
        DB::table('homework_comments')
            ->where('student_id', $data1['student_id'])
            ->where('homework_id', $homework_id)
            ->update($data1);
    
        return true;
    }
    

    function get_student_info($student_id, $acd_yr)
    {
        return Student::where('student_id', $student_id)
                      ->where('academic_yr', $acd_yr)
                      ->get()
                      ->toArray();
    }
    
    function get_class_name($class_id)
    {
        return DB::table('class')->where('class_id',$class_id)->value('name');
    }
    
    function get_section_name($section_id)
    {
        return DB::table('section')->where('section_id',$section_id)->value('name');
    }
    
    function get_total_stu_attendance_till_a_month($student_id,$date_from,$date_to,$acd_yr)
    {
		$query=DB::select("SELECT sum(if(attendance_status = 0, 1, 0)) as total_present_days FROM `attendance` WHERE student_id=".$student_id." and only_date>= '".$date_from."' and only_date<= '".$date_to."' and academic_yr='".$acd_yr."'");

		$result= $query;
		
        foreach($result as $r)
        {
            $total_attendance = $r->total_present_days;
        }
		//print_r($this->db->last_query());
        return $total_attendance;
    }
    
    function get_total_stu_workingday_till_a_month($student_id,$date_from,$date_to,$acd_yr)
    {
		$query=DB::select("SELECT count(*) as total_working_days FROM `attendance` WHERE student_id=".$student_id." and only_date>= '".$date_from."' and only_date<= '".$date_to."' and academic_yr='".$acd_yr."'");

		$result= $query;
		
        foreach($result as $r)
        {
            $total_working_days = $r->total_working_days;
        }
        return $total_working_days;
    }
