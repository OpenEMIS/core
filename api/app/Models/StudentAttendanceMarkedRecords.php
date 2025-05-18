<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class StudentAttendanceMarkedRecords extends Model
{
    use HasFactory;
use InstitutionScope;

    // ✅ Allow mass assignment
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'period', 'subject_id', 'no_scheduled_class', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'subject_id'];
    protected $table = "student_attendance_marked_records";
    protected $primaryKey = ["institution_id", "academic_period_id", "institution_class_id", "education_grade_id", "date", "period", "subject_id"];


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];









    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    // Override setKeysForSaveQuery to handle composite keys
    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }


}
