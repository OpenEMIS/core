<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingResultType extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "training_result_types";
}
