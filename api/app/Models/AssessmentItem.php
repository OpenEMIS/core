<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "assessment_items";
}
