<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealProgrammes extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'academic_period_id', 'name', 'code', 'type', 'targeting', 'start_date', 'end_date', 'amount', 'implementer', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "meal_programmes";








private function emptyFunction() { return; }
}
