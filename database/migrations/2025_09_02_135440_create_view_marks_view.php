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
        DB::statement("CREATE VIEW `view_marks` AS select `a`.`mark_id` AS `mark_id`,`a`.`class_id` AS `class_id`,`a`.`section_id` AS `section_id`,`a`.`exam_id` AS `exam_id`,`a`.`subject_id` AS `subject_id`,`a`.`marks_headings_id` AS `marks_headings_id`,`a`.`date` AS `date`,`a`.`academic_yr` AS `academic_yr`,`c`.`name` AS `exam_name`,`b`.`student_id` AS `student_id`,`b`.`percent` AS `percent` from (((`u333015459_arnoldstest`.`mark_master` `a` join `u333015459_arnoldstest`.`mark_detail` `b`) join `u333015459_arnoldstest`.`exam` `c`) join `u333015459_arnoldstest`.`marks_headings` `d`) where `a`.`mark_id` = `b`.`mark_id` and `a`.`exam_id` = `c`.`exam_id` and `a`.`marks_headings_id` = `d`.`marks_headings_id` and `d`.`written_exam` = 'Y'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_marks`");
    }
};
