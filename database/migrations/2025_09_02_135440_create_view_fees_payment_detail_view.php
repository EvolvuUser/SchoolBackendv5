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
        DB::statement("CREATE VIEW `view_fees_payment_detail` AS select `u333015459_arnoldstest`.`fees_payment_detail`.`fees_payment_id` AS `fees_payment_id`,`u333015459_arnoldstest`.`fees_payment_detail`.`installment` AS `installment`,`u333015459_arnoldstest`.`fees_payment_detail`.`fee_type_id` AS `fee_type_id`,`u333015459_arnoldstest`.`fees_payment_detail`.`amount` AS `amount`,`u333015459_arnoldstest`.`fees_payment_detail`.`academic_yr` AS `academic_yr` from `u333015459_arnoldstest`.`fees_payment_detail` union select `u333015459_arnoldstest`.`onlinefees_payment_detail`.`fees_payment_id` AS `fees_payment_id`,`u333015459_arnoldstest`.`onlinefees_payment_detail`.`installment` AS `installment`,`u333015459_arnoldstest`.`onlinefees_payment_detail`.`fee_type_id` AS `fee_type_id`,`u333015459_arnoldstest`.`onlinefees_payment_detail`.`amount` AS `amount`,`u333015459_arnoldstest`.`onlinefees_payment_detail`.`academic_yr` AS `academic_yr` from `u333015459_arnoldstest`.`onlinefees_payment_detail`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_fees_payment_detail`");
    }
};
