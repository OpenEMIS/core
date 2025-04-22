<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityGroup extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_groups";


    public function institutions()
    {
        return $this->hasMany(SecurityGroupInstitutions::class, 'security_group_id', 'id');
    }
}
