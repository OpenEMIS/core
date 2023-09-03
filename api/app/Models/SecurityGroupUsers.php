<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityGroupUsers extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_group_users";
}
