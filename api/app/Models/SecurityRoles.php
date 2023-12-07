<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityRoles extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_roles";

    public function roleFunctions()
    {
        return $this->hasMany(SecurityRoleFunction::class, 'security_role_id', 'id');
    }
}
