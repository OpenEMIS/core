<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonRooms extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'institution_schedule_lesson_detail_id', 'institution_room_id', 'institution_schedule_lesson_detail_id', 'institution_room_id'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








    public function lesson()
    {
        return $this->belongsTo(InstitutionScheduleLessonDetails::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function institution_room()
    {
        return $this->belongsTo(InstitutionRooms::class, 'institution_room_id', 'id');
    }
}
