<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateTime extends Model
{
    protected $table = 'late_time';
    protected $primaryKey = 'lt_id'; 
    protected $fillable = ['late_time', 'tc_id'];
}
