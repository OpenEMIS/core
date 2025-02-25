<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyForms extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "survey_forms";


    public function customModule()
    {
        return $this->belongsTo(CustomModules::class, 'custom_module_id', 'id');
    }
}
