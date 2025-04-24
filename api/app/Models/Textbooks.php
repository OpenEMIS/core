<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Textbooks extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'title', 'author', 'publisher', 'year_published', 'ISBN', 'expiry_date', 'academic_period_id', 'education_grade_id', 'education_subject_id', 'textbook_dimension_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'education_grade_id', 'education_subject_id', 'textbook_dimension_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "textbooks";








private function emptyFunction() { return; }
}
