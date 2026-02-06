<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\UuidId;

class InstitutionSubjectStudents extends Model
{
    use UuidId;
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id',
        'total_mark',
        'outcome_result',
        'student_id',
        'institution_subject_id',
        'institution_class_id',
        'institution_id',
        'academic_period_id',
        'education_subject_id',
        'education_grade_id',
        'student_status_id',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    public $incrementing = false;
    protected $table = "institution_subject_students";
    protected $casts = [
        'id' => 'string',
    ];
    protected $primaryKey = ['student_id',
        'institution_class_id',
        'institution_id',
        'academic_period_id',
        'education_subject_id',
        'education_grade_id'];
    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }









    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    public function studentStatus()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }

    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }

    public function class()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }
}
