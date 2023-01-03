<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionPositions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_positions";

    public function staffPositionTitle()
    {
        return $this->belongsTo(StaffPositionTitles::class, 'staff_position_title_id', 'id');
    }


    public function staffPositionGrades()
    {
        return $this->belongsTo(StaffPositionGrades::class, 'staff_position_grade_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }
}
