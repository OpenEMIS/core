<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonRooms extends Model
{
    use HasFactory;

    public function lesson()
    {
        return $this->belongsTo(InstitutionScheduleLessonDetails::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function institutionRooms()
    {
        return $this->belongsTo(InstitutionRooms::class, 'institution_room_id', 'id');
    }
}