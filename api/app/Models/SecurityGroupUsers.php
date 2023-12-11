<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityGroupUsers extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_group_users";


    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'security_user_id', 'id');
    }


    public function securityGroup()
    {
        return $this->belongsTo(SecurityGroup::class, 'security_group_id', 'id');
    }


    public function securityRole()
    {
        return $this->belongsTo(SecurityRoles::class, 'security_role_id', 'id');
    }
}
