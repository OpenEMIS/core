<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleCurriculumLessons extends Model
{
    use HasFactory;

    public function institution_subject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'institution_subject_id', 'id');
    }
    
}
