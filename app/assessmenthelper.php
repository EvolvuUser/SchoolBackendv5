<?php

function show_listing_of_proficiency_students_class9($class_id,$section_id,$from,$to){
		$query = DB::select("SELECT v.student_id, v.exam_id, exam_name, round(avg(v.percent)) as percentage, s.first_name,s.mid_name,s.last_name FROM view_finalmarks_percent_for_class9 as v join student as s on v.student_id = s.student_id WHERE v.class_id=".$class_id." and v.section_id=".$section_id." group by v.student_id, v.exam_id having round(avg(v.percent))>=".$from." and round(avg(v.percent))<=".$to." order by v.exam_id, percentage desc");
        return  $query;
    }
    
function show_listing_of_proficiency_students_class11($class_id,$section_id,$from,$to){
		
		$query = DB::select("SELECT v.student_id, v.exam_id,exam_name, round(avg(v.percent)) as percentage, s.first_name,s.mid_name,s.last_name FROM view_finalmarks_percent_for_class11 as v join student as s on v.student_id = s.student_id WHERE v.class_id=".$class_id." and v.section_id=".$section_id." group by v.student_id, v.exam_id having round(avg(v.percent))>=".$from." and round(avg(v.percent))<=".$to." order by v.exam_id, percentage desc");
		//print_r($this->db->last_query());
        return  $query;
    }
    
function show_listing_of_proficiency_students($class_id,$section_id,$term_id,$from,$to,$acd_yr,$max_highest_marks){
		if($acd_yr=='2020-2021'){
		    $query = DB::select("SELECT v.student_id, 'Both' as term_id, round(avg(v.percent)) as percentage, s.first_name,s.mid_name,s.last_name FROM view_totalmarks_percent as v join student as s on v.student_id = s.student_id WHERE v.class_id=".$class_id." and v.section_id=".$section_id." group by v.student_id having round(avg(v.percent))>=".$from." and round(avg(v.percent))<=".$to." order by percentage desc");
		    
		}else{
		    $query = DB::select("SELECT view_totalmarks_percent.student_id, term_id, round(view_totalmarks_percent.percent) as percentage, student.first_name,student.mid_name,student.last_name FROM `view_totalmarks_percent` join student on view_totalmarks_percent.student_id = student.student_id WHERE view_totalmarks_percent.class_id=".$class_id." and view_totalmarks_percent.section_id=".$section_id." and round(percent)>=".$from." and round(percent)<=".$to." and final_highest_total_marks=".$max_highest_marks." and term_id ='".$term_id."' order by term_id asc, percentage desc");
		}
        return  $query;
    }
function get_max_highest_marks_per_term($class_id,$section_id,$term_id)
    {
        $query = DB::select("SELECT max(final_highest_total_marks) as max_highest_marks FROM `view_totalmarks_percent` WHERE class_id =".$class_id." and section_id =".$section_id." AND term_id = ".$term_id);
        if (!empty($query)) {
        return $query[0]->max_highest_marks ?? 0;
        }
    
        return 0;
    }
    
function get_proficiency_certificate_publish_value($student_id, $term_id)
{
    $query = DB::select("
        SELECT * 
        FROM proficiency_certificate 
        WHERE student_id = ? 
        AND term_id LIKE ?
    ", [$student_id, $term_id . '%']);

    foreach ($query as $row) {
        return $row->publish;
    }
}
    
