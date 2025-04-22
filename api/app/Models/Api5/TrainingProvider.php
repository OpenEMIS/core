<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProvider extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "training_providers";

}
