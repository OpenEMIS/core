<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityRoleFunction extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_role_functions";
}
