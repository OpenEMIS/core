<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionMealStudents extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_id', 'academic_period_id', 'institution_class_id', 'institution_id', 'meal_programmes_id', 'date', 'meal_benefit_id', 'meal_received_id', 'paid', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'academic_period_id', 'institution_class_id', 'institution_id', 'meal_programmes_id', 'meal_benefit_id', 'meal_received_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_meal_students";








private function emptyFunction() { return; }
}
