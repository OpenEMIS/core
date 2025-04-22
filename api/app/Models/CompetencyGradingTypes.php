<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyGradingTypes extends Model
{
    use HasFactory;
    public $incrementing = true;
    public $fillable = ['id', 'code', 'name', 'modified_user_id', 'modified', 'created_user_id', 'created'];
    public $timestamps = false;
    protected $table = "competency_grading_types";
    protected $dates = ['modified', 'created'];


private function emptyFunction() { return; }
}
