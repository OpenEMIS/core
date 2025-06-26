<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionScheduleTerms extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'start_date', 'end_date', 'academic_period_id', 'institution_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'institution_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








private function emptyFunction() { return; }
}
