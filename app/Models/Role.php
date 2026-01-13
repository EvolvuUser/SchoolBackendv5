<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role_master';
    protected $primaryKey = 'role_id';
    public $incrementing = false;
    protected $fillable = ['role_id', 'name', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
