<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class InstitutionClassGrades extends Model
{
    use HasFactory;
    use UuidId;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'institution_class_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_class_id', 'education_grade_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $table = "institution_class_grades";
    protected $primaryKey = 'id';

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








    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }
}
