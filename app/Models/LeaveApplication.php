<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $table = 'leave_application';
    protected $fillable = ['staff_id','leave_type_id','leave_start_date','leave_end_date','no_of_days','reason','status','academic_yr'];
}
