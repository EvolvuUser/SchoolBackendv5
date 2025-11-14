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
        DB::statement("CREATE VIEW `view_attendance_monthwise` AS select `a`.`student_id` AS `student_id`,`a`.`class_id` AS `class_id`,`a`.`section_id` AS `section_id`,`a`.`academic_yr` AS `academic_yr`,if(date_format(`a`.`only_date`,'%m') = '01',count(0),NULL) AS `jan`,if(date_format(`a`.`only_date`,'%m') = '02',count(0),NULL) AS `feb`,if(date_format(`a`.`only_date`,'%m') = '03',count(0),NULL) AS `mar`,if(date_format(`a`.`only_date`,'%m') = '04',count(0),NULL) AS `apr`,if(date_format(`a`.`only_date`,'%m') = '05',count(0),NULL) AS `may`,if(date_format(`a`.`only_date`,'%m') = '06',count(0),NULL) AS `jun`,if(date_format(`a`.`only_date`,'%m') = '07',count(0),NULL) AS `jul`,if(date_format(`a`.`only_date`,'%m') = '08',count(0),NULL) AS `aug`,if(date_format(`a`.`only_date`,'%m') = '09',count(0),NULL) AS `sep`,if(date_format(`a`.`only_date`,'%m') = '10',count(0),NULL) AS `oct`,if(date_format(`a`.`only_date`,'%m') = '11',count(0),NULL) AS `nov`,if(date_format(`a`.`only_date`,'%m') = '12',count(0),NULL) AS `decm` from `u333015459_arnoldstest`.`attendance` `a` where `a`.`attendance_status` = 0 group by `a`.`student_id`,date_format(`a`.`only_date`,'%m')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `view_attendance_monthwise`");
    }
};
