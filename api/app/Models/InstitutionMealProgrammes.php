<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionMealProgrammes extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'academic_period_id', 'meal_programmes_id', 'institution_id', 'date_received', 'quantity_received', 'delivery_status_id', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'meal_rating_id', 'academic_period_id', 'meal_programmes_id', 'institution_id', 'delivery_status_id', 'modified_user_id', 'created_user_id', 'meal_rating_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_meal_programmes";








private function emptyFunction() { return; }
}
