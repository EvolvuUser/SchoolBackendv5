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
        DB::statement("CREATE VIEW `view_fee_allotment` AS select `b`.`fee_allotment_id` AS `fee_allotment_id`,`b`.`fees_category_id` AS `fees_category_id`,`b`.`fees` AS `fees`,`b`.`academic_yr` AS `academic_yr`,`c`.`installment` AS `installment`,`c`.`due_date` AS `due_date`,`c`.`installment_fees` AS `installment_fees`,`d`.`name` AS `category_name` from ((`u333015459_arnoldstest`.`fees_allotment` `b` join `u333015459_arnoldstest`.`fees_allotment_detail` `c`) join `u333015459_arnoldstest`.`fees_category` `d`) where `b`.`fee_allotment_id` = `c`.`fee_allotment_id` and `b`.`fees_category_id` = `d`.`fees_category_id` group by `c`.`fee_allotment_id`,`c`.`installment`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_fee_allotment`");
    }
};
