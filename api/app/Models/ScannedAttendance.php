<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScannedAttendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'openemis_no',
        'date',
        'time',
        'latitude',
        'longitude',
        'created_user_id',
        'created'
    ];

    protected $primaryKey = 'code';
    public $timestamps = false;
    protected $table = "scanned_attendances";

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'openemis_no', 'openemis_no');
    }
}
