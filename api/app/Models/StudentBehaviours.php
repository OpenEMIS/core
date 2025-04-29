<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class StudentBehaviours extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'description', 'action', 'date_of_behaviour', 'time_of_behaviour', 'academic_period_id', 'student_id', 'institution_id', 'status_id', 'student_behaviour_category_id', 'assignee_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_behaviour_classification_id', 'academic_period_id', 'student_id', 'institution_id', 'status_id', 'student_behaviour_category_id', 'assignee_id', 'modified_user_id', 'created_user_id', 'student_behaviour_classification_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "student_behaviours";








    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }
}
