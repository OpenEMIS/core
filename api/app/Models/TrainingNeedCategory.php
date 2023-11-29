<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingNeedCategory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "training_need_categories";
}
