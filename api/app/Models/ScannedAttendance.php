<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScannedAttendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'openemis_no',
        'scanner_code',
        'datetime',
        'latitude',
        'longitude',
        'location',
        'access',
        'created_user_id',
        'created',
        'modified_user_id',
        'modified'
    ];

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = "institution_scanned";

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'openemis_no', 'openemis_no');
    }

    public function createdUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }

    public function modifiedUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }
    
}
