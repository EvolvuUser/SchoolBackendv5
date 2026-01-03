<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTodo extends Model
{
    use HasFactory;
    public $table = 'daily_todos';
    protected $fillable = [
        'title',
        'description',
        'login_type',
        'reg_id',
        'is_completed',
        'due_date'
    ];
}
