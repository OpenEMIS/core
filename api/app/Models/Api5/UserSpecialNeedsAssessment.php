<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSpecialNeedsAssessment extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "user_special_needs_assessments";

}
