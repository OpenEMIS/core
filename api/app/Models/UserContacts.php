<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContacts extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "user_contacts";

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'security_user_id'); // Use 'security_user_id' as the foreign key
    }
}
