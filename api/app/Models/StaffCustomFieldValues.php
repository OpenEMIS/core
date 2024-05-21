<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffCustomFieldValues extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "staff_custom_field_values";
    protected $primaryKey = 'id';
    public $incrementing = false;
}
