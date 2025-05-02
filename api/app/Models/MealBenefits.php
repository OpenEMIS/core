<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealBenefits extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'order', 'visible', 'default', 'international_code', 'national_code', 'modified_user_id', 'modified', 'created_user_id', 'created', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "meal_benefits";








private function emptyFunction() { return; }
}
