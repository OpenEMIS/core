<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "training_courses";

    protected $appends = ['code_name'];


    public function getCodeNameAttribute()
    {
        return $this->attributes['code']. ' - ' .$this->attributes['name'];
    }
}
