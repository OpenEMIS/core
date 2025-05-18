<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalForm extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "appraisal_forms";

    protected $appends = ['code_name'];


    public function getCodeNameAttribute()
    {
        return $this->attributes['code']. ' - ' .$this->attributes['name'];
    }
}
