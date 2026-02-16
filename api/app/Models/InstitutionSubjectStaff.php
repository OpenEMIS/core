<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\UuidId;

class InstitutionSubjectStaff extends Model
{
    use HasFactory;
use InstitutionScope;
    use UuidId;


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['id', 'start_date', 'end_date', 'staff_id', 'institution_id', 'institution_subject_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'institution_id', 'institution_subject_id', 'modified_user_id', 'created_user_id'];

    // ✅ Define the primary key
    protected $table = "institution_subject_staff";

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    public $incrementing = false;


    protected $dates = ['modified', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $casts = [
        'id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }








    public function staff()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function institutionSubject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'institution_subject_id', 'id');
    }

}
