<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionRoomTypes extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'room_type', 'total_rooms', 'academic_period_id', 'institution_id'];

    public $timestamps = false;
    protected $table = "summary_institution_room_types";


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    // ✅ Define the primary key
    public $incrementing = false;
    protected $primaryKey = null;








private function emptyFunction() { return; }
}
