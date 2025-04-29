<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionRooms extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'start_date', 'start_year', 'end_date', 'end_year', 'accessibility', 'comment', 'room_type_id', 'room_status_id', 'institution_floor_id', 'institution_id', 'infrastructure_condition_id', 'area', 'previous_institution_room_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'room_type_id', 'room_status_id', 'institution_floor_id', 'institution_id', 'infrastructure_condition_id', 'previous_institution_room_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








private function emptyFunction() { return; }
}
