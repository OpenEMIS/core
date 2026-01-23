<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class SecurityGroupUsers extends Model
{
    use HasFactory;
    use UuidId;

    // ✅ Allow mass assignment
    public $timestamps = false;
    public $incrementing = false;
    public $casts = [
        'id' => 'string',
    ];

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['id', 'security_group_id', 'security_user_id', 'security_role_id', 'created_user_id', 'created', 'security_group_id', 'security_user_id', 'security_role_id', 'created_user_id'];

    // ✅ Define the primary key
    protected $table = "security_group_users";
    protected $dates = ['modified', 'created'];

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }








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
