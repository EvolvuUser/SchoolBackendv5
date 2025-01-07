<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveAllocation extends Model
{
    protected $table= 'leave_allocation';
    protected $fillable = ['staff_id','leave_type_id','leaves_allocated','academic_yr'];
}
