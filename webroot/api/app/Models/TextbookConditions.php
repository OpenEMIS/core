<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextbookConditions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "textbook_conditions";

}