<?php

function show_listing_of_proficiency_students_class9($class_id, $section_id, $from, $to)
{
    // $query = DB::select('SELECT v.student_id, v.exam_id, exam_name, round(avg(v.percent)) as percentage, s.first_name,s.mid_name,s.last_name FROM view_finalmarks_percent_for_class9 as v join student as s on v.student_id = s.student_id WHERE v.class_id=' . $class_id . ' and v.section_id=' . $section_id . ' group by v.student_id, v.exam_id having round(avg(v.percent))>=' . $from . ' and round(avg(v.percent))<=' . $to . ' order by v.exam_id, percentage desc');
    $query = "
    SELECT
        v.student_id,
        v.exam_id,
        v.exam_name,
        ROUND(AVG(v.percent)) AS percentage,
        s.first_name,
        s.mid_name,
        s.last_name,
        class.name as class_name , 
        section.name as section_name
    FROM
        view_finalmarks_percent_for_class9 AS v
    JOIN
        student AS s
        ON v.student_id = s.student_id
    LEFT JOIN 
        class
        ON class.class_id = s.class_id
    LEFT JOIN 
        section
        ON section.section_id = s.section_id
    WHERE
        v.class_id = {$class_id}
        AND v.section_id = {$section_id}
    GROUP BY
        v.student_id,
        v.exam_id
    HAVING
        ROUND(AVG(v.percent)) >= {$from}
        AND ROUND(AVG(v.percent)) <= {$to}
    ORDER BY
        v.exam_id,
        percentage DESC;
    ";
    return $query;
}

function show_listing_of_proficiency_students_class11($class_id, $section_id, $from, $to)
{
    $query = DB::select('SELECT v.student_id, v.exam_id,exam_name, round(avg(v.percent)) as percentage, s.first_name,s.mid_name,s.last_name FROM view_finalmarks_percent_for_class11 as v join student as s on v.student_id = s.student_id WHERE v.class_id=' . $class_id . ' and v.section_id=' . $section_id . ' group by v.student_id, v.exam_id having round(avg(v.percent))>=' . $from . ' and round(avg(v.percent))<=' . $to . ' order by v.exam_id, percentage desc');
    // print_r($this->db->last_query());
    return $query;
}

// function show_listing_of_proficiency_students($class_id, $section_id, $term_id, $from, $to, $acd_yr, $max_highest_marks)
// {
//     if ($acd_yr == '2020-2021') {
//         $query = DB::select("SELECT v.student_id, 'Both' as term_id, round(avg(v.percent)) as percentage, s.first_name,s.mid_name,s.last_name FROM view_totalmarks_percent as v join student as s on v.student_id = s.student_id WHERE v.class_id=" . $class_id . ' and v.section_id=' . $section_id . ' group by v.student_id having round(avg(v.percent))>=' . $from . ' and round(avg(v.percent))<=' . $to . ' order by percentage desc');
//     } else {
//         $query = DB::select('SELECT view_totalmarks_percent.student_id, term_id, round(view_totalmarks_percent.percent) as percentage, student.first_name,student.mid_name,student.last_name FROM `view_totalmarks_percent` join student on view_totalmarks_percent.student_id = student.student_id WHERE view_totalmarks_percent.class_id=' . $class_id . ' and view_totalmarks_percent.section_id=' . $section_id . ' and round(percent)>=' . $from . ' and round(percent)<=' . $to . ' and final_highest_total_marks=' . $max_highest_marks . " and term_id ='" . $term_id . "' order by term_id asc, percentage desc");
//     }
//     return $query;
// }

function show_listing_of_proficiency_students(
    $class_id,
    $section_id,
    $term_id,
    $from,
    $to,
    $acd_yr,
    $max_highest_marks
) {
    if ($acd_yr == '2020-2021') {

        $query = DB::select("
            SELECT
                v.student_id,
                'Both' AS term_id,
                ROUND(AVG(v.percent)) AS percentage,
                s.first_name,
                s.mid_name,
                s.last_name,
                class.name as class_name, 
                section.name as section_name
            FROM
                view_totalmarks_percent AS v
            JOIN
                student AS s
                ON v.student_id = s.student_id
            LEFT JOIN 
                class
                ON class.class_id = s.class_id
            LEFT JOIN 
                section
                ON section.section_id = s.section_id
            WHERE
                v.class_id = {$class_id}
                AND v.section_id = {$section_id}
            GROUP BY
                v.student_id
            HAVING
                ROUND(AVG(v.percent)) >= {$from}
                AND ROUND(AVG(v.percent)) <= {$to}
            ORDER BY
                percentage DESC
        ");

    } else {

        $query = DB::select("
            SELECT
                v.student_id,
                v.term_id,
                ROUND(v.percent) AS percentage,
                s.first_name,
                s.mid_name,
                s.last_name,
                class.name as class_name, 
                section.name as section_name
            FROM
                view_totalmarks_percent AS v
            JOIN
                student AS s
                ON v.student_id = s.student_id
            LEFT JOIN 
                class
                ON class.class_id = s.class_id
            LEFT JOIN 
                section
                ON section.section_id = s.section_id
            WHERE
                v.class_id = {$class_id}
                AND v.section_id = {$section_id}
                AND ROUND(v.percent) >= {$from}
                AND ROUND(v.percent) <= {$to}
                AND v.final_highest_total_marks = {$max_highest_marks}
                AND v.term_id = '{$term_id}'
            ORDER BY
                v.term_id ASC,
                percentage DESC
        ");
    }

    return $query;
}

function get_max_highest_marks_per_term($class_id, $section_id, $term_id)
{
    $query = DB::select('SELECT max(final_highest_total_marks) as max_highest_marks FROM `view_totalmarks_percent` WHERE class_id =' . $class_id . ' and section_id =' . $section_id . ' AND term_id = ' . $term_id);
    if (!empty($query)) {
        return $query[0]->max_highest_marks ?? 0;
    }

    return 0;
}

function get_proficiency_certificate_publish_value($student_id, $term_id)
{
    $query = DB::select('
        SELECT * 
        FROM proficiency_certificate 
        WHERE student_id = ? 
        AND term_id LIKE ?
    ', [$student_id, $term_id . '%']);

    foreach ($query as $row) {
        return $row->publish;
    }
}

function get_published_terms($class_id, $section_id)
{
    $query = DB::select('SELECT a.term_id, b.name FROM report_card_publish a, term b WHERE a.term_id=b.term_id and a.class_id=' . $class_id . ' and a.section_id=' . $section_id . " and a.publish='Y' order by a.term_id");
    return $query;
}

function get_subjects_by_class_section($class_id, $section_id, $acd_yr)
{
    $query = DB::select('select distinct c.sub_rc_master_id as sub_rc_master_id,c.name as name from subject as a join sub_subreportcard_mapping as b on a.sm_id=b.sm_id join subjects_on_report_card_master as c on b.sub_rc_master_id=c.sub_rc_master_id where a.class_id = ' . $class_id . ' and a.section_id = ' . $section_id . " and a.academic_yr= '" . $acd_yr . "' order by a.section_id asc,c.sequence asc");
    return $query;
}

function get_exams_by_class_per_term($class_id, $term_id, $acd_yr)
{
    $query = DB::select('SELECT DISTINCT exam.exam_id,exam.name FROM `allot_mark_headings` join exam on allot_mark_headings.exam_id = exam.exam_id WHERE class_id = ' . $class_id . ' and term_id = ' . $term_id . " AND allot_mark_headings.academic_yr = '" . $acd_yr . "' order by exam.start_date");
    return $query;
}

function get_marks($exam_id, $class_id, $section_id, $subject_id, $student_id, $acd_yr)
{
    $res = DB::table('student_marks')
        ->select('student_marks.*')
        ->where('student_marks.exam_id', $exam_id)
        // ->where('student_marks.class_id', $class_id)   // commented same as CI
        // ->where('student_marks.section_id', $section_id)
        ->where('student_marks.subject_id', $subject_id)
        ->where('student_marks.academic_yr', $acd_yr)
        ->where('student_marks.student_id', $student_id)
        ->where('student_marks.publish', 'Y')
        ->get()
        ->toArray();  // returns array of objects

    return json_decode(json_encode($res), true);  // convert to array of arrays (CI style)
}

function get_marks_headings_name_by_class_and_subject($class_id, $sm_id, $acd_yr)
{
    $query = DB::select("SELECT distinct(allot_mark_headings.marks_headings_id), marks_headings.name FROM `allot_mark_headings` JOIN marks_headings on allot_mark_headings.marks_headings_id = marks_headings.marks_headings_id WHERE class_id= '" . $class_id . "' and allot_mark_headings.sm_id = '" . $sm_id . "' and allot_mark_headings.academic_yr = '" . $acd_yr . "' order by marks_headings.sequence");
    // print_r($this->db->last_query());
    return $query;
}

function get_reportcard_remark_of_a_student($student_id, $term_id)
{
    $query = DB::select('select remark from report_card_remarks where student_id=' . $student_id . ' and term_id=' . $term_id);
    // print_r($this->db->last_query());
    $res = $query;
    foreach ($res as $row)
        return $row->remark;
}

function get_grade_based_on_marks($mark, $subject_type, $class_id)
{
    if (is_nan($mark)) {
        return '';
    }
    if (is_numeric($mark)) {
        $query = DB::select('Select name from grade where class_id=' . $class_id . " and subject_type='" . $subject_type . "' and mark_from<=" . $mark . ' and mark_upto>=' . $mark);
        // print_r($this->db->last_query());
        $res = $query;
        if (count($res) > 0) {
            foreach ($res as $row)
                return $row->name;
        } else {
            return '';
        }
    } else {
        return '';
    }
}

function get_promote_to_of_a_student($student_id, $term_id)
{
    $query = DB::select('select promot from report_card_remarks where student_id=' . $student_id . ' and term_id=' . $term_id);
    // print_r($this->db->last_query());
    $res = $query;
    foreach ($res as $row)
        return $row->promot;
}

function get_school_reopen_date($class_id, $section_id)
{
    $query = DB::select('Select * from report_card_publish WHERE class_id =' . $class_id . ' and section_id =' . $section_id);
    $res = $query;
    // print_r($this->db->last_query());
    foreach ($res as $row)
        return $row->reopen_date;
}

function get_scholastic_subject_alloted_to_class($class_id, $acd_yr)
{
    return DB::table('subjects_on_report_card')
        ->join(
            'subjects_on_report_card_master',
            'subjects_on_report_card.sub_rc_master_id',
            '=',
            'subjects_on_report_card_master.sub_rc_master_id'
        )
        ->select('subjects_on_report_card_master.*')
        ->where('subjects_on_report_card.class_id', $class_id)
        ->where('subjects_on_report_card.subject_type', 'Scholastic')
        ->where('subjects_on_report_card.academic_yr', $acd_yr)
        ->orderBy('subjects_on_report_card.class_id', 'asc')
        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
        ->get()
        ->toArray();
}

function get_coscholastic_subject_alloted_to_class($class_id, $acd_yr)
{
    return DB::table('subjects_on_report_card')
        ->join(
            'subjects_on_report_card_master',
            'subjects_on_report_card.sub_rc_master_id',
            '=',
            'subjects_on_report_card_master.sub_rc_master_id'
        )
        ->select('*')
        ->where('subjects_on_report_card.class_id', $class_id)
        // ->where('subjects_on_report_card.section_id', $section_id)
        ->where('subjects_on_report_card.subject_type', 'Co-Scholastic')
        ->where('subjects_on_report_card.academic_yr', $acd_yr)
        ->orderBy('subjects_on_report_card.class_id', 'asc')
        // ->orderBy('subjects_on_report_card.section_id', 'asc')
        ->orderBy('subjects_on_report_card_master.sequence', 'asc')
        ->get()
        ->toArray();
}

function get_published_exams_class9n10($class_id, $section_id, $acd_yr)
{
    $query = DB::select('SELECT DISTINCT exam.exam_id,exam.name FROM `report_card_publish` join exam on report_card_publish.term_id = exam.exam_id WHERE class_id = ' . $class_id . ' AND section_id = ' . $section_id . " AND report_card_publish.publish = 'Y' order by exam.start_date");
    // print_r($this->db->last_query());
    return $query;
}

function get_scholastic_subject_for_which_marks_are_alloted_to_student($student_id)
{
    $query = DB::select("SELECT distinct(a.subject_id) as sub_rc_master_id, b.name from student_marks a, subjects_on_report_card_master b, subjects_on_report_card c where a.subject_id=b.sub_rc_master_id and b.sub_rc_master_id=c.sub_rc_master_id and c.subject_type<>'Co-Scholastic' and c.class_id=a.class_id and a.student_id=" . $student_id . ' order by b.sequence');
    // echo $this->db->last_query();
    return $query;
}

function get_highestmarks_of_subject_exam_class($exam_id, $class_id, $sm_id, $acd_yr)
{
    // $query	=$this->db->query("SELECT max(highest_marks) as max_marks from allot_mark_headings where exam_id=".$exam_id." and class_id=". $class_id." and sm_id=".$sm_id." and academic_yr='".$acd_yr."'");
    $query = DB::select('SELECT sum(highest_marks) as max_marks from allot_mark_headings where exam_id=' . $exam_id . ' and class_id=' . $class_id . ' and sm_id=' . $sm_id . " and academic_yr='" . $acd_yr . "'");
    $res = $query;

    foreach ($res as $row)
        return $row->max_marks;
}

function check_cbse_rc_publish_of_a_class($class_id, $section_id)
{
    $query = DB::select('SELECT r.* FROM report_card_publish r join exam e on r.term_id = e.exam_id WHERE r.class_id = ' . $class_id . ' AND r.section_id = ' . $section_id . " AND r.publish = 'Y' and (e.name='Term 1' or e.name='Term 2' or e.name='Final Exam')");
    // print_r($this->db->last_query());
    $res = $query;
    if (count($res) > 0) {
        return 'Y';
    } else {
        return 'N';
    }
}

function check_rc_publish_of_a_class($class_id, $section_id, $term_id = '')
{
    $query = DB::table('report_card_publish')
        ->where('class_id', $class_id)
        ->where('section_id', $section_id)
        ->where('publish', 'Y');

    if ($term_id !== '') {
        $query->where('term_id', $term_id);
    }

    $res = $query->get();

    if ($res->count() > 0) {
        return 'Y';
    } else {
        return 'N';
    }
}

function get_current_exams($student_id, $acd_yr)
{
    $query = DB::table('student_marks')
        ->select(
            'student_marks.exam_id',
            'exam.name',
            'exam.open_day',
            'exam.term_id'
        )
        ->join('exam', 'student_marks.exam_id', '=', 'exam.exam_id')
        ->where('student_marks.publish', 'Y')
        ->where('student_marks.academic_yr', $acd_yr)
        ->where('student_marks.student_id', $student_id)
        ->distinct()
        ->orderBy('exam.start_date', 'asc')
        ->get();

    return $query->toArray();
}

function get_reportcard_publish_value($class_id, $section_id, $term_id = '')
{
    $query = DB::table('report_card_publish')
        ->where('class_id', $class_id)
        ->where('section_id', $section_id);

    if ($term_id != '') {
        $query->where('term_id', $term_id);
    }

    $res = $query->get();

    foreach ($res as $row) {
        return $row->publish;
    }
}

function get_domain_master_by_class_id($class_id)
{
    $query = DB::select('SELECT * from domain_master where class_id=' . $class_id);
    return $query;
}

function get_parameter_by_dm_id($dm_id)
{
    $query = DB::select('SELECT * from domain_parameter_details where dm_id=' . $dm_id);
    return $query;
}

function get_published_domain_parameter_value_by_id($student_id, $dd_id, $term_id, $acd_yr)
{
    $query = DB::select('Select parameter_value from student_domain_details where student_id=' . $student_id . ' and term_id=' . $term_id . ' and parameter_id=' . $dd_id . " and academic_yr='" . $acd_yr . "' and publish='Y'");
    $res = $query;
    // print_r($this->db->last_query());
    foreach ($res as $row)
        return $row->parameter_value;
}

function get_activity_alloted_to_class($class_id, $acd_yr)
{
    $query = DB::table('subjects_on_report_card')
        ->select('*')
        ->join(
            'subjects_on_report_card_master',
            'subjects_on_report_card.sub_rc_master_id',
            '=',
            'subjects_on_report_card_master.sub_rc_master_id'
        )
        ->where('subjects_on_report_card.class_id', $class_id)
        // ->where('subjects_on_report_card.section_id', $section_id); // Lija for report card
        ->where('subjects_on_report_card.subject_type', 'Activity')
        ->where('subjects_on_report_card.academic_yr', $acd_yr)
        ->orderBy('subjects_on_report_card.class_id', 'asc')
        // ->orderBy('subjects_on_report_card.section_id', 'asc'); // Lija report card
        ->orderBy('subjects_on_report_card_master.sequence', 'asc');

    return $query->get()->toArray();
}

function get_exam_for_which_marks_available($class_id, $section_id, $student_id, $subject_type)
{
    $query = DB::table('student_marks')
        ->select(DB::raw('DISTINCT(student_marks.exam_id) as exam_id'), 'exam.name')
        ->join('exam', 'student_marks.exam_id', '=', 'exam.exam_id')
        ->join(
            'subjects_on_report_card',
            'student_marks.subject_id',
            '=',
            'subjects_on_report_card.sub_rc_master_id'
        )
        ->join('report_card_publish', function ($join) {
            $join
                ->on('student_marks.class_id', '=', 'report_card_publish.class_id')
                ->on('student_marks.section_id', '=', 'report_card_publish.section_id')
                ->on('student_marks.exam_id', '=', 'report_card_publish.term_id');
        })
        ->where('student_marks.class_id', $class_id)
        ->where('student_marks.section_id', $section_id)
        ->where('student_marks.student_id', $student_id)
        ->where('subjects_on_report_card.subject_type', $subject_type)
        ->where('subjects_on_report_card.class_id', $class_id)
        ->where('student_marks.publish', 'Y')
        ->where('report_card_publish.publish', 'Y')
        ->orderBy('student_marks.exam_id', 'asc');  // Lija 28-02-25

    return $query->get()->toArray();
}

function parent_feedback_parameter($class_id)
{
    $query = DB::select('SELECT * from parent_feedback_master where class_id=' . $class_id);
    return $query;
}

function get_published_parent_feedback_parameter_value_by_id($student_id, $pfm_id, $term_id, $acd_yr)
{
    $query = DB::select('Select parameter_value from parent_feedback where student_id=' . $student_id . ' and term_id=' . $term_id . ' and pfm_id=' . $pfm_id . " and publish='Y' and academic_yr='" . $acd_yr . "'");
    $res = $query;
    // print_r($this->db->last_query());
    foreach ($res as $row)
        return $row->parameter_value;
}

function peer_assessment_parameter($class_id)
{
    $query = DB::select('SELECT * from peer_assessment_master where class_id=' . $class_id);
    return $query;
}

function get_published_peer_assessment_parameter_value_by_id($student_id, $pam_id, $term_id, $acd_yr)
{
    $query = DB::select('Select parameter_value from peer_assessment where student_id=' . $student_id . ' and term_id=' . $term_id . ' and pam_id=' . $pam_id . " and academic_yr='" . $acd_yr . "' and publish='Y'");
    $res = $query;
    foreach ($res as $row)
        return $row->parameter_value;
}

function get_self_assessment_master($class_id)
{
    $query = DB::select('SELECT * from self_assessment_master where class_id=' . $class_id);
    return $query;
}

function get_published_self_assessment_parameter_value_by_id($student_id, $sam_id, $term_id, $acd_yr)
{
    $query = DB::select('Select parameter_value from self_assessment where student_id=' . $student_id . ' and term_id=' . $term_id . ' and sam_id=' . $sam_id . " and academic_yr='" . $acd_yr . "' and publish='Y'");
    $res = $query;
    foreach ($res as $row)
        return $row->parameter_value;
}

function get_term_of_exam($exam_id)
{
    $res = DB::table('exam')
        ->where('exam_id', $exam_id)
        ->get()
        ->toArray();

    foreach ($res as $row) {
        return $row->term_id;
    }
}
