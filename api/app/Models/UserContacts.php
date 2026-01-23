<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContacts extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'contact_type_id', 'value', 'preferred', 'security_user_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'contact_type_id', 'security_user_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "user_contacts";








    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'security_user_id'); // Use 'security_user_id' as the foreign key
    }
}
