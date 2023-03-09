<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityUserCode extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "security_user_codes";

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'security_user_id', 'id');
    }
}
