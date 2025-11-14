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
        DB::statement("CREATE VIEW `view_fees_payment_record` AS select `u333015459_arnoldstest`.`fees_payment_record`.`fees_payment_id` AS `fees_payment_id`,`u333015459_arnoldstest`.`fees_payment_record`.`student_id` AS `student_id`,`u333015459_arnoldstest`.`fees_payment_record`.`payment_date` AS `payment_date`,`u333015459_arnoldstest`.`fees_payment_record`.`amount` AS `amount`,`u333015459_arnoldstest`.`fees_payment_record`.`concession` AS `concession`,`u333015459_arnoldstest`.`fees_payment_record`.`payment_amount` AS `payment_amount`,`u333015459_arnoldstest`.`fees_payment_record`.`payment_mode` AS `payment_mode`,`u333015459_arnoldstest`.`fees_payment_record`.`cheque_no` AS `cheque_no`,`u333015459_arnoldstest`.`fees_payment_record`.`bank_name` AS `bank_name`,`u333015459_arnoldstest`.`fees_payment_record`.`fee_allotment_id` AS `fee_allotment_id`,`u333015459_arnoldstest`.`fees_payment_record`.`receipt_no` AS `receipt_no`,`u333015459_arnoldstest`.`fees_payment_record`.`dataentry_by` AS `dataentry_by`,`u333015459_arnoldstest`.`fees_payment_record`.`isCancel` AS `isCancel`,`u333015459_arnoldstest`.`fees_payment_record`.`academic_yr` AS `academic_yr` from `u333015459_arnoldstest`.`fees_payment_record` union select `u333015459_arnoldstest`.`onlinefees_payment_record`.`fees_payment_id` AS `fees_payment_id`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`student_id` AS `student_id`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`payment_date` AS `payment_date`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`amount` AS `amount`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`concession` AS `concession`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`payment_amount` AS `payment_amount`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`payment_mode` AS `payment_mode`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`cheque_no` AS `cheque_no`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`bank_name` AS `bank_name`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`fee_allotment_id` AS `fee_allotment_id`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`receipt_no` AS `receipt_no`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`dataentry_by` AS `dataentry_by`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`isCancel` AS `isCancel`,`u333015459_arnoldstest`.`onlinefees_payment_record`.`academic_yr` AS `academic_yr` from `u333015459_arnoldstest`.`onlinefees_payment_record`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_fees_payment_record`");
    }
};
