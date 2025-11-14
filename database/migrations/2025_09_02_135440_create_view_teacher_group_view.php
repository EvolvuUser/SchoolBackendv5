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
        DB::statement("CREATE VIEW `view_teacher_group` AS select distinct `s`.`teacher_id` AS `teacher_id`,`t`.`name` AS `name`,'Preprimary' AS `teacher_group`,`s`.`academic_yr` AS `academic_yr` from ((`u333015459_arnoldstest`.`subject` `s` join `u333015459_arnoldstest`.`teacher` `t`) join `u333015459_arnoldstest`.`class` `c`) where `s`.`teacher_id` = `t`.`teacher_id` and `s`.`class_id` = `c`.`class_id` and (`c`.`name` = 'Nursery' or `c`.`name` = 'LKG' or `c`.`name` = 'UKG') union select distinct `s`.`teacher_id` AS `teacher_id`,`t`.`name` AS `name`,'Primary' AS `teacher_group`,`s`.`academic_yr` AS `academic_yr` from ((`u333015459_arnoldstest`.`subject` `s` join `u333015459_arnoldstest`.`teacher` `t`) join `u333015459_arnoldstest`.`class` `c`) where `s`.`teacher_id` = `t`.`teacher_id` and `s`.`class_id` = `c`.`class_id` and (`c`.`name` = '1' or `c`.`name` = '2' or `c`.`name` = '3' or `c`.`name` = '4') union select distinct `s`.`teacher_id` AS `teacher_id`,`t`.`name` AS `name`,'Secondary' AS `teacher_group`,`s`.`academic_yr` AS `academic_yr` from ((`u333015459_arnoldstest`.`subject` `s` join `u333015459_arnoldstest`.`teacher` `t`) join `u333015459_arnoldstest`.`class` `c`) where `s`.`teacher_id` = `t`.`teacher_id` and `s`.`class_id` = `c`.`class_id` and (`c`.`name` = '5' or `c`.`name` = '6' or `c`.`name` = '7' or `c`.`name` = '8') union select distinct `s`.`teacher_id` AS `teacher_id`,`t`.`name` AS `name`,'Highschool' AS `teacher_group`,`s`.`academic_yr` AS `academic_yr` from ((`u333015459_arnoldstest`.`subject` `s` join `u333015459_arnoldstest`.`teacher` `t`) join `u333015459_arnoldstest`.`class` `c`) where `s`.`teacher_id` = `t`.`teacher_id` and `s`.`class_id` = `c`.`class_id` and (`c`.`name` = '9' or `c`.`name` = '10' or `c`.`name` = '11' or `c`.`name` = '12')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_teacher_group`");
    }
};
