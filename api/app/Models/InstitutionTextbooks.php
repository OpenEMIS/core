<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionTextbooks extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'comment', 'textbook_status_id', 'textbook_condition_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'education_subject_id', 'security_user_id', 'textbook_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'textbook_status_id', 'textbook_condition_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'education_subject_id', 'security_user_id', 'textbook_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'institution_textbooks';








private function emptyFunction() { return; }
}
