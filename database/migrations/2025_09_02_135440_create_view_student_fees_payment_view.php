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
        DB::statement("CREATE VIEW `view_student_fees_payment` AS select concat(`a`.`student_id`,'^',`b`.`installment`) AS `student_installment`,`a`.`student_id` AS `student_id`,`a`.`fee_allotment_id` AS `fee_allotment_id`,`b`.`installment` AS `installment`,`a`.`academic_yr` AS `academic_yr`,`c`.`class_id` AS `class_id`,sum(`b`.`amount`) AS `fees_paid` from ((`u333015459_arnoldstest`.`fees_payment_record` `a` join `u333015459_arnoldstest`.`fees_payment_detail` `b`) join `u333015459_arnoldstest`.`student` `c`) where `a`.`fees_payment_id` = `b`.`fees_payment_id` and `a`.`student_id` = `c`.`student_id` and `c`.`IsDelete` = 'N' and `a`.`isCancel` = 'N' group by `a`.`student_id`,`b`.`installment` union select concat(`d`.`student_id`,'^',`e`.`installment`) AS `student_installment`,`d`.`student_id` AS `student_id`,`d`.`fee_allotment_id` AS `fee_allotment_id`,`e`.`installment` AS `installment`,`d`.`academic_yr` AS `academic_yr`,`f`.`class_id` AS `class_id`,sum(`e`.`amount`) AS `fees_paid` from ((`u333015459_arnoldstest`.`onlinefees_payment_record` `d` join `u333015459_arnoldstest`.`onlinefees_payment_detail` `e`) join `u333015459_arnoldstest`.`student` `f`) where `d`.`fees_payment_id` = `e`.`fees_payment_id` and `d`.`student_id` = `f`.`student_id` and `f`.`IsDelete` = 'N' and `d`.`isCancel` = 'N' group by `d`.`student_id`,`e`.`installment`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_student_fees_payment`");
    }
};
