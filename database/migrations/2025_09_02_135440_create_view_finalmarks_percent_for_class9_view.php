<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE VIEW `view_finalmarks_percent_for_class9` AS select `sm`.`student_id` AS `student_id`,`sm`.`class_id` AS `class_id`,`sm`.`section_id` AS `section_id`,`u333015459_arnoldstest`.`exam`.`exam_id` AS `exam_id`,`u333015459_arnoldstest`.`exam`.`name` AS `exam_name`,sum(`sm`.`total_marks`) AS `final_total_marks`,sum(`sm`.`highest_total_marks`) AS `final_highest_total_marks`,round(sum(`sm`.`total_marks`) / sum(`sm`.`highest_total_marks`) * 100,2) AS `percent`,`sm`.`academic_yr` AS `academic_yr` from (((`u333015459_arnoldstest`.`student_marks` `sm` join `u333015459_arnoldstest`.`subjects_on_report_card` `sb` on(`sm`.`subject_id` = `sb`.`sub_rc_master_id`)) join `u333015459_arnoldstest`.`exam` on(`sm`.`exam_id` = `u333015459_arnoldstest`.`exam`.`exam_id`)) join `u333015459_arnoldstest`.`class` `c` on(`sm`.`class_id` = `c`.`class_id`)) where `sm`.`class_id` = `sb`.`class_id` and `sb`.`subject_type` = 'Scholastic' and `sm`.`publish` = 'Y' and (`u333015459_arnoldstest`.`exam`.`name` like 'Final Exam%' or `u333015459_arnoldstest`.`exam`.`name` like 'Term 1%' or `u333015459_arnoldstest`.`exam`.`name` like 'Term 2%') and `c`.`name` = '9' group by `sm`.`student_id`,`u333015459_arnoldstest`.`exam`.`exam_id`,`sm`.`academic_yr`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_finalmarks_percent_for_class9`");
    }
};
