<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffCustomFormField extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "staff_custom_forms_fields";
    protected $primaryKey = 'id';
    public $incrementing = false;


    public function staffCustomField()
    {
        return $this->belongsTo(StaffCustomFields::class, 'staff_custom_field_id', 'id');
    }
}
