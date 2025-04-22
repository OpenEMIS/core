<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityRoleFunction extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_role_functions";
}
