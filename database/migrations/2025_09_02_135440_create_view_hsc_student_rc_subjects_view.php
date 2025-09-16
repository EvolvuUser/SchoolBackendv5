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
        DB::statement("CREATE VIEW `view_hsc_student_rc_subjects` AS select `a`.`student_id` AS `student_id`,`c`.`sub_rc_master_id` AS `sub_rc_master_id`,'Compulsary' AS `subject_type` from ((`u333015459_arnoldstest`.`subjects_higher_secondary_studentwise` `a` join `u333015459_arnoldstest`.`subject_group_details` `b`) join `u333015459_arnoldstest`.`sub_subreportcard_mapping` `c`) where `a`.`sub_group_id` = `b`.`sub_group_id` and `b`.`sm_hsc_id` = `c`.`sm_id` union select `a`.`student_id` AS `student_id`,`b`.`sub_rc_master_id` AS `sub_rc_master_id`,'Optional' AS `subject_type` from (`u333015459_arnoldstest`.`subjects_higher_secondary_studentwise` `a` join `u333015459_arnoldstest`.`sub_subreportcard_mapping` `b`) where `a`.`opt_subject_id` = `b`.`sm_id` union select `a`.`student_id` AS `student_id`,`b`.`sub_rc_master_id` AS `sub_rc_master_id`,'Co-scholastic_hsc' AS `subject_type` from (`u333015459_arnoldstest`.`student` `a` join `u333015459_arnoldstest`.`subjects_on_report_card` `b`) where `a`.`class_id` = `b`.`class_id` and `a`.`IsDelete` = 'N' and `b`.`subject_type` = 'Co-scholastic_hsc'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_hsc_student_rc_subjects`");
    }
};
