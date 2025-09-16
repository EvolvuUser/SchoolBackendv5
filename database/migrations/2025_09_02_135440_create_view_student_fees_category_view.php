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
        DB::statement("CREATE VIEW `view_student_fees_category` AS select concat(`a`.`student_id`,'^',`b`.`installment`) AS `student_installment`,`a`.`student_id` AS `student_id`,`b`.`installment` AS `installment`,`b`.`installment_fees` AS `installment_fees`,`b`.`category_name` AS `fees_category_name`,`b`.`fee_allotment_id` AS `fee_allotment_id`,`e`.`first_name` AS `first_name`,`e`.`last_name` AS `last_name`,`e`.`roll_no` AS `roll_no`,`e`.`section_id` AS `section_id`,`e`.`class_id` AS `class_id`,`b`.`due_date` AS `due_date`,`a`.`academic_yr` AS `academic_yr` from ((`u333015459_arnoldstest`.`fees_student_category` `a` join `u333015459_arnoldstest`.`view_fee_allotment` `b`) join `u333015459_arnoldstest`.`student` `e`) where `a`.`fees_category_id` = `b`.`fees_category_id` and `a`.`student_id` = `e`.`student_id` and `a`.`academic_yr` = `b`.`academic_yr` and `b`.`academic_yr` = `e`.`academic_yr` and `b`.`installment_fees` <> 0 and `e`.`IsDelete` = 'N' group by `a`.`student_id`,`b`.`installment`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_student_fees_category`");
    }
};
