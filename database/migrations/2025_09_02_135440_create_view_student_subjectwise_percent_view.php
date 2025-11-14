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
        DB::statement("CREATE VIEW `view_student_subjectwise_percent` AS select `a`.`student_id` AS `student_id`,`a`.`subject_id` AS `subject_id`,sum(`a`.`total_marks`) AS `sum(a.total_marks)`,sum(`a`.`highest_total_marks`) AS `sum(a.highest_total_marks)`,round(sum(`a`.`total_marks`) / sum(`a`.`highest_total_marks`) * 100,0) AS `sub_percent` from ((`u333015459_arnoldstest`.`student_marks` `a` join `u333015459_arnoldstest`.`subjects_on_report_card` `b`) join `u333015459_arnoldstest`.`student` `c`) where `a`.`subject_id` = `b`.`sub_rc_master_id` and `a`.`student_id` = `c`.`student_id` and `b`.`class_id` = `c`.`class_id` and `b`.`subject_type` = 'Scholastic' group by `a`.`student_id`,`a`.`subject_id`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_student_subjectwise_percent`");
    }
};
